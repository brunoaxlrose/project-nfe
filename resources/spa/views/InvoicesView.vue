<script setup lang="ts">
import { onBeforeUnmount, onMounted, reactive, ref, watch } from 'vue'
import { Ban, ChevronLeft, ChevronRight, Copy, Download, FileCode2, FilePlus2, Files, FileText, MoreVertical, PencilLine, RefreshCw, Search, Send, Trash2 } from '@lucide/vue'
import { api, apiError } from '../services/api'
import { useToastStore } from '../stores/toast'
import { useAuthStore } from '../stores/auth'
import EmptyState from '../components/EmptyState.vue'
import BaseModal from '../components/BaseModal.vue'
import ConfirmModal from '../components/ConfirmModal.vue'

type FiscalAction = 'cancelar' | 'cce'

const toast = useToastStore()
const auth = useAuthStore()
const rows = ref<Record<string, any>[]>([])
const loading = ref(true)
const search = ref('')
const status = ref('')
const page = ref(1)
const activeMenu = ref<number | string | null>(null)
const sending = ref(false)
const deleting = ref(false)
const pendingRow = ref<Record<string, any> | null>(null)
const deleteRow = ref<Record<string, any> | null>(null)
const actionModal = reactive<{ open: boolean; type: FiscalAction; row: Record<string, any> | null; text: string; error: string; loading: boolean }>({ open: false, type: 'cancelar', row: null, text: '', error: '', loading: false })
const meta = reactive({ current_page: 1, last_page: 1, total: 0, from: 0, to: 0 })
let debounce: number | undefined

const id = (row: Record<string, any>) => row.id || row.id_nfe
const money = (value: unknown) => new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL', minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(Number(value || 0))
const date = (value: string) => value ? new Intl.DateTimeFormat('pt-BR', { dateStyle: 'short', timeStyle: 'short' }).format(new Date(value)) : '—'
const statusLabel = (value: string) => ({ rascunho: 'Pendente', pendente: 'Pendente', gerando: 'Processando', assinado: 'Processando', aguardando_retorno: 'Processando', autorizada: 'Autorizada', autorizado: 'Autorizada', simulada: 'Simulada', cancelada: 'Cancelada', rejeitada: 'Rejeitada', erro: 'Erro' }[value?.toLowerCase()] || value || 'Pendente')
const statusClass = (value: string) => ({ rascunho: 'pendente', gerando: 'processando', assinado: 'processando', aguardando_retorno: 'processando' }[value?.toLowerCase()] || value?.toLowerCase() || 'pendente')
const isAuthorized = (row: Record<string, any>) => ['autorizada', 'autorizado', 'emitida'].includes(String(row.status).toLowerCase())
const isPending = (row: Record<string, any>) => String(row.status).toLowerCase() === 'rascunho'
const rejectionMessage = (row: Record<string, any>) => {
  if (Number(row.cstat) === 302) return 'Destinatário irregular na SEFAZ. Confira CNPJ e Inscrição Estadual e valide a situação fiscal no SINTEGRA/SEFAZ.'
  if (Number(row.cstat) === 518) return 'CFOP incompatível: esta é uma NF-e de saída, mas existe item com CFOP de entrada.'
  const technical = String(row.xmotivo || '')
  const reason = technical.toLocaleLowerCase('pt-BR')
  if (reason.includes('duplicidade')) return 'Esta NF-e já foi enviada. Consulte a chave e o protocolo anteriores antes de tentar novamente.'
  if (reason.includes('inscrição estadual') || reason.includes('inscricao estadual')) return 'Inscrição Estadual não aceita. Confira o número, a UF e a situação cadastral indicada.'
  if (reason.includes('cnpj') || reason.includes('cpf')) return 'CNPJ ou CPF não aceito. Confira o documento e a situação cadastral do destinatário.'
  if (reason.includes('ncm')) return 'NCM inválido ou não vigente. Revise o cadastro do produto indicado.'
  if (reason.includes('csosn') || reason.includes('cst')) return 'Tributação incompatível. Revise o CSOSN/CST e a natureza da operação.'
  if (reason.includes('total') || reason.includes('desconto') || reason.includes('base de cálculo') || reason.includes('base de calculo')) return 'Totais inconsistentes. Revise itens, descontos e impostos indicados pela SEFAZ.'
  return technical
}

