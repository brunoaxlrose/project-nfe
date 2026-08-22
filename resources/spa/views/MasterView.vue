<script setup lang="ts">
import { computed, onMounted, reactive, ref } from 'vue'
import { Building2, CalendarClock, CirclePlus, CreditCard, Pencil, Power, PowerOff, Search, ShieldCheck, Trash2 } from '@lucide/vue'
import { api, apiError } from '../services/api'
import { useToastStore } from '../stores/toast'
import BaseModal from '../components/BaseModal.vue'
import EmptyState from '../components/EmptyState.vue'
import CurrencyInput from '../components/CurrencyInput.vue'
import ConfirmModal from '../components/ConfirmModal.vue'

type Plano = { id_plano: number; nome: string; slug: string; descricao?: string; valor_mensal: string | number; limite_usuarios?: number; duracao_dias?: number | null; modulos: string[]; ativo: boolean }
type Empresa = { id: number; razao_social: string; nome_fantasia?: string; cnpj: string; ativa: boolean; usuarios_count: number; assinatura?: { status: string; inicia_em: string; termina_em?: string; carencia_ate?: string; observacoes?: string; plano: Plano } }

const toast = useToastStore()
const loading = ref(true)
const saving = ref(false)
const query = ref('')
const empresas = ref<Empresa[]>([])
const planos = ref<Plano[]>([])
const modulos = ref<string[]>([])
const metricas = ref({ empresas: 0, ativas: 0, suspensas: 0, vencendo_30_dias: 0 })
const companyModal = ref(false)
const planModal = ref(false)
const selectedCompany = ref<Empresa | null>(null)
const selectedPlan = ref<Plano | null>(null)
const deletePlanOpen = ref(false)
const errors = ref<Record<string, string[]>>({})

const today = () => new Date().toISOString().slice(0, 10)
const plusDays = (days: number) => { const date = new Date(); date.setDate(date.getDate() + days); return date.toISOString().slice(0, 10) }
const plusDaysFrom = (value: string, days: number) => { const [year, month, day] = value.split('-').map(Number); const date = new Date(Date.UTC(year, month - 1, day)); date.setUTCDate(date.getUTCDate() + days); return date.toISOString().slice(0, 10) }
const toInputDate = (value?: string) => value ? value.slice(0, 10) : ''
const companyForm = reactive({ razao_social: '', nome_fantasia: '', cnpj: '', ativa: true, id_plano: null as number | null, status: 'teste', inicia_em: today(), termina_em: plusDays(30), carencia_ate: '', observacoes: '', admin_nome: '', admin_email: '', admin_password: '', admin_password_confirmation: '' })
const planForm = reactive({ nome: '', slug: '', descricao: '', valor_mensal: 0, limite_usuarios: null as number | null, duracao_dias: null as number | null, modulos: [] as string[], ativo: true })
const selectedCompanyPlan = computed(() => planos.value.find((plan) => plan.id_plano === companyForm.id_plano))

const filtered = computed(() => {
  const term = query.value.toLocaleLowerCase('pt-BR').replace(/\D/g, '') || query.value.toLocaleLowerCase('pt-BR')
  return empresas.value.filter((empresa) => !term || `${empresa.razao_social} ${empresa.nome_fantasia || ''} ${empresa.cnpj}`.toLocaleLowerCase('pt-BR').replace(/[^a-zà-ú0-9 ]/g, '').includes(term))
})
const money = (value: string | number) => Number(value || 0).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' })
const slugify = (value: string) => value.normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLocaleLowerCase('pt-BR').trim().replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, '')
const dateLabel = (value?: string) => value ? new Intl.DateTimeFormat('pt-BR').format(new Date(value)) : 'Sem vencimento'
const cnpjLabel = (value: string) => value.replace(/^(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})$/, '$1.$2.$3/$4-$5')
function maskCnpj(event: Event) {
  const digits = (event.target as HTMLInputElement).value.replace(/\D/g, '').slice(0, 14)
  companyForm.cnpj = digits.replace(/^(\d{2})(\d)/, '$1.$2').replace(/^(\d{2})\.(\d{3})(\d)/, '$1.$2.$3').replace(/\.(\d{3})(\d)/, '.$1/$2').replace(/(\d{4})(\d)/, '$1-$2')
}

