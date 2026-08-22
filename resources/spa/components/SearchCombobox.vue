<script setup lang="ts">
import { ChevronDown, LoaderCircle, Search, X } from '@lucide/vue'
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import { api } from '../services/api'

const props = withDefaults(defineProps<{
  modelValue: Record<string, any> | null
  endpoint: string
  labelKey: string
  placeholder: string
  searchPlaceholder?: string
  emptyText?: string
  params?: Record<string, unknown>
}>(), {
  searchPlaceholder: 'Digite para buscar…',
  emptyText: 'Nenhum resultado encontrado.',
  params: () => ({}),
})

const emit = defineEmits<{
  'update:modelValue': [value: Record<string, any> | null]
  select: [value: Record<string, any>]
}>()

const root = ref<HTMLElement | null>(null)
const input = ref<HTMLInputElement | null>(null)
const open = ref(false)
const loading = ref(false)
const query = ref('')
const options = ref<Record<string, any>[]>([])
const activeIndex = ref(-1)
const error = ref('')
let debounce: number | undefined
let requestId = 0

const selectedLabel = computed(() => props.modelValue?.[props.labelKey] || '')

async function searchOptions() {
  const currentRequest = ++requestId
  loading.value = true
  error.value = ''
  try {
    const { data } = await api.get(props.endpoint, {
      params: { page: 1, per_page: 12, ativo: true, search: query.value.trim(), ...props.params },
    })
    if (currentRequest !== requestId) return
    options.value = data.data || data
    activeIndex.value = options.value.length ? 0 : -1
  } catch {
    if (currentRequest !== requestId) return
    options.value = []
    error.value = 'Não foi possível carregar os resultados.'
  } finally {
    if (currentRequest === requestId) loading.value = false
  }
}

async function show() {
  open.value = true
  query.value = ''
  await nextTick()
  input.value?.focus()
  await searchOptions()
}

function select(option: Record<string, any>) {
  emit('update:modelValue', option)
  emit('select', option)
  open.value = false
  query.value = ''
}

function clear(event?: Event) {
  event?.stopPropagation()
  emit('update:modelValue', null)
  query.value = ''
  options.value = []
  if (open.value) input.value?.focus()
}

function keydown(event: KeyboardEvent) {
  if (event.key === 'ArrowDown') {
    event.preventDefault()
    activeIndex.value = Math.min(options.value.length - 1, activeIndex.value + 1)
  } else if (event.key === 'ArrowUp') {
    event.preventDefault()
    activeIndex.value = Math.max(0, activeIndex.value - 1)
  } else if (event.key === 'Enter' && activeIndex.value >= 0) {
    event.preventDefault()
    select(options.value[activeIndex.value])
  } else if (event.key === 'Escape') {
    open.value = false
  }
}

function closeOutside(event: MouseEvent) {
  if (!root.value?.contains(event.target as Node)) open.value = false
}

watch(query, () => {
  if (!open.value) return
  window.clearTimeout(debounce)
  options.value = []
  activeIndex.value = -1
  debounce = window.setTimeout(searchOptions, 280)
})

onMounted(() => document.addEventListener('mousedown', closeOutside))
onBeforeUnmount(() => {
  document.removeEventListener('mousedown', closeOutside)
  window.clearTimeout(debounce)
})
</script>

<template>
  <div ref="root" class="search-combobox" :class="{ 'is-open': open }">
    <button type="button" class="combobox-trigger" :aria-expanded="open" aria-haspopup="listbox" @click="show">
      <span v-if="modelValue" class="combobox-selected"><slot name="selected" :option="modelValue"><strong>{{ selectedLabel }}</strong></slot></span>
      <span v-else class="combobox-placeholder">{{ placeholder }}</span>
      <span v-if="modelValue" class="combobox-clear" role="button" tabindex="0" aria-label="Limpar seleção" @click="clear" @keydown.enter.prevent="clear" @keydown.space.prevent="clear"><X :size="15" /></span>
      <ChevronDown v-else :size="17" />
    </button>

    <Transition name="dropdown">
      <div v-if="open" class="combobox-menu">
        <div class="combobox-search"><Search :size="17" /><input ref="input" v-model="query" role="combobox" :placeholder="searchPlaceholder" autocomplete="off" @keydown="keydown"></div>
        <div class="combobox-results" role="listbox">
          <button v-for="(option, index) in options" :key="option.id" type="button" role="option" :aria-selected="modelValue?.id === option.id" :class="{ active: activeIndex === index, selected: modelValue?.id === option.id }" @mouseenter="activeIndex = index" @click="select(option)">
            <slot name="option" :option="option"><strong>{{ option[labelKey] }}</strong></slot>
          </button>
          <div v-if="loading" class="combobox-feedback"><LoaderCircle class="spin" :size="18" /> Buscando…</div>
          <div v-else-if="error" class="combobox-feedback is-error">{{ error }}</div>
          <div v-else-if="!options.length" class="combobox-feedback">{{ emptyText }}</div>
        </div>
        <footer><span v-if="query">Resultados para “{{ query }}”</span><span v-else>Digite para refinar a busca</span><kbd>↑↓</kbd><kbd>Enter</kbd></footer>
      </div>
    </Transition>
  </div>
</template>
