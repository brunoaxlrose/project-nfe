<x-app-layout title="Usuários e permissões" :partial="$partial ?? false">
    <div class="mx-auto max-w-[1600px]" x-data="usuarioManager" x-init="init()">
        <div class="mb-6 flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <p class="mb-2 text-sm font-medium text-blue-600">Administração / Acessos</p>
                <h1 class="text-2xl font-bold tracking-tight text-slate-950">Usuários e permissões</h1>
                <p class="mt-2 text-sm text-slate-500">Gerencie quem acessa a empresa e quais ações cada perfil pode executar.</p>
            </div>
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
                <button type="button" @click="abrirGerenciarPerfis" class="border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">Gerenciar Perfil</button>
                <button type="button" @click="abrirNovoUsuario" class="bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-blue-700">Novo usuário</button>
            </div>
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

        <div x-cloak x-show="modalPerfilAberto" x-transition.opacity class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/60 p-3 sm:p-6">
            <div @click.outside="fecharModalPerfil" class="flex h-[88vh] w-full max-w-5xl flex-col overflow-hidden border border-slate-200 bg-white shadow-2xl">
                <div class="flex items-start justify-between gap-4 border-b border-slate-200 px-5 py-4">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-blue-600">Perfis</p>
                        <h2 class="mt-1 text-xl font-bold text-slate-950">Gerenciar Perfil</h2>
                        <p class="mt-1 text-sm text-slate-500">Crie, renomeie ou remova perfis de acesso do tenant.</p>
                    </div>
                    <button type="button" @click="fecharModalPerfil" class="px-3 py-2 text-sm font-semibold text-slate-500 hover:bg-slate-100">Fechar</button>
                </div>

                <div class="grid min-h-0 flex-1 gap-0 overflow-hidden lg:grid-cols-[minmax(0,340px)_minmax(0,1fr)]">
                    <div class="min-h-0 overflow-y-auto border-b border-slate-200 lg:border-b-0 lg:border-r">
                        <div class="border-b border-slate-100 px-5 py-3">
                            <p class="text-sm font-semibold text-slate-900">Perfis cadastrados</p>
                            <p class="mt-1 text-xs text-slate-500">Perfis com usuários vinculados não podem ser excluídos.</p>
                        </div>
                        <div class="divide-y divide-slate-100">
                            <template x-for="perfil in perfis" :key="'modal-perfil-' + perfil.id">
                                <div class="flex flex-col gap-3 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
                                    <div>
                                        <p class="font-semibold text-slate-900" x-text="perfil.nome"></p>
                                        <p class="mt-1 text-xs text-slate-500">
                                            <span x-text="perfil.slug"></span>
                                            <span> · </span>
                                            <span x-text="(perfil.usuarios_count || 0) + ' usuário(s)'"></span>
                                        </p>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <button type="button" @click="editarPerfil(perfil)" class="inline-flex h-9 w-9 items-center justify-center border border-slate-200 text-blue-700 hover:bg-blue-50" title="Editar perfil" aria-label="Editar perfil">
                                            <x-icon name="edit" class="h-4 w-4" />
                                        </button>
                                        <button type="button" @click="excluirPerfil(perfil)" :disabled="perfilEmUso(perfil) || salvandoPerfil" class="inline-flex h-9 w-9 items-center justify-center border border-slate-200 text-red-600 hover:bg-red-50 disabled:cursor-not-allowed disabled:text-slate-300 disabled:hover:bg-white" title="Excluir perfil" aria-label="Excluir perfil">
                                            <x-icon name="trash" class="h-4 w-4" />
                                        </button>
                                    </div>
                                </div>
                            </template>
                            <div x-show="!perfis.length" x-cloak class="px-5 py-8 text-center text-sm text-slate-500">Nenhum perfil cadastrado.</div>
                        </div>
                    </div>

                    <form @submit.prevent="salvarPerfil" class="flex min-h-0 flex-col bg-slate-50">
                        <div class="min-h-0 flex-1 overflow-y-auto">
                            <div class="border-b border-slate-200 p-5">
                                <p class="text-sm font-semibold text-slate-900" x-text="perfilForm.id ? 'Editar perfil' : 'Novo perfil'"></p>
                                <label class="mt-4 block">
                                    <span class="label">Nome do perfil</span>
                                    <input x-model="perfilForm.nome" class="field bg-white" required maxlength="80" placeholder="Ex: Financeiro">
                                </label>
                                <p class="mt-2 text-xs text-slate-500">Crie ou selecione um perfil para editar as permissões padrão.</p>
                                <p x-show="mensagemPerfil" x-cloak class="mt-4 border px-3 py-2 text-sm" :class="erroPerfil ? 'border-red-200 bg-red-50 text-red-700' : 'border-emerald-200 bg-emerald-50 text-emerald-700'" x-text="mensagemPerfil"></p>
                            </div>

                            <div x-show="perfilForm.id" x-cloak class="border-b border-slate-200 bg-white p-5">
                                <div class="mb-4 flex flex-col gap-3 xl:flex-row xl:items-end xl:justify-between">
                                    <div>
                                        <p class="text-sm font-semibold text-slate-900">Usuários do perfil</p>
                                        <p class="mt-1 text-xs text-slate-500">Adicione usuários existentes a este grupo ou remova o vínculo.</p>
                                    </div>
                                    <div class="grid w-full gap-2 sm:grid-cols-[minmax(0,1fr)_auto] xl:w-[430px]">
                                        <select x-model.number="usuarioPerfilFormId" class="field bg-white">
                                            <option value="">Adicionar usuário</option>
                                            <template x-for="usuario in usuariosDisponiveisPerfil()" :key="'perfil-disponivel-' + usuario.id">
                                                <option :value="usuario.id" x-text="usuario.nome + ' - ' + usuario.email"></option>
                                            </template>
                                        </select>
                                        <button type="button" @click="adicionarUsuarioPerfil" :disabled="!usuarioPerfilFormId || salvandoPerfilUsuario" class="inline-flex h-[46px] items-center justify-center gap-2 bg-blue-600 px-4 text-sm font-semibold text-white hover:bg-blue-700 disabled:opacity-50">
                                            <x-icon name="user-plus" class="h-4 w-4" />
                                            Adicionar
                                        </button>
                                    </div>
                                </div>
                                <div class="divide-y divide-slate-100 border border-slate-100">
                                    <template x-for="usuario in usuariosDoPerfil()" :key="'perfil-usuario-' + usuario.id">
                                        <div class="flex items-center justify-between gap-3 px-4 py-3">
                                            <div class="min-w-0">
                                                <p class="truncate text-sm font-semibold text-slate-900" x-text="usuario.nome"></p>
                                                <p class="truncate text-xs text-slate-500" x-text="usuario.email"></p>
                                            </div>
                                            <button type="button" @click="removerUsuarioPerfil(usuario)" :disabled="salvandoPerfilUsuario" class="inline-flex h-9 w-9 shrink-0 items-center justify-center border border-slate-200 text-red-600 hover:bg-red-50 disabled:opacity-50" title="Remover do perfil" aria-label="Remover do perfil">
                                                <x-icon name="user-minus" class="h-4 w-4" />
                                            </button>
                                        </div>
                                    </template>
                                    <div x-show="!usuariosDoPerfil().length" x-cloak class="px-4 py-5 text-sm text-slate-500">Nenhum usuário vinculado a este perfil.</div>
                                </div>
                            </div>

                            <div x-show="perfilForm.id" x-cloak class="bg-white p-5">
                                <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                    <div>
                                        <p class="text-sm font-semibold text-slate-900">Permissões do perfil</p>
                                        <p class="mt-1 text-xs text-slate-500">Marque as ações liberadas por padrão para este perfil.</p>
                                    </div>
                                    <button type="button" @click="salvarPermissoes" :disabled="!perfilSelecionadoId || salvandoPermissoes" class="bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white hover:bg-slate-800 disabled:opacity-50" x-text="salvandoPermissoes ? 'Salvando...' : 'Salvar permissões'"></button>
                                </div>
                                <div class="grid gap-4 xl:grid-cols-2">
                                    <template x-for="grupo in permissoes" :key="'modal-permissoes-' + grupo[0]?.categoria">
                                        <div class="border border-slate-100 p-4">
                                            <p class="mb-3 text-xs font-semibold uppercase tracking-wide text-slate-500" x-text="grupo[0]?.categoria"></p>
                                            <div class="space-y-2">
                                                <template x-for="permissao in grupo" :key="'modal-permissao-' + permissao.id">
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
                            </div>

                            <div x-show="!perfilForm.id" x-cloak class="border-t border-slate-200 bg-white p-5 text-sm text-slate-500">
                                Salve o perfil para liberar usuários e permissões.
                            </div>
                        </div>

                        <div class="shrink-0 border-t border-slate-200 bg-white p-5">
                            <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                                <button type="button" x-show="perfilForm.id" x-cloak @click="cancelarEdicaoPerfil" class="border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">Cancelar edição</button>
                                <button type="submit" :disabled="salvandoPerfil" class="bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-blue-700 disabled:opacity-50" x-text="salvandoPerfil ? 'Salvando...' : (perfilForm.id ? 'Salvar alterações' : 'Criar perfil')"></button>
                            </div>
                        </div>
                    </form>
                </div>
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
                    modalPerfilAberto: false,
                    isLoading: false,
                    salvandoUsuario: false,
                    salvandoPerfil: false,
                    salvandoPerfilUsuario: false,
                    salvandoPermissoes: false,
                    mensagem: '',
                    mensagemPerfil: '',
                    erro: false,
                    erroPerfil: false,
                    form: { id: null, nome: '', email: '', id_perfil: '', ativo: true, permissoes_especificas: [], password: '', password_confirmation: '' },
                    perfilForm: { id: null, nome: '' },
                    usuarioPerfilFormId: '',
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
                    abrirGerenciarPerfis() {
                        this.limparPerfilForm();
                        this.modalPerfilAberto = true;
                    },
                    fecharModalPerfil() {
                        if (this.salvandoPerfil) return;
                        this.modalPerfilAberto = false;
                        this.limparPerfilForm();
                    },
                    limparPerfilForm() {
                        this.perfilForm = { id: null, nome: '' };
                        this.perfilSelecionadoId = '';
                        this.permissoesSelecionadas = [];
                        this.usuarioPerfilFormId = '';
                        this.mensagemPerfil = '';
                        this.erroPerfil = false;
                    },
                    editarPerfil(perfil) {
                        this.perfilForm = { id: perfil.id, nome: perfil.nome };
                        this.perfilSelecionadoId = perfil.id;
                        this.usuarioPerfilFormId = '';
                        this.carregarPermissoesPerfil();
                        this.mensagemPerfil = '';
                        this.erroPerfil = false;
                    },
                    cancelarEdicaoPerfil() {
                        this.limparPerfilForm();
                    },
                    perfilEmUso(perfil) {
                        return Number(perfil.usuarios_count || 0) > 0;
                    },
                    usuariosDoPerfil() {
                        if (!this.perfilForm.id) return [];
                        return this.usuarios.filter((usuario) => Number(usuario.perfil?.id) === Number(this.perfilForm.id));
                    },
                    usuariosDisponiveisPerfil() {
                        if (!this.perfilForm.id) return [];
                        return this.usuarios.filter((usuario) => Number(usuario.perfil?.id) !== Number(this.perfilForm.id));
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
                        if (!this.validarStepAtual()) return;
                        if (this.step < 4) this.step += 1;
                    },
                    voltarStep() {
                        if (this.step > 1) this.step -= 1;
                    },
                    validarEmail(email) {
                        return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(String(email || '').trim());
                    },
                    validarSenha(password) {
                        return /[a-z]/.test(password)
                            && /[A-Z]/.test(password)
                            && /\d/.test(password)
                            && /[^A-Za-z0-9]/.test(password)
                            && String(password || '').length >= 8;
                    },
                    validarStepAtual() {
                        if (this.step === 1) {
                            if (!String(this.form.nome || '').trim() || String(this.form.nome || '').trim().length < 2) {
                                window.fiscalToast?.('warning', 'Informe o nome completo do usuário antes de continuar.', 'Nome obrigatório');
                                return false;
                            }
                            if (!this.validarEmail(this.form.email)) {
                                window.fiscalToast?.('warning', 'Informe um e-mail válido antes de continuar.', 'E-mail obrigatório');
                                return false;
                            }
                        }

                        if (this.step === 2 && !this.form.id_perfil) {
                            window.fiscalToast?.('warning', 'Selecione o perfil de acesso do usuário antes de continuar.', 'Perfil obrigatório');
                            return false;
                        }

                        if (this.step === 4) {
                            const password = String(this.form.password || '');
                            const confirmation = String(this.form.password_confirmation || '');

                            if (!this.form.id && !password) {
                                window.fiscalToast?.('warning', 'Informe uma senha temporária para o novo usuário.', 'Senha obrigatória');
                                return false;
                            }

                            if (password || confirmation) {
                                if (!this.validarSenha(password)) {
                                    window.fiscalToast?.('warning', 'Use no mínimo 8 caracteres, com maiúscula, minúscula, número e símbolo.', 'Senha fraca');
                                    return false;
                                }
                                if (password !== confirmation) {
                                    window.fiscalToast?.('warning', 'A confirmação da senha não confere.', 'Senha divergente');
                                    return false;
                                }
                            }
                        }

                        return true;
                    },
                    perfilSelecionadoNome() {
                        const perfil = this.perfis.find((item) => Number(item.id) === Number(this.form.id_perfil));
                        return perfil?.nome || 'Não selecionado';
                    },
                    async salvarUsuario() {
                        if (!this.validarStepAtual()) return;

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
                    async salvarPerfil() {
                        this.salvandoPerfil = true;
                        this.mensagemPerfil = '';
                        try {
                            const url = this.perfilForm.id ? '/api/perfis/' + this.perfilForm.id : '/api/perfis';
                            const method = this.perfilForm.id ? 'PUT' : 'POST';
                            const response = await fiscalFetch(url, {
                                method,
                                headers: this.headers(),
                                body: JSON.stringify({ nome: this.perfilForm.nome }),
                            });
                            const data = await this.parse(response);
                            this.erroPerfil = false;
                            this.mensagemPerfil = data.message;
                            window.fiscalToast?.('success', data.message, 'Perfis');
                            const perfilSalvoId = data.perfil?.id || data.perfil?.id_perfil || this.perfilForm.id;
                            this.limparPerfilForm();
                            await this.carregarTudo();
                            const perfilSalvo = this.perfis.find((item) => Number(item.id) === Number(perfilSalvoId));
                            if (perfilSalvo) {
                                this.editarPerfil(perfilSalvo);
                            }
                        } catch (error) {
                            this.erroPerfil = true;
                            this.mensagemPerfil = error.message;
                        } finally {
                            this.salvandoPerfil = false;
                        }
                    },
                    async excluirPerfil(perfil) {
                        if (this.perfilEmUso(perfil) || this.salvandoPerfil) return;
                        const confirmado = await window.fiscalConfirm?.({
                            title: 'Excluir perfil',
                            message: 'Deseja excluir o perfil "' + perfil.nome + '"? Esta ação não pode ser desfeita.',
                            confirmText: 'Excluir perfil',
                            cancelText: 'Cancelar',
                        });
                        if (!confirmado) return;

                        this.salvandoPerfil = true;
                        this.mensagemPerfil = '';
                        try {
                            const response = await fiscalFetch('/api/perfis/' + perfil.id, {
                                method: 'DELETE',
                                headers: this.headers(),
                            });
                            const data = await this.parse(response);
                            window.fiscalToast?.('success', data.message, 'Perfis');
                            if (Number(this.perfilSelecionadoId) === Number(perfil.id)) {
                                this.perfilSelecionadoId = '';
                                this.permissoesSelecionadas = [];
                            }
                            this.limparPerfilForm();
                            await this.carregarTudo();
                        } catch (error) {
                            this.erroPerfil = true;
                            this.mensagemPerfil = error.message;
                        } finally {
                            this.salvandoPerfil = false;
                        }
                    },
                    async adicionarUsuarioPerfil() {
                        if (!this.perfilForm.id || !this.usuarioPerfilFormId) return;

                        this.salvandoPerfilUsuario = true;
                        this.mensagemPerfil = '';
                        try {
                            const response = await fiscalFetch('/api/perfis/' + this.perfilForm.id + '/usuarios', {
                                method: 'POST',
                                headers: this.headers(),
                                body: JSON.stringify({ id_usuario: Number(this.usuarioPerfilFormId) }),
                            });
                            const data = await this.parse(response);
                            window.fiscalToast?.('success', data.message, 'Perfis');
                            this.usuarioPerfilFormId = '';
                            await this.carregarTudo();
                            const perfilAtual = this.perfis.find((item) => Number(item.id) === Number(this.perfilForm.id));
                            if (perfilAtual) this.editarPerfil(perfilAtual);
                        } catch (error) {
                            this.erroPerfil = true;
                            this.mensagemPerfil = error.message;
                        } finally {
                            this.salvandoPerfilUsuario = false;
                        }
                    },
                    async removerUsuarioPerfil(usuario) {
                        if (!this.perfilForm.id || this.salvandoPerfilUsuario) return;

                        this.salvandoPerfilUsuario = true;
                        this.mensagemPerfil = '';
                        try {
                            const response = await fiscalFetch('/api/perfis/' + this.perfilForm.id + '/usuarios/' + usuario.id, {
                                method: 'DELETE',
                                headers: this.headers(),
                            });
                            const data = await this.parse(response);
                            window.fiscalToast?.('success', data.message, 'Perfis');
                            await this.carregarTudo();
                            const perfilAtual = this.perfis.find((item) => Number(item.id) === Number(this.perfilForm.id));
                            if (perfilAtual) this.editarPerfil(perfilAtual);
                        } catch (error) {
                            this.erroPerfil = true;
                            this.mensagemPerfil = error.message;
                        } finally {
                            this.salvandoPerfilUsuario = false;
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
