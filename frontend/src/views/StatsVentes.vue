<template>
  <div class="min-h-screen bg-gradient-to-br from-green-50 to-emerald-50 p-6">
    <!-- Header with Filters -->
    <div class="mb-8">
      <div class="flex justify-between items-center mb-6">
        <h1 class="text-4xl font-bold text-gray-900">📊 Statistiques Ventes</h1>
        <div class="flex gap-4 items-center">
          <div class="flex gap-2">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Date Début</label>
              <input
                v-model="startDate"
                type="date"
                class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500"
              />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Date Fin</label>
              <input
                v-model="endDate"
                type="date"
                class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500"
              />
            </div>
            <button
              @click="applyFilters"
              class="mt-6 px-6 py-2 bg-green-600 hover:bg-green-700 text-white font-medium rounded-lg transition"
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
        <div class="w-12 h-12 border-4 border-green-200 border-t-green-600 rounded-full animate-spin"></div>
        <p class="text-gray-500">Chargement des données...</p>
      </div>
    </div>

    <!-- Error State -->
    <div v-if="error && !loading" class="bg-red-50 border border-red-200 rounded-lg p-4 mb-6">
      <p class="text-red-700 font-medium">⚠️ {{ error }}</p>
    </div>

    <!-- KPI Cards -->
    <div v-if="!loading && !error" class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
      <!-- CA Total -->
      <div class="bg-white rounded-lg shadow-md p-6 border-l-4 border-green-500">
        <p class="text-gray-600 text-sm font-medium mb-2">💰 CA Total</p>
        <p class="text-3xl font-bold text-gray-900">{{ formatCurrency(caTotal) }}</p>
        <p class="text-xs text-gray-500 mt-2">Toutes sociétés confondues</p>
      </div>

      <!-- Nombre Factures -->
      <div class="bg-white rounded-lg shadow-md p-6 border-l-4 border-blue-500">
        <p class="text-gray-600 text-sm font-medium mb-2">📄 Factures</p>
        <p class="text-3xl font-bold text-gray-900">{{ totalFactures }}</p>
        <p class="text-xs text-gray-500 mt-2">Nombre total de factures</p>
      </div>

      <!-- Panier Moyen Global -->
      <div class="bg-white rounded-lg shadow-md p-6 border-l-4 border-purple-500">
        <p class="text-gray-600 text-sm font-medium mb-2">🛒 Panier Moyen</p>
        <p class="text-3xl font-bold text-gray-900">{{ formatCurrency(panierMoyenGlobal) }}</p>
        <p class="text-xs text-gray-500 mt-2">Montant moyen par facture</p>
      </div>

      <!-- Taux Conversion Moyen -->
      <div class="bg-white rounded-lg shadow-md p-6 border-l-4 border-orange-500">
        <p class="text-gray-600 text-sm font-medium mb-2">📈 Taux Conversion</p>
        <p class="text-3xl font-bold text-gray-900">{{ tauxConversionMoyen.toFixed(1) }}%</p>
        <p class="text-xs text-gray-500 mt-2">Devis → Facture</p>
      </div>
    </div>

    <!-- Charts Grid -->
    <div v-if="!loading && !error" class="grid grid-cols-1 lg:grid-cols-2 gap-6">
      <!-- CA par Société -->
      <div class="bg-white rounded-lg shadow-md p-6">
        <h2 class="text-xl font-bold text-gray-900 mb-4">🏢 CA par Société</h2>
        <div class="relative h-96">
          <Bar
            v-if="caSocieteData.labels.length > 0"
            :data="caSocieteData"
            :options="barChartOptions"
          />
          <div v-else class="flex items-center justify-center h-full text-gray-500">
            Aucune donnée disponible
          </div>
        </div>
      </div>

      <!-- Évolution CA Mensuel -->
      <div class="bg-white rounded-lg shadow-md p-6">
        <h2 class="text-xl font-bold text-gray-900 mb-4">📅 Évolution CA (12 mois)</h2>
        <div class="relative h-96">
          <Line
            v-if="evolutionCAData.labels.length > 0"
            :data="evolutionCAData"
            :options="lineChartOptions"
          />
          <div v-else class="flex items-center justify-center h-full text-gray-500">
            Aucune donnée disponible
          </div>
        </div>
      </div>

      <!-- Panier Moyen par Client -->
      <div class="bg-white rounded-lg shadow-md p-6">
        <h2 class="text-xl font-bold text-gray-900 mb-4">🛒 Top 10 Paniers Moyens</h2>
        <div class="relative h-96">
          <Bar
            v-if="panierMoyenData.labels.length > 0"
            :data="panierMoyenData"
            :options="panierChartOptions"
          />
          <div v-else class="flex items-center justify-center h-full text-gray-500">
            Aucune donnée disponible
          </div>
        </div>
      </div>

      <!-- Taux Conversion par Client -->
      <div class="bg-white rounded-lg shadow-md p-6">
        <h2 class="text-xl font-bold text-gray-900 mb-4">📊 Taux Conversion Devis/Facture</h2>
        <div class="relative h-96">
          <Bar
            v-if="tauxConversionData.labels.length > 0"
            :data="tauxConversionData"
            :options="tauxConversionChartOptions"
          />
          <div v-else class="flex items-center justify-center h-full text-gray-500">
            Aucune donnée disponible
          </div>
        </div>
      </div>
    </div>

    <!-- Detailed Tables -->
    <div v-if="!loading && !error" class="grid grid-cols-1 lg:grid-cols-2 gap-6 mt-6">
      <!-- Tableau CA par Société -->
      <div class="bg-white rounded-lg shadow-md overflow-hidden">
        <div class="bg-green-50 p-4 border-b border-green-200">
          <h3 class="text-lg font-bold text-gray-900">🏢 CA Détaillé par Société</h3>
        </div>
        <div class="overflow-x-auto">
          <table class="w-full text-sm">
            <thead class="bg-gray-50 border-b border-gray-200">
              <tr>
                <th class="px-4 py-3 text-left font-semibold text-gray-700">Société</th>
                <th class="px-4 py-3 text-right font-semibold text-gray-700">Factures</th>
                <th class="px-4 py-3 text-right font-semibold text-gray-700">CA Total</th>
                <th class="px-4 py-3 text-right font-semibold text-gray-700">CA Moyen</th>
              </tr>
            </thead>
            <tbody>
              <tr
                v-for="item in caSociete"
                :key="item.id"
                class="border-b border-gray-100 hover:bg-green-50"
              >
                <td class="px-4 py-3 font-medium text-gray-900">{{ item.societe }}</td>
                <td class="px-4 py-3 text-right text-gray-700">{{ item.nombre_factures }}</td>
                <td class="px-4 py-3 text-right text-green-600 font-semibold">
                  {{ formatCurrency(item.chiffre_affaires) }}
                </td>
                <td class="px-4 py-3 text-right text-gray-700">
                  {{ formatCurrency(item.ca_moyen_facture) }}
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Tableau Taux Conversion -->
      <div class="bg-white rounded-lg shadow-md overflow-hidden">
        <div class="bg-orange-50 p-4 border-b border-orange-200">
          <h3 class="text-lg font-bold text-gray-900">📈 Taux Conversion Détaillé</h3>
        </div>
        <div class="overflow-x-auto">
          <table class="w-full text-sm">
            <thead class="bg-gray-50 border-b border-gray-200">
              <tr>
                <th class="px-4 py-3 text-left font-semibold text-gray-700">Client</th>
                <th class="px-4 py-3 text-right font-semibold text-gray-700">Devis</th>
                <th class="px-4 py-3 text-right font-semibold text-gray-700">Factures</th>
                <th class="px-4 py-3 text-right font-semibold text-gray-700">Taux</th>
              </tr>
            </thead>
            <tbody>
              <tr
                v-for="item in tauxConversion"
                :key="item.id"
                class="border-b border-gray-100 hover:bg-orange-50"
              >
                <td class="px-4 py-3 font-medium text-gray-900">{{ item.client }}</td>
                <td class="px-4 py-3 text-right text-gray-700">{{ item.nombre_devis }}</td>
                <td class="px-4 py-3 text-right text-gray-700">{{ item.nombre_factures }}</td>
                <td class="px-4 py-3 text-right">
                  <span
                    :class="[
                      'font-semibold px-3 py-1 rounded-full text-sm',
                      item.taux_conversion >= 70
                        ? 'bg-green-100 text-green-700'
                        : item.taux_conversion >= 40
                          ? 'bg-yellow-100 text-yellow-700'
                          : 'bg-red-100 text-red-700'
                    ]"
                  >
                    {{ item.taux_conversion.toFixed(1) }}%
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
  const sum = tauxConversion.value.reduce((acc, item) => acc + item.taux_conversion, 0)
  return sum / tauxConversion.value.length
})

// Chart Data - CA par Société
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

// Chart Data - Évolution CA
const evolutionCAData = computed(() => ({
  labels: evolutionCA.value.map((item) => item.mois_libelle || item.mois),
  datasets: [
    {
      label: 'CA Mensuel',
      data: evolutionCA.value.map((item) => item.chiffre_affaires),
      backgroundColor: 'rgba(59, 130, 246, 0.2)',
      borderColor: '#3B82F6',
      borderWidth: 2,
      fill: true,
      tension: 0.4
    }
  ]
}))

// Chart Data - Panier Moyen
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

// Chart Data - Taux Conversion
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
      beginAtZero: true
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
      beginAtZero: true
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
      beginAtZero: true
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
      max: 100
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

    const response = await axios.get(`/api/stats/ventes?${params.toString()}`)

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
