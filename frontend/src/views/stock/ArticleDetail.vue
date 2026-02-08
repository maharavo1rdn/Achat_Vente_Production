<template>
  <div class="page-container">
    <!-- Loading state -->
    <div v-if="loading" class="loading-state">
      <div class="spinner"></div>
      <p class="loading-text">Chargement de l'article...</p>
    </div>

    <!-- Error state -->
    <div v-else-if="error" class="error-state">
      <div class="error-icon">
        <svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
        </svg>
      </div>
      <p class="error-message">{{ error }}</p>
      <button @click="loadArticle" class="btn-primary">
        Réessayer
      </button>
    </div>

    <!-- Content -->
    <div v-else class="content-wrapper">
      <!-- Header avec actions -->
      <div class="page-header fade-in" style="animation-delay: 0.1s">
        <div class="flex items-center gap-4">
          <button @click="$router.back()" class="action-btn">
            <ArrowLeft class="w-5 h-5" />
          </button>
          <div>
            <h1 class="page-title">{{ article.designation }}</h1>
            <p class="page-subtitle">Référence: {{ article.reference }}</p>
          </div>
        </div>
        <div class="flex gap-3">
          <button @click="openEditModal" class="btn-secondary flex items-center gap-2">
            <Edit class="w-4 h-4" />
            Modifier
          </button>
          <button @click="confirmDelete" class="btn-danger flex items-center gap-2">
            <Trash2 class="w-4 h-4" />
            Supprimer
          </button>
        </div>
      </div>

      <!-- Cartes d'information -->
      <div class="stats-grid fade-in" style="animation-delay: 0.15s">
        <!-- Informations générales -->
        <div class="stat-card">
          <div class="stat-content">
            <div class="flex items-center gap-2 mb-4">
              <Info class="w-5 h-5 text-gray-600" />
              <h3 class="text-lg font-semibold text-gray-900">Informations générales</h3>
            </div>
            <div class="space-y-3">
              <div>
                <div class="stat-label">Catégorie</div>
                <div class="stat-value text-base">{{ article.categorie }}</div>
              </div>
              <div>
                <div class="stat-label">Unité</div>
                <div class="stat-value text-base">{{ article.unite }}</div>
              </div>
              <div>
                <div class="stat-label">TVA</div>
                <div class="stat-value text-base">{{ article.taux_tva }}%</div>
              </div>
              <div>
                <div class="stat-label">Statut</div>
                <span v-if="article.est_actif" class="badge-success">Actif</span>
                <span v-else class="badge-danger">Inactif</span>
              </div>
            </div>
          </div>
        </div>

        <!-- Prix -->
        <div class="stat-card">
          <div class="stat-content">
            <div class="flex items-center gap-2 mb-4">
              <DollarSign class="w-5 h-5 text-gray-600" />
              <h3 class="text-lg font-semibold text-gray-900">Prix</h3>
            </div>
            <div class="space-y-4">
              <div>
                <div class="stat-label">Prix d'achat</div>
                <div class="text-2xl font-bold text-gray-900">
                  {{ formatPrice(article.prix_achat_ref) }}
                </div>
              </div>
              <div class="divider"></div>
              <div>
                <div class="stat-label">Prix de vente</div>
                <div class="text-2xl font-bold text-green-600">
                  {{ formatPrice(article.prix_vente_ref) }}
                </div>
              </div>
              <div class="divider"></div>
              <div>
                <div class="stat-label">Marge</div>
                <div class="text-xl font-bold text-blue-600">
                  {{ calculerMarge() }}%
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Stock total -->
        <div class="stat-card">
          <div class="stat-content">
            <div class="flex items-center gap-2 mb-4">
              <Package class="w-5 h-5 text-gray-600" />
              <h3 class="text-lg font-semibold text-gray-900">Stock total</h3>
            </div>
            <div class="text-center py-4">
              <div class="text-4xl font-bold text-gray-900 mb-2">
                {{ stockTotal }}
              </div>
              <div class="text-gray-500 uppercase text-sm tracking-wide">
                {{ article.unite }}
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Description -->
      <div v-if="article.description" class="card fade-in" style="animation-delay: 0.2s">
        <div class="flex items-center gap-2 mb-4">
          <FileText class="w-5 h-5 text-gray-600" />
          <h2 class="card-title">Description</h2>
        </div>
        <p class="text-gray-700 leading-relaxed">{{ article.description }}</p>
      </div>

      <!-- Tabs -->
      <div class="card fade-in" style="animation-delay: 0.25s">
        <!-- Tab headers -->
        <div class="border-b border-gray-200 px-6">
          <div class="flex gap-8">
            <button
              v-for="tab in tabs"
              :key="tab.id"
              @click="activeTab = tab.id"
              :class="[
                'py-4 font-medium transition-all duration-200 border-b-2',
                activeTab === tab.id
                  ? 'border-black text-black'
                  : 'border-transparent text-gray-500 hover:text-gray-700'
              ]"
            >
              {{ tab.label }}
            </button>
          </div>
        </div>

        <!-- Tab content -->
        <div class="p-6">
          <!-- Stock par filiale -->
          <div v-if="activeTab === 'stock'" class="space-y-4">
            <div class="flex justify-between items-center mb-6">
              <h3 class="text-lg font-bold">Stock par filiale</h3>
              <button @click="openMouvementModal" class="btn-primary flex items-center gap-2">
                <Plus class="w-4 h-4" />
                Mouvement de stock
              </button>
            </div>

            <div v-if="stocks.length === 0" class="empty-state">
              <Package class="empty-state-icon w-16 h-16" />
              <p class="empty-state-text">Aucun stock pour cet article</p>
            </div>

            <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
              <div
                v-for="stock in stocks"
                :key="stock.id"
                class="bg-white border border-gray-200 rounded-lg p-6 hover:shadow-md transition-all duration-200"
              >
                <div class="flex items-start justify-between mb-4">
                  <div>
                    <h4 class="font-bold text-gray-900">{{ stock.filiale }}</h4>
                    <p class="text-sm text-gray-500">{{ formatDate(stock.date_maj) }}</p>
                  </div>
                  <span
                    :class="[
                      'badge',
                      stock.quantite_actuelle === 0
                        ? 'badge-danger'
                        : stock.quantite_actuelle < 10
                        ? 'badge-warning'
                        : 'badge-success'
                    ]"
                  >
                    {{ stock.quantite_actuelle > 0 ? 'En stock' : 'Épuisé' }}
                  </span>
                </div>
                <div class="text-3xl font-bold text-gray-900">
                  {{ stock.quantite_actuelle }} <span class="text-lg text-gray-500">{{ article.unite }}</span>
                </div>
              </div>
            </div>
          </div>

          <!-- Historique des mouvements -->
          <div v-if="activeTab === 'mouvements'">
            <div class="flex justify-between items-center mb-6">
              <h3 class="text-lg font-bold">Historique des mouvements</h3>
            </div>

            <div v-if="mouvements.length === 0" class="empty-state">
              <TrendingUp class="empty-state-icon w-16 h-16" />
              <p class="empty-state-text">Aucun mouvement enregistré</p>
            </div>

            <div v-else class="table-wrapper">
              <table class="table">
                <thead>
                  <tr>
                    <th>Date</th>
                    <th>Type</th>
                    <th>Filiale</th>
                    <th>Stock avant</th>
                    <th>Entrée</th>
                    <th>Sortie</th>
                    <th>Stock après</th>
                    <th>Personnel</th>
                    <th>Référence</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="mvt in mouvements" :key="mvt.id" class="table-row">
                    <td>{{ formatDateTime(mvt.date_mouvement) }}</td>
                    <td>
                      <span
                        :class="[
                          'badge',
                          mvt.type_mouvement === 'ENTREE' ? 'badge-success' : 'badge-danger'
                        ]"
                      >
                        {{ mvt.type_mouvement }}
                      </span>
                    </td>
                    <td>{{ mvt.filiale || '-' }}</td>
                    <td class="font-medium">{{ mvt.quantite_stock_avant }}</td>
                    <td class="text-green-600 font-bold">
                      {{ mvt.quantite_entree > 0 ? '+' + mvt.quantite_entree : '-' }}
                    </td>
                    <td class="text-red-600 font-bold">
                      {{ mvt.quantite_sortie > 0 ? '-' + mvt.quantite_sortie : '-' }}
                    </td>
                    <td class="font-bold">{{ mvt.quantite_stock_apres }}</td>
                    <td>{{ (mvt.personnel_nom || '') }} {{ (mvt.personnel_prenom || '') }}</td>
                    <td class="text-sm text-gray-500">{{ mvt.reference_document || '-' }}</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import {
  ArrowLeft,
  Edit,
  Trash2,
  Info,
  DollarSign,
  Package,
  FileText,
  Plus,
  TrendingUp
} from 'lucide-vue-next'
import articleService from '@/services/articleService'
import stockService from '@/services/stockService'

