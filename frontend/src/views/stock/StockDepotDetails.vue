<template>
  <div class="page-container">
    <!-- Header -->
    <div class="page-header fade-in" style="animation-delay: 0.1s">
      <div>
        <h1 class="page-title">Détails Stock Dépôt</h1>
        <p class="page-subtitle">Valorisation et inventaire par dépôt</p>
      </div>
      <button @click="$router.go(-1)" class="btn-secondary">
        <ArrowLeft class="w-4 h-4" />
        <span>Retour</span>
      </button>
    </div>

    <!-- Depot Info Card -->
    <div class="depot-info-card fade-in" style="animation-delay: 0.15s" v-if="depotInfo">
      <div class="depot-header">
        <div>
          <h2 class="depot-name">{{ depotInfo.depot }}</h2>
          <p class="depot-location">
            {{ depotInfo.filiale }} › {{ depotInfo.site }}
          </p>
        </div>
        <div class="valorisation-badge">
          <span class="badge-label">Méthode de valorisation</span>
          <span class="badge-value" :class="getMethodeClass(depotInfo.methode_valorisation)">
            {{ depotInfo.methode_valorisation }}
          </span>
        </div>
      </div>
    </div>

    <!-- Stats Cards -->
    <div class="stats-grid fade-in" style="animation-delay: 0.2s">
      <div class="stat-card">
        <div class="stat-content">
          <p class="stat-label">Articles en stock</p>
          <h3 class="stat-value">{{ stockArticles.length }}</h3>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-content">
          <p class="stat-label">Valeur totale</p>
          <h3 class="stat-value text-green-600">{{ formatCurrency(valeurTotale) }}</h3>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-content">
          <p class="stat-label">Lots actifs</p>
          <h3 class="stat-value text-blue-600">{{ totalLotsActifs }}</h3>
        </div>
      </div>
    </div>

    <!-- Filters -->
    <div class="filters-section fade-in" style="animation-delay: 0.25s">
      <div class="filter-group">
        <label class="filter-label">Filtrer par article</label>
        <input 
          v-model="searchTerm" 
          type="text" 
          placeholder="Rechercher un article..." 
          class="filter-input"
        />
      </div>
      <div class="filter-group">
        <label class="filter-label">Affichage</label>
        <select v-model="viewMode" class="filter-select">
          <option value="articles">Articles</option>
          <option value="lots">Lots détaillés</option>
        </select>
      </div>
    </div>

    <!-- Articles View -->
    <div v-if="viewMode === 'articles'" class="articles-section fade-in" style="animation-delay: 0.3s">
      <div class="section-header">
        <h2 class="section-title">Articles en Stock</h2>
      </div>
      
      <div class="articles-grid">
        <div 
          v-for="article in filteredArticles" 
          :key="article.article_id"
          class="article-card"
          @click="selectArticle(article)"
        >
          <div class="article-header">
            <div>
              <h3 class="article-name">{{ article.designation }}</h3>
              <p class="article-ref">{{ article.reference }}</p>
            </div>
            <div class="article-quantity">
              <span class="quantity-value">{{ article.quantite_actuelle }}</span>
              <span class="quantity-unit">{{ article.unite || 'unités' }}</span>
            </div>
          </div>
          
          <div class="article-details">
            <div class="detail-row" v-if="depotInfo.methode_valorisation === 'CMUP'">
              <span class="detail-label">CMUP actuel</span>
              <span class="detail-value">{{ formatCurrency(article.cmup_actuel) }}</span>
            </div>
            <div class="detail-row" v-else>
              <span class="detail-label">Méthode valorisation</span>
              <span class="detail-value" :class="getMethodeClass(depotInfo.methode_valorisation)">{{ depotInfo.methode_valorisation }}</span>
            </div>
            <div class="detail-row">
              <span class="detail-label">Valeur stock</span>
              <span class="detail-value text-green-600">{{ article.valeur_stock_total ? formatCurrency(article.valeur_stock_total) : 'Calculé par lots' }}</span>
            </div>
            <div class="detail-row">
              <span class="detail-label">Dernière MAJ</span>
              <span class="detail-value">{{ formatDate(article.date_maj) }}</span>
            </div>
          </div>
          
          <div class="article-lots" v-if="article.lots && article.lots.length > 0">
            <h4 class="lots-title">Lots ({{ article.lots.length }})</h4>
            <div class="lots-list">
              <div 
                v-for="lot in article.lots.slice(0, 3)" 
                :key="lot.id"
                class="lot-item"
              >
                <span class="lot-number">{{ lot.numero_lot }}</span>
                <span class="lot-quantity">{{ lot.quantite_restante }}</span>
                <span class="lot-date">{{ formatDateShort(lot.date_entree) }}</span>
              </div>
              <div v-if="article.lots.length > 3" class="lot-item-more">
                +{{ article.lots.length - 3 }} autres...
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Lots Detailed View -->
    <div v-if="viewMode === 'lots'" class="lots-section fade-in" style="animation-delay: 0.3s">
      <div class="section-header">
        <h2 class="section-title">Détail des Lots</h2>
      </div>
      
      <div class="lots-table">
        <table class="data-table">
          <thead>
            <tr>
              <th>Numéro de lot</th>
              <th>Article</th>
              <th>Date d'entrée</th>
              <th>Qté initiale</th>
              <th>Qté restante</th>
              <th>Prix unitaire</th>
              <th>Valeur restante</th>
              <th>Statut</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="lot in filteredLots" :key="lot.id" class="table-row">
              <td class="font-mono">{{ lot.numero_lot }}</td>
              <td>
                <div>
                  <div class="font-medium">{{ lot.article_designation }}</div>
                  <div class="text-sm text-gray-500">{{ lot.article_reference }}</div>
                </div>
              </td>
              <td>{{ formatDate(lot.date_entree) }}</td>
              <td>{{ lot.quantite_initiale }}</td>
              <td class="font-semibold">{{ lot.quantite_restante }}</td>
              <td>{{ formatCurrency(lot.prix_unitaire_achat) }}</td>
              <td class="font-semibold text-green-600">
                {{ formatCurrency((Number(lot.quantite_restante) || 0) * (Number(lot.prix_unitaire_achat) || 0)) }}
              </td>
              <td>
                <span :class="getStatutClass(lot.statut)" class="status-badge">
                  {{ lot.statut }}
                </span>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Article Detail Modal -->
    <div v-if="selectedArticle" class="modal-overlay" @click="closeModal">
      <div class="modal-content" @click.stop>
        <div class="modal-header">
          <h3 class="modal-title">{{ selectedArticle.designation }}</h3>
          <button @click="closeModal" class="btn-close">
            <X class="w-4 h-4" />
          </button>
        </div>
        
        <div class="modal-body">
          <!-- Article Summary -->
          <div class="summary-section">
            <h4 class="subsection-title">Résumé Article</h4>
            <div class="summary-grid">
              <div class="summary-item">
                <label>Référence</label>
                <span>{{ selectedArticle.reference }}</span>
              </div>
              <div class="summary-item">
                <label>Quantité en stock</label>
                <span class="font-semibold">{{ selectedArticle.quantite_actuelle }}</span>
              </div>
              <div class="summary-item" v-if="depotInfo.methode_valorisation === 'CMUP'">
                <label>CMUP actuel</label>
                <span>{{ formatCurrency(selectedArticle.cmup_actuel) }}</span>
              </div>
              <div class="summary-item" v-else>
                <label>Méthode valorisation</label>
                <span :class="getMethodeClass(depotInfo.methode_valorisation)">{{ depotInfo.methode_valorisation }}</span>
              </div>
              <div class="summary-item">
                <label>Valeur totale</label>
                <span class="font-semibold text-green-600">{{ selectedArticle.valeur_stock_total ? formatCurrency(selectedArticle.valeur_stock_total) : 'Calculé par lots' }}</span>
              </div>
            </div>
          </div>

          <!-- Lots Details -->
          <div class="lots-detail-section" v-if="selectedArticle.lots && selectedArticle.lots.length > 0">
            <h4 class="subsection-title">Détail des Lots</h4>
            <div class="lots-detail-table">
              <table class="data-table">
                <thead>
                  <tr>
                    <th>Numéro</th>
                    <th>Date entrée</th>
                    <th>Qté restante</th>
                    <th>Prix unitaire</th>
                    <th>Valeur</th>
                    <th>Âge (jours)</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="lot in selectedArticle.lots" :key="lot.id">
                    <td class="font-mono text-sm">{{ lot.numero_lot }}</td>
                    <td>{{ formatDateShort(lot.date_entree) }}</td>
                    <td class="font-semibold">{{ lot.quantite_restante }}</td>
                    <td>{{ formatCurrency(lot.prix_unitaire_achat) }}</td>
                    <td class="text-green-600">{{ formatCurrency((Number(lot.quantite_restante) || 0) * (Number(lot.prix_unitaire_achat) || 0)) }}</td>
                    <td>{{ calculateAge(lot.date_entree) }}</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>

          <!-- Historical Movements -->
          <div class="movements-section" v-if="selectedArticle.mouvements && selectedArticle.mouvements.length > 0">
            <h4 class="subsection-title">Mouvements Récents</h4>

            <!-- Prix par date -->
            <div v-if="selectedArticle.priceHistory && selectedArticle.priceHistory.length > 0" class="price-history">
              <h5 class="text-sm font-semibold mb-2">Historique prix (dernier 10)</h5>
              <ul class="price-history-list mb-4">
                <li v-for="(ph, idx) in selectedArticle.priceHistory" :key="idx" class="text-sm text-gray-700">
                  {{ formatDate(ph.date) }} — {{ formatCurrency(ph.prix) }} <span class="text-xs text-gray-500">(qté: {{ ph.quantite }})</span>
                </li>
              </ul>
            </div>

            <div class="movements-list">
              <div v-for="mouvement in selectedArticle.mouvements.slice(0, 10)" :key="mouvement.id" class="movement-item">
                <div class="movement-date">{{ formatDate(mouvement.date_mouvement) }}</div>
                <div class="movement-type">{{ mouvement.type_mouvement }}</div>
                <div class="movement-quantity" :class="Number(mouvement.quantite_entree) > 0 ? 'text-green-600' : 'text-red-600'">
                  {{ Number(mouvement.quantite_entree) > 0 ? '+' + Number(mouvement.quantite_entree) : '-' + Number(mouvement.quantite_sortie) }}
                </div>
                <div class="movement-price text-sm text-gray-700">
                  <template v-if="mouvement.prix_unitaire_mouvement !== null && mouvement.prix_unitaire_mouvement !== undefined">
                    {{ formatCurrency(mouvement.prix_unitaire_mouvement) }}
                    <span class="text-xs text-gray-500"> • {{ formatCurrency((Number(mouvement.quantite_entree) > 0 ? Number(mouvement.quantite_entree) : Number(mouvement.quantite_sortie) || 0) * (Number(mouvement.prix_unitaire_mouvement) || 0)) }}</span>
                  </template>
                </div>
                <div class="movement-ref text-sm text-gray-500">{{ mouvement.reference_document || 'N/A' }}</div>
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
import { useRoute } from 'vue-router'
import { ArrowLeft, X } from 'lucide-vue-next'
import stockService from '@/services/stockService'

