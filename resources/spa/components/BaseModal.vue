<script setup lang="ts">
import { X } from '@lucide/vue'
defineProps<{ open: boolean; title: string; description?: string; wide?: boolean }>()
defineEmits<{ close: [] }>()
</script>

<template>
  <Teleport to="body">
    <Transition name="modal">
      <div v-if="open" class="modal-backdrop" role="presentation">
        <section class="modal-card" :class="{ 'modal-card--wide': wide }" role="dialog" aria-modal="true" :aria-label="title">
          <header class="modal-header">
            <div><h2>{{ title }}</h2><p v-if="description">{{ description }}</p></div>
            <button class="icon-button" aria-label="Fechar" @click="$emit('close')"><X :size="20" /></button>
          </header>
          <div class="modal-body"><slot /></div>
          <footer v-if="$slots.footer" class="modal-footer"><slot name="footer" /></footer>
        </section>
      </div>
    </Transition>
  </Teleport>
</template>
