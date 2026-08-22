<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { ArrowRight, CheckCircle2, CircleDollarSign, Clock3, FilePlus2, Files, Package, Send, TrendingUp, UsersRound } from '@lucide/vue'
import { api } from '../services/api'
import { useAuthStore } from '../stores/auth'

const auth = useAuthStore()
const loading = ref(true)
const summary = ref<Record<string, any>>({})
const name = computed(() => (auth.user?.nome || auth.user?.name || 'usuário').split(' ')[0])
const currency = (value: number) => new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(value || 0)
const sevenDayValue = computed(() => Array.isArray(summary.value.volume_7_dias) ? summary.value.volume_7_dias.reduce((total: number, day: { valor?: number }) => total + Number(day.valor || 0), 0) : 0)

onMounted(async () => {
  try { summary.value = (await api.get('/faturamento/resumo')).data } catch { summary.value = {} } finally { loading.value = false }
})
</script>

<template>
  <div class="dashboard-page">
    <header class="page-heading dashboard-heading"><div><span class="eyebrow">Visão geral</span><h1>Olá, {{ name }}. <em>Vamos fluir?</em></h1><p>Acompanhe sua operação fiscal e acesse os atalhos mais usados.</p></div><a class="button button--primary" href="/dashboard/nfe/nova"><FilePlus2 :size="18" /> Emitir nova NF-e</a></header>
    <section class="metric-grid" :class="{ 'is-loading': loading }">
      <article class="metric-card"><span class="metric-icon metric-icon--blue"><Files :size="21" /></span><div><small>Notas emitidas</small><strong>{{ summary.notas_emitidas ?? 0 }}</strong><span><TrendingUp :size="14" /> visão consolidada</span></div></article>
      <article class="metric-card"><span class="metric-icon metric-icon--green"><CircleDollarSign :size="21" /></span><div><small>Movimentação em 7 dias</small><strong class="metric-currency">{{ currency(sevenDayValue) }}</strong><span><CheckCircle2 :size="14" /> volume fiscal recente</span></div></article>
      <article class="metric-card"><span class="metric-icon metric-icon--amber"><Clock3 :size="21" /></span><div><small>Processando</small><strong>{{ summary.processando ?? 0 }}</strong><span>aguardando retorno</span></div></article>
      <article class="metric-card"><span class="metric-icon metric-icon--violet"><Send :size="21" /></span><div><small>Autorizadas</small><strong>{{ summary.autorizadas ?? 0 }}</strong><span>processadas pela SEFAZ</span></div></article>
    </section>
    <section class="dashboard-columns">
      <article class="panel quick-panel"><div class="panel-heading"><div><span class="eyebrow">Acesso rápido</span><h2>Continue seu trabalho</h2></div></div><div class="quick-grid">
        <a href="/dashboard/nfe/nova"><span class="quick-icon"><FilePlus2 :size="20" /></span><div><strong>Nova NF-e</strong><small>Inicie uma emissão</small></div><ArrowRight :size="17" /></a>
        <RouterLink to="/dashboard/clientes"><span class="quick-icon"><UsersRound :size="20" /></span><div><strong>Clientes</strong><small>Consulte e cadastre</small></div><ArrowRight :size="17" /></RouterLink>
        <RouterLink to="/dashboard/produtos"><span class="quick-icon"><Package :size="20" /></span><div><strong>Produtos</strong><small>Organize seu catálogo</small></div><ArrowRight :size="17" /></RouterLink>
        <RouterLink to="/dashboard/notas"><span class="quick-icon"><Files :size="20" /></span><div><strong>Histórico</strong><small>Acompanhe as notas</small></div><ArrowRight :size="17" /></RouterLink>
      </div></article>
      <article class="panel status-panel"><div class="panel-heading"><div><span class="eyebrow">Status do ambiente</span><h2>Operação monitorada</h2></div><span class="status-pill"><i /> Online</span></div><div class="status-list"><div><span>API FiscalFlow</span><strong>Operacional</strong></div><div><span>Ambiente SEFAZ</span><strong>Configurado</strong></div><div><span>Segurança da sessão</span><strong>Protegida</strong></div></div><a href="/dashboard/configuracoes" class="text-link">Ver configurações <ArrowRight :size="15" /></a></article>
    </section>
  </div>
</template>
