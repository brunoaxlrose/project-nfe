<x-app-layout title="Dashboard" header="Visão geral" :partial="$partial ?? false">
    <div class="mb-8 flex flex-col justify-between gap-4 sm:flex-row sm:items-end"><div><p class="mb-2 text-sm font-medium text-blue-600">{{ now()->format('d/m/Y') }}</p><h1 class="text-2xl font-bold tracking-tight text-slate-950 sm:text-3xl">Visão geral</h1><p class="mt-2 text-sm text-slate-500">Acompanhe a operação fiscal da sua empresa.</p></div><x-button href="{{ route('nfe.create') }}" tag="a"><x-icon name="plus" class="h-4 w-4" /> Nova NF-e</x-button></div>
    <div x-data="dashboardPage" class="space-y-6">
        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"><div class="flex items-start justify-between"><p class="text-sm font-medium text-slate-500">Notas emitidas</p><span class="h-2.5 w-2.5 rounded-full bg-blue-500"></span></div><p class="mt-5 text-3xl font-bold tracking-tight text-slate-950" x-text="stats.notas_emitidas">0</p><p class="mt-2 text-xs text-blue-600" x-text="stats.notas_emitidas ? 'Notas registradas no histórico' : 'Sem emissões ainda'"></p></div>
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"><div class="flex items-start justify-between"><p class="text-sm font-medium text-slate-500">Autorizadas</p><span class="h-2.5 w-2.5 rounded-full bg-emerald-500"></span></div><p class="mt-5 text-3xl font-bold tracking-tight text-slate-950" x-text="stats.autorizadas">0</p><p class="mt-2 text-xs text-emerald-600" x-text="stats.autorizadas ? 'Autorizadas pela SEFAZ' : 'Nenhuma nota autorizada'"></p></div>
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"><div class="flex items-start justify-between"><p class="text-sm font-medium text-slate-500">Processando</p><span class="h-2.5 w-2.5 rounded-full bg-amber-500"></span></div><p class="mt-5 text-3xl font-bold tracking-tight text-slate-950" x-text="stats.processando">0</p><p class="mt-2 text-xs text-amber-600" x-text="stats.processando ? 'Aguardando retorno da SEFAZ' : 'Nenhuma nota em processamento'"></p></div>
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"><div class="flex items-start justify-between"><p class="text-sm font-medium text-slate-500">Rejeitadas</p><span class="h-2.5 w-2.5 rounded-full bg-red-500"></span></div><p class="mt-5 text-3xl font-bold tracking-tight text-slate-950" x-text="stats.rejeitadas">0</p><p class="mt-2 text-xs text-red-600" x-text="stats.rejeitadas ? 'Rejeitadas ou com erro' : 'Nenhuma nota rejeitada'"></p></div>
        </div>
    <div class="mt-6 grid gap-6 xl:grid-cols-[1.35fr_1fr]"><div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm"><div class="flex items-center justify-between"><div><h2 class="font-semibold text-slate-900">Volume de emissões</h2><p class="mt-1 text-sm text-slate-500">Notas registradas nos últimos 7 dias</p></div><span class="rounded-lg bg-slate-100 px-3 py-2 text-xs font-medium text-slate-600" x-text="volumeTotal ? volumeTotal + ' nota(s)' : 'Sem dados'"></span></div><div x-show="volumeTotal" class="mt-6 flex h-48 items-end justify-between gap-2"><template x-for="dia in volume" :key="dia.data"><div class="flex h-full flex-1 flex-col items-center justify-end gap-2" :title="dia.quantidade + ' nota(s) · ' + money(dia.valor)"><div class="flex h-full w-full items-end justify-center"><div class="w-full max-w-10 rounded-t-lg bg-blue-500 transition-all" :style="'height:' + (dia.quantidade ? Math.max(12, (dia.quantidade / volumeMax) * 100) : 3) + '%'" :class="dia.quantidade ? 'opacity-90' : 'opacity-20'"></div></div><span class="text-[11px] text-slate-500" x-text="dia.label"></span><span class="text-xs font-semibold text-slate-700" x-text="dia.quantidade"></span></div></template></div><div x-show="!volumeTotal" class="flex h-48 items-center justify-center text-sm text-slate-400">As emissões aparecerão aqui após a primeira NF-e.</div></div><div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm"><h2 class="font-semibold text-slate-900">Ações rápidas</h2><div class="mt-5 space-y-3"><a href="{{ route('nfe.create') }}" class="flex items-center gap-4 rounded-xl border border-slate-200 p-4 transition hover:border-blue-200 hover:bg-blue-50/50"><span class="flex h-10 w-10 items-center justify-center rounded-xl bg-blue-50 text-blue-600"><x-icon name="plus" class="h-5 w-5" /></span><span><span class="block text-sm font-semibold text-slate-800">Emitir nova NF-e</span><span class="mt-1 block text-xs text-slate-500">Preencha os dados da operação</span></span><span class="ml-auto text-slate-400">→</span></a><a href="{{ route('nfe.index') }}" class="flex items-center gap-4 rounded-xl border border-slate-200 p-4 transition hover:border-blue-200 hover:bg-blue-50/50"><span class="flex h-10 w-10 items-center justify-center rounded-xl bg-slate-100 text-slate-600"><x-icon name="receipt" class="h-5 w-5" /></span><span><span class="block text-sm font-semibold text-slate-800">Consultar histórico</span><span class="mt-1 block text-xs text-slate-500">Veja todas as notas emitidas</span></span><span class="ml-auto text-slate-400">→</span></a></div></div></div>
    </div>
    <script>
        if (!window.fiscalDashboardRegistered) {
            window.fiscalDashboardRegistered = true;
            window.registerFiscalAlpine((Alpine) => {
                Alpine.data('dashboardPage', () => ({
                    stats: { notas_emitidas: 0, autorizadas: 0, processando: 0, rejeitadas: 0 },
                    volume: [],
                    get volumeTotal() { return this.volume.reduce((total, dia) => total + Number(dia.quantidade || 0), 0); },
                    get volumeMax() { return Math.max(1, ...this.volume.map((dia) => Number(dia.quantidade || 0))); },
                    money(value) { return new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(Number(value) || 0); },
                    init() { this.load(); },
                    async load() {
                        try {
                            const response = await window.fiscalFetch('/api/faturamento/resumo', {
                                headers: { Accept: 'application/json', Authorization: 'Bearer ' + localStorage.getItem('nfe_token') },
                            });
                            if (!response.ok) throw new Error('Falha ao carregar o resumo.');
                            const data = await response.json();
                            this.stats = { ...this.stats, ...data };
                            this.volume = data.volume_7_dias || [];
                        } catch (error) {
                            if (!error.authExpired) window.fiscalToast?.('warning', 'Não foi possível atualizar o resumo das notas agora.');
                        }
                    },
                }));
            });
        }
    </script>
</x-app-layout>
