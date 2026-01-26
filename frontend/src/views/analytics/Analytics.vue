<template>
  <div class="page-container">
    <div class="page-header fade-in" style="animation-delay: 0.1s">
      <div>
        <h1 class="page-title">Analytique</h1>
        <p class="page-subtitle">Visualisations des indicateurs clés</p>
      </div>
      <div class="header-actions">
        <div class="flex items-center gap-2">
          <input type="date" v-model="startDate" class="input-date" />
          <span class="text-gray-400">à</span>
          <input type="date" v-model="endDate" class="input-date" />
        </div>
        <button @click="loadStats" class="btn-secondary">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
          </svg>
          <span>Appliquer</span>
        </button>
      </div>
    </div>

    <div v-if="loading" class="loading-state">
      <div class="spinner"></div>
      <p class="loading-text">Chargement des données...</p>
    </div>

    <div v-else-if="error" class="error-state">
      <div class="error-icon">
        <svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
      </div>
      <p class="error-message">{{ error }}</p>
    </div>

    <div v-else class="space-y-6">
      <!-- KPI Cards: CA, Marge, Rentabilité -->
      <div class="stats-grid fade-in" style="animation-delay: 0.2s">
        <div class="stat-card">
          <p class="stat-label">Chiffre d'affaires</p>
          <h3 class="stat-value">{{ formatCurrency(stats.totalCA) }}</h3>
        </div>
        <div class="stat-card">
          <p class="stat-label">Marge brute</p>
          <h3 class="stat-value">{{ formatCurrency(stats.margeBrute) }}</h3>
        </div>
        <div class="stat-card">
          <p class="stat-label">Rentabilité</p>
          <h3 class="stat-value">{{ formatPercent(stats.tauxRentabilite) }}</h3>
        </div>
      </div>

      <!-- Gauge (Doughnut) for Rentabilité -->
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 fade-in" style="animation-delay: 0.25s">
        <div class="card p-4">
          <h2 class="card-title">Taux de rentabilité</h2>
          <Doughnut :data="gaugeData" :options="gaugeOptions" class="h-64" />
        </div>

        <!-- Top 5 Clients: Horizontal Bar -->
        <div class="card p-4 lg:col-span-1">
          <h2 class="card-title">Top 5 Clients (par nombre)</h2>
          <Bar :data="topClientsData" :options="horizontalBarOptions" class="h-64" />
        </div>

        <!-- Top 5 Articles: Column Bar -->
        <div class="card p-4 lg:col-span-1">
          <h2 class="card-title">Top 5 Articles (par quantités)</h2>
          <Bar :data="topArticlesData" :options="verticalBarOptions" class="h-64" />
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import api from '@/services/api/api'
import { Bar, Doughnut } from 'vue-chartjs'
import { Chart, registerables } from 'chart.js'
Chart.register(...registerables)

const loading = ref(false)
const error = ref(null)
const stats = ref({ totalCA: 0, margeBrute: 0, tauxRentabilite: 0 })
const topClients = ref([])
const topArticles = ref([])
const startDate = ref('')
const endDate = ref('')

const formatCurrency = (amount) => new Intl.NumberFormat('fr-MG', { style: 'currency', currency: 'MGA', minimumFractionDigits: 0 }).format(Number(amount) || 0)
const formatPercent = (value) => `${(Number(value) || 0).toFixed(1)} %`

const loadStats = async () => {
  loading.value = true
  error.value = null
  try {
    const params = {}
    if (startDate.value && endDate.value) {
      params['periode'] = [startDate.value, endDate.value]
    }
    const { data } = await api.get('/dashboard/stats', { params })
    stats.value = {
      totalCA: data.totalCA ?? data.total_ca ?? 0,
      margeBrute: data.margeBrute ?? data.marge_brute ?? 0,
      tauxRentabilite: data.tauxRentabilite ?? data.taux_rentabilite ?? 0
    }
    topClients.value = data.topClients ?? data.top_clients ?? []
    topArticles.value = data.topArticles ?? data.top_articles ?? []
  } catch (e) {
    console.error(e)
    error.value = 'Erreur lors du chargement des statistiques.'
  } finally {
    loading.value = false
  }
}

