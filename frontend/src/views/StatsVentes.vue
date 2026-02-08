<template>
  <div class="page-container">
    <!-- Loading state -->
    <div v-if="loading" class="loading-state">
      <div class="spinner"></div>
      <p class="loading-text">Chargement des statistiques ventes...</p>
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
          <h1 class="page-title">📊 Statistiques Ventes</h1>
          <p class="page-subtitle">Analyse détaillée de vos ventes et clients</p>
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
            <div class="stat-icon bg-green-100 text-green-600">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
              </svg>
            </div>
          </div>
          <div class="stat-content">
            <p class="stat-label">CA Total</p>
            <h3 class="stat-value">{{ formatCurrency(caTotal) }}</h3>
            <p class="stat-subvalue">Toutes sociétés</p>
          </div>
        </div>

        <div class="stat-card">
          <div class="stat-header">
            <div class="stat-icon bg-blue-100 text-blue-600">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
              </svg>
            </div>
          </div>
          <div class="stat-content">
            <p class="stat-label">Factures</p>
            <h3 class="stat-value">{{ totalFactures }}</h3>
            <p class="stat-subvalue">Nombre total</p>
          </div>
        </div>

        <div class="stat-card">
          <div class="stat-header">
            <div class="stat-icon bg-purple-100 text-purple-600">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path>
              </svg>
            </div>
          </div>
          <div class="stat-content">
            <p class="stat-label">Panier Moyen</p>
            <h3 class="stat-value">{{ formatCurrency(panierMoyenGlobal) }}</h3>
            <p class="stat-subvalue">Moyenne par facture</p>
          </div>
        </div>

        <div class="stat-card">
          <div class="stat-header">
            <div class="stat-icon bg-orange-100 text-orange-600">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
              </svg>
            </div>
          </div>
          <div class="stat-content">
            <p class="stat-label">Taux Conversion</p>
            <h3 class="stat-value">{{ (Number(tauxConversionMoyen) || 0).toFixed(1) }}%</h3>
            <p class="stat-subvalue">Devis → Facture</p>
          </div>
        </div>
      </div>

      <!-- Charts Grid -->
      <div class="activities-grid fade-in" style="animation-delay: 0.25s">
        <!-- CA par Société -->
        <div class="card">
          <div class="card-header">
            <h2 class="card-title">🏢 CA par Société</h2>
          </div>
          <div class="chart-container">
            <Bar
              v-if="caSocieteData.labels.length > 0"
              :data="caSocieteData"
              :options="barChartOptions"
            />
            <div v-else class="empty-state-small">
              <p>Aucune donnée disponible</p>
            </div>
          </div>
        </div>

        <!-- Évolution CA -->
        <div class="card">
          <div class="card-header">
            <h2 class="card-title">📅 Évolution CA (12 mois)</h2>
          </div>
          <div class="chart-container">
            <Line
              v-if="evolutionCAData.labels.length > 0"
              :data="evolutionCAData"
              :options="lineChartOptions"
            />
            <div v-else class="empty-state-small">
              <p>Aucune donnée disponible</p>
            </div>
          </div>
        </div>
      </div>

      <!-- Second Charts Row -->
      <div class="activities-grid fade-in" style="animation-delay: 0.3s">
        <!-- Panier Moyen -->
        <div class="card">
          <div class="card-header">
            <h2 class="card-title">🛒 Top 10 Paniers Moyens</h2>
          </div>
          <div class="chart-container">
            <Bar
              v-if="panierMoyenData.labels.length > 0"
              :data="panierMoyenData"
              :options="panierChartOptions"
            />
            <div v-else class="empty-state-small">
              <p>Aucune donnée disponible</p>
            </div>
          </div>
        </div>

        <!-- Taux Conversion -->
        <div class="card">
          <div class="card-header">
            <h2 class="card-title">📊 Taux Conversion</h2>
          </div>
          <div class="chart-container">
            <Bar
              v-if="tauxConversionData.labels.length > 0"
              :data="tauxConversionData"
              :options="tauxConversionChartOptions"
            />
            <div v-else class="empty-state-small">
              <p>Aucune donnée disponible</p>
            </div>
          </div>
        </div>
      </div>

      <!-- Detailed Tables -->
      <div class="activities-grid fade-in" style="animation-delay: 0.35s">
        <!-- CA par Société -->
        <div class="card">
          <div class="card-header">
            <h2 class="card-title">🏢 CA Détaillé par Société</h2>
          </div>
          <div class="table-container">
            <table class="data-table">
              <thead>
                <tr>
                  <th class="table-header">Société</th>
                  <th class="table-header text-right">Factures</th>
                  <th class="table-header text-right">CA Total</th>
                  <th class="table-header text-right">CA Moyen</th>
                </tr>
              </thead>
              <tbody>
                <tr
                  v-for="item in caSociete"
                  :key="item.id || item.societe"
                  class="table-row"
                >
                  <td class="table-cell font-medium">{{ item.societe }}</td>
                  <td class="table-cell text-right">{{ item.nombre_factures }}</td>
                  <td class="table-cell text-right text-green-600 font-semibold">
                    {{ formatCurrency(item.chiffre_affaires) }}
                  </td>
                  <td class="table-cell text-right">
                    {{ formatCurrency(item.ca_moyen_facture) }}
                  </td>
                </tr>
              </tbody>
            </table>
            <div v-if="caSociete.length === 0" class="empty-state-small">
              <p>Aucune donnée disponible</p>
            </div>
          </div>
        </div>

        <!-- Taux Conversion -->
        <div class="card">
          <div class="card-header">
            <h2 class="card-title">📈 Taux Conversion Détaillé</h2>
          </div>
          <div class="table-container">
            <table class="data-table">
              <thead>
                <tr>
                  <th class="table-header">Client</th>
                  <th class="table-header text-right">Devis</th>
                  <th class="table-header text-right">Factures</th>
                  <th class="table-header text-right">Taux</th>
                </tr>
              </thead>
              <tbody>
                <tr
                  v-for="item in tauxConversion"
                  :key="item.id || item.client"
                  class="table-row"
                >
                  <td class="table-cell font-medium">{{ item.client }}</td>
                  <td class="table-cell text-right">{{ item.nombre_devis }}</td>
                  <td class="table-cell text-right">{{ item.nombre_factures }}</td>
                  <td class="table-cell text-right">
                    <span
                      :class="[
                        'badge',
                        item.taux_conversion >= 70
                          ? 'badge-success'
                          : item.taux_conversion >= 40
                            ? 'badge-warning'
                            : 'badge-error'
                      ]"
                    >
                      {{ (Number(item.taux_conversion) || 0).toFixed(1) }}%
                    </span>
                  </td>
                </tr>
              </tbody>
            </table>
            <div v-if="tauxConversion.length === 0" class="empty-state-small">
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
import { Bar, Line } from 'vue-chartjs'
import {
  Chart as ChartJS,
  CategoryScale,
  LinearScale,
  PointElement,
  LineElement,
  BarElement,
  Title,
  Tooltip,
  Legend
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
  Legend
)

