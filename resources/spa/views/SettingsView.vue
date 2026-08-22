<script setup lang="ts">
import { computed, onMounted, reactive, ref } from 'vue'
import { Building2, CheckCircle2, FileKey2, ImagePlus, RadioTower, Save, ShieldCheck, SlidersHorizontal, Trash2 } from '@lucide/vue'
import { api, apiError } from '../services/api'
import { useToastStore } from '../stores/toast'
import { useAuthStore } from '../stores/auth'
import ConfirmModal from '../components/ConfirmModal.vue'

const toast = useToastStore()
const auth = useAuthStore()
const loading = ref(true)
const saving = ref(false)
const testing = ref(false)
const removeOpen = ref(false)
const activeTab = ref<'empresa' | 'fiscal' | 'seguranca'>('empresa')
const logo = ref<File | null>(null)
const certificate = ref<File | null>(null)
const errors = ref<Record<string, string[]>>({})
const form = reactive<Record<string, any>>({ ambiente: 2, crt: 1, serie_padrao: 1, cfop_padrao: '5102', inscricao_estadual_isento: false })
const logoPreview = computed(() => logo.value ? URL.createObjectURL(logo.value) : form.logo_data_url)
const tabs = [{ id: 'empresa', label: 'Empresa', icon: Building2 }, { id: 'fiscal', label: 'Fiscal e emissão', icon: SlidersHorizontal }, { id: 'seguranca', label: 'Certificado e segurança', icon: ShieldCheck }] as const

async function load() {
  loading.value = true
  try { Object.assign(form, (await api.get('/configuracoes-emissor')).data) }
  catch (error) { toast.show('error', apiError(error).message) } finally { loading.value = false }
}
function digits(value: unknown) { return String(value || '').replace(/\D/g, '') }
async function save() {
  saving.value = true; errors.value = {}
  try {
    const payload = new FormData()
    const ignored = new Set(['id', 'id_configuracao_emissor', 'id_empresa', 'created_at', 'updated_at', 'certificado_configurado', 'certificado_dono', 'certificado_autoridade', 'logo_configurada', 'logo_data_url', 'certificado_validade', 'certificado_valid_from', 'certificado_subject', 'certificado_issuer', 'certificado_serial'])
    Object.entries(form).forEach(([key, value]) => { if (!ignored.has(key) && value !== null && value !== undefined) payload.append(key, typeof value === 'boolean' ? (value ? '1' : '0') : String(value)) })
    if (logo.value) payload.append('logo', logo.value)
    if (certificate.value) payload.append('certificado', certificate.value)
    const { data } = await api.post('/configuracoes-emissor', payload)
    toast.show('success', data.message || 'Configurações salvas com sucesso.'); logo.value = null; certificate.value = null
    if (data.data) Object.assign(form, data.data); else await load()
    await auth.refresh()
  } catch (error) { const parsed = apiError(error); errors.value = parsed.errors; toast.show('error', parsed.message) } finally { saving.value = false }
}
async function testConnection() {
  testing.value = true
  try { const { data } = await api.post('/configuracoes-emissor/testar-comunicacao'); toast.show(data.success ? 'success' : 'warning', data.message || 'Teste concluído.', 'Comunicação SEFAZ') }
  catch (error) { toast.show('error', apiError(error).message) } finally { testing.value = false }
}
async function removeCertificate() {
  try { const { data } = await api.delete('/configuracoes-emissor/certificado'); toast.show('success', data.message || 'Certificado removido.'); removeOpen.value = false; await load() }
  catch (error) { toast.show('error', apiError(error).message) }
}
function chooseFile(event: Event, target: 'logo' | 'certificate') { const file = (event.target as HTMLInputElement).files?.[0] || null; if (target === 'logo') logo.value = file; else certificate.value = file }
onMounted(load)
</script>