async function load() {
  loading.value = true
  activeMenu.value = null
  try {
    const { data } = await api.get('/faturamento/notas', { params: { page: page.value, per_page: 15, busca: search.value || undefined, status: status.value || undefined } })
    rows.value = data.data || []
    Object.assign(meta, { current_page: data.current_page || 1, last_page: data.last_page || 1, total: data.total || 0, from: data.from || 0, to: data.to || 0 })
  } catch (error) { toast.show('error', apiError(error).message) } finally { loading.value = false }
}

function changePage(target: number) { if (target < 1 || target > meta.last_page) return; page.value = target; load() }
function toggleMenu(row: Record<string, any>, event: Event) { event.stopPropagation(); activeMenu.value = activeMenu.value === id(row) ? null : id(row) }
function closeMenu() { activeMenu.value = null }

async function download(row: Record<string, any>, type: 'pdf' | 'xml' | 'zip') {
  activeMenu.value = null
  const isDraftPreview = isPending(row) && type === 'pdf'
  const previewWindow = isDraftPreview ? window.open('about:blank', '_blank') : null
  if (previewWindow) {
    previewWindow.document.title = 'Carregando prévia DANFE…'
    previewWindow.document.body.innerHTML = '<main style="min-height:100vh;display:grid;place-items:center;font:14px system-ui;color:#475569">Gerando prévia DANFE sem valor fiscal…</main>'
    previewWindow.opener = null
  }
  try {
    const response = await api.get(`/faturamento/notas/${id(row)}/download`, { params: { tipo: type }, responseType: 'blob' })
    const url = URL.createObjectURL(response.data)
    if (isDraftPreview) {
      if (previewWindow) previewWindow.location.replace(url)
      else {
        const link = document.createElement('a')
        link.href = url
        link.target = '_blank'
        link.rel = 'noopener'
        link.click()
      }
      window.setTimeout(() => URL.revokeObjectURL(url), 60000)
      return
    }
    const link = document.createElement('a')
    link.href = url
    link.download = `${type === 'pdf' ? 'danfe' : 'nfe'}-${row.numero || id(row)}.${type}`
    link.click()
    URL.revokeObjectURL(url)
  } catch (error) {
    previewWindow?.close()
    toast.show('warning', apiError(error).message, 'Documento indisponível')
  }
}

function openFiscalAction(type: FiscalAction, row: Record<string, any>) {
  activeMenu.value = null
  Object.assign(actionModal, { open: true, type, row, text: '', error: '', loading: false })
}
function closeFiscalAction() { if (!actionModal.loading) Object.assign(actionModal, { open: false, row: null, text: '', error: '' }) }

async function submitFiscalAction() {
  if (!actionModal.row || actionModal.text.trim().length < 15) { actionModal.error = 'Informe pelo menos 15 caracteres.'; return }
  actionModal.loading = true; actionModal.error = ''
  try {
    const endpoint = actionModal.type === 'cce' ? 'cce' : 'cancelar'
    const { data } = await api.post(`/faturamento/notas/${id(actionModal.row)}/${endpoint}`, { justificativa: actionModal.text.trim() })
    toast.show('success', data.message || 'Operação fiscal concluída.')
    Object.assign(actionModal, { open: false, row: null, text: '', error: '' }); await load()
  } catch (error) { const parsed = apiError(error); actionModal.error = parsed.message; toast.show('error', parsed.message) } finally { actionModal.loading = false }
}

function requestSend(row: Record<string, any>) { activeMenu.value = null; pendingRow.value = row }
async function sendPending() {
  if (!pendingRow.value) return
  sending.value = true
  try {
    const { data } = await api.post(`/nfe/${id(pendingRow.value)}/emitir`, {})
    toast.show('success', data.status === 'autorizada' ? 'NF-e autorizada pela SEFAZ.' : 'NF-e enviada para processamento.')
    pendingRow.value = null; await load()
  } catch (error) { toast.show('error', apiError(error).message, 'Não foi possível enviar a NF-e') } finally { sending.value = false }
}

