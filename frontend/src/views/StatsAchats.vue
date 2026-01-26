<template>
  <div class="min-h-screen bg-gradient-to-br from-blue-50 to-indigo-50 p-6">
    <!-- Header with Filters -->
    <div class="mb-8">
      <div class="flex justify-between items-center mb-6">
        <h1 class="text-4xl font-bold text-gray-900">📊 Statistiques Achats</h1>
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

    <!-- Statistics Cards -->
    <div v-if="!loading && !error" class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
      <!-- Total Dépenses -->
      <div class="bg-white rounded-lg shadow-md p-6 border-l-4 border-blue-500">
        <p class="text-gray-600 text-sm font-medium mb-2">💰 Dépenses Totales</p>
        <p class="text-3xl font-bold text-gray-900">{{ formatCurrency(totalDepenses) }}</p>
        <p class="text-xs text-gray-500 mt-2">Montant total payé</p>
      </div>

      <!-- Fournisseur Top -->
      <div class="bg-white rounded-lg shadow-md p-6 border-l-4 border-green-500">
        <p class="text-gray-600 text-sm font-medium mb-2">🏆 Fournisseur Principal</p>
        <p class="text-2xl font-bold text-gray-900">{{ topFournisseur.nom }}</p>
        <p class="text-xs text-gray-500 mt-2">{{ formatCurrency(topFournisseur.total_paye) }} dépensé</p>
      </div>

      <!-- Meilleur Taux de Service -->
      <div class="bg-white rounded-lg shadow-md p-6 border-l-4 border-purple-500">
        <p class="text-gray-600 text-sm font-medium mb-2">⭐ Meilleur Taux Service</p>
        <p class="text-3xl font-bold text-gray-900">{{ meilleurTauxService.taux }}%</p>
        <p class="text-xs text-gray-500 mt-2">{{ meilleurTauxService.nom }}</p>
      </div>
    </div>

    <!-- Charts Grid -->
    <div v-if="!loading && !error" class="grid grid-cols-1 lg:grid-cols-2 gap-6">
      <!-- Répartition des Dépenses par Fournisseur -->
      <div class="bg-white rounded-lg shadow-md p-6">
        <h2 class="text-xl font-bold text-gray-900 mb-4">📈 Répartition des Dépenses</h2>
        <div class="relative h-96">
          <Doughnut
            v-if="depenseData.labels.length > 0"
            :data="depenseData"
            :options="chartOptions"
          />
          <div v-else class="flex items-center justify-center h-full text-gray-500">
            Aucune donnée disponible
          </div>
        </div>
      </div>

      <!-- Délai Moyen de Livraison -->
      <div class="bg-white rounded-lg shadow-md p-6">
        <h2 class="text-xl font-bold text-gray-900 mb-4">📅 Délai Moyen de Livraison (jours)</h2>
        <div class="relative h-96">
          <Bar
            v-if="delaiData.labels.length > 0"
            :data="delaiData"
            :options="delaiChartOptions"
          />
          <div v-else class="flex items-center justify-center h-full text-gray-500">
            Aucune donnée disponible
          </div>
        </div>
      </div>

      <!-- Taux de Service par Fournisseur -->
      <div class="bg-white rounded-lg shadow-md p-6">
        <h2 class="text-xl font-bold text-gray-900 mb-4">✅ Taux de Service (%)</h2>
        <div class="relative h-96">
          <Bar
            v-if="tauxData.labels.length > 0"
            :data="tauxData"
            :options="tauxChartOptions"
          />
          <div v-else class="flex items-center justify-center h-full text-gray-500">
            Aucune donnée disponible
          </div>
        </div>
      </div>

      <!-- Distribution Budget par Fournisseur (Horizontal Bar) -->
      <div class="bg-white rounded-lg shadow-md p-6">
        <h2 class="text-xl font-bold text-gray-900 mb-4">💳 Budget par Fournisseur</h2>
        <div class="relative h-96">
          <Bar
            v-if="budgetData.labels.length > 0"
            :data="budgetData"
            :options="budgetChartOptions"
          />
          <div v-else class="flex items-center justify-center h-full text-gray-500">
            Aucune donnée disponible
          </div>
        </div>
      </div>
    </div>

    <!-- Detailed Tables -->
    <div v-if="!loading && !error" class="grid grid-cols-1 lg:grid-cols-2 gap-6 mt-6">
      <!-- Tableau Dépenses Fournisseurs -->
      <div class="bg-white rounded-lg shadow-md overflow-hidden">
        <div class="bg-blue-50 p-4 border-b border-blue-200">
          <h3 class="text-lg font-bold text-gray-900">📋 Dépenses par Fournisseur</h3>
        </div>
        <div class="overflow-x-auto">
          <table class="w-full text-sm">
            <thead class="bg-gray-50 border-b border-gray-200">
              <tr>
                <th class="px-4 py-3 text-left font-semibold text-gray-700">Fournisseur</th>
                <th class="px-4 py-3 text-right font-semibold text-gray-700">Dépensé</th>
                <th class="px-4 py-3 text-right font-semibold text-gray-700">À Payer</th>
                <th class="px-4 py-3 text-right font-semibold text-gray-700">Factures</th>
              </tr>
            </thead>
            <tbody>
              <tr
                v-for="item in depensesFournisseurs"
                :key="item.id"
                class="border-b border-gray-100 hover:bg-blue-50"
              >
                <td class="px-4 py-3 font-medium text-gray-900">{{ item.nom }}</td>
                <td class="px-4 py-3 text-right text-blue-600 font-semibold">
                  {{ formatCurrency(item.total_paye) }}
                </td>
                <td class="px-4 py-3 text-right text-red-600">
                  {{ formatCurrency(item.reste_a_payer) }}
                </td>
                <td class="px-4 py-3 text-right text-gray-700">{{ item.nombre_factures }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Tableau Taux Service -->
      <div class="bg-white rounded-lg shadow-md overflow-hidden">
        <div class="bg-purple-50 p-4 border-b border-purple-200">
          <h3 class="text-lg font-bold text-gray-900">📊 Taux de Service</h3>
        </div>
        <div class="overflow-x-auto">
          <table class="w-full text-sm">
            <thead class="bg-gray-50 border-b border-gray-200">
              <tr>
                <th class="px-4 py-3 text-left font-semibold text-gray-700">Fournisseur</th>
                <th class="px-4 py-3 text-right font-semibold text-gray-700">BC</th>
                <th class="px-4 py-3 text-right font-semibold text-gray-700">FA Reçues</th>
                <th class="px-4 py-3 text-right font-semibold text-gray-700">Taux</th>
              </tr>
            </thead>
            <tbody>
              <tr
                v-for="item in tauxServiceData"
                :key="item.id"
                class="border-b border-gray-100 hover:bg-purple-50"
              >
                <td class="px-4 py-3 font-medium text-gray-900">{{ item.nom }}</td>
                <td class="px-4 py-3 text-right text-gray-700">{{ item.nombre_bc }}</td>
                <td class="px-4 py-3 text-right text-gray-700">
                  {{ item.nombre_factures_recues }}
                </td>
                <td class="px-4 py-3 text-right">
                  <span
                    :class="[
                      'font-semibold px-3 py-1 rounded-full text-sm',
                      item.taux_service_percent >= 90
                        ? 'bg-green-100 text-green-700'
                        : item.taux_service_percent >= 70
                          ? 'bg-yellow-100 text-yellow-700'
                          : 'bg-red-100 text-red-700'
                    ]"
                  >
                    {{ item.taux_service_percent.toFixed(1) }}%
                  </span>
                </td>
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
const depensesFournisseurs = ref([])
const delaiMoyenData = ref([])
const tauxServiceData = ref([])

