@props(['title' => 'FiscalFlow', 'header' => null, 'partial' => false])

@if ($partial)
    {{ $slot }}
@else
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'FiscalFlow' }} · FiscalFlow</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        brand: { 50: '#eff6ff', 600: '#2563eb', 700: '#1d4ed8' },
                    },
                },
            },
        };

        window.registerFiscalAlpine = function (register) {
            if (window.Alpine) {
                register(window.Alpine);
                return;
            }

            document.addEventListener('alpine:init', () => register(window.Alpine), { once: true });
        };

        window.fiscalFetch = async function (input, init = {}) {
            const response = await window.fetch(input, init);
            const requestUrl = new URL(typeof input === 'string' ? input : input.url, window.location.href);

            if (response.status !== 401 || requestUrl.origin !== window.location.origin || !requestUrl.pathname.startsWith('/api/')) {
                return response;
            }

            const message = 'Sua sessão expirou. Entre novamente para continuar.';
            localStorage.removeItem('nfe_token');
            localStorage.removeItem('nfe_user');
            sessionStorage.setItem('fiscalflow_auth_message', message);

            if (!window.fiscalAuthRedirecting) {
                window.fiscalAuthRedirecting = true;
                window.setTimeout(() => window.location.replace('{{ route('login') }}'), 80);
            }

            const error = new Error(message);
            error.status = 401;
            error.authExpired = true;
            throw error;
        };

        document.addEventListener('alpine:init', () => {
            Alpine.data('tabManager', () => ({
                sidebarOpen: false,
                tabs: [],
                activeTab: null,
                routes: {
                    '/dashboard': { id: 'dashboard', titulo: 'Dashboard' },
                    '/dashboard/nfe/nova': { id: 'nfe-create', titulo: 'Nova NF-e' },
                    '/dashboard/notas': { id: 'nfe-index', titulo: 'Histórico de notas' },
                    '/dashboard/configuracoes': { id: 'configuracoes', titulo: 'Configurações' },
                },
                init() {
                    this.$nextTick(() => this.bootstrap());
                    this.$el.addEventListener('click', (event) => this.interceptNavigation(event));
                },
                get activeTabData() {
                    return this.tabs.find((tab) => tab.id === this.activeTab) || null;
                },
                routeFor(url) {
                    const target = new URL(url, window.location.href);
                    const route = this.routes[target.pathname];

                    return route ? { ...route, url: target.href } : null;
                },
                bootstrap() {
                    const current = this.routeFor(window.location.href) || {
                        ...this.routes['/dashboard'],
                        url: new URL('/dashboard', window.location.origin).href,
                    };
                    const workspace = this.$refs.workspace;
                    const initialPane = workspace?.querySelector('[data-tab-pane]');

                    if (!workspace || !initialPane) {
                        return;
                    }

                    initialPane.dataset.tabId = current.id;
                    this.tabs = [{
                        ...current,
                        conteudoHtml: null,
                        loaded: true,
                        loading: false,
                        error: '',
                        pane: initialPane,
                    }];
                    this.activeTab = current.id;
                    this.syncPanes();
                },
                interceptNavigation(event) {
                    if (event.defaultPrevented || event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) {
                        return;
                    }

                    const link = event.target.closest('a[href]');
                    if (!link || link.target === '_blank' || link.hasAttribute('download')) {
                        return;
                    }

                    const route = this.routeFor(link.href);
                    if (!route) {
                        return;
                    }

                    event.preventDefault();
                    this.sidebarOpen = false;
                    this.openTab(route);
                },
                async openTab(route) {
                    const existing = this.tabs.find((tab) => tab.id === route.id);

                    if (existing) {
                        this.activateTab(existing.id);
                        return;
                    }

                    const tab = {
                        ...route,
                        conteudoHtml: '',
                        loaded: false,
                        loading: true,
                        error: '',
                        pane: null,
                    };

                    this.tabs.push(tab);
                    this.activeTab = tab.id;
                    this.syncPanes();

                    try {
                        const response = await fetch(tab.url, {
                            headers: {
                                Accept: 'text/html',
                                'X-Requested-With': 'XMLHttpRequest',
                            },
                        });

                        if (!response.ok) {
                            throw new Error('Não foi possível carregar esta tela. Atualize a página e tente novamente.');
                        }

                        tab.conteudoHtml = await response.text();
                        tab.loaded = true;
                        this.mountTab(tab);
                    } catch (error) {
                        tab.error = error.message || 'Não foi possível carregar esta tela.';
                        tab.loaded = true;
                        this.mountTab(tab);
                        window.fiscalToast?.('error', tab.error, 'Aba indisponível');
                    } finally {
                        tab.loading = false;
                    }
                },
                mountTab(tab) {
                    if (tab.pane) {
                        this.syncPanes();
                        return;
                    }

                    const pane = document.createElement('div');
                    pane.dataset.tabPane = '';
                    pane.dataset.tabId = tab.id;
                    pane.className = 'workspace-tab-pane';

                    if (tab.error) {
                        const errorBox = document.createElement('div');
                        errorBox.className = 'rounded-xl border border-red-200 bg-red-50 p-5 text-sm text-red-700';
                        errorBox.textContent = tab.error;
                        pane.appendChild(errorBox);
                    } else {
                        pane.innerHTML = tab.conteudoHtml;
                        this.executeScripts(pane);
                        this.normalizeMasks(pane);
                    }

                    this.$refs.workspace.appendChild(pane);
                    tab.pane = pane;

                    if (!tab.error && window.Alpine) {
                        window.Alpine.initTree(pane);
                    }

                    this.syncPanes();
                },
                executeScripts(container) {
                    container.querySelectorAll('script').forEach((oldScript) => {
                        const script = document.createElement('script');

                        Array.from(oldScript.attributes).forEach((attribute) => {
                            script.setAttribute(attribute.name, attribute.value);
                        });

                        script.textContent = oldScript.textContent;
                        oldScript.replaceWith(script);
                    });
                },
                normalizeMasks(container) {
                    container.querySelectorAll('[x-mask]').forEach((input) => {
                        const mask = input.getAttribute('x-mask');

                        if (mask && mask.startsWith("'") && mask.endsWith("'")) {
                            input.setAttribute('x-mask', mask.slice(1, -1));
                        }
                    });
                },
                activateTab(id) {
                    if (!this.tabs.some((tab) => tab.id === id)) {
                        return;
                    }

                    this.activeTab = id;
                    this.syncPanes();
                },
                closeTab(id) {
                    const index = this.tabs.findIndex((tab) => tab.id === id);

                    if (index < 0) {
                        return;
                    }

                    const wasActive = this.activeTab === id;
                    const previous = this.tabs[index - 1];
                    const next = this.tabs[index + 1];
                    const tab = this.tabs[index];

                    tab.pane?.remove();
                    this.tabs.splice(index, 1);

                    if (!wasActive) {
                        return;
                    }

                    const fallback = previous || next;

                    if (fallback) {
                        this.activeTab = fallback.id;
                        this.syncPanes();
                        return;
                    }

                    this.openTab({
                        ...this.routes['/dashboard'],
                        url: new URL('/dashboard', window.location.origin).href,
                    });
                },
                syncPanes() {
                    this.$refs.workspace?.querySelectorAll('[data-tab-pane]').forEach((pane) => {
                        pane.hidden = pane.dataset.tabId !== this.activeTab;
                    });
                },
            }));
        });
    </script>
    <script defer src="https://cdn.jsdelivr.net/npm/@alpinejs/mask@3.x.x/dist/cdn.min.js"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>
        [x-cloak] { display: none !important; }
        .sidebar-scroll::-webkit-scrollbar { width: 4px; }
        .sidebar-scroll::-webkit-scrollbar-thumb { background: #334155; border-radius: 999px; }
        .field { margin-top: .5rem; display: block; width: 100%; border: 1px solid #cbd5e1; border-radius: 0; background: #fff; padding: .75rem; font-size: .875rem; box-shadow: none; }
        .field:focus { border-color: #2563eb; box-shadow: 0 0 0 3px rgb(37 99 235 / .12); outline: none; }
        .field[type=file] { padding: .45rem; }
    </style>
</head>
<body class="bg-slate-50 text-slate-900 antialiased" x-data="tabManager">
    <div class="min-h-screen">
        <div x-cloak x-show="sidebarOpen" x-transition.opacity class="fixed inset-0 z-30 bg-slate-950/50 lg:hidden" @click="sidebarOpen = false"></div>

        <aside class="fixed inset-y-0 left-0 z-40 flex w-72 -translate-x-full flex-col bg-slate-950 text-slate-300 transition-transform duration-200 lg:translate-x-0" :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'">
            <div class="flex h-20 items-center gap-3 border-b border-white/10 px-6">
                <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-blue-600 text-lg font-bold text-white">F</div>
                <div><p class="font-semibold tracking-tight text-white">FiscalFlow</p><p class="text-xs text-slate-500">Gestão fiscal inteligente</p></div>
            </div>
            <div class="sidebar-scroll flex-1 overflow-y-auto px-4 py-6">
                <p class="mb-3 px-3 text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-500">Workspace</p>
                <nav class="space-y-1">
                    <x-nav-link href="{{ route('dashboard') }}" :active="request()->routeIs('dashboard*')" icon="grid">Dashboard</x-nav-link>
                    <x-nav-link href="{{ route('nfe.create') }}" :active="request()->routeIs('nfe.create')" icon="plus">Emitir NF-e</x-nav-link>
                    <x-nav-link href="{{ route('nfe.index') }}" :active="request()->routeIs('nfe.index')" icon="receipt">Histórico de Notas</x-nav-link>
                    <x-nav-link href="{{ route('configuracoes') }}" :active="request()->routeIs('configuracoes')" icon="settings">Configurações</x-nav-link>
                </nav>
                <div class="mt-8 rounded-2xl border border-white/10 bg-white/5 p-4">
                    <div class="flex items-start gap-3"><span class="mt-0.5 h-2.5 w-2.5 rounded-full bg-emerald-400 shadow-[0_0_0_4px_rgba(52,211,153,0.12)]"></span><div><p class="text-sm font-medium text-white">Ambiente seguro</p><p class="mt-1 text-xs leading-5 text-slate-400">Homologação SEFAZ ativa</p></div></div>
                </div>
            </div>
            <div class="border-t border-white/10 p-4"><div class="flex items-center gap-3 rounded-xl px-2 py-2"><div id="sidebar-initials" class="flex h-9 w-9 items-center justify-center rounded-full bg-blue-500/20 text-sm font-semibold text-blue-300">FF</div><div class="min-w-0"><p id="sidebar-user-name" class="truncate text-sm font-medium text-white">Usuário</p><p id="sidebar-user-role" class="truncate text-xs text-slate-500">Autenticado</p></div><button type="button" onclick="logoutFiscalFlow()" class="ml-auto text-slate-500 hover:text-white" title="Sair">↪</button></div></div>
        </aside>

        <div class="lg:pl-72">
            <header class="sticky top-0 z-20 flex h-20 items-center justify-between border-b border-slate-200 bg-white/90 px-4 backdrop-blur sm:px-8">
                <div class="flex items-center gap-3"><button class="rounded-lg p-2 text-slate-500 hover:bg-slate-100 lg:hidden" @click="sidebarOpen = true" aria-label="Abrir menu"><x-icon name="menu" class="h-6 w-6" /></button><p class="font-semibold text-slate-900" x-text="activeTabData?.titulo || 'FiscalFlow'"></p></div>
                <div class="flex items-center gap-3"><span class="hidden items-center gap-2 rounded-full bg-emerald-50 px-3 py-1.5 text-xs font-medium text-emerald-700 sm:flex"><span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span> Sistema operacional</span><button type="button" onclick="logoutFiscalFlow()" class="flex items-center gap-2 rounded-lg border border-slate-200 px-3 py-2 text-sm font-medium text-slate-600 hover:bg-slate-50"><x-icon name="logout" class="h-4 w-4" /> <span class="hidden sm:inline">Sair</span></button></div>
            </header>

            <div x-cloak x-show="tabs.length" class="border-b border-slate-200 bg-white px-4 sm:px-8">
                <div class="flex min-w-0 items-end gap-1 overflow-x-auto pt-2" role="tablist" aria-label="Abas abertas">
                    <template x-for="tab in tabs" :key="tab.id">
                        <div class="group flex min-w-0 max-w-xs items-center border-b-2 px-3 py-2.5 text-sm" :class="activeTab === tab.id ? 'border-blue-600 text-blue-700' : 'border-transparent text-slate-500 hover:border-slate-300 hover:text-slate-700'" role="tab" :aria-selected="activeTab === tab.id">
                            <button type="button" class="min-w-0 flex-1 truncate text-left font-medium" @click="activateTab(tab.id)" x-text="tab.titulo"></button>
                            <span x-show="tab.loading" class="ml-2 h-3.5 w-3.5 animate-spin rounded-full border-2 border-slate-300 border-t-blue-600" aria-label="Carregando"></span>
                            <button type="button" @click="closeTab(tab.id)" class="ml-2 rounded p-0.5 text-slate-400 opacity-0 transition hover:bg-slate-100 hover:text-slate-700 group-hover:opacity-100" :class="activeTab === tab.id ? 'opacity-100' : ''" :aria-label="'Fechar aba ' + tab.titulo">×</button>
                        </div>
                    </template>
                </div>
            </div>

            <main class="mx-auto max-w-[1600px] p-4 sm:p-8">
                <div x-ref="workspace" class="workspace-container">
                    <div data-tab-pane>{{ $slot }}</div>
                </div>
            </main>
        </div>
    </div>
    <x-toast />
</body>
<script>
    (function () {
        const token = localStorage.getItem('nfe_token');

        if (!token) {
            window.location.replace('{{ route('login') }}');
            return;
        }

        const user = JSON.parse(localStorage.getItem('nfe_user') || '{}');
        const initials = (user.name || 'Usuário').split(' ').map((part) => part[0]).slice(0, 2).join('').toUpperCase();
        document.getElementById('sidebar-initials').textContent = initials;
        document.getElementById('sidebar-user-name').textContent = user.name || 'Usuário';
        document.getElementById('sidebar-user-role').textContent = user.perfil || 'Autenticado';
    }());

    function logoutFiscalFlow() {
        localStorage.removeItem('nfe_token');
        localStorage.removeItem('nfe_user');
        window.location.href = '{{ route('login') }}';
    }
</script>
</html>
@endif
