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
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
            d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
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
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15">
              </path>
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

        <!-- Rotation Sparkline Card -->
        <div class="stat-card">
          <div class="stat-header">
            <div class="stat-icon bg-indigo-100 text-indigo-600">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4" />
              </svg>
            </div>
            <span class="text-xs font-medium text-indigo-600">Moyenne: {{ formatPercent(stockStats.tauxRotationMoyenne) }}</span>
          </div>
          <div class="stat-content">
            <p class="stat-label">Rotation des stocks</p>
            <div class="h-16 mt-2">
              <Line :data="chartData" :options="{ ...chartOptions, scales: { x: { display: false }, y: { display: false } }, plugins: { legend: { display: false }, tooltip: { enabled: true } } }" />
            </div>
          </div>
        </div>

        <div class="stat-card">
          <div class="stat-header">
            <div class="stat-icon bg-yellow-100 text-yellow-600">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
            </div>
          </div>
          <div class="stat-content">
            <p class="stat-label">Durée moyenne stockage</p>
            <h3 class="stat-value">{{ stockStats.dureeMoyenne }} jours</h3>
          </div>
        </div>
      </div>

      <!-- Rotation Detailed Section -->
      <div class="card fade-in" style="animation-delay: 0.25s">
        <div class="card-header">
          <h2 class="card-title">Analyse détaillée de la rotation des stocks</h2>
        </div>
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
          <div class="lg:col-span-2 h-80">
            <Line :data="chartData" :options="chartOptions" />
          </div>
          <div class="overflow-x-auto border rounded-lg">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
              <thead class="bg-gray-50">
                <tr>
                  <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Article</th>
                  <th class="px-3 py-2 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Sorties</th>
                  <th class="px-3 py-2 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Stock</th>
                  <th class="px-3 py-2 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Taux</th>
                </tr>
              </thead>
              <tbody class="bg-white divide-y divide-gray-200">
                <tr v-for="(item, index) in stockStats.rotationData" :key="index" class="hover:bg-gray-50 transition-colors">
                  <td class="px-3 py-3 whitespace-nowrap">
                    <div class="font-medium text-gray-900 truncate max-w-[150px]" :title="item.designation">
                      {{ item.designation }}
                    </div>
                    <div class="text-xs text-gray-500">{{ item.categorie }}</div>
                  </td>
                  <td class="px-3 py-3 text-right text-gray-600">{{ item.total_sorties }}</td>
                  <td class="px-3 py-3 text-right text-gray-600">{{ item.stock_actuel }}</td>
                  <td class="px-3 py-3 text-right">
                    <span :class="[
                      'px-2 py-1 rounded text-xs font-bold',
                      item.taux_rotation >= 100 ? 'bg-green-100 text-green-700' : 
                      item.taux_rotation > 0 ? 'bg-blue-100 text-blue-700' : 'bg-gray-100 text-gray-500'
                    ]">
                      {{ item.taux_rotation.toFixed(1) }}%
                    </span>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- Statistiques Financières Section -->
      <div class="space-y-6 fade-in" style="animation-delay: 0.28s">
        <div class="flex items-center justify-between border-b pb-2">
          <h2 class="text-xl font-bold text-gray-800">Statistiques Financières</h2>
        </div>

        <div class="stats-grid">
          <div class="stat-card border-l-4 border-l-blue-500 shadow-sm hover:shadow-md transition-shadow">
            <div class="stat-content">
              <p class="stat-label">Encours Clients</p>
              <h3 class="stat-value text-blue-600">{{ formatCurrency(totalEncoursClients) }}</h3>
              <p class="text-[10px] text-gray-500 mt-1 uppercase tracking-wider">Créances à recouvrer</p>
            </div>
          </div>

          <div class="stat-card border-l-4 border-l-red-500 shadow-sm hover:shadow-md transition-shadow">
            <div class="stat-content">
              <p class="stat-label">Encours Fournisseurs</p>
              <h3 class="stat-value text-red-600">{{ formatCurrency(totalEncoursFournisseurs) }}</h3>
              <p class="text-[10px] text-gray-500 mt-1 uppercase tracking-wider">Dettes à payer</p>
            </div>
          </div>

          <div class="stat-card border-l-4 border-l-green-500 shadow-sm hover:shadow-md transition-shadow">
            <div class="stat-content">
              <p class="stat-label">Trésorerie Nette</p>
              <h3 class="stat-value text-green-600">{{ formatCurrency(totalTresorerie) }}</h3>
              <p class="text-[10px] text-gray-500 mt-1 uppercase tracking-wider">Liquidités disponibles</p>
            </div>
          </div>

          <div class="stat-card border-l-4 border-l-purple-500 shadow-sm hover:shadow-md transition-shadow">
            <div class="stat-content">
              <p class="stat-label">BFR</p>
              <h3 class="stat-value text-purple-600">{{ formatCurrency(financeStats.bfr.bfr) }}</h3>
              <p class="text-[10px] text-gray-500 mt-1 uppercase tracking-wider">Besoin en Fonds de Roulement</p>
            </div>
          </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
          <!-- Détails Encours -->
          <div class="card">
            <div class="card-header border-none pb-0">
              <h3 class="font-semibold text-gray-700">Top clients avec factures impayées</h3>
            </div>
            <div class="activity-list mt-4">
              <div v-if="financeStats.encoursClients.length === 0" class="empty-state-small py-4">
                Aucun encours client
              </div>
              <div v-for="item in financeStats.encoursClients.slice(0, 5)" :key="item.entreprise_id" class="activity-item">
                <span class="text-sm font-medium">{{ item.client_nom }}</span>
                <span class="text-sm font-bold text-blue-600">{{ formatCurrency(item.total_encours) }}</span>
              </div>
            </div>
          </div>

          <div class="card">
            <div class="card-header border-none pb-0">
              <h3 class="font-semibold text-gray-700">Répartition par Caisse</h3>
            </div>
            <div class="activity-list mt-4">
              <div v-for="caisse in financeStats.tresorerieNet" :key="caisse.id" class="activity-item">
                <div>
                  <p class="text-sm font-medium">{{ caisse.libelle }}</p>
                  <p class="text-xs text-gray-500">{{ caisse.entreprise_nom }}</p>
                </div>
                <span class="text-sm font-bold text-green-600">{{ formatCurrency(caisse.solde_actuel) }}</span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- New Statistique Commerciale Section -->
      <div class="space-y-6 fade-in" style="animation-delay: 0.32s">
        <div class="flex items-center justify-between border-b pb-2">
          <h2 class="text-xl font-bold text-gray-800">Statistique Commerciale</h2>
          <router-link to="/stats/commercial" class="text-sm text-gray-500 hover:text-gray-900">
            Voir le détail
          </router-link>
        </div>

        <div class="stats-grid">
          <div class="stat-card">
            <div class="stat-header">
              <div class="stat-icon bg-blue-100 text-blue-600">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                </svg>
              </div>
            </div>
            <div class="stat-content">
              <p class="stat-label">Taux de fidélisation</p>
              <h3 class="stat-value">{{ formatPercent(commercialStats.fidelisation.taux_fidelisation) }}</h3>
            </div>
          </div>

          <div class="stat-card">
            <div class="stat-header">
              <div class="stat-icon bg-green-100 text-green-600">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9V3H6v6m12 0l-6 6-6-6m12 0H6" />
                </svg>
              </div>
            </div>
            <div class="stat-content">
              <p class="stat-label">Nouveaux clients</p>
              <h3 class="stat-value">{{ commercialStats.nouveauxClients }}</h3>
            </div>
          </div>

          <div class="stat-card">
            <div class="stat-header">
              <div class="stat-icon bg-purple-100 text-purple-600">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0-12a5.5 5.5 0 019.288 0m-9.288 0a5.5 5.5 0 019.288 0m-9.288 0H2v2a3 3 0 015.356 1.857M7 4v2c0 .656.126 1.283.356 1.857m9.288-4H22v2a3 3 0 01-5.356 1.857M17 4v2c0 .656-.126 1.283-.356 1.857M7 10h.01M17 10h.01" />
                </svg>
              </div>
            </div>
            <div class="stat-content">
              <p class="stat-label">Clients fidèles</p>
              <h3 class="stat-value">{{ commercialStats.fidelisation.clients_fideles }}</h3>
            </div>
          </div>

          <div class="stat-card">
            <div class="stat-header">
              <div class="stat-icon bg-orange-100 text-orange-600">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                </svg>
              </div>
            </div>
            <div class="stat-content">
              <p class="stat-label">Total clients</p>
              <h3 class="stat-value">{{ commercialStats.fidelisation.total_clients }}</h3>
            </div>
          </div>
        </div>

        <!-- Top Performers -->
        <div class="card">
          <div class="card-header">
            <h3 class="card-title">Top commerciaux</h3>
          </div>
          <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
              <thead class="bg-gray-50">
                <tr>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Commercial</th>
                  <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Ventes</th>
                  <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Total</th>
                </tr>
              </thead>
              <tbody class="bg-white divide-y divide-gray-200">
                <tr v-for="item in commercialStats.performance.slice(0, 5)" :key="item.personnel_id" class="hover:bg-gray-50">
                  <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ item.nom_complet }}</td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-right text-gray-500">{{ item.nombre_ventes }}</td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-right font-medium text-green-600">{{ formatCurrency(item.total_ventes) }}</td>
                </tr>
              </tbody>
            </table>
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
            <div v-else v-for="vente in recentVentes" :key="vente.id" class="activity-item">
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
            <div v-else v-for="achat in recentAchats" :key="achat.id" class="activity-item">
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

        <!-- New Stock Alert Section -->
        <div class="card col-span-2">
          <div class="card-header">
            <h2 class="card-title">Articles proches de la rupture</h2>
            <router-link to="/stock" class="card-link">
              Gérer le stock
            </router-link>
          </div>
          <div class="activity-list">
            <div v-if="stockStats.articlesRupture.length === 0" class="empty-state-small">
              <p>Aucun article en alerte</p>
            </div>
            <div v-else v-for="article in stockStats.articlesRupture" :key="article.id" class="activity-item">
              <div class="activity-info">
                <p class="activity-title">{{ article.designation }}</p>
                <p class="activity-subtitle">{{ article.categorie }}</p>
              </div>
              <div class="activity-meta">
                <p class="activity-amount text-red-600">{{ article.quantite_actuelle }} unités</p>
                <span class="badge badge-danger">Alerte</span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import { Package, TrendingUp, ShoppingCart, Wallet } from 'lucide-vue-next'
