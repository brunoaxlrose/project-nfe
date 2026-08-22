<script setup lang="ts">
import { computed, onMounted, reactive, ref } from 'vue'
import { CirclePlus, KeyRound, Pencil, Search, ShieldCheck, UserCog, UsersRound } from '@lucide/vue'
import { api, apiError } from '../services/api'
import { useToastStore } from '../stores/toast'
import BaseModal from '../components/BaseModal.vue'
import EmptyState from '../components/EmptyState.vue'
import { useAuthStore } from '../stores/auth'

const toast = useToastStore()
const auth = useAuthStore()
const users = ref<Record<string, any>[]>([])
const profiles = ref<Record<string, any>[]>([])
const permissionGroups = ref<Record<string, any>[][]>([])
const loading = ref(true)
const saving = ref(false)
const modalOpen = ref(false)
const query = ref('')
const errors = ref<Record<string, string[]>>({})
const selected = ref<Record<string, any> | null>(null)
const planUsage = ref<{ usuarios: number; limite: number | null; nome: string }>({ usuarios: 0, limite: null, nome: '' })
const planLoaded = ref(false)
const form = reactive({ nome: '', email: '', id_perfil: null as number | null, ativo: true, copiar_minhas_permissoes: true, permissoes_especificas: [] as number[], password: '', password_confirmation: '' })
const filtered = computed(() => { const term = query.value.toLocaleLowerCase('pt-BR'); return users.value.filter((user) => !term || `${user.nome} ${user.email} ${user.perfil?.nome || ''}`.toLocaleLowerCase('pt-BR').includes(term)) })
const permissionCount = computed(() => permissionGroups.value.reduce((sum, group) => sum + group.length, 0))
const canCreateUser = computed(() => planLoaded.value && (planUsage.value.limite === null || planUsage.value.usuarios < planUsage.value.limite))
const availableSeats = computed(() => planUsage.value.limite === null ? null : Math.max(0, planUsage.value.limite - planUsage.value.usuarios))

async function load() {
  planLoaded.value = false
  loading.value = true
  try {
    const [userResponse, profileResponse] = await Promise.all([api.get('/usuarios'), api.get('/perfis')])
    users.value = userResponse.data
    profiles.value = profileResponse.data.perfis
    permissionGroups.value = profileResponse.data.permissoes
    try {
      const planResponse = await api.get('/minha-assinatura')
      planUsage.value = { usuarios: Number(planResponse.data.uso?.usuarios || 0), limite: planResponse.data.plano?.limite_usuarios ?? null, nome: planResponse.data.plano?.nome || '' }
      planLoaded.value = true
    } catch (error) { toast.show('warning', apiError(error).message, 'Limite do plano') }
  } catch (error) { toast.show('error', apiError(error).message) } finally { loading.value = false }
}
function open(row?: Record<string, any>) {
  if (!row && !canCreateUser.value) return
  selected.value = row || null; errors.value = {}
  Object.assign(form, { nome: row?.nome || '', email: row?.email || '', id_perfil: row?.perfil?.id || auth.user?.perfil_id || auth.user?.id_perfil || null, ativo: row?.ativo ?? true, copiar_minhas_permissoes: !row, permissoes_especificas: (row?.permissoes_especificas || []).map((item: any) => item.id), password: '', password_confirmation: '' })
  modalOpen.value = true
}
async function save() {
  saving.value = true; errors.value = {}
  try {
    const payload: Record<string, any> = { ...form }
    if (selected.value && !payload.password) { delete payload.password; delete payload.password_confirmation }
    const response = selected.value ? await api.put(`/usuarios/${selected.value.id}`, payload) : await api.post('/usuarios', payload)
    toast.show('success', response.data.message); modalOpen.value = false; await load()
  } catch (error) { const parsed = apiError(error); errors.value = parsed.errors; toast.show('error', parsed.message) } finally { saving.value = false }
}
onMounted(load)
</script>

