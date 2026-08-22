<?php

namespace App\Http\Controllers;

use App\Models\Destinatario;
use App\Models\NaturezaOperacao;
use App\Models\Cliente;
use App\Models\Produto;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Database\Seeders\NaturezaOperacaoSeeder;
use App\Models\ConfiguracaoEmissor;

class CatalogController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $destinatarios = Destinatario::query()
            ->where('tipo', 'fornecedor')
            ->orderBy('nome_razao_social')
            ->get();
        return response()->json($destinatarios);
    }

    public function store(Request $request): JsonResponse
    {
        $request->merge([
            'documento' => preg_replace('/\D/', '', (string) $request->input('documento', '')),
            'cep' => preg_replace('/\D/', '', (string) $request->input('cep', '')),
            'codigo_municipio_ibge' => preg_replace('/\D/', '', (string) $request->input('codigo_municipio_ibge', '')),
        ]);

        $data = $request->validate([
            'nome_razao_social' => ['required', 'string', 'max:120'],
            'documento' => ['required', 'digits_between:11,14'],
            'inscricao_estadual' => ['nullable', 'string', 'max:14'],
            'cep' => ['nullable', 'digits:8'],
            'logradouro' => ['nullable', 'string', 'max:120'],
            'numero' => ['nullable', 'string', 'max:20'],
            'complemento' => ['nullable', 'string', 'max:120'],
            'bairro' => ['nullable', 'string', 'max:60'],
            'municipio' => ['nullable', 'string', 'max:60'],
            'codigo_municipio_ibge' => ['nullable', 'digits:7'],
            'uf' => ['nullable', 'size:2'],
            'ativo' => ['sometimes', 'boolean'],
        ]);

        $data['tipo'] = 'fornecedor';
        $data['ativo'] = $data['ativo'] ?? true;
        $data['id_empresa'] = $request->user()->id_empresa;

        $fornecedor = Destinatario::create($data);

        return response()->json([
            'message' => 'Fornecedor cadastrado com sucesso.',
            'fornecedor' => $fornecedor,
        ], 201);
    }

    public function update(Request $request, Destinatario $fornecedor): JsonResponse
    {
        $request->merge([
            'documento' => preg_replace('/\D/', '', (string) $request->input('documento', '')),
            'cep' => preg_replace('/\D/', '', (string) $request->input('cep', '')),
            'codigo_municipio_ibge' => preg_replace('/\D/', '', (string) $request->input('codigo_municipio_ibge', '')),
        ]);

        $data = $request->validate([
            'nome_razao_social' => ['required', 'string', 'max:120'],
            'documento' => ['required', 'digits_between:11,14'],
            'inscricao_estadual' => ['nullable', 'string', 'max:14'],
            'cep' => ['nullable', 'digits:8'],
            'logradouro' => ['nullable', 'string', 'max:120'],
            'numero' => ['nullable', 'string', 'max:20'],
            'complemento' => ['nullable', 'string', 'max:120'],
            'bairro' => ['nullable', 'string', 'max:60'],
            'municipio' => ['nullable', 'string', 'max:60'],
            'codigo_municipio_ibge' => ['nullable', 'digits:7'],
            'uf' => ['nullable', 'size:2'],
            'ativo' => ['sometimes', 'boolean'],
        ]);

        $fornecedor->update($data);

        return response()->json([
            'message' => 'Fornecedor atualizado com sucesso.',
            'fornecedor' => $fornecedor,
        ]);
    }

    public function destroy(Destinatario $fornecedor): JsonResponse
    {
        $fornecedor->delete();

        return response()->json([
            'message' => 'Fornecedor excluído com sucesso.',
        ]);
    }

    public function destinatario(): JsonResponse
    {
        return response()->json(Destinatario::query()->where('ativo', true)->orderBy('nome_razao_social')->get());
    }

    public function destinatariosBuscar(Request $request): JsonResponse
    {
        $term = trim((string) $request->query('q', ''));
        $documento = preg_replace('/\D+/', '', $term);

        return response()->json(
            Destinatario::query()
                ->where('ativo', true)
                ->when($term !== '', fn ($query) => $query->where(function ($search) use ($term, $documento): void {
                    $search->where('nome_razao_social', 'ilike', '%'.$term.'%');
                    if ($documento !== '') {
                        $search->orWhere('documento', 'like', '%'.$documento.'%');
                    }
                }))
                ->orderBy('nome_razao_social')
                ->limit(10)
                ->get(),
        );
    }

    public function naturezas(Request $request): JsonResponse
    {
        $empresaId = (int) $request->user()->id_empresa;
        $incluirInativas = $request->boolean('all', false);

        $query = NaturezaOperacao::query()
            ->when(!$incluirInativas, fn ($q) => $q->where('ativa', true))
            ->orderBy('nome');

        $naturezas = $query->get();

        if ($naturezas->isEmpty()) {
            app(NaturezaOperacaoSeeder::class)->seedEmpresa($empresaId);

            $naturezas = NaturezaOperacao::query()
                ->when(!$incluirInativas, fn ($q) => $q->where('ativa', true))
                ->orderBy('nome')
                ->get();
        }

        return response()->json($naturezas);
    }

    public function naturezasSalvar(Request $request): JsonResponse
    {
        $request->merge([
            'cfop_padrao' => preg_replace('/\D+/', '', (string) $request->input('cfop_padrao', '')),
            'csosn_padrao' => preg_replace('/\D+/', '', (string) $request->input('csosn_padrao', '')),
            'cst_padrao' => preg_replace('/\D+/', '', (string) $request->input('cst_padrao', '')),
        ]);

        $data = $request->validate([
            'nome' => ['required', 'string', 'max:120'],
            'tipo_movimento' => ['required', 'in:Entrada,Saída,Saida'],
            'cfop_padrao' => ['required', 'digits:4'],
            'csosn_padrao' => ['nullable', 'string', 'max:4'],
            'cst_padrao' => ['nullable', 'string', 'max:3'],
            'calcula_impostos' => ['sometimes', 'boolean'],
            'calcula_icms' => ['sometimes', 'boolean'],
            'calcula_ipi' => ['sometimes', 'boolean'],
            'calcula_pis' => ['sometimes', 'boolean'],
            'calcula_cofins' => ['sometimes', 'boolean'],
            'informacoes_complementares' => ['nullable', 'string', 'max:1000'],
            'ativa' => ['sometimes', 'boolean'],
        ]);

        $data['tipo_movimento'] = $data['tipo_movimento'] === 'Saida' ? 'Saída' : $data['tipo_movimento'];
        $data['ativa'] = $data['ativa'] ?? true;
        $data['id_empresa'] = $request->user()->id_empresa;

        $natureza = NaturezaOperacao::create($data);

        return response()->json([
            'message' => 'Natureza de operação cadastrada com sucesso.',
            'natureza' => $natureza,
        ], 201);
    }

    public function naturezasEditar(Request $request, NaturezaOperacao $natureza): JsonResponse
    {
        $request->merge([
            'cfop_padrao' => preg_replace('/\D+/', '', (string) $request->input('cfop_padrao', '')),
            'csosn_padrao' => preg_replace('/\D+/', '', (string) $request->input('csosn_padrao', '')),
            'cst_padrao' => preg_replace('/\D+/', '', (string) $request->input('cst_padrao', '')),
        ]);

        $data = $request->validate([
            'nome' => ['required', 'string', 'max:120'],
            'tipo_movimento' => ['required', 'in:Entrada,Saída,Saida'],
            'cfop_padrao' => ['required', 'digits:4'],
            'csosn_padrao' => ['nullable', 'string', 'max:4'],
            'cst_padrao' => ['nullable', 'string', 'max:3'],
            'calcula_impostos' => ['sometimes', 'boolean'],
            'calcula_icms' => ['sometimes', 'boolean'],
            'calcula_ipi' => ['sometimes', 'boolean'],
            'calcula_pis' => ['sometimes', 'boolean'],
            'calcula_cofins' => ['sometimes', 'boolean'],
            'informacoes_complementares' => ['nullable', 'string', 'max:1000'],
            'ativa' => ['sometimes', 'boolean'],
        ]);

        $data['tipo_movimento'] = $data['tipo_movimento'] === 'Saida' ? 'Saída' : $data['tipo_movimento'];
        $natureza->update($data);

        return response()->json([
            'message' => 'Natureza de operação atualizada com sucesso.',
            'natureza' => $natureza,
        ]);
    }

    public function naturezasExcluir(NaturezaOperacao $natureza): JsonResponse
    {
        $natureza->delete();

        return response()->json([
            'message' => 'Natureza de operação excluída com sucesso.',
        ]);
    }

    public function clientesBuscar(Request $request): JsonResponse
    {
        $term = trim((string) $request->query('q', ''));
        return response()->json(Cliente::query()->where('ativo', true)->when($term !== '', fn ($q) => $q->where(fn ($query) => $query->where('razao_social', 'ilike', "%{$term}%")->orWhere('documento', 'like', "%{$term}%")))->orderBy('razao_social')->limit(10)->get());
    }

    public function clientesListar(Request $request): JsonResponse
    {
        $clientes = Cliente::query()
            ->orderBy('razao_social')
            ->get();
        return response()->json($clientes);
    }

    public function clientesSalvar(Request $request): JsonResponse
    {
        $request->merge([
            'documento' => preg_replace('/\D/', '', (string) $request->input('documento', '')),
            'cep' => preg_replace('/\D/', '', (string) $request->input('cep', '')),
            'codigo_ibge' => preg_replace('/\D/', '', (string) $request->input('codigo_ibge', '')),
            'uf' => strtoupper((string) $request->input('uf', '')),
        ]);

        $data = $request->validate([
            'razao_social' => ['required', 'string', 'max:120'],
            'documento' => ['required', 'digits_between:11,14'],
            'inscricao_estadual' => ['nullable', 'string', 'max:14'],
            'cep' => ['nullable', 'digits:8'],
            'logradouro' => ['nullable', 'string', 'max:120'],
            'numero' => ['nullable', 'string', 'max:20'],
            'complemento' => ['nullable', 'string', 'max:120'],
            'bairro' => ['nullable', 'string', 'max:60'],
            'cidade' => ['nullable', 'string', 'max:60'],
            'codigo_ibge' => ['nullable', 'digits:7'],
            'uf' => ['nullable', 'size:2'],
            'ativo' => ['sometimes', 'boolean'],
        ]);

        $data['ativo'] = $data['ativo'] ?? true;
        $data['id_empresa'] = $request->user()->id_empresa;

        $cliente = Cliente::create($data);

        return response()->json([
            'message' => 'Cliente cadastrado com sucesso.',
            'cliente' => $cliente,
        ], 201);
    }

    public function clientesEditar(Request $request, Cliente $cliente): JsonResponse
    {
        $request->merge([
            'documento' => preg_replace('/\D/', '', (string) $request->input('documento', '')),
            'cep' => preg_replace('/\D/', '', (string) $request->input('cep', '')),
            'codigo_ibge' => preg_replace('/\D/', '', (string) $request->input('codigo_ibge', '')),
            'uf' => strtoupper((string) $request->input('uf', '')),
        ]);

        $data = $request->validate([
            'razao_social' => ['required', 'string', 'max:120'],
            'documento' => ['required', 'digits_between:11,14'],
            'inscricao_estadual' => ['nullable', 'string', 'max:14'],
            'cep' => ['nullable', 'digits:8'],
            'logradouro' => ['nullable', 'string', 'max:120'],
            'numero' => ['nullable', 'string', 'max:20'],
            'complemento' => ['nullable', 'string', 'max:120'],
            'bairro' => ['nullable', 'string', 'max:60'],
            'cidade' => ['nullable', 'string', 'max:60'],
            'codigo_ibge' => ['nullable', 'digits:7'],
            'uf' => ['nullable', 'size:2'],
            'ativo' => ['sometimes', 'boolean'],
        ]);

        $cliente->update($data);

        return response()->json([
            'message' => 'Cliente atualizado com sucesso.',
            'cliente' => $cliente,
        ]);
    }

    public function clientesExcluir(Cliente $cliente): JsonResponse
    {
        $cliente->delete();

        return response()->json([
            'message' => 'Cliente excluído com sucesso.',
        ]);
    }

    public function importarCliente(Request $request): JsonResponse
    {
        $request->merge([
            'documento' => preg_replace('/\D/', '', (string) $request->input('documento', '')),
            'cep' => preg_replace('/\D/', '', (string) $request->input('cep', '')),
            'codigo_ibge' => preg_replace('/\D/', '', (string) $request->input('codigo_ibge', '')),
            'uf' => strtoupper((string) $request->input('uf', '')),
        ]);

        $data = $request->validate([
            'razao_social' => ['required', 'string', 'max:120'],
            'documento' => ['required', 'digits_between:11,14'],
            'inscricao_estadual' => ['nullable', 'string', 'max:14'],
            'cep' => ['nullable', 'digits:8'],
            'logradouro' => ['nullable', 'string', 'max:120'],
            'numero' => ['nullable', 'string', 'max:20'],
            'bairro' => ['nullable', 'string', 'max:60'],
            'cidade' => ['nullable', 'string', 'max:60'],
            'codigo_ibge' => ['nullable', 'digits:7'],
            'uf' => ['nullable', 'size:2'],
        ]);

        $data['ativo'] = true;
        $data['id_empresa'] = $request->user()->id_empresa;

        $cliente = DB::transaction(function () use ($data): Cliente {
            return Cliente::query()->updateOrCreate(['documento' => $data['documento']], $data);
        });

        return response()->json($cliente);
    }

    public function produtosBuscar(Request $request): JsonResponse
    {
        $term = trim((string) $request->query('q', ''));
        return response()->json(Produto::query()->where('ativo', true)->when($term !== '', fn ($q) => $q->where(fn ($query) => $query->where('descricao', 'ilike', "%{$term}%")->orWhere('codigo', 'like', "%{$term}%")))->orderBy('descricao')->limit(10)->get());
    }

    public function produtosListar(Request $request): JsonResponse
    {
        $produtos = Produto::query()
            ->orderBy('descricao')
            ->get();
        return response()->json($produtos);
    }

    public function produtosSalvar(Request $request): JsonResponse
    {
        $config = ConfiguracaoEmissor::current();
        $request->merge([
            'ncm' => preg_replace('/\D+/', '', (string) $request->input('ncm', '')),
            'cfop' => preg_replace('/\D+/', '', (string) $request->input('cfop', '')),
            'csosn' => preg_replace('/\D+/', '', (string) $request->input('csosn', '')),
            'cst' => preg_replace('/\D+/', '', (string) $request->input('cst', '')),
        ]);
        $data = $request->validate([
            'codigo' => ['required', 'string', 'max:60'],
            'descricao' => ['required', 'string', 'max:120'],
            'ncm' => ['required', 'digits:8'],
            'valor_unitario' => ['required', 'numeric', 'min:0'],
            'cfop' => ['nullable', 'digits:4'],
            'csosn' => ['nullable', 'string', 'max:4'],
            'cst' => ['nullable', 'string', 'max:3'],
            'unidade' => ['required', 'string', 'max:6'],
            'ativo' => ['sometimes', 'boolean'],
        ]);

        $data['ativo'] = $data['ativo'] ?? true;
        $data['cfop'] = $data['cfop'] ?: $config->cfop_padrao;
        $data['csosn'] = $data['csosn'] ?: $config->csosn_padrao;
        $data['unidade'] = strtoupper(trim($data['unidade']));
        $data['id_empresa'] = $request->user()->id_empresa;

        if (Produto::query()->where('codigo', $data['codigo'])->exists()) {
            return response()->json([
                'message' => 'Este código já está cadastrado. Use o botão de editar no produto existente.',
            ], 409);
        }

        $produto = Produto::create($data);

        return response()->json([
            'message' => 'Produto cadastrado com sucesso.',
            'produto' => $produto,
        ], 201);
    }

    public function produtosEditar(Request $request, Produto $produto): JsonResponse
    {
        $config = ConfiguracaoEmissor::current();
        $request->merge([
            'ncm' => preg_replace('/\D+/', '', (string) $request->input('ncm', '')),
            'cfop' => preg_replace('/\D+/', '', (string) $request->input('cfop', '')),
            'csosn' => preg_replace('/\D+/', '', (string) $request->input('csosn', '')),
            'cst' => preg_replace('/\D+/', '', (string) $request->input('cst', '')),
        ]);
        $data = $request->validate([
            'codigo' => ['required', 'string', 'max:60'],
            'descricao' => ['required', 'string', 'max:120'],
            'ncm' => ['required', 'digits:8'],
            'valor_unitario' => ['required', 'numeric', 'min:0'],
            'cfop' => ['nullable', 'digits:4'],
            'csosn' => ['nullable', 'string', 'max:4'],
            'cst' => ['nullable', 'string', 'max:3'],
            'unidade' => ['required', 'string', 'max:6'],
            'ativo' => ['sometimes', 'boolean'],
        ]);

        $data['cfop'] = $data['cfop'] ?: $config->cfop_padrao;
        $data['csosn'] = $data['csosn'] ?: $config->csosn_padrao;
        $data['unidade'] = strtoupper(trim($data['unidade']));
        $produto->update($data);

        return response()->json([
            'message' => 'Produto atualizado com sucesso.',
            'produto' => $produto,
        ]);
    }

    public function produtosExcluir(Produto $produto): JsonResponse
    {
        $produto->delete();

        return response()->json([
            'message' => 'Produto excluído com sucesso.',
        ]);
    }
}
