<script setup lang="ts">
import { nextTick, ref, watch } from 'vue'

const props = withDefaults(defineProps<{
  modelValue: number | string | null | undefined
  placeholder?: string
  disabled?: boolean
}>(), { placeholder: '0,00', disabled: false })

const emit = defineEmits<{ 'update:modelValue': [value: number] }>()
const input = ref<HTMLInputElement | null>(null)
const focused = ref(false)
const display = ref(format(props.modelValue))

function numeric(value: unknown): number {
  const raw = String(value ?? '').trim()
  const normalized = raw.includes(',') ? raw.replace(/\./g, '').replace(',', '.') : raw
  const parsed = Number(normalized.replace(/[^\d.-]/g, ''))
  return Number.isFinite(parsed) ? Math.max(0, parsed) : 0
}

function cents(value: unknown): number {
  const rawDigits = String(value ?? '').replace(/\D/g, '')
  if (!rawDigits) return 0
  return Number(rawDigits) / 100
}

function format(value: unknown): string {
  return numeric(value).toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
}

function sanitize(value: string): string {
  return format(cents(value))
}

function onInput(event: Event) {
  const element = event.target as HTMLInputElement
  display.value = sanitize(element.value)
  element.value = display.value
  emit('update:modelValue', numeric(display.value))
  requestAnimationFrame(() => element.setSelectionRange(element.value.length, element.value.length))
}

async function onFocus() {
  focused.value = true
  await nextTick()
  input.value?.select()
}

function onBlur() {
  focused.value = false
  display.value = format(display.value)
  emit('update:modelValue', numeric(display.value))
}

watch(() => props.modelValue, (value) => { if (!focused.value) display.value = format(value) })
</script>

<template>
  <input ref="input" :value="display" type="text" inputmode="decimal" :placeholder="placeholder" :disabled="disabled" autocomplete="off" @input="onInput" @focus="onFocus" @blur="onBlur">
</template>
