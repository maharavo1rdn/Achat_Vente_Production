<template>
  <div class="page-container">
    <!-- Loading state -->
    <div v-if="loading" class="loading-state">
      <div class="spinner"></div>
      <p class="loading-text">Chargement des articles...</p>
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
      <button @click="loadArticles" class="btn-primary">
        Réessayer
      </button>
    </div>

    <!-- Content -->
    <div v-else class="content-wrapper">
      <!-- Header -->
      <div class="page-header fade-in" style="animation-delay: 0.1s">
        <div>
          <h1 class="page-title">Gestion des Articles</h1>
          <p class="page-subtitle">Gérez votre catalogue de produits</p>
        </div>
        <button @click="openCreateModal" class="btn-primary">
          <Plus class="w-4 h-4" />
          <span>Nouvel Article</span>
        </button>
      </div>

      <!-- Stats Cards -->
      <div class="stats-grid fade-in" style="animation-delay: 0.15s">
        <div class="stat-card">
          <div class="stat-content">
            <p class="stat-label">Total articles</p>
            <h3 class="stat-value">{{ articles.length }}</h3>
          </div>
        </div>
        <div class="stat-card">
          <div class="stat-content">
            <p class="stat-label">Articles actifs</p>
            <h3 class="stat-value text-green-600">{{ activeArticles }}</h3>
          </div>
        </div>
        <div class="stat-card">
          <div class="stat-content">
            <p class="stat-label">Articles inactifs</p>
            <h3 class="stat-value text-red-600">{{ inactiveArticles }}</h3>
          </div>
        </div>
        <div class="stat-card">
          <div class="stat-content">
            <p class="stat-label">Catégories</p>
            <h3 class="stat-value text-blue-600">{{ categoriesCount }}</h3>
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
              <input v-model="searchQuery" type="text" class="search-input" placeholder="Référence, désignation..." />
            </div>
          </div>
          <div class="filter-item">
            <label class="label">Catégorie</label>
            <select v-model="selectedCategorie" class="select">
              <option value="">Toutes les catégories</option>
              <option value="1">Matières Premières</option>
              <option value="2">Produits Finis</option>
            </select>
          </div>
          <div class="filter-item">
            <label class="label">Statut</label>
            <select v-model="selectedStatut" class="select">
              <option value="">Tous</option>
              <option value="actif">Actif</option>
              <option value="inactif">Inactif</option>
            </select>
          </div>
        </div>
      </div>

      <!-- View Toggle -->
      <div class="view-toggle fade-in" style="animation-delay: 0.25s">
        <div class="toggle-group">
          <button @click="viewMode = 'grid'" :class="viewMode === 'grid' ? 'toggle-btn-active' : 'toggle-btn'">
            <Grid3x3 class="w-4 h-4" />
            <span>Grille</span>
          </button>
          <button @click="viewMode = 'table'" :class="viewMode === 'table' ? 'toggle-btn-active' : 'toggle-btn'">
            <List class="w-4 h-4" />
            <span>Liste</span>
          </button>
        </div>
      </div>

      <!-- Grid View -->
      <div v-if="viewMode === 'grid'" class="grid-view fade-in" style="animation-delay: 0.3s">
        <div v-for="article in filteredArticles" :key="article.id" class="article-card" @click="goToDetail(article.id)">
          <div class="article-header">
            <div class="article-info">
              <h3 class="article-title">{{ article.designation }}</h3>
              <p class="article-ref">Réf: {{ article.reference }}</p>
            </div>
            <span :class="article.est_actif ? 'badge badge-success' : 'badge badge-danger'">
              {{ article.est_actif ? 'Actif' : 'Inactif' }}
            </span>
          </div>

          <div class="article-details">
            <div class="detail-row">
              <span class="detail-label">Catégorie</span>
              <span class="detail-value">{{ article.categorie }}</span>
            </div>
            <div class="divider"></div>
            <div class="detail-row">
              <span class="detail-label">Prix achat</span>
              <span class="detail-value-price">{{ formatCurrency(article.prix_achat_ref) }}</span>
            </div>
            <div class="detail-row">
              <span class="detail-label">Prix vente</span>
              <span class="detail-value-price-green">{{ formatCurrency(article.prix_vente_ref) }}</span>
            </div>
            <div class="divider"></div>
            <div class="detail-row">
              <span class="detail-label">Unité</span>
              <span class="detail-value">{{ article.unite }}</span>
            </div>
          </div>

          <div class="article-actions">
            <button @click.stop="goToDetail(article.id)" class="action-btn-primary">
              <Eye class="w-4 h-4" />
              <span>Détails</span>
            </button>
            <button @click.stop="editArticle(article)" class="action-btn-icon">
              <Edit class="w-4 h-4" />
            </button>
            <button @click.stop="deleteArticle(article.id)" class="action-btn-icon-danger">
              <Trash2 class="w-4 h-4" />
            </button>
          </div>
        </div>
      </div>

      <!-- Table View -->
      <div v-else class="card fade-in" style="animation-delay: 0.3s">
        <div class="table-wrapper">
          <table class="table">
            <thead>
              <tr>
                <th>Référence</th>
                <th>Désignation</th>
                <th>Catégorie</th>
                <th>Unité</th>
                <th class="text-right">Prix Achat</th>
                <th class="text-right">Prix Vente</th>
                <th class="text-center">TVA</th>
                <th class="text-center">Statut</th>
                <th class="text-center">Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="article in filteredArticles" :key="article.id" class="table-row"
                @click="goToDetail(article.id)">
                <td class="font-medium">{{ article.reference }}</td>
                <td class="font-medium">{{ article.designation }}</td>
                <td>{{ article.categorie }}</td>
                <td>{{ article.unite }}</td>
                <td class="text-right font-semibold">{{ formatCurrency(article.prix_achat_ref) }}</td>
                <td class="text-right font-semibold text-green-600">{{ formatCurrency(article.prix_vente_ref) }}</td>
                <td class="text-center">{{ article.taux_tva ?? 0 }}%</td>
                <td class="text-center">
                  <span :class="article.est_actif ? 'badge badge-success' : 'badge badge-danger'">
                    {{ article.est_actif ? 'Actif' : 'Inactif' }}
                  </span>
                </td>
                <td>
                  <div class="table-actions" @click.stop>
                    <button @click="goToDetail(article.id)" class="action-btn">
                      <Eye class="w-4 h-4" />
                    </button>
                    <button @click="editArticle(article)" class="action-btn">
                      <Edit class="w-4 h-4" />
                    </button>
                    <button @click="deleteArticle(article.id)" class="action-btn text-red-600">
                      <Trash2 class="w-4 h-4" />
                    </button>
                  </div>
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
import { useRouter } from 'vue-router'
import { Plus, Edit, Trash2, Search, Grid3x3, List, Eye } from 'lucide-vue-next'
import articleService from '@/services/articleService'