// State
const loading = ref(false)
const error = ref(null)
const startDate = ref('')
const endDate = ref('')

// Data
const caSociete = ref([])
const evolutionCA = ref([])
const panierMoyen = ref([])
const tauxConversion = ref([])

// Initialize dates
const initializeDates = () => {
  const today = new Date()
  const thirtyDaysAgo = new Date(today.getTime() - 30 * 24 * 60 * 60 * 1000)
  endDate.value = today.toISOString().split('T')[0]
  startDate.value = thirtyDaysAgo.toISOString().split('T')[0]
}

// Format currency
const formatCurrency = (value) => {
  const num = Number(value)
  return new Intl.NumberFormat('fr-MG', {
    style: 'currency',
    currency: 'MGA',
    minimumFractionDigits: 0
  }).format(isNaN(num) ? 0 : num)
}

// Computed KPIs
const caTotal = computed(() => {
  return caSociete.value.reduce((sum, item) => sum + (item.chiffre_affaires || 0), 0)
})

const totalFactures = computed(() => {
  return caSociete.value.reduce((sum, item) => sum + (item.nombre_factures || 0), 0)
})

const panierMoyenGlobal = computed(() => {
  return totalFactures.value > 0 ? caTotal.value / totalFactures.value : 0
})

const tauxConversionMoyen = computed(() => {
  if (tauxConversion.value.length === 0) return 0
  const sum = tauxConversion.value.reduce((acc, item) => acc + (item.taux_conversion || 0), 0)
  return sum / tauxConversion.value.length
})

