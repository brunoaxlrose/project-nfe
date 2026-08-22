<script setup lang="ts">
import { computed, onMounted, reactive, ref, watch } from 'vue'
import { ChevronLeft, ChevronRight, CirclePlus, Pencil, Search, SlidersHorizontal, Trash2 } from '@lucide/vue'
import { api, apiError } from '../services/api'
import { useToastStore } from '../stores/toast'
import BaseModal from '../components/BaseModal.vue'
import ConfirmModal from '../components/ConfirmModal.vue'
import EmptyState from '../components/EmptyState.vue'
import CurrencyInput from '../components/CurrencyInput.vue'

type Entity = 'clientes' | 'fornecedores' | 'produtos' | 'naturezas'
type Field = { key: string; label: string; type?: 'text' | 'number' | 'currency' | 'select' | 'textarea' | 'checkbox'; required?: boolean; placeholder?: string; options?: { label: string; value: string }[]; span?: 2 }
type Column = { key: string; label: string; format?: 'document' | 'currency' | 'status' }
type Config = { title: string; singular: string; description: string; endpoint: string; permission: string; nameKey: string; fields: Field[]; columns: Column[]; defaults: Record<string, unknown> }

const props = defineProps<{ entity: Entity }>()
const toast = useToastStore()
const configs: Record<Entity, Config> = {
  clientes: { title: 'Clientes', singular: 'cliente', description: 'Gerencie os destinatários das suas operações fiscais.', endpoint: '/clientes', permission: 'clientes', nameKey: 'razao_social', defaults: { ativo: true }, columns: [{ key: 'razao_social', label: 'Razão social' }, { key: 'documento', label: 'CPF / CNPJ', format: 'document' }, { key: 'cidade', label: 'Cidade / UF' }, { key: 'ativo', label: 'Status', format: 'status' }], fields: [
    { key: 'razao_social', label: 'Razão social / Nome', required: true, span: 2 }, { key: 'documento', label: 'CPF ou CNPJ', required: true }, { key: 'inscricao_estadual', label: 'Inscrição estadual' }, { key: 'cep', label: 'CEP' }, { key: 'logradouro', label: 'Logradouro', span: 2 }, { key: 'numero', label: 'Número' }, { key: 'complemento', label: 'Complemento' }, { key: 'bairro', label: 'Bairro' }, { key: 'cidade', label: 'Cidade' }, { key: 'codigo_ibge', label: 'Código IBGE' }, { key: 'uf', label: 'UF' }, { key: 'ativo', label: 'Cadastro ativo', type: 'checkbox', span: 2 },
  ] },
  fornecedores: { title: 'Fornecedores', singular: 'fornecedor', description: 'Centralize parceiros e dados cadastrais de compra.', endpoint: '/fornecedores', permission: 'fornecedores', nameKey: 'nome_razao_social', defaults: { ativo: true }, columns: [{ key: 'nome_razao_social', label: 'Razão social' }, { key: 'documento', label: 'CPF / CNPJ', format: 'document' }, { key: 'municipio', label: 'Cidade / UF' }, { key: 'ativo', label: 'Status', format: 'status' }], fields: [
    { key: 'nome_razao_social', label: 'Razão social / Nome', required: true, span: 2 }, { key: 'documento', label: 'CPF ou CNPJ', required: true }, { key: 'inscricao_estadual', label: 'Inscrição estadual' }, { key: 'cep', label: 'CEP' }, { key: 'logradouro', label: 'Logradouro', span: 2 }, { key: 'numero', label: 'Número' }, { key: 'complemento', label: 'Complemento' }, { key: 'bairro', label: 'Bairro' }, { key: 'municipio', label: 'Município' }, { key: 'codigo_municipio_ibge', label: 'Código IBGE' }, { key: 'uf', label: 'UF' }, { key: 'ativo', label: 'Cadastro ativo', type: 'checkbox', span: 2 },
  ] },
  produtos: { title: 'Produtos', singular: 'produto', description: 'Mantenha preços e classificações fiscais consistentes.', endpoint: '/produtos', permission: 'produtos', nameKey: 'descricao', defaults: { ativo: true, unidade: 'UN', valor_unitario: 0 }, columns: [{ key: 'codigo', label: 'Código' }, { key: 'descricao', label: 'Descrição' }, { key: 'ncm', label: 'NCM' }, { key: 'valor_unitario', label: 'Valor', format: 'currency' }, { key: 'ativo', label: 'Status', format: 'status' }], fields: [
    { key: 'codigo', label: 'Código interno', required: true, placeholder: 'Ex.: PROD-001' }, { key: 'descricao', label: 'Descrição', required: true, span: 2, placeholder: 'Ex.: Camiseta algodão branca' }, { key: 'ncm', label: 'NCM', required: true, placeholder: '8 dígitos' }, { key: 'valor_unitario', label: 'Valor unitário', type: 'currency', required: true, placeholder: '0,00' }, { key: 'unidade', label: 'Unidade', required: true, placeholder: 'Ex.: UN, PC, KG' }, { key: 'cfop', label: 'CFOP padrão', placeholder: '4 dígitos' }, { key: 'csosn', label: 'CSOSN', placeholder: 'Ex.: 0400' }, { key: 'cst', label: 'CST', placeholder: 'Ex.: 00' }, { key: 'ativo', label: 'Produto ativo', type: 'checkbox', span: 2 },
  ] },
  naturezas: { title: 'Naturezas de operação', singular: 'natureza', description: 'Configure regras fiscais reutilizáveis para emissão.', endpoint: '/naturezas-operacao', permission: 'naturezas', nameKey: 'nome', defaults: { ativa: true, tipo_movimento: 'Saída', calcula_impostos: false, calcula_icms: false, calcula_ipi: false, calcula_pis: false, calcula_cofins: false }, columns: [{ key: 'nome', label: 'Natureza' }, { key: 'tipo_movimento', label: 'Movimento' }, { key: 'cfop_padrao', label: 'CFOP' }, { key: 'ativa', label: 'Status', format: 'status' }], fields: [
    { key: 'nome', label: 'Nome da operação', required: true, span: 2 }, { key: 'tipo_movimento', label: 'Tipo de movimento', type: 'select', required: true, options: [{ label: 'Saída', value: 'Saída' }, { label: 'Entrada', value: 'Entrada' }] }, { key: 'cfop_padrao', label: 'CFOP padrão', required: true }, { key: 'csosn_padrao', label: 'CSOSN padrão' }, { key: 'cst_padrao', label: 'CST padrão' }, { key: 'informacoes_complementares', label: 'Informações complementares', type: 'textarea', span: 2 }, { key: 'calcula_impostos', label: 'Calcular impostos', type: 'checkbox' }, { key: 'calcula_icms', label: 'Calcular ICMS', type: 'checkbox' }, { key: 'calcula_ipi', label: 'Calcular IPI', type: 'checkbox' }, { key: 'calcula_pis', label: 'Calcular PIS', type: 'checkbox' }, { key: 'calcula_cofins', label: 'Calcular COFINS', type: 'checkbox' }, { key: 'ativa', label: 'Natureza ativa', type: 'checkbox' },
  ] },
}

