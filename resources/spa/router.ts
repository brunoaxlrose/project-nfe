import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from './stores/auth'
import AuthView from './views/AuthView.vue'
import DashboardView from './views/DashboardView.vue'
import CatalogView from './views/CatalogView.vue'
import InvoicesView from './views/InvoicesView.vue'
import NewInvoiceView from './views/NewInvoiceView.vue'
import SettingsView from './views/SettingsView.vue'
import UsersView from './views/UsersView.vue'
import AppShell from './layouts/AppShell.vue'

const router = createRouter({
  history: createWebHistory(),
  routes: [
    { path: '/login', component: AuthView, meta: { guest: true, mode: 'login' } },
    // Cadastro público de empresas desativado.
    // { path: '/register', component: AuthView, meta: { guest: true, mode: 'register' } },
    {
      path: '/dashboard', component: AppShell, meta: { auth: true }, children: [
        { path: '', component: DashboardView },
        { path: 'clientes', component: CatalogView, props: { entity: 'clientes' }, meta: { permission: 'clientes.visualizar' } },
        { path: 'fornecedores', component: CatalogView, props: { entity: 'fornecedores' }, meta: { permission: 'fornecedores.visualizar' } },
        { path: 'produtos', component: CatalogView, props: { entity: 'produtos' }, meta: { permission: 'produtos.visualizar' } },
        { path: 'naturezas', component: CatalogView, props: { entity: 'naturezas' }, meta: { permission: 'naturezas.visualizar' } },
        { path: 'notas', component: InvoicesView, meta: { permission: 'nfe.visualizar' } },
        { path: 'nfe/nova', component: NewInvoiceView, meta: { permission: 'nfe.criar' } },
        { path: 'configuracoes', component: SettingsView, meta: { permission: 'menu.configuracoes' } },
        { path: 'usuarios', component: UsersView, meta: { permission: 'menu.usuarios' } },
      ],
    },
    { path: '/', redirect: '/dashboard' },
    { path: '/:pathMatch(.*)*', redirect: '/dashboard' },
  ],
})

router.beforeEach(async (to) => {
  const auth = useAuthStore()
  if (to.meta.auth && !auth.authenticated) return { path: '/login', query: { redirect: to.fullPath } }
  if (to.meta.guest && auth.authenticated) return '/dashboard'
  if (to.meta.permission && !auth.can(String(to.meta.permission))) return '/dashboard'
})

export default router
