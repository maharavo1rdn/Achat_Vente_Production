<template>
  <aside class="sidebar" :class="{ 'collapsed': isCollapsed }">
    <!-- Header -->
    <div class="sidebar-header">
      <div class="flex items-center justify-between w-full">
        <div class="flex items-center space-x-3">
          <div class="logo-container">
            <Building2 class="w-5 h-5 text-gray-900" />
          </div>
          <div v-if="!isCollapsed" class="flex-1">
            <h1 class="text-base font-bold text-white">ERP Achat-Vente</h1>
            <p class="text-xs text-gray-400">Gestion Commerciale</p>
          </div>
        </div>
        <button 
          @click="toggleSidebar" 
          class="toggle-btn"
          :title="isCollapsed ? 'Étendre' : 'Réduire'"
        >
          <ChevronLeft :class="{ 'rotate-180': isCollapsed }" class="w-4 h-4" />
        </button>
      </div>
    </div>

    <!-- Navigation -->
    <nav class="sidebar-nav">
      <!-- Dashboard -->
      <div class="nav-section">
        <router-link to="/" class="nav-item" :class="{ 'active': isActive('/') }">
          <div class="nav-item-icon">
            <LayoutDashboard class="w-4 h-4" />
          </div>
          <span v-if="!isCollapsed" class="nav-item-label">Tableau de bord</span>
        </router-link>
      </div>

      <!-- Produits & Stock -->
      <div class="nav-section">
        <h3 v-if="!isCollapsed" class="nav-section-title">PRODUITS & STOCK</h3>
        <div class="nav-group">
          <button
            @click="toggleMenu('stock')"
            class="nav-item nav-item-parent"
            :class="{ 'active': isStockActive, 'expanded': openMenus.stock }"
          >
            <div class="nav-item-icon">
              <Package class="w-4 h-4" />
            </div>
            <span v-if="!isCollapsed" class="nav-item-label">Gestion Stock</span>
            <ChevronDown
              v-if="!isCollapsed"
              class="nav-chevron"
              :class="{ 'rotated': openMenus.stock }"
            />
          </button>
          
          <Transition name="dropdown">
            <div v-if="openMenus.stock && !isCollapsed" class="nav-submenu">
              <router-link to="/articles" class="nav-subitem" :class="{ 'active': isActive('/articles') }">
                <div class="nav-subitem-dot"></div>
                <span>Articles</span>
              </router-link>
              <router-link to="/stock" class="nav-subitem" :class="{ 'active': isActive('/stock') }">
                <div class="nav-subitem-dot"></div>
                <span>Stock</span>
              </router-link>
            </div>
          </Transition>
        </div>
      </div>

      <!-- Achats -->
      <div class="nav-section">
        <h3 v-if="!isCollapsed" class="nav-section-title">ACHATS</h3>
        <div class="nav-group">
          <button
            @click="toggleMenu('achats')"
            class="nav-item nav-item-parent"
            :class="{ 'active': isAchatsActive, 'expanded': openMenus.achats }"
          >
            <div class="nav-item-icon">
              <ShoppingCart class="w-4 h-4" />
            </div>
            <span v-if="!isCollapsed" class="nav-item-label">Gestion Achats</span>
            <ChevronDown
              v-if="!isCollapsed"
              class="nav-chevron"
              :class="{ 'rotated': openMenus.achats }"
            />
          </button>
          
          <Transition name="dropdown">
            <div v-if="openMenus.achats && !isCollapsed" class="nav-submenu">
              <router-link
                v-for="item in achatsMenuItems"
                :key="item.path"
                :to="item.path"
                class="nav-subitem"
                :class="{ 'active': isActive(item.path) }"
              >
                <div class="nav-subitem-dot"></div>
                <span>{{ item.label }}</span>
              </router-link>
            </div>
          </Transition>
        </div>
      </div>

      <!-- Ventes -->
      <div class="nav-section">
        <h3 v-if="!isCollapsed" class="nav-section-title">VENTES</h3>
        <div class="nav-group">
          <button
            @click="toggleMenu('ventes')"
            class="nav-item nav-item-parent"
            :class="{ 'active': isVentesActive, 'expanded': openMenus.ventes }"
          >
            <div class="nav-item-icon">
              <TrendingUp class="w-4 h-4" />
            </div>
            <span v-if="!isCollapsed" class="nav-item-label">Gestion Ventes</span>
            <ChevronDown
              v-if="!isCollapsed"
              class="nav-chevron"
              :class="{ 'rotated': openMenus.ventes }"
            />
          </button>
          
          <Transition name="dropdown">
            <div v-if="openMenus.ventes && !isCollapsed" class="nav-submenu">
              <router-link
                v-for="item in ventesMenuItems"
                :key="item.path"
                :to="item.path"
                class="nav-subitem"
                :class="{ 'active': isActive(item.path) }"
              >
                <div class="nav-subitem-dot"></div>
                <span>{{ item.label }}</span>
              </router-link>
            </div>
          </Transition>
        </div>
      </div>

      <!-- Finance -->
      <div class="nav-section">
        <h3 v-if="!isCollapsed" class="nav-section-title">FINANCE</h3>
        <router-link to="/caisse" class="nav-item" :class="{ 'active': isActive('/caisse') }">
          <div class="nav-item-icon">
            <Wallet class="w-4 h-4" />
          </div>
          <span v-if="!isCollapsed" class="nav-item-label">Caisse</span>
        </router-link>
      </div>

      <!-- Paramètres -->
      <div class="nav-section">
        <h3 v-if="!isCollapsed" class="nav-section-title">PARAMÈTRES</h3>
        <div class="nav-group">
          <button
            @click="toggleMenu('settings')"
            class="nav-item nav-item-parent"
            :class="{ 'active': isSettingsActive, 'expanded': openMenus.settings }"
          >
            <div class="nav-item-icon">
              <Settings class="w-4 h-4" />
            </div>
            <span v-if="!isCollapsed" class="nav-item-label">Configuration</span>
            <ChevronDown
              v-if="!isCollapsed"
              class="nav-chevron"
              :class="{ 'rotated': openMenus.settings }"
            />
          </button>
          
          <Transition name="dropdown">
            <div v-if="openMenus.settings && !isCollapsed" class="nav-submenu">
              <router-link to="/entreprises" class="nav-subitem" :class="{ 'active': isActive('/entreprises') }">
                <div class="nav-subitem-dot"></div>
                <span>Entreprises</span>
              </router-link>
              <router-link to="/personnel" class="nav-subitem" :class="{ 'active': isActive('/personnel') }">
                <div class="nav-subitem-dot"></div>
                <span>Personnel</span>
              </router-link>
            </div>
          </Transition>
        </div>
      </div>
    </nav>

    <!-- Footer -->
    <div class="sidebar-footer">
      <div class="user-card">
        <div class="user-avatar">
          <User class="w-4 h-4 text-gray-900" />
        </div>
        <div v-if="!isCollapsed" class="user-details">
          <p class="user-name">Admin</p>
          <p class="user-role">Administrateur</p>
        </div>
        <button 
          v-if="!isCollapsed" 
          class="logout-btn" 
          @click="logout" 
          title="Se déconnecter"
        >
          <LogOut class="w-4 h-4" />
        </button>
      </div>
    </div>
  </aside>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import { useRoute } from 'vue-router'