import api from '@/services/api/api'
import statStockService from '@/services/statStockService'
import financeService from '@/services/financeService'
import statCommercialService from '@/services/statCommercialService'
import { Line } from 'vue-chartjs'
import {
  Chart as ChartJS,
  Title,
  Tooltip,
  Legend,
  LineElement,
  CategoryScale,
  LinearScale,
  PointElement,
  Filler
} from 'chart.js'

ChartJS.register(Title, Tooltip, Legend, LineElement, CategoryScale, LinearScale, PointElement, Filler)

const stats = ref({
  totalArticles: 0,
  totalFacturesVente: 0,
  totalFacturesAchat: 0,
  soldeCaisse: 0
})

const stockStats = ref({
  tauxRotationMoyenne: 0,
  valeurImmobilise: 0,
  dureeMoyenne: 0,
  articlesRupture: [],
  rotationData: [] // Store full rotation data
})

const financeStats = ref({
  encoursClients: [],
  encoursFournisseurs: [],
  tresorerieNet: [],
  bfr: {
    valeur_stock: 0,
    creances_clients: 0,
    dettes_fournisseurs: 0,
    bfr: 0
  }
})

const commercialStats = ref({
  performance: [],
  fidelisation: { taux_fidelisation: 0, clients_fideles: 0, total_clients: 0 },
  nouveauxClients: 0
})

