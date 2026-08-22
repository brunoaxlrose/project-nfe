<script setup lang="ts">
import { CheckCircle2, CircleAlert, Info, X } from '@lucide/vue'
import { useToastStore } from '../stores/toast'
const toast = useToastStore()
</script>

<template>
  <div class="toast-stack" aria-live="polite">
    <TransitionGroup name="toast">
      <article v-for="item in toast.items" :key="item.id" class="toast" :class="`toast--${item.type}`">
        <CheckCircle2 v-if="item.type === 'success'" :size="20" />
        <CircleAlert v-else-if="item.type === 'error' || item.type === 'warning'" :size="20" />
        <Info v-else :size="20" />
        <div><strong>{{ item.title }}</strong><p>{{ item.message }}</p></div>
        <button aria-label="Fechar aviso" @click="toast.dismiss(item.id)"><X :size="16" /></button>
      </article>
    </TransitionGroup>
  </div>
</template>
