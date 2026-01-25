<template>
  <div class="page-container">
    <!-- Loading state -->
    <div v-if="loading" class="loading-state">
      <div class="spinner"></div>
      <p class="loading-text">Chargement...</p>
    </div>

    <!-- Content -->
    <div v-else class="content-wrapper">
      <!-- Header -->
      <div class="page-header fade-in" style="animation-delay: 0.1s">
        <div>
          <h1 class="page-title">Nouveau Devis</h1>
          <p class="page-subtitle">Créer un devis de vente</p>
        </div>
        <div class="header-actions">
          <router-link to="/ventes/devis" class="btn-secondary">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
            </svg>
            <span>Retour</span>
          </router-link>
          <button class="btn-primary" @click="save" :disabled="saving">
            <svg v-if="!saving" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
            </svg>
            <span>{{ saving ? 'Enregistrement...' : 'Enregistrer' }}</span>
          </button>
        </div>
      </div>

      <!-- Form -->
      <div class="form-container">
        <!-- Informations générales -->
        <div class="card fade-in" style="animation-delay: 0.15s">
          <div class="card-section">
            <h3 class="section-title">Informations générales</h3>
            <div class="form-grid">
              <div class="form-group">
                <label class="label">Client *</label>
                <select v-model="form.entreprise_client_id" class="select">
                  <option value="">Sélectionner un client</option>
                  <option v-for="client in clients" :key="client.id" :value="client.id">
                    {{ client.nom }}
                  </option>
                </select>
              </div>

              <div class="form-group">
                <label class="label">Filiale *</label>
                <select v-model="form.entreprise_filiale_id" class="select">
                  <option value="">Sélectionner une filiale</option>
                  <option v-for="filiale in filiales" :key="filiale.id" :value="filiale.id">
                    {{ filiale.nom }}
                  </option>
                </select>
              </div>

              <div class="form-group">
                <label class="label">Date du devis *</label>
                <input v-model="form.date_devis" type="date" class="input" />
              </div>
            </div>
          </div>
        </div>

        <!-- Articles -->
        <div class="card fade-in" style="animation-delay: 0.2s">
          <div class="card-section">
            <div class="section-header">
              <h3 class="section-title">Articles</h3>
              <button @click="addArticle" class="btn-secondary">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                </svg>
                <span>Ajouter un article</span>
              </button>
            </div>

            <div v-if="form.details.length === 0" class="empty-state">
              <svg class="w-12 h-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2M4 13h2m8-5v2m0 0v2m0-2h2m-2 0H8"></path>
              </svg>
              <p class="empty-text">Aucun article ajouté</p>
              <p class="empty-subtext">Cliquez sur "Ajouter un article" pour commencer</p>
            </div>

            <div v-else class="articles-table-wrapper">
              <table class="articles-table">
                <thead>
                  <tr>
                    <th>Article</th>
                    <th>Quantité</th>
                    <th>Prix unitaire</th>
                    <th>Total</th>
                    <th>Actions</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="(detail, index) in form.details" :key="index" class="article-row">
                    <td>
                      <select v-model="detail.article_id" class="select" @change="onArticleChange(index)">
                        <option value="">Sélectionner un article</option>
                        <option v-for="article in articles" :key="article.id" :value="article.id">
                          {{ article.designation }}
                        </option>
                      </select>
                    </td>
                    <td>
                      <input v-model.number="detail.quantite" type="number" min="1" step="0.01" class="input" @input="calculateLineTotal(index)" />
                    </td>
                    <td>
                      <input v-model.number="detail.prix_unitaire" type="number" min="0" step="0.01" class="input" @input="calculateLineTotal(index)" />
                    </td>
                    <td class="total-cell">
                      {{ formatCurrency(detail.quantite * detail.prix_unitaire) }}
                    </td>
                    <td class="actions-cell">
                      <button @click="removeArticle(index)" class="btn-danger">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                        </svg>
                      </button>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>

            <!-- Total -->
            <div v-if="form.details.length > 0" class="total-section">
              <div class="total-line">
                <span class="total-label">Total HT:</span>
                <span class="total-value">{{ formatCurrency(totalHT) }}</span>
              </div>
              <div class="total-line">
                <span class="total-label">TVA (20%):</span>
                <span class="total-value">{{ formatCurrency(totalTVA) }}</span>
              </div>
              <div class="total-line total-final">
                <span class="total-label">Total TTC:</span>
                <span class="total-value text-blue-600">{{ formatCurrency(totalTTC) }}</span>
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
import { useRouter } from 'vue-router'
import venteService from '../../services/venteService'
import articleService from '../../services/articleService'
import entrepriseService from '../../services/entrepriseService'

const router = useRouter()

// State
const loading = ref(false)
const saving = ref(false)
const clients = ref([])
const filiales = ref([])
const articles = ref([])

// Form data
const form = ref({
  entreprise_client_id: '',
  entreprise_filiale_id: '',
  personnel_id: '',
  date_devis: new Date().toISOString().split('T')[0],
  details: []
})

// Set personnel_id from current user
const user = JSON.parse(localStorage.getItem('user') || '{}')
form.value.personnel_id = user.id || ''