const config = computed(() => configs[props.entity])
const rows = ref<Record<string, any>[]>([])
const loading = ref(true)
const saving = ref(false)
const deleting = ref(false)
const search = ref('')
const status = ref('all')
const page = ref(1)
const meta = reactive({ current_page: 1, last_page: 1, total: 0, from: 0, to: 0 })
const modalOpen = ref(false)
const confirmOpen = ref(false)
const selected = ref<Record<string, any> | null>(null)
const form = reactive<Record<string, any>>({})
const errors = ref<Record<string, string[]>>({})
const cepLoading = ref(false)
const cepMessage = ref('')
const documentLoading = ref(false)
const documentMessage = ref('')
let debounce: number | undefined
let cepDebounce: number | undefined
let cepLookupToken = 0
let documentDebounce: number | undefined
let documentLookupToken = 0

function digits(value: unknown, max?: number): string {
  const onlyDigits = String(value ?? '').replace(/\D/g, '')
  return max ? onlyDigits.slice(0, max) : onlyDigits
}

function maskDocument(value: unknown): string {
  const valueDigits = digits(value, 14)
  if (valueDigits.length <= 11) {
    return valueDigits
      .replace(/(\d{3})(\d)/, '$1.$2')
      .replace(/(\d{3})\.(\d{3})(\d)/, '$1.$2.$3')
      .replace(/(\d{3})\.(\d{3})\.(\d{3})(\d)/, '$1.$2.$3-$4')
  }
  return valueDigits
    .replace(/(\d{2})(\d)/, '$1.$2')
    .replace(/(\d{2})\.(\d{3})(\d)/, '$1.$2.$3')
    .replace(/(\d{2})\.(\d{3})\.(\d{3})(\d)/, '$1.$2.$3/$4')
    .replace(/(\d{2})\.(\d{3})\.(\d{3})\/(\d{4})(\d)/, '$1.$2.$3/$4-$5')
}

