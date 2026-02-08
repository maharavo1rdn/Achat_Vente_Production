<template>
  <div class="page-container">
    <!-- Loading state -->
    <div v-if="loading" class="loading-state">
      <div class="spinner"></div>
      <p class="loading-text">Chargement des mouvements de stock...</p>
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
      <button @click="loadMouvements" class="btn-primary">
        Réessayer
      </button>
    </div>

    <!-- Content -->
    <div v-else class="content-wrapper">
      <!-- Header -->
      <div class="page-header fade-in" style="animation-delay: 0.1s">
        <div>
          <h1 class="page-title">📦 Mouvements de Stock</h1>
          <p class="page-subtitle">Suivi des entrées, sorties et inventaires</p>
        </div>
        <div class="header-actions">
          <button @click="openCreateModal" class="btn-primary">
            <Plus class="w-4 h-4" />
            <span>Nouveau Mouvement</span>
          </button>
        </div>
      </div>

      <!-- Stats Cards -->
      <div class="stats-grid fade-in" style="animation-delay: 0.15s">
        <div class="stat-card">
          <div class="stat-header">
            <div class="stat-icon bg-blue-100 text-blue-600">
              <TrendingUp class="w-5 h-5" />
            </div>
          </div>
          <div class="stat-content">
            <p class="stat-label">Total Entrées</p>
            <h3 class="stat-value">{{ totalEntrees }}</h3>
          </div>
        </div>
        <div class="stat-card">
          <div class="stat-header">
            <div class="stat-icon bg-red-100 text-red-600">
              <TrendingDown class="w-5 h-5" />
            </div>
          </div>
          <div class="stat-content">
            <p class="stat-label">Total Sorties</p>
            <h3 class="stat-value">{{ totalSorties }}</h3>
          </div>
        </div>
        <div class="stat-card">
          <div class="stat-header">
            <div class="stat-icon bg-green-100 text-green-600">
              <Package class="w-5 h-5" />
            </div>
          </div>
          <div class="stat-content">
            <p class="stat-label">Mouvements</p>
            <h3 class="stat-value">{{ mouvements.length }}</h3>
          </div>
        </div>
        <div class="stat-card">
          <div class="stat-header">
            <div class="stat-icon bg-purple-100 text-purple-600">
              <Warehouse class="w-5 h-5" />
            </div>
          </div>
          <div class="stat-content">
            <p class="stat-label">Dépôts actifs</p>
            <h3 class="stat-value">{{ depotsActifs }}</h3>
          </div>
        </div>
      </div>

      <!-- Filters -->
      <div class="card fade-in" style="animation-delay: 0.2s">
        <div class="filter-grid">
          <div class="filter-item-large">
            <label class="label">Rechercher</label>
            <div class="search-box">
              <Search class="w-4 h-4 text-gray-400" />
              <input v-model="searchQuery" type="text" class="search-input" placeholder="Référence document, article..." />
            </div>
          </div>
          <div class="filter-item">
            <label class="label">Type</label>
            <select v-model="selectedType" class="select">
              <option value="">Tous les types</option>
              <option value="ACHAT">Achat</option>
              <option value="VENTE">Vente</option>
              <option value="INVENTAIRE">Inventaire</option>
              <option value="TRANSFERT">Transfert</option>
            </select>
          </div>
          <div class="filter-item">
            <label class="label">Dépôt</label>
            <select v-model="selectedDepot" class="select">
              <option value="">Tous les dépôts</option>
              <option v-for="depot in depots" :key="depot.id" :value="depot.id">
                {{ depot.nom }}
              </option>
            </select>
          </div>
          <div class="filter-item">
            <label class="label">Date début</label>
            <input v-model="dateDebut" type="date" class="select" />
          </div>
          <div class="filter-item">
            <label class="label">Date fin</label>
            <input v-model="dateFin" type="date" class="select" />
          </div>
        </div>
        <div class="flex justify-end mt-4">
          <button @click="applyFilters" class="btn-secondary">
            <Filter class="w-4 h-4" />
            <span>Appliquer</span>
          </button>
          <button @click="resetFilters" class="btn-secondary ml-2">
            <RefreshCw class="w-4 h-4" />
            <span>Réinitialiser</span>
          </button>
        </div>
      </div>

      <!-- View Toggle -->
      <div class="view-toggle fade-in" style="animation-delay: 0.25s">
        <div class="toggle-group">
          <button @click="viewMode = 'table'" :class="viewMode === 'table' ? 'toggle-btn-active' : 'toggle-btn'">
            <List class="w-4 h-4" />
            <span>Liste</span>
          </button>
          <button @click="viewMode = 'stats'" :class="viewMode === 'stats' ? 'toggle-btn-active' : 'toggle-btn'">
            <BarChart3 class="w-4 h-4" />
            <span>Statistiques</span>
          </button>
          <button @click="viewMode = 'situation'" :class="viewMode === 'situation' ? 'toggle-btn-active' : 'toggle-btn'">
            <PackageOpen class="w-4 h-4" />
            <span>Situation</span>
          </button>
        </div>
      </div>

      <!-- Table View -->
      <div v-if="viewMode === 'table'" class="card fade-in" style="animation-delay: 0.3s">
        <div class="table-wrapper">
          <table class="table">
            <thead>
              <tr>
                <th>Date</th>
                <th>Type</th>
                <th>Article</th>
                <th>Référence</th>
                <th>Dépôt</th>
                <th class="text-right">Stock avant</th>
                <th class="text-right">Entrée</th>
                <th class="text-right">Sortie</th>
                <th class="text-right">Stock après</th>
                <th class="text-center">Personnel</th>
                <th class="text-center">Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="mouvement in filteredMouvements" :key="mouvement.id" class="table-row">
                <td class="font-medium">{{ formatDate(mouvement.date_mouvement) }}</td>
                <td>
                  <span :class="getTypeBadgeClass(mouvement.type_mouvement)">
                    {{ getTypeLabel(mouvement.type_mouvement) }}
                  </span>
                </td>
                <td>
                  <div class="flex flex-col">
                    <span class="font-medium">{{ mouvement.article_designation }}</span>
                    <span class="text-xs text-gray-500">{{ mouvement.article_reference }}</span>
                  </div>
                </td>
                <td>{{ mouvement.reference_document || '-' }}</td>
                <td>{{ mouvement.depot_nom || '-' }}</td>
                <td class="text-right font-semibold">{{ mouvement.quantite_stock_avant }}</td>
                <td class="text-right font-semibold text-green-600" v-if="mouvement.quantite_entree > 0">
                  +{{ mouvement.quantite_entree }}
                </td>
                <td class="text-right" v-else>-</td>
                <td class="text-right font-semibold text-red-600" v-if="mouvement.quantite_sortie > 0">
                  -{{ mouvement.quantite_sortie }}
                </td>
                <td class="text-right" v-else>-</td>
                <td class="text-right font-bold">{{ mouvement.quantite_stock_apres }}</td>
                <td class="text-center">
                  {{ (mouvement.personnel_prenom || '') }} {{ (mouvement.personnel_nom || '') }}
                </td>
                <td>
                  <div class="table-actions" @click.stop>
                    <button @click="viewMouvement(mouvement)" class="action-btn" title="Voir détails">
                      <Eye class="w-4 h-4" />
                    </button>
                    <button v-if="mouvement.type_mouvement === 'VENTE'" 
                            @click="simulerFIFO(mouvement)" 
                            class="action-btn text-blue-600" 
                            title="Vérifier FIFO">
                      <GitBranch class="w-4 h-4" />
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
        
        <!-- Pagination -->
        <div class="pagination" v-if="filteredMouvements.length > 0">
          <div class="pagination-info">
            Affichage de {{ filteredMouvements.length }} mouvements
          </div>
        </div>
      </div>

      <!-- Statistics View -->
      <div v-else-if="viewMode === 'stats'" class="fade-in" style="animation-delay: 0.3s">
        <div class="activities-grid">
          <!-- Statistiques par type -->
          <div class="card">
            <div class="card-header">
              <h2 class="card-title">📊 Statistiques par Type</h2>
            </div>
            <div class="activity-list">
              <div v-for="stat in statistiquesParType" :key="stat.type" class="activity-item">
                <div class="activity-info">
                  <span :class="getTypeBadgeClass(stat.type)" class="inline-block mr-2">
                    {{ getTypeLabel(stat.type) }}
                  </span>
                  <span class="text-gray-500 text-sm">{{ stat.nombre }} mouvements</span>
                </div>
                <div class="activity-meta">
                  <p class="activity-amount">{{ stat.total }} unités</p>
                </div>
              </div>
            </div>
          </div>

          <!-- Top Articles -->
          <div class="card">
            <div class="card-header">
              <h2 class="card-title">🏆 Top 5 Articles</h2>
            </div>
            <div class="activity-list">
              <div v-for="(article, index) in topArticles" :key="article.article_id" class="activity-item">
                <div class="activity-info">
                  <p class="activity-title">#{{ index + 1 }} - {{ article.article_designation }}</p>
                  <p class="activity-subtitle">{{ article.article_reference }}</p>
                </div>
                <div class="activity-meta">
                  <p class="activity-amount">{{ article.total_mouvements }} mouvements</p>
                  <span class="badge badge-success">{{ article.total_quantite }} unités</span>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Situation View -->
      <div v-else class="fade-in" style="animation-delay: 0.3s">
        <div class="activities-grid">
          <!-- Situation globale -->
          <div class="card">
            <div class="card-header">
              <h2 class="card-title">📦 Situation Globale</h2>
              <button @click="loadSituationGlobale" class="card-link">
                <RefreshCw class="w-4 h-4" />
              </button>
            </div>
            <div class="activity-list">
              <div v-if="situationGlobale.length === 0" class="empty-state-small">
                <p>Aucune donnée disponible</p>
              </div>
              <div v-for="situation in situationGlobale" :key="situation.article_id" class="activity-item">
                <div class="activity-info">
                  <p class="activity-title">{{ situation.article_designation }}</p>
                  <p class="activity-subtitle">{{ situation.article_reference }}</p>
                </div>
                <div class="activity-meta">
                  <p class="activity-amount">{{ situation.quantite_totale }} unités</p>
                  <span class="badge badge-info">{{ situation.nombre_depots }} dépôts</span>
                </div>
              </div>
            </div>
          </div>

          <!-- Dernières sorties -->
          <div class="card">
            <div class="card-header">
              <h2 class="card-title">🔄 Dernières Sorties</h2>
              <button @click="loadDernieresSorties" class="card-link">
                Voir tout
              </button>
            </div>
            <div class="activity-list">
              <div v-if="dernieresSorties.length === 0" class="empty-state-small">
                <p>Aucune sortie récente</p>
              </div>
              <div v-for="sortie in dernieresSorties" :key="sortie.id" class="activity-item">
                <div class="activity-info">
                  <p class="activity-title">{{ sortie.article_designation }}</p>
                  <p class="activity-subtitle">{{ formatDate(sortie.date_mouvement) }} - {{ sortie.depot_nom }}</p>
                </div>
                <div class="activity-meta">
                  <p class="activity-amount text-red-600">-{{ sortie.quantite_sortie }}</p>
                  <span class="badge badge-warning">{{ sortie.personnel_prenom }}</span>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { 
  Plus, Search, Filter, RefreshCw, List, BarChart3, PackageOpen, 
  Eye, TrendingUp, TrendingDown, Package, Warehouse, GitBranch 
} from 'lucide-vue-next'
import mouvementService from '@/services/mouvementStockService'
import depotService from '@/services/depotService'

