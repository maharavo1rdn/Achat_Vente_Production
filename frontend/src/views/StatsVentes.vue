<template>
  <div class="page-container">
    <!-- Loading state -->
    <div v-if="loading" class="loading-state">
      <div class="spinner"></div>
      <p class="loading-text">Chargement des statistiques achats...</p>
    </div>

    <!-- Error state -->
    <div v-else-if="error" class="error-state">
      <div class="error-icon">
        <svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
        </svg>
      </div>
      <p class="error-message">{{ error }}</p>
      <button @click="fetchData" class="btn-primary">
        Réessayer
      </button>
    </div>

    <!-- Content -->
    <div v-else class="content-wrapper">
      <!-- Header -->
      <div class="page-header fade-in" style="animation-delay: 0.1s">
        <div>
          <h1 class="page-title">📊 Statistiques Achats</h1>
          <p class="page-subtitle">Analyse détaillée de vos dépenses et fournisseurs</p>
        </div>
        <div class="header-actions">
          <div class="flex items-center gap-2">
            <input type="date" v-model="startDate" class="input-date" />
            <span class="text-gray-400">à</span>
            <input type="date" v-model="endDate" class="input-date" />
          </div>
          <button @click="fetchData" class="btn-secondary">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
            </svg>
            <span>Appliquer</span>
          </button>
        </div>
      </div>

      <!-- Statistics Cards -->
      <div class="stats-grid fade-in" style="animation-delay: 0.2s">
        <div class="stat-card">
          <div class="stat-header">
            <div class="stat-icon bg-blue-100 text-blue-600">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
              </svg>
            </div>
          </div>
          <div class="stat-content">
            <p class="stat-label">Dépenses Totales</p>
            <h3 class="stat-value">{{ formatCurrency(totalDepenses) }}</h3>
            <p class="stat-subvalue">Montant total payé</p>
          </div>
        </div>

        <div class="stat-card">
          <div class="stat-header">
            <div class="stat-icon bg-green-100 text-green-600">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
              </svg>
            </div>
          </div>
          <div class="stat-content">
            <p class="stat-label">Fournisseur Principal</p>
            <h3 class="stat-value text-lg">{{ topFournisseur.nom }}</h3>
            <p class="stat-subvalue">{{ formatCurrency(topFournisseur.total_paye) }} dépensé</p>
          </div>
        </div>

        <div class="stat-card">
          <div class="stat-header">
            <div class="stat-icon bg-purple-100 text-purple-600">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
              </svg>
            </div>
          </div>
          <div class="stat-content">
            <p class="stat-label">Meilleur Taux Service</p>
            <h3 class="stat-value">{{ meilleurTauxService.taux }}%</h3>
            <p class="stat-subvalue">{{ meilleurTauxService.nom }}</p>
          </div>
        </div>

        <div class="stat-card">
          <div class="stat-header">
            <div class="stat-icon bg-orange-100 text-orange-600">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
              </svg>
            </div>
          </div>
          <div class="stat-content">
            <p class="stat-label">Délai Livraison Moyen</p>
            <h3 class="stat-value">{{ delaiMoyenGlobal.toFixed(1) }} jours</h3>
            <p class="stat-subvalue">Moyenne tous fournisseurs</p>
          </div>
        </div>
      </div>

      <!-- Charts Grid -->
      <div class="activities-grid fade-in" style="animation-delay: 0.25s">
        <!-- Répartition Dépenses -->
        <div class="card">
          <div class="card-header">
            <h2 class="card-title">📈 Répartition des Dépenses</h2>
          </div>
          <div class="chart-container">
            <Doughnut
              v-if="depenseData.labels.length > 0"
              :data="depenseData"
              :options="chartOptions"
            />
            <div v-else class="empty-state-small">
              <p>Aucune donnée disponible</p>
            </div>
          </div>
        </div>

        <!-- Délai Moyen Livraison -->
        <div class="card">
          <div class="card-header">
            <h2 class="card-title">📅 Délai Moyen de Livraison</h2>
          </div>
          <div class="chart-container">
            <Bar
              v-if="delaiData.labels.length > 0"
              :data="delaiData"
              :options="delaiChartOptions"
            />
            <div v-else class="empty-state-small">
              <p>Aucune donnée disponible</p>
            </div>
          </div>
        </div>
      </div>

      <!-- Second Charts Row -->
      <div class="activities-grid fade-in" style="animation-delay: 0.3s">
        <!-- Taux de Service -->
        <div class="card">
          <div class="card-header">
            <h2 class="card-title">✅ Taux de Service</h2>
          </div>
          <div class="chart-container">
            <Bar
              v-if="tauxData.labels.length > 0"
              :data="tauxData"
              :options="tauxChartOptions"
            />
            <div v-else class="empty-state-small">
              <p>Aucune donnée disponible</p>
            </div>
          </div>
        </div>

        <!-- Budget par Fournisseur -->
        <div class="card">
          <div class="card-header">
            <h2 class="card-title">💳 Budget vs Payé</h2>
          </div>
          <div class="chart-container">
            <Bar
              v-if="budgetData.labels.length > 0"
              :data="budgetData"
              :options="budgetChartOptions"
            />
            <div v-else class="empty-state-small">
              <p>Aucune donnée disponible</p>
            </div>
          </div>
        </div>
      </div>

      <!-- Detailed Tables -->
      <div class="activities-grid fade-in" style="animation-delay: 0.35s">
        <!-- Dépenses Fournisseurs -->
        <div class="card">
          <div class="card-header">
            <h2 class="card-title">📋 Dépenses par Fournisseur</h2>
          </div>
          <div class="table-container">
            <table class="data-table">
              <thead>
                <tr>
                  <th class="table-header">Fournisseur</th>
                  <th class="table-header text-right">Dépensé</th>
                  <th class="table-header text-right">À Payer</th>
                  <th class="table-header text-right">Factures</th>
                </tr>
              </thead>
              <tbody>
                <tr
                  v-for="item in depensesFournisseurs"
                  :key="item.id"
                  class="table-row"
                >
                  <td class="table-cell font-medium">{{ item.nom }}</td>
                  <td class="table-cell text-right text-blue-600 font-semibold">
                    {{ formatCurrency(item.total_paye) }}
                  </td>
                  <td class="table-cell text-right text-red-600">
                    {{ formatCurrency(item.reste_a_payer) }}
                  </td>
                  <td class="table-cell text-right">{{ item.nombre_factures }}</td>
                </tr>
              </tbody>
            </table>
            <div v-if="depensesFournisseurs.length === 0" class="empty-state-small">
              <p>Aucune donnée disponible</p>
            </div>
          </div>
        </div>

        <!-- Taux Service -->
        <div class="card">
          <div class="card-header">
            <h2 class="card-title">📊 Taux de Service</h2>
          </div>
          <div class="table-container">
            <table class="data-table">
              <thead>
                <tr>
                  <th class="table-header">Fournisseur</th>
                  <th class="table-header text-right">BC</th>
                  <th class="table-header text-right">FA Reçues</th>
                  <th class="table-header text-right">Taux</th>
                </tr>
              </thead>
              <tbody>
                <tr
                  v-for="item in tauxServiceData"
                  :key="item.id"
                  class="table-row"
                >
                  <td class="table-cell font-medium">{{ item.nom }}</td>
                  <td class="table-cell text-right">{{ item.nombre_bc }}</td>
                  <td class="table-cell text-right">
                    {{ item.nombre_factures_recues }}
                  </td>
                  <td class="table-cell text-right">
                    <span
                      :class="[
                        'badge',
                        item.taux_service_percent >= 90
                          ? 'badge-success'
                          : item.taux_service_percent >= 70
                            ? 'badge-warning'
                            : 'badge-error'
                      ]"
                    >
                      {{ item.taux_service_percent.toFixed(1) }}%
                    </span>
                  </td>
                </tr>
              </tbody>
            </table>
            <div v-if="tauxServiceData.length === 0" class="empty-state-small">
              <p>Aucune donnée disponible</p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { Bar, Doughnut } from 'vue-chartjs'
