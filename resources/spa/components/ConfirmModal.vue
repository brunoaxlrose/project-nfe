<script setup lang="ts">
import { TriangleAlert } from '@lucide/vue'
import BaseModal from './BaseModal.vue'
defineProps<{ open: boolean; title: string; message: string; loading?: boolean }>()
defineEmits<{ close: []; confirm: [] }>()
</script>

<template>
  <BaseModal :open="open" :title="title" @close="$emit('close')">
    <div class="confirm-message"><span><TriangleAlert :size="22" /></span><p>{{ message }}</p></div>
    <template #footer>
      <button class="button button--ghost" :disabled="loading" @click="$emit('close')">Cancelar</button>
      <button class="button button--danger" :disabled="loading" @click="$emit('confirm')">{{ loading ? 'Excluindo…' : 'Sim, excluir' }}</button>
    </template>
  </BaseModal>
</template>