const totalEncoursClients = computed(() => 
  financeStats.value.encoursClients.reduce((sum, item) => sum + parseFloat(item.total_encours), 0)
)

const totalEncoursFournisseurs = computed(() => 
  financeStats.value.encoursFournisseurs.reduce((sum, item) => sum + parseFloat(item.total_encours), 0)
)

const totalTresorerie = computed(() => 
  financeStats.value.tresorerieNet.reduce((sum, item) => sum + parseFloat(item.solde_actuel), 0)
)

const chartData = computed(() => {
  return {
    labels: stockStats.value.rotationData.map(item => item.designation),
    datasets: [
      {
        label: 'Taux de rotation (%)',
        backgroundColor: 'rgba(79, 70, 229, 0.2)',
        borderColor: '#4f46e5',
        pointBackgroundColor: '#4f46e5',
        pointBorderColor: '#fff',
        data: stockStats.value.rotationData.map(item => item.taux_rotation),
        fill: true,
        tension: 0.4
      }
    ]
  }
})

const chartOptions = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: {
      display: false
    },
    tooltip: {
      callbacks: {
        label: function (context) {
          const item = stockStats.value.rotationData[context.dataIndex]
          return [
            `Taux: ${context.parsed.y.toFixed(1)}%`,
            `Sorties: ${item.total_sorties}`,
            `Stock: ${item.stock_actuel}`
          ]
        }
      }
    }
  },
  scales: {
    y: {
      beginAtZero: true,
      ticks: {
        callback: value => value + '%'
      }
    }
  }
}

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