import {
  Chart as ChartJS,
  CategoryScale,
  LinearScale,
  PointElement,
  LineElement,
  BarElement,
  Title,
  Tooltip,
  Legend,
  ArcElement
} from 'chart.js'
import api from '@/services/api/api'

ChartJS.register(
  CategoryScale,
  LinearScale,
  PointElement,
  LineElement,
  BarElement,
  Title,
  Tooltip,
  Legend,
  ArcElement
)

// State
const loading = ref(false)
const error = ref(null)
const startDate = ref('')
const endDate = ref('')

// Data
const depensesFournisseurs = ref([])
const delaiMoyenData = ref([])
const tauxServiceData = ref([])

// Initialize dates
const initializeDates = () => {
  const today = new Date()
  const thirtyDaysAgo = new Date(today.getTime() - 30 * 24 * 60 * 60 * 1000)
  endDate.value = today.toISOString().split('T')[0]
  startDate.value = thirtyDaysAgo.toISOString().split('T')[0]
}

// Format currency
const formatCurrency = (value) => {
  return new Intl.NumberFormat('fr-FR', {
    style: 'currency',
    currency: 'XOF',
    minimumFractionDigits: 0
  }).format(value || 0)
}

// Computed KPIs
const totalDepenses = computed(() => {
  return depensesFournisseurs.value.reduce((sum, item) => sum + (item.total_paye || 0), 0)
})

