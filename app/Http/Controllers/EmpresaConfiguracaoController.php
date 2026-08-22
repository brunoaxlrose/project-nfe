<?php

namespace App\Http\Controllers;

use App\Exceptions\NfeEmissionException;
use App\Models\ConfiguracaoEmissor;
use App\Models\Empresa;
use App\Services\SefazCommunicationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class EmpresaConfiguracaoController extends Controller
{
    public function __construct(private readonly SefazCommunicationService $sefaz)
    {
    }

    public function show(): JsonResponse
    {
        $config = ConfiguracaoEmissor::current();
        $data = $config->makeHidden([
            'certificado_base64',
            'certificado_senha',
            'certificado_storage_path',
            'logo_path',
        ])->toArray();

        try {
            $data['certificado_configurado'] = filled($config->certificado_base64);
        } catch (\Illuminate\Contracts\Encryption\DecryptException $exception) {
            // Permite recuperar registros criptografados com uma APP_KEY antiga.
            // O próximo envio do certificado substituirá o valor ilegível.
            $data['certificado_configurado'] = false;
            DB::table($config->getTable())
                ->where($config->getKeyName(), $config->getKey())
                ->update([
                    'certificado_base64' => null,
                    'certificado_senha' => null,
                    'certificado_storage_path' => null,
                    'certificado_validade' => null,
                    'certificado_valid_from' => null,
                    'certificado_subject' => null,
                    'certificado_issuer' => null,
                    'certificado_serial' => null,
                    'updated_at' => now(),
                ]);
            $data['certificado_dono'] = null;
            $data['certificado_autoridade'] = null;
            Log::warning('Certificado salvo com uma APP_KEY diferente da atual.', [
                'id_empresa' => $config->id_empresa,
                'exception' => $exception,
            ]);
        }
        $data['certificado_dono'] = $config->certificado_subject;
        $data['certificado_autoridade'] = $config->certificado_issuer;
        $data['logo_configurada'] = false;
        $data['logo_data_url'] = null;

        try {
            $data['logo_configurada'] = filled($config->logo_path)
                && Storage::disk('local')->exists($config->logo_path);
            $data['logo_data_url'] = $this->logoDataUrl($config);
        } catch (\Throwable $exception) {
            // A falha no volume de arquivos não pode impedir a leitura das configurações.
            Log::warning('Não foi possível ler o logo da empresa.', [
                'id_empresa' => $config->id_empresa,
                'exception' => $exception,
            ]);
        }

        return response()->json($data);
    }

    public function update(Request $request): JsonResponse
    {
        if ($request->hasFile('certificado') && !$request->user()->temPermissao('certificado.gerenciar')) {
            return response()->json([
                'message' => 'Você não possui permissão para alterar o certificado digital.',
            ], 403);
        }

        $request->merge([
            'cnpj' => $this->digits($request->input('cnpj')),
            'cep' => $this->digits($request->input('cep')),
            'codigo_municipio_ibge' => $this->digits($request->input('codigo_municipio_ibge')),
            'cnae' => $this->digits($request->input('cnae')),
            'cfop_padrao' => $this->digits($request->input('cfop_padrao')),
            'csosn_padrao' => $this->digits($request->input('csosn_padrao')),
            'proximo_numero' => $request->filled('proximo_numero')
                ? (int) $request->input('proximo_numero')
                : null,
            'uf' => strtoupper((string) $request->input('uf')),
        ]);

        $data = $request->validate($this->rules(), $this->messages());
        $config = ConfiguracaoEmissor::current();

        // Uma tela parcialmente carregada não pode apagar uma IE válida só
        // porque o campo veio vazio no FormData. A limpeza só ocorre quando
        // o usuário marca explicitamente a opção de isenção.
        if (!$request->boolean('inscricao_estadual_isento') && blank($data['inscricao_estadual'] ?? null)) {
            $data['inscricao_estadual'] = $config->inscricao_estadual;
        }

        $newFiles = [];
        $certificateReplaced = false;

        try {
            DB::transaction(function () use ($request, $data, $config, &$newFiles, &$certificateReplaced): void {
                $attributes = $data;
                unset($attributes['logo'], $attributes['certificado'], $attributes['certificado_senha']);

                if (!empty($attributes['inscricao_estadual_isento'])) {
                    $attributes['inscricao_estadual'] = null;
                }

                if ($request->hasFile('logo')) {
                    $logo = $request->file('logo');
                    $logoPath = $this->tenantPath('logos', $logo);
                    Storage::disk('local')->put($logoPath, $logo->getContent());
                    $newFiles[] = $logoPath;
                    $attributes['logo_path'] = $logoPath;
                }

                if ($request->hasFile('certificado')) {
                    $certificateReplaced = true;
                    Log::info('Upload de certificado recebido.', [
                        'id_empresa' => $config->id_empresa,
                        'nome' => $request->file('certificado')->getClientOriginalName(),
                        'tamanho' => $request->file('certificado')->getSize(),
                        'erro_upload' => $request->file('certificado')->getError(),
                    ]);
                    $certificate = $this->readCertificate(
                        $request->file('certificado'),
                        (string) ($data['certificado_senha'] ?? ''),
                    );
                    $certificatePath = $this->tenantPath('certificados', $request->file('certificado'));
                    $attributes['certificado_base64'] = base64_encode($certificate['contents']);
                    $attributes['certificado_senha'] = $data['certificado_senha'];
                    $attributes['certificado_validade'] = Carbon::createFromTimestamp($certificate['parsed']['validTo_time_t']);
                    $attributes['certificado_valid_from'] = !empty($certificate['parsed']['validFrom_time_t'])
                        ? Carbon::createFromTimestamp($certificate['parsed']['validFrom_time_t'])
                        : null;
                    $attributes['certificado_subject'] = $this->certificateName($certificate['parsed']['subject'] ?? []);
                    $attributes['certificado_issuer'] = $this->certificateName($certificate['parsed']['issuer'] ?? []);
                    $attributes['certificado_serial'] = (string) ($certificate['parsed']['serialNumberHex'] ?? $certificate['parsed']['serialNumber'] ?? '');

                    // A cópia física é apenas um backup. O certificado principal fica
                    // criptografado no banco; falha no volume local não deve desfazer o cadastro.
                    try {
                        Storage::disk('local')->put(
                            $certificatePath,
                            Crypt::encryptString($certificate['contents']),
                        );
                        $newFiles[] = $certificatePath;
                        $attributes['certificado_storage_path'] = $certificatePath;
                    } catch (\Throwable $exception) {
                        Log::warning('Não foi possível criar a cópia física do certificado; o cadastro seguirá com a cópia criptografada no banco.', [
                            'id_empresa' => $config->id_empresa,
                            'exception' => $exception,
                        ]);
                        $attributes['certificado_storage_path'] = null;
                    }
                } else {
                    Log::warning('Salvar configurações sem arquivo de certificado.', [
                        'id_empresa' => $config->id_empresa,
                        'arquivos_recebidos' => array_keys($request->allFiles()),
                    ]);
                }

                if ($certificateReplaced) {
                    // O valor antigo pode estar ilegível por ter sido criptografado
                    // com outra APP_KEY. Persistimos os atributos já criptografados
                    // diretamente para não disparar a comparação do Eloquent.
                    $config->fill($attributes);
                    $rawAttributes = $config->getAttributes();
                    unset($rawAttributes[$config->getKeyName()]);
                    $rawAttributes['updated_at'] = now();

                    DB::table($config->getTable())
                        ->where($config->getKeyName(), $config->getKey())
                        ->update($rawAttributes);

                    $config->syncOriginal();
                } else {
                    // Sem upload de certificado, ainda precisamos aplicar todos
                    // os campos enviados pela tela antes de salvar.
                    $config->fill($attributes)->save();
                }

                $empresa = Empresa::query()->whereKey($config->id_empresa)->firstOrFail();
                $empresa->fill([
                    'razao_social' => $attributes['razao_social'],
                    'nome_fantasia' => $attributes['nome_fantasia'] ?? null,
                    'cnpj' => $attributes['cnpj'],
                    'inscricao_estadual' => $attributes['inscricao_estadual'] ?? null,
                    'crt' => $attributes['crt'],
                    'cep' => $attributes['cep'],
                    'logradouro' => $attributes['logradouro'],
                    'numero' => $attributes['numero'],
                    'bairro' => $attributes['bairro'],
                    'municipio' => $attributes['municipio'],
                    'codigo_municipio_ibge' => $attributes['codigo_municipio_ibge'] ?? null,
                    'uf' => $attributes['uf'],
                ])->save();
            });
        } catch (ValidationException $exception) {
            $this->deleteFiles($newFiles);
            throw $exception;
        } catch (\Throwable $exception) {
            $this->deleteFiles($newFiles);
            Log::error('Falha ao salvar as preferências da empresa.', [
                'id_empresa' => $config->id_empresa,
                'id_usuario' => $request->user()?->id,
                'exception' => $exception,
            ]);

            return response()->json([
                'message' => 'Não foi possível salvar as configurações agora. Tente novamente.',
            ], 500);
        }

        return response()->json(array_merge(
            $this->show()->getData(true),
            ['message' => 'Configurações salvas com sucesso.'],
        ));
    }

    public function testarComunicacao(Request $request): JsonResponse
    {
        $data = $request->validate([
            'ambiente' => ['nullable', 'in:1,2'],
        ], [
            'ambiente.in' => 'Selecione um ambiente válido para testar a comunicação.',
        ]);

        try {
            $config = ConfiguracaoEmissor::current();
            $result = $this->sefaz->testStatusServico($config, isset($data['ambiente']) ? (int) $data['ambiente'] : null);

            return response()->json($result, $result['success'] ? 200 : 503);
        } catch (NfeEmissionException $exception) {
            return response()->json([
                'success' => false,
                'message' => $exception->getMessage(),
                'status' => $exception->nfeStatus,
                'cstat' => $exception->cstat,
            ], $exception->httpStatus);
        }
    }

    public function removerCertificado(): JsonResponse
    {
        $config = ConfiguracaoEmissor::current();
        $certificatePath = $config->certificado_storage_path;

        $config->forceFill([
            'certificado_base64' => null,
            'certificado_storage_path' => null,
            'certificado_senha' => null,
            'certificado_validade' => null,
            'certificado_valid_from' => null,
            'certificado_subject' => null,
            'certificado_issuer' => null,
            'certificado_serial' => null,
        ])->save();

        if ($certificatePath) {
            Storage::disk('local')->delete($certificatePath);
        }

        return response()->json([
            'message' => 'Certificado digital removido com segurança.',
            'data' => $this->show()->getData(true),
        ]);
    }

    private function rules(): array
    {
        return [
            'razao_social' => ['required', 'string', 'max:120'],
            'nome_fantasia' => ['nullable', 'string', 'max:120'],
            'cnpj' => ['required', 'digits:14'],
            'inscricao_estadual' => ['nullable', 'string', 'max:14'],
            'inscricao_estadual_isento' => ['sometimes', 'boolean'],
            'inscricao_municipal' => ['nullable', 'string', 'max:20'],
            'cnae' => ['nullable', 'digits_between:1,10'],
            'crt' => ['required', 'integer', 'between:1,4'],
            'tamanho_empresa' => ['nullable', 'in:MEI,ME,EPP,GRANDE'],
            'cep' => ['required', 'digits:8'],
            'uf' => ['required', 'size:2'],
            'municipio' => ['required', 'string', 'max:60'],
            'bairro' => ['required', 'string', 'max:60'],
            'logradouro' => ['required', 'string', 'max:120'],
            'numero' => ['required', 'string', 'max:20'],
            'complemento' => ['nullable', 'string', 'max:60'],
            'codigo_municipio_ibge' => ['nullable', 'digits:7'],
            'telefone' => ['nullable', 'string', 'max:20'],
            'celular' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:120'],
            'ambiente' => ['required', 'in:1,2'],
            'serie_padrao' => ['required', 'integer', 'min:1', 'max:999'],
            'proximo_numero' => ['nullable', 'integer', 'min:1', 'max:999999999'],
            'cfop_padrao' => ['required', 'digits:4'],
            'csosn_padrao' => ['nullable', 'digits:4'],
            'logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            // PKCS#12 files are often reported by PHP as application/octet-stream.
            // Validate the declared extension here and validate the actual certificate
            // contents/password in readCertificate() below.
            'certificado' => ['nullable', 'file', 'extensions:pfx,p12', 'max:5120'],
            'certificado_senha' => ['nullable', 'string', 'max:200'],
        ];
    }

    private function messages(): array
    {
        return [
            'razao_social.required' => 'Informe a razão social da empresa.',
            'cnpj.required' => 'Informe o CNPJ da empresa.',
            'cnpj.digits' => 'Informe um CNPJ válido com 14 números.',
            'cep.required' => 'Informe o CEP da empresa.',
            'cep.digits' => 'Informe um CEP válido com 8 números.',
            'uf.required' => 'Informe a UF da empresa.',
            'uf.size' => 'A UF deve ter 2 letras.',
            'municipio.required' => 'Informe o município da empresa.',
            'bairro.required' => 'Informe o bairro da empresa.',
            'logradouro.required' => 'Informe o endereço da empresa.',
            'numero.required' => 'Informe o número do endereço.',
            'codigo_municipio_ibge.digits' => 'O código IBGE deve ter 7 números.',
            'cfop_padrao.digits' => 'O CFOP padrão deve ter 4 números.',
            'csosn_padrao.digits' => 'O CSOSN padrão deve ter 4 números.',
            'certificado.extensions' => 'Envie um certificado com extensão .pfx ou .p12.',
            'certificado_senha.max' => 'A senha do certificado não pode exceder 200 caracteres.',
            'logo.image' => 'Envie um arquivo de imagem válido para o logotipo.',
        ];
    }

    private function readCertificate(UploadedFile $file, string $password): array
    {
        if ($password === '') {
            throw ValidationException::withMessages([
                'certificado_senha' => 'Informe a senha do certificado A1.',
            ]);
        }

        $contents = file_get_contents($file->getRealPath());

        if ($contents === false || $contents === '') {
            throw ValidationException::withMessages([
                'certificado' => 'Não foi possível ler o arquivo do certificado.',
            ]);
        }

        $certificates = [];

        if (!openssl_pkcs12_read($contents, $certificates, $password) || empty($certificates['cert'])) {
            throw ValidationException::withMessages([
                'certificado' => 'O certificado A1 não é válido ou a senha está incorreta.',
            ]);
        }

        $parsed = openssl_x509_parse($certificates['cert']);

        if (!is_array($parsed) || empty($parsed['validTo_time_t'])) {
            throw ValidationException::withMessages([
                'certificado' => 'Não foi possível identificar a validade do certificado A1.',
            ]);
        }

        if ((int) $parsed['validTo_time_t'] < now()->timestamp) {
            throw ValidationException::withMessages([
                'certificado' => 'O certificado A1 está vencido. Envie um certificado dentro da validade.',
            ]);
        }

        return [
            'contents' => $contents,
            'parsed' => $parsed,
        ];
    }

    private function certificateName(array $part): string
    {
        $commonName = $part['CN'] ?? null;

        if (is_string($commonName) && $commonName !== '') {
            return $commonName;
        }

        return collect($part)
            ->filter(fn ($value): bool => is_string($value) && $value !== '')
            ->map(fn (string $value): string => $value)
            ->implode(' | ');
    }

    private function logoDataUrl(ConfiguracaoEmissor $config): ?string
    {
        if (!$config->logo_path || !Storage::disk('local')->exists($config->logo_path)) {
            return null;
        }

        $contents = Storage::disk('local')->get($config->logo_path);

        if (strlen($contents) > 2 * 1024 * 1024) {
            return null;
        }

        $mime = Storage::disk('local')->mimeType($config->logo_path) ?: 'image/png';

        return 'data:'.$mime.';base64,'.base64_encode($contents);
    }

    private function tenantPath(string $folder, UploadedFile $file): string
    {
        $empresaId = (int) auth()->user()?->id_empresa;
        $extension = strtolower($file->getClientOriginalExtension() ?: $file->extension() ?: 'bin');

        return 'empresa/'.$empresaId.'/'.$folder.'/'.str()->uuid().'.'.$extension;
    }

    private function deleteFiles(array $paths): void
    {
        foreach ($paths as $path) {
            Storage::disk('local')->delete($path);
        }
    }

    private function digits(mixed $value): string
    {
        return preg_replace('/\D+/', '', (string) $value) ?? '';
    }
}
