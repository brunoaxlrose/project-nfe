<x-app-layout title="Naturezas de Operação" header="Naturezas de Operação" :partial="$partial ?? false">
    <div x-data="naturezaManager()" class="w-full mt-2 space-y-4">
        
        <!-- Header & Action Bar -->
        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 bg-white p-4 border border-slate-200">
            <div class="flex-1 w-full sm:w-auto">
                <div class="relative">
                    <input 
                        type="text" 
                        x-model="searchQuery" 
                        placeholder="Buscar por descrição, CFOP ou CSOSN..." 
                        class="w-full pl-10 pr-4 py-2 border border-slate-200 text-sm focus:outline-none focus:border-blue-600 focus:ring-1 focus:ring-blue-600"
                    >
                    <span class="absolute left-3 top-2.5 text-slate-400">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </span>
                </div>
            </div>
            <div class="flex items-center gap-2 w-full sm:w-auto justify-end">
                <button 
                    x-show="can('naturezas.criar')" 
                    x-cloak
                    type="button" 
                    @click="abrirNovaNatureza" 
                    class="bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700 transition flex items-center gap-2"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    Nova Natureza
                </button>
            </div>
        </div>

        <!-- Table Listing Card -->
        <div class="bg-white border border-slate-200">
            <div class="p-4 border-b border-slate-200 flex justify-between items-center bg-slate-50/50">
                <div>
                    <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wider">Naturezas de Operação Cadastradas</h3>
                    <p class="text-xs text-slate-500 mt-0.5">Gerencie as naturezas fiscais de entrada e saída utilizadas na emissão de NF-e.</p>
                </div>
                <span class="text-xs font-semibold text-slate-500 bg-slate-100 px-2.5 py-1 rounded" x-text="`${filteredNaturezas().length} natureza(s)`"></span>
            </div>

            <div class="relative overflow-x-auto">
                <table class="w-full text-left text-sm text-slate-600 border-collapse">
                    <thead class="bg-slate-50 text-xs font-semibold uppercase tracking-wider text-slate-700 border-b border-slate-200">
                        <tr>
                            <th class="px-5 py-3 border border-slate-200">Descrição / Nome</th>
                            <th class="px-5 py-3 border border-slate-200 text-center">Tipo</th>
                            <th class="px-5 py-3 border border-slate-200 text-center">CFOP Padrão</th>
                            <th class="px-5 py-3 border border-slate-200 text-center">CSOSN / CST</th>
                            <th class="px-5 py-3 border border-slate-200 text-center">Regras Tributárias</th>
                            <th class="px-5 py-3 border border-slate-200 text-center">Status</th>
                            <th class="px-5 py-3 border border-slate-200 text-center">Ações</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200">
                        <template x-for="natureza in paginatedNaturezas()" :key="natureza.id">
                            <tr class="odd:bg-white even:bg-slate-50 hover:bg-slate-100/80 transition-colors">
                                <td class="px-5 py-3.5 border border-slate-200 font-semibold text-slate-900">
                                    <span x-text="natureza.nome"></span>
                                    <template x-if="natureza.informacoes_complementares">
                                        <p class="text-xs font-normal text-slate-500 mt-0.5 max-w-md truncate" x-text="natureza.informacoes_complementares"></p>
                                    </template>
                                </td>
                                <td class="px-5 py-3.5 border border-slate-200 text-center">
                                    <span class="inline-flex items-center px-2 py-0.5 text-xs font-medium rounded"
                                        :class="natureza.tipo_movimento === 'Entrada' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-blue-50 text-blue-700 border border-blue-200'"
                                        x-text="natureza.tipo_movimento">
                                    </span>
                                </td>
                                <td class="px-5 py-3.5 border border-slate-200 text-center font-mono font-semibold text-slate-800" x-text="natureza.cfop_padrao"></td>
                                <td class="px-5 py-3.5 border border-slate-200 text-center text-slate-600 font-mono" x-text="natureza.csosn_padrao || natureza.cst_padrao || '-'"></td>
                                <td class="px-5 py-3.5 border border-slate-200 text-center text-xs">
                                    <div class="flex items-center justify-center gap-1">
                                        <span class="px-1.5 py-0.5 rounded text-[11px] font-medium" :class="natureza.calcula_icms ? 'bg-blue-100 text-blue-700' : 'bg-slate-100 text-slate-400'">ICMS</span>
                                        <span class="px-1.5 py-0.5 rounded text-[11px] font-medium" :class="natureza.calcula_ipi ? 'bg-indigo-100 text-indigo-700' : 'bg-slate-100 text-slate-400'">IPI</span>
                                        <span class="px-1.5 py-0.5 rounded text-[11px] font-medium" :class="natureza.calcula_pis ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-400'">PIS</span>
                                        <span class="px-1.5 py-0.5 rounded text-[11px] font-medium" :class="natureza.calcula_cofins ? 'bg-amber-100 text-amber-700' : 'bg-slate-100 text-slate-400'">COF</span>
                                    </div>
                                </td>
                                <td class="px-5 py-3.5 border border-slate-200 text-center">
                                    <button type="button" @click="toggleAtivo(natureza)" class="relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none" :class="natureza.ativa ? 'bg-emerald-500' : 'bg-slate-200'">
                                        <span aria-hidden="true" class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out" :class="natureza.ativa ? 'translate-x-5' : 'translate-x-0'"></span>
                                    </button>
                                </td>
                                <td class="px-5 py-3.5 border border-slate-200 text-center space-x-2">
                                    <button x-show="can('naturezas.editar')" type="button" @click="editarNatureza(natureza)" class="text-blue-600 hover:text-blue-800 transition" title="Editar Natureza">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-5 h-5 inline">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10" />
                                        </svg>
                                    </button>
                                    <button x-show="can('naturezas.excluir')" type="button" @click="excluirNatureza(natureza)" class="text-red-600 hover:text-red-800 transition" title="Excluir Natureza">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-5 h-5 inline">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                    </button>
                                </td>
                            </tr>
                        </template>
                        <tr x-show="!isLoading && !filteredNaturezas().length" x-cloak>
                            <td colspan="7" class="px-5 py-10 text-center text-sm text-slate-500">Nenhuma natureza de operação encontrada.</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination Bar -->
            <div class="p-4 border-t border-slate-200 flex flex-col sm:flex-row justify-between items-center gap-3 bg-white" x-show="filteredNaturezas().length > 0">
                <span class="text-xs text-slate-500">
                    Exibindo página <span class="font-semibold text-slate-700" x-text="currentPage"></span> de <span class="font-semibold text-slate-700" x-text="totalPages()"></span>
                </span>
                <div class="flex items-center gap-1">
                    <button 
                        type="button" 
                        @click="prevPage" 
                        :disabled="currentPage === 1" 
                        class="px-3 py-1.5 border border-slate-200 text-xs font-semibold text-slate-600 hover:bg-slate-50 disabled:opacity-40 disabled:cursor-not-allowed transition"
                    >
                        Anterior
                    </button>
                    <button 
                        type="button" 
                        @click="nextPage" 
                        :disabled="currentPage >= totalPages()" 
                        class="px-3 py-1.5 border border-slate-200 text-xs font-semibold text-slate-600 hover:bg-slate-50 disabled:opacity-40 disabled:cursor-not-allowed transition"
                    >
                        Próximo
                    </button>
                </div>
            </div>
        </div>

        <!-- Modal Cadastro/Edição de Natureza de Operação -->
        <div 
            x-show="modalAberto" 
            x-cloak 
            class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 p-4 backdrop-blur-sm"
        >
            <div 
                @click.away="fecharModal" 
                class="flex max-h-[90vh] w-full max-w-2xl flex-col bg-white border border-slate-200 shadow-2xl"
            >
                <div class="flex items-center justify-between border-b border-slate-200 px-6 py-4 bg-slate-50/50">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-wider text-blue-600">Cadastro Fiscal</p>
                        <h3 class="text-lg font-bold text-slate-900" x-text="form.id ? 'Editar Natureza de Operação' : 'Nova Natureza de Operação'"></h3>
                    </div>
                    <button type="button" @click="fecharModal" class="px-3 py-1.5 text-sm font-semibold text-slate-500 hover:bg-slate-100">Fechar</button>
                </div>

                <form @submit.prevent="salvar" class="flex min-h-0 flex-1 flex-col">
                    <div class="min-h-0 flex-1 overflow-y-auto p-6 space-y-5">
                        
                        <!-- Dados Gerais -->
                        <div>
                            <h4 class="text-xs font-bold uppercase tracking-wider text-slate-500 mb-3">Dados Gerais</h4>
                            <div class="space-y-4">
                                <div>
                                    <label class="block">
                                        <span class="label">Descrição / Nome da Operação <b class="text-red-600">*</b></span>
                                        <input x-model="form.nome" class="field" required placeholder="Ex: Venda de mercadoria adquirida de terceiros">
                                    </label>
                                </div>
                                <div class="grid gap-4 md:grid-cols-2">
                                    <label>
                                        <span class="label">Tipo de Movimento <b class="text-red-600">*</b></span>
                                        <select x-model="form.tipo_movimento" class="field" required>
                                            <option value="Saída">Saída</option>
                                            <option value="Entrada">Entrada</option>
                                        </select>
                                    </label>
                                    <label>
                                        <span class="label">CFOP Padrão <b class="text-red-600">*</b></span>
                                        <input x-model="form.cfop_padrao" class="field" required maxlength="4" placeholder="Ex: 5102" inputmode="numeric">
                                    </label>
                                </div>
                                <div class="grid gap-4 md:grid-cols-2">
                                    <label>
                                        <span class="label">CSOSN Padrão (Simples Nacional)</span>
                                        <input x-model="form.csosn_padrao" class="field" maxlength="4" placeholder="Ex: 0102" inputmode="numeric">
                                    </label>
                                    <label>
                                        <span class="label">CST Padrão (Regime Normal)</span>
                                        <input x-model="form.cst_padrao" class="field" maxlength="3" placeholder="Ex: 000" inputmode="numeric">
                                    </label>
                                </div>
                            </div>
                        </div>

                        <!-- Regras de Tributação -->
                        <div class="border-t border-slate-200 pt-5">
                            <h4 class="text-xs font-bold uppercase tracking-wider text-slate-500 mb-3">Regras de Cálculo de Impostos</h4>
                            <div class="grid gap-3 sm:grid-cols-2 md:grid-cols-4 bg-slate-50 p-4 border border-slate-200 rounded">
                                <label class="flex items-center gap-2.5 cursor-pointer">
                                    <input x-model="form.calcula_icms" type="checkbox" class="h-4 w-4 text-blue-600 rounded border-slate-300 focus:ring-blue-500">
                                    <span class="text-xs font-semibold text-slate-700">Calcula ICMS</span>
                                </label>
                                <label class="flex items-center gap-2.5 cursor-pointer">
                                    <input x-model="form.calcula_ipi" type="checkbox" class="h-4 w-4 text-blue-600 rounded border-slate-300 focus:ring-blue-500">
                                    <span class="text-xs font-semibold text-slate-700">Calcula IPI</span>
                                </label>
                                <label class="flex items-center gap-2.5 cursor-pointer">
                                    <input x-model="form.calcula_pis" type="checkbox" class="h-4 w-4 text-blue-600 rounded border-slate-300 focus:ring-blue-500">
                                    <span class="text-xs font-semibold text-slate-700">Calcula PIS</span>
                                </label>
                                <label class="flex items-center gap-2.5 cursor-pointer">
                                    <input x-model="form.calcula_cofins" type="checkbox" class="h-4 w-4 text-blue-600 rounded border-slate-300 focus:ring-blue-500">
                                    <span class="text-xs font-semibold text-slate-700">Calcula COFINS</span>
                                </label>
                            </div>
                        </div>

                        <!-- Informações Complementares -->
                        <div class="border-t border-slate-200 pt-5">
                            <label class="block">
                                <span class="label">Informações Complementares de Interesse do Fisco (Padrão no DANFE/XML)</span>
                                <textarea x-model="form.informacoes_complementares" rows="3" class="field" placeholder="Texto fixo que será sugerido automaticamente no campo de informações complementares ao emitir notas com esta natureza..."></textarea>
                            </label>
                        </div>

                        <div class="flex items-center gap-2 pt-2">
                            <input x-model="form.ativa" type="checkbox" id="natureza_ativa" class="h-4 w-4 text-blue-600 rounded border-slate-300 focus:ring-blue-500">
                            <label for="natureza_ativa" class="text-sm font-semibold text-slate-800 cursor-pointer">Natureza de Operação Ativa</label>
                        </div>

                    </div>
                    
                    <div class="flex justify-end gap-3 border-t border-slate-200 bg-slate-50 px-6 py-4">
                        <button type="button" @click="fecharModal" class="border border-slate-200 px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-100">Cancelar</button>
                        <button type="submit" class="bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-blue-700">Salvar Natureza</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        if (!window.fiscalNaturezaManagerRegistered) {
            window.fiscalNaturezaManagerRegistered = true;
            window.registerFiscalAlpine((Alpine) => {
                Alpine.data('naturezaManager', () => ({
                    naturezas: [],
                    isLoading: false,
                    modalAberto: false,
                    searchQuery: '',
                    currentPage: 1,
                    itemsPerPage: 10,
                    form: {
                        id: null,
                        nome: '',
                        tipo_movimento: 'Saída',
                        cfop_padrao: '',
                        csosn_padrao: '',
                        cst_padrao: '',
                        calcula_impostos: false,
                        calcula_icms: false,
                        calcula_ipi: false,
                        calcula_pis: false,
                        calcula_cofins: false,
                        informacoes_complementares: '',
                        ativa: true
                    },

                    init() {
                        this.listar();
                        this.$watch('searchQuery', () => {
                            this.currentPage = 1;
                        });
                    },

                    filteredNaturezas() {
                        if (!this.searchQuery) return this.naturezas;
                        const query = this.searchQuery.toLowerCase();
                        return this.naturezas.filter(item => 
                            (item.nome && item.nome.toLowerCase().includes(query)) ||
                            (item.cfop_padrao && item.cfop_padrao.includes(query)) ||
                            (item.csosn_padrao && item.csosn_padrao.includes(query)) ||
                            (item.tipo_movimento && item.tipo_movimento.toLowerCase().includes(query))
                        );
                    },

                    paginatedNaturezas() {
                        const start = (this.currentPage - 1) * this.itemsPerPage;
                        return this.filteredNaturezas().slice(start, start + this.itemsPerPage);
                    },

                    totalPages() {
                        return Math.ceil(this.filteredNaturezas().length / this.itemsPerPage) || 1;
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
                            const response = await fiscalFetch('/api/naturezas-operacao?all=1', {
                                headers: { 'Authorization': 'Bearer ' + localStorage.getItem('nfe_token') }
                            });
                            if (response.ok) {
                                this.naturezas = await response.json();
                            }
                        } catch (err) {
                            window.fiscalToast?.('error', 'Não foi possível carregar as naturezas de operação.', 'Erro');
                        } finally {
                            this.isLoading = false;
                        }
                    },

                    abrirNovaNatureza() {
                        this.form = {
                            id: null,
                            nome: '',
                            tipo_movimento: 'Saída',
                            cfop_padrao: '',
                            csosn_padrao: '0400',
                            cst_padrao: '',
                            calcula_impostos: false,
                            calcula_icms: false,
                            calcula_ipi: false,
                            calcula_pis: false,
                            calcula_cofins: false,
                            informacoes_complementares: '',
                            ativa: true
                        };
                        this.modalAberto = true;
                    },

                    editarNatureza(natureza) {
                        this.form = {
                            id: natureza.id || natureza.id_natureza_operacao,
                            nome: natureza.nome || natureza.descricao || '',
                            tipo_movimento: natureza.tipo_movimento || 'Saída',
                            cfop_padrao: natureza.cfop_padrao || '',
                            csosn_padrao: natureza.csosn_padrao || '',
                            cst_padrao: natureza.cst_padrao || '',
                            calcula_impostos: !!natureza.calcula_impostos,
                            calcula_icms: !!natureza.calcula_icms,
                            calcula_ipi: !!natureza.calcula_ipi,
                            calcula_pis: !!natureza.calcula_pis,
                            calcula_cofins: !!natureza.calcula_cofins,
                            informacoes_complementares: natureza.informacoes_complementares || '',
                            ativa: natureza.ativa !== undefined ? !!natureza.ativa : true
                        };
                        this.modalAberto = true;
                    },

                    fecharModal() {
                        this.modalAberto = false;
                    },

                    async salvar() {
                        const id = this.form.id;
                        const method = id ? 'PUT' : 'POST';
                        const endpoint = id ? '/api/naturezas-operacao/' + id : '/api/naturezas-operacao';

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
                                window.fiscalToast?.('success', data.message || 'Natureza salva com sucesso.', 'Sucesso');
                                this.fecharModal();
                                this.listar();
                            } else {
                                window.fiscalToast?.('error', data.message || 'Falha ao salvar natureza.', 'Erro');
                            }
                        } catch (err) {
                            window.fiscalToast?.('error', 'Erro na conexão com o servidor.', 'Erro');
                        }
                    },

                    async toggleAtivo(natureza) {
                        const originalValue = natureza.ativa;
                        natureza.ativa = !natureza.ativa;
                        const id = natureza.id || natureza.id_natureza_operacao;

                        try {
                            const response = await fiscalFetch('/api/naturezas-operacao/' + id, {
                                method: 'PUT',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'Authorization': 'Bearer ' + localStorage.getItem('nfe_token')
                                },
                                body: JSON.stringify({
                                    nome: natureza.nome,
                                    tipo_movimento: natureza.tipo_movimento,
                                    cfop_padrao: natureza.cfop_padrao,
                                    csosn_padrao: natureza.csosn_padrao,
                                    cst_padrao: natureza.cst_padrao,
                                    calcula_impostos: natureza.calcula_impostos,
                                    calcula_icms: natureza.calcula_icms,
                                    calcula_ipi: natureza.calcula_ipi,
                                    calcula_pis: natureza.calcula_pis,
                                    calcula_cofins: natureza.calcula_cofins,
                                    informacoes_complementares: natureza.informacoes_complementares,
                                    ativa: natureza.ativa
                                })
                            });
                            if (!response.ok) {
                                throw new Error();
                            }
                            window.fiscalToast?.('success', `Natureza ${natureza.ativa ? 'ativada' : 'inativada'} com sucesso.`, 'Status');
                        } catch (err) {
                            natureza.ativa = originalValue;
                            window.fiscalToast?.('error', 'Falha ao alterar status da natureza.', 'Erro');
                        }
                    },

                    async excluirNatureza(natureza) {
                        const confirmado = await window.fiscalConfirm?.({
                            title: 'Excluir Natureza de Operação',
                            message: 'Deseja realmente excluir a natureza "' + (natureza.nome || natureza.descricao) + '"?',
                            confirmText: 'Excluir',
                            cancelText: 'Cancelar'
                        });
                        if (!confirmado) {
                            return;
                        }
                        const id = natureza.id || natureza.id_natureza_operacao;
                        try {
                            const response = await fiscalFetch('/api/naturezas-operacao/' + id, {
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
                    }
                }));
            });
        }
    </script>
</x-app-layout>
