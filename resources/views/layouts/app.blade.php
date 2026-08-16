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
            localStorage.removeItem('fiscalflow_tabs');
            localStorage.removeItem('fiscalflow_active_tab');
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
                sidebarCollapsed: false,
                profileOpen: false,
                initialized: false,
                tabs: [],
                activeTab: null,
                permissions: [],
                permissionsReady: false,
                frontendVersion: '2026-08-16-fornecedores-v3',
                routes: {
                    '/dashboard': { id: 'dashboard', titulo: 'Início', permission: 'menu.dashboard' },
                    '/dashboard/nfe/nova': { id: 'nfe-create', titulo: 'Nova NF-e', permission: 'nfe.criar' },
                    '/dashboard/notas': { id: 'nfe-index', titulo: 'Notas Fiscais (NF-e)', permission: 'nfe.visualizar' },
                    '/dashboard/configuracoes': { id: 'configuracoes', titulo: 'Configurações', permission: 'menu.configuracoes' },
                    '/dashboard/usuarios': { id: 'usuarios', titulo: 'Usuários e permissões', permission: 'menu.usuarios' },
                    '/dashboard/fornecedores': { id: 'fornecedores', titulo: 'Fornecedores', permission: 'fornecedores.visualizar' },
                },
                init() {
                    if (this.initialized) {
                        return;
                    }

                    this.initialized = true;
                    this.invalidateOldWorkspaceCache();
                    const storedUser = JSON.parse(localStorage.getItem('nfe_user') || '{}');
                    this.permissionsReady = Array.isArray(storedUser.permissions);
                    this.permissions = this.permissionsReady ? storedUser.permissions : [];
                    this.sidebarCollapsed = localStorage.getItem('fiscalflow_sidebar_collapsed') === '1';
                    this.$nextTick(() => this.bootstrap());
                    this.refreshAccess();
                    this.$el.addEventListener('click', (event) => this.interceptNavigation(event));
                    window.addEventListener('popstate', () => {
                        const route = this.routeFor(window.location.href);

                        if (route) {
                            this.openTab(route);
                        }
                    });
                },
                get activeTabData() {
                    return this.tabs.find((tab) => tab.id === this.activeTab) || null;
                },
                invalidateOldWorkspaceCache() {
                    const currentVersion = localStorage.getItem('fiscalflow_frontend_version');

                    if (currentVersion === this.frontendVersion) {
                        return;
                    }

                    localStorage.setItem('fiscalflow_frontend_version', this.frontendVersion);
                    localStorage.removeItem('fiscalflow_tabs');
                    localStorage.removeItem('fiscalflow_active_tab');
                },
                can(permission) {
                    return !permission || !this.permissionsReady || this.permissions.includes(permission);
                },
                firstAllowedRoute() {
                    const route = Object.values(this.routes).find((item) => this.can(item.permission));

                    return route
                        ? { ...route, url: new URL(Object.keys(this.routes).find((path) => this.routes[path].id === route.id), window.location.origin).href }
                        : null;
                },
                async refreshAccess() {
                    try {
                        const response = await window.fiscalFetch('/api/auth/me', {
                            headers: {
                                Accept: 'application/json',
                                Authorization: 'Bearer ' + localStorage.getItem('nfe_token'),
                            },
                        });
                        const data = await response.json();

                        if (!response.ok || !data.user) {
                            return;
                        }

                        localStorage.setItem('nfe_user', JSON.stringify(data.user));
                        this.permissions = Array.isArray(data.user.permissions) ? data.user.permissions : [];
                        this.permissionsReady = true;

                        if (this.activeTab !== null) {
                            this.tabs
                                .filter((tab) => !this.can(tab.permission))
                                .forEach((tab) => tab.pane?.remove());
                            this.tabs = this.tabs.filter((tab) => this.can(tab.permission));

                            if (!this.tabs.some((tab) => tab.id === this.activeTab)) {
                                const fallback = this.firstAllowedRoute();

                                if (fallback) {
                                    this.openTab(fallback);
                                }
                            }
                        }

                        this.persistTabs();
                    } catch (error) {
                        if (!error.authExpired) {
                            window.fiscalToast?.('warning', 'Não foi possível atualizar suas permissões agora.', 'Controle de acesso');
                        }
                    }
                },
                routeFor(url) {
                    const target = new URL(url, window.location.href);

                    if (target.origin !== window.location.origin) {
                        return null;
                    }

                    const route = this.routes[target.pathname];

                    return route ? { ...route, url: target.href } : null;
                },
                dashboardRoute() {
                    return {
                        ...this.routes['/dashboard'],
                        url: new URL('/dashboard', window.location.origin).href,
                    };
                },
                setAddress(tab) {
                    const target = new URL(tab.url, window.location.href);

                    if (window.location.pathname !== target.pathname || window.location.search !== target.search) {
                        window.history.pushState({ fiscalflowTab: tab.id }, '', target.pathname + target.search);
                    }
                },
                readPersistedTabs() {
                    try {
                        const stored = JSON.parse(localStorage.getItem('fiscalflow_tabs') || '[]');

                        if (!Array.isArray(stored)) {
                            return [];
                        }

                        return stored
                            .map((item) => this.routeFor(item.url || ''))
                            .filter((route, index, routes) => route && this.can(route.permission) && routes.findIndex((item) => item.id === route.id) === index);
                    } catch (error) {
                        localStorage.removeItem('fiscalflow_tabs');
                        localStorage.removeItem('fiscalflow_active_tab');
                        return [];
                    }
                },
                persistTabs() {
                    const navigation = this.tabs.map((tab) => ({
                        id: tab.id,
                        titulo: tab.titulo,
                        url: tab.url,
                    }));

                    localStorage.setItem('fiscalflow_tabs', JSON.stringify(navigation));
                    localStorage.setItem('fiscalflow_active_tab', this.activeTab || '');
                },
                bootstrap() {
                    const current = this.routeFor(window.location.href) || this.firstAllowedRoute();
                    const workspace = this.$refs.workspace;
                    const initialPane = workspace?.querySelector('[data-tab-pane]');

                    if (!current || !workspace || !initialPane) {
                        return;
                    }

                    if (!this.can(current.permission)) {
                        const fallback = this.firstAllowedRoute();

                        if (fallback) {
                            window.location.replace(fallback.url);
                        }
                        return;
                    }

                    const persisted = this.readPersistedTabs();
                    const restored = persisted.map((route) => ({
                        ...route,
                        conteudoHtml: '',
                        loaded: false,
                        loading: false,
                        error: '',
                        pane: null,
                    }));
                    let currentTab = restored.find((tab) => tab.id === current.id);

                    if (!currentTab) {
                        currentTab = {
                            ...current,
                            conteudoHtml: null,
                            loaded: true,
                            loading: false,
                            error: '',
                            pane: initialPane,
                        };
                        restored.push(currentTab);
                    } else {
                        currentTab.loaded = true;
                        currentTab.pane = initialPane;
                    }

                    initialPane.dataset.tabId = current.id;
                    this.tabs = restored;
                    const storedActive = localStorage.getItem('fiscalflow_active_tab');
                    this.activeTab = restored.some((tab) => tab.id === storedActive) ? storedActive : current.id;
                    this.persistTabs();
                    this.syncPanes();

                    if (this.activeTab !== current.id) {
                        this.activateTab(this.activeTab);
                    }
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
                    this.profileOpen = false;
                    this.openTab(route);
                },
                requestHeaders() {
                    const headers = {
                        Accept: 'text/html',
                        'X-Requested-With': 'XMLHttpRequest',
                    };
                    const token = localStorage.getItem('nfe_token');

                    if (token) {
                        headers.Authorization = 'Bearer ' + token;
                    }

                    return headers;
                },
                async openTab(route) {
                    if (!this.can(route.permission)) {
                        window.fiscalToast?.('error', 'Você não possui permissão para acessar esta área.', 'Acesso restrito');
                        return;
                    }

                    const existing = this.tabs.find((tab) => tab.id === route.id);

                    if (existing) {
                        await this.activateTab(existing.id);
                        return;
                    }

                    const tab = {
                        ...route,
                        conteudoHtml: '',
                        loaded: false,
                        loading: false,
                        error: '',
                        pane: null,
                    };

                    this.tabs.push(tab);
                    await this.activateTab(tab.id);
                },
                async activateTab(id) {
                    const tab = this.tabs.find((item) => item.id === id);

                    if (!tab) {
                        return;
                    }

                    this.activeTab = id;
                    this.setAddress(tab);
                    this.profileOpen = false;
                    this.persistTabs();
                    this.syncPanes();

                    if (!tab.loaded && !tab.loading) {
                        await this.loadTab(tab);
                    }
                },
                async loadTab(tab) {
                    if (tab.loaded || tab.loading) {
                        return;
                    }

                    tab.loading = true;
                    this.syncPanes();

                    try {
                        const response = await window.fetch(tab.url, {
                            headers: this.requestHeaders(),
                        });

                        if (response.status === 401) {
                            throw new Error('Sua sessão expirou. Entre novamente para continuar.');
                        }

                        if (!response.ok) {
                            throw new Error('Não foi possível carregar esta tela. Tente novamente.');
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
                        this.persistTabs();
                        this.syncPanes();
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
                        errorBox.className = 'border border-red-200 bg-red-50 p-5 text-sm text-red-700';
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
                    container.querySelectorAll('input').forEach((input) => {
                        let hasDynamic = false;
                        for (let i = 0; i < input.attributes.length; i++) {
                            const name = input.attributes[i].name;
                            if (name.includes('dynamic')) {
                                hasDynamic = true;
                                break;
                            }
                        }
                        if (hasDynamic) return;
                        const mask = input.getAttribute('x-mask');

                        if (mask && mask.startsWith("'") && mask.endsWith("'")) {
                            input.setAttribute('x-mask', mask.slice(1, -1));
                        }
                    });
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
                        this.persistTabs();
                        return;
                    }

                    const fallback = previous || next;

                    if (fallback) {
                        this.activateTab(fallback.id);
                        return;
                    }

                    this.openTab(this.firstAllowedRoute() || this.dashboardRoute());
                },
                isMenuActive(id) {
                    return this.activeTab === id;
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
<body class="bg-slate-50 text-slate-900 antialiased" x-data="tabManager" @keydown.escape.window="profileOpen = false; activeDropdown = null">
    <div class="min-h-screen flex flex-col">
        <!-- Topbar / Header -->
        <header class="bg-slate-950 text-slate-300 border-b border-slate-800 sticky top-0 z-50">
            <div class="mx-auto max-w-[1800px] px-4 sm:px-6 lg:px-8">
                <div class="flex h-16 items-center justify-between">
                    <!-- Logo / Brand & Navigation -->
                    <div class="flex items-center gap-8">
                        <div class="flex items-center gap-3 cursor-pointer" @click="openTab(dashboardRoute())">
                            <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-blue-600 text-lg font-bold text-white">F</div>
                            <span class="font-bold tracking-tight text-white text-lg">FiscalFlow</span>
                        </div>

                        <!-- Desktop Top Navigation Menu with Alpine Dropdowns -->
                        <nav x-data="{ activeDropdown: null }" @click.outside="activeDropdown = null" class="hidden lg:flex items-center gap-1">
                            <!-- Meu Negócio -->
                            <div class="relative">
                                <button @click="activeDropdown = activeDropdown === 'negocio' ? null : 'negocio'" class="flex items-center gap-1.5 px-3 py-2 text-sm font-medium hover:text-white rounded-md transition" :class="activeDropdown === 'negocio' ? 'text-white bg-slate-900' : ''">
                                    Meu Negócio <x-icon name="chevron-down" class="h-4 w-4" />
                                </button>
                               <div x-show="activeDropdown === 'negocio'" x-cloak x-transition class="absolute left-0 mt-2 w-48 bg-slate-900 border border-slate-800 rounded-lg shadow-xl py-1 text-sm text-slate-400 z-50">
                                    <a href="#" @click.prevent="openTab({id: 'dashboard', titulo: 'Início', permission: 'menu.dashboard', url: '{{ route('dashboard') }}'}); activeDropdown = null" class="block px-4 py-2 hover:bg-slate-800 hover:text-white transition">Início / Dashboard</a>
                                </div>
                            </div>

                            <!-- Cadastros -->
                            <div class="relative">
                                <button @click="activeDropdown = activeDropdown === 'cadastros' ? null : 'cadastros'" class="flex items-center gap-1.5 px-3 py-2 text-sm font-medium hover:text-white rounded-md transition" :class="activeDropdown === 'cadastros' ? 'text-white bg-slate-900' : ''">
                                    Cadastros <x-icon name="chevron-down" class="h-4 w-4" />
                                </button>
                                <div x-show="activeDropdown === 'cadastros'" x-cloak x-transition class="absolute left-0 mt-2 w-64 bg-slate-900 border border-slate-800 rounded-lg shadow-xl p-4 grid grid-cols-1 gap-2 text-sm text-slate-400 z-50">
                                    <div>
                                        <p class="text-xs font-semibold uppercase tracking-wider text-slate-500 mb-1">Cadastros</p>
                                        <a x-show="can('produtos.visualizar')" x-cloak href="#" @click.prevent="openTab({id: 'produtos', titulo: 'Produtos', permission: 'produtos.visualizar', url: '/dashboard/produtos'}); activeDropdown = null" class="block py-1 hover:text-white transition">Produtos</a>
                                        <a x-show="can('clientes.visualizar')" x-cloak href="#" @click.prevent="openTab({id: 'clientes', titulo: 'Clientes e Fornecedores', permission: 'clientes.visualizar', url: '/dashboard/clientes'}); activeDropdown = null" class="block py-1 hover:text-white transition">Clientes e Fornecedores</a>
                                    </div>
                                </div>
                            </div>

                            <!-- Vendas -->
                            <div class="relative">
                                <button @click="activeDropdown = activeDropdown === 'vendas' ? null : 'vendas'" class="flex items-center gap-1.5 px-3 py-2 text-sm font-medium hover:text-white rounded-md transition" :class="activeDropdown === 'vendas' ? 'text-white bg-slate-900' : ''">
                                    Vendas <x-icon name="chevron-down" class="h-4 w-4" />
                                </button>
                                <div x-show="activeDropdown === 'vendas'" x-cloak x-transition class="absolute left-0 mt-2 w-48 bg-slate-900 border border-slate-800 rounded-lg shadow-xl py-1 text-sm text-slate-400 z-50">
                                    <a x-show="can('nfe.visualizar')" x-cloak href="#" @click.prevent="openTab({id: 'nfe-index', titulo: 'Histórico de notas', permission: 'nfe.visualizar', url: '{{ route('nfe.index') }}'}); activeDropdown = null" class="block px-4 py-2 hover:bg-slate-800 hover:text-white transition">Notas Fiscais (NF-e)</a>
                                    <a x-show="can('nfe.criar')" x-cloak href="#" @click.prevent="openTab({id: 'nfe-create', titulo: 'Nova NF-e', permission: 'nfe.criar', url: '{{ route('nfe.create') }}'}); activeDropdown = null" class="block px-4 py-2 hover:bg-slate-800 hover:text-white transition">Emitir NF-e</a>
                                </div>
                            </div>
                        </nav>
                    </div>

                    <!-- User actions & Mobile Navigation -->
                    <div class="flex items-center gap-4">
                        <span class="hidden md:inline-flex items-center gap-2 bg-slate-900 border border-slate-800 px-3 py-1.5 text-xs font-medium text-emerald-400 rounded-full">
                            <span class="h-1.5 w-1.5 bg-emerald-500 rounded-full"></span> Homologação SEFAZ
                        </span>

                        <div class="relative">
                            <button type="button" class="flex items-center gap-2 border border-slate-800 bg-slate-900 px-3 py-2 text-sm font-medium text-slate-300 hover:text-white rounded-md transition" @click="profileOpen = !profileOpen" :aria-expanded="profileOpen">
                                <div id="sidebar-initials" class="flex h-7 w-7 items-center justify-center rounded-full bg-blue-500/20 text-xs font-semibold text-blue-300">FF</div>
                                <span class="hidden sm:inline" id="sidebar-user-name">Usuário</span>
                                <x-icon name="chevron-down" class="h-4 w-4" />
                            </button>
                            <div x-cloak x-show="profileOpen" x-transition @click.outside="profileOpen = false" class="absolute right-0 mt-2 w-64 border border-slate-800 bg-slate-950 p-2 shadow-xl rounded-lg z-50">
                                <div class="border-b border-slate-800 px-3 py-3">
                                    <p class="text-sm font-semibold text-white" id="profile-menu-name">Usuário</p>
                                    <p class="mt-1 text-xs text-slate-500" id="profile-menu-role">Autenticado</p>
                                </div>
                                <a x-show="can('menu.configuracoes')" x-cloak href="#" @click.prevent="openTab({id: 'configuracoes', titulo: 'Configurações', permission: 'menu.configuracoes', url: '{{ route('configuracoes') }}'}); profileOpen = false" class="flex items-center gap-3 px-3 py-3 text-sm text-slate-300 hover:bg-slate-900 hover:text-white rounded-md"><x-icon name="settings" class="h-4 w-4" /> Configurações</a>
                                <a x-show="can('menu.usuarios')" x-cloak href="#" @click.prevent="openTab({id: 'usuarios', titulo: 'Usuários e permissões', permission: 'menu.usuarios', url: '{{ route('usuarios.index') }}'}); profileOpen = false" class="flex items-center gap-3 px-3 py-3 text-sm text-slate-300 hover:bg-slate-900 hover:text-white rounded-md"><x-icon name="users" class="h-4 w-4" /> Usuários e permissões</a>
                                <button type="button" onclick="logoutFiscalFlow()" class="flex w-full items-center gap-3 px-3 py-3 text-left text-sm text-red-400 hover:bg-slate-900 rounded-md"><x-icon name="logout" class="h-4 w-4" /> Sair do sistema</button>
                            </div>
                        </div>

                        <!-- Mobile navigation toggle -->
                        <button class="p-2 text-slate-400 hover:text-white lg:hidden rounded-md" @click="sidebarOpen = !sidebarOpen" aria-label="Abrir menu" :aria-expanded="sidebarOpen">
                            <x-icon name="menu" class="h-6 w-6" />
                        </button>
                    </div>
                </div>
            </div>

            <!-- Mobile menu drawer -->
            <div x-cloak x-show="sidebarOpen" x-transition class="lg:hidden bg-slate-900 border-t border-slate-800 px-4 py-3 space-y-1">
                <p class="text-xs font-semibold uppercase tracking-wider text-slate-500 px-3 py-1">Menu</p>
                <a href="#" @click.prevent="openTab({id: 'dashboard', titulo: 'Início', permission: 'menu.dashboard', url: '{{ route('dashboard') }}'}); sidebarOpen = false" class="block px-3 py-2 text-slate-300 hover:text-white hover:bg-slate-800 rounded-md text-sm">Dashboard</a>
                <a x-show="can('nfe.visualizar')" x-cloak href="#" @click.prevent="openTab({id: 'nfe-index', titulo: 'Histórico de notas', permission: 'nfe.visualizar', url: '{{ route('nfe.index') }}'}); sidebarOpen = false" class="block px-3 py-2 text-slate-300 hover:text-white hover:bg-slate-800 rounded-md text-sm">Notas Fiscais (NF-e)</a>
                <a x-show="can('clientes.visualizar')" x-cloak href="#" @click.prevent="openTab({id: 'clientes', titulo: 'Clientes e Fornecedores', permission: 'clientes.visualizar', url: '/dashboard/clientes'}); sidebarOpen = false" class="block px-3 py-2 text-slate-300 hover:text-white hover:bg-slate-800 rounded-md text-sm">Clientes e Fornecedores</a>
                <a x-show="can('nfe.criar')" x-cloak href="#" @click.prevent="openTab({id: 'nfe-create', titulo: 'Nova NF-e', permission: 'nfe.criar', url: '{{ route('nfe.create') }}'}); sidebarOpen = false" class="block px-3 py-2 text-slate-300 hover:text-white hover:bg-slate-800 rounded-md text-sm">Emitir NF-e</a>
            </div>
        </header>

        <!-- Thin Tabs Bar under top header with breadcrumb -->
        <div x-cloak x-show="tabs.length" class="bg-white border-b border-slate-200 px-4 pt-2">
            <div class="w-full flex items-center justify-between mb-1.5 text-xs text-slate-400">
                <div class="flex items-center gap-1.5">
                    <span>FiscalFlow</span>
                    <span>/</span>
                    <span x-show="activeTabData?.id === 'nfe-index' || activeTabData?.id === 'nfe-create'" x-cloak>Vendas</span>
                    <span x-show="activeTabData?.id === 'nfe-index' || activeTabData?.id === 'nfe-create'" x-cloak>/</span>
                    <span x-show="activeTabData?.id === 'fornecedores' || activeTabData?.id === 'produtos' || activeTabData?.id === 'clientes'" x-cloak>Cadastros</span>
                    <span x-show="activeTabData?.id === 'fornecedores' || activeTabData?.id === 'produtos' || activeTabData?.id === 'clientes'" x-cloak>/</span>
                    <span x-show="activeTabData?.id === 'usuarios' || activeTabData?.id === 'configuracoes'" x-cloak>Meu Negócio</span>
                    <span x-show="activeTabData?.id === 'usuarios' || activeTabData?.id === 'configuracoes'" x-cloak>/</span>
                    <span class="text-blue-600 font-semibold" x-text="activeTabData?.id === 'nfe-index' ? 'Notas Fiscais (NF-e)' : (activeTabData?.titulo || 'Início')"></span>
                </div>
            </div>
            <div class="w-full flex min-w-0 items-end gap-1 overflow-x-auto" role="tablist" aria-label="Abas abertas">
                <template x-for="tab in tabs" :key="tab.id">
                    <div class="group flex min-w-[9rem] max-w-[16rem] shrink-0 items-center border-b-2 px-3 py-1.5 text-sm" :class="activeTab === tab.id ? 'border-blue-600 text-blue-700 font-semibold' : 'border-transparent text-slate-500 hover:border-slate-300 hover:text-slate-700'" role="tab" :aria-selected="activeTab === tab.id">
                        <button type="button" class="min-w-0 flex-1 truncate text-left" @click="activateTab(tab.id)" x-text="tab.titulo"></button>
                        <span x-show="tab.loading" x-cloak class="ml-2 h-3.5 w-3.5 shrink-0 animate-spin rounded-full border-2 border-slate-300 border-t-blue-600" aria-label="Carregando"></span>
                        <button type="button" @click="closeTab(tab.id)" class="ml-2 rounded p-0.5 text-slate-400 opacity-0 transition hover:bg-slate-100 hover:text-slate-700 group-hover:opacity-100" :class="activeTab === tab.id ? 'opacity-100' : ''" :aria-label="'Fechar aba ' + tab.titulo">×</button>
                    </div>
                </template>
            </div>
        </div>

        <div class="flex-1 flex flex-col">
            <!-- Content area maximizing screen utilization -->
            <main class="w-full px-4 py-3 lg:px-6">
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
        const userName = user.name || 'Usuário';
        const initials = userName.split(' ').map((part) => part[0]).slice(0, 2).join('').toUpperCase();
        const elements = {
            sidebarInitials: document.getElementById('sidebar-initials'),
            sidebarUserName: document.getElementById('sidebar-user-name'),
            sidebarUserRole: document.getElementById('sidebar-user-role'),
            headerInitials: document.getElementById('header-initials'),
            headerUserName: document.getElementById('header-user-name'),
            profileMenuName: document.getElementById('profile-menu-name'),
            profileMenuRole: document.getElementById('profile-menu-role'),
        };

        if (elements.sidebarInitials) elements.sidebarInitials.textContent = initials;
        if (elements.sidebarUserName) elements.sidebarUserName.textContent = userName;
        if (elements.sidebarUserRole) elements.sidebarUserRole.textContent = user.perfil || 'Autenticado';
        if (elements.headerInitials) elements.headerInitials.textContent = initials;
        if (elements.headerUserName) elements.headerUserName.textContent = userName;
        if (elements.profileMenuName) elements.profileMenuName.textContent = userName;
        if (elements.profileMenuRole) elements.profileMenuRole.textContent = user.perfil || 'Autenticado';
    }());

    function logoutFiscalFlow() {
        localStorage.removeItem('nfe_token');
        localStorage.removeItem('nfe_user');
        localStorage.removeItem('fiscalflow_tabs');
        localStorage.removeItem('fiscalflow_active_tab');
        localStorage.removeItem('fiscalflow_sidebar_collapsed');
        window.location.href = '{{ route('login') }}';
    }
</script>
</html>
@endif