const topFournisseur = computed(() => {
  if (depensesFournisseurs.value.length === 0) {
    return { nom: '-', total_paye: 0 }
  }
  return depensesFournisseurs.value.reduce((max, item) =>
    (item.total_paye || 0) > (max.total_paye || 0) ? item : max
  )
})

const meilleurTauxService = computed(() => {
  if (tauxServiceData.value.length === 0) {
    return { nom: '-', taux: 0 }
  }
  const best = tauxServiceData.value.reduce((max, item) =>
    (item.taux_service_percent || 0) > (max.taux_service_percent || 0) ? item : max
  )
  return { nom: best.nom, taux: best.taux_service_percent.toFixed(1) }
})

const delaiMoyenGlobal = computed(() => {
  if (delaiMoyenData.value.length === 0) return 0
  const sum = delaiMoyenData.value.reduce((acc, item) => acc + (parseFloat(item.delai_moyen_jours) || 0), 0)
  return sum / delaiMoyenData.value.length
})

// Chart Data
const depenseData = computed(() => ({
  labels: depensesFournisseurs.value.slice(0, 8).map((item) => item.nom),
  datasets: [
    {
      label: 'Montant Dépensé',
      data: depensesFournisseurs.value.slice(0, 8).map((item) => item.total_paye),
      backgroundColor: [
        '#3B82F6',
        '#10B981',
        '#F59E0B',
        '#EF4444',
        '#8B5CF6',
        '#EC4899',
        '#14B8A6',
        '#F97316'
      ],
      borderColor: '#ffffff',
      borderWidth: 2
    }
  ]
}))

const delaiData = computed(() => ({
  labels: delaiMoyenData.value.slice(0, 10).map((item) => item.nom),
  datasets: [
    {
      label: 'Délai Moyen (jours)',
      data: delaiMoyenData.value.slice(0, 10).map((item) => parseFloat(item.delai_moyen_jours) || 0),
      backgroundColor: '#60A5FA',
      borderColor: '#3B82F6',
      borderWidth: 1
    }
  ]
}))

const tauxData = computed(() => ({
  labels: tauxServiceData.value.slice(0, 10).map((item) => item.nom),
  datasets: [
    {
      label: 'Taux de Service (%)',
      data: tauxServiceData.value.slice(0, 10).map((item) => parseFloat(item.taux_service_percent) || 0),
      backgroundColor: (context) => {
        const value = context.raw || 0
        if (value >= 90) return '#10B981'
        if (value >= 70) return '#F59E0B'
        return '#EF4444'
      },
      borderColor: '#ffffff',
      borderWidth: 1
    }
  ]
}))

