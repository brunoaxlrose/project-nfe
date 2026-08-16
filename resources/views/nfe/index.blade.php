<x-app-layout title="Histórico de Notas" header="Notas fiscais de saída" :partial="$partial ?? false">
    <div x-data="notasPage" class="mx-auto w-full max-w-[1800px]">
        <div class="mb-6 flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <p class="mb-2 text-sm font-medium text-blue-600">Fiscal / Documentos</p>
                <h1 class="text-2xl font-bold tracking-tight text-slate-950 sm:text-3xl">Notas fiscais de saída</h1>
                <p class="mt-2 text-sm text-slate-500">Consulte, exporte e acompanhe as operações fiscais da sua empresa.</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <button type="button" @click="imprimir" class="inline-flex items-center gap-2 border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                    <span aria-hidden="true">⎙</span>
                    Imprimir
                </button>
                <button type="button" @click="exportar" class="inline-flex items-center gap-2 border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                    <span aria-hidden="true">↓</span>
                    Exportar CSV
                </button>
                <button x-show="can('nfe.criar')" x-cloak type="button" @click="abrirModalEmissao" class="inline-flex items-center gap-2 bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-blue-700">
                    <x-icon name="plus" class="h-4 w-4" />
                    Nova NF-e
                </button>
            </div>
        </div>

        <section class="border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 px-5 py-4 lg:px-6">
                <div class="flex flex-col gap-4 xl:flex-row xl:items-end">
                    <label class="min-w-0 flex-1">
                        <span class="label">Pesquisar</span>
                        <div class="relative">
                            <input x-model="filters.busca" @input.debounce.400ms="load()" class="field pr-10" placeholder="Nome, CPF/CNPJ ou número da nota">
                            <x-icon name="search" class="pointer-events-none absolute right-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
                        </div>
                    </label>
                    <label class="w-full xl:w-64">
                        <span class="label">Intervalo</span>
                        <select x-model="periodo" @change="setPeriodo($event.target.value)" class="field">
                            <option value="30d">Últimos 30 dias</option>
                            <option value="3m">Últimos 3 meses</option>
                            <option value="6m">Últimos 6 meses</option>
                            <option value="year">Este ano</option>
                            <option value="previous-year">Ano anterior</option>
                            <option value="all">Todos os períodos</option>
                            <option value="custom">Personalizado</option>
                        </select>
                    </label>
                    <label class="w-full xl:w-52">
                        <span class="label">Situação</span>
                        <select x-model="filters.status" @change="load()" class="field">
                            <option value="">Todas as situações</option>
                            <option value="autorizada">Emitida DANFE</option>
                            <option value="simulada">Simulada</option>
                            <option value="aguardando_retorno">Processando</option>
                            <option value="rejeitada">Rejeitada</option>
                            <option value="cancelada">Cancelada</option>
                            <option value="erro">Erro</option>
                        </select>
                    </label>
                </div>

                <div x-show="periodo === 'custom'" x-cloak class="mt-4 grid max-w-xl gap-4 border border-blue-100 bg-blue-50/50 p-4 sm:grid-cols-2">
                    <label>
                        <span class="label">Data inicial</span>
                        <input type="date" x-model="filters.data_inicio" @change="load()" class="field">
                    </label>
                    <label>
                        <span class="label">Data final</span>
                        <input type="date" x-model="filters.data_fim" @change="load()" class="field">
                    </label>
                </div>

                <div class="mt-4 flex flex-wrap items-center justify-between gap-3 text-xs text-slate-500">
                    <div class="flex flex-wrap items-center gap-3">
                        <span class="inline-flex items-center gap-2"><span class="h-2 w-2 bg-blue-500"></span><span x-text="periodoDescricao"></span></span>
                        <span x-show="loading" x-cloak class="inline-flex items-center gap-2 text-blue-600"><span class="h-3 w-3 animate-spin rounded-full border-2 border-blue-200 border-t-blue-600"></span> Atualizando...</span>
                    </div>
                    <button type="button" @click="limparFiltros" class="font-semibold text-blue-600 hover:text-blue-800">Limpar filtros</button>
                </div>
            </div>

            <div x-show="selected.length" x-cloak class="flex flex-wrap items-center gap-3 border-b border-blue-100 bg-blue-50 px-5 py-3 text-sm text-blue-800 lg:px-6">
                <span><strong x-text="selected.length"></strong> nota(s) selecionada(s)</span>
                <button type="button" @click="imprimirSelecionadas" class="font-semibold hover:underline">Imprimir selecionadas</button>
                <button type="button" @click="exportarSelecionadas" class="font-semibold hover:underline">Exportar selecionadas</button>
            </div>

            <div x-show="error" x-cloak class="m-5 border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700" x-text="error"></div>

            <div class="overflow-x-auto">
                <table class="w-full min-w-[1180px] divide-y divide-slate-100">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="w-12 px-5 py-3 text-left"><input type="checkbox" :checked="allSelected" @change="toggleAll($event.target.checked)" aria-label="Selecionar todas as notas"></th>
                            <th class="px-5 py-3 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-500">Número</th>
                            <th class="px-5 py-3 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-500">Data de emissão</th>
                            <th class="px-5 py-3 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-500">Destinatário</th>
                            <th class="px-5 py-3 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-500">Documento</th>
                            <th class="px-5 py-3 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-500">Situação</th>
                            <th class="px-5 py-3 text-right text-[11px] font-semibold uppercase tracking-wider text-slate-500">Valor total</th>
                            <th class="w-16 px-5 py-3 text-right text-[11px] font-semibold uppercase tracking-wider text-slate-500">Ações</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <template x-for="nota in notas" :key="nota.id">
                            <tr class="transition hover:bg-slate-50" :class="selected.includes(nota.id) ? 'bg-blue-50/50' : ''">
                                <td class="px-5 py-4"><input type="checkbox" :value="nota.id" :checked="selected.includes(nota.id)" @change="toggleNota(nota.id, $event.target.checked)" :aria-label="'Selecionar nota ' + nota.numero"></td>
                                <td class="whitespace-nowrap px-5 py-4 text-sm font-semibold text-slate-800" x-text="formatNumber(nota.numero, nota.serie)"></td>
                                <td class="whitespace-nowrap px-5 py-4 text-sm text-slate-600" x-text="formatDate(nota.created_at)"></td>
                                <td class="min-w-[280px] px-5 py-4"><p class="text-sm font-medium text-slate-800" x-text="nota.destinatario_nome || 'Não informado'"></p><p x-show="nota.natureza_operacao" class="mt-1 text-xs text-slate-500" x-text="nota.natureza_operacao?.descricao || nota.natureza_operacao?.nome || ''"></p></td>
                                <td class="whitespace-nowrap px-5 py-4 text-sm text-slate-600" x-text="formatDocument(nota.destinatario_documento)"></td>
                                <td class="whitespace-nowrap px-5 py-4"><span class="inline-flex items-center gap-1.5 px-2.5 py-1 text-xs font-semibold ring-1 ring-inset" :class="badgeClass(nota.status)"><span class="h-1.5 w-1.5 rounded-full bg-current"></span><span x-text="statusLabel(nota.status)"></span></span><p x-show="['rejeitada', 'erro'].includes(nota.status) && nota.xmotivo" class="mt-1 max-w-[260px] truncate text-xs text-red-600" :title="nota.xmotivo" x-text="nota.xmotivo"></p></td>
                                <td class="whitespace-nowrap px-5 py-4 text-right text-sm font-semibold tabular-nums text-slate-800" x-text="money(nota.valor_total)"></td>
                                <td class="relative px-5 py-4 text-right">
                                    <button type="button" @click="activeMenu = activeMenu === nota.id ? null : nota.id" class="inline-flex h-8 w-8 items-center justify-center text-lg text-slate-500 hover:bg-slate-100 hover:text-slate-900" :aria-expanded="activeMenu === nota.id" aria-label="Abrir ações da nota">⋮</button>
                                    <div x-show="activeMenu === nota.id" x-cloak @click.outside="activeMenu = null" class="absolute right-5 top-12 z-30 w-60 border border-slate-200 bg-white py-1 text-left shadow-xl">
                                        <button x-show="can('nfe.baixar')" x-cloak type="button" @click="download(nota, 'pdf')" class="action-item"><span>▣</span> Gerar PDF DANFE / imprimir</button>
                                        <button x-show="can('nfe.baixar')" x-cloak type="button" @click="download(nota, 'xml')" class="action-item"><span>&lt;/&gt;</span> Exportar XML</button>
                                        <button x-show="can('nfe.cancelar')" x-cloak type="button" @click="abrirModal('cancelar', nota)" :disabled="!['autorizada', 'simulada'].includes(nota.status)" class="action-item disabled:cursor-not-allowed disabled:opacity-40"><span>×</span> Cancelar NF-e</button>
                                        <button x-show="can('nfe.cce')" x-cloak type="button" @click="abrirModal('cce', nota)" :disabled="nota.status !== 'autorizada'" class="action-item disabled:cursor-not-allowed disabled:opacity-40"><span>✎</span> Carta de correção (CC-e)</button>
                                        <button x-show="can('nfe.clonar')" x-cloak type="button" @click="clonar(nota)" class="action-item"><span>⧉</span> Clonar nota</button>
                                    </div>
                                </td>
                            </tr>
                        </template>
                        <tr x-show="loading && !notas.length" x-cloak><td colspan="8" class="px-5 py-16 text-center text-sm text-slate-500">Carregando notas...</td></tr>
                        <tr x-show="!loading && !notas.length" x-cloak><td colspan="8" class="px-5 py-16 text-center"><div class="mx-auto flex h-12 w-12 items-center justify-center bg-slate-100 text-slate-400"><x-icon name="receipt" class="h-6 w-6" /></div><p class="mt-4 text-sm font-semibold text-slate-700">Nenhuma nota encontrada</p><p class="mt-1 text-sm text-slate-500">Ajuste os filtros ou emita uma nova NF-e.</p></td></tr>
                    </tbody>
                </table>
            </div>

            <div class="flex flex-col gap-3 border-t border-slate-100 px-5 py-4 sm:flex-row sm:items-center sm:justify-between lg:px-6">
                <p class="text-xs text-slate-500">Exibindo <span class="font-semibold text-slate-700" x-text="notas.length"></span> de <span class="font-semibold text-slate-700" x-text="total"></span> nota(s)</p>
                <div class="flex items-center gap-2">
                    <button type="button" @click="load(page - 1)" :disabled="page <= 1 || loading" class="border border-slate-200 px-3 py-2 text-xs font-semibold text-slate-600 disabled:cursor-not-allowed disabled:opacity-40">Anterior</button>
                    <span class="px-2 text-xs text-slate-500">Página <span x-text="page"></span></span>
                    <button type="button" @click="load(page + 1)" :disabled="!hasMore || loading" class="border border-slate-200 px-3 py-2 text-xs font-semibold text-slate-600 disabled:cursor-not-allowed disabled:opacity-40">Próxima</button>
                </div>
            </div>
        </section>

        <div x-show="modal.open" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/50 p-4" @keydown.escape.window="fecharModal">
            <div class="w-full max-w-lg border border-slate-200 bg-white p-6 shadow-2xl" @click.outside="fecharModal">
                <div class="flex items-start justify-between gap-4">
                    <div><p class="text-xs font-semibold uppercase tracking-wide text-blue-600" x-text="modal.type === 'cce' ? 'Carta de correção' : 'Cancelamento de NF-e'"></p><h2 class="mt-1 text-xl font-bold text-slate-950" x-text="modal.nota ? formatNumber(modal.nota.numero, modal.nota.serie) : ''"></h2></div>
                    <button type="button" @click="fecharModal" class="text-xl text-slate-400 hover:text-slate-700" aria-label="Fechar">×</button>
                </div>
                <p class="mt-4 text-sm text-slate-600" x-text="modal.type === 'cce' ? 'Informe a correção permitida pela legislação. A CC-e não pode alterar valores, destinatário ou a data de emissão.' : 'A justificativa deve ter pelo menos 15 caracteres. A transmissão será registrada para auditoria.'"></p>
                <label class="mt-5 block"><span class="label" x-text="modal.type === 'cce' ? 'Texto da correção' : 'Justificativa'"></span><textarea x-model="modal.text" rows="5" class="field" :placeholder="modal.type === 'cce' ? 'Descreva a correção fiscal' : 'Informe o motivo do cancelamento'"></textarea><small x-show="modal.error" x-text="modal.error" class="mt-1 block text-xs text-red-600"></small></label>
                <div class="mt-6 flex justify-end gap-3"><button type="button" @click="fecharModal" class="border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">Voltar</button><button type="button" @click="enviarModal" :disabled="modal.loading" class="inline-flex items-center gap-2 bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white disabled:opacity-60"><span x-show="modal.loading" x-cloak class="h-4 w-4 animate-spin rounded-full border-2 border-blue-200 border-t-white"></span><span x-text="modal.type === 'cce' ? 'Enviar CC-e' : 'Solicitar cancelamento'"></span></button></div>
            </div>
        </div>

        <div
            x-show="isModalEmissaoOpen"
            x-cloak
            @keydown.escape.window="fecharModalEmissao"
            @nfe-emissao-loading.window="emissaoLoading = Boolean($event.detail.loading)"
            @nfe-emitida.window="concluirEmissao($event.detail)"
            class="fixed inset-0 z-[70] flex items-center justify-center bg-slate-950/70 p-3 sm:p-6"
            role="dialog"
            aria-modal="true"
            aria-label="Nova nota fiscal"
        >
            <div class="flex max-h-[92vh] w-full max-w-[1500px] flex-col overflow-hidden border border-slate-200 bg-white shadow-2xl">
                <header class="flex min-h-16 shrink-0 items-center justify-between gap-4 border-b border-slate-200 bg-white px-4 py-3 sm:px-6">
                    <div class="min-w-0">
                        <p class="truncate text-base font-bold text-slate-950 sm:text-lg">Nova nota fiscal</p>
                        <p class="hidden text-xs text-slate-500 sm:block">Preencha, revise e emita sem sair do histórico.</p>
                    </div>
                    <div class="flex shrink-0 items-center gap-2">
                        <span x-show="emissaoLoading" x-cloak class="hidden items-center gap-2 text-xs font-medium text-blue-700 sm:inline-flex"><span class="h-3.5 w-3.5 animate-spin rounded-full border-2 border-blue-200 border-t-blue-600"></span>Processando emissão</span>
                        <button type="button" @click="fecharModalEmissao" :disabled="emissaoLoading" class="border border-slate-300 px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-50">Cancelar</button>
                        <button type="button" @click="fecharModalEmissao" :disabled="emissaoLoading" class="flex h-10 w-10 items-center justify-center text-xl text-slate-500 hover:bg-slate-100 disabled:cursor-not-allowed disabled:opacity-50" aria-label="Fechar emissão">×</button>
                    </div>
                </header>
                <div class="min-h-0 flex-1 overflow-y-auto bg-slate-50 p-3 sm:p-5 lg:p-6">
                    @include('nfe.partials.emissao-form', ['modalMode' => true])
                </div>
            </div>
        </div>
    </div>

    <script>
        if (!window.fiscalNotasPageRegistered) {
            window.fiscalNotasPageRegistered = true;
            window.registerFiscalAlpine((Alpine) => {
                Alpine.data('notasPage', () => ({
                notas: [],
                page: 1,
                total: 0,
                hasMore: false,
                loading: false,
                pendingPage: null,
                loadRequestId: 0,
                error: '',
                activeMenu: null,
                selected: [],
                isModalEmissaoOpen: false,
                emissaoLoading: false,
                periodo: '30d',
                periodoDescricao: 'Últimos 30 dias',
                filters: { busca: '', data_inicio: '', data_fim: '', status: '' },
                modal: { open: false, type: '', nota: null, text: '', error: '', loading: false },
                initialized: false,

                get allSelected() {
                    return this.notas.length > 0 && this.notas.every((nota) => this.selected.includes(nota.id));
                },

                init() {
                    if (this.initialized) {
                        return;
                    }

                    this.initialized = true;
                    this.setPeriodo(this.periodo, false);
                    this.load();
                },

                destroy() {
                    document.body.classList.remove('overflow-hidden');
                },

                abrirModalEmissao() {
                    if (this.isModalEmissaoOpen) {
                        return;
                    }

                    this.isModalEmissaoOpen = true;
                    document.body.classList.add('overflow-hidden');
                    this.$nextTick(() => window.dispatchEvent(new CustomEvent('abrir-formulario-nfe')));
                },

                fecharModalEmissao() {
                    if (this.emissaoLoading) {
                        window.fiscalToast?.('warning', 'Aguarde a conclusão da emissão antes de fechar.', 'Emissão em andamento');
                        return;
                    }

                    this.isModalEmissaoOpen = false;
                    document.body.classList.remove('overflow-hidden');
                    window.dispatchEvent(new CustomEvent('resetar-formulario-nfe'));
                },

                async concluirEmissao() {
                    this.emissaoLoading = false;
                    this.isModalEmissaoOpen = false;
                    document.body.classList.remove('overflow-hidden');
                    window.dispatchEvent(new CustomEvent('resetar-formulario-nfe'));
                    await this.load(1, true);
                },

                headers() {
                    return { Authorization: 'Bearer ' + localStorage.getItem('nfe_token'), Accept: 'application/json' };
                },

                dataIso(date) {
                    const offset = date.getTimezoneOffset();
                    return new Date(date.getTime() - (offset * 60000)).toISOString().slice(0, 10);
                },

                setPeriodo(periodo, carregar = true) {
                    this.periodo = periodo;
                    const hoje = new Date();
                    const fim = this.dataIso(hoje);

                    if (periodo === 'all') {
                        this.filters.data_inicio = '';
                        this.filters.data_fim = '';
                        this.periodoDescricao = 'Todos os períodos';
                    } else if (periodo === '30d') {
                        const inicio = new Date(hoje);
                        inicio.setDate(inicio.getDate() - 29);
                        this.filters.data_inicio = this.dataIso(inicio);
                        this.filters.data_fim = fim;
                        this.periodoDescricao = 'Últimos 30 dias';
                    } else if (periodo === '3m') {
                        const inicio = new Date(hoje);
                        inicio.setMonth(inicio.getMonth() - 3);
                        this.filters.data_inicio = this.dataIso(inicio);
                        this.filters.data_fim = fim;
                        this.periodoDescricao = 'Últimos 3 meses';
                    } else if (periodo === '6m') {
                        const inicio = new Date(hoje);
                        inicio.setMonth(inicio.getMonth() - 6);
                        this.filters.data_inicio = this.dataIso(inicio);
                        this.filters.data_fim = fim;
                        this.periodoDescricao = 'Últimos 6 meses';
                    } else if (periodo === 'year') {
                        this.filters.data_inicio = this.dataIso(new Date(hoje.getFullYear(), 0, 1));
                        this.filters.data_fim = fim;
                        this.periodoDescricao = 'Este ano';
                    } else if (periodo === 'previous-year') {
                        this.filters.data_inicio = this.dataIso(new Date(hoje.getFullYear() - 1, 0, 1));
                        this.filters.data_fim = this.dataIso(new Date(hoje.getFullYear() - 1, 11, 31));
                        this.periodoDescricao = 'Ano anterior';
                    } else {
                        this.periodoDescricao = 'Período personalizado';
                    }

                    if (carregar && periodo !== 'custom') this.load();
                },

                limparFiltros() {
                    this.filters.busca = '';
                    this.filters.status = '';
                    this.setPeriodo('30d');
                },

                async load(nextPage = 1, force = false) {
                    if (this.loading && !force) {
                        this.pendingPage = nextPage;
                        return;
                    }

                    const requestId = ++this.loadRequestId;
                    this.loading = true;
                    this.error = '';
                    this.page = nextPage;
                    const params = new URLSearchParams({ page: String(this.page), per_page: '20', ...this.filters });
                    Object.entries(this.filters).forEach(([key, value]) => { if (!value) params.delete(key); });

                    try {
                        const response = await fiscalFetch('/api/faturamento/notas?' + params.toString(), { headers: this.headers() });
                        const data = await response.json().catch(() => ({}));
                        if (!response.ok) throw new Error(data.message || 'Não foi possível carregar as notas.');
                        if (requestId === this.loadRequestId) {
                            this.notas = data.data || [];
                            this.total = data.total || 0;
                            this.hasMore = Boolean(data.next_page_url);
                            this.selected = this.selected.filter((id) => this.notas.some((nota) => nota.id === id));
                        }
                    } catch (error) {
                        if (requestId === this.loadRequestId) {
                            this.error = error.message;
                            window.fiscalToast?.('error', error.message, 'Histórico indisponível');
                        }
                    } finally {
                        if (requestId === this.loadRequestId) {
                            this.loading = false;

                            if (this.pendingPage !== null) {
                                const pendingPage = this.pendingPage;
                                this.pendingPage = null;
                                this.load(pendingPage);
                            }
                        }
                    }
                },

                toggleAll(checked) {
                    this.selected = checked ? this.notas.map((nota) => nota.id) : [];
                },

                toggleNota(id, checked) {
                    this.selected = checked ? [...new Set([...this.selected, id])] : this.selected.filter((selectedId) => selectedId !== id);
                },

                statusLabel(status) {
                    return ({ autorizada: 'Emitida DANFE', simulada: 'Simulada', rejeitada: 'Rejeitada', aguardando_retorno: 'Processando', cancelada: 'Cancelada', erro: 'Erro', gerando: 'Gerando', assinado: 'Assinada' }[status] || status || 'Pendente');
                },

                badgeClass(status) {
                    if (status === 'autorizada') return 'bg-blue-50 text-blue-700 ring-blue-600/20';
                    if (status === 'cancelada') return 'bg-amber-50 text-amber-700 ring-amber-600/20';
                    if (['rejeitada', 'erro'].includes(status)) return 'bg-red-50 text-red-700 ring-red-600/20';
                    return 'bg-amber-50 text-amber-700 ring-amber-600/20';
                },

                formatNumber(numero, serie) {
                    return String(numero || 0).padStart(6, '0') + ' · Série ' + (serie || 1);
                },

                formatDocument(documento) {
                    const digits = String(documento || '').replace(/\D/g, '');
                    if (digits.length === 14) return digits.replace(/^(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})$/, '$1.$2.$3/$4-$5');
                    if (digits.length === 11) return digits.replace(/^(\d{3})(\d{3})(\d{3})(\d{2})$/, '$1.$2.$3-$4');
                    return documento || '—';
                },

                formatDate(value) {
                    return value ? new Intl.DateTimeFormat('pt-BR', { dateStyle: 'short', timeStyle: 'short' }).format(new Date(value)) : '—';
                },

                money(value) {
                    return new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(Number(value) || 0);
                },

                async download(nota, type) {
                    this.activeMenu = null;
                    try {
                        const response = await fiscalFetch('/api/faturamento/notas/' + nota.id + '/download?tipo=' + type, { headers: this.headers() });
                        if (!response.ok) {
                            const data = await response.json().catch(() => ({}));
                            throw new Error(data.message || 'O arquivo ainda não está disponível.');
                        }
                        const blob = await response.blob();
                        const url = URL.createObjectURL(blob);
                        const link = document.createElement('a');
                        link.href = url;
                        link.download = (type === 'pdf' ? 'danfe-' : 'nfe-') + nota.id + '.' + type;
                        link.click();
                        URL.revokeObjectURL(url);
                    } catch (error) {
                        window.fiscalToast?.('warning', error.message, 'Download indisponível');
                    }
                },

                imprimir() {
                    window.print();
                },

                imprimirSelecionadas() {
                    if (!this.selected.length) return;
                    this.imprimir();
                },

                exportarSelecionadas() {
                    const registros = this.notas.filter((nota) => this.selected.includes(nota.id));
                    this.baixarCsv(registros);
                },

                exportar() {
                    this.baixarCsv(this.notas);
                },

                baixarCsv(registros) {
                    if (!registros.length) {
                        window.fiscalToast?.('warning', 'Não há notas para exportar com os filtros atuais.', 'Exportação');
                        return;
                    }
                    const linhas = [['Número', 'Série', 'Emissão', 'Destinatário', 'Documento', 'Situação', 'Valor total'], ...registros.map((nota) => [nota.numero, nota.serie, this.formatDate(nota.created_at), nota.destinatario_nome || '', nota.destinatario_documento || '', this.statusLabel(nota.status), Number(nota.valor_total || 0).toFixed(2).replace('.', ',')])];
                    const csv = '\ufeff' + linhas.map((linha) => linha.map((valor) => '"' + String(valor).replace(/"/g, '""') + '"').join(';')).join('\n');
                    const url = URL.createObjectURL(new Blob([csv], { type: 'text/csv;charset=utf-8;' }));
                    const link = document.createElement('a');
                    link.href = url;
                    link.download = 'notas-fiscais-' + this.dataIso(new Date()) + '.csv';
                    link.click();
                    URL.revokeObjectURL(url);
                },

                abrirModal(type, nota) {
                    this.activeMenu = null;
                    this.modal = { open: true, type, nota, text: '', error: '', loading: false };
                },

                fecharModal() {
                    if (this.modal.loading) return;
                    this.modal = { open: false, type: '', nota: null, text: '', error: '', loading: false };
                },

                async enviarModal() {
                    if (this.modal.text.trim().length < 15) {
                        this.modal.error = 'Informe pelo menos 15 caracteres.';
                        return;
                    }
                    this.modal.loading = true;
                    this.modal.error = '';
                    const endpoint = this.modal.type === 'cce' ? 'cce' : 'cancelar';
                    try {
                        const response = await fiscalFetch('/api/faturamento/notas/' + this.modal.nota.id + '/' + endpoint, { method: 'POST', headers: { ...this.headers(), 'Content-Type': 'application/json' }, body: JSON.stringify({ justificativa: this.modal.text }) });
                        const data = await response.json().catch(() => ({}));
                        if (!response.ok) throw new Error(data.message || 'Não foi possível enviar a solicitação.');
                        window.fiscalToast?.('success', data.message || 'Solicitação enviada com sucesso.', 'Operação fiscal');
                        this.fecharModal();
                        this.load(this.page);
                    } catch (error) {
                        this.modal.error = error.message;
                        window.fiscalToast?.('error', error.message, 'Operação não concluída');
                    } finally {
                        this.modal.loading = false;
                    }
                },

                async clonar(nota) {
                    this.activeMenu = null;
                    try {
                        const response = await fiscalFetch('/api/faturamento/notas/' + nota.id + '/clonar', { method: 'POST', headers: this.headers() });
                        const data = await response.json().catch(() => ({}));
                        if (!response.ok) throw new Error(data.message || 'Não foi possível clonar a nota.');
                        sessionStorage.setItem('fiscalflow_clone_draft', JSON.stringify(data.payload || {}));
                        window.fiscalToast?.('success', 'Rascunho criado. Abrindo a nova emissão.', 'Nota clonada');
                        this.abrirModalEmissao();
                    } catch (error) {
                        window.fiscalToast?.('error', error.message, 'Clonagem indisponível');
                    }
                },
                }));
            });
        }
    </script>
    <style>
        .action-item { display: flex; width: 100%; align-items: center; gap: .65rem; padding: .65rem .8rem; font-size: .78rem; color: #334155; }
        .action-item:hover { background: #f8fafc; color: #1d4ed8; }
        .action-item span { width: 1rem; text-align: center; color: #64748b; }
        @media print { aside, header, nav, button, .action-item { display: none !important; } main { padding: 0 !important; } }
    </style>
</x-app-layout>