const loading = ref(false)
const error = ref(null)
const viewMode = ref('table')
const searchQuery = ref('')
const selectedType = ref('')
const selectedDepot = ref('')
const dateDebut = ref('')
const dateFin = ref('')
const mouvements = ref([])
const depots = ref([])
const situationGlobale = ref([])
const dernieresSorties = ref([])
const statistiques = ref(null)

// Computed properties
const filteredMouvements = computed(() => {
  return mouvements.value.filter(mouvement => {
    const matchSearch = !searchQuery.value ||
      mouvement.reference_document?.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
      mouvement.article_designation?.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
      mouvement.article_reference?.toLowerCase().includes(searchQuery.value.toLowerCase())

    const matchType = !selectedType.value || mouvement.type_mouvement === selectedType.value
    const matchDepot = !selectedDepot.value || mouvement.depot_id == selectedDepot.value
    const matchDate = (!dateDebut.value || new Date(mouvement.date_mouvement) >= new Date(dateDebut.value)) &&
                     (!dateFin.value || new Date(mouvement.date_mouvement) <= new Date(dateFin.value))

    return matchSearch && matchType && matchDepot && matchDate
  }).slice(0, 100) // Limiter à 100 résultats pour les performances
})

const totalEntrees = computed(() => {
  return mouvements.value.reduce((sum, m) => sum + parseFloat(m.quantite_entree || 0), 0)
})