// Chart Data
const caSocieteData = computed(() => ({
  labels: caSociete.value.map((item) => item.societe),
  datasets: [
    {
      label: 'Chiffre d\'Affaires',
      data: caSociete.value.map((item) => item.chiffre_affaires),
      backgroundColor: '#10B981',
      borderColor: '#059669',
      borderWidth: 1
    }
  ]
}))

const evolutionCAData = computed(() => ({
  labels: evolutionCA.value.map((item) => item.mois_libelle || item.mois),
  datasets: [
    {
      label: 'CA Mensuel',
      data: evolutionCA.value.map((item) => item.chiffre_affaires),
      backgroundColor: 'rgba(59, 130, 246, 0.1)',
      borderColor: '#3B82F6',
      borderWidth: 2,
      fill: true,
      tension: 0.4
    }
  ]
}))

const panierMoyenData = computed(() => ({
  labels: panierMoyen.value.slice(0, 10).map((item) => item.client),
  datasets: [
    {
      label: 'Panier Moyen',
      data: panierMoyen.value.slice(0, 10).map((item) => item.panier_moyen),
      backgroundColor: '#8B5CF6',
      borderColor: '#7C3AED',
      borderWidth: 1
    }
  ]
}))

const tauxConversionData = computed(() => ({
  labels: tauxConversion.value.slice(0, 10).map((item) => item.client),
  datasets: [
    {
      label: 'Taux de Conversion (%)',
      data: tauxConversion.value.slice(0, 10).map((item) => item.taux_conversion),
      backgroundColor: (context) => {
        const value = context.raw || 0
        if (value >= 70) return '#10B981'
        if (value >= 40) return '#F59E0B'
        return '#EF4444'
      },
      borderColor: '#ffffff',
      borderWidth: 1
    }
  ]
}))

// Chart Options
const barChartOptions = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: {
      display: false
    }
  },
  scales: {
    y: {
      beginAtZero: true,
      grid: {
        display: false
      }
    },
    x: {
      grid: {
        display: false
      }
    }
  }
}

const lineChartOptions = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: {
      display: false
    }
  },
  scales: {
    y: {
      beginAtZero: true,
      grid: {
        display: false
      }
    },
    x: {
      grid: {
        display: false
      }
    }
  }
}

const panierChartOptions = {
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

const tauxConversionChartOptions = {
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

// Fetch data
const fetchData = async () => {
  loading.value = true
  error.value = null
  try {
    const params = new URLSearchParams()
    if (startDate.value) params.append('start_date', startDate.value)
    if (endDate.value) params.append('end_date', endDate.value)
    params.append('nb_mois', '12')

    const response = await api.get(`/stats/ventes?${params.toString()}`)

    if (response.data.success) {
      caSociete.value = response.data.data.ca_par_societe || []
      evolutionCA.value = response.data.data.evolution_ca || []
      panierMoyen.value = response.data.data.panier_moyen || []
      tauxConversion.value = response.data.data.taux_conversion || []
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