import {
  Building2,
  LayoutDashboard,
  Package,
  ShoppingCart,
  TrendingUp,
  Wallet,
  Settings,
  User,
  LogOut,
  ChevronDown,
  ChevronLeft
} from 'lucide-vue-next'

const route = useRoute()
const isCollapsed = ref(false)
const openMenus = ref({
  stock: false,
  achats: false,
  ventes: false,
  settings: false
})

const achatsMenuItems = [
  { path: '/achats/proforma', label: 'Proforma' },
  { path: '/achats/bon-commande', label: 'Bons de Commande' },
  { path: '/achats/factures', label: 'Factures' }
]

const ventesMenuItems = [
  { path: '/ventes/devis', label: 'Devis' },
  { path: '/ventes/bon-commande', label: 'Bons de Commande' },
  { path: '/ventes/factures', label: 'Factures' }
]

const isStockActive = computed(() => route.path.startsWith('/articles') || route.path.startsWith('/stock'))
const isAchatsActive = computed(() => route.path.startsWith('/achats'))
const isVentesActive = computed(() => route.path.startsWith('/ventes'))
const isSettingsActive = computed(() => route.path.startsWith('/entreprises') || route.path.startsWith('/personnel'))

watch(() => route.path, (newPath) => {
  if (newPath.startsWith('/articles') || newPath.startsWith('/stock')) {
    openMenus.value.stock = true
  }
  if (newPath.startsWith('/achats')) {
    openMenus.value.achats = true
  }
  if (newPath.startsWith('/ventes')) {
    openMenus.value.ventes = true
  }
  if (newPath.startsWith('/entreprises') || newPath.startsWith('/personnel')) {
    openMenus.value.settings = true
  }
}, { immediate: true })

const isActive = computed(() => (path) => {
  if (path === '/') return route.path === '/'
  return route.path.startsWith(path)
})

function toggleMenu(menu) {
  openMenus.value[menu] = !openMenus.value[menu]
}

