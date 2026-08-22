import { defineStore } from 'pinia'
import { ref } from 'vue'

type ToastType = 'success' | 'error' | 'warning' | 'info'
interface Toast { id: number; type: ToastType; title: string; message: string }

export const useToastStore = defineStore('toast', () => {
  const items = ref<Toast[]>([])
  let nextId = 1
  function show(type: ToastType, message: string, title = type === 'error' ? 'Algo deu errado' : 'Tudo certo') {
    const id = nextId++
    items.value.push({ id, type, title, message })
    const duration = type === 'error' ? 12000 : type === 'warning' ? 8000 : 5000
    window.setTimeout(() => dismiss(id), duration)
  }
  function dismiss(id: number) { items.value = items.value.filter((item) => item.id !== id) }
  return { items, show, dismiss }
})