const route = useRoute()

// Reactive data
const loading = ref(true)
const error = ref('')
const depotInfo = ref(null)
const stockArticles = ref([])
const stockLots = ref([])
const selectedArticle = ref(null)
const searchTerm = ref('')
const viewMode = ref('articles')

// Computed properties
const valeurTotale = computed(() => {
  return stockArticles.value.reduce((sum, article) => {
    const valeur = parseFloat(article.valeur_stock_total) || 0
    return sum + valeur
  }, 0)
})

const totalLotsActifs = computed(() => {
  return stockLots.value.filter(lot => lot.statut === 'ACTIF').length
})

const filteredArticles = computed(() => {
  if (!searchTerm.value) return stockArticles.value
  return stockArticles.value.filter(article => 
    article.designation.toLowerCase().includes(searchTerm.value.toLowerCase()) ||
    article.reference.toLowerCase().includes(searchTerm.value.toLowerCase())
  )
})

const filteredLots = computed(() => {
  if (!searchTerm.value) return stockLots.value
  return stockLots.value.filter(lot => 
    lot.numero_lot.toLowerCase().includes(searchTerm.value.toLowerCase()) ||
    lot.article_designation.toLowerCase().includes(searchTerm.value.toLowerCase()) ||
    lot.article_reference.toLowerCase().includes(searchTerm.value.toLowerCase())
  )
})

