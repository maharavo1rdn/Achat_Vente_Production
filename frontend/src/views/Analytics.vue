<template>
  <div class="min-h-screen bg-gradient-to-br from-blue-50 to-indigo-50 p-6">
    <!-- Header with Filters -->
    <div class="mb-8">
      <div class="flex justify-between items-center mb-6">
        <h1 class="text-4xl font-bold text-gray-900">📊 Analytique Générale</h1>
        <div class="flex gap-4 items-center">
          <div class="flex gap-2">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Date Début</label>
              <input
                v-model="startDate"
                type="date"
                class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
              />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Date Fin</label>
              <input
                v-model="endDate"
                type="date"
                class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
              />
            </div>
            <button
              @click="applyFilters"
              class="mt-6 px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition"
            >
              Appliquer
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="flex justify-center items-center h-96">
      <div class="flex flex-col items-center gap-4">
        <div class="w-12 h-12 border-4 border-blue-200 border-t-blue-600 rounded-full animate-spin"></div>
        <p class="text-gray-500">Chargement des données...</p>
      </div>
    </div>

    <!-- Error State -->
    <div v-if="error && !loading" class="bg-red-50 border border-red-200 rounded-lg p-4 mb-6">
      <p class="text-red-700 font-medium">⚠️ {{ error }}</p>
    </div>

    <!-- KPI Cards -->
    <div v-if="!loading && !error" class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
      <div class="bg-white rounded-lg shadow-md p-6 border-l-4 border-blue-500">
        <p class="text-gray-600 text-sm font-medium mb-2">💰 Chiffre d'Affaires Total</p>
        <p class="text-3xl font-bold text-gray-900">{{ formatCurrency(stats.totalCA) }}</p>
        <p class="text-xs text-gray-500 mt-2">Montant des ventes validées</p>
      </div>

      <div class="bg-white rounded-lg shadow-md p-6 border-l-4 border-green-500">
        <p class="text-gray-600 text-sm font-medium mb-2">📈 Marge Brute</p>
        <p class="text-3xl font-bold text-gray-900">{{ formatCurrency(stats.margeBrute) }}</p>
        <p class="text-xs text-gray-500 mt-2">CA - Coût Achats</p>
      </div>

      <div class="bg-white rounded-lg shadow-md p-6 border-l-4 border-purple-500">
        <p class="text-gray-600 text-sm font-medium mb-2">📊 Taux Rentabilité</p>
        <p class="text-3xl font-bold text-gray-900">{{ (Number(stats.tauxRentabilite) || 0).toFixed(2) }}%</p>
        <p class="text-xs text-gray-500 mt-2">(Marge / CA) × 100</p>
      </div>
    </div>

    <!-- Charts -->
    <div v-if="!loading && !error" class="grid grid-cols-1 lg:grid-cols-2 gap-6">
      <!-- Rentabilité Chart -->
      <div class="bg-white rounded-lg shadow-md p-6">
        <h2 class="text-xl font-bold text-gray-900 mb-4">💎 Composition du Résultat</h2>
        <div class="relative h-96">
          <Doughnut
            v-if="rentabiliteData.labels.length > 0"
            :data="rentabiliteData"
            :options="chartOptions"
          />
          <div v-else class="flex items-center justify-center h-full text-gray-500">
            Aucune donnée disponible
          </div>
        </div>
      </div>

      <!-- Top Clients Chart -->
      <div class="bg-white rounded-lg shadow-md p-6">
        <h2 class="text-xl font-bold text-gray-900 mb-4">👥 Top 5 Clients par Nombre de Factures</h2>
        <div class="relative h-96">
          <Bar
            v-if="topClientsData.labels.length > 0"
            :data="topClientsData"
            :options="topClientsChartOptions"
          />
          <div v-else class="flex items-center justify-center h-full text-gray-500">
            Aucune donnée disponible
          </div>
        </div>
      </div>

      <!-- Top Articles Chart -->
      <div class="bg-white rounded-lg shadow-md p-6 lg:col-span-2">
        <h2 class="text-xl font-bold text-gray-900 mb-4">📦 Top 5 Articles par Quantité Vendue</h2>
        <div class="relative h-96">
          <Bar
            v-if="topArticlesData.labels.length > 0"
            :data="topArticlesData"
            :options="topArticlesChartOptions"
          />
          <div v-else class="flex items-center justify-center h-full text-gray-500">
            Aucune donnée disponible
          </div>
        </div>
      </div>
    </div>

    <!-- Detail Tables -->
    <div v-if="!loading && !error" class="grid grid-cols-1 lg:grid-cols-2 gap-6 mt-6">
      <!-- Top Clients Table -->
      <div class="bg-white rounded-lg shadow-md overflow-hidden">
        <div class="bg-blue-50 p-4 border-b border-blue-200">
          <h3 class="text-lg font-bold text-gray-900">👥 Top 5 Clients</h3>
        </div>
        <div class="overflow-x-auto">
          <table class="w-full text-sm">
            <thead class="bg-gray-50 border-b border-gray-200">
              <tr>
                <th class="px-4 py-3 text-left font-semibold text-gray-700">Client</th>
                <th class="px-4 py-3 text-right font-semibold text-gray-700">Factures</th>
                <th class="px-4 py-3 text-right font-semibold text-gray-700">Montant Total</th>
              </tr>
            </thead>
            <tbody>
              <tr
                v-for="client in stats.topClients"
                :key="client.id"
                class="border-b border-gray-100 hover:bg-blue-50"
              >
                <td class="px-4 py-3 font-medium text-gray-900">{{ client.nom }}</td>
                <td class="px-4 py-3 text-right text-blue-600 font-semibold">{{ client.factures }}</td>
                <td class="px-4 py-3 text-right text-gray-700">{{ formatCurrency(client.montant_total) }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Top Articles Table -->
      <div class="bg-white rounded-lg shadow-md overflow-hidden">
        <div class="bg-orange-50 p-4 border-b border-orange-200">
          <h3 class="text-lg font-bold text-gray-900">📦 Top 5 Articles</h3>
        </div>
        <div class="overflow-x-auto">
          <table class="w-full text-sm">
            <thead class="bg-gray-50 border-b border-gray-200">
              <tr>
                <th class="px-4 py-3 text-left font-semibold text-gray-700">Article</th>
                <th class="px-4 py-3 text-right font-semibold text-gray-700">Quantité</th>
                <th class="px-4 py-3 text-right font-semibold text-gray-700">Montant</th>
              </tr>
            </thead>
            <tbody>
              <tr
                v-for="article in stats.topArticles"
                :key="article.id"
                class="border-b border-gray-100 hover:bg-orange-50"
              >
                <td class="px-4 py-3 font-medium text-gray-900">{{ article.designation }}</td>
                <td class="px-4 py-3 text-right text-orange-600 font-semibold">{{ article.quantite }}</td>
                <td class="px-4 py-3 text-right text-gray-700">{{ formatCurrency(article.montant) }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import axios from 'axios'
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
const stats = ref({
  totalCA: 0,
  margeBrute: 0,
  tauxRentabilite: 0,
  topClients: [],
  topArticles: []
})

// Initialize dates to last 30 days
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

// Chart Data - Rentabilité (Doughnut)
const rentabiliteData = computed(() => ({
  labels: ['Marge Brute', 'Coût Achats'],
  datasets: [
    {
      data: [
        stats.value.margeBrute,
        stats.value.totalCA - stats.value.margeBrute
      ],
      backgroundColor: ['#10B981', '#EF4444'],
      borderColor: '#ffffff',
      borderWidth: 2
    }
  ]
}))

// Chart Data - Top Clients (Horizontal Bar)
const topClientsData = computed(() => ({
  labels: stats.value.topClients.map((c) => c.nom),
  datasets: [
    {
      label: 'Nombre de Factures',
      data: stats.value.topClients.map((c) => c.factures),
      backgroundColor: '#3B82F6',
      borderColor: '#1E40AF',
      borderWidth: 1
    }
  ]
}))

// Chart Data - Top Articles (Vertical Bar)
const topArticlesData = computed(() => ({
  labels: stats.value.topArticles.map((a) => a.designation?.substring(0, 15) || 'N/A'),
  datasets: [
    {
      label: 'Quantité Vendue',
      data: stats.value.topArticles.map((a) => a.quantite),
      backgroundColor: '#F59E0B',
      borderColor: '#D97706',
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
      position: 'bottom'
    }
  }
}

const topClientsChartOptions = {
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
      beginAtZero: true
    }
  }
}

const topArticlesChartOptions = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: {
      display: false
    }
  },
  scales: {
    y: {
      beginAtZero: true
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

    const response = await axios.get(`/api/dashboard/stats?${params.toString()}`)

    if (response.data) {
      stats.value = {
        totalCA: response.data.totalCA || 0,
        margeBrute: response.data.margeBrute || 0,
        tauxRentabilite: response.data.tauxRentabilite || 0,
        topClients: response.data.topClients || [],
        topArticles: response.data.topArticles || []
      }
    } else {
      error.value = 'Erreur lors du chargement des données'
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
