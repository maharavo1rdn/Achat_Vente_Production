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

      <!-- Valorisation -->
      <div class="card fade-in" style="animation-delay: 0.22s">
        <div class="card-header-simple">
          <h2 class="card-title">Valorisation du Stock</h2>
          <div class="flex items-center gap-3">
            <label class="text-sm text-gray-600">Regrouper par</label>
            <select v-model="groupBy" class="select">
              <option value="filiale">Filiale</option>
              <option value="site">Site</option>
              <option value="depot">Dépôt</option>
              <option value="article">Article</option>
            </select>
            <button @click="loadValorisation" class="btn-primary">Actualiser</button>
          </div>
        </div>
        <div class="p-4">
          <div class="mb-4">
            <label class="label">Sélectionner un dépôt pour voir les détails</label>
            <div class="flex gap-3 items-end">
              <div class="flex-1">
                <label class="text-sm text-gray-600">Filiale</label>
                <select v-model="selectedFilialeName" class="select" @change="onFilialeChange">
                  <option value="">Choisir une filiale</option>
                  <option v-for="s in structureFilteredFiliales" :key="s" :value="s">{{ s }}</option>
                </select>
              </div>

              <div class="flex-1">
                <label class="text-sm text-gray-600">Site</label>
                <select v-model="selectedSite" class="select" @change="onSiteChange" :disabled="!selectedFilialeName">
                  <option value="">Choisir un site</option>
                  <option v-for="s in filteredSites" :key="s" :value="s">{{ s }}</option>
                </select>
              </div>

              <div class="flex-1">
                <label class="text-sm text-gray-600">Dépôt</label>
                <select v-model="selectedDepot" class="select" :disabled="!selectedSite">
                  <option value="">Choisir un dépôt</option>
                  <option v-for="d in filteredDepots" :key="d.id" :value="d.id">{{ d.nom }}</option>
                </select>
              </div>

              <button 
                @click="voirDetailsDepot" 
                :disabled="!selectedDepot"
                class="btn-primary disabled:opacity-50 disabled:cursor-not-allowed"
              >
                Voir détails
              </button>
            </div>
          </div>

          <div class="mb-4">
            <label class="label">Filtrer par Filiale / Site / Dépôt (pour tableau)</label>
            <div class="flex gap-3">
              <select v-model="selectedFilialeFilter" class="select">
                <option value="">Toutes les filiales</option>
                <option v-for="s in structureFilteredFiliales" :key="s" :value="s">{{ s }}</option>
              </select>

              <select v-model="selectedSiteFilter" class="select">
                <option value="">Tous les sites</option>
                <option v-for="s in structureFilteredSites" :key="s" :value="s">{{ s }}</option>
              </select>

              <select v-model="selectedDepotFilter" class="select">
                <option value="">Tous les dépôts</option>
                <option v-for="d in structureFilteredDepots" :key="d" :value="d">{{ d }}</option>
              </select>
            </div>
          </div>

          <div class="table-wrapper">
            <table class="table">
              <thead>
                <tr>
                  <th>{{ groupByHeader }}</th>
                  <th class="text-right">Valeur Comptable</th>
                  <th class="text-right">Valeur Vente Potentielle</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="row in valoriseGrouped" :key="row.key">
                  <td class="font-medium">{{ row.key }}</td>
                  <td class="text-right font-semibold">{{ formatCurrency(row.valeur_comptable) }}</td>
                  <td class="text-right font-semibold">{{ formatCurrency(row.valeur_vente_potentielle) }}</td>
                </tr>
                <tr class="border-t">
                  <td class="font-medium">Total</td>
                  <td class="text-right font-semibold">{{ formatCurrency(valoriseTotals.valeur_comptable) }}</td>
                  <td class="text-right font-semibold">{{ formatCurrency(valoriseTotals.valeur_vente_potentielle) }}</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- Stock Table -->
      <div class="card fade-in" style="animation-delay: 0.25s">
        <div class="card-header-simple">
          <h2 class="card-title">État du Stock</h2>
        </div>
        <div class="table-wrapper">
          <table class="table">
            <thead>
              <tr>
                <th>Article</th>
                <th>Référence</th>
                <th>Filiale</th>
                <th>Dépôt</th>
                <th class="text-center">Quantité</th>
                <th>Unité</th>
                <th class="text-right">CMUP</th>
                <th class="text-right">Valeur Stock</th>
                <th>Méthode Val.</th>
                <th>Dernière MAJ</th>
                <th class="text-center">Statut</th>
                <th class="text-center">Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="stock in filteredStocks" :key="stock.id" class="table-row">
                <td class="font-medium">{{ stock.article }}</td>
                <td class="text-gray-600">{{ stock.reference }}</td>
                <td>{{ stock.filiale }}</td>
                <td class="text-sm text-gray-600">{{ stock.depot }}</td>
                <td class="text-center font-semibold">{{ stock.quantite_actuelle }}</td>
                <td>{{ stock.unite }}</td>
                <td class="text-right">{{ formatCurrency(stock.cmup_actuel || 0) }}</td>
                <td class="text-right font-medium">{{ formatCurrency(stock.valeur_stock_total || 0) }}</td>
                <td class="text-xs">
                  <span class="badge badge-secondary">{{ stock.methode_valorisation_code || 'N/A' }}</span>
                </td>
                <td class="text-gray-600">{{ formatDate(stock.date_maj) }}</td>
                <td class="text-center">
                  <span :class="getStockBadgeClass(stock.quantite_actuelle)">
                    {{ getStockStatus(stock.quantite_actuelle) }}
                  </span>
                </td>
                <td>
                  <div class="table-actions">
                    <button @click="viewHistorique(stock)" class="action-btn" title="Historique">
                      <History class="w-4 h-4" />
                    </button>
                  </div>
                </td>
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