async function cloneInvoice(row: Record<string, any>) {
  activeMenu.value = null
  try { await api.post(`/faturamento/notas/${id(row)}/clonar`); toast.show('success', 'Uma nova nota pendente foi criada com os mesmos dados.', 'Nota clonada'); await load() }
  catch (error) { toast.show('error', apiError(error).message, 'Clonagem indisponível') }
}

function requestDelete(row: Record<string, any>) { activeMenu.value = null; deleteRow.value = row }
async function deletePending() {
  if (!deleteRow.value) return
  deleting.value = true
  try { await api.delete(`/faturamento/notas/${id(deleteRow.value)}`); toast.show('success', 'Nota pendente excluída com sucesso.'); deleteRow.value = null; await load() }
  catch (error) { toast.show('error', apiError(error).message, 'Erro ao excluir') } finally { deleting.value = false }
}

watch([search, status], () => { clearTimeout(debounce); debounce = window.setTimeout(() => { page.value = 1; load() }, 350) })
onMounted(() => { load(); document.addEventListener('click', closeMenu) })
onBeforeUnmount(() => { window.clearTimeout(debounce); document.removeEventListener('click', closeMenu) })
</script>

<template>
  <div>
    <header class="page-heading"><div><span class="eyebrow">Documentos fiscais</span><h1>Notas fiscais</h1><p>Consulte emissões, retornos da SEFAZ e documentos disponíveis.</p></div><a href="/dashboard/nfe/nova" class="button button--primary"><FilePlus2 :size="18" /> Emitir nova NF-e</a></header>
    <section class="panel data-panel invoice-list-panel">
      <div class="data-toolbar"><div class="search-box"><Search :size="18" /><input v-model="search" placeholder="Buscar por número, destinatário ou documento…"></div><select v-model="status" class="standalone-select"><option value="">Todos os status</option><option value="rascunho">Pendentes</option><option value="autorizada">Autorizadas</option><option value="aguardando_retorno">Processando</option><option value="simulada">Simuladas</option><option value="cancelada">Canceladas</option><option value="rejeitada">Rejeitadas</option><option value="erro">Com erro</option></select><button class="icon-button" title="Atualizar" @click="load"><RefreshCw :size="18" /></button></div>
      <div class="table-wrap"><table class="data-table"><thead><tr><th>Número</th><th>Destinatário</th><th>Emissão</th><th>Valor</th><th>Status</th><th class="actions-column">Ações</th></tr></thead>
        <tbody v-if="loading"><tr v-for="n in 5" :key="n"><td v-for="x in 6" :key="x"><span class="skeleton" /></td></tr></tbody>
        <tbody v-else><tr v-for="row in rows" :key="id(row)"><td><strong>{{ row.numero || 'Pendente' }}</strong><small class="cell-subtitle">Série {{ row.serie || '1' }}</small></td><td><strong>{{ row.destinatario_nome || row.cliente?.razao_social || row.razao_social || 'Não informado' }}</strong><small class="cell-subtitle">{{ row.destinatario_documento || row.documento || '' }}</small></td><td>{{ date(row.created_at || row.data_emissao) }}</td><td class="money-cell">{{ money(row.valor_total || row.total) }}</td><td><span class="invoice-status" :class="`status-${statusClass(row.status)}`"><i />{{ statusLabel(row.status) }}</span><small v-if="['rejeitada', 'erro'].includes(row.status) && rejectionMessage(row)" class="status-detail" :title="row.xmotivo">{{ rejectionMessage(row) }}</small></td><td class="invoice-actions"><button class="more-button" title="Mais ações" aria-label="Abrir ações da nota" :aria-expanded="activeMenu === id(row)" @click="toggleMenu(row, $event)"><MoreVertical :size="18" /></button><div v-if="activeMenu === id(row)" class="invoice-actions-menu" @click.stop><button v-if="auth.can('nfe.baixar')" @click="download(row, 'pdf')"><FileText :size="16" />{{ isPending(row) ? 'Visualizar prévia DANFE' : 'Gerar PDF DANFE / imprimir' }}</button><button v-if="auth.can('nfe.baixar')" @click="download(row, 'xml')"><FileCode2 :size="16" />Exportar XML</button><button v-if="auth.can('nfe.baixar')" @click="download(row, 'zip')"><Download :size="16" />Baixar documentos (.zip)</button><button v-if="auth.can('nfe.cancelar') && isAuthorized(row)" class="danger-menu-action" @click="openFiscalAction('cancelar', row)"><Ban :size="16" />Cancelar NF-e na SEFAZ</button><button v-if="isPending(row) && auth.can('nfe.criar')" class="primary-menu-action" @click="requestSend(row)"><Send :size="16" />Enviar para a SEFAZ</button><button v-if="isPending(row) && auth.can('nfe.cancelar')" class="danger-menu-action" @click="requestDelete(row)"><Trash2 :size="16" />Excluir pendente</button><button v-if="auth.can('nfe.cce')" :disabled="!isAuthorized(row)" @click="openFiscalAction('cce', row)"><PencilLine :size="16" />Carta de correção (CC-e)</button><button v-if="auth.can('nfe.clonar')" @click="cloneInvoice(row)"><Copy :size="16" />Clonar nota</button></div></td></tr></tbody>
      </table><EmptyState v-if="!loading && !rows.length" title="Nenhuma nota encontrada" message="Quando você emitir uma NF-e, ela aparecerá aqui."><a href="/dashboard/nfe/nova" class="button button--primary button--compact"><Files :size="16" /> Emitir primeira nota</a></EmptyState></div>
      <footer v-if="meta.total" class="pagination"><span>Mostrando {{ meta.from }}–{{ meta.to }} de {{ meta.total }}</span><div><button :disabled="meta.current_page <= 1" @click="changePage(meta.current_page - 1)"><ChevronLeft :size="17" /></button><span>Página {{ meta.current_page }} de {{ meta.last_page }}</span><button :disabled="meta.current_page >= meta.last_page" @click="changePage(meta.current_page + 1)"><ChevronRight :size="17" /></button></div></footer>
    </section>

    <BaseModal :open="actionModal.open" :title="actionModal.type === 'cce' ? 'Carta de correção (CC-e)' : 'Cancelar NF-e na SEFAZ'" :description="actionModal.row ? `NF-e nº ${actionModal.row.numero} · Série ${actionModal.row.serie}` : ''" @close="closeFiscalAction"><div class="fiscal-action-form"><p>{{ actionModal.type === 'cce' ? 'Descreva a correção permitida pela legislação. Valores, destinatário e data de emissão não podem ser alterados.' : 'Informe uma justificativa com pelo menos 15 caracteres. O cancelamento será transmitido à SEFAZ.' }}</p><label><span>{{ actionModal.type === 'cce' ? 'Texto da correção' : 'Justificativa do cancelamento' }}</span><textarea v-model="actionModal.text" rows="5" :placeholder="actionModal.type === 'cce' ? 'Descreva claramente a correção fiscal…' : 'Informe o motivo do cancelamento…'"></textarea><small v-if="actionModal.error">{{ actionModal.error }}</small></label></div><template #footer><button class="button button--ghost" :disabled="actionModal.loading" @click="closeFiscalAction">Voltar</button><button class="button" :class="actionModal.type === 'cancelar' ? 'button--danger' : 'button--primary'" :disabled="actionModal.loading" @click="submitFiscalAction">{{ actionModal.loading ? 'Enviando…' : actionModal.type === 'cce' ? 'Enviar CC-e' : 'Transmitir cancelamento' }}</button></template></BaseModal>

    <BaseModal :open="Boolean(pendingRow)" title="Enviar NF-e para a SEFAZ?" :description="pendingRow ? `NF-e nº ${pendingRow.numero} · Série ${pendingRow.serie}` : ''" @close="!sending && (pendingRow = null)"><div class="confirm-message"><span><Send :size="21" /></span><p>Esta nota está pendente e ainda não tem validade fiscal. Ao confirmar, ela será enviada para autorização na SEFAZ.</p></div><template #footer><button class="button button--ghost" :disabled="sending" @click="pendingRow = null">Revisar depois</button><button class="button button--primary" :disabled="sending" @click="sendPending">{{ sending ? 'Enviando…' : 'Enviar para a SEFAZ' }}</button></template></BaseModal>

    <ConfirmModal :open="Boolean(deleteRow)" :loading="deleting" title="Excluir nota pendente?" :message="`A NF-e nº ${deleteRow?.numero || '—'} será removida. Essa ação não poderá ser desfeita.`" @close="deleteRow = null" @confirm="deletePending" />
  </div>
</template>
