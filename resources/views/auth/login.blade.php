<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Entrar · FiscalFlow</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>
<body class="min-h-screen bg-slate-950 text-slate-900 antialiased">
    <main class="grid min-h-screen lg:grid-cols-[1.1fr_0.9fr]">
        <section class="relative hidden overflow-hidden bg-gradient-to-br from-slate-950 via-slate-900 to-blue-950 p-12 text-white lg:flex lg:flex-col lg:justify-between">
            <div class="absolute -right-32 -top-32 h-96 w-96 rounded-full bg-blue-500/20 blur-3xl"></div>
            <div class="relative flex items-center gap-3"><div class="flex h-11 w-11 items-center justify-center rounded-xl bg-blue-600 text-xl font-bold">F</div><span class="text-lg font-semibold">FiscalFlow</span></div>
            <div class="relative max-w-xl"><p class="mb-5 text-sm font-semibold uppercase tracking-[0.2em] text-blue-300">Operação fiscal</p><h1 class="text-5xl font-bold leading-tight tracking-tight">Emita suas NF-e com controle e segurança.</h1><p class="mt-6 max-w-lg text-lg leading-8 text-slate-300">Um workspace interno para acompanhar emissões, documentos e o relacionamento com a SEFAZ.</p></div>
            <p class="relative text-sm text-slate-500">Ambiente de homologação · FiscalFlow</p>
        </section>
        <section class="flex items-center justify-center bg-slate-50 px-6 py-12 sm:px-10">
            <div class="w-full max-w-md" x-data="loginForm()">
                <div class="mb-10 lg:hidden"><div class="flex items-center gap-3"><div class="flex h-11 w-11 items-center justify-center rounded-xl bg-blue-600 text-xl font-bold text-white">F</div><span class="text-lg font-semibold text-slate-900">FiscalFlow</span></div></div>
                <div class="rounded-2xl border border-slate-200 bg-white p-7 shadow-sm sm:p-9">
                    <p class="text-sm font-semibold text-blue-600">Bem-vindo de volta</p><h2 class="mt-2 text-2xl font-bold tracking-tight text-slate-950">Acesse sua conta</h2><p class="mt-2 text-sm text-slate-500">Entre para gerenciar suas operações fiscais.</p>
                    <form class="mt-8 space-y-5" @submit.prevent="submit">
                        <div><label for="email" class="mb-2 block text-sm font-medium text-slate-700">E-mail</label><input id="email" x-model="email" type="email" autocomplete="username" required class="w-full rounded-none border border-slate-300 bg-white px-4 py-3 text-sm shadow-sm outline-none transition focus:border-blue-500 focus:ring-4 focus:ring-blue-100" placeholder="voce@empresa.com.br"></div>
                        <div><div class="mb-2 flex items-center justify-between"><label for="password" class="block text-sm font-medium text-slate-700">Senha</label></div><input id="password" x-model="password" type="password" autocomplete="current-password" required class="w-full rounded-none border border-slate-300 bg-white px-4 py-3 text-sm shadow-sm outline-none transition focus:border-blue-500 focus:ring-4 focus:ring-blue-100" placeholder="Digite sua senha"></div>
                        <div x-cloak x-show="error" class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700" x-text="error"></div>
                        <button type="submit" :disabled="loading" class="flex w-full items-center justify-center gap-2 rounded-xl bg-blue-600 px-4 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-70"><svg x-show="loading" class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path></svg><span x-text="loading ? 'Autenticando...' : 'Entrar no sistema'"></span></button>
                    </form>
                </div>
                {{-- Cadastro público de empresas desativado. --}}
            </div>
        </section>
    </main>
    <script>
        function loginForm() {
            return {
                email: '', password: '', loading: false, error: '',
                init() {
                    const message = sessionStorage.getItem('fiscalflow_auth_message');
                    if (message) {
                        this.error = message;
                        sessionStorage.removeItem('fiscalflow_auth_message');
                    }
                },
                async submit() {
                    this.loading = true; this.error = '';
                    try {
                        const response = await fetch('/api/auth/login', { method: 'POST', headers: {'Content-Type': 'application/json', 'Accept': 'application/json'}, body: JSON.stringify({email: this.email, password: this.password}) });
                        const data = await response.json();
                        if (!response.ok) throw new Error(data.message || 'Não foi possível autenticar.');
                        localStorage.setItem('nfe_token', data.access_token);
                        localStorage.setItem('nfe_user', JSON.stringify(data.user));
                        window.location.href = '/dashboard';
                    } catch (error) { this.error = error.message; } finally { this.loading = false; }
                }
            }
        }
        if (localStorage.getItem('nfe_token')) window.location.replace('/dashboard');
    </script>
</body>
</html>