onMounted(loadStats)

// Gauge chart for rentability (0-100%)
const gaugeData = computed(() => {
  const pct = Math.max(0, Math.min(100, Number(stats.value.tauxRentabilite) || 0))
  return {
    labels: ['Rentabilité', 'Reste'],
    datasets: [{
      data: [pct, 100 - pct],
      backgroundColor: ['#10b981', '#e5e7eb'],
      borderWidth: 0
    }]
  }
})

const gaugeOptions = {
  cutout: '70%',
  rotation: -90,
  circumference: 180,
  plugins: {
    legend: { display: false },
    tooltip: { enabled: true }
  }
}

// Top Clients: horizontal bar by total
const topClientsData = computed(() => {
  const labels = topClients.value.map(c => c.nom || c.name || 'Client')
  const values = topClients.value.map(c => Number(c.factures ?? c.nb_achats ?? 0))
  return {
    labels,
    datasets: [{
      label: "Nombre d'achats",
      data: values,
      backgroundColor: '#3b82f6'
    }]
  }
})

const horizontalBarOptions = {
  indexAxis: 'y',
  plugins: { legend: { display: false } },
  scales: {
    x: { ticks: { callback: (v) => new Intl.NumberFormat('fr-MG').format(v) } },
    y: { ticks: { autoSkip: false } }
  }
}

// Top Articles: vertical bar by montant
const topArticlesData = computed(() => {
  const labels = topArticles.value.map(a => a.designation || a.nom || a.reference || 'Article')
  const values = topArticles.value.map(a => Number(a.quantite ?? a.qty ?? 0))
  return {
    labels,
    datasets: [{
      label: 'Quantité vendue',
      data: values,
      backgroundColor: '#f59e0b'
    }]
  }
})

const verticalBarOptions = {
  plugins: { legend: { display: false } },
  scales: {
    y: { ticks: { callback: (v) => new Intl.NumberFormat('fr-MG').format(v) }, beginAtZero: true }
  }
}
</script>

<style scoped>
.page-container { @apply min-h-screen ml-64 p-6 bg-gray-50; }
.page-header { @apply flex items-center justify-between mb-6; }
.page-title { @apply text-2xl font-bold text-gray-900; }
.page-subtitle { @apply text-sm text-gray-500 mt-1; }
.header-actions { @apply flex items-center gap-3; }
.input-date { @apply px-3 py-2 rounded-lg border border-gray-300 bg-white text-sm text-gray-700 focus:outline-none focus:ring-2 focus:ring-gray-400; }

.stats-grid { @apply grid grid-cols-1 md:grid-cols-3 gap-4; }
.stat-card { @apply bg-white rounded-lg border border-gray-200 p-5; }
.stat-label { @apply text-xs text-gray-500 uppercase tracking-wide font-medium; }
.stat-value { @apply text-xl font-bold text-gray-900; }

.card-title { @apply text-base font-semibold text-gray-900 mb-3; }
.card { @apply bg-white rounded-lg border border-gray-200; }

.loading-state { @apply flex flex-col items-center justify-center py-20; }
.spinner { @apply w-12 h-12 border-4 border-gray-200 border-t-gray-900 rounded-full; animation: spin 1s linear infinite; }
@keyframes spin { to { transform: rotate(360deg); } }
.loading-text { @apply mt-4 text-sm text-gray-600; }

.error-state { @apply flex flex-col items-center justify-center py-20; }
.error-icon { @apply text-red-600 mb-4; }
.error-message { @apply text-sm text-red-600 mb-4; }

@media (max-width: 1024px) { .page-container { @apply ml-20 p-4; } }
@media (max-width: 768px) { .page-container { @apply ml-0 p-4; } }
</style>