<template>
  <div>
    <header class="page-heading"><div><span class="eyebrow">Administração</span><h1>Usuários e acessos</h1><p>Gerencie sua equipe respeitando a quantidade de acessos liberada no plano.</p></div><button class="button button--primary" :disabled="!canCreateUser" :title="!canCreateUser ? 'Limite de usuários do plano atingido' : undefined" @click="open()"><CirclePlus :size="18" /> Novo usuário</button></header>
    <section class="metric-grid users-metrics"><article class="metric-card"><span class="metric-icon metric-icon--blue"><UsersRound :size="21" /></span><div><small>Usuários cadastrados</small><strong>{{ users.length }}</strong><span>membros no workspace</span></div></article><article class="metric-card"><span class="metric-icon metric-icon--violet"><ShieldCheck :size="21" /></span><div><small>Perfis de acesso</small><strong>{{ profiles.length }}</strong><span>grupos configurados</span></div></article><article class="metric-card"><span class="metric-icon metric-icon--green"><KeyRound :size="21" /></span><div><small>Permissões disponíveis</small><strong>{{ permissionCount }}</strong><span>ações controladas</span></div></article></section>
    <div v-if="planUsage.limite !== null" class="seat-summary" :class="{ full: !canCreateUser }"><UsersRound :size="18" /><div><strong>{{ planUsage.usuarios }} de {{ planUsage.limite }} acessos utilizados</strong><span v-if="canCreateUser">{{ availableSeats }} acesso(s) ainda disponível(is) no plano {{ planUsage.nome }}.</span><span v-else>O limite do plano foi atingido. Altere o plano para cadastrar outro acesso ativo.</span></div></div>
    <section class="panel data-panel users-table-panel">
      <div class="data-toolbar"><div class="search-box"><Search :size="18" /><input v-model="query" placeholder="Buscar por nome, e-mail ou perfil…"></div></div>
      <div class="table-wrap"><table class="data-table"><thead><tr><th>Usuário</th><th>Perfil</th><th>Permissões extras</th><th>Status</th><th class="actions-column">Ações</th></tr></thead><tbody v-if="loading"><tr v-for="n in 4" :key="n"><td v-for="x in 5" :key="x"><span class="skeleton" /></td></tr></tbody><tbody v-else><tr v-for="user in filtered" :key="user.id"><td><div class="user-cell"><span>{{ user.nome.split(' ').slice(0,2).map((part: string) => part[0]).join('') }}</span><div><strong>{{ user.nome }}</strong><small>{{ user.email }}</small></div></div></td><td><span class="profile-badge"><UserCog :size="14" />{{ user.perfil?.nome || 'Sem perfil' }}</span></td><td>{{ user.permissoes_especificas?.length || 0 }}</td><td><span class="table-status" :class="{ 'is-inactive': !user.ativo }"><i />{{ user.ativo ? 'Ativo' : 'Inativo' }}</span></td><td class="row-actions"><button title="Editar usuário" @click="open(user)"><Pencil :size="16" /></button></td></tr></tbody></table><EmptyState v-if="!loading && !filtered.length" title="Nenhum usuário encontrado" message="Ajuste a busca ou convide um novo membro." /></div>
    </section>
    <BaseModal :open="modalOpen" :title="selected ? 'Editar usuário' : 'Novo usuário'" description="Defina o perfil principal e, se necessário, acessos adicionais." wide @close="modalOpen = false">
      <form id="user-form" class="modal-form-grid" @submit.prevent="save">
        <label><span>Nome completo *</span><input v-model="form.nome" autocomplete="name" placeholder="Ex.: Bruno Oliveira"><small v-if="errors.nome">{{ errors.nome[0] }}</small></label>
        <label><span>E-mail *</span><input v-model="form.email" type="email" autocomplete="email" placeholder="usuario@empresa.com.br"><small v-if="errors.email">{{ errors.email[0] }}</small></label>
        <label v-if="!selected" class="checkbox-field field-span-2 copy-access-field"><input v-model="form.copiar_minhas_permissoes" type="checkbox"><span><strong>Usar os mesmos acessos do meu usuário</strong><small>O novo usuário receberá seu perfil e suas permissões adicionais, sempre limitado aos módulos do plano.</small></span></label>
        <label v-if="selected || !form.copiar_minhas_permissoes" class="field-span-2"><span>Perfil de acesso *</span><select v-model="form.id_perfil"><option :value="null" disabled>Selecione um perfil</option><option v-for="profile in profiles" :key="profile.id" :value="profile.id">{{ profile.nome }}</option></select><small v-if="errors.id_perfil">{{ errors.id_perfil[0] }}</small></label>
        <label><span>{{ selected ? 'Nova senha (opcional)' : 'Senha temporária *' }}</span><input v-model="form.password" type="password" autocomplete="new-password" placeholder="Mínimo 8 caracteres"><small v-if="errors.password">{{ errors.password[0] }}</small></label>
        <label><span>Confirme a senha</span><input v-model="form.password_confirmation" type="password" autocomplete="new-password" placeholder="Repita a senha"></label>
        <label class="checkbox-field field-span-2"><input v-model="form.ativo" type="checkbox"><span>Usuário ativo e autorizado a entrar</span></label>
      </form>
      <template #footer><button class="button button--ghost" @click="modalOpen = false">Cancelar</button><button form="user-form" class="button button--primary" :disabled="saving">{{ saving ? 'Salvando…' : 'Salvar usuário' }}</button></template>
    </BaseModal>
  </div>
</template>
