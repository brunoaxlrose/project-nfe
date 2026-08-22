import axios, { AxiosError } from 'axios'

export const api = axios.create({
  baseURL: '/api',
  headers: { Accept: 'application/json', 'Content-Type': 'application/json' },
  timeout: 30000,
})

api.interceptors.request.use((config) => {
  const token = localStorage.getItem('nfe_token')
  if (token) config.headers.Authorization = `Bearer ${token}`
  if (config.data instanceof FormData) delete config.headers['Content-Type']
  return config
})

api.interceptors.response.use(
  (response) => response,
  (error: AxiosError) => {
    if (error.response?.status === 401) {
      localStorage.removeItem('nfe_token')
      localStorage.removeItem('nfe_user')
      if (!window.location.pathname.startsWith('/login')) {
        sessionStorage.setItem('auth_message', 'Sua sessão expirou. Entre novamente para continuar.')
        window.location.assign('/login')
      }
    }
    return Promise.reject(error)
  },
)

export function apiError(error: unknown): { message: string; errors: Record<string, string[]> } {
  if (!axios.isAxiosError(error)) return { message: 'Ocorreu um erro inesperado.', errors: {} }
  const payload = error.response?.data as { message?: string; errors?: Record<string, string[]> } | undefined
  const errors = payload?.errors || {}
  const firstValidation = Object.values(errors).flat()[0]
  if (firstValidation) return { message: firstValidation, errors }
  if (payload?.message && payload.message !== 'The given data was invalid.') return { message: payload.message, errors }
  if (error.code === 'ECONNABORTED') return { message: 'A operação demorou mais que o esperado. Verifique sua conexão e tente novamente.', errors }
  if (!error.response) return { message: 'Não foi possível conectar ao servidor. Verifique sua internet e tente novamente.', errors }
  const statusMessages: Record<number, string> = {
    400: 'A solicitação contém dados inválidos. Revise as informações e tente novamente.',
    403: 'Seu usuário não tem permissão para realizar esta operação.',
    404: 'O registro solicitado não foi encontrado ou não está mais disponível.',
    409: 'Esta operação entra em conflito com um registro já existente.',
    413: 'O arquivo ou conteúdo enviado excede o tamanho permitido.',
    415: 'O formato do conteúdo enviado não é compatível com esta operação.',
    422: 'Alguns campos precisam ser corrigidos antes de continuar.',
    429: 'Foram feitas muitas tentativas em pouco tempo. Aguarde alguns instantes e tente novamente.',
    500: 'O servidor encontrou um erro ao processar a operação. Nenhuma nova tentativa deve ser feita até revisar os dados.',
    503: 'O serviço está temporariamente indisponível. Aguarde alguns instantes e tente novamente.',
  }
  return { message: statusMessages[error.response.status] || 'Não foi possível concluir a operação.', errors }
}