// Methods
const loadStockDetails = async () => {
  try {
    loading.value = true
    error.value = ''
    
    const depotId = route.params.depotId || route.query.depotId
    console.log('🔍 Debug - depotId récupéré:', depotId)
    console.log('🔍 Debug - route.params:', route.params)
    console.log('🔍 Debug - route.query:', route.query)
    
    if (!depotId) {
      throw new Error('ID du dépôt manquant')
    }

    console.log('📡 Chargement des données pour le dépôt:', depotId)

    // Load depot info and stock data
    const [depotResponse, articlesResponse, lotsResponse] = await Promise.all([
      stockService.getDepotInfo(depotId),
      stockService.getStockByDepot(depotId),
      stockService.getLotsByDepot(depotId)
    ])

    console.log('📊 Réponses API:')
    console.log('  - depotResponse:', depotResponse)
    console.log('  - articlesResponse:', articlesResponse)
    console.log('  - lotsResponse:', lotsResponse)

    depotInfo.value = depotResponse.data
    stockArticles.value = articlesResponse.data
    stockLots.value = lotsResponse.data

    console.log('💾 Données chargées:')
    console.log('  - depotInfo:', depotInfo.value)
    console.log('  - stockArticles:', stockArticles.value)
    console.log('  - stockLots:', stockLots.value)

    // Associate lots with articles
    stockArticles.value.forEach(article => {
      article.lots = stockLots.value.filter(lot => lot.article_id === article.article_id)
    })

  } catch (err) {
    console.error('❌ Erreur chargement données:', err)
    error.value = err.message || 'Erreur lors du chargement des données'
  } finally {
    loading.value = false
  }
}

