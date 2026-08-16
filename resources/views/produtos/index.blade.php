<x-app-layout title="Produtos" :partial="$partial ?? false">
    <div class="w-full mt-2" x-data="produtoManager" x-init="init()">
        <div class="mb-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div class="relative flex-1 max-w-md">
                <span class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none text-slate-400">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </span>
                <input type="text" x-model="searchQuery" placeholder="Buscar produto por código ou descrição..." class="block w-full pl-10 pr-3 py-2 border border-slate-300 rounded-md text-sm focus:ring-blue-500 focus:border-blue-500">
            </div>
            <div class="flex items-center gap-2">
                <button type="button" x-show="can('produtos.criar')" @click="abrirNovoProduto" class="bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-blue-700">Novo produto</button>
            </div>
        </div>

        <div class="space-y-6">
            <section class="border border-slate-200 bg-white shadow-sm">
                <div class="flex flex-col gap-3 border-b border-slate-200 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h2 class="text-sm font-semibold text-slate-900">Produtos no Catálogo</h2>
                        <p class="mt-1 text-xs text-slate-500">Gerencie o portfólio de itens de venda da sua empresa.</p>
                    </div>
                    <span class="text-xs text-slate-500" x-text="filteredProdutos().length + ' produto(s)'"></span>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[820px] text-sm border-collapse border border-slate-200">
                        <thead class="bg-slate-100 text-center text-xs uppercase tracking-wide text-slate-700 border-b border-slate-200">
                            <tr>
                                <th class="px-5 py-3 border border-slate-200 text-center">Código</th>
                                <th class="px-5 py-3 border border-slate-200 text-center">Descrição</th>
                                <th class="px-5 py-3 border border-slate-200 text-center">NCM</th>
                                <th class="px-5 py-3 border border-slate-200 text-center">Unidade</th>
                                <th class="px-5 py-3 border border-slate-200 text-center">Valor Unitário</th>
                                <th class="px-5 py-3 border border-slate-200 text-center">Status</th>
                                <th class="px-5 py-3 border border-slate-200 text-center">Ações</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200 text-center">
                            <template x-for="produto in paginatedProdutos()" :key="produto.id">
                                <tr class="odd:bg-white even:bg-slate-50 hover:bg-slate-100/80 transition-colors">
                                    <td class="px-5 py-3.5 border border-slate-200 font-semibold text-slate-700 text-center" x-text="produto.codigo"></td>
                                    <td class="px-5 py-3.5 border border-slate-200 font-medium text-slate-900 text-center" x-text="produto.descricao"></td>
                                    <td class="px-5 py-3.5 border border-slate-200 text-slate-600 text-center" x-text="produto.ncm"></td>
                                    <td class="px-5 py-3.5 border border-slate-200 text-slate-600 text-center" x-text="produto.unidade"></td>
                                    <td class="px-5 py-3.5 border border-slate-200 font-semibold text-slate-800 text-center" x-text="money(produto.valor_unitario)"></td>
                                    <td class="px-5 py-3.5 border border-slate-200 text-center">
                                        <span class="inline-flex px-2 py-1 text-xs font-semibold" :class="produto.ativo ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-500'" x-text="produto.ativo ? 'Ativo' : 'Inativo'"></span>
                                    </td>
                                    <td class="px-5 py-3.5 border border-slate-200 text-center space-x-2">
                                        <button x-show="can('produtos.editar')" type="button" @click="editarProduto(produto)" class="text-blue-600 hover:text-blue-800 transition" title="Editar Produto">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-5 h-5 inline">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10" />
                                            </svg>
                                        </button>
                                        <button x-show="can('produtos.excluir')" type="button" @click="excluirProduto(produto)" class="text-red-600 hover:text-red-800 transition" title="Excluir Produto">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-5 h-5 inline">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </button>
                                    </td>
                                </tr>
                            </template>
                            <tr x-show="!isLoading && !filteredProdutos().length" x-cloak>
                                <td colspan="7" class="px-5 py-10 text-center text-sm text-slate-500">Nenhum produto cadastrado.</td>
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
                        <h2 class="mt-1 text-xl font-bold text-slate-950" x-text="form.id ? 'Editar produto' : 'Novo produto'"></h2>
                        <p class="mt-1 text-sm text-slate-500">Preencha os campos para salvar as informações no catálogo.</p>
                    </div>
                    <button type="button" @click="fecharModal" class="px-3 py-2 text-sm font-semibold text-slate-500 hover:bg-slate-100">Fechar</button>
                </div>

                <form @submit.prevent="salvar" class="flex min-h-0 flex-1 flex-col">
                    <div class="min-h-0 flex-1 overflow-y-auto p-5 space-y-4">
                        <div class="grid gap-4 md:grid-cols-2">
                            <label>
                                <span class="label">Código do Produto <b class="text-red-600">*</b></span>
                                <input x-model="form.codigo" class="field" required placeholder="Ex: PROD-001">
                            </label>
                            <label>
                                <span class="label">Descrição / Nome <b class="text-red-600">*</b></span>
                                <input x-model="form.descricao" class="field" required placeholder="Ex: Parafuso Sextavado 8mm">
                            </label>
                        </div>
                        <div class="grid gap-4 md:grid-cols-3">
                            <label>
                                <span class="label">NCM (8 dígitos) <b class="text-red-600">*</b></span>
                                <input x-model="form.ncm" x-mask="9999.99.99" class="field" required placeholder="Ex: 7318.15.00">
                            </label>
                            <label>
                                <span class="label">Unidade Comercial <b class="text-red-600">*</b></span>
                                <input x-model="form.unidade" class="field" required placeholder="Ex: UN, KG, PC">
                            </label>
                            <label>
                                <span class="label">Valor Unitário <b class="text-red-600">*</b></span>
                                <input x-model="form.valor_unitario" 
                                       type="text" 
                                       @input="formatMoneyInput"
                                       class="field" required placeholder="Ex: 15,5000">
                            </label>
                        </div>
                        <div class="grid gap-4 md:grid-cols-3">
                            <label>
                                <span class="label">CFOP Padrão</span>
                                <input x-model="form.cfop" class="field" maxlength="4" placeholder="Ex: 5102">
                            </label>
                            <label>
                                <span class="label">CSOSN Padrão</span>
                                <input x-model="form.csosn" class="field" maxlength="4" placeholder="Ex: 101">
                            </label>
                            <label>
                                <span class="label">CST Padrão</span>
                                <input x-model="form.cst" class="field" maxlength="3" placeholder="Ex: 000">
                            </label>
                        </div>
                        <div class="flex items-center gap-2">
                            <input x-model="form.ativo" type="checkbox" id="ativo" class="h-4 w-4 border-slate-300 text-blue-600 focus:ring-blue-500">
                            <label for="ativo" class="text-sm font-semibold text-slate-700">Produto Ativo</label>
                        </div>
                    </div>
                    <div class="flex shrink-0 items-center justify-end gap-3 border-t border-slate-200 px-5 py-4">
                        <button type="button" @click="fecharModal" class="border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">Cancelar</button>
                        <button type="submit" class="bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-blue-700">Salvar Produto</button>
                    </div>
                </form>
            </div>
       <script>
        if (!window.fiscalProdutosManagerRegistered) {
            window.fiscalProdutosManagerRegistered = true;
            window.registerFiscalAlpine((Alpine) => {
                Alpine.data('produtoManager', () => ({
                    produtos: [],
                    isLoading: false,
                    modalAberto: false,
                    searchQuery: '',
                    currentPage: 1,
                    itemsPerPage: 10,
                    form: {
                        id: null,
                        codigo: '',
                        descricao: '',
                        ncm: '',
                        unidade: 'UN',
                        valor_unitario: 0.00,
                        cfop: '',
                        csosn: '',
                        cst: '',
                        ativo: true
                    },

                    init() {
                        this.listar();
                        this.$watch('searchQuery', () => {
                            this.currentPage = 1;
                        });
                    },

                    paginatedProdutos() {
                        const start = (this.currentPage - 1) * this.itemsPerPage;
                        return this.filteredProdutos().slice(start, start + this.itemsPerPage);
                    },

                    totalPages() {
                        return Math.ceil(this.filteredProdutos().length / this.itemsPerPage);
                    },

                    prevPage() {
                        if (this.currentPage > 1) this.currentPage--;
                    },

                    nextPage() {
                        if (this.currentPage < this.totalPages()) this.currentPage++;
                    },

                    filteredProdutos() {
                        if (!this.searchQuery) return this.produtos;
                        const query = this.searchQuery.toLowerCase();
                        return this.produtos.filter(item => 
                            (item.codigo && item.codigo.toLowerCase().includes(query)) ||
                            (item.descricao && item.descricao.toLowerCase().includes(query))
                        );
                    },

                    async listar() {
                        this.isLoading = true;
                        try {
                            const response = await fiscalFetch('/api/produtos', {
                                headers: { 'Authorization': 'Bearer ' + localStorage.getItem('nfe_token') }
                            });
                            if (response.ok) {
                                this.produtos = await response.json();
                            }
                        } catch (err) {
                            window.fiscalToast?.('error', 'Não foi possível carregar produtos.', 'Erro');
                        } finally {
                            this.isLoading = false;
                        }
                    },

                    abrirNovoProduto() {
                        this.form = {
                            id: null,
                            codigo: '',
                            descricao: '',
                            ncm: '',
                            unidade: 'UN',
                            valor_unitario: 0.00,
                            cfop: '',
                            csosn: '',
                            cst: '',
                            ativo: true
                        };
                        this.modalAberto = true;
                    },

                    editarProduto(produto) {
                        this.form = { ...produto };
                        if (this.form.ncm) {
                            // format 73181500 to 7318.15.00
                            const clean = this.form.ncm.replace(/\D/g, '');
                            if (clean.length === 8) {
                                this.form.ncm = clean.replace(/^(\d{4})(\d{2})(\d{2})$/, '$1.$2.$3');
                            }
                        }
                        if (this.form.valor_unitario !== undefined && this.form.valor_unitario !== null) {
                            // format float to string 15.5000 -> 15,5000 (allowing easy edit with money mask)
                            this.form.valor_unitario = Number(this.form.valor_unitario)
                                .toFixed(4)
                                .replace('.', ',');
                        }
                        this.modalAberto = true;
                    },

                    fecharModal() {
                        this.modalAberto = false;
                    },

                    async salvar() {
                        const method = this.form.id ? 'PUT' : 'POST';
                        const endpoint = this.form.id ? '/api/produtos/' + this.form.id : '/api/produtos';

                        // Prepare payload with unformatted numeric values
                        const payload = { ...this.form };
                        if (payload.ncm) {
                            payload.ncm = payload.ncm.replace(/\D/g, '');
                        }
                        if (payload.valor_unitario && typeof payload.valor_unitario === 'string') {
                            payload.valor_unitario = parseFloat(
                                payload.valor_unitario
                                    .replace(/\./g, '')
                                    .replace(',', '.')
                            ) || 0;
                        }

                        try {
                            const response = await fiscalFetch(endpoint, {
                                method: method,
                                headers: {
                                    'Content-Type': 'application/json',
                                    'Authorization': 'Bearer ' + localStorage.getItem('nfe_token')
                                },
                                body: JSON.stringify(payload)
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

                    formatMoneyInput(e) {
                        let value = e.target.value.replace(/\D/g, '');
                        if (!value) {
                            this.form.valor_unitario = '';
                            return;
                        }
                        // Fill zeros to have at least 4 digits
                        while (value.length < 5) {
                            value = '0' + value;
                        }
                        const integerPart = value.slice(0, -4);
                        const decimalPart = value.slice(-4);
                        
                        // format integer part with dot separators
                        const formattedInteger = Number(integerPart).toLocaleString('pt-BR');
                        this.form.valor_unitario = formattedInteger + ',' + decimalPart;
                    },

                    async excluirProduto(produto) {
                        if (!confirm('Deseja realmente excluir o produto ' + produto.descricao + '?')) {
                            return;
                        }
                        try {
                            const response = await fiscalFetch('/api/produtos/' + produto.id, {
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

                    money(value) {
                        return new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(Number(value) || 0);
                    }
                }));
            });
        }
    </script>
</x-app-layout>
