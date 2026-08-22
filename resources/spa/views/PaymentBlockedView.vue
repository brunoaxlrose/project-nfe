<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import { ArrowRight, CheckCircle2, Clipboard, CreditCard, Loader2, LogOut, QrCode, ShieldAlert } from '@lucide/vue'
import { api, apiError } from '../services/api'
import { useAuthStore } from '../stores/auth'
import { useToastStore } from '../stores/toast'

type Plano = { id_plano: number; nome: string; descricao?: string; valor_mensal: string | number; duracao_dias?: number | null; modulos?: string[] }

const router = useRouter()
const auth = useAuthStore()
const toast = useToastStore()
const loading = ref(true)
const generating = ref(false)
const checking = ref(false)
const planos = ref<Plano[]>([])
const planoAtual = ref<Plano | null>(null)
const assinatura = ref<Record<string, any> | null>(null)
const empresa = ref<Record<string, any> | null>(null)
const valorPix = ref('0')
const selectedPlanId = ref<number | null>(null)
const pix = ref<{ transaction_id: string; external_reference: string; valor: string; qr_code: string; qr_code_base64: string } | null>(null)

const selectedPlan = computed(() => planos.value.find((plan) => plan.id_plano === selectedPlanId.value) || planoAtual.value || planos.value[0])
const qrImage = computed(() => pix.value?.qr_code_base64 ? `data:image/png;base64,${pix.value.qr_code_base64}` : '')
const money = (value: unknown) => Number(value || 0).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' })
const dateLabel = (value?: string) => value ? new Intl.DateTimeFormat('pt-BR').format(new Date(value)) : 'sem data'

async function load() {
  loading.value = true
  try {
    const { data } = await api.get('/pagamentos/opcoes')
    empresa.value = data.empresa
    assinatura.value = data.assinatura
    planoAtual.value = data.plano_atual
    planos.value = data.planos || []
    valorPix.value = data.valor_pix || String(data.planos?.[0]?.valor_mensal || '0')
    selectedPlanId.value = planoAtual.value?.id_plano || planos.value[0]?.id_plano || null
  } catch (error) {
    toast.show('error', apiError(error).message, 'Pagamento')
  } finally {
    loading.value = false
  }
}

async function generatePix() {
  if (!selectedPlan.value || !auth.user) return
  generating.value = true
  try {
    const { data } = await api.post('/pagamentos/pix', {
      id_plano: selectedPlan.value.id_plano,
      id_usuario: auth.user.id,
      email: auth.user.email,
    })
    pix.value = data
    toast.show('success', 'Pix gerado. Use o QR Code ou o Copia e Cola.', 'Renovação')
  } catch (error) {
    toast.show('error', apiError(error).message, 'Pagamento')
  } finally {
    generating.value = false
  }
}

async function copyPix() {
  if (!pix.value?.qr_code) return
  await navigator.clipboard.writeText(pix.value.qr_code)
  toast.show('success', 'Código Pix copiado.')
}

async function checkAccess() {
  checking.value = true
  try {
    const ok = await auth.refresh()
    if (ok) {
      sessionStorage.removeItem('subscription_blocked')
      toast.show('success', 'Pagamento confirmado. Acesso liberado.')
      await router.replace('/dashboard')
      return
    }
    toast.show('warning', 'Ainda não recebemos a confirmação do pagamento.')
  } finally {
    checking.value = false
  }
}

function logout() {
  auth.logout()
  router.replace('/login')
}

onMounted(load)
</script>

<template>
  <main class="payment-lock-page">
    <section class="payment-lock-shell">
      <header class="payment-lock-header">
        <a class="auth-brand" href="/"><span class="brand-mark">F</span><strong>FiscalFlow</strong></a>
        <button class="button button--ghost button--compact" @click="logout"><LogOut :size="16" /> Sair</button>
      </header>

      <div class="payment-lock-grid">
        <section class="payment-lock-copy">
          <span class="lock-badge"><ShieldAlert :size="18" /> Acesso bloqueado</span>
          <h1>Seu plano venceu. Para continuar, renove pelo Pix.</h1>
          <p>Nenhum módulo do sistema fica disponível enquanto a assinatura estiver vencida. Após a confirmação do Asaas, o acesso é liberado automaticamente.</p>

          <div class="blocked-company">
            <strong>{{ empresa?.nome_fantasia || empresa?.razao_social || 'Empresa' }}</strong>
            <span v-if="planoAtual">Plano atual: {{ planoAtual.nome }}</span>
            <span v-if="assinatura?.termina_em">Vencimento: {{ dateLabel(assinatura.termina_em) }}</span>
          </div>
        </section>

        <section class="payment-panel">
          <div v-if="loading" class="payment-loading"><Loader2 class="spin" :size="24" /> Carregando opções de renovação…</div>
          <template v-else>
            <header>
              <span><CreditCard :size="20" /></span>
              <div><h2>Renovação por Pix</h2><p>Valor fixo de homologação: {{ money(valorPix) }}</p></div>
            </header>

            <div class="payment-plan-list">
              <button v-for="plan in planos" :key="plan.id_plano" :class="{ active: selectedPlanId === plan.id_plano }" @click="selectedPlanId = plan.id_plano">
                <span><strong>{{ plan.nome }}</strong><small>{{ plan.descricao || 'Plano FiscalFlow' }}</small></span>
                <b>{{ money(valorPix) }}</b>
                <CheckCircle2 v-if="selectedPlanId === plan.id_plano" :size="18" />
              </button>
            </div>

            <button class="button button--primary payment-main-action" :disabled="generating || !selectedPlan" @click="generatePix">
              <QrCode :size="18" />{{ generating ? 'Gerando Pix…' : 'Gerar Pix para renovar' }}
            </button>

            <div v-if="pix" class="pix-box">
              <img v-if="qrImage" :src="qrImage" alt="QR Code Pix">
              <div class="pix-copy">
                <strong>Pix Copia e Cola</strong>
                <textarea readonly :value="pix.qr_code" rows="4" />
                <button class="button button--ghost" @click="copyPix"><Clipboard :size="17" /> Copiar código</button>
              </div>
              <button class="button button--primary payment-main-action" :disabled="checking" @click="checkAccess">
                <ArrowRight :size="18" />{{ checking ? 'Verificando…' : 'Já paguei, verificar acesso' }}
              </button>
            </div>
          </template>
        </section>
      </div>
    </section>
  </main>
</template>