const selectArticle = async (article) => {
  try {
    // Load detailed movements for this article
    const mouvementsResponse = await stockService.getMouvementsByArticle(article.article_id, route.params.depotId || route.query.depotId)
    article.mouvements = mouvementsResponse.data

    // Extraire l'historique des prix (mouvements avec prix)
    article.priceHistory = (mouvementsResponse.data || [])
      .filter(m => m.prix_unitaire_mouvement !== null && m.prix_unitaire_mouvement !== undefined)
      .map(m => ({ date: m.date_mouvement, prix: m.prix_unitaire_mouvement, quantite: Number(m.quantite_entree) > 0 ? Number(m.quantite_entree) : Number(m.quantite_sortie) }))
      .slice(0, 10)

    selectedArticle.value = article
  } catch (err) {
    console.error('Erreur chargement mouvements:', err)
    selectedArticle.value = article // Show modal anyway without movements
  }
}

const closeModal = () => {
  selectedArticle.value = null
}

// Utility methods
const getMethodeClass = (methode) => {
  const classes = {
    'CMUP': 'bg-blue-100 text-blue-800',
    'FIFO': 'bg-green-100 text-green-800', 
    'LIFO': 'bg-purple-100 text-purple-800'
  }
  return classes[methode] || 'bg-gray-100 text-gray-800'
}

const getStatutClass = (statut) => {
  return statut === 'ACTIF' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'
}

const formatCurrency = (value) => {
  const numValue = parseFloat(value) || 0
  return new Intl.NumberFormat('fr-MG', {
    style: 'currency',
    currency: 'MGA',
    minimumFractionDigits: 0
  }).format(numValue)
}