function toggleSidebar() {
  isCollapsed.value = !isCollapsed.value
  if (isCollapsed.value) {
    Object.keys(openMenus.value).forEach(key => {
      openMenus.value[key] = false
    })
  }
}

const logout = () => {
  localStorage.removeItem('token')
  window.location.href = '/login'
}
</script>

<style scoped>
.sidebar {
  @apply fixed left-0 top-0 h-screen bg-gray-900 shadow-xl flex flex-col z-50 transition-all duration-300;
  width: 260px;
  background: linear-gradient(180deg, #1f2937 0%, #111827 100%);
}

.sidebar.collapsed {
  width: 70px;
}

.sidebar-header {
  @apply p-4 border-b border-gray-800;
}

.logo-container {
  @apply w-10 h-10 bg-white rounded-lg flex items-center justify-center shadow-md flex-shrink-0;
}

.toggle-btn {
  @apply p-1.5 rounded-lg text-gray-400 hover:text-white hover:bg-gray-800 transition-all;
}

.toggle-btn svg {
  @apply transition-transform duration-300;
}

.sidebar-nav {
  @apply flex-1 overflow-y-auto py-4 px-3 space-y-6;
}

.sidebar-nav::-webkit-scrollbar {
  @apply w-1;
}

.sidebar-nav::-webkit-scrollbar-track {
  @apply bg-transparent;
}

.sidebar-nav::-webkit-scrollbar-thumb {
  @apply bg-gray-700 rounded-full;
}

.nav-section {
  @apply space-y-1;
}

.nav-section-title {
  @apply text-xs font-semibold text-gray-500 uppercase tracking-wider px-3 mb-2;
}

.nav-group {
  @apply space-y-1;
}

.nav-item {
  @apply flex items-center gap-3 px-3 py-2.5 rounded-lg text-gray-400 hover:text-white cursor-pointer relative transition-all;
}

.nav-item:hover {
  @apply bg-gray-800;
}

.nav-item-icon {
  @apply flex items-center justify-center w-8 h-8 rounded-lg bg-gray-800 transition-all flex-shrink-0;
}

.nav-item:hover .nav-item-icon {
  @apply bg-gray-700;
}

.nav-item.active .nav-item-icon {
  @apply bg-white text-gray-900;
}

.nav-item-label {
  @apply text-sm font-medium flex-1;
}

.nav-item.active {
  @apply text-white;
}

.nav-chevron {
  @apply w-4 h-4 text-gray-400 transition-transform ml-auto flex-shrink-0;
}

.nav-chevron.rotated {
  @apply rotate-180 text-white;
}

.nav-submenu {
  @apply mt-1 ml-3 pl-3 space-y-1 border-l-2 border-gray-800;
}

.nav-subitem {
  @apply flex items-center gap-2 px-3 py-2 rounded-lg text-gray-400 hover:text-white hover:bg-gray-800 transition-all text-sm;
}

.nav-subitem-dot {
  @apply w-1.5 h-1.5 rounded-full bg-gray-600 flex-shrink-0;
}

.nav-subitem.active {
  @apply text-white bg-gray-800;
}

.nav-subitem.active .nav-subitem-dot {
  @apply bg-white;
}

.dropdown-enter-active,
.dropdown-leave-active {
  transition: all 0.3s ease;
  overflow: hidden;
}

.dropdown-enter-from,
.dropdown-leave-to {
  opacity: 0;
  max-height: 0;
}

.dropdown-enter-to,
.dropdown-leave-from {
  opacity: 1;
  max-height: 300px;
}

.sidebar-footer {
  @apply p-3 border-t border-gray-800;
}

.user-card {
  @apply flex items-center gap-3 p-2.5 rounded-lg bg-gray-800 hover:bg-gray-700 cursor-pointer transition-all;
}

.user-avatar {
  @apply w-9 h-9 rounded-lg bg-white flex items-center justify-center shadow-md flex-shrink-0;
}

.user-details {
  @apply flex-1 min-w-0;
}

.user-name {
  @apply text-xs font-semibold text-white truncate;
}

.user-role {
  @apply text-xs text-gray-400 truncate;
}

.logout-btn {
  @apply p-2 rounded-lg bg-gray-900 text-gray-400 hover:bg-red-600 hover:text-white transition-all flex-shrink-0;
}

@media (max-width: 1024px) {
  .sidebar {
    width: 70px;
  }
  
  .sidebar.collapsed {
    width: 70px;
  }
  
  .toggle-btn,
  .nav-section-title,
  .nav-item-label,
  .nav-chevron,
  .user-details,
  .logout-btn {
    @apply hidden;
  }
  
  .nav-submenu {
    @apply hidden;
  }
}
</style>