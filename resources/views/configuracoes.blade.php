<x-app-layout title="Preferências" header="Preferências da empresa" :partial="$partial ?? false">
    <div x-data="configManager()" class="w-full mt-2">

        <div x-show="error" x-cloak class="mb-5 border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700" role="alert">
            <span x-text="error"></span>
        </div>

        <form @submit.prevent="salvar" enctype="multipart/form-data" class="min-w-0 border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 bg-white px-5 pt-5 sm:px-8">
                <div class="flex flex-col gap-4 pb-5 xl:flex-row xl:items-start xl:justify-between">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-[0.18em] text-blue-600">Preferências da empresa</p>
                        <h2 class="mt-2 text-xl font-bold text-slate-950" x-text="activeTabLabel()"></h2>
                        <p class="mt-1 text-sm text-slate-500">Alterne entre as abas para ajustar dados fiscais, emissão e certificado digital.</p>
                    </div>
                    <div class="border border-slate-200 bg-slate-50 px-4 py-3 text-sm">
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Empresa atual</p>
                        <p class="mt-1 max-w-[360px] truncate font-semibold text-slate-900" x-text="form.razao_social || 'Configure os dados da empresa'"></p>
                        <p class="mt-1 text-xs text-slate-500" x-text="form.cnpj || 'CNPJ não informado'"></p>
                    </div>
                </div>

                <nav class="-mb-px flex gap-6 overflow-x-auto" aria-label="Abas de configuração">
                    <template x-for="tab in visibleTabs()" :key="tab.id">
                        <button
                            type="button"
                            @click="activeTab = tab.id"
                            class="flex shrink-0 items-center gap-3 border-b-2 px-1 py-4 text-sm font-semibold transition"
                            :class="activeTab === tab.id ? 'border-blue-600 text-blue-700' : 'border-transparent text-slate-500 hover:border-slate-300 hover:text-slate-900'"
                        >
                            <span class="flex h-7 w-7 items-center justify-center border text-xs font-bold"
                                :class="activeTab === tab.id ? 'border-blue-600 bg-blue-600 text-white' : 'border-slate-200 bg-slate-50 text-slate-500'"
                                x-text="tab.number"></span>
                            <span x-text="tab.label"></span>
                        </button>
                    </template>
                </nav>
            </div>

            <div>
                <section x-show="activeTab === 'empresa'" class="space-y-8 p-5 sm:p-8">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-[0.16em] text-blue-600">01 · Cadastro fisco-legal</p>
                        <h2 class="mt-2 text-xl font-bold text-slate-950">Dados da empresa</h2>
                        <p class="mt-1 text-sm text-slate-500">Essas informações serão utilizadas no XML da NF-e e no DANFE.</p>
                    </div>

                    <div class="grid gap-x-6 gap-y-5 md:grid-cols-2 xl:grid-cols-3">
                        <label class="xl:col-span-2">
                            <span class="label">Razão social <b class="text-red-600">*</b></span>
                            <input x-model="form.razao_social" placeholder="Razão social igual ao cadastro da Receita Federal" :class="fieldClass('razao_social')" autocomplete="organization" required>
                            <p x-show="fieldError('razao_social')" class="field-error" x-text="fieldError('razao_social')"></p>
                        </label>
                        <label>
                            <span class="label">Nome fantasia</span>
                            <input x-model="form.nome_fantasia" placeholder="Nome comercial exibido internamente" :class="fieldClass('nome_fantasia')" autocomplete="organization">
                            <p x-show="fieldError('nome_fantasia')" class="field-error" x-text="fieldError('nome_fantasia')"></p>
                        </label>
                        <label>
                            <span class="label">CNPJ <b class="text-red-600">*</b></span>
                            <input x-model="form.cnpj" x-mask="99.999.999/9999-99" inputmode="numeric" maxlength="18" placeholder="00.000.000/0000-00" :class="fieldClass('cnpj')" required>
                            <p x-show="fieldError('cnpj')" class="field-error" x-text="fieldError('cnpj')"></p>
                        </label>
                        <label>
                            <span class="label">Inscrição estadual</span>
                            <input name="inscricao_estadual" x-model="form.inscricao_estadual" :disabled="form.inscricao_estadual_isento" placeholder="Informe a IE ou marque isento" :class="fieldClass('inscricao_estadual')" inputmode="numeric">
                            <p x-show="fieldError('inscricao_estadual')" class="field-error" x-text="fieldError('inscricao_estadual')"></p>
                        </label>
                        <label class="flex items-end gap-2 pb-3">
                            <input name="inscricao_estadual_isento" x-model="form.inscricao_estadual_isento" type="checkbox" class="h-4 w-4 border-slate-300 text-blue-600 focus:ring-blue-500">
                            <span class="text-sm text-slate-700">Inscrição estadual isenta</span>
                        </label>
                        <label>
                            <span class="label">Inscrição municipal</span>
                            <input x-model="form.inscricao_municipal" placeholder="Opcional" :class="fieldClass('inscricao_municipal')">
                        </label>
                        <label>
                            <span class="label">CNAE</span>
                            <input x-model="form.cnae" placeholder="Ex.: 1412601" :class="fieldClass('cnae')" inputmode="numeric" maxlength="10">
                        </label>
                        <label>
                            <span class="label">Regime tributário <b class="text-red-600">*</b></span>
                            <select name="crt" x-model.number="form.crt" :class="fieldClass('crt')" required>
                                <option value="1">Simples Nacional</option>
                                <option value="2">Simples Nacional - excesso de sublimite</option>
                                <option value="3">Regime normal</option>
                                <option value="4">MEI</option>
                            </select>
                        </label>
                        <label>
                            <span class="label">Tamanho da empresa</span>
                            <select x-model="form.tamanho_empresa" :class="fieldClass('tamanho_empresa')">
                                <option value="">Selecione o porte</option>
                                <option value="MEI">MEI</option>
                                <option value="ME">ME</option>
                                <option value="EPP">EPP</option>
                                <option value="GRANDE">Grande empresa</option>
                            </select>
                        </label>
                    </div>

                    <div class="border-t border-slate-200 pt-7">
                        <h3 class="text-base font-bold text-slate-950">Logotipo para o DANFE</h3>
                        <p class="mt-1 text-sm text-slate-500">Envie uma imagem JPG, PNG ou WEBP de até 2 MB.</p>
                        <div class="mt-4 flex flex-wrap items-center gap-5">
                            <div class="flex h-24 w-52 items-center justify-center border border-dashed border-slate-300 bg-slate-50 p-3">
                                <template x-if="logoPreview">
                                    <img :src="logoPreview" alt="Pré-visualização do logotipo" class="max-h-full max-w-full object-contain">
                                </template>
                                <span x-show="!logoPreview" class="text-center text-xs text-slate-400">Nenhum logotipo selecionado</span>
                            </div>
                            <label class="text-sm font-semibold text-blue-700">
                                <span class="cursor-pointer border border-blue-200 px-4 py-2 hover:bg-blue-50">Escolher imagem</span>
                                <input x-ref="logo" type="file" accept="image/jpeg,image/png,image/webp" class="sr-only" @change="previewLogo">
                            </label>
                        </div>
                    </div>

                    <div class="border-t border-slate-200 pt-7">
                        <h3 class="text-base font-bold text-slate-950">Endereço fiscal</h3>
                        <div class="mt-4 grid gap-x-6 gap-y-5 md:grid-cols-2 xl:grid-cols-4">
                            <label>
                                <span class="label">CEP <b class="text-red-600">*</b></span>
                                <input x-model="form.cep" x-mask="99999-999" @input.debounce.500ms="buscarCep" inputmode="numeric" maxlength="9" placeholder="00000-000" :class="fieldClass('cep')" required>
                                <p x-show="consultandoCep" class="mt-1 text-xs text-blue-600">Consultando endereço...</p>
                                <p x-show="fieldError('cep')" class="field-error" x-text="fieldError('cep')"></p>
                            </label>
                            <label>
                                <span class="label">UF <b class="text-red-600">*</b></span>
                                <input x-model="form.uf" maxlength="2" placeholder="SP" :class="fieldClass('uf')" required>
                                <p x-show="fieldError('uf')" class="field-error" x-text="fieldError('uf')"></p>
                            </label>
                            <label class="xl:col-span-2">
                                <span class="label">Município <b class="text-red-600">*</b></span>
                                <input x-model="form.municipio" placeholder="Município do endereço fiscal" :class="fieldClass('municipio')" required>
                                <p x-show="fieldError('municipio')" class="field-error" x-text="fieldError('municipio')"></p>
                            </label>
                            <label>
                                <span class="label">Bairro <b class="text-red-600">*</b></span>
                                <input x-model="form.bairro" placeholder="Bairro" :class="fieldClass('bairro')" required>
                                <p x-show="fieldError('bairro')" class="field-error" x-text="fieldError('bairro')"></p>
                            </label>
                            <label class="md:col-span-2">
                                <span class="label">Endereço <b class="text-red-600">*</b></span>
                                <input x-model="form.logradouro" placeholder="Rua, avenida ou estrada" :class="fieldClass('logradouro')" required>
                                <p x-show="fieldError('logradouro')" class="field-error" x-text="fieldError('logradouro')"></p>
                            </label>
                            <label>
                                <span class="label">Número <b class="text-red-600">*</b></span>
                                <input x-model="form.numero" placeholder="Número" :class="fieldClass('numero')" required>
                                <p x-show="fieldError('numero')" class="field-error" x-text="fieldError('numero')"></p>
                            </label>
                            <label>
                                <span class="label">Complemento</span>
                                <input x-model="form.complemento" placeholder="Sala, bloco, galpão, referência..." :class="fieldClass('complemento')">
                            </label>
                            <label>
                                <span class="label">Código IBGE</span>
                                <input x-model="form.codigo_municipio_ibge" placeholder="Ex.: 3550308" :class="fieldClass('codigo_municipio_ibge')" inputmode="numeric" maxlength="7">
                            </label>
                        </div>
                    </div>

                    <div class="border-t border-slate-200 pt-7">
                        <h3 class="text-base font-bold text-slate-950">Contato</h3>
                        <div class="mt-4 grid gap-x-6 gap-y-5 md:grid-cols-3">
                            <label><span class="label">Telefone</span><input x-model="form.telefone" placeholder="(00) 0000-0000" :class="fieldClass('telefone')" inputmode="tel"></label>
                            <label><span class="label">Celular</span><input x-model="form.celular" placeholder="(00) 00000-0000" :class="fieldClass('celular')" inputmode="tel"></label>
                            <label><span class="label">E-mail</span><input x-model="form.email" type="email" placeholder="fiscal@empresa.com.br" :class="fieldClass('email')" autocomplete="email"></label>
                        </div>
                    </div>
                </section>

                <section x-show="activeTab === 'emissao'" x-cloak class="space-y-8 p-5 sm:p-8">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-[0.16em] text-blue-600">02 · Emissão fiscal</p>
                        <h2 class="mt-2 text-xl font-bold text-slate-950">Configurações de emissão e ambiente</h2>
                        <p class="mt-1 text-sm text-slate-500">Escolha o ambiente e confirme a disponibilidade da SEFAZ para a UF cadastrada.</p>
                    </div>

                    <div class="grid gap-5 md:grid-cols-2">
                        <label>
                            <span class="label">Ambiente da NF-e <b class="text-red-600">*</b></span>
                            <select name="ambiente" x-model.number="form.ambiente" :class="fieldClass('ambiente')" required>
                                <option value="2">2 - Homologação (ambiente de testes)</option>
                                <option value="1">1 - Produção (com valor fiscal)</option>
                            </select>
                        </label>
                        <div class="border border-blue-100 bg-blue-50 px-4 py-3 text-sm text-blue-800">
                            <p class="font-semibold">UF consultada</p>
                            <p class="mt-1" x-text="form.uf || 'Informe a UF na aba Dados da empresa.'"></p>
                        </div>
                        <label>
                            <span class="label">Série padrão <b class="text-red-600">*</b></span>
                            <input x-model.number="form.serie_padrao" type="number" min="1" max="999" placeholder="Ex.: 1" :class="fieldClass('serie_padrao')" required>
                        </label>
                        <label>
                            <span class="label">Próximo número da NF-e</span>
                            <input name="proximo_numero" x-model.number="form.proximo_numero" type="number" min="1" max="999999999" placeholder="Ex.: 424" :class="fieldClass('proximo_numero')">
                            <small class="mt-1 block text-xs text-slate-500">Use para continuar a sequência de outro sistema. Depois de autorizar, o próximo número avança automaticamente.</small>
                        </label>
                        <label>
                            <span class="label">CFOP padrão <b class="text-red-600">*</b></span>
                            <input x-model="form.cfop_padrao" inputmode="numeric" maxlength="4" placeholder="Ex.: 5901" :class="fieldClass('cfop_padrao')" required>
                        </label>
                        <label>
                            <span class="label">CSOSN/CST padrão</span>
                            <input x-model="form.csosn_padrao" inputmode="numeric" maxlength="4" placeholder="Ex.: 0400" :class="fieldClass('csosn_padrao')">
                        </label>
                    </div>

                    <div class="border border-slate-200 bg-slate-50 p-5">
                        <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
                            <div>
                                <h3 class="font-bold text-slate-950">Disponibilidade dos ambientes da SEFAZ</h3>
                                <p class="mt-1 text-sm text-slate-500">O teste usa o certificado A1 desta empresa e consulta o serviço oficial.</p>
                            </div>
                            <button type="button" @click="testarComunicacao" :disabled="communication.loading" class="inline-flex items-center justify-center gap-2 bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-emerald-700 disabled:cursor-not-allowed disabled:opacity-60">
                                <span x-show="communication.loading" class="h-4 w-4 animate-spin border-2 border-white/40 border-t-white"></span>
                                <span x-text="communication.loading ? 'Testando comunicação...' : 'Testar comunicação'"></span>
                            </button>
                        </div>
                        <div x-show="communication.state === 'success'" x-cloak class="mt-5 border border-emerald-200 bg-emerald-50 p-5 text-center text-emerald-800">
                            <div class="mx-auto flex h-12 w-12 items-center justify-center border-2 border-emerald-500 text-2xl font-bold text-emerald-600">✓</div>
                            <p class="mt-3 text-xs font-bold uppercase tracking-[0.15em]" x-text="form.ambiente == 1 ? 'Produção' : 'Homologação'"></p>
                            <p class="mt-1 font-semibold" x-text="communication.motivo || 'Serviço em Operação'"></p>
                            <p class="mt-1 text-xs" x-text="'Status ' + communication.cstat + ' · Tempo de resposta: ' + communication.latencia_ms + ' ms'"></p>
                        </div>
                        <div x-show="communication.state === 'error'" x-cloak class="mt-5 border border-red-200 bg-red-50 p-4 text-sm text-red-700">
                            <p class="font-semibold">Comunicação não confirmada</p>
                            <p class="mt-1" x-text="communication.message"></p>
                        </div>
                    </div>
                </section>

                <section x-show="activeTab === 'certificado' && can('certificado.gerenciar')" x-cloak class="space-y-8 p-5 sm:p-8">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-[0.16em] text-blue-600">03 · Identidade digital</p>
                        <h2 class="mt-2 text-xl font-bold text-slate-950">Certificado Digital A1</h2>
                        <p class="mt-1 text-sm text-slate-500">O certificado é validado antes de ser salvo e armazenado cifrado por empresa.</p>
                    </div>

                    <div x-show="form.certificado_configurado" x-cloak class="border border-emerald-200 bg-emerald-50 p-5">
                        <div class="flex flex-col gap-5 sm:flex-row sm:items-start">
                            <div class="flex h-14 w-14 shrink-0 items-center justify-center border border-emerald-300 bg-white text-2xl text-emerald-600">⌑</div>
                            <div class="min-w-0 flex-1">
                                <p class="font-bold text-emerald-800">Certificado válido até: <span x-text="formatDate(form.certificado_validade)"></span></p>
                                <dl class="mt-3 grid gap-2 text-sm text-emerald-900 sm:grid-cols-2">
                                    <div><dt class="font-semibold">Dono</dt><dd class="break-words" x-text="form.certificado_dono || 'Não informado'"></dd></div>
                                    <div><dt class="font-semibold">Autoridade certificadora</dt><dd class="break-words" x-text="form.certificado_autoridade || 'Não informado'"></dd></div>
                                    <div><dt class="font-semibold">Início da validade</dt><dd x-text="formatDate(form.certificado_valid_from)"></dd></div>
                                </dl>
                                <div class="mt-4 flex flex-wrap gap-3">
                                    <button type="button" @click="$refs.certificate.click()" class="bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-700">Atualizar certificado</button>
                                    <button type="button" @click="removerCertificado" class="border border-red-300 bg-white px-4 py-2 text-sm font-semibold text-red-700 hover:bg-red-50">Excluir certificado</button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="border border-slate-200 p-5">
                        <h3 class="font-bold text-slate-950" x-text="form.certificado_configurado ? 'Substituir certificado A1' : 'Cadastrar certificado A1'"></h3>
                        <p class="mt-1 text-sm text-slate-500">Formatos aceitos: .pfx e .p12. A senha nunca é exibida novamente.</p>
                        <div class="mt-5 grid gap-5 md:grid-cols-2">
                            <label>
                                <span class="label">Arquivo do certificado</span>
                                <input x-ref="certificate" type="file" accept=".pfx,.p12" @change="certificate = $event.target.files[0] || null" :class="fieldClass('certificado')">
                                <p x-show="fieldError('certificado')" class="field-error" x-text="fieldError('certificado')"></p>
                            </label>
                            <label>
                                <span class="label">Senha do certificado</span>
                                <input x-model="form.certificado_senha" type="password" autocomplete="new-password" placeholder="Senha do arquivo .pfx/.p12" :class="fieldClass('certificado_senha')">
                                <p x-show="fieldError('certificado_senha')" class="field-error" x-text="fieldError('certificado_senha')"></p>
                            </label>
                        </div>
                        <div class="mt-5 border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                            O certificado pertence exclusivamente à empresa atual. Nunca envie o arquivo por e-mail nem o comite no Git.
                        </div>
                    </div>
                </section>

                <div class="flex flex-col justify-between gap-3 border-t border-slate-200 px-5 py-5 sm:flex-row sm:items-center sm:px-8">
                    <p class="text-xs text-slate-500" x-show="lastSavedAt" x-text="'Última atualização: ' + lastSavedAt"></p>
                    <div class="flex flex-col gap-2 sm:ml-auto sm:flex-row">
                        <button type="submit" @click.prevent="salvar()" :disabled="saving" class="inline-flex items-center justify-center gap-2 bg-blue-600 px-5 py-3 text-sm font-semibold text-white hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-60">
                            <span x-show="saving" class="h-4 w-4 animate-spin border-2 border-white/40 border-t-white"></span>
                            <span x-text="saving ? 'Salvando configurações...' : 'Salvar configurações'"></span>
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <script>
        function configManager() {
            return {
                activeTab: 'empresa',
                initialized: false,
                loading: false,
                saving: false,
                error: '',
                errors: {},
                consultandoCep: false,
                certificate: null,
                logoPreview: '',
                lastSavedAt: '',
                tabs: [
                    { id: 'empresa', number: '01', label: 'Dados da empresa' },
                    { id: 'emissao', number: '02', label: 'Emissão e ambiente' },
                    { id: 'certificado', number: '03', label: 'Certificado Digital', permission: 'certificado.gerenciar' },
                ],
                communication: {
                    loading: false,
                    state: 'idle',
                    message: '',
                    motivo: '',
                    cstat: '',
                    latencia_ms: 0,
                },
                form: {
                    razao_social: '',
                    nome_fantasia: '',
                    cnpj: '',
                    inscricao_estadual: '',
                    inscricao_estadual_isento: false,
                    inscricao_municipal: '',
                    cnae: '',
                    crt: '1',
                    tamanho_empresa: '',
                    cep: '',
                    uf: '',
                    municipio: '',
                    bairro: '',
                    logradouro: '',
                    numero: '',
                    complemento: '',
                    codigo_municipio_ibge: '',
                    telefone: '',
                    celular: '',
                    email: '',
                    ambiente: '2',
                    serie_padrao: 1,
                    proximo_numero: '',
                    cfop_padrao: '5901',
                    csosn_padrao: '0400',
                    certificado_configurado: false,
                    certificado_validade: null,
                    certificado_valid_from: null,
                    certificado_dono: '',
                    certificado_autoridade: '',
                    certificado_senha: '',
                    logo_data_url: '',
                },
                init() {
                    if (this.initialized) {
                        return;
                    }

                    this.initialized = true;
                    this.activeTab = this.visibleTabs()[0]?.id || 'empresa';
                    this.carregar();
                },
                headers() {
                    return {
                        Authorization: 'Bearer ' + localStorage.getItem('nfe_token'),
                        Accept: 'application/json',
                    };
                },
                can(permission) {
                    if (!permission) {
                        return true;
                    }

                    const user = JSON.parse(localStorage.getItem('nfe_user') || '{}');

                    return !Array.isArray(user.permissions) || user.permissions.includes(permission);
                },
                visibleTabs() {
                    return this.tabs.filter((tab) => this.can(tab.permission));
                },
                activeTabLabel() {
                    return this.visibleTabs().find((tab) => tab.id === this.activeTab)?.label || 'Configurações';
                },
                fieldClass(field) {
                    return this.errors[field] ? 'field border-red-500 focus:border-red-500 focus:ring-red-100' : 'field';
                },
                fieldError(field) {
                    const error = this.errors[field];

                    return Array.isArray(error) ? error[0] : (error || '');
                },
                async parse(response) {
                    const data = await response.json().catch(() => ({}));

                    if (!response.ok) {
                        const exception = new Error(data.message || 'Não foi possível concluir a operação.');
                        exception.status = response.status;
                        exception.errors = data.errors || {};
                        throw exception;
                    }

                    return data;
                },
                notify(type, message, title) {
                    window.fiscalToast?.(type, message, title);
                },
                async carregar() {
                    if (this.loading) {
                        return;
                    }

                    this.loading = true;
                    this.error = '';

                    try {
                        const response = await fiscalFetch('/api/configuracoes-emissor', { headers: this.headers() });
                        const data = await this.parse(response);
                        Object.assign(this.form, data);
                        this.logoPreview = data.logo_data_url || '';
                    } catch (exception) {
                        this.error = exception.message;
                    } finally {
                        this.loading = false;
                    }
                },
                async buscarCep() {
                    const cep = String(this.form.cep || '').replace(/\D/g, '');

                    if (cep.length !== 8 || this.consultandoCep) {
                        return;
                    }

                    this.consultandoCep = true;

                    try {
                        const response = await fetch('https://brasilapi.com.br/api/cep/v1/' + cep);

                        if (response.status === 429) {
                            throw new Error('A consulta de CEP atingiu o limite temporário. Preencha o endereço manualmente.');
                        }

                        if (!response.ok) {
                            throw new Error('CEP não encontrado. Preencha o endereço manualmente.');
                        }

                        const data = await response.json();
                        Object.assign(this.form, {
                            cep,
                            logradouro: data.street || this.form.logradouro,
                            bairro: data.neighborhood || this.form.bairro,
                            municipio: data.city || this.form.municipio,
                            uf: data.state || this.form.uf,
                            codigo_municipio_ibge: data.city_ibge || this.form.codigo_municipio_ibge,
                        });
                        this.notify('success', 'Endereço preenchido automaticamente. Confira os dados antes de salvar.', 'CEP localizado');
                    } catch (exception) {
                        this.notify('warning', exception.message, 'Consulta de CEP indisponível');
                    } finally {
                        this.consultandoCep = false;
                    }
                },
                previewLogo(event) {
                    const file = event.target.files?.[0];

                    if (!file) {
                        return;
                    }

                    const reader = new FileReader();
                    reader.onload = (loadEvent) => {
                        this.logoPreview = loadEvent.target.result;
                    };
                    reader.readAsDataURL(file);
                },
                async testarComunicacao() {
                    if (this.communication.loading) {
                        return;
                    }

                    this.communication = { ...this.communication, loading: true, state: 'idle', message: '' };

                    try {
                        const response = await fiscalFetch('/api/configuracoes-emissor/testar-comunicacao', {
                            method: 'POST',
                            headers: { ...this.headers(), 'Content-Type': 'application/json' },
                            body: JSON.stringify({ ambiente: Number(this.form.ambiente) }),
                        });
                        const data = await this.parse(response);
                        this.communication = { ...this.communication, ...data, loading: false, state: data.success ? 'success' : 'error' };

                        if (data.success) {
                            this.notify('success', data.motivo || 'Serviço em operação.', 'Comunicação confirmada');
                        } else {
                            this.notify('warning', data.motivo || 'A SEFAZ não confirmou a operação.', 'Serviço indisponível');
                        }
                    } catch (exception) {
                        this.communication = { ...this.communication, loading: false, state: 'error', message: exception.message };
                        this.notify('error', exception.message, 'Falha na comunicação');
                    }
                },
                async salvar() {
                    if (this.saving) {
                        return;
                    }

                    this.saving = true;
                    this.error = '';
                    this.errors = {};

                    try {
                        const body = new FormData();
                        const fields = [
                            'razao_social', 'nome_fantasia', 'cnpj', 'inscricao_estadual', 'inscricao_estadual_isento',
                            'inscricao_municipal', 'cnae', 'crt', 'tamanho_empresa', 'cep', 'uf', 'municipio', 'bairro',
                            'logradouro', 'numero', 'complemento', 'codigo_municipio_ibge', 'telefone', 'celular', 'email',
                            'ambiente', 'serie_padrao', 'proximo_numero', 'cfop_padrao', 'csosn_padrao',
                        ];

                        // Leia o ambiente diretamente do select. Isso evita que um estado
                        // Alpine antigo (normalmente o padrão 2) sobrescreva a escolha 1.
                        const ambienteSelect = this.$root.querySelector('select[name="ambiente"]');
                        if (ambienteSelect) {
                            this.form.ambiente = Number(ambienteSelect.value);
                        }

                        const crtSelect = this.$root.querySelector('select[name="crt"]');
                        if (crtSelect) {
                            this.form.crt = Number(crtSelect.value);
                        }

                        const inscricaoEstadualInput = this.$root.querySelector('input[name="inscricao_estadual"]');
                        const inscricaoEstadualIsento = this.$root.querySelector('input[name="inscricao_estadual_isento"]');
                        const proximoNumeroInput = this.$root.querySelector('input[name="proximo_numero"]');
                        if (inscricaoEstadualInput && !inscricaoEstadualInput.disabled) {
                            this.form.inscricao_estadual = inscricaoEstadualInput.value.trim();
                        }
                        if (inscricaoEstadualIsento) {
                            this.form.inscricao_estadual_isento = inscricaoEstadualIsento.checked;
                        }
                        if (proximoNumeroInput) {
                            this.form.proximo_numero = proximoNumeroInput.value.trim();
                        }

                        fields.forEach((field) => {
                            let value = this.form[field];

                            if (['cnpj', 'cep', 'cnae', 'codigo_municipio_ibge', 'cfop_padrao', 'csosn_padrao'].includes(field)) {
                                value = String(value || '').replace(/\D/g, '');
                            }

                            if (typeof value === 'boolean') {
                                value = value ? '1' : '0';
                            }

                            body.append(field, value ?? '');
                        });

                        // Os selects são a fonte definitiva do valor escolhido.
                        // Isso evita que uma resposta antiga de carregamento sobrescreva
                        // o CRT/ambiente no estado Alpine antes do envio.
                        const crtValue = this.$root.querySelector('select[name="crt"]')?.value;
                        const ambienteValue = this.$root.querySelector('select[name="ambiente"]')?.value;
                        const porteValue = this.$root.querySelector('select[x-model="form.tamanho_empresa"]')?.value;
                        if (crtValue !== undefined) {
                            body.set('crt', crtValue);
                        }
                        if (ambienteValue !== undefined) {
                            body.set('ambiente', ambienteValue);
                        }
                        if (porteValue !== undefined) {
                            body.set('tamanho_empresa', porteValue);
                        }
                        if (proximoNumeroInput) {
                            body.set('proximo_numero', proximoNumeroInput.value.trim());
                        }

                        const certificate = this.$refs.certificate?.files?.[0] || this.certificate;

                        if (certificate) {
                            body.append('certificado', certificate, certificate.name);
                            body.append('certificado_senha', this.form.certificado_senha || '');
                        }

                        const logo = this.$refs.logo?.files?.[0];

                        if (logo) {
                            body.append('logo', logo);
                        }

                        const response = await fiscalFetch('/api/configuracoes-emissor', {
                            method: 'POST',
                            headers: this.headers(),
                            body,
                        });
                        const data = await this.parse(response);
                        Object.assign(this.form, data, { certificado_senha: '' });
                        this.logoPreview = data.logo_data_url || this.logoPreview;
                        this.certificate = null;
                        if (this.$refs.certificate) this.$refs.certificate.value = '';
                        this.lastSavedAt = new Date().toLocaleString('pt-BR');
                        this.notify('success', 'As preferências foram salvas com segurança.', 'Configurações atualizadas');
                    } catch (exception) {
                        this.errors = exception.errors || {};
                        this.error = exception.message;
                        this.notify(exception.status === 422 ? 'warning' : 'error', exception.message, 'Revise os campos');
                    } finally {
                        this.saving = false;
                    }
                },
                async removerCertificado() {
                    if (!window.confirm('Deseja excluir o certificado digital desta empresa?')) {
                        return;
                    }

                    try {
                        const response = await fiscalFetch('/api/configuracoes-emissor/certificado', {
                            method: 'DELETE',
                            headers: this.headers(),
                        });
                        const data = await this.parse(response);
                        Object.assign(this.form, data.data || {}, {
                            certificado_senha: '',
                            certificado_configurado: false,
                        });
                        this.notify('success', 'O certificado foi removido desta empresa.', 'Certificado excluído');
                    } catch (exception) {
                        this.notify('error', exception.message, 'Não foi possível excluir');
                    }
                },
                formatDate(value) {
                    if (!value) {
                        return 'Não informado';
                    }

                    return new Date(value).toLocaleDateString('pt-BR');
                },
            };
        }
    </script>

    <style>
        .label { display: block; font-size: .75rem; font-weight: 600; color: #334155; }
        .field { margin-top: .5rem; display: block; width: 100%; border: 1px solid #cbd5e1; border-radius: 0; background: #fff; padding: .75rem; font-size: .875rem; color: #0f172a; box-shadow: none; }
        .field:focus { border-color: #2563eb; box-shadow: 0 0 0 3px rgb(37 99 235 / .12); outline: none; }
        .field:disabled { cursor: not-allowed; background: #f1f5f9; color: #64748b; }
        .field-error { margin-top: .35rem; font-size: .75rem; color: #dc2626; }
    </style>
</x-app-layout>
