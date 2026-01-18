<template>
  <div class="page-container">
    <!-- Loading state -->
    <div v-if="loading" class="loading-state">
      <div class="spinner"></div>
      <p class="loading-text">Chargement du tableau de bord...</p>
    </div>

    <!-- Error state -->
    <div v-else-if="error" class="error-state">
      <div class="error-icon">
        <svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
        </svg>
      </div>
      <p class="error-message">{{ error }}</p>
      <button @click="loadDashboardData" class="btn-primary">
        Réessayer
      </button>
    </div>

    <!-- Content -->
    <div v-else class="content-wrapper">
      <!-- Header -->
      <div class="page-header fade-in" style="animation-delay: 0.1s">
        <div>
          <h1 class="page-title">Tableau de bord</h1>
          <p class="page-subtitle">Vue d'ensemble de votre activité commerciale</p>
        </div>
        <div class="header-actions">
          <button @click="loadDashboardData" class="btn-secondary">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
            </svg>
            <span>Actualiser</span>
          </button>
        </div>
      </div>

      <!-- Statistics Cards -->
      <div class="stats-grid fade-in" style="animation-delay: 0.2s">
        <div class="stat-card">
          <div class="stat-header">
            <div class="stat-icon bg-blue-100 text-blue-600">
              <Package class="w-5 h-5" />
            </div>
          </div>
          <div class="stat-content">
            <p class="stat-label">Articles en stock</p>
            <h3 class="stat-value">{{ stats.totalArticles }}</h3>
          </div>
        </div>

        <div class="stat-card">
          <div class="stat-header">
            <div class="stat-icon bg-green-100 text-green-600">
              <TrendingUp class="w-5 h-5" />
            </div>
          </div>
          <div class="stat-content">
            <p class="stat-label">Factures de vente</p>
            <h3 class="stat-value">{{ stats.totalFacturesVente }}</h3>
          </div>
        </div>

        <div class="stat-card">
          <div class="stat-header">
            <div class="stat-icon bg-orange-100 text-orange-600">
              <ShoppingCart class="w-5 h-5" />
            </div>
          </div>
          <div class="stat-content">
            <p class="stat-label">Factures d'achat</p>
            <h3 class="stat-value">{{ stats.totalFacturesAchat }}</h3>
          </div>
        </div>

        <div class="stat-card">
          <div class="stat-header">
            <div class="stat-icon bg-purple-100 text-purple-600">
              <Wallet class="w-5 h-5" />
            </div>
          </div>
          <div class="stat-content">
            <p class="stat-label">Solde caisse</p>
            <h3 class="stat-value">{{ formatCurrency(stats.soldeCaisse) }}</h3>
          </div>
        </div>
      </div>

      <!-- Recent Activities -->
      <div class="activities-grid fade-in" style="animation-delay: 0.3s">
        <!-- Recent Sales -->
        <div class="card">
          <div class="card-header">
            <h2 class="card-title">Dernières ventes</h2>
            <router-link to="/ventes/factures" class="card-link">
              Voir tout
            </router-link>
          </div>
          <div class="activity-list">
            <div v-if="recentVentes.length === 0" class="empty-state-small">
              <p>Aucune vente récente</p>
            </div>
            <div 
              v-else
              v-for="vente in recentVentes" 
              :key="vente.id" 
              class="activity-item"
            >
              <div class="activity-info">
                <p class="activity-title">{{ vente.numero }}</p>
                <p class="activity-subtitle">{{ vente.client }}</p>
              </div>
              <div class="activity-meta">
                <p class="activity-amount">{{ formatCurrency(vente.montant) }}</p>
                <span class="badge badge-success">{{ vente.statut }}</span>
              </div>
            </div>
          </div>
        </div>

        <!-- Recent Purchases -->
        <div class="card">
          <div class="card-header">
            <h2 class="card-title">Derniers achats</h2>
            <router-link to="/achats/factures" class="card-link">
              Voir tout
            </router-link>
          </div>
          <div class="activity-list">
            <div v-if="recentAchats.length === 0" class="empty-state-small">
              <p>Aucun achat récent</p>
            </div>
            <div 
              v-else
              v-for="achat in recentAchats" 
              :key="achat.id" 
              class="activity-item"
            >
              <div class="activity-info">
                <p class="activity-title">{{ achat.numero }}</p>
                <p class="activity-subtitle">{{ achat.fournisseur }}</p>
              </div>
              <div class="activity-meta">
                <p class="activity-amount">{{ formatCurrency(achat.montant) }}</p>
                <span class="badge badge-warning">{{ achat.statut }}</span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { Package, TrendingUp, ShoppingCart, Wallet } from 'lucide-vue-next'