const totalSorties = computed(() => {
  return mouvements.value.reduce((sum, m) => sum + parseFloat(m.quantite_sortie || 0), 0)
})

const depotsActifs = computed(() => {
  const depotIds = [...new Set(mouvements.value.map(m => m.depot_id))]
  return depotIds.length
})

const statistiquesParType = computed(() => {
  const stats = {}
  mouvements.value.forEach(m => {
    if (!stats[m.type_mouvement]) {
      stats[m.type_mouvement] = {
        type: m.type_mouvement,
        nombre: 0,
        total: 0
      }
    }
    stats[m.type_mouvement].nombre++
    stats[m.type_mouvement].total += parseFloat(m.quantite_entree || 0) + parseFloat(m.quantite_sortie || 0)
  })
  return Object.values(stats)
})

const topArticles = computed(() => {
  const articles = {}
  mouvements.value.forEach(m => {
    if (!articles[m.article_id]) {
      articles[m.article_id] = {
        article_id: m.article_id,
        article_reference: m.article_reference,
        article_designation: m.article_designation,
        total_mouvements: 0,
        total_quantite: 0
      }
    }
    articles[m.article_id].total_mouvements++
    articles[m.article_id].total_quantite += parseFloat(m.quantite_entree || 0) + parseFloat(m.quantite_sortie || 0)
  })
  return Object.values(articles)
    .sort((a, b) => b.total_mouvements - a.total_mouvements)
    .slice(0, 5)
})

