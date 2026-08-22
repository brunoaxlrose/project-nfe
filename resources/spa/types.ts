export interface AuthUser {
  id: number
  nome?: string
  name?: string
  email: string
  perfil?: string | { nome?: string; slug?: string }
  permissions?: string[]
  ambiente_sefaz?: number
}

export interface PaginationMeta {
  current_page: number
  last_page: number
  per_page: number
  total: number
  from: number | null
  to: number | null
}

export interface Paginated<T> {
  data: T[]
  meta: PaginationMeta
}
