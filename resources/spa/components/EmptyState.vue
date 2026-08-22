<script setup lang="ts">
import { Inbox } from '@lucide/vue'
defineProps<{ title: string; message: string }>()
</script>

<template>
  <div class="empty-state"><span><Inbox :size="26" /></span><h3>{{ title }}</h3><p>{{ message }}</p><slot /></div>
</template>