const router = useRouter()
const searchQuery = ref('')
const selectedCategorie = ref('')
const selectedStatut = ref('')
const viewMode = ref('grid')
const articles = ref([])
const loading = ref(false)
const error = ref(null)

const filteredArticles = computed(() => {
  return articles.value.filter(article => {
    const matchSearch = !searchQuery.value ||
      article.reference?.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
      article.designation?.toLowerCase().includes(searchQuery.value.toLowerCase())

    const matchCategorie = !selectedCategorie.value || article.categorie === selectedCategorie.value
    const matchStatut = !selectedStatut.value ||
      (selectedStatut.value === 'actif' && article.est_actif) ||
      (selectedStatut.value === 'inactif' && !article.est_actif)

    return matchSearch && matchCategorie && matchStatut
  })
})

const activeArticles = computed(() => {
  return articles.value.filter(a => a.est_actif).length
})

const inactiveArticles = computed(() => {
  return articles.value.filter(a => !a.est_actif).length
})

const categoriesCount = computed(() => {
  return [...new Set(articles.value.map(a => a.categorie))].length
})

const formatCurrency = (amount) => {
  const num = Number(amount)
  return new Intl.NumberFormat('fr-MG', {
    style: 'currency',
    currency: 'MGA',
    minimumFractionDigits: 0
  }).format(isNaN(num) ? 0 : num)
}

const loadArticles = async () => {
  loading.value = true
  error.value = null
  try {
    const response = await articleService.getAll()
    articles.value = response.data || []
  } catch (err) {
    error.value = 'Erreur lors du chargement des articles'
    console.error('Erreur chargement articles:', err)
  } finally {
    loading.value = false
  }
}

const loadArticleCategories = async () => {
  loading.value = true
  error.value = null
  try {
    const response = await articleService.getAll()
    articles.value = response.data || []
  } catch (err) {
    error.value = 'Erreur lors du chargement des articles'
    console.error('Erreur chargement articles:', err)
  } finally {
    loading.value = false
  }

}

const openCreateModal = () => {
  console.log('Open create modal')
}

const editArticle = (article) => {
  console.log('Edit article:', article)
}

const deleteArticle = async (id) => {
  if (confirm('Êtes-vous sûr de vouloir supprimer cet article ?')) {
    try {
      await articleService.delete(id)
      articles.value = articles.value.filter(a => a.id !== id)
      alert('Article supprimé avec succès')
    } catch (err) {
      alert('Erreur lors de la suppression')
      console.error('Erreur suppression article:', err)
    }
  }
}

const goToDetail = (id) => {
  router.push({ name: 'article-detail', params: { id } })
}

onMounted(() => {
  loadArticles()
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
  to {
    transform: rotate(360deg);
  }
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

.filter-grid {
  @apply grid grid-cols-1 md:grid-cols-4 gap-4;
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

.grid-view {
  @apply grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4;
}

.article-card {
  @apply bg-white border border-gray-200 rounded-lg p-5 hover:shadow-lg hover:border-gray-900 transition-all cursor-pointer;
}

.article-header {
  @apply flex items-start justify-between mb-4;
}

.article-info {
  @apply flex-1;
}

.article-title {
  @apply font-semibold text-base text-gray-900 mb-1;
}

.article-ref {
  @apply text-xs text-gray-500;
}

.article-details {
  @apply space-y-3 mb-4;
}

.detail-row {
  @apply flex items-center justify-between text-sm;
}

.detail-label {
  @apply text-gray-500;
}

.detail-value {
  @apply font-medium text-gray-900;
}

.detail-value-price {
  @apply font-semibold text-gray-900;
}

.detail-value-price-green {
  @apply font-semibold text-green-600;
}

.divider {
  @apply border-t border-gray-100;
}

.article-actions {
  @apply flex gap-2 pt-4 border-t border-gray-100;
}

.action-btn-primary {
  @apply flex-1 flex items-center justify-center gap-2 px-4 py-2 bg-gray-900 text-white rounded-lg hover:bg-gray-800 transition-all text-sm font-medium;
}

.action-btn-icon {
  @apply p-2 rounded-lg text-gray-600 hover:bg-gray-100 hover:text-gray-900 transition-all;
}

.action-btn-icon-danger {
  @apply p-2 rounded-lg text-red-600 hover:bg-red-50 transition-all;
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
  @apply hover:bg-gray-50 transition-colors cursor-pointer;
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
</style>