// Computed
const totalHT = computed(() => {
  return form.value.details.reduce((total, detail) => {
    return total + (detail.quantite * detail.prix_unitaire)
  }, 0)
})

const totalTVA = computed(() => {
  return totalHT.value * 0.2
})

const totalTTC = computed(() => {
  return totalHT.value + totalTVA.value
})

// Methods
const loadData = async () => {
  loading.value = true
  try {
    const [clientsRes, filialesRes, articlesRes] = await Promise.all([
      entrepriseService.getAll({ type_entreprise: 'CLIENT' }),
      entrepriseService.getAll({ type_entreprise: 'INTERNE' }),
      articleService.getAll()
    ])

    clients.value = clientsRes.data || []
    filiales.value = filialesRes.data || []
    articles.value = articlesRes.data || []
  } catch (error) {
    console.error('Erreur lors du chargement des données:', error)
  } finally {
    loading.value = false
  }
}

const addArticle = () => {
  form.value.details.push({
    article_id: '',
    quantite: 1,
    prix_unitaire: 0
  })
}

const removeArticle = (index) => {
  form.value.details.splice(index, 1)
}

const onArticleChange = (index) => {
  const detail = form.value.details[index]
  const article = articles.value.find(a => a.id === detail.article_id)
  if (article) {
    detail.prix_unitaire = article.prix_vente_ref || 0
    calculateLineTotal(index)
  }
}

const calculateLineTotal = (index) => {
  // Already calculated in computed properties
}

const formatCurrency = (amount) => {
  return new Intl.NumberFormat('fr-MG', {
    style: 'currency',
    currency: 'MGA',
    minimumFractionDigits: 0
  }).format(amount)
}

const save = async () => {
  // Validation
  if (!form.value.entreprise_client_id || !form.value.entreprise_filiale_id) {
    alert('Veuillez remplir tous les champs obligatoires')
    return
  }

  if (form.value.details.length === 0) {
    alert('Veuillez ajouter au moins un article')
    return
  }

  // Validate details
  for (const detail of form.value.details) {
    if (!detail.article_id || !detail.quantite || detail.prix_unitaire < 0) {
      alert('Veuillez vérifier tous les articles (article, quantité et prix requis)')
      return
    }
  }

  saving.value = true
  try {
    const dataToSend = {
      ...form.value,
      montant_ttc: totalTTC.value
    }

    await venteService.devis.create(dataToSend)
    router.push('/ventes/devis')
  } catch (error) {
    console.error('Erreur lors de la création du devis:', error)
    alert('Erreur lors de la création du devis')
  } finally {
    saving.value = false
  }
}

// Lifecycle
onMounted(() => {
  loadData()
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

.header-actions {
  @apply flex items-center gap-3;
}

.btn-primary {
  @apply inline-flex items-center gap-2 px-4 py-2 bg-gray-900 text-white text-sm font-medium rounded-lg hover:bg-gray-800 transition-colors;
}

.btn-secondary {
  @apply inline-flex items-center gap-2 px-4 py-2 bg-white text-gray-700 text-sm font-medium rounded-lg border border-gray-300 hover:bg-gray-50 transition-colors;
}

.btn-danger {
  @apply p-2 text-red-600 hover:bg-red-50 rounded-lg transition-colors;
}

.form-container {
  @apply space-y-6;
}

.card {
  @apply bg-white rounded-lg border border-gray-200 p-6;
}

.card-section {
  @apply space-y-4;
}

.section-title {
  @apply text-lg font-semibold text-gray-900;
}

.section-header {
  @apply flex items-center justify-between mb-4;
}

.form-grid {
  @apply grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4;
}

.form-group {
  @apply space-y-2;
}

.label {
  @apply block text-xs font-medium text-gray-700;
}

.input {
  @apply w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-gray-900 focus:border-gray-900 outline-none text-sm;
}

.select {
  @apply w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-gray-900 focus:border-gray-900 outline-none text-sm;
}

.empty-state {
  @apply flex flex-col items-center justify-center py-12 text-center;
}

.empty-text {
  @apply text-sm font-medium text-gray-900 mt-4;
}

.empty-subtext {
  @apply text-xs text-gray-500 mt-1;
}

.articles-table-wrapper {
  @apply overflow-x-auto border border-gray-200 rounded-lg;
}

.articles-table {
  @apply w-full;
}

.articles-table thead {
  @apply bg-gray-50 border-b border-gray-200;
}

.articles-table th {
  @apply px-4 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider;
}

.articles-table td {
  @apply px-4 py-3 text-sm text-gray-900 border-b border-gray-100;
}

.article-row {
  @apply hover:bg-gray-50 transition-colors;
}

.total-cell {
  @apply font-medium text-gray-900;
}

.actions-cell {
  @apply text-center;
}

.total-section {
  @apply mt-6 pt-4 border-t border-gray-200;
}

.total-line {
  @apply flex justify-between items-center py-2;
}

.total-label {
  @apply text-sm font-medium text-gray-700;
}

.total-value {
  @apply text-sm font-semibold text-gray-900;
}

.total-final {
  @apply border-t border-gray-200 pt-2 mt-2;
}

.total-final .total-label {
  @apply text-base font-bold text-gray-900;
}

.total-final .total-value {
  @apply text-base font-bold text-gray-900;
}
</style>