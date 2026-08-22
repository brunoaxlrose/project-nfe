<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { Building2, ChevronDown, CircleGauge, Crown, FilePlus2, Files, LogOut, Menu, Package, PanelLeftClose, PanelLeftOpen, Settings2, Tags, Users, UserRound, X } from '@lucide/vue'
import { useAuthStore } from '../stores/auth'

const auth = useAuthStore()
const router = useRouter()
const route = useRoute()
const collapsed = ref(localStorage.getItem('ff_sidebar') === 'collapsed')
const mobileOpen = ref(false)
const profileOpen = ref(false)

const nav = [
  { label: 'Visão geral', to: '/dashboard', icon: CircleGauge, permission: 'menu.dashboard' },
  { label: 'Notas fiscais', to: '/dashboard/notas', icon: Files, permission: 'nfe.visualizar' },
  { label: 'Clientes', to: '/dashboard/clientes', icon: UserRound, permission: 'clientes.visualizar' },
  { label: 'Fornecedores', to: '/dashboard/fornecedores', icon: Building2, permission: 'fornecedores.visualizar' },
  { label: 'Produtos', to: '/dashboard/produtos', icon: Package, permission: 'produtos.visualizar' },
  { label: 'Naturezas', to: '/dashboard/naturezas', icon: Tags, permission: 'naturezas.visualizar' },
]
const visibleNav = computed(() => nav.filter((item) => auth.can(item.permission)))
const displayName = computed(() => auth.user?.nome || auth.user?.name || 'Usuário')
const initials = computed(() => displayName.value.split(' ').slice(0, 2).map((part) => part[0]).join('').toUpperCase())
const environment = computed(() => Number(auth.user?.ambiente_sefaz || 2))
const environmentLabel = computed(() => environment.value === 1 ? 'Produção' : 'Homologação')

function toggleCollapsed() {
  collapsed.value = !collapsed.value
  localStorage.setItem('ff_sidebar', collapsed.value ? 'collapsed' : 'expanded')
}
function logout() { auth.logout(); router.replace('/login') }
onMounted(() => auth.refresh())
</script>

<template>
  <div class="app-shell" :class="{ 'sidebar-collapsed': collapsed }">
    <div v-if="mobileOpen" class="mobile-overlay" @click="mobileOpen = false" />
    <aside class="sidebar" :class="{ 'is-mobile-open': mobileOpen }">
      <button class="sidebar-toggle" :aria-label="collapsed ? 'Expandir menu lateral' : 'Recolher menu lateral'" :title="collapsed ? 'Expandir menu' : 'Recolher menu'" @click="toggleCollapsed">
        <PanelLeftOpen v-if="collapsed" :size="17" /><PanelLeftClose v-else :size="17" />
      </button>
      <div class="brand-row">
        <RouterLink class="brand" to="/dashboard"><span class="brand-mark">F</span><span class="brand-copy"><strong>FiscalFlow</strong><small>Gestão fiscal</small></span></RouterLink>
        <button class="sidebar-close" @click="mobileOpen = false"><X :size="20" /></button>
      </div>
      <nav class="main-nav" aria-label="Navegação principal">
        <p class="nav-eyebrow">Workspace</p>
        <RouterLink v-for="item in visibleNav" :key="item.to" :to="item.to" :title="collapsed ? item.label : undefined" @click="mobileOpen = false">
          <component :is="item.icon" :size="19" /><span>{{ item.label }}</span>
        </RouterLink>
        <p class="nav-eyebrow nav-eyebrow--space">Administração</p>
        <RouterLink v-if="auth.can('menu.usuarios')" to="/dashboard/usuarios"><Users :size="19" /><span>Usuários</span></RouterLink>
        <RouterLink v-if="auth.can('menu.configuracoes')" to="/dashboard/configuracoes"><Settings2 :size="19" /><span>Configurações</span></RouterLink>
        <template v-if="auth.user?.is_master">
          <p class="nav-eyebrow nav-eyebrow--space">Plataforma</p>
          <RouterLink to="/dashboard/master"><Crown :size="19" /><span>Empresas e planos</span></RouterLink>
        </template>
      </nav>
      <div v-if="auth.user?.is_master" class="sidebar-foot master-environment" title="Administração global da plataforma"><Crown :size="16" /><div><strong>Conta MASTER</strong><small>Controle global SaaS</small></div></div>
      <div v-else class="sidebar-foot" :class="{ production: environment === 1 }" :title="`SEFAZ: ${environmentLabel}`"><span class="environment-dot" /><div><strong>{{ environmentLabel }}</strong><small>{{ environment === 1 ? 'SEFAZ · valor fiscal' : 'SEFAZ · sem valor fiscal' }}</small></div></div>
    </aside>

    <section class="main-area">
      <header class="topbar">
        <div class="topbar-start">
          <button class="mobile-menu" aria-label="Abrir menu" @click="mobileOpen = true"><Menu :size="21" /></button>
          <button class="collapse-button" :aria-label="collapsed ? 'Expandir menu' : 'Recolher menu'" @click="toggleCollapsed">
            <PanelLeftOpen v-if="collapsed" :size="20" /><PanelLeftClose v-else :size="20" />
          </button>
          <span class="topbar-context">FiscalFlow <b>/</b> {{ route.meta.title || 'Workspace' }}</span>
        </div>
        <div class="topbar-actions">
          <RouterLink v-if="auth.can('nfe.criar')" class="button button--primary button--compact" to="/dashboard/nfe/nova"><FilePlus2 :size="17" /> Nova NF-e</RouterLink>
          <div class="profile-menu">
            <button class="profile-trigger" @click="profileOpen = !profileOpen"><span class="avatar">{{ initials }}</span><span class="profile-copy"><strong>{{ displayName }}</strong><small>{{ auth.user?.email }}</small></span><ChevronDown :size="16" /></button>
            <Transition name="dropdown"><div v-if="profileOpen" class="profile-dropdown"><button @click="logout"><LogOut :size="17" /> Sair da conta</button></div></Transition>
          </div>
        </div>
      </header>
      <main class="page-container"><RouterView /></main>
    </section>
  </div>
</template>
