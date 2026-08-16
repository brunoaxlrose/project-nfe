<x-app-layout title="Usuários e permissões" :partial="$partial ?? false">
    <div class="mx-auto max-w-[1600px]" x-data="usuarioManager" x-init="init()">
        <div class="mb-6 flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <p class="mb-2 text-sm font-medium text-blue-600">Administração / Acessos</p>
                <h1 class="text-2xl font-bold tracking-tight text-slate-950">Usuários e permissões</h1>
                <p class="mt-2 text-sm text-slate-500">Gerencie quem acessa a empresa e quais ações cada perfil pode executar.</p>
            </div>
            <button type="button" @click="abrirNovoUsuario" class="bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-blue-700">Novo usuário</button>
        </div>

        <div class="space-y-6">
            <section class="border border-slate-200 bg-white shadow-sm">
                <div class="flex flex-col gap-3 border-b border-slate-200 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h2 class="text-sm font-semibold text-slate-900">Usuários do tenant</h2>
                        <p class="mt-1 text-xs text-slate-500">Somente funcionários vinculados à empresa logada aparecem aqui.</p>
                    </div>
                    <span class="text-xs text-slate-500" x-text="usuarios.length + ' usuário(s)'"></span>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[820px] text-sm">
                        <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
                            <tr>
                                <th class="px-5 py-3">Nome</th>
                                <th class="px-5 py-3">E-mail</th>
                                <th class="px-5 py-3">Perfil</th>
                                <th class="px-5 py-3">Acessos extras</th>
                                <th class="px-5 py-3">Status</th>
                                <th class="px-5 py-3 text-right">Ações</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <template x-for="usuario in usuarios" :key="usuario.id">
                                <tr class="hover:bg-slate-50">
                                    <td class="px-5 py-4 font-medium text-slate-900" x-text="usuario.nome"></td>
                                    <td class="px-5 py-4 text-slate-600" x-text="usuario.email"></td>
                                    <td class="px-5 py-4 text-slate-600" x-text="usuario.perfil?.nome || 'Sem perfil'"></td>
                                    <td class="px-5 py-4">
                                        <span class="inline-flex bg-slate-100 px-2 py-1 text-xs font-semibold text-slate-600" x-show="!usuario.permissoes_especificas?.length">Nenhum</span>
                                        <span x-show="usuario.permissoes_especificas?.length" x-cloak class="inline-flex bg-blue-50 px-2 py-1 text-xs font-semibold text-blue-700" x-text="usuario.permissoes_especificas.length + ' permissão(ões)'"></span>
                                    </td>
                                    <td class="px-5 py-4">
                                        <span class="inline-flex px-2 py-1 text-xs font-semibold" :class="usuario.ativo ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-500'" x-text="usuario.ativo ? 'Ativo' : 'Inativo'"></span>
                                    </td>
                                    <td class="px-5 py-4 text-right">
                                        <button type="button" @click="editarUsuario(usuario)" class="text-sm font-semibold text-blue-700 hover:text-blue-900">Editar</button>
                                    </td>
                                </tr>
                            </template>
                            <tr x-show="!isLoading && !usuarios.length" x-cloak>
                                <td colspan="6" class="px-5 py-10 text-center text-sm text-slate-500">Nenhum usuário cadastrado.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>

            <section class="border border-slate-200 bg-white p-5 shadow-sm">
                <div class="mb-5 flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
                    <div>
                        <h2 class="text-sm font-semibold text-slate-900">Permissões por perfil</h2>
                        <p class="mt-1 text-xs text-slate-500">Ajuste o acesso padrão de Administrador, Operador, Faturamento ou outros perfis do tenant.</p>
                    </div>
                    <label class="w-full lg:w-80">
                        <span class="label">Perfil</span>
                        <select x-model.number="perfilSelecionadoId" @change="carregarPermissoesPerfil" class="field">
                            <option value="">Selecione</option>
                            <template x-for="perfil in perfis" :key="perfil.id">
                                <option :value="perfil.id" x-text="perfil.nome"></option>
                            </template>
                        </select>
                    </label>
                </div>
                <div class="grid max-h-[380px] gap-4 overflow-y-auto pr-1 lg:grid-cols-2 2xl:grid-cols-3">
                    <template x-for="grupo in permissoes" :key="grupo[0]?.categoria">
                        <div class="border border-slate-100 p-4">
                            <p class="mb-3 text-xs font-semibold uppercase tracking-wide text-slate-500" x-text="grupo[0]?.categoria"></p>
                            <div class="space-y-2">
                                <template x-for="permissao in grupo" :key="permissao.id">
                                    <label class="flex items-start gap-2 text-sm text-slate-700">
                                        <input type="checkbox" class="mt-1 h-4 w-4 accent-blue-600" :value="permissao.id" x-model="permissoesSelecionadas">
                                        <span>
                                            <span class="block font-medium" x-text="permissao.nome"></span>
                                            <span class="block text-xs text-slate-500" x-text="permissao.slug"></span>
                                        </span>
                                    </label>
                                </template>
                            </div>
                        </div>
                    </template>
                </div>
                <div class="mt-5 flex justify-end border-t border-slate-100 pt-4">
                    <button type="button" @click="salvarPermissoes" :disabled="!perfilSelecionadoId || salvandoPermissoes" class="bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white hover:bg-slate-800 disabled:opacity-50" x-text="salvandoPermissoes ? 'Salvando...' : 'Salvar permissões do perfil'"></button>
                </div>
            </section>
        </div>

        <div x-cloak x-show="modalUsuarioAberto" x-transition.opacity class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/60 p-3 sm:p-6">
            <div @click.outside="fecharModalUsuario" class="flex max-h-[92vh] w-full max-w-5xl flex-col overflow-hidden border border-slate-200 bg-white shadow-2xl">
                <div class="flex items-start justify-between gap-4 border-b border-slate-200 px-5 py-4">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-blue-600">Acessos</p>
                        <h2 class="mt-1 text-xl font-bold text-slate-950" x-text="form.id ? 'Editar usuário' : 'Novo usuário'"></h2>
                        <p class="mt-1 text-sm text-slate-500">Complete as etapas para liberar o acesso com segurança.</p>
                    </div>
                    <button type="button" @click="fecharModalUsuario" class="px-3 py-2 text-sm font-semibold text-slate-500 hover:bg-slate-100">Fechar</button>
                </div>

                <div class="border-b border-slate-200 px-5 py-4">
                    <div class="grid gap-3 sm:grid-cols-4">
                        <template x-for="item in etapas" :key="item.id">
                            <button type="button" @click="step = item.id" class="flex items-center gap-3 border px-3 py-3 text-left transition" :class="step === item.id ? 'border-blue-600 bg-blue-50 text-blue-700' : 'border-slate-200 text-slate-500 hover:bg-slate-50'">
                                <span class="flex h-7 w-7 shrink-0 items-center justify-center border text-xs font-bold" :class="step === item.id ? 'border-blue-600 bg-blue-600 text-white' : 'border-slate-200 bg-white text-slate-500'" x-text="item.id"></span>
                                <span class="min-w-0">
                                    <span class="block text-xs font-bold uppercase tracking-wide" x-text="item.titulo"></span>
                                    <span class="block truncate text-xs" x-text="item.subtitulo"></span>
                                </span>
                            </button>
                        </template>
                    </div>
                </div>

                <form @submit.prevent="salvarUsuario" class="flex min-h-0 flex-1 flex-col">
                    <div class="min-h-0 flex-1 overflow-y-auto p-5">
                        <div x-show="step === 1" x-cloak class="grid gap-4 md:grid-cols-2">
                            <label>
                                <span class="label">Nome completo</span>
                                <input x-model="form.nome" class="field" required placeholder="Ex: Bruno Oliveira">
                            </label>
                            <label>
                                <span class="label">E-mail</span>
                                <input x-model="form.email" type="email" class="field" required placeholder="usuario@empresa.com.br">
                            </label>
                        </div>

                        <div x-show="step === 2" x-cloak class="grid gap-4 md:grid-cols-2">
                            <label>
                                <span class="label">Perfil de acesso</span>
                                <select x-model.number="form.id_perfil" class="field" required>
                                    <option value="">Selecione</option>
                                    <template x-for="perfil in perfis" :key="perfil.id">
                                        <option :value="perfil.id" x-text="perfil.nome"></option>
                                    </template>
                                </select>
                            </label>
                            <div class="border border-slate-200 p-4">
                                <label class="flex items-center gap-3">
                                    <input x-model="form.ativo" type="checkbox" class="h-4 w-4 accent-blue-600">
                                    <span class="text-sm font-semibold text-slate-800">Usuário ativo</span>
                                </label>
                                <p class="mt-2 text-xs text-slate-500">Usuários inativos não conseguem entrar no sistema, mesmo com senha correta.</p>
                            </div>
                        </div>

                        <div x-show="step === 3" x-cloak>
                            <div class="mb-4 border border-blue-100 bg-blue-50 px-4 py-3 text-sm text-blue-800">
                                Permissões específicas são exceções. O ideal é controlar o acesso pelo perfil e usar esta etapa somente quando necessário.
                            </div>
                            <div class="grid max-h-[50vh] gap-4 overflow-y-auto pr-1 lg:grid-cols-2">
                                <template x-for="grupo in permissoes" :key="'usuario-' + grupo[0]?.categoria">
                                    <div class="border border-slate-100 p-4">
                                        <p class="mb-3 text-xs font-semibold uppercase tracking-wide text-slate-500" x-text="grupo[0]?.categoria"></p>
                                        <div class="space-y-2">
                                            <template x-for="permissao in grupo" :key="'usuario-permissao-' + permissao.id">
                                                <label class="flex items-start gap-2 text-sm text-slate-700">
                                                    <input type="checkbox" class="mt-1 h-4 w-4 accent-blue-600" :value="permissao.id" x-model="form.permissoes_especificas">
                                                    <span>
                                                        <span class="block font-medium" x-text="permissao.nome"></span>
                                                        <span class="block text-xs text-slate-500" x-text="permissao.slug"></span>
                                                    </span>
                                                </label>
                                            </template>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </div>

                        <div x-show="step === 4" x-cloak class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_320px]">
                            <div class="grid gap-4 sm:grid-cols-2">
                                <label>
                                    <span class="label" x-text="form.id ? 'Nova senha opcional' : 'Senha temporária'"></span>
                                    <input x-model="form.password" type="password" autocomplete="new-password" class="field" :required="!form.id">
                                </label>
                                <label>
                                    <span class="label">Confirmar senha</span>
                                    <input x-model="form.password_confirmation" type="password" autocomplete="new-password" class="field" :required="!form.id">
                                </label>
                                <p class="text-xs text-slate-500 sm:col-span-2">Use no mínimo 8 caracteres, com maiúscula, minúscula, número e símbolo.</p>
                            </div>
                            <aside class="border border-slate-200 bg-slate-50 p-4">
                                <p class="text-xs font-bold uppercase tracking-wide text-slate-500">Resumo</p>
                                <dl class="mt-4 space-y-3 text-sm">
                                    <div><dt class="text-slate-500">Nome</dt><dd class="font-semibold text-slate-900" x-text="form.nome || 'Não informado'"></dd></div>
                                    <div><dt class="text-slate-500">E-mail</dt><dd class="font-semibold text-slate-900" x-text="form.email || 'Não informado'"></dd></div>
                                    <div><dt class="text-slate-500">Perfil</dt><dd class="font-semibold text-slate-900" x-text="perfilSelecionadoNome()"></dd></div>
                                    <div><dt class="text-slate-500">Permissões extras</dt><dd class="font-semibold text-slate-900" x-text="form.permissoes_especificas.length"></dd></div>
                                </dl>
                            </aside>
                        </div>

                        <p x-show="mensagem" x-cloak class="mt-5 border px-3 py-2 text-sm" :class="erro ? 'border-red-200 bg-red-50 text-red-700' : 'border-emerald-200 bg-emerald-50 text-emerald-700'" x-text="mensagem"></p>
                    </div>

                    <div class="flex flex-col-reverse gap-3 border-t border-slate-200 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
                        <button type="button" @click="fecharModalUsuario" class="border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">Cancelar</button>
                        <div class="flex items-center justify-end gap-3">
                            <button type="button" x-show="step > 1" x-cloak @click="voltarStep" class="border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">Voltar</button>
                            <button type="button" x-show="step < 4" x-cloak @click="avancarStep" class="bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">Continuar</button>
                            <button type="submit" x-show="step === 4" x-cloak :disabled="salvandoUsuario" class="bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700 disabled:opacity-50" x-text="salvandoUsuario ? 'Salvando...' : 'Salvar usuário'"></button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        if (!window.fiscalUsuarioManagerRegistered) {
            window.fiscalUsuarioManagerRegistered = true;
            window.registerFiscalAlpine((Alpine) => {
                Alpine.data('usuarioManager', () => ({
                    usuarios: [],
                    perfis: [],
                    permissoes: [],
                    perfilSelecionadoId: '',
                    permissoesSelecionadas: [],
                    etapas: [
                        { id: 1, titulo: 'Dados', subtitulo: 'Nome e e-mail' },
                        { id: 2, titulo: 'Perfil', subtitulo: 'Papel e status' },
                        { id: 3, titulo: 'Permissões', subtitulo: 'Exceções' },
                        { id: 4, titulo: 'Senha', subtitulo: 'Revisão final' },
                    ],
                    step: 1,
                    modalUsuarioAberto: false,
                    isLoading: false,
                    salvandoUsuario: false,
                    salvandoPermissoes: false,
                    mensagem: '',
                    erro: false,
                    form: { id: null, nome: '', email: '', id_perfil: '', ativo: true, permissoes_especificas: [], password: '', password_confirmation: '' },
                    init() { this.carregarTudo(); },
                    headers() { return { Authorization: 'Bearer ' + localStorage.getItem('nfe_token'), Accept: 'application/json', 'Content-Type': 'application/json' }; },
                    async parse(response) { const data = await response.json().catch(() => ({})); if (!response.ok) throw new Error(data.message || 'Não foi possível concluir a operação.'); return data; },
                    async carregarTudo() {
                        if (this.isLoading) return;
                        this.isLoading = true;
                        try {
                            const [usuariosResponse, perfisResponse] = await Promise.all([
                                fiscalFetch('/api/usuarios', { headers: this.headers() }),
                                fiscalFetch('/api/perfis', { headers: this.headers() }),
                            ]);
                            this.usuarios = await this.parse(usuariosResponse);
                            const data = await this.parse(perfisResponse);
                            this.perfis = data.perfis || [];
                            this.permissoes = data.permissoes || [];
                        } catch (error) {
                            window.fiscalToast?.('error', error.message, 'Usuários');
                        } finally {
                            this.isLoading = false;
                        }
                    },
                    limparForm() {
                        this.form = { id: null, nome: '', email: '', id_perfil: '', ativo: true, permissoes_especificas: [], password: '', password_confirmation: '' };
                        this.step = 1;
                        this.mensagem = '';
                        this.erro = false;
                    },
                    abrirNovoUsuario() {
                        this.limparForm();
                        this.modalUsuarioAberto = true;
                    },
                    fecharModalUsuario() {
                        if (this.salvandoUsuario) return;
                        this.modalUsuarioAberto = false;
                        this.limparForm();
                    },
                    editarUsuario(usuario) {
                        this.form = {
                            id: usuario.id,
                            nome: usuario.nome,
                            email: usuario.email,
                            id_perfil: usuario.perfil?.id || '',
                            ativo: usuario.ativo,
                            permissoes_especificas: (usuario.permissoes_especificas || []).map((permissao) => permissao.id),
                            password: '',
                            password_confirmation: '',
                        };
                        this.step = 1;
                        this.mensagem = '';
                        this.erro = false;
                        this.modalUsuarioAberto = true;
                    },
                    avancarStep() {
                        if (this.step < 4) this.step += 1;
                    },
                    voltarStep() {
                        if (this.step > 1) this.step -= 1;
                    },
                    perfilSelecionadoNome() {
                        const perfil = this.perfis.find((item) => Number(item.id) === Number(this.form.id_perfil));
                        return perfil?.nome || 'Não selecionado';
                    },
                    async salvarUsuario() {
                        this.salvandoUsuario = true;
                        this.mensagem = '';
                        try {
                            const url = this.form.id ? '/api/usuarios/' + this.form.id : '/api/usuarios';
                            const method = this.form.id ? 'PUT' : 'POST';
                            const response = await fiscalFetch(url, { method, headers: this.headers(), body: JSON.stringify(this.form) });
                            const data = await this.parse(response);
                            this.erro = false;
                            window.fiscalToast?.('success', data.message, 'Acessos');
                            this.modalUsuarioAberto = false;
                            this.limparForm();
                            await this.carregarTudo();
                        } catch (error) {
                            this.erro = true;
                            this.mensagem = error.message;
                        } finally {
                            this.salvandoUsuario = false;
                        }
                    },
                    carregarPermissoesPerfil() {
                        const perfil = this.perfis.find((item) => Number(item.id) === Number(this.perfilSelecionadoId));
                        this.permissoesSelecionadas = perfil ? (perfil.permissoes || []).map((item) => item.id) : [];
                    },
                    async salvarPermissoes() {
                        this.salvandoPermissoes = true;
                        try {
                            const response = await fiscalFetch('/api/perfis/' + this.perfilSelecionadoId + '/permissoes', {
                                method: 'PUT',
                                headers: this.headers(),
                                body: JSON.stringify({ permissoes: this.permissoesSelecionadas.map(Number) }),
                            });
                            const data = await this.parse(response);
                            window.fiscalToast?.('success', data.message, 'Permissões');
                            await this.carregarTudo();
                            this.carregarPermissoesPerfil();
                        } catch (error) {
                            window.fiscalToast?.('error', error.message, 'Permissões');
                        } finally {
                            this.salvandoPermissoes = false;
                        }
                    },
                }));
            });
        }
    </script>
</x-app-layout>
