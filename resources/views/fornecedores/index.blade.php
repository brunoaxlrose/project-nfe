<x-app-layout title="Fornecedores" :partial="$partial ?? false">
    <div class="w-full mt-2" x-data="fornecedorManager" x-init="init()">
        <div class="mb-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div class="relative flex-1 max-w-md">
                <span class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none text-slate-400">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </span>
                <input type="text" x-model="searchQuery" placeholder="Buscar fornecedor por nome ou documento..." class="block w-full pl-10 pr-3 py-2 border border-slate-300 rounded-md text-sm focus:ring-blue-500 focus:border-blue-500">
            </div>
            <div class="flex items-center gap-2">
                <button type="button" x-show="can('fornecedores.criar')" @click="abrirNovoFornecedor" class="bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-blue-700">Novo fornecedor</button>
            </div>
        </div>

        <div class="space-y-6">
            <section class="border border-slate-200 bg-white shadow-sm">
                <div class="flex flex-col gap-3 border-b border-slate-200 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h2 class="text-sm font-semibold text-slate-900">Fornecedores Cadastrados</h2>
                        <p class="mt-1 text-xs text-slate-500">Lista de parceiros de negócios e fornecedores de produtos da empresa.</p>
                    </div>
                    <span class="text-xs text-slate-500" x-text="filteredFornecedores().length + ' fornecedor(es)'"></span>
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
                            <template x-for="fornecedor in paginatedFornecedores()" :key="fornecedor.id">
                                <tr class="odd:bg-white even:bg-slate-50 hover:bg-slate-100/80 transition-colors">
                                    <td class="px-5 py-3.5 border border-slate-200 font-medium text-slate-900 text-center" x-text="fornecedor.nome_razao_social"></td>
                                    <td class="px-5 py-3.5 border border-slate-200 text-slate-600 text-center" x-text="formatDocumento(fornecedor.documento)"></td>
                                    <td class="px-5 py-3.5 border border-slate-200 text-slate-600 text-center" x-text="fornecedor.inscricao_estadual || 'Isento/Não informado'"></td>
                                    <td class="px-5 py-3.5 border border-slate-200 text-slate-600 text-center" x-text="fornecedor.municipio ? fornecedor.municipio + ' / ' + fornecedor.uf : 'Não informado'"></td>
                                    <td class="px-5 py-3.5 border border-slate-200 text-center">
                                        <span class="inline-flex px-2 py-1 text-xs font-semibold" :class="fornecedor.ativo ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-500'" x-text="fornecedor.ativo ? 'Ativo' : 'Inativo'"></span>
                                    </td>
                                    <td class="px-5 py-3.5 border border-slate-200 text-center space-x-2">
                                        <button x-show="can('fornecedores.editar')" type="button" @click="editarFornecedor(fornecedor)" class="text-blue-600 hover:text-blue-800 transition" title="Editar Fornecedor">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-5 h-5 inline">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10" />
                                            </svg>
                                        </button>
                                        <button x-show="can('fornecedores.excluir')" type="button" @click="excluirFornecedor(fornecedor)" class="text-red-600 hover:text-red-800 transition" title="Excluir Fornecedor">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-5 h-5 inline">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </button>
                                    </td>
                                </tr>
                            </template>
                            <tr x-show="!isLoading && !filteredFornecedores().length" x-cloak>
                                <td colspan="6" class="px-5 py-10 text-center text-sm text-slate-500">Nenhum fornecedor cadastrado.</td>
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
                        <h2 class="mt-1 text-xl font-bold text-slate-950" x-text="form.id ? 'Editar fornecedor' : 'Novo fornecedor'"></h2>
                        <p class="mt-1 text-sm text-slate-500">Preencha os campos para salvar as informações cadastrais.</p>
                    </div>
                    <button type="button" @click="fecharModal" class="px-3 py-2 text-sm font-semibold text-slate-500 hover:bg-slate-100">Fechar</button>
                </div>

                <form @submit.prevent="salvar" class="flex min-h-0 flex-1 flex-col">
                    <div class="min-h-0 flex-1 overflow-y-auto p-5 space-y-4">
                        <div class="grid gap-4 md:grid-cols-2">
                            <label>
                                <span class="label">Razão Social / Nome completo</span>
                                <input x-model="form.nome_razao_social" class="field" required placeholder="Ex: Distribuidora Brasil LTDA">
                            </label>
                            <label>
                                <span class="label">CPF ou CNPJ</span>
                                <input x-model="form.documento" class="field" required placeholder="Apenas números">
                            </label>
                        </div>
                        <div class="grid gap-4 md:grid-cols-2">
                            <label>
                                <span class="label">Inscrição Estadual</span>
                                <input x-model="form.inscricao_estadual" class="field" placeholder="Inscrição Estadual ou em branco se isento">
                            </label>
                            <label>
                                <span class="label">CEP</span>
                                <input x-model="form.cep" class="field" placeholder="Ex: 01001000">
                            </label>
                        </div>
                        <div class="grid gap-4 md:grid-cols-3">
                            <label class="md:col-span-2">
                                <span class="label">Logradouro</span>
                                <input x-model="form.logradouro" class="field" placeholder="Ex: Av. Paulista">
                            </label>
                            <label>
                                <span class="label">Número</span>
                                <input x-model="form.numero" class="field" placeholder="Ex: 1000">
                            </label>
                        </div>
                        <div class="grid gap-4 md:grid-cols-3">
                            <label>
                                <span class="label">Bairro</span>
                                <input x-model="form.bairro" class="field" placeholder="Ex: Centro">
                            </label>
                            <label>
                                <span class="label">Município</span>
                                <input x-model="form.municipio" class="field" placeholder="Ex: São Paulo">
                            </label>
                            <label>
                                <span class="label">UF</span>
                                <input x-model="form.uf" class="field" maxlength="2" placeholder="Ex: SP">
                            </label>
                        </div>
                        <div class="flex items-center gap-2">
                            <input x-model="form.ativo" type="checkbox" id="ativo" class="h-4 w-4 border-slate-300 text-blue-600 focus:ring-blue-500">
                            <label for="ativo" class="text-sm font-semibold text-slate-700">Fornecedor Ativo</label>
                        </div>
                    </div>
                    <div class="flex shrink-0 items-center justify-end gap-3 border-t border-slate-200 px-5 py-4">
                        <button type="button" @click="fecharModal" class="border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">Cancelar</button>
                        <button type="submit" class="bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-blue-700">Salvar Cadastro</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        if (!window.fiscalFornecedoresManagerRegistered) {
            window.fiscalFornecedoresManagerRegistered = true;
            window.registerFiscalAlpine((Alpine) => {
                Alpine.data('fornecedorManager', () => ({
                    fornecedores: [],
                    isLoading: false,
                    modalAberto: false,
                    searchQuery: '',
                    currentPage: 1,
                    itemsPerPage: 10,
                    form: {
                        id: null,
                        nome_razao_social: '',
                        documento: '',
                        inscricao_estadual: '',
                        cep: '',
                        logradouro: '',
                        numero: '',
                        complemento: '',
                        bairro: '',
                        municipio: '',
                        uf: '',
                        ativo: true
                    },

                    init() {
                        this.listar();
                        this.$watch('searchQuery', () => {
                            this.currentPage = 1;
                        });
                    },

                    filteredFornecedores() {
                        if (!this.searchQuery) return this.fornecedores;
                        const query = this.searchQuery.toLowerCase();
                        return this.fornecedores.filter(item => 
                            (item.nome_razao_social && item.nome_razao_social.toLowerCase().includes(query)) ||
                            (item.documento && item.documento.includes(query))
                        );
                    },

                    paginatedFornecedores() {
                        const start = (this.currentPage - 1) * this.itemsPerPage;
                        return this.filteredFornecedores().slice(start, start + this.itemsPerPage);
                    },

                    totalPages() {
                        return Math.ceil(this.filteredFornecedores().length / this.itemsPerPage);
                    },

                    prevPage() {
                        if (this.currentPage > 1) this.currentPage--;
                    },

                    nextPage() {
                        if (this.currentPage < this.totalPages()) this.currentPage++;
                    },

                    async listar() {
                        this.isLoading = true;
                        try {
                            const response = await fiscalFetch('/api/fornecedores', {
                                headers: { 'Authorization': 'Bearer ' + localStorage.getItem('nfe_token') }
                            });
                            if (response.ok) {
                                this.fornecedores = await response.json();
                            }
                        } catch (err) {
                            window.fiscalToast?.('error', 'Não foi possível carregar fornecedores.', 'Erro');
                        } finally {
                            this.isLoading = false;
                        }
                    },

                    abrirNovoFornecedor() {
                        this.form = {
                            id: null,
                            nome_razao_social: '',
                            documento: '',
                            inscricao_estadual: '',
                            cep: '',
                            logradouro: '',
                            numero: '',
                            complemento: '',
                            bairro: '',
                            municipio: '',
                            uf: '',
                            ativo: true
                        };
                        this.modalAberto = true;
                    },

                    editarFornecedor(fornecedor) {
                        this.form = { ...fornecedor };
                        this.modalAberto = true;
                    },

                    fecharModal() {
                        this.modalAberto = false;
                    },

                    async salvar() {
                        const method = this.form.id ? 'PUT' : 'POST';
                        const endpoint = this.form.id ? '/api/fornecedores/' + this.form.id : '/api/fornecedores';

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

                    async excluirFornecedor(fornecedor) {
                        if (!confirm('Deseja realmente excluir o fornecedor ' + fornecedor.nome_razao_social + '?')) {
                            return;
                        }
                        try {
                            const response = await fiscalFetch('/api/fornecedores/' + fornecedor.id, {
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