<template>
  <div>
    <header class="page-heading"><div><span class="eyebrow">Administração</span><h1>Configurações</h1><p>Dados empresariais, preferências fiscais e credenciais de emissão.</p></div><button class="button button--primary" :disabled="saving || loading" @click="save"><Save :size="18" />{{ saving ? 'Salvando…' : 'Salvar alterações' }}</button></header>
    <div class="settings-layout">
      <aside class="settings-nav panel"><button v-for="tab in tabs" :key="tab.id" :class="{ active: activeTab === tab.id }" @click="activeTab = tab.id"><component :is="tab.icon" :size="18" /><span>{{ tab.label }}</span></button></aside>
      <section class="panel settings-panel" :class="{ 'is-loading': loading }">
        <div v-if="activeTab === 'empresa'" class="settings-section"><header><span class="settings-icon"><Building2 :size="21" /></span><div><h2>Dados da empresa</h2><p>Informações que identificam o emitente nos documentos fiscais.</p></div></header>
          <div class="settings-form"><label class="field-span-2"><span>Razão social *</span><input v-model="form.razao_social" placeholder="Razão social da empresa"><small v-if="errors.razao_social">{{ errors.razao_social[0] }}</small></label><label><span>Nome fantasia</span><input v-model="form.nome_fantasia" placeholder="Nome usado comercialmente"></label><label><span>CNPJ *</span><input :value="form.cnpj" inputmode="numeric" placeholder="00.000.000/0000-00" @input="form.cnpj = digits(($event.target as HTMLInputElement).value)"><small v-if="errors.cnpj">{{ errors.cnpj[0] }}</small></label><label><span>Inscrição estadual</span><input v-model="form.inscricao_estadual" :disabled="form.inscricao_estadual_isento" placeholder="Inscrição estadual"></label><label class="checkbox-field"><input v-model="form.inscricao_estadual_isento" type="checkbox"><span>Contribuinte isento</span></label><label><span>Inscrição municipal</span><input v-model="form.inscricao_municipal" placeholder="Inscrição municipal"></label><label><span>CNAE</span><input v-model="form.cnae" inputmode="numeric" placeholder="Código CNAE"></label><label><span>Telefone</span><input v-model="form.telefone" placeholder="(00) 0000-0000"></label><label><span>Celular</span><input v-model="form.celular" placeholder="(00) 00000-0000"></label><label class="field-span-2"><span>E-mail</span><input v-model="form.email" type="email" placeholder="empresa@exemplo.com.br"></label>
            <div class="form-divider field-span-2"><strong>Endereço fiscal</strong></div><label><span>CEP *</span><input v-model="form.cep" inputmode="numeric" placeholder="00000-000"><small v-if="errors.cep">{{ errors.cep[0] }}</small></label><label><span>UF *</span><input v-model="form.uf" maxlength="2" placeholder="UF"></label><label class="field-span-2"><span>Logradouro *</span><input v-model="form.logradouro" placeholder="Rua, avenida ou rodovia"></label><label><span>Número *</span><input v-model="form.numero" placeholder="Número"></label><label><span>Complemento</span><input v-model="form.complemento" placeholder="Sala, bloco, referência…"></label><label><span>Bairro *</span><input v-model="form.bairro" placeholder="Bairro"></label><label><span>Município *</span><input v-model="form.municipio" placeholder="Município"></label><label><span>Código IBGE</span><input v-model="form.codigo_municipio_ibge" placeholder="7 dígitos"></label>
            <div class="form-divider field-span-2"><strong>Identidade visual</strong></div><div class="logo-upload field-span-2"><span class="logo-preview"><img v-if="logoPreview" :src="logoPreview" alt="Logo da empresa"><ImagePlus v-else :size="25" /></span><div><strong>Logotipo da empresa</strong><p>PNG, JPG ou WebP com até 2 MB.</p><label class="button button--ghost button--compact">Escolher imagem<input type="file" accept="image/png,image/jpeg,image/webp" hidden @change="chooseFile($event, 'logo')"></label><small v-if="logo">{{ logo.name }}</small></div></div>
          </div>
        </div>
        <div v-else-if="activeTab === 'fiscal'" class="settings-section"><header><span class="settings-icon"><SlidersHorizontal :size="21" /></span><div><h2>Preferências fiscais</h2><p>Padrões usados para agilizar novas emissões.</p></div></header><div class="settings-form"><label><span>Ambiente SEFAZ *</span><select v-model="form.ambiente"><option :value="2">Homologação</option><option :value="1">Produção</option></select></label><label><span>Regime tributário (CRT) *</span><select v-model="form.crt"><option :value="1">Simples Nacional</option><option :value="2">Simples — excesso sublimite</option><option :value="3">Regime Normal</option><option :value="4">MEI</option></select></label><label><span>Série padrão *</span><input v-model.number="form.serie_padrao" type="number" min="1" max="999" placeholder="1"></label><label><span>Próximo número</span><input v-model.number="form.proximo_numero" type="number" min="1" placeholder="Próximo número da NF-e"></label><label><span>CFOP padrão *</span><input v-model="form.cfop_padrao" maxlength="4" placeholder="Ex.: 5901"><small v-if="errors.cfop_padrao">{{ errors.cfop_padrao[0] }}</small></label><label><span>CSOSN padrão</span><input v-model="form.csosn_padrao" maxlength="4" placeholder="Ex.: 0400"></label><div class="environment-warning field-span-2" :class="{ production: Number(form.ambiente) === 1 }"><RadioTower :size="20" /><div><strong>{{ Number(form.ambiente) === 1 ? 'Ambiente de produção' : 'Ambiente de homologação' }}</strong><p>{{ Number(form.ambiente) === 1 ? 'As notas emitidas possuem valor fiscal. Confira todos os dados antes de transmitir.' : 'Indicado para testes. Os documentos emitidos não possuem valor fiscal.' }}</p></div></div></div></div>
        <div v-else class="settings-section"><header><span class="settings-icon"><FileKey2 :size="21" /></span><div><h2>Certificado digital A1</h2><p>Credencial protegida usada para assinar e transmitir documentos.</p></div></header><div class="certificate-status" :class="{ configured: form.certificado_configurado }"><span><CheckCircle2 v-if="form.certificado_configurado" :size="23" /><FileKey2 v-else :size="23" /></span><div><strong>{{ form.certificado_configurado ? 'Certificado configurado' : 'Nenhum certificado configurado' }}</strong><p v-if="form.certificado_dono">{{ form.certificado_dono }}</p><small v-if="form.certificado_validade">Validade: {{ new Date(form.certificado_validade).toLocaleDateString('pt-BR') }}</small></div></div><div class="settings-form certificate-form"><label class="field-span-2"><span>Arquivo do certificado (.pfx ou .p12)</span><input type="file" accept=".pfx,.p12" @change="chooseFile($event, 'certificate')"><small v-if="certificate">Selecionado: {{ certificate.name }}</small><small v-if="errors.certificado">{{ errors.certificado[0] }}</small></label><label class="field-span-2"><span>Senha do certificado</span><input v-model="form.certificado_senha" type="password" autocomplete="new-password" placeholder="Senha do certificado A1"><small v-if="errors.certificado_senha">{{ errors.certificado_senha[0] }}</small></label></div><div class="security-actions"><button class="button button--ghost" :disabled="testing || !form.certificado_configurado" @click="testConnection"><RadioTower :size="17" />{{ testing ? 'Testando…' : 'Testar comunicação' }}</button><button v-if="form.certificado_configurado" class="button danger-outline" @click="removeOpen = true"><Trash2 :size="17" /> Remover certificado</button></div></div>
      </section>
    </div>
    <ConfirmModal :open="removeOpen" title="Remover certificado?" message="A emissão ficará indisponível até que um novo certificado A1 seja configurado." @close="removeOpen = false" @confirm="removeCertificate" />
  </div>
</template>
