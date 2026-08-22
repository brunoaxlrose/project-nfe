<script setup lang="ts">
import { TriangleAlert } from '@lucide/vue'
import BaseModal from './BaseModal.vue'
withDefaults(defineProps<{ open: boolean; title: string; message: string; loading?: boolean; confirmLabel?: string; loadingLabel?: string; variant?: 'danger' | 'primary' }>(), {
  confirmLabel: 'Sim, excluir',
  loadingLabel: 'Excluindo...',
  variant: 'danger',
})
defineEmits<{ close: []; confirm: [] }>()
</script>

<template>
  <BaseModal :open="open" :title="title" @close="$emit('close')">
    <div class="confirm-message"><span><TriangleAlert :size="22" /></span><p>{{ message }}</p></div>
    <template #footer>
      <button class="button button--ghost" :disabled="loading" @click="$emit('close')">Cancelar</button>
      <button class="button" :class="variant === 'danger' ? 'button--danger' : 'button--primary'" :disabled="loading" @click="$emit('confirm')">{{ loading ? loadingLabel : confirmLabel }}</button>
    </template>
  </BaseModal>
</template>