import api from '@/services/api/api'

const stats = ref({
  totalArticles: 0,
  totalFacturesVente: 0,
  totalFacturesAchat: 0,
  soldeCaisse: 0
})

const recentVentes = ref([])
const recentAchats = ref([])
const loading = ref(false)
const error = ref(null)

const formatCurrency = (amount) => {
  return new Intl.NumberFormat('fr-MG', {
    style: 'currency',
    currency: 'MGA',
    minimumFractionDigits: 0
  }).format(amount)
}

const loadDashboardData = async () => {
  loading.value = true
  error.value = null
  try {
    const statsResponse = await api.get('/dashboard/stats')
    stats.value = statsResponse.data || stats.value

    const ventesResponse = await api.get('/dashboard/recent-ventes')
    recentVentes.value = ventesResponse.data || []

    const achatsResponse = await api.get('/dashboard/recent-achats')
    recentAchats.value = achatsResponse.data || []

  } catch (err) {
    error.value = 'Erreur lors du chargement des données'
    console.error('Erreur chargement dashboard:', err)
    
    stats.value = {
      totalArticles: 0,
      totalFacturesVente: 0,
      totalFacturesAchat: 0,
      soldeCaisse: 0
    }
    recentVentes.value = []
    recentAchats.value = []
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  loadDashboardData()
})
</script>

<style scoped>
.page-container {
  @apply min-h-screen ml-64 p-6 bg-gray-50;
}

@media (max-width: 1024px) {
  .page-container {
    @apply ml-20 p-4;
  }
}

@media (max-width: 768px) {
  .page-container {
    @apply ml-0 p-4;
  }
}

/* Loading State */
.loading-state {
  @apply flex flex-col items-center justify-center py-20;
}

.spinner {
  @apply w-12 h-12 border-4 border-gray-200 border-t-gray-900 rounded-full;
  animation: spin 1s linear infinite;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

.loading-text {
  @apply mt-4 text-sm text-gray-600;
}

/* Error State */
.error-state {
  @apply flex flex-col items-center justify-center py-20;
}

.error-icon {
  @apply text-red-600 mb-4;
}

.error-message {
  @apply text-sm text-red-600 mb-4;
}

/* Content Wrapper */
.content-wrapper {
  @apply space-y-6;
}

/* Fade In Animation */
@keyframes fadeInUp {
  from {
    opacity: 0;
    transform: translateY(20px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.fade-in {
  animation: fadeInUp 0.5s ease-out forwards;
  opacity: 0;
}

/* Page Header */
.page-header {
  @apply flex items-center justify-between mb-6;
}

.page-title {
  @apply text-2xl font-bold text-gray-900;
}

.page-subtitle {
  @apply text-sm text-gray-500 mt-1;
}

.header-actions {
  @apply flex items-center gap-3;
}

/* Stats Grid */
.stats-grid {
  @apply grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4;
}

.stat-card {
  @apply bg-white rounded-lg border border-gray-200 p-5 hover:shadow-md transition-all;
}

.stat-header {
  @apply flex items-center justify-between mb-3;
}

.stat-icon {
  @apply w-10 h-10 rounded-lg flex items-center justify-center;
}

.stat-content {
  @apply space-y-1;
}

.stat-label {
  @apply text-xs text-gray-500 uppercase tracking-wide font-medium;
}

.stat-value {
  @apply text-xl font-bold text-gray-900;
}

/* Activities Grid */
.activities-grid {
  @apply grid grid-cols-1 lg:grid-cols-2 gap-6;
}

.card {
  @apply bg-white rounded-lg border border-gray-200 p-5;
}

.card-header {
  @apply flex items-center justify-between mb-4 pb-3 border-b border-gray-200;
}

.card-title {
  @apply text-base font-semibold text-gray-900;
}

.card-link {
  @apply text-xs text-gray-500 hover:text-gray-900 transition-colors;
}

.activity-list {
  @apply space-y-3;
}

.activity-item {
  @apply flex items-center justify-between p-3 rounded-lg hover:bg-gray-50 transition-all;
}

.activity-info {
  @apply flex-1;
}

.activity-title {
  @apply text-sm font-medium text-gray-900;
}

.activity-subtitle {
  @apply text-xs text-gray-500 mt-0.5;
}

.activity-meta {
  @apply text-right space-y-1;
}

.activity-amount {
  @apply text-sm font-semibold text-gray-900;
}

.empty-state-small {
  @apply text-center py-8 text-sm text-gray-500;
}

/* Badges */
.badge {
  @apply inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium;
}

.badge-success {
  @apply bg-green-100 text-green-700;
}

.badge-warning {
  @apply bg-orange-100 text-orange-700;
}
</style>