function maskCep(value: unknown): string {
  return digits(value, 8).replace(/(\d{5})(\d)/, '$1-$2')
}

function isValidCnpj(value: unknown): boolean {
  const cnpj = digits(value, 14)
  if (cnpj.length !== 14 || /^(\d)\1+$/.test(cnpj)) return false

  const calculate = (base: string, weights: number[]) => {
    const sum = weights.reduce((total, weight, index) => total + Number(base[index]) * weight, 0)
    const remainder = sum % 11
    return remainder < 2 ? 0 : 11 - remainder
  }

  return calculate(cnpj.slice(0, 12), [5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2]) === Number(cnpj[12])
    && calculate(cnpj.slice(0, 13), [6, 5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2]) === Number(cnpj[13])
}

function normalizeTextValue(key: string, value: unknown): string {
  if (key === 'documento') return maskDocument(value)
  if (key === 'cep') return maskCep(value)
  if (key === 'uf') return String(value ?? '').replace(/[^a-zA-Z]/g, '').slice(0, 2).toUpperCase()
  if (key === 'unidade') return String(value ?? '').trimStart().slice(0, 6).toUpperCase()
  if (['codigo_ibge', 'codigo_municipio_ibge'].includes(key)) return digits(value, 7)
  if (['ncm'].includes(key)) return digits(value, 8)
  if (['cfop', 'cfop_padrao', 'csosn', 'csosn_padrao'].includes(key)) return digits(value, 4)
  if (['cst', 'cst_padrao'].includes(key)) return digits(value, 3)
  return String(value ?? '')
}

function inputAttrs(field: Field): Record<string, string> {
  const attrs: Record<string, string> = {}
  if (['documento', 'cep', 'codigo_ibge', 'codigo_municipio_ibge', 'ncm', 'cfop', 'cfop_padrao', 'csosn', 'csosn_padrao', 'cst', 'cst_padrao'].includes(field.key)) attrs.inputmode = 'numeric'
  if (field.key === 'documento') attrs.maxlength = '18'
  if (field.key === 'cep') attrs.maxlength = '9'
  if (field.key === 'uf') attrs.maxlength = '2'
  if (['codigo_ibge', 'codigo_municipio_ibge'].includes(field.key)) attrs.maxlength = '7'
  if (field.key === 'ncm') attrs.maxlength = '8'
  if (['cfop', 'cfop_padrao', 'csosn', 'csosn_padrao'].includes(field.key)) attrs.maxlength = '4'
  if (['cst', 'cst_padrao'].includes(field.key)) attrs.maxlength = '3'
  return attrs
}

function inputValue(field: Field): string {
  return normalizeTextValue(field.key, form[field.key])
}

function handleInput(field: Field, event: Event) {
  const element = event.target as HTMLInputElement
  const value = normalizeTextValue(field.key, element.value)
  form[field.key] = value
  element.value = value
  if (errors.value[field.key]) delete errors.value[field.key]
  if (field.key === 'documento') scheduleDocumentLookup(value)
  if (field.key === 'cep') scheduleCepLookup(value)
}

function canLookupCep(): boolean {
  return props.entity === 'clientes' || props.entity === 'fornecedores'
}

function canLookupDocument(): boolean {
  return props.entity === 'clientes' || props.entity === 'fornecedores'
}

function scheduleDocumentLookup(value: unknown) {
  window.clearTimeout(documentDebounce)
  documentMessage.value = ''
  if (!canLookupDocument()) return

  const document = digits(value)
  if (document.length < 14) {
    documentLoading.value = false
    return
  }

  if (!isValidCnpj(document)) {
    documentLoading.value = false
    errors.value = { ...errors.value, documento: ['Informe um CNPJ válido.'] }
    return
  }

  documentDebounce = window.setTimeout(() => lookupCnpj(document), 450)
}