const route = useRoute()
const router = useRouter()

const article = ref({})
const stocks = ref([])
const mouvements = ref([])
const activeTab = ref('stock')

const tabs = [
  { id: 'stock', label: 'Stock par filiale' },
  { id: 'mouvements', label: 'Historique des mouvements' }
]

const stockTotal = computed(() => {
  return stocks.value.reduce((total, stock) => total + parseFloat(stock.quantite_actuelle || 0), 0)
})

onMounted(async () => {
  await loadArticle()
  await loadStocks()
  await loadMouvements()
})

async function loadArticle() {
  const id = route.params.id
  article.value = await articleService.getById(id)
}

async function loadStocks() {
  const id = route.params.id
  stocks.value = await articleService.getStockByArticle(id)
}

async function loadMouvements() {
  const id = route.params.id
  mouvements.value = await articleService.getMouvementsByArticle(id)
}

function calculerMarge() {
  if (!article.value.prix_achat_ref || article.value.prix_achat_ref === 0) return 0
  const marge =
    (((Number(article.value.prix_vente_ref) || 0) - (Number(article.value.prix_achat_ref) || 0)) /
      (Number(article.value.prix_achat_ref) || 1)) *
    100
  return (Number(marge) || 0).toFixed(2)
}

function formatPrice(price) {
  return new Intl.NumberFormat('fr-MG', {
    style: 'currency',
    currency: 'MGA',
    minimumFractionDigits: 0
  }).format(price || 0)
}