// Methods
const formatDate = (dateString) => {
  if (!dateString) return '-'
  const date = new Date(dateString)
  return date.toLocaleDateString('fr-FR', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

const getTypeLabel = (type) => {
  const types = {
    'ACHAT': 'Achat',
    'VENTE': 'Vente',
    'INVENTAIRE': 'Inventaire',
    'TRANSFERT': 'Transfert'
  }
  return types[type] || type
}

const getTypeBadgeClass = (type) => {
  const classes = {
    'ACHAT': 'badge badge-success',
    'VENTE': 'badge badge-danger',
    'INVENTAIRE': 'badge badge-warning',
    'TRANSFERT': 'badge badge-info'
  }
  return classes[type] || 'badge'
}

const loadMouvements = async () => {
  loading.value = true
  error.value = null
  try {
    const response = await mouvementService.getMouvementsDetailles()
    mouvements.value = response.data.data || []
  } catch (err) {
    error.value = 'Erreur lors du chargement des mouvements'
    console.error('Erreur chargement mouvements:', err)
  } finally {
    loading.value = false
  }
}

const loadSituationGlobale = async () => {
  try {
    const response = await mouvementService.getSituationGlobale()
    situationGlobale.value = response.data.data || []
  } catch (err) {
    console.error('Erreur chargement situation:', err)
  }
}

const loadDernieresSorties = async () => {
  try {
    const response = await mouvementService.getDernieresSorties(10)
    dernieresSorties.value = response.data.data || []
  } catch (err) {
    console.error('Erreur chargement dernières sorties:', err)
  }
}

const loadDepots = async () => {
  try {
    // À remplacer par votre service de dépôts
    const response = await depotService.getAll()
    depots.value = response.data
    // alert(JSON.stringify(depots.value));
  } catch (err) {
    console.error('Erreur chargement dépôts:', err)
  }
}

const applyFilters = () => {
  // Les filtres sont déjà appliqués via computed property
  console.log('Filtres appliqués')
}

const resetFilters = () => {
  searchQuery.value = ''
  selectedType.value = ''
  selectedDepot.value = ''
  dateDebut.value = ''
  dateFin.value = ''
}

const openCreateModal = () => {
  console.log('Ouvrir modal de création')
  // À implémenter : ouvrir un modal de création de mouvement
}

const viewMouvement = (mouvement) => {
  console.log('Voir mouvement:', mouvement)
  // À implémenter : navigation vers la page détail ou ouverture de modal
}

const simulerFIFO = async (mouvement) => {
  try {
    const response = await mouvementService.getDepotFIFOPourSortie(
      mouvement.article_id, 
      mouvement.quantite_sortie
    )
    alert(`FIFO pour ${mouvement.quantite_sortie} unités: ${JSON.stringify(response.data.data)}`)
  } catch (err) {
    console.error('Erreur simulation FIFO:', err)
    alert('Erreur lors de la simulation FIFO')
  }
}

// Initialize
onMounted(() => {
  loadMouvements()
  loadSituationGlobale()
  loadDernieresSorties()
  loadDepots()
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

/* Card */
.card {
  @apply bg-white rounded-lg border border-gray-200 p-5;
}

/* Filter Grid */
.filter-grid {
  @apply grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4;
}

.filter-item-large {
  @apply md:col-span-2;
}

.filter-item {
  @apply space-y-2;
}

.label {
  @apply block text-xs font-medium text-gray-700;
}

.search-box {
  @apply flex items-center gap-3 px-4 py-2.5 bg-white border border-gray-300 rounded-lg transition-all;
}

.search-box:focus-within {
  @apply ring-2 ring-gray-900 border-gray-900;
}

.search-input {
  @apply flex-1 bg-transparent border-none outline-none text-sm;
}

.select {
  @apply w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-gray-900 focus:border-gray-900 outline-none text-sm bg-white disabled:bg-gray-100 disabled:text-gray-400 disabled:cursor-not-allowed;
}

/* View Toggle */
.view-toggle {
  @apply flex justify-end;
}

.toggle-group {
  @apply inline-flex items-center gap-1 p-1 bg-gray-100 rounded-lg;
}

.toggle-btn {
  @apply flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-medium text-gray-600 hover:text-gray-900 transition-all;
}

.toggle-btn-active {
  @apply flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-medium bg-white text-gray-900 shadow-sm;
}

/* Table Styles */
.table-wrapper {
  @apply overflow-x-auto;
}

.table {
  @apply w-full;
}

.table thead {
  @apply bg-gray-50 border-b border-gray-200;
}

.table th {
  @apply px-6 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider;
}

.table td {
  @apply px-6 py-4 text-sm text-gray-900;
}

.table tbody tr {
  @apply border-b border-gray-100;
}

.table-row {
  @apply hover:bg-gray-50 transition-colors;
}

.table-actions {
  @apply flex items-center justify-center gap-2;
}

.action-btn {
  @apply p-2 rounded-lg text-gray-600 hover:bg-gray-100 hover:text-gray-900 transition-all;
}

/* Activities Grid */
.activities-grid {
  @apply grid grid-cols-1 lg:grid-cols-2 gap-6;
}

.card-header {
  @apply flex items-center justify-between mb-4 pb-3 border-b border-gray-200;
}

.card-title {
  @apply text-base font-semibold text-gray-900;
}

.card-link {
  @apply flex items-center gap-1 text-xs text-gray-500 hover:text-gray-900 transition-colors;
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
  @apply inline-flex items-center px-3 py-1 rounded-full text-xs font-medium;
}

.badge-success {
  @apply bg-green-100 text-green-700;
}

.badge-danger {
  @apply bg-red-100 text-red-700;
}

.badge-warning {
  @apply bg-orange-100 text-orange-700;
}

.badge-info {
  @apply bg-blue-100 text-blue-700;
}

/* Pagination */
.pagination {
  @apply flex items-center justify-between mt-4 pt-4 border-t border-gray-200;
}

.pagination-info {
  @apply text-sm text-gray-500;
}

/* Buttons */
.btn-primary {
  @apply flex items-center gap-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition-all;
}

.btn-secondary {
  @apply flex items-center gap-2 px-4 py-2 border border-gray-300 hover:bg-gray-50 text-gray-700 font-medium rounded-lg transition-all;
}
</style>