<script setup lang="ts">
import { computed } from 'vue'
import { useRouter } from 'vue-router'
import { ArrowLeft, Home, LockKeyhole, SearchX } from '@lucide/vue'

const props = defineProps<{
  status: 403 | 404
  title?: string
  message?: string
}>()

const router = useRouter()
const isForbidden = computed(() => props.status === 403)
const title = computed(() => props.title || (isForbidden.value ? 'Acesso negado' : 'Página não encontrada'))
const message = computed(() => props.message || (isForbidden.value
  ? 'Seu perfil não possui permissão para acessar esta área.'
  : 'O endereço informado não existe ou não está disponível.'))
</script>

<template>
  <main class="error-page">
    <section class="error-card">
      <span class="error-icon" :class="{ forbidden: isForbidden }">
        <LockKeyhole v-if="isForbidden" :size="28" />
        <SearchX v-else :size="28" />
      </span>
      <strong>{{ status }}</strong>
      <h1>{{ title }}</h1>
      <p>{{ message }}</p>
      <div class="error-actions">
        <button class="button button--ghost" @click="router.back()"><ArrowLeft :size="17" /> Voltar</button>
        <RouterLink class="button button--primary" to="/dashboard"><Home :size="17" /> Início</RouterLink>
      </div>
    </section>
  </main>
</template>