// Initialize dates to last 30 days
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
    currency: 'XOF'
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

// Chart Data - Dépenses (Doughnut)
const depenseData = computed(() => ({
  labels: depensesFournisseurs.value.map((item) => item.nom),
  datasets: [
    {
      label: 'Montant Dépensé',
      data: depensesFournisseurs.value.map((item) => item.total_paye),
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

// Chart Data - Délai Moyen
const delaiData = computed(() => ({
  labels: delaiMoyenData.value.map((item) => item.nom),
  datasets: [
    {
      label: 'Délai Moyen (jours)',
      data: delaiMoyenData.value.map((item) => parseFloat(item.delai_moyen_jours) || 0),
      backgroundColor: '#60A5FA',
      borderColor: '#3B82F6',
      borderWidth: 1
    }
  ]
}))

// Chart Data - Taux Service
const tauxData = computed(() => ({
  labels: tauxServiceData.value.map((item) => item.nom),
  datasets: [
    {
      label: 'Taux de Service (%)',
      data: tauxServiceData.value.map((item) => parseFloat(item.taux_service_percent) || 0),
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

// Chart Data - Budget (Horizontal Bar)
const budgetData = computed(() => ({
  labels: depensesFournisseurs.value.map((item) => item.nom),
  datasets: [
    {
      label: 'Montant Total',
      data: depensesFournisseurs.value.map((item) => item.total_facture || 0),
      backgroundColor: '#8B5CF6',
      borderColor: '#6D28D9',
      borderWidth: 1
    },
    {
      label: 'Montant Payé',
      data: depensesFournisseurs.value.map((item) => item.total_paye || 0),
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
      position: 'bottom'
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
      beginAtZero: true
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
      max: 100
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

    const response = await axios.get(`/api/stats/achats?${params.toString()}`)

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