const formatPercent = (value) => {
  return new Intl.NumberFormat('fr-FR', {
    style: 'percent',
    minimumFractionDigits: 1
  }).format(value / 100)
}

const loadDashboardData = async () => {
  loading.value = true
  error.value = null
  try {
    // Stats de base
    const statsResponse = await api.get('/dashboard/stats')
    stats.value = statsResponse.data || stats.value

    const ventesResponse = await api.get('/dashboard/recent-ventes')
    recentVentes.value = ventesResponse.data || []

    const achatsResponse = await api.get('/dashboard/recent-achats')
    recentAchats.value = achatsResponse.data || []

    try {
      const rotationResp = await statStockService.getTauxRotationStock()
      console.log('Rotation response:', rotationResp) // DEBUG

      const rotationData = Array.isArray(rotationResp.data) ? rotationResp.data : []
      stockStats.value.rotationData = rotationData

      const avgRotation = rotationData.length > 0
        ? rotationData.reduce((sum, item) => {
          const taux = parseFloat(item.taux_rotation) || 0
          return sum + taux
        }, 0) / rotationData.length
        : 0
      stockStats.value.tauxRotationMoyenne = avgRotation
    } catch (err) {
      console.error('Erreur taux rotation:', err)
      stockStats.value.tauxRotationMoyenne = 0
      stockStats.value.rotationData = []
    }

    try {
      const immobiliseResp = await statStockService.getValeurStockImmobilise()
      console.log('Immobilise response:', immobiliseResp) // DEBUG

      stockStats.value.valeurImmobilise = parseFloat(immobiliseResp.data?.total) || 0
    } catch (err) {
      console.error('Erreur valeur immobilisé:', err)
      stockStats.value.valeurImmobilise = 0
    }

    try {
      const dureeResp = await statStockService.getDureeStockMoyenne()
      console.log('Duree response:', dureeResp) 

      const duree = parseFloat(dureeResp.data?.duree_moyenne)
      stockStats.value.dureeMoyenne = !isNaN(duree) ? Math.round(duree) : 0
    } catch (err) {
      console.error('Erreur durée moyenne:', err)
      stockStats.value.dureeMoyenne = 0
    }

    try {
      const ruptureResp = await statStockService.getArticlesRupture()
      console.log('Rupture response:', ruptureResp) 

      stockStats.value.articlesRupture = Array.isArray(ruptureResp.data) ? ruptureResp.data : []
    } catch (err) {
      console.error('Erreur articles rupture:', err)
      stockStats.value.articlesRupture = []
    }

    // Statistiques Financières
    try {
      const finResp = await financeService.getAllStats()
      console.log('Finance stats:', finResp.data)
      financeStats.value = {
        encoursClients: finResp.data.encours_clients || [],
        encoursFournisseurs: finResp.data.encours_fournisseurs || [],
        tresorerieNet: finResp.data.tresorerie_net || [],
        bfr: finResp.data.bfr || financeStats.value.bfr
      }
    } catch (err) {
      console.error('Erreur stats financières:', err)
    }

    // Statistiques Commerciales
    try {
      const perfResp = await statCommercialService.getPerformanceCommercial()
      commercialStats.value.performance = perfResp.data || []

      const fidResp = await statCommercialService.getTauxFidelisation()
      commercialStats.value.fidelisation = fidResp.data || commercialStats.value.fidelisation

      const nouveauxResp = await statCommercialService.getNouveauxClients()
      commercialStats.value.nouveauxClients = nouveauxResp.data.nouveaux_clients || 0
    } catch (err) {
      console.error('Erreur stats commerciales:', err)
    }

  } catch (err) {
    error.value = 'Erreur lors du chargement des données'
    console.error('Erreur chargement dashboard:', err)

    stats.value = {
      totalArticles: 0,
      totalFacturesVente: 0,
      totalFacturesAchat: 0,
      soldeCaisse: 0
    }
    stockStats.value = {
      tauxRotationMoyenne: 0,
      valeurImmobilise: 0,
      dureeMoyenne: 0,
      articlesRupture: []
    }
    commercialStats.value = {
      performance: [],
      fidelisation: { taux_fidelisation: 0, clients_fideles: 0, total_clients: 0 },
      nouveauxClients: 0
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
  to {
    transform: rotate(360deg);
  }
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
  @apply grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4;
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

/* Badges */
.badge-danger {
  @apply bg-red-100 text-red-700;
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