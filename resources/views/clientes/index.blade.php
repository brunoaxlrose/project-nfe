<x-app-layout title="Clientes e Fornecedores" :partial="$partial ?? false">
    <div class="w-full mt-2" x-data="clienteManager" x-init="init()">
        <div class="mb-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div class="relative flex-1 max-w-md">
                <span class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none text-slate-400">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </span>
                <input type="text" x-model="searchQuery" placeholder="Buscar cliente por nome ou documento..." class="block w-full pl-10 pr-3 py-2 border border-slate-300 rounded-md text-sm focus:ring-blue-500 focus:border-blue-500">
            </div>
            <div class="flex items-center gap-2">
                <button type="button" x-show="can('clientes.criar')" @click="abrirNovoCliente" class="bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-blue-700">Novo cliente</button>
            </div>
        </div>

        <div class="space-y-6">
            <section class="border border-slate-200 bg-white shadow-sm">
                <div class="flex flex-col gap-3 border-b border-slate-200 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h2 class="text-sm font-semibold text-slate-900">Clientes Cadastrados</h2>
                        <p class="mt-1 text-xs text-slate-500">Gerencie a lista de clientes e destinatários de notas fiscais da empresa.</p>
                    </div>
                    <span class="text-xs text-slate-500" x-text="filteredClientes().length + ' cliente(s)'"></span>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[820px] text-sm border-collapse border border-slate-200">
                        <thead class="bg-slate-100 text-center text-xs uppercase tracking-wide text-slate-700 border-b border-slate-200">
                            <tr>
                                <th class="px-5 py-3 border border-slate-200 text-center">Razão Social</th>
                                <th class="px-5 py-3 border border-slate-200 text-center">Documento (CPF/CNPJ)</th>
                                <th class="px-5 py-3 border border-slate-200 text-center">Inscrição Estadual</th>
                                <th class="px-5 py-3 border border-slate-200 text-center">Cidade / UF</th>
                                <th class="px-5 py-3 border border-slate-200 text-center">Status</th>
                                <th class="px-5 py-3 border border-slate-200 text-center">Ações</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200 text-center">
                            <template x-for="cliente in paginatedClientes()" :key="cliente.id">
                                <tr class="odd:bg-white even:bg-slate-50 hover:bg-slate-100/80 transition-colors">
                                    <td class="px-5 py-3.5 border border-slate-200 font-medium text-slate-900 text-center" x-text="cliente.razao_social"></td>
                                    <td class="px-5 py-3.5 border border-slate-200 text-slate-600 text-center" x-text="formatDocumento(cliente.documento)"></td>
                                    <td class="px-5 py-3.5 border border-slate-200 text-slate-600 text-center" x-text="cliente.inscricao_estadual || 'Isento/Não informado'"></td>
                                    <td class="px-5 py-3.5 border border-slate-200 text-slate-600 text-center" x-text="cliente.cidade ? cliente.cidade + ' / ' + cliente.uf : 'Não informado'"></td>
                                    <td class="px-5 py-3.5 border border-slate-200 text-center">
                                        <span class="inline-flex px-2 py-1 text-xs font-semibold" :class="cliente.ativo ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-500'" x-text="cliente.ativo ? 'Ativo' : 'Inativo'"></span>
                                    </td>
                                    <td class="px-5 py-3.5 border border-slate-200 text-center space-x-2">
                                        <button x-show="can('clientes.editar')" type="button" @click="editarCliente(cliente)" class="text-blue-600 hover:text-blue-800 transition" title="Editar Cliente">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-5 h-5 inline">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10" />
                                            </svg>
                                        </button>
                                        <button x-show="can('clientes.excluir')" type="button" @click="excluirCliente(cliente)" class="text-red-600 hover:text-red-800 transition" title="Excluir Cliente">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-5 h-5 inline">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </button>
                                    </td>
                                </tr>
                            </template>
                            <tr x-show="!isLoading && !filteredClientes().length" x-cloak>
                                <td colspan="6" class="px-5 py-10 text-center text-sm text-slate-500">Nenhum cliente cadastrado.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Datatable Pagination Footer -->
                <div class="flex items-center justify-between border-t border-slate-200 bg-slate-50 px-5 py-3" x-show="totalPages() > 1" x-cloak>
                    <div class="text-xs text-slate-500">
                        Exibindo página <span class="font-bold text-slate-700" x-text="currentPage"></span> de <span class="font-bold text-slate-700" x-text="totalPages()"></span>
                    </div>
                    <div class="flex items-center gap-1">
                        <button type="button" @click="prevPage" :disabled="currentPage === 1" class="px-2.5 py-1.5 border border-slate-300 rounded text-xs font-semibold bg-white text-slate-700 hover:bg-slate-50 disabled:opacity-50 disabled:cursor-not-allowed">Anterior</button>
                        <template x-for="p in totalPages()" :key="p">
                            <button type="button" @click="currentPage = p" class="px-3 py-1.5 border rounded text-xs font-semibold" :class="currentPage === p ? 'bg-blue-600 border-blue-600 text-white' : 'border-slate-300 bg-white text-slate-700 hover:bg-slate-50'" x-text="p"></button>
                        </template>
                        <button type="button" @click="nextPage" :disabled="currentPage === totalPages()" class="px-2.5 py-1.5 border border-slate-300 rounded text-xs font-semibold bg-white text-slate-700 hover:bg-slate-50 disabled:opacity-50 disabled:cursor-not-allowed">Próximo</button>
                    </div>
                </div>
            </section>
        </div>

        <!-- Create/Edit Modal -->
        <div x-cloak x-show="modalAberto" x-transition.opacity class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/60 p-3 sm:p-6">
            <div @click.outside="fecharModal" class="flex max-h-[92vh] w-full max-w-3xl flex-col overflow-hidden border border-slate-200 bg-white shadow-2xl">
                <div class="flex items-start justify-between gap-4 border-b border-slate-200 px-5 py-4">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-blue-600">Cadastro</p>
                        <h2 class="mt-1 text-xl font-bold text-slate-950" x-text="form.id ? 'Editar cliente' : 'Novo cliente'"></h2>
                        <p class="mt-1 text-sm text-slate-500">Preencha os campos para salvar as informações do cliente.</p>
                    </div>
                    <button type="button" @click="fecharModal" class="px-3 py-2 text-sm font-semibold text-slate-500 hover:bg-slate-100">Fechar</button>
                </div>

                <form @submit.prevent="salvar" class="flex min-h-0 flex-1 flex-col">
                    <div class="min-h-0 flex-1 overflow-y-auto p-5 space-y-4">
                        <div class="grid gap-4 md:grid-cols-2">
                            <label>
                                <span class="label">Razão Social / Nome completo <b class="text-red-600">*</b></span>
                                <input x-model="form.razao_social" class="field" required placeholder="Ex: João da Silva">
                            </label>
                            <label class="relative block">
                                <span class="label">CPF ou CNPJ <b class="text-red-600">*</b></span>
                                <input x-model="form.documento" 
                                       x-mask:dynamic="form.documento.replace(/\D/g, '').length > 11 ? '99.999.999/9999-99' : '999.999.999-99'"
                                       @input="if(form.documento && form.documento.replace(/\D/g, '').length === 14) buscarCNPJ()"
                                       class="field pr-10" required placeholder="Ex: 00.000.000/0001-00 ou 000.000.000-00">
                                <span x-show="buscandoCNPJ" x-cloak class="absolute right-3 top-9 flex h-5 w-5 items-center justify-center">
                                    <svg class="animate-spin h-5 w-5 text-blue-600" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                    </svg>
                                </span>
                            </label>
                        </div>
                        <div class="grid gap-4 md:grid-cols-2">
                            <label>
                                <span class="label">Inscrição Estadual</span>
                                <input x-model="form.inscricao_estadual" class="field" placeholder="Inscrição Estadual ou em branco se isento">
                            </label>
                            <label class="relative block">
                                <span class="label">CEP</span>
                                <input x-model="form.cep" 
                                       x-mask="99999-999"
                                       @input="if(form.cep.replace(/\D/g, '').length === 8) buscarCEP()"
                                       class="field pr-10" placeholder="Ex: 01001-000">
                                <span x-show="buscandoCEP" x-cloak class="absolute right-3 top-9 flex h-5 w-5 items-center justify-center">
                                    <svg class="animate-spin h-5 w-5 text-blue-600" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                    </svg>
                                </span>
                            </label>
                        </div>
                        <div class="grid gap-4 md:grid-cols-3">
                            <label class="md:col-span-2">
                                <span class="label">Logradouro</span>
                                <input x-model="form.logradouro" class="field" placeholder="Ex: Rua das Flores">
                            </label>
                            <label>
                                <span class="label">Número</span>
                                <input x-model="form.numero" class="field" placeholder="Ex: 123">
                            </label>
                        </div>
                        <div class="grid gap-4 md:grid-cols-3">
                            <label>
                                <span class="label">Bairro</span>
                                <input x-model="form.bairro" class="field" placeholder="Ex: Centro">
                            </label>
                            <label>
                                <span class="label">Cidade</span>
                                <input x-model="form.cidade" class="field" placeholder="Ex: São Paulo">
                            </label>
                            <label>
                                <span class="label">UF</span>
                                <input x-model="form.uf" class="field" maxlength="2" placeholder="Ex: SP">
                            </label>
                        </div>
                        <div class="flex items-center gap-2">
                            <input x-model="form.ativo" type="checkbox" id="ativo" class="h-4 w-4 border-slate-300 text-blue-600 focus:ring-blue-500">
                            <label for="ativo" class="text-sm font-semibold text-slate-700">Cliente Ativo</label>
                        </div>
                    </div>
                    <div class="flex shrink-0 items-center justify-end gap-3 border-t border-slate-200 px-5 py-4">
                        <button type="button" @click="fecharModal" class="border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">Cancelar</button>
                        <button type="submit" class="bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-blue-700">Salvar Cliente</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        if (!window.fiscalClientesManagerRegistered) {
            window.fiscalClientesManagerRegistered = true;
            window.registerFiscalAlpine((Alpine) => {
                Alpine.data('clienteManager', () => ({
                    clientes: [],
                    isLoading: false,
                    modalAberto: false,
                    buscandoCNPJ: false,
                    buscandoCEP: false,
                    searchQuery: '',
                    currentPage: 1,
                    itemsPerPage: 10,
                    form: {
                        id: null,
                        razao_social: '',
                        documento: '',
                        inscricao_estadual: '',
                        cep: '',
                        logradouro: '',
                        numero: '',
                        complemento: '',
                        bairro: '',
                        cidade: '',
                        uf: '',
                        ativo: true
                    },

                    init() {
                        this.listar();
                        this.$watch('searchQuery', () => {
                            this.currentPage = 1;
                        });
                    },

                    documentoMask() {
                        const clean = (this.form.documento || '').replace(/\D/g, '');
                        return clean.length > 11 ? '99.999.999/9999-99' : '999.999.999-99';
                    },

                    async buscarCNPJ() {
                        const cnpjClean = this.form.documento.replace(/\D/g, '');
                        if (cnpjClean.length !== 14 || this.buscandoCNPJ) return;
                        this.buscandoCNPJ = true;
                        try {
                            const res = await fetch(`https://brasilapi.com.br/api/cnpj/v1/${cnpjClean}`);
                            if (res.ok) {
                                const data = await res.json();
                                this.form.razao_social = data.razao_social || data.nome_fantasia || this.form.razao_social;
                                this.form.cep = data.cep || this.form.cep;
                                this.form.logradouro = data.logradouro || this.form.logradouro;
                                this.form.numero = data.numero || this.form.numero;
                                this.form.complemento = data.complemento || this.form.complemento;
                                this.form.bairro = data.bairro || this.form.bairro;
                                this.form.cidade = data.municipio || this.form.cidade;
                                this.form.uf = data.uf || this.form.uf;
                                window.fiscalToast?.('success', 'Dados do CNPJ importados com sucesso!', 'BrasilAPI');
                            }
                        } catch (err) {
                            console.error(err);
                        } finally {
                            this.buscandoCNPJ = false;
                        }
                    },

                    async buscarCEP() {
                        const cepClean = this.form.cep.replace(/\D/g, '');
                        if (cepClean.length !== 8 || this.buscandoCEP) return;
                        this.buscandoCEP = true;
                        try {
                            const res = await fetch(`https://brasilapi.com.br/api/cep/v1/${cepClean}`);
                            if (res.ok) {
                                const data = await res.json();
                                this.form.logradouro = data.street || this.form.logradouro;
                                this.form.bairro = data.neighborhood || this.form.bairro;
                                this.form.cidade = data.city || this.form.cidade;
                                this.form.uf = data.state || this.form.uf;
                                window.fiscalToast?.('success', 'Endereço atualizado pelo CEP!', 'BrasilAPI');
                            }
                        } catch (err) {
                            console.error(err);
                        } finally {
                            this.buscandoCEP = false;
                        }
                    },

                    paginatedClientes() {
                        const start = (this.currentPage - 1) * this.itemsPerPage;
                        return this.filteredClientes().slice(start, start + this.itemsPerPage);
                    },

                    totalPages() {
                        return Math.ceil(this.filteredClientes().length / this.itemsPerPage);
                    },

                    prevPage() {
                        if (this.currentPage > 1) this.currentPage--;
                    },

                    nextPage() {
                        if (this.currentPage < this.totalPages()) this.currentPage++;
                    },

                    filteredClientes() {
                        if (!this.searchQuery) return this.clientes;
                        const query = this.searchQuery.toLowerCase();
                        return this.clientes.filter(item => 
                            (item.razao_social && item.razao_social.toLowerCase().includes(query)) ||
                            (item.documento && item.documento.includes(query))
                        );
                    },

                    async listar() {
                        this.isLoading = true;
                        try {
                            const response = await fiscalFetch('/api/clientes', {
                                headers: { 'Authorization': 'Bearer ' + localStorage.getItem('nfe_token') }
                            });
                            if (response.ok) {
                                this.clientes = await response.json();
                            }
                        } catch (err) {
                            window.fiscalToast?.('error', 'Não foi possível carregar clientes.', 'Erro');
                        } finally {
                            this.isLoading = false;
                        }
                    },

                    abrirNovoCliente() {
                        this.form = {
                            id: null,
                            razao_social: '',
                            documento: '',
                            inscricao_estadual: '',
                            cep: '',
                            logradouro: '',
                            numero: '',
                            complemento: '',
                            bairro: '',
                            cidade: '',
                            uf: '',
                            ativo: true
                        };
                        this.modalAberto = true;
                    },

                    editarCliente(cliente) {
                        this.form = { ...cliente };
                        this.modalAberto = true;
                    },

                    fecharModal() {
                        this.modalAberto = false;
                    },

                    async salvar() {
                        const method = this.form.id ? 'PUT' : 'POST';
                        const endpoint = this.form.id ? '/api/clientes/' + this.form.id : '/api/clientes';

                        try {
                            const response = await fiscalFetch(endpoint, {
                                method: method,
                                headers: {
                                    'Content-Type': 'application/json',
                                    'Authorization': 'Bearer ' + localStorage.getItem('nfe_token')
                                },
                                body: JSON.stringify(this.form)
                            });

                            const data = await response.json();
                            if (response.ok) {
                                window.fiscalToast?.('success', data.message || 'Cadastro realizado.', 'Sucesso');
                                this.fecharModal();
                                this.listar();
                            } else {
                                window.fiscalToast?.('error', data.message || 'Falha ao salvar.', 'Erro');
                            }
                        } catch (err) {
                            window.fiscalToast?.('error', 'Erro na conexão com o servidor.', 'Erro');
                        }
                    },

                    async excluirCliente(cliente) {
                        if (!confirm('Deseja realmente excluir o cliente ' + cliente.razao_social + '?')) {
                            return;
                        }
                        try {
                            const response = await fiscalFetch('/api/clientes/' + cliente.id, {
                                method: 'DELETE',
                                headers: { 'Authorization': 'Bearer ' + localStorage.getItem('nfe_token') }
                            });
                            const data = await response.json();
                            if (response.ok) {
                                window.fiscalToast?.('success', data.message || 'Excluído.', 'Sucesso');
                                this.listar();
                            }
                        } catch (err) {
                            window.fiscalToast?.('error', 'Falha ao excluir.', 'Erro');
                        }
                    },

                    formatDocumento(doc) {
                        if (!doc) return '';
                        const clean = doc.replace(/\D/g, '');
                        if (clean.length === 14) {
                            return clean.replace(/^(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})$/, '$1.$2.$3/$4-$5');
                        }
                        if (clean.length === 11) {
                            return clean.replace(/^(\d{3})(\d{3})(\d{3})(\d{2})$/, '$1.$2.$3-$4');
                        }
                        return doc;
                    }
                }));
            });
        }
    </script>
</x-app-layout>