function formatDate(date) {
  if (!date) return '-'
  return new Date(date).toLocaleDateString('fr-FR')
}

function formatDateTime(date) {
  if (!date) return '-'
  return new Date(date).toLocaleString('fr-FR')
}

function openEditModal() {
  // TODO: Implémenter modal d'édition
  console.log('Edit article', article.value.id)
}

function confirmDelete() {
  if (confirm('Êtes-vous sûr de vouloir supprimer cet article ?')) {
    deleteArticle()
  }
}

async function deleteArticle() {
  await articleService.delete(article.value.id)
  router.push('/articles')
}

function openMouvementModal() {
  // TODO: Implémenter modal de mouvement
  console.log('Add mouvement')
}
</script>

<style scoped>
/* Page container */
.page-container {
  min-height: 100vh;
  background: linear-gradient(to bottom right, #f8fafc, #f1f5f9);
  padding: 2rem;
}

/* Page header */
.page-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 2rem;
  padding: 1.5rem;
  background: white;
  border-radius: 0.75rem;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.page-title {
  font-size: 2.25rem;
  font-weight: 800;
  color: #111827;
  margin: 0;
}

.page-subtitle {
  color: #6b7280;
  font-size: 0.875rem;
  margin: 0;
}

/* Content wrapper */
.content-wrapper {
  max-width: 72rem;
  margin: 0 auto;
}

/* Stats grid */
.stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(18rem, 1fr));
  gap: 1.5rem;
  margin-bottom: 2rem;
}

/* Cards */
.card {
  background: white;
  border-radius: 0.75rem;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
  padding: 1.5rem;
  margin-bottom: 1.5rem;
}

.stat-card {
  background: white;
  border-radius: 0.75rem;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
  overflow: hidden;
}

.stat-content {
  padding: 1.5rem;
}

.card-title {
  font-size: 1.25rem;
  font-weight: 600;
  color: #111827;
  margin: 0;
}

/* Buttons */
.btn-primary {
  background: #000000;
  color: white;
  padding: 0.75rem 1.5rem;
  border-radius: 0.5rem;
  font-weight: 500;
  transition: all 0.2s;
  border: none;
  cursor: pointer;
}

.btn-primary:hover {
  background: #1f2937;
  transform: translateY(-1px);
  box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
}