// Filter variables for table display
const selectedFilialeFilter = ref('')
const selectedSiteFilter = ref('')
const selectedDepotFilter = ref('')

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
  return new Intl.NumberFormat('fr-MG', {
    style: 'currency',
    currency: 'MGA',
    minimumFractionDigits: 0
  }).format(amount)
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

const structureFilteredSites = computed(() => {
  const set = new Set()
  structure.value.forEach(s => set.add(s.site))
  return Array.from(set)
})

const structureFilteredDepots = computed(() => {
  const set = new Set()
  structure.value.forEach(s => set.add(s.depot_logistique || s.depot))
  return Array.from(set)
})

// Computed for cascading depot selection
const filteredSites = computed(() => {
  console.log('filteredSites - selectedFilialeName:', selectedFilialeName.value)
  console.log('filteredSites - structure data:', structure.value)
  if (!selectedFilialeName.value) return []
  const filtered = structure.value.filter(s => s.entreprise === selectedFilialeName.value)
  console.log('filteredSites - filtered by entreprise:', filtered)
  const sites = filtered.map(s => s.site)  // API returns site_geo as 'site'
  console.log('filteredSites - sites mapped:', sites)
  const uniqueSites = [...new Set(sites)]
  console.log('filteredSites - unique sites:', uniqueSites)
  return uniqueSites
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
    grouped[key].valeur_comptable += parseFloat(r.valeur_comptable || 0)
    grouped[key].valeur_vente_potentielle += parseFloat(r.valeur_vente_potentielle || 0)
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
  console.log('Open mouvement modal')
}

const viewHistorique = (stock) => {
  console.log('View historique:', stock)
}

const onFilialeChange = () => {
  console.log('onFilialeChange - new filiale:', selectedFilialeName.value)
  selectedSite.value = ''
  selectedDepot.value = ''
  console.log('onFilialeChange - reset site and depot to empty string')
}

const onSiteChange = () => {
  selectedDepot.value = ''
}

const voirDetailsDepot = () => {
  if (!selectedDepot.value) {
    console.error('Aucun dépôt sélectionné')
    return
  }
  
  console.log('🔍 Debug selectedDepot:', selectedDepot.value)
  
  // Extract the depot ID from the selected depot object
  const depotId = selectedDepot.value.depot_id || selectedDepot.value.id || selectedDepot.value
  console.log('🔍 Debug depotId extrait:', depotId)
  
  router.push({ name: 'stock-depot-details', params: { depotId } })
}

const viewDepotDetails = (depotId) => {
  if (!depotId) {
    console.error('ID dépôt manquant')
    return
  }
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