async function lookupCnpj(cnpj: string) {
  const token = ++documentLookupToken
  documentLoading.value = true
  documentMessage.value = 'Consultando CNPJ...'

  try {
    const { data } = await api.get(`/cnpj/${cnpj}`)
    if (token !== documentLookupToken) return

    const nameKey = props.entity === 'clientes' ? 'razao_social' : 'nome_razao_social'
    const cityKey = props.entity === 'clientes' ? 'cidade' : 'municipio'
    const ibgeKey = props.entity === 'clientes' ? 'codigo_ibge' : 'codigo_municipio_ibge'

    form[nameKey] = data.razao_social || data.nome_fantasia || form[nameKey] || ''
    form.cep = maskCep(data.cep || form.cep)
    form.logradouro = data.logradouro || form.logradouro || ''
    form.numero = data.numero || form.numero || ''
    form.complemento = data.complemento || form.complemento || ''
    form.bairro = data.bairro || form.bairro || ''
    form[cityKey] = data.municipio || form[cityKey] || ''
    form[ibgeKey] = digits(data.codigo_ibge, 7) || form[ibgeKey] || ''
    form.uf = normalizeTextValue('uf', data.uf || form.uf)

    delete errors.value.documento
    documentMessage.value = 'Dados preenchidos pelo CNPJ.'
  } catch (error) {
    if (token !== documentLookupToken) return
    const parsed = apiError(error)
    errors.value = { ...errors.value, documento: [parsed.message || 'CNPJ não encontrado na BrasilAPI.'] }
    documentMessage.value = ''
  } finally {
    if (token === documentLookupToken) documentLoading.value = false
  }
}

function scheduleCepLookup(value: unknown) {
  window.clearTimeout(cepDebounce)
  cepMessage.value = ''
  if (!canLookupCep()) return

  const cep = digits(value)
  if (cep.length !== 8) {
    cepLoading.value = false
    return
  }

  cepDebounce = window.setTimeout(() => lookupCep(cep), 450)
}

async function lookupCep(cep: string) {
  const token = ++cepLookupToken
  cepLoading.value = true
  cepMessage.value = 'Consultando CEP...'

  try {
    const { data } = await api.get(`/cep/${cep}`)
    if (token !== cepLookupToken) return

    const cityKey = props.entity === 'clientes' ? 'cidade' : 'municipio'
    const ibgeKey = props.entity === 'clientes' ? 'codigo_ibge' : 'codigo_municipio_ibge'
    form.cep = maskCep(data.cep || cep)
    form.logradouro = data.logradouro || ''
    form.bairro = data.bairro || ''
    form[cityKey] = data.municipio || ''
    form[ibgeKey] = digits(data.codigo_ibge, 7)
    form.uf = normalizeTextValue('uf', data.uf)
    delete errors.value.cep
    cepMessage.value = 'Endereço preenchido pelo CEP.'
  } catch (error) {
    if (token !== cepLookupToken) return
    const parsed = apiError(error)
    errors.value = { ...errors.value, cep: [parsed.message || 'CEP não encontrado.'] }
    cepMessage.value = ''
  } finally {
    if (token === cepLookupToken) cepLoading.value = false
  }
}

function normalizedPayload(): Record<string, any> {
  const payload: Record<string, any> = { ...form }
  for (const key of ['documento', 'cep', 'codigo_ibge', 'codigo_municipio_ibge', 'ncm', 'cfop', 'cfop_padrao', 'csosn', 'csosn_padrao', 'cst', 'cst_padrao']) {
    if (key in payload) payload[key] = digits(payload[key]) || null
  }
  if ('uf' in payload) payload.uf = normalizeTextValue('uf', payload.uf) || null
  if ('unidade' in payload) payload.unidade = normalizeTextValue('unidade', payload.unidade)
  return payload
}

function validateFront(): boolean {
  const localErrors: Record<string, string[]> = {}
  for (const field of config.value.fields) {
    const value = form[field.key]
    const empty = field.type === 'currency' ? value === '' || value === null || value === undefined : String(value ?? '').trim() === ''
    if (field.required && empty) localErrors[field.key] = [`O campo ${field.label.toLowerCase()} é obrigatório.`]
  }

  const documento = digits(form.documento)
  if (documento && ![11, 14].includes(documento.length)) localErrors.documento = ['Informe um CPF com 11 números ou CNPJ com 14 números.']
  if (documento.length === 14 && !isValidCnpj(documento)) localErrors.documento = ['Informe um CNPJ válido.']
  const cep = digits(form.cep)
  if (cep && cep.length !== 8) localErrors.cep = ['Informe um CEP válido com 8 números.']
  const uf = normalizeTextValue('uf', form.uf)
  if (uf && uf.length !== 2) localErrors.uf = ['Informe a UF com 2 letras.']
  const codigoIbge = digits(form.codigo_ibge || form.codigo_municipio_ibge)
  if (codigoIbge && codigoIbge.length !== 7) {
    const key = 'codigo_ibge' in form ? 'codigo_ibge' : 'codigo_municipio_ibge'
    localErrors[key] = ['Informe o código IBGE com 7 números.']
  }
  const ncm = digits(form.ncm)
  if (ncm && ncm.length !== 8) localErrors.ncm = ['Informe o NCM com 8 números.']
  if ('valor_unitario' in form && Number(form.valor_unitario || 0) <= 0) localErrors.valor_unitario = ['Informe um valor unitário maior que zero.']
  const cfop = digits(form.cfop || form.cfop_padrao)
  if (cfop && cfop.length !== 4) {
    const key = 'cfop' in form ? 'cfop' : 'cfop_padrao'
    localErrors[key] = ['Informe o CFOP com 4 números.']
  }

  errors.value = localErrors
  return Object.keys(localErrors).length === 0
}

