<script setup lang="ts">
import { reactive, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { ArrowRight, Check, Eye, EyeOff, LockKeyhole, ShieldCheck, Sparkles } from '@lucide/vue'
import { useAuthStore } from '../stores/auth'
import { apiError } from '../services/api'

const route = useRoute()
const router = useRouter()
const auth = useAuthStore()
const showPassword = ref(false)
const errorMessage = ref(sessionStorage.getItem('auth_message') || '')
sessionStorage.removeItem('auth_message')
const errors = ref<Record<string, string[]>>({})
const form = reactive({ email: '', password: '' })
async function submit() {
  errorMessage.value = ''; errors.value = {}
  try {
    await auth.login({ email: form.email, password: form.password })
    await router.replace(sessionStorage.getItem('subscription_blocked') === '1' ? '/pagamento' : String(route.query.redirect || '/dashboard'))
  } catch (error) {
    const parsed = apiError(error); errorMessage.value = parsed.message; errors.value = parsed.errors
  }
}
</script>

<template>
  <div class="auth-page">
    <section class="auth-story">
      <div class="story-glow story-glow--one" /><div class="story-glow story-glow--two" />
      <div class="story-content">
        <a class="auth-brand" href="/"><span class="brand-mark">F</span><strong>FiscalFlow</strong></a>
        <div class="story-main"><span class="story-kicker"><Sparkles :size="15" /> Gestão fiscal sem ruído</span><h1>Seu fiscal organizado.<br><em>Seu negócio fluindo.</em></h1><p>Cadastros, emissão e acompanhamento de NF-e em um workspace seguro, claro e feito para a rotina real.</p>
          <div class="story-points"><span><Check :size="16" /> Dados separados por empresa</span><span><Check :size="16" /> Controle granular de acesso</span><span><Check :size="16" /> Comunicação integrada à SEFAZ</span></div>
        </div>
        <div class="story-security"><ShieldCheck :size="20" /><span><strong>Segurança em primeiro lugar</strong><small>Certificados e dados fiscais protegidos.</small></span></div>
      </div>
    </section>
    <main class="auth-form-area">
      <div class="auth-form-card">
        <span class="auth-icon"><LockKeyhole :size="23" /></span>
        <h2>Bem-vindo de volta</h2>
        <p>Entre para continuar no seu workspace.</p>
        <div v-if="errorMessage" class="form-alert">{{ errorMessage }}</div>
        <form novalidate @submit.prevent="submit">
          <label>E-mail<input v-model="form.email" type="email" autocomplete="email" placeholder="voce@empresa.com.br"><small v-if="errors.email">{{ errors.email[0] }}</small></label>
          <label>Senha<div class="password-field"><input v-model="form.password" :type="showPassword ? 'text' : 'password'" autocomplete="current-password" placeholder="Mínimo de 8 caracteres"><button type="button" :aria-label="showPassword ? 'Ocultar senha' : 'Mostrar senha'" @click="showPassword = !showPassword"><EyeOff v-if="showPassword" :size="18" /><Eye v-else :size="18" /></button></div><small v-if="errors.password">{{ errors.password[0] }}</small></label>
          <button class="button button--primary auth-submit" :disabled="auth.loading">{{ auth.loading ? 'Aguarde…' : 'Entrar no FiscalFlow' }}<ArrowRight :size="18" /></button>
        </form>
        <!-- Cadastro público de empresas desativado. -->
      </div>
    </main>
  </div>
</template>