const formatDate = (date) => {
  if (!date) return 'N/A'
  return new Date(date).toLocaleDateString('fr-FR', {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

const formatDateShort = (date) => {
  if (!date) return 'N/A'
  return new Date(date).toLocaleDateString('fr-FR')
}

const calculateAge = (dateEntree) => {
  if (!dateEntree) return 'N/A'
  const today = new Date()
  const entree = new Date(dateEntree)
  const diffTime = Math.abs(today - entree)
  const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24))
  return `${diffDays} j`
}

// Lifecycle
onMounted(() => {
  loadStockDetails()
})
</script>

<style scoped>
.page-container {
  @apply min-h-screen bg-gray-50 p-6;
  /* Ajuster la marge pour éviter le chevauchement avec la sidebar */
  margin-left: 256px; /* Sidebar fixe de 256px */
}

@media (max-width: 768px) {
  .page-container {
    margin-left: 0; /* Pas de marge sur mobile */
  }
}

.page-header {
  @apply flex justify-between items-start mb-8;
}

.page-title {
  @apply text-3xl font-bold text-gray-900 mb-2;
}

.page-subtitle {
  @apply text-gray-600 text-lg;
}

.depot-info-card {
  @apply bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-6;
}

.depot-header {
  @apply flex justify-between items-start;
}

.depot-name {
  @apply text-xl font-semibold text-gray-900 mb-1;
}

.depot-location {
  @apply text-gray-600;
}

.valorisation-badge {
  @apply text-right;
}

.badge-label {
  @apply block text-sm text-gray-600 mb-1;
}

.badge-value {
  @apply inline-block px-3 py-1 rounded-full text-sm font-medium;
}

.stats-grid {
  @apply grid grid-cols-1 md:grid-cols-3 gap-6 mb-8;
}

.stat-card {
  @apply bg-white rounded-xl shadow-sm border border-gray-200 p-6;
}

.stat-content {
  @apply text-center;
}

.stat-label {
  @apply text-gray-600 text-sm mb-2;
}

.stat-value {
  @apply text-2xl font-bold;
}

.filters-section {
  @apply bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-6;
}

.filter-group {
  @apply flex flex-col space-y-2 sm:flex-row sm:space-y-0 sm:space-x-4 mb-4 last:mb-0;
}

.filter-label {
  @apply text-sm font-medium text-gray-700 min-w-max;
}

.filter-input, .filter-select {
  @apply border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent;
}

.articles-section {
  @apply mb-8;
}

.section-header {
  @apply mb-6;
}

.section-title {
  @apply text-xl font-semibold text-gray-900;
}

.articles-grid {
  @apply grid grid-cols-1 lg:grid-cols-2 xl:grid-cols-3 gap-6;
}

.article-card {
  @apply bg-white rounded-xl shadow-sm border border-gray-200 p-6 cursor-pointer transition-all duration-200 hover:shadow-md hover:border-blue-300;
}

.article-header {
  @apply flex justify-between items-start mb-4;
}

.article-name {
  @apply font-semibold text-gray-900 mb-1;
}

.article-ref {
  @apply text-sm text-gray-600;
}

.article-quantity {
  @apply text-right;
}

.quantity-value {
  @apply block text-xl font-bold text-blue-600;
}

.quantity-unit {
  @apply text-sm text-gray-600;
}

.article-details {
  @apply space-y-2 mb-4;
}

.detail-row {
  @apply flex justify-between;
}

.detail-label {
  @apply text-sm text-gray-600;
}

.detail-value {
  @apply text-sm font-medium;
}

.lots-title {
  @apply text-sm font-medium text-gray-900 mb-2;
}

.lots-list {
  @apply space-y-1;
}

.lot-item {
  @apply flex justify-between text-xs text-gray-600 py-1;
}

.lot-item-more {
  @apply text-xs text-blue-600 font-medium pt-1;
}

.lots-table {
  @apply bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden;
}

.data-table {
  @apply w-full;
}

.data-table th {
  @apply px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider bg-gray-50;
}

.data-table td {
  @apply px-6 py-4 whitespace-nowrap text-sm text-gray-900;
}

.table-row {
  @apply border-b border-gray-200 hover:bg-gray-50;
}

.status-badge {
  @apply inline-block px-2 py-1 text-xs font-medium rounded-full;
}

.modal-overlay {
  @apply fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4;
}

.modal-content {
  @apply bg-white rounded-xl shadow-xl max-w-4xl w-full max-h-screen overflow-y-auto;
}

.modal-header {
  @apply flex justify-between items-center p-6 border-b border-gray-200;
}

.modal-title {
  @apply text-xl font-semibold text-gray-900;
}

.modal-body {
  @apply p-6 space-y-6;
}

.subsection-title {
  @apply text-lg font-medium text-gray-900 mb-3;
}

.summary-grid {
  @apply grid grid-cols-2 md:grid-cols-4 gap-4;
}

.summary-item {
  @apply flex flex-col space-y-1;
}

.summary-item label {
  @apply text-sm text-gray-600;
}

.summary-item span {
  @apply text-sm font-medium text-gray-900;
}

.movements-list {
  @apply space-y-2 max-h-64 overflow-y-auto;
}

.movement-item {
  @apply flex items-center space-x-4 py-2 px-3 bg-gray-50 rounded-lg;
}

.btn-secondary {
  @apply inline-flex items-center space-x-2 px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg transition-colors;
}

.btn-close {
  @apply p-2 hover:bg-gray-100 rounded-lg transition-colors;
}

.btn-primary {
  @apply inline-flex items-center space-x-2 px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-colors font-medium;
}

.fade-in {
  @apply opacity-0 animate-fadeIn;
}

@keyframes fadeIn {
  from { opacity: 0; transform: translateY(20px); }
  to { opacity: 1; transform: translateY(0); }
}

.animate-fadeIn {
  animation: fadeIn 0.5s ease-out forwards;
}
</style>