.btn-secondary {
  background: white;
  color: #374151;
  padding: 0.75rem 1.5rem;
  border-radius: 0.5rem;
  font-weight: 500;
  transition: all 0.2s;
  border: 1px solid #d1d5db;
  cursor: pointer;
}

.btn-secondary:hover {
  background: #f9fafb;
  border-color: #9ca3af;
  transform: translateY(-1px);
  box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.05);
}

.btn-danger {
  background: #dc2626;
  color: white;
  padding: 0.75rem 1.5rem;
  border-radius: 0.5rem;
  font-weight: 500;
  transition: all 0.2s;
  border: none;
  cursor: pointer;
}

.btn-danger:hover {
  background: #b91c1c;
  transform: translateY(-1px);
  box-shadow: 0 10px 15px -3px rgba(220, 38, 38, 0.1), 0 4px 6px -2px rgba(220, 38, 38, 0.05);
}

.action-btn {
  background: white;
  border: 1px solid #d1d5db;
  border-radius: 0.5rem;
  padding: 0.75rem;
  cursor: pointer;
  transition: all 0.2s;
  color: #374151;
}

.action-btn:hover {
  background: #f3f4f6;
  border-color: #9ca3af;
  color: #111827;
  transform: translateY(-1px);
  box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.05);
}

/* Stats */
.stat-label {
  font-size: 0.875rem;
  font-weight: 500;
  color: #6b7280;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  margin-bottom: 0.25rem;
}

.stat-value {
  font-size: 1rem;
  font-weight: 600;
  color: #111827;
}

/* Badges */
.badge {
  display: inline-flex;
  align-items: center;
  padding: 0.25rem 0.75rem;
  border-radius: 9999px;
  font-size: 0.75rem;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.05em;
}

.badge-success {
  background: #dcfce7;
  color: #166534;
}

.badge-danger {
  background: #fef2f2;
  color: #991b1b;
}

.badge-warning {
  background: #fef3c7;
  color: #92400e;
}

/* Divider */
.divider {
  height: 1px;
  background: #e5e7eb;
  margin: 1rem 0;
}

/* Table */
.table-wrapper {
  background: white;
  border-radius: 0.5rem;
  overflow: hidden;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.table {
  width: 100%;
  border-collapse: collapse;
}

.table th {
  background: #f9fafb;
  padding: 1rem;
  text-align: left;
  font-weight: 600;
  color: #374151;
  border-bottom: 1px solid #e5e7eb;
  font-size: 0.875rem;
  text-transform: uppercase;
  letter-spacing: 0.05em;
}

.table td {
  padding: 1rem;
  border-bottom: 1px solid #f3f4f6;
  color: #374151;
}

.table-row:hover {
  background: #f9fafb;
}

/* Empty state */
.empty-state {
  text-align: center;
  padding: 3rem 1rem;
}

.empty-state-icon {
  color: #d1d5db;
  margin: 0 auto 1rem;
}

.empty-state-text {
  color: #6b7280;
  font-size: 1.125rem;
  font-weight: 500;
}

/* Loading state */
.loading-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  min-height: 24rem;
  text-align: center;
}

.spinner {
  width: 3rem;
  height: 3rem;
  border: 4px solid #e5e7eb;
  border-top: 4px solid #000000;
  border-radius: 50%;
  animation: spin 1s linear infinite;
  margin-bottom: 1rem;
}

@keyframes spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}

.loading-text {
  color: #6b7280;
  font-size: 1.125rem;
  font-weight: 500;
}

/* Error state */
.error-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  min-height: 24rem;
  text-align: center;
  padding: 2rem;
}

.error-icon {
  color: #dc2626;
  margin-bottom: 1rem;
}

.error-message {
  color: #dc2626;
  font-size: 1.125rem;
  font-weight: 500;
  margin-bottom: 1.5rem;
}

/* Animations */
.fade-in {
  animation: fadeIn 0.6s ease-out forwards;
  opacity: 0;
  transform: translateY(20px);
}

@keyframes fadeIn {
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

/* Responsive */
@media (max-width: 768px) {
  .page-container {
    padding: 1rem;
  }

  .page-header {
    flex-direction: column;
    gap: 1rem;
    text-align: center;
  }

  .page-title {
    font-size: 1.875rem;
  }

  .stats-grid {
    grid-template-columns: 1fr;
  }

  .table-wrapper {
    overflow-x: auto;
  }
}
</style>
