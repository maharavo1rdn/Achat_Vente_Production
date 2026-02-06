<template>
  <div class="page-container">
    <!-- Loading state -->
    <div v-if="loading" class="loading-state">
      <div class="spinner"></div>
      <p class="loading-text">Chargement du stock...</p>
    </div>

    <!-- Error state -->
    <div v-else-if="error" class="error-state">
      <div class="error-icon">
        <svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
        </svg>
      </div>
      <p class="error-message">{{ error }}</p>
      <button @click="loadStockData" class="btn-primary">
        Réessayer
      </button>
    </div>

    <!-- Content -->
    <div v-else class="content-wrapper">
      <!-- Header -->
      <div class="page-header fade-in" style="animation-delay: 0.1s">
        <div>
          <h1 class="page-title">Gestion du Stock</h1>
          <p class="page-subtitle">Suivi des stocks par filiale</p>
        </div>
        <button @click="openMouvementModal" class="btn-primary">
          <ArrowRightLeft class="w-4 h-4" />
          <span>Nouveau Mouvement</span>
        </button>
      </div>

      <!-- Stats Cards -->
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4 fade-in" style="animation-delay: 0.15s">
        <div class="stat-card">
          <div class="stat-content">
            <p class="stat-label">Articles en stock</p>
            <h3 class="stat-value">{{ stocks.length }}</h3>
          </div>
        </div>
        <div class="stat-card">
          <div class="stat-content">
            <p class="stat-label">Stock disponible</p>
            <h3 class="stat-value text-green-600">{{ stockDisponible }}</h3>
          </div>
        </div>
        <div class="stat-card">
          <div class="stat-content">
            <p class="stat-label">Stock bas</p>
            <h3 class="stat-value text-orange-600">{{ stockBas }}</h3>
          </div>
        </div>
        <div class="stat-card">
          <div class="stat-content">
            <p class="stat-label">Rupture</p>
            <h3 class="stat-value text-red-600">{{ stockRupture }}</h3>
          </div>
        </div>
        <div class="stat-card">
          <div class="stat-content">
            <p class="stat-label">Valeur totale stock</p>
            <h3 class="stat-value text-purple-600">{{ formatCurrency(valeurTotaleStock) }}</h3>
          </div>
        </div>
      </div>

      <!-- Filters -->
      <div class="card fade-in" style="animation-delay: 0.2s">
        <div class="filter-grid">
          <div class="filter-item">
            <label class="label">Filiale</label>
            <select v-model="selectedFiliale" class="select">
              <option value="">Toutes les filiales</option>
              <option value="1">Filiale A</option>
              <option value="2">Filiale B</option>
            </select>
          </div>
          <div class="filter-item">
            <label class="label">Article</label>
            <div class="search-box">
              <Search class="w-4 h-4 text-gray-400" />
              <input
                v-model="searchArticle"
                type="text"
                class="search-input"
                placeholder="Rechercher un article..."
              />
            </div>
          </div>
          <div class="filter-item">
            <label class="label">Alerte Stock</label>
            <select v-model="alerteStock" class="select">
              <option value="">Tous</option>
              <option value="bas">Stock bas (&lt; 10)</option>
              <option value="zero">Rupture (= 0)</option>
            </select>
          </div>
        </div>
      </div>

      <!-- Valorisation (section compacte) -->
      <div class="card fade-in" style="animation-delay: 0.22s">
        <div class="card-header-simple flex items-center justify-between">
          <h2 class="card-title">Valorisation du Stock</h2>
          <div class="flex items-center gap-2">
            <select v-model="groupBy" class="select select-sm">
              <option value="filiale">Par Filiale</option>
              <option value="site">Par Site</option>
              <option value="depot">Par Dépôt</option>
            </select>
          </div>
        </div>
        
        <!-- Tableau valorisation compact -->
        <div class="table-wrapper" v-if="valoriseGrouped.length > 0">
          <table class="table table-compact">
            <thead>
              <tr>
                <th>{{ groupByHeader }}</th>
                <th class="text-right">Valeur Comptable</th>
                <th class="text-right">Valeur Vente</th>
                <th class="text-right">Marge</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="row in valoriseGrouped" :key="row.key" class="hover:bg-gray-50">
                <td class="font-medium">{{ row.key }}</td>
                <td class="text-right">{{ formatCurrency(row.valeur_comptable) }}</td>
                <td class="text-right text-green-600">{{ formatCurrency(row.valeur_vente_potentielle) }}</td>
                <td class="text-right" :class="row.valeur_vente_potentielle - row.valeur_comptable >= 0 ? 'text-green-600' : 'text-red-600'">
                  {{ formatCurrency(row.valeur_vente_potentielle - row.valeur_comptable) }}
                </td>
              </tr>
              <tr class="bg-gray-50 font-semibold border-t-2">
                <td>Total</td>
                <td class="text-right">{{ formatCurrency(valoriseTotals.valeur_comptable) }}</td>
                <td class="text-right text-green-600">{{ formatCurrency(valoriseTotals.valeur_vente_potentielle) }}</td>
                <td class="text-right text-green-700">
                  {{ formatCurrency(valoriseTotals.valeur_vente_potentielle - valoriseTotals.valeur_comptable) }}
                </td>
              </tr>
            </tbody>
          </table>
        </div>
        <div v-else class="p-4 text-center text-gray-500">
          Aucune donnée de valorisation
        </div>
      </div>

      <!-- Navigation vers dépôt -->
      <div class="card fade-in p-4" style="animation-delay: 0.23s">
        <div class="flex gap-3 items-end flex-wrap">
          <div class="flex-1 min-w-[150px]">
            <label class="label">Filiale</label>
            <select v-model="selectedFilialeName" class="select" @change="onFilialeChange">
              <option value="">Sélectionner</option>
              <option v-for="s in structureFilteredFiliales" :key="s" :value="s">{{ s }}</option>
            </select>
          </div>
          <div class="flex-1 min-w-[150px]">
            <label class="label">Site</label>
            <select v-model="selectedSite" class="select" @change="onSiteChange" :disabled="!selectedFilialeName">
              <option value="">Sélectionner</option>
              <option v-for="s in filteredSites" :key="s" :value="s">{{ s }}</option>
            </select>
          </div>
          <div class="flex-1 min-w-[150px]">
            <label class="label">Dépôt</label>
            <select v-model="selectedDepot" class="select" :disabled="!selectedSite">
              <option value="">Sélectionner</option>
              <option v-for="d in filteredDepots" :key="d.id" :value="d.id">{{ d.nom }}</option>
            </select>
          </div>
          <button 
            @click="voirDetailsDepot" 
            :disabled="!selectedDepot"
            class="btn-primary h-10 disabled:opacity-50"
          >
            <Eye class="w-4 h-4 mr-1" />
            Détails dépôt
          </button>
        </div>
      </div>

      <!-- Stock Table (simplifié) -->
      <div class="card fade-in" style="animation-delay: 0.25s">
        <div class="card-header-simple flex items-center justify-between">
          <h2 class="card-title">État du Stock ({{ filteredStocks.length }} articles)</h2>
        </div>
        <div class="table-wrapper">
          <table class="table">
            <thead>
              <tr>
                <th>Article</th>
                <th>Réf.</th>
                <th>Localisation</th>
                <th class="text-center">Qté</th>
                <th class="text-right">CMUP</th>
                <th class="text-right">Valeur</th>
                <th class="text-center">Statut</th>
                <th class="text-center">Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="stock in filteredStocks" :key="stock.id" 
                  class="table-row"
                  :class="{ 'bg-red-50': stock.quantite_actuelle === 0, 'bg-orange-50': stock.quantite_actuelle > 0 && stock.quantite_actuelle < 10 }">
                <td class="font-medium">{{ stock.article }}</td>
                <td class="text-gray-600 text-sm">{{ stock.reference }}</td>
                <td class="text-sm">
                  <div>{{ stock.filiale }}</div>
                  <div class="text-gray-500 text-xs">{{ stock.depot }}</div>
                </td>
                <td class="text-center font-bold" :class="{
                  'text-red-600': stock.quantite_actuelle === 0,
                  'text-orange-600': stock.quantite_actuelle > 0 && stock.quantite_actuelle < 10,
                  'text-green-600': stock.quantite_actuelle >= 10
                }">{{ stock.quantite_actuelle }} {{ stock.unite_code }}</td>
                <td class="text-right text-sm">{{ formatCurrency(stock.cmup_actuel) }}</td>
                <td class="text-right font-medium">{{ formatCurrency(stock.valeur_stock_total) }}</td>
                <td class="text-center">
                  <span :class="getStockBadgeClass(stock.quantite_actuelle)">
                    {{ getStockStatus(stock.quantite_actuelle) }}
                  </span>
                </td>
                <td>
                  <div class="table-actions">
                    <button @click="viewHistorique(stock)" class="action-btn" title="Voir détails">
                      <Eye class="w-4 h-4" />
                    </button>
                  </div>
                </td>
              </tr>
              <tr v-if="filteredStocks.length === 0">
                <td colspan="8" class="text-center text-gray-500 py-8">Aucun article en stock</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Recent Movements -->
      <div class="card fade-in" style="animation-delay: 0.3s">
        <div class="card-header-simple">
          <h2 class="card-title">Derniers Mouvements</h2>
        </div>
        <div class="table-wrapper">
          <table class="table">
            <thead>
              <tr>
                <th>Date</th>
                <th>Type</th>
                <th>Article</th>
                <th class="text-center">Stock Avant</th>
                <th class="text-center">Entrée</th>
                <th class="text-center">Sortie</th>
                <th class="text-center">Stock Après</th>
                <th class="text-right">Prix Unit.</th>
                <th>Personnel</th>
                <th>Référence</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="mvt in recentMouvements" :key="mvt.id" class="table-row">
                <td class="text-gray-600">{{ formatDateTime(mvt.date_mouvement) }}</td>
                <td>
                  <span :class="getTypeBadgeClass(mvt.type_mouvement)">
                    {{ mvt.type_mouvement }}
                  </span>
                </td>
                <td class="font-medium">{{ mvt.article }}</td>
                <td class="text-center">{{ mvt.quantite_stock_avant }}</td>
                <td class="text-center">
                  <span v-if="mvt.quantite_entree > 0" class="text-green-600 font-semibold">
                    +{{ mvt.quantite_entree }}
                  </span>
                  <span v-else class="text-gray-400">-</span>
                </td>
                <td class="text-center">
                  <span v-if="mvt.quantite_sortie > 0" class="text-red-600 font-semibold">
                    -{{ mvt.quantite_sortie }}
                  </span>
                  <span v-else class="text-gray-400">-</span>
                </td>
                <td class="text-center font-semibold">{{ mvt.quantite_stock_apres }}</td>
                <td class="text-right font-medium">{{ formatCurrency(mvt.prix_unitaire_mouvement) }}</td>
                <td class="text-gray-600">{{ mvt.personnel }}</td>
                <td class="text-xs text-gray-500">{{ mvt.reference_document }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { useRouter } from 'vue-router'
import { ArrowRightLeft, History, Search, Eye } from 'lucide-vue-next'
import stockService from '@/services/stockService'

const router = useRouter()

const selectedFiliale = ref('')
const searchArticle = ref('')
const alerteStock = ref('')
const stocks = ref([])
const recentMouvements = ref([])
const loading = ref(false)
const error = ref(null)

// Valorisation data
const valoriseRows = ref([])
const valoriseGrouped = ref([])
const valoriseTotals = ref({ valeur_comptable: 0, valeur_vente_potentielle: 0 })
const groupBy = ref('filiale')
const selectedFilialeName = ref('')
const selectedSite = ref('')
const selectedDepot = ref('')
const structure = ref([])

const loadStockData = async () => {
  loading.value = true
  error.value = null
  try {
    const [stockResponse, mouvementsResponse, structureResponse] = await Promise.all([
      stockService.getStock(),
      stockService.getMouvements(),
      stockService.getStructureOrganisation()
    ])
    stocks.value = stockResponse.data || []
    recentMouvements.value = mouvementsResponse.data || []
    structure.value = structureResponse.data || []

    // Load valorisation initially
    await loadValorisation()
  } catch (err) {
    error.value = 'Erreur lors du chargement des données de stock'
    console.error('Erreur chargement stock:', err)
  } finally {
    loading.value = false
  }
}

const filteredStocks = computed(() => {
  return stocks.value.filter(stock => {
    const matchFiliale = !selectedFiliale.value || stock.filiale === selectedFiliale.value
    const matchArticle = !searchArticle.value || 
      stock.article.toLowerCase().includes(searchArticle.value.toLowerCase()) ||
      stock.reference.toLowerCase().includes(searchArticle.value.toLowerCase())
    const matchAlerte = !alerteStock.value ||
      (alerteStock.value === 'bas' && stock.quantite_actuelle < 10) ||
      (alerteStock.value === 'zero' && stock.quantite_actuelle === 0)
    
    return matchFiliale && matchArticle && matchAlerte
  })
})

const stockDisponible = computed(() => {
  return stocks.value.filter(s => s.quantite_actuelle >= 10).length
})

const stockBas = computed(() => {
  return stocks.value.filter(s => s.quantite_actuelle > 0 && s.quantite_actuelle < 10).length
})

const stockRupture = computed(() => {
  return stocks.value.filter(s => s.quantite_actuelle === 0).length
})

const valeurTotaleStock = computed(() => {
  return stocks.value.reduce((sum, s) => sum + (parseFloat(s.valeur_stock_total) || 0), 0)
})

const getStockBadgeClass = (quantite) => {
  if (quantite === 0) return 'badge badge-danger'
  if (quantite < 10) return 'badge badge-warning'
  return 'badge badge-success'
}

const getStockStatus = (quantite) => {
  if (quantite === 0) return 'Rupture'
  if (quantite < 10) return 'Stock bas'
  return 'Disponible'
}

const getTypeBadgeClass = (type) => {
  switch(type) {
    case 'ACHAT': return 'badge badge-success'
    case 'VENTE': return 'badge badge-info'
    case 'INVENTAIRE': return 'badge badge-warning'
    case 'TRANSFERT': return 'badge badge-secondary'
    default: return 'badge badge-secondary'
  }
}

const formatCurrency = (amount) => {
  const value = parseFloat(amount) || 0
  return new Intl.NumberFormat('fr-MG', {
    style: 'currency',
    currency: 'MGA',
    minimumFractionDigits: 0,
    maximumFractionDigits: 2
  }).format(value)
}

const formatDate = (date) => {
  return new Date(date).toLocaleDateString('fr-FR')
}

const formatDateTime = (datetime) => {
  return new Date(datetime).toLocaleString('fr-FR', {
    year: 'numeric',
    month: '2-digit',
    day: '2-digit',
    hour: '2-digit',
    minute: '2-digit'
  })
}

const structureFilteredFiliales = computed(() => {
  const set = new Set()
  structure.value.forEach(s => set.add(s.entreprise))
  return Array.from(set)
})

// Computed for cascading depot selection
const filteredSites = computed(() => {
  if (!selectedFilialeName.value) return []
  const filtered = structure.value.filter(s => s.entreprise === selectedFilialeName.value)
  return [...new Set(filtered.map(s => s.site))]
})

const filteredDepots = computed(() => {
  if (!selectedSite.value) return []
  return structure.value
    .filter(s => s.entreprise === selectedFilialeName.value && s.site === selectedSite.value)  // API returns site_geo as 'site'
    .map(s => ({ id: s.depot_id, nom: s.depot }))  // API returns depot_logistique as 'depot'
})

const groupByHeader = computed(() => {
  switch(groupBy.value) {
    case 'filiale': return 'Filiale'
    case 'site': return 'Site'
    case 'depot': return 'Dépôt'
    default: return 'Article'
  }
})

const aggregateValorise = (rows) => {
  const grouped = {}
  rows.forEach(r => {
    let key = ''
    switch(groupBy.value) {
      case 'filiale': key = r.filiale || 'Autres'; break
      case 'site': key = r.site || 'Autres'; break
      case 'depot': key = r.depot || 'Autres'; break
      case 'article': key = r.reference || r.designation || 'Autres'; break
    }
    if (!grouped[key]) grouped[key] = { valeur_comptable: 0, valeur_vente_potentielle: 0 }
    // Utiliser valeur_comptable ou valeur_stock_total selon ce que l'API retourne
    const valeurComptable = parseFloat(r.valeur_comptable) || parseFloat(r.valeur_stock_total) || 0
    const valeurVente = parseFloat(r.valeur_vente_potentielle) || (parseFloat(r.quantite_actuelle) * parseFloat(r.prix_vente_ref)) || 0
    grouped[key].valeur_comptable += valeurComptable
    grouped[key].valeur_vente_potentielle += valeurVente
  })

  const result = Object.keys(grouped).map(k => ({ key: k, valeur_comptable: grouped[k].valeur_comptable, valeur_vente_potentielle: grouped[k].valeur_vente_potentielle }))
  // Sort desc by valeur comptable
  result.sort((a,b) => b.valeur_comptable - a.valeur_comptable)

  valoriseGrouped.value = result
  valoriseTotals.value = result.reduce((acc, cur) => ({ valeur_comptable: acc.valeur_comptable + cur.valeur_comptable, valeur_vente_potentielle: acc.valeur_vente_potentielle + cur.valeur_vente_potentielle }), { valeur_comptable: 0, valeur_vente_potentielle: 0 })
}

const loadValorisation = async () => {
  try {
    const params = {}
    if (selectedFilialeName.value) params.filiale = selectedFilialeName.value
    if (selectedSite.value) params.site = selectedSite.value
    if (selectedDepot.value) params.depot = selectedDepot.value

    const res = await stockService.getValorise(params)
    valoriseRows.value = res.data || []
    aggregateValorise(valoriseRows.value)
  } catch (err) {
    console.error('Erreur chargement valorisation:', err)
  }
}

const openMouvementModal = () => {
  router.push({ name: 'mouvement-stock' })
}

const viewHistorique = (stock) => {
  router.push({ name: 'stock-depot-details', params: { depotId: stock.depot_id } })
}

const onFilialeChange = () => {
  selectedSite.value = ''
  selectedDepot.value = ''
}

const onSiteChange = () => {
  selectedDepot.value = ''
}

const voirDetailsDepot = () => {
  if (!selectedDepot.value) return
  const depotId = selectedDepot.value.depot_id || selectedDepot.value.id || selectedDepot.value
  router.push({ name: 'stock-depot-details', params: { depotId } })
}

const viewDepotDetails = (depotId) => {
  if (!depotId) return
  router.push({ name: 'stock-depot-details', params: { depotId } })
}

onMounted(() => {
  loadStockData()
})

// Recharger la valorisation quand le regroupement ou les filtres changent
watch([groupBy, selectedFilialeName, selectedSite, selectedDepot], () => {
  loadValorisation()
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

.error-state {
  @apply flex flex-col items-center justify-center py-20;
}

.error-icon {
  @apply text-red-600 mb-4;
}

.error-message {
  @apply text-sm text-red-600 mb-4;
}

.content-wrapper {
  @apply space-y-6;
}

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

.page-header {
  @apply flex items-center justify-between mb-6;
}

.page-title {
  @apply text-2xl font-bold text-gray-900;
}

.page-subtitle {
  @apply text-sm text-gray-500 mt-1;
}

.stats-grid {
  @apply grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4;
}

.stat-card {
  @apply bg-white rounded-lg border border-gray-200 p-5 hover:shadow-md transition-all;
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

.card {
  @apply bg-white rounded-lg border border-gray-200 p-5;
}

.card-header-simple {
  @apply mb-4 pb-3 border-b border-gray-200;
}

.card-title {
  @apply text-base font-semibold text-gray-900;
}

.filter-grid {
  @apply grid grid-cols-1 md:grid-cols-3 gap-4;
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
  @apply w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-gray-900 focus:border-gray-900 outline-none text-sm;
}

.select-sm {
  @apply px-3 py-1.5 text-sm w-auto min-w-[140px];
}

.table-wrapper {
  @apply overflow-x-auto;
}

.table {
  @apply w-full;
}

.table-compact th {
  @apply px-4 py-2;
}

.table-compact td {
  @apply px-4 py-2;
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

.table-row.bg-red-50:hover {
  @apply bg-red-100;
}

.table-row.bg-orange-50:hover {
  @apply bg-orange-100;
}

.table-actions {
  @apply flex items-center justify-center gap-2;
}

.action-btn {
  @apply p-2 rounded-lg text-gray-600 hover:bg-gray-100 hover:text-gray-900 transition-all;
}

.badge {
  @apply inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium;
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

.badge-secondary {
  @apply bg-gray-100 text-gray-700;
}
</style>