function applySelectedPlanDuration() {
  const plan = planos.value.find((item) => item.id_plano === companyForm.id_plano)
  if (plan?.duracao_dias && companyForm.inicia_em) {
    companyForm.termina_em = plusDaysFrom(companyForm.inicia_em, plan.duracao_dias)
    companyForm.status = 'teste'
  }
}

async function load() {
  loading.value = true
  try {
    const { data } = await api.get('/master/overview')
    empresas.value = data.empresas; planos.value = data.planos; modulos.value = data.modulos; metricas.value = data.metricas
  } catch (error) { toast.show('error', apiError(error).message) } finally { loading.value = false }
}

function openCompany(company?: Empresa) {
  selectedCompany.value = company || null; errors.value = {}
  const subscription = company?.assinatura
  Object.assign(companyForm, {
    razao_social: company?.razao_social || '', nome_fantasia: company?.nome_fantasia || '', cnpj: company?.cnpj || '', ativa: company?.ativa ?? true,
    id_plano: subscription?.plano?.id_plano || planos.value.find((plan) => plan.ativo)?.id_plano || null,
    status: subscription?.status || 'teste', inicia_em: toInputDate(subscription?.inicia_em) || today(), termina_em: toInputDate(subscription?.termina_em) || plusDays(30),
    carencia_ate: toInputDate(subscription?.carencia_ate), observacoes: subscription?.observacoes || '', admin_nome: '', admin_email: '', admin_password: '', admin_password_confirmation: '',
  })
  if (!company) applySelectedPlanDuration()
  companyModal.value = true
}

async function saveCompany() {
  saving.value = true; errors.value = {}
  try {
    const payload: Record<string, unknown> = { ...companyForm, cnpj: companyForm.cnpj.replace(/\D/g, ''), termina_em: companyForm.termina_em || null, carencia_ate: companyForm.carencia_ate || null }
    if (selectedCompany.value) {
      delete payload.cnpj; delete payload.admin_nome; delete payload.admin_email; delete payload.admin_password; delete payload.admin_password_confirmation
      await api.put(`/master/empresas/${selectedCompany.value.id}`, payload)
    } else await api.post('/master/empresas', payload)
    toast.show('success', selectedCompany.value ? 'Liberação atualizada com sucesso.' : 'Empresa criada e liberada com sucesso.')
    companyModal.value = false; await load()
  } catch (error) { const parsed = apiError(error); errors.value = parsed.errors; toast.show('error', parsed.message) } finally { saving.value = false }
}

function openPlan(plan?: Plano) {
  selectedPlan.value = plan || null; errors.value = {}
  Object.assign(planForm, { nome: plan?.nome || '', slug: plan?.slug || '', descricao: plan?.descricao || '', valor_mensal: Number(plan?.valor_mensal || 0), limite_usuarios: plan?.limite_usuarios || null, duracao_dias: plan?.duracao_dias || null, modulos: [...(plan?.modulos || [])], ativo: plan?.ativo ?? true })
  planModal.value = true
}

async function savePlan() {
  saving.value = true; errors.value = {}
  try {
    const payload = { ...planForm, slug: slugify(planForm.slug || planForm.nome) }
    selectedPlan.value ? await api.put(`/master/planos/${selectedPlan.value.id_plano}`, payload) : await api.post('/master/planos', payload)
    toast.show('success', selectedPlan.value ? 'Plano atualizado com sucesso.' : 'Plano criado com sucesso.')
    planModal.value = false; await load()
  } catch (error) { const parsed = apiError(error); errors.value = parsed.errors; toast.show('error', parsed.message) } finally { saving.value = false }
}