function resetForm(row?: Record<string, any>) {
  window.clearTimeout(cepDebounce)
  window.clearTimeout(documentDebounce)
  cepLoading.value = false
  cepMessage.value = ''
  documentLoading.value = false
  documentMessage.value = ''
  Object.keys(form).forEach((key) => delete form[key])
  Object.assign(form, config.value.defaults, row || {})
  errors.value = {}; selected.value = row || null; modalOpen.value = true
}
function display(row: Record<string, any>, column: Column) {
  const value = row[column.key]
  if (column.key === 'cidade' || column.key === 'municipio') return [value, row.uf].filter(Boolean).join(' / ') || '—'
  if (column.format === 'document') {
    const digits = String(value || '').replace(/\D/g, '')
    return digits.length === 14 ? digits.replace(/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/, '$1.$2.$3/$4-$5') : digits.replace(/(\d{3})(\d{3})(\d{3})(\d{2})/, '$1.$2.$3-$4')
  }
  if (column.format === 'currency') return new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(Number(value || 0))
  return value || '—'
}
async function load() {
  loading.value = true
  try {
    const params: Record<string, unknown> = { page: page.value, per_page: 12, search: search.value }
    if (status.value !== 'all') params.ativo = status.value === 'active'
    if (props.entity === 'naturezas') params.all = true
    const { data } = await api.get(config.value.endpoint, { params })
    rows.value = data.data || data
    const m = data.meta || data
    Object.assign(meta, { current_page: m.current_page || 1, last_page: m.last_page || 1, total: m.total ?? rows.value.length, from: m.from ?? (rows.value.length ? 1 : 0), to: m.to ?? rows.value.length })
  } catch (error) { toast.show('error', apiError(error).message) } finally { loading.value = false }
}
async function save() {
  errors.value = {}
  if (!validateFront()) {
    toast.show('error', 'Revise os campos destacados antes de salvar.')
    return
  }
  saving.value = true
  try {
    const payload = normalizedPayload()
    const response = selected.value ? await api.put(`${config.value.endpoint}/${selected.value.id}`, payload) : await api.post(config.value.endpoint, payload)
    toast.show('success', response.data.message || `${config.value.singular} salvo com sucesso.`)
    modalOpen.value = false; await load()
  } catch (error) { const parsed = apiError(error); errors.value = parsed.errors; toast.show('error', parsed.message) } finally { saving.value = false }
}
async function remove() {
  if (!selected.value) return
  deleting.value = true
  try { await api.delete(`${config.value.endpoint}/${selected.value.id}`); toast.show('success', `${config.value.singular[0].toUpperCase()}${config.value.singular.slice(1)} excluído com sucesso.`); confirmOpen.value = false; await load() }
  catch (error) { toast.show('error', apiError(error).message) } finally { deleting.value = false }
}
function requestDelete(row: Record<string, any>) { selected.value = row; confirmOpen.value = true }
function changePage(value: number) { if (value < 1 || value > meta.last_page) return; page.value = value; load() }
watch([search, status], () => { window.clearTimeout(debounce); debounce = window.setTimeout(() => { page.value = 1; load() }, 350) })
watch(() => props.entity, () => { search.value = ''; status.value = 'all'; page.value = 1; load() })
onMounted(load)
</script>