const budgetData = computed(() => ({
  labels: depensesFournisseurs.value.slice(0, 8).map((item) => item.nom),
  datasets: [
    {
      label: 'Montant Total',
      data: depensesFournisseurs.value.slice(0, 8).map((item) => item.total_facture || 0),
      backgroundColor: '#8B5CF6',
      borderColor: '#6D28D9',
      borderWidth: 1
    },
    {
      label: 'Montant Payé',
      data: depensesFournisseurs.value.slice(0, 8).map((item) => item.total_paye || 0),
      backgroundColor: '#06B6D4',
      borderColor: '#0891B2',
      borderWidth: 1
    }
  ]
}))

// Chart Options
const chartOptions = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: {
      position: 'bottom',
      labels: {
        boxWidth: 12,
        padding: 20
      }
    }
  }
}

const delaiChartOptions = {
  responsive: true,
  maintainAspectRatio: false,
  indexAxis: 'y',
  plugins: {
    legend: {
      display: false
    }
  },
  scales: {
    x: {
      beginAtZero: true,
      grid: {
        display: false
      }
    },
    y: {
      grid: {
        display: false
      }
    }
  }
}

const tauxChartOptions = {
  responsive: true,
  maintainAspectRatio: false,
  indexAxis: 'y',
  plugins: {
    legend: {
      display: false
    }
  },
  scales: {
    x: {
      beginAtZero: true,
      max: 100,
      grid: {
        display: false
      }
    },
    y: {
      grid: {
        display: false
      }
    }
  }
}

const budgetChartOptions = {
  responsive: true,
  maintainAspectRatio: false,
  indexAxis: 'y',
  plugins: {
    legend: {
      position: 'bottom'
    }
  },
  scales: {
    x: {
      beginAtZero: true,
      grid: {
        display: false
      }
    },
    y: {
      grid: {
        display: false
      }
    }
  }
}

// Fetch data
const fetchData = async () => {
  loading.value = true
  error.value = null
  try {
    const params = new URLSearchParams()
    if (startDate.value) params.append('start_date', startDate.value)
    if (endDate.value) params.append('end_date', endDate.value)

    const response = await api.get(`/stats/achats?${params.toString()}`)

    if (response.data.success) {
      depensesFournisseurs.value = response.data.data.depenses_par_fournisseur || []
      delaiMoyenData.value = response.data.data.delai_moyen_livraison || []
      tauxServiceData.value = response.data.data.taux_service_fournisseur || []
    } else {
      error.value = response.data.message || 'Erreur lors du chargement des données'
    }
  } catch (err) {
    error.value = err.response?.data?.message || 'Erreur lors de la requête'
    console.error('API Error:', err)
  } finally {
    loading.value = false
  }
}

// Apply filters
const applyFilters = () => {
  fetchData()
}

// Lifecycle
onMounted(() => {
  initializeDates()
  fetchData()
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

.input-date {
  @apply px-3 py-2 rounded-lg border border-gray-300 bg-white text-sm text-gray-700 focus:outline-none focus:ring-2 focus:ring-gray-400;
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

.stat-subvalue {
  @apply text-xs text-gray-500;
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

/* Chart Container */
.chart-container {
  @apply relative h-72 mt-2;
}

/* Table Styles */
.table-container {
  @apply overflow-x-auto;
}

.data-table {
  @apply w-full text-sm;
}

.table-header {
  @apply px-4 py-3 text-left font-semibold text-gray-700 bg-gray-50 border-b border-gray-200;
}

.table-row {
  @apply border-b border-gray-100 hover:bg-gray-50;
}

.table-cell {
  @apply px-4 py-3;
}

/* Badges */
.badge {
  @apply inline-flex items-center px-3 py-1 rounded-full text-xs font-medium;
}

.badge-success {
  @apply bg-green-100 text-green-700;
}

.badge-warning {
  @apply bg-orange-100 text-orange-700;
}

.badge-error {
  @apply bg-red-100 text-red-700;
}

.empty-state-small {
  @apply text-center py-8 text-sm text-gray-500;
}

/* Buttons */
.btn-primary {
  @apply px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition-all;
}

.btn-secondary {
  @apply flex items-center gap-2 px-4 py-2 border border-gray-300 hover:bg-gray-50 text-gray-700 font-medium rounded-lg transition-all;
}
</style>