async function togglePlan() {
  if (!selectedPlan.value) return
  saving.value = true
  try {
    const { data } = await api.patch(`/master/planos/${selectedPlan.value.id_plano}/status`)
    toast.show('success', data.message); planModal.value = false; await load()
  } catch (error) { toast.show('error', apiError(error).message) } finally { saving.value = false }
}

async function deletePlan() {
  if (!selectedPlan.value) return
  saving.value = true
  try {
    const { data } = await api.delete(`/master/planos/${selectedPlan.value.id_plano}`)
    toast.show('success', data.message); deletePlanOpen.value = false; planModal.value = false; await load()
  } catch (error) { toast.show('error', apiError(error).message); deletePlanOpen.value = false } finally { saving.value = false }
}

onMounted(load)
</script>

<template>
  <div class="master-page">
    <header class="page-heading"><div><span class="eyebrow">Administração SaaS</span><h1>Empresas e planos</h1><p>Controle liberações, vigências e módulos contratados sem acessar os dados operacionais dos clientes.</p></div><div class="heading-actions"><button class="button button--ghost" @click="openPlan()"><CreditCard :size="18" /> Novo plano</button><button class="button button--primary" @click="openCompany()"><CirclePlus :size="18" /> Nova empresa</button></div></header>

    <section class="metric-grid master-metrics">
      <article class="metric-card"><span class="metric-icon metric-icon--blue"><Building2 :size="21" /></span><div><small>Empresas cadastradas</small><strong>{{ metricas.empresas }}</strong><span>tenants na plataforma</span></div></article>
      <article class="metric-card"><span class="metric-icon metric-icon--green"><ShieldCheck :size="21" /></span><div><small>Acesso liberado</small><strong>{{ metricas.ativas }}</strong><span>assinaturas vigentes</span></div></article>
      <article class="metric-card"><span class="metric-icon metric-icon--amber"><CalendarClock :size="21" /></span><div><small>Vencem em 30 dias</small><strong>{{ metricas.vencendo_30_dias }}</strong><span>exigem acompanhamento</span></div></article>
      <article class="metric-card"><span class="metric-icon metric-icon--violet"><CreditCard :size="21" /></span><div><small>Planos configurados</small><strong>{{ planos.length }}</strong><span>{{ metricas.suspensas }} empresa(s) suspensa(s)</span></div></article>
    </section>

    <section class="panel master-plans"><header><div><h2>Planos comerciais</h2><p>Módulos e limites que poderão ser vinculados às empresas.</p></div></header><div class="plan-list"><button v-for="plan in planos" :key="plan.id_plano" :class="{ inactive: !plan.ativo }" @click="openPlan(plan)"><span><strong>{{ plan.nome }}</strong><small>{{ plan.duracao_dias ? `${plan.duracao_dias} dia(s) de acesso` : (plan.modulos.includes('*') ? 'Todos os módulos' : `${plan.modulos.length} módulos`) }}{{ !plan.ativo ? ' · Inativo' : '' }}</small></span><b>{{ money(plan.valor_mensal) }}<small>/mês</small></b><Pencil :size="15" /></button></div></section>

    <section class="panel data-panel master-companies">
      <div class="data-toolbar"><div class="search-box"><Search :size="18" /><input v-model="query" placeholder="Buscar empresa por razão social, nome fantasia ou CNPJ…"></div></div>
      <div class="table-wrap"><table class="data-table"><thead><tr><th>Empresa</th><th>Plano</th><th>Vigência</th><th>Usuários</th><th>Status</th><th class="actions-column">Ações</th></tr></thead><tbody v-if="loading"><tr v-for="n in 4" :key="n"><td v-for="x in 6" :key="x"><span class="skeleton" /></td></tr></tbody><tbody v-else><tr v-for="empresa in filtered" :key="empresa.id"><td><div class="company-cell"><strong>{{ empresa.nome_fantasia || empresa.razao_social }}</strong><span>{{ empresa.razao_social }}</span><small>{{ cnpjLabel(empresa.cnpj) }}</small></div></td><td><strong>{{ empresa.assinatura?.plano?.nome || 'Sem plano' }}</strong><small class="cell-note">{{ empresa.assinatura?.plano ? money(empresa.assinatura.plano.valor_mensal) : '—' }}</small></td><td><span>{{ dateLabel(empresa.assinatura?.inicia_em) }}</span><small class="cell-note">até {{ dateLabel(empresa.assinatura?.termina_em) }}</small></td><td>{{ empresa.usuarios_count }}</td><td><span class="subscription-status" :class="[empresa.assinatura?.status, { disabled: !empresa.ativa }]">{{ !empresa.ativa ? 'Suspensa' : (empresa.assinatura?.status || 'Sem assinatura') }}</span></td><td class="row-actions"><button title="Editar liberação" @click="openCompany(empresa)"><Pencil :size="16" /></button></td></tr></tbody></table><EmptyState v-if="!loading && !filtered.length" title="Nenhuma empresa encontrada" message="Ajuste a busca ou cadastre uma nova empresa." /></div>
    </section>

    <BaseModal :open="companyModal" :title="selectedCompany ? 'Editar empresa e liberação' : 'Cadastrar e liberar empresa'" description="A vigência é validada no login e em todas as APIs protegidas." wide @close="companyModal = false">
      <form id="master-company-form" class="modal-form-grid" @submit.prevent="saveCompany">
        <label><span>Razão social *</span><input v-model="companyForm.razao_social" placeholder="Razão social completa"><small v-if="errors.razao_social">{{ errors.razao_social[0] }}</small></label>
        <label><span>Nome fantasia</span><input v-model="companyForm.nome_fantasia" placeholder="Nome comercial"></label>
        <label v-if="!selectedCompany"><span>CNPJ *</span><input :value="companyForm.cnpj" inputmode="numeric" maxlength="18" placeholder="00.000.000/0000-00" @input="maskCnpj"><small v-if="errors.cnpj">{{ errors.cnpj[0] }}</small></label>
        <label><span>Plano *</span><select v-model="companyForm.id_plano" @change="applySelectedPlanDuration"><option :value="null" disabled>Selecione o plano</option><option v-for="plan in planos.filter(p => p.ativo || p.id_plano === companyForm.id_plano)" :key="plan.id_plano" :value="plan.id_plano">{{ plan.nome }} · {{ plan.duracao_dias ? `${plan.duracao_dias} dia(s)` : money(plan.valor_mensal) }}</option></select><small v-if="errors.id_plano">{{ errors.id_plano[0] }}</small></label>
        <label><span>Status da assinatura *</span><select v-model="companyForm.status"><option value="teste">Período de teste</option><option value="ativa">Ativa</option><option value="suspensa">Suspensa</option><option v-if="selectedCompany" value="cancelada">Cancelada</option></select></label>
        <label><span>Início *</span><input v-model="companyForm.inicia_em" type="date" @change="applySelectedPlanDuration"><small v-if="errors.inicia_em">{{ errors.inicia_em[0] }}</small></label>
        <label><span>Fim da vigência</span><input v-model="companyForm.termina_em" type="date" :disabled="Boolean(selectedCompanyPlan?.duracao_dias)"><small v-if="selectedCompanyPlan?.duracao_dias">Definido automaticamente pelo plano.</small><small v-if="errors.termina_em">{{ errors.termina_em[0] }}</small></label>
        <label><span>Carência até</span><input v-model="companyForm.carencia_ate" type="date"><small v-if="errors.carencia_ate">{{ errors.carencia_ate[0] }}</small></label>
        <label v-if="selectedCompany" class="checkbox-field"><input v-model="companyForm.ativa" type="checkbox"><span>Empresa ativa na plataforma</span></label>
        <template v-if="!selectedCompany"><div class="form-divider field-span-2">Administrador inicial da empresa</div><label><span>Nome *</span><input v-model="companyForm.admin_nome" placeholder="Nome do responsável"><small v-if="errors.admin_nome">{{ errors.admin_nome[0] }}</small></label><label><span>E-mail *</span><input v-model="companyForm.admin_email" type="email" placeholder="responsavel@empresa.com.br"><small v-if="errors.admin_email">{{ errors.admin_email[0] }}</small></label><label><span>Senha temporária *</span><input v-model="companyForm.admin_password" type="password" autocomplete="new-password" placeholder="Mínimo 8 caracteres"><small v-if="errors.admin_password">{{ errors.admin_password[0] }}</small></label><label><span>Confirmar senha *</span><input v-model="companyForm.admin_password_confirmation" type="password" autocomplete="new-password" placeholder="Repita a senha"></label></template>
        <label class="field-span-2"><span>Observações internas</span><textarea v-model="companyForm.observacoes" rows="3" placeholder="Condições comerciais, responsável, referência do contrato…"></textarea></label>
      </form>
      <template #footer><button class="button button--ghost" @click="companyModal = false">Cancelar</button><button form="master-company-form" class="button button--primary" :disabled="saving">{{ saving ? 'Salvando…' : 'Salvar e aplicar' }}</button></template>
    </BaseModal>

    <BaseModal :open="planModal" :title="selectedPlan ? 'Editar plano' : 'Novo plano'" description="Os módulos são verificados no backend, não apenas escondidos no menu." wide @close="planModal = false">
      <form id="master-plan-form" class="modal-form-grid" @submit.prevent="savePlan"><label><span>Nome *</span><input v-model="planForm.nome" placeholder="Ex.: Profissional"><small v-if="errors.nome">{{ errors.nome[0] }}</small></label><label><span>Valor mensal *</span><CurrencyInput v-model="planForm.valor_mensal" placeholder="0,00" /><small v-if="errors.valor_mensal">{{ errors.valor_mensal[0] }}</small></label><label><span>Limite de usuários</span><input v-model.number="planForm.limite_usuarios" type="number" min="1" placeholder="Sem limite"><small v-if="errors.limite_usuarios">{{ errors.limite_usuarios[0] }}</small></label><label><span>Duração padrão em dias</span><input v-model.number="planForm.duracao_dias" type="number" min="1" placeholder="Sem prazo automático"><small v-if="errors.duracao_dias">{{ errors.duracao_dias[0] }}</small></label><label class="field-span-2"><span>Descrição</span><textarea v-model="planForm.descricao" rows="2" placeholder="Resumo comercial do plano"></textarea></label><div class="field-span-2 plan-modules"><strong>Módulos liberados *</strong><label><input v-model="planForm.modulos" type="checkbox" value="*"><span>Todos os módulos atuais e futuros</span></label><label v-for="module in modulos" :key="module"><input v-model="planForm.modulos" type="checkbox" :value="module" :disabled="planForm.modulos.includes('*')"><span>{{ module }}</span></label><small v-if="errors.modulos">{{ errors.modulos[0] }}</small></div><label class="checkbox-field field-span-2"><input v-model="planForm.ativo" type="checkbox"><span>Plano disponível para novas liberações</span></label></form>
      <template #footer><button v-if="selectedPlan" class="button danger-outline" :disabled="saving" @click="deletePlanOpen = true"><Trash2 :size="16" /> Excluir</button><button v-if="selectedPlan" class="button button--ghost" :disabled="saving" @click="togglePlan"><Power v-if="!selectedPlan.ativo" :size="16" /><PowerOff v-else :size="16" />{{ selectedPlan.ativo ? 'Inativar' : 'Ativar' }}</button><button class="button button--ghost" @click="planModal = false">Cancelar</button><button form="master-plan-form" class="button button--primary" :disabled="saving">{{ saving ? 'Salvando…' : 'Salvar plano' }}</button></template>
    </BaseModal>
    <ConfirmModal :open="deletePlanOpen" :loading="saving" title="Excluir plano?" :message="`O plano “${selectedPlan?.nome || ''}” será excluído. Planos que já foram utilizados por empresas somente podem ser inativados.`" @close="deletePlanOpen = false" @confirm="deletePlan" />
  </div>
</template>