<template>
  <div class="catalog-page">
    <header class="page-heading"><div><span class="eyebrow">Cadastros</span><h1>{{ config.title }}</h1><p>{{ config.description }}</p></div><button class="button button--primary" @click="resetForm()"><CirclePlus :size="18" /> Novo {{ config.singular }}</button></header>
    <section class="panel data-panel">
      <div class="data-toolbar"><div class="search-box"><Search :size="18" /><input v-model="search" :placeholder="`Buscar ${config.title.toLowerCase()}…`"></div><div class="filter-select"><SlidersHorizontal :size="17" /><select v-model="status"><option value="all">Todos os status</option><option value="active">Ativos</option><option value="inactive">Inativos</option></select></div></div>
      <div class="table-wrap">
        <table class="data-table"><thead><tr><th v-for="column in config.columns" :key="column.key">{{ column.label }}</th><th class="actions-column">Ações</th></tr></thead>
          <tbody v-if="loading"><tr v-for="n in 5" :key="n"><td v-for="column in config.columns" :key="column.key"><span class="skeleton" /></td><td><span class="skeleton skeleton--short" /></td></tr></tbody>
          <tbody v-else><tr v-for="row in rows" :key="row.id"><td v-for="(column, index) in config.columns" :key="column.key"><strong v-if="index === 0">{{ display(row, column) }}</strong><span v-else-if="column.format === 'status'" class="table-status" :class="{ 'is-inactive': !(row.ativo ?? row.ativa) }"><i />{{ (row.ativo ?? row.ativa) ? 'Ativo' : 'Inativo' }}</span><span v-else>{{ display(row, column) }}</span></td><td class="row-actions"><button title="Editar" @click="resetForm(row)"><Pencil :size="16" /></button><button class="danger-action" title="Excluir" @click="requestDelete(row)"><Trash2 :size="16" /></button></td></tr></tbody>
        </table>
        <EmptyState v-if="!loading && !rows.length" title="Nenhum registro encontrado" message="Ajuste os filtros ou adicione o primeiro cadastro."><button class="button button--primary button--compact" @click="resetForm()"><CirclePlus :size="16" /> Adicionar</button></EmptyState>
      </div>
      <footer v-if="meta.total" class="pagination"><span>Mostrando {{ meta.from }}–{{ meta.to }} de {{ meta.total }}</span><div><button :disabled="meta.current_page <= 1" @click="changePage(meta.current_page - 1)"><ChevronLeft :size="17" /></button><span>Página {{ meta.current_page }} de {{ meta.last_page }}</span><button :disabled="meta.current_page >= meta.last_page" @click="changePage(meta.current_page + 1)"><ChevronRight :size="17" /></button></div></footer>
    </section>

    <BaseModal :open="modalOpen" :title="selected ? `Editar ${config.singular}` : `Novo ${config.singular}`" description="Os campos com asterisco são obrigatórios." wide @close="modalOpen = false">
      <form id="catalog-form" class="modal-form-grid" @submit.prevent="save">
        <label v-for="field in config.fields" :key="field.key" :class="{ 'field-span-2': field.span === 2, 'checkbox-field': field.type === 'checkbox' }">
          <template v-if="field.type === 'checkbox'"><input v-model="form[field.key]" type="checkbox"><span>{{ field.label }}</span></template>
          <template v-else><span>{{ field.label }} <b v-if="field.required">*</b></span><select v-if="field.type === 'select'" v-model="form[field.key]"><option v-for="option in field.options" :key="option.value" :value="option.value">{{ option.label }}</option></select><textarea v-else-if="field.type === 'textarea'" v-model="form[field.key]" rows="3" :placeholder="field.placeholder || `Informe ${field.label.toLowerCase()}`" /><CurrencyInput v-else-if="field.type === 'currency'" v-model="form[field.key]" :placeholder="field.placeholder" /><input v-else :value="inputValue(field)" :type="field.type || 'text'" v-bind="inputAttrs(field)" :placeholder="field.placeholder || `Informe ${field.label.toLowerCase()}`" autocomplete="off" @input="handleInput(field, $event)"><small v-if="errors[field.key]">{{ errors[field.key][0] }}</small><small v-else-if="field.key === 'documento' && documentMessage" class="field-hint">{{ documentMessage }}</small><small v-else-if="field.key === 'cep' && cepMessage" class="field-hint">{{ cepMessage }}</small></template>
        </label>
      </form>
      <template #footer><button class="button button--ghost" :disabled="saving" @click="modalOpen = false">Cancelar</button><button class="button button--primary" form="catalog-form" :disabled="saving">{{ saving ? 'Salvando…' : 'Salvar cadastro' }}</button></template>
    </BaseModal>
    <ConfirmModal :open="confirmOpen" :loading="deleting" :title="`Excluir ${config.singular}?`" :message="`Esta ação removerá “${selected?.[config.nameKey] || ''}”. Ela não poderá ser desfeita.`" @close="confirmOpen = false" @confirm="remove" />
  </div>
</template>
