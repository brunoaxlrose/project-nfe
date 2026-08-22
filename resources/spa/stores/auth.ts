import { defineStore } from 'pinia'
import { computed, ref } from 'vue'
import { api } from '../services/api'
import type { AuthUser } from '../types'

export const useAuthStore = defineStore('auth', () => {
  const token = ref(localStorage.getItem('nfe_token'))
  const user = ref<AuthUser | null>(JSON.parse(localStorage.getItem('nfe_user') || 'null'))
  const loading = ref(false)
  const authenticated = computed(() => Boolean(token.value))
  const permissions = computed(() => user.value?.permissions || [])

  function persist(accessToken: string, authUser: AuthUser) {
    token.value = accessToken
    user.value = authUser
    localStorage.setItem('nfe_token', accessToken)
    localStorage.setItem('nfe_user', JSON.stringify(authUser))
  }

  async function login(credentials: { email: string; password: string }) {
    loading.value = true
    try {
      const { data } = await api.post('/auth/login', credentials)
      persist(data.access_token, data.user)
      if (data.code === 'assinatura_bloqueada' || data.user?.assinatura_bloqueada) {
        sessionStorage.setItem('subscription_blocked', '1')
      } else {
        sessionStorage.removeItem('subscription_blocked')
      }
    } finally {
      loading.value = false
    }
  }

  // Cadastro público de empresas desativado.
  // A criação deve ser feita somente por um fluxo administrativo controlado.

  async function refresh() {
    if (!token.value) return false
    try {
      const { data } = await api.get('/auth/me')
      user.value = data.user
      localStorage.setItem('nfe_user', JSON.stringify(data.user))
      sessionStorage.removeItem('subscription_blocked')
      return true
    } catch { return false }
  }

  function can(permission?: string) {
    if (user.value?.is_master || !permission) return true
    if (!permissions.value.includes(permission)) return false
    const module = permission.startsWith('nfe.') || permission === 'menu.nfe' ? 'nfe'
      : permission.startsWith('clientes.') ? 'clientes'
      : permission.startsWith('fornecedores.') ? 'fornecedores'
      : permission.startsWith('produtos.') ? 'produtos'
      : permission.startsWith('naturezas.') ? 'naturezas'
      : permission.startsWith('usuarios.') || permission.startsWith('perfis.') || permission === 'menu.usuarios' ? 'usuarios'
      : permission.startsWith('configuracoes.') || permission.startsWith('certificado.') || permission === 'menu.configuracoes' ? 'configuracoes'
      : null
    const modules = user.value?.modulos_plano || []
    return !module || modules.includes('*') || modules.includes(module)
  }

  function logout() {
    token.value = null
    user.value = null
    localStorage.removeItem('nfe_token')
    localStorage.removeItem('nfe_user')
    sessionStorage.removeItem('subscription_blocked')
  }

  return { token, user, loading, authenticated, permissions, login, refresh, can, logout }
})
