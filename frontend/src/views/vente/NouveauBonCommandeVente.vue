<template>
  <div class="page-container">
    <!-- Simple toast -->
    <div v-if="toast.show" :class="['fixed top-6 right-6 z-50 px-4 py-3 rounded-md shadow-md text-white', toast.type === 'success' ? 'bg-green-600' : 'bg-red-600']">
      {{ toast.message }}
      <button class="ml-3 font-bold" @click="toast.show = false">✕</button>
    </div>
    
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
          <h1 class="page-title">Nouveau Bon de Commande</h1>
          <p class="page-subtitle">Créer un bon de commande à partir d'un devis (modifiable)</p>
        </div>
        <div class="header-actions">
          <router-link to="/ventes/bon-commande" class="btn-secondary">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
            </svg>
            <span>Retour</span>
          </router-link>
        </div>
      </div>

      <!-- Form -->
      <div class="form-container">
        <!-- Sélection du devis -->
        <div class="card fade-in" style="animation-delay: 0.15s">
          <div class="card-section">
            <h3 class="section-title">Sélectionner un devis</h3>
            <div class="form-group">
              <label class="label">Devis *</label>
              <select v-model="selectedDevisId" @change="onDevisChange" class="select" :disabled="devisLoaded">
                <option value="">Sélectionner un devis validé</option>
                <option v-for="devis in devisList" :key="devis.id" :value="devis.id">
                  {{ devis.numero_devis }} - {{ devis.client_nom }} - {{ formatCurrency(devis.montant_ttc) }}
                </option>
              </select>
            </div>
            <p v-if="devisList.length === 0" class="text-sm text-yellow-600 mt-2">
              Aucun devis validé disponible. Veuillez d'abord créer et valider un devis.
            </p>

            <!-- Info devis sélectionné -->
            <div v-if="selectedDevisId && selectedDevis" class="mt-4 p-4 bg-blue-50 rounded-lg">
              <h4 class="font-semibold text-blue-900 mb-2">Détails du devis sélectionné</h4>
              <div class="grid grid-cols-2 gap-3 text-sm">
                <div>
                  <span class="text-gray-600">Client:</span>
                  <span class="ml-2 font-medium">{{ selectedDevis.client_nom }}</span>
                </div>
                <div>
                  <span class="text-gray-600">Date:</span>
                  <span class="ml-2 font-medium">{{ formatDate(selectedDevis.date_devis) }}</span>
                </div>
                <div>
                  <span class="text-gray-600">Montant TTC:</span>
                  <span class="ml-2 font-medium">{{ formatCurrency(selectedDevis.montant_ttc) }}</span>
                </div>
                <div>
                  <span class="text-gray-600">Articles:</span>
                  <span class="ml-2 font-medium">{{ selectedDevis.details?.length || 0 }}</span>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Formulaire éditable si un devis est chargé -->
        <div v-if="devisLoaded" class="space-y-6">
          <!-- Informations générales -->
          <div class="card fade-in" style="animation-delay: 0.2s">
            <div class="card-section">
              <h3 class="section-title">Informations du Bon de Commande</h3>
              <div class="form-grid">
                <div class="form-group">
                  <label class="label">Date du BC *</label>
                  <input v-model="form.date_bc" type="date" class="input" />
                </div>

                <div class="form-group">
                  <label class="label">Statut *</label>
                  <select v-model="form.statut_id" class="select">
                    <option value="">Sélectionner un statut</option>
                    <option v-for="s in statuts" :key="s.id" :value="s.id">{{ s.libelle }}</option>
                  </select>
                </div>
              </div>
            </div>
          </div>

          <!-- Articles -->
          <div class="card fade-in" style="animation-delay: 0.25s">
            <div class="card-section">
              <div class="section-header">
                <h3 class="section-title">Articles (modifiable)</h3>
                <button @click="addArticle" class="btn-secondary">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                  </svg>
                  <span>Ajouter un article</span>
                </button>
              </div>

              <div class="articles-list">
                <div v-for="(detail, index) in form.details" :key="index" class="article-item">
                  <div class="article-grid">
                    <div class="form-group">
                      <label class="label">Article *</label>
                      <select v-model="detail.article_id" @change="onArticleChange(index)" class="select">
                        <option value="">Sélectionner un article</option>
                        <option v-for="article in articles" :key="article.id" :value="article.id">
                          {{ article.designation }}
                        </option>
                      </select>
                    </div>

                    <div class="form-group">
                      <label class="label">Quantité *</label>
                      <input v-model.number="detail.quantite" type="number" min="1" class="input" />
                    </div>

                    <div class="form-group">
                      <label class="label">Prix unitaire (MGA) *</label>
                      <input v-model.number="detail.prix_unitaire" type="number" min="0" step="0.01" class="input" />
                    </div>

                    <div class="form-group">
                      <label class="label">Total HT (MGA)</label>
                      <div class="total-display">
                        {{ formatCurrency((Number(detail.quantite) || 0) * (Number(detail.prix_unitaire) || 0)) }}
                      </div>
                    </div>

                    <div class="form-group flex items-end">
                      <button @click="removeArticle(index)" class="btn-danger">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                        </svg>
                        <span>Supprimer</span>
                      </button>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Totaux -->
          <div class="card fade-in" style="animation-delay: 0.3s">
            <div class="card-section">
              <h3 class="section-title">Totaux</h3>
              <div class="totals-grid">
                <div class="total-row">
                  <span class="total-label">Total HT:</span>
                  <span class="total-value">{{ formatCurrency(totalHT) }}</span>
                </div>
                <div class="total-row">
                  <span class="total-label">TVA (20%):</span>
                  <span class="total-value">{{ formatCurrency(totalTVA) }}</span>
                </div>
                <div class="total-row-final">
                  <span class="total-label-final">Total TTC:</span>
                  <span class="total-value-final">{{ formatCurrency(totalTTC) }}</span>
                </div>
              </div>
            </div>
          </div>

          <!-- Actions -->
          <div class="flex justify-end gap-3">
            <button @click="resetForm" class="btn-secondary">
              <span>Annuler</span>
            </button>
            <button @click="createBC" class="btn-primary" :disabled="saving">
              <svg v-if="!saving" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
              </svg>
              <span>{{ saving ? 'Création...' : 'Créer le Bon de Commande' }}</span>
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import venteService from '@/services/venteService'
import articleService from '@/services/articleService'
import statutService from '@/services/statutService'

const router = useRouter()

// State
const loading = ref(true)
const saving = ref(false)
const devisList = ref([])
const selectedDevisId = ref('')
const selectedDevis = ref(null)
const devisLoaded = ref(false)
const statuts = ref([])
const articles = ref([])

const form = ref({
  devis_id: '',
  entreprise_client_id: '',
  entreprise_filiale_id: '',
  date_bc: new Date().toISOString().split('T')[0],
  statut_id: '',
  details: []
})

// Computed
const totalHT = computed(() => {
  return form.value.details.reduce((total, detail) => {
    return total + ((Number(detail.quantite) || 0) * (Number(detail.prix_unitaire) || 0))
  }, 0)
})

const totalTVA = computed(() => {
  return totalHT.value * 0.2
})

const totalTTC = computed(() => {
  return totalHT.value + totalTVA.value
})

// Toast state & helper
const toast = ref({ show: false, message: '', type: 'success' })

const showToast = (message, type = 'success', duration = 4000) => {
  toast.value.message = message
  toast.value.type = type
  toast.value.show = true
  setTimeout(() => { toast.value.show = false }, duration)
}

// Methods
const loadData = async () => {
  loading.value = true
  try {
    const [devisRes, articlesRes, statutsRes] = await Promise.all([
      venteService.devis.getAll(),
      articleService.getAll(),
      statutService.getAll()
    ])
    
    const allDevis = devisRes.data || []
    // Filtrer uniquement les devis validés
    devisList.value = allDevis.filter(d => d.statut === 'VALIDE' || d.statut_id === 2)
    articles.value = articlesRes.data || []
    statuts.value = statutsRes.data || []
  } catch (error) {
    console.error('Erreur lors du chargement des données:', error)
    showToast('Erreur lors du chargement des données', 'error')
  } finally {
    loading.value = false
  }
}

const onDevisChange = async () => {
  if (selectedDevisId.value) {
    try {
      // Charger les détails complets du devis
      const res = await venteService.devis.getById(selectedDevisId.value)
      selectedDevis.value = res.data
      
      // Remplir le formulaire avec les données du devis
      form.value.devis_id = selectedDevis.value.id
      form.value.entreprise_client_id = selectedDevis.value.entreprise_client_id
      form.value.entreprise_filiale_id = selectedDevis.value.entreprise_filiale_id
      form.value.date_bc = new Date().toISOString().split('T')[0]
      form.value.statut_id = ''
      
      // Copier les articles du devis dans le formulaire
      form.value.details = (selectedDevis.value.details || []).map(d => ({
        article_id: d.article_id,
        quantite: d.quantite,
        prix_unitaire: d.prix_unitaire
      }))
      
      devisLoaded.value = true
    } catch (error) {
      console.error('Erreur lors du chargement du devis:', error)
      showToast('Erreur lors du chargement des détails du devis', 'error')
    }
  } else {
    selectedDevis.value = null
    devisLoaded.value = false
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
  }
}

const resetForm = () => {
  selectedDevisId.value = ''
  selectedDevis.value = null
  devisLoaded.value = false
  form.value = {
    devis_id: '',
    entreprise_client_id: '',
    entreprise_filiale_id: '',
    date_bc: new Date().toISOString().split('T')[0],
    statut_id: '',
    details: []
  }
}

const formatCurrency = (amount) => {
  const num = Number(amount)
  return new Intl.NumberFormat('fr-MG', {
    style: 'currency',
    currency: 'MGA',
    minimumFractionDigits: 0
  }).format(isNaN(num) ? 0 : num)
}

const formatDate = (date) => {
  if (!date) return '-'
  return new Date(date).toLocaleDateString('fr-FR')
}

const createBC = async () => {
  // Validation
  if (!form.value.devis_id) {
    showToast('Veuillez sélectionner un devis', 'error')
    return
  }

  if (!form.value.statut_id) {
    showToast('Veuillez sélectionner un statut', 'error')
    return
  }

  if (form.value.details.length === 0) {
    showToast('Veuillez ajouter au moins un article', 'error')
    return
  }

  // Validate details
  for (const detail of form.value.details) {
    if (!detail.article_id || !detail.quantite || detail.prix_unitaire < 0) {
      showToast('Veuillez vérifier tous les articles (article, quantité et prix requis)', 'error')
      return
    }
  }

  saving.value = true
  try {
    const dataToSend = {
      devis_id: form.value.devis_id,
      entreprise_client_id: form.value.entreprise_client_id,
      entreprise_filiale_id: form.value.entreprise_filiale_id,
      date_bc: form.value.date_bc,
      statut_id: form.value.statut_id,
      montant_ttc: totalTTC.value,
      details: form.value.details
    }

    const res = await venteService.bonCommande.create(dataToSend)
    showToast(res && res.data ? `Bon de commande créé (ID: ${res.data})` : 'Bon de commande créé avec succès')
    
    setTimeout(() => {
      router.push('/ventes/bon-commande')
    }, 1500)
  } catch (error) {
    console.error('Erreur lors de la création du BC:', error)
    showToast('Erreur lors de la création du bon de commande', 'error')
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
  @apply flex flex-col items-center justify-center min-h-[400px];
}

.spinner {
  @apply w-12 h-12 border-4 border-blue-200 border-t-blue-600 rounded-full animate-spin;
}

.loading-text {
  @apply mt-4 text-gray-600 font-medium;
}

.content-wrapper {
  @apply space-y-6;
}

.page-header {
  @apply flex items-center justify-between mb-6;
}

.page-title {
  @apply text-2xl font-bold text-gray-900;
}

.page-subtitle {
  @apply text-sm text-gray-600 mt-1;
}

.header-actions {
  @apply flex gap-3;
}

.btn-secondary {
  @apply flex items-center gap-2 px-4 py-2 bg-white border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors duration-200;
}

.btn-primary {
  @apply flex items-center gap-2 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors duration-200 disabled:opacity-50 disabled:cursor-not-allowed;
}

.btn-danger {
  @apply flex items-center gap-2 px-3 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors duration-200 text-sm;
}

.form-container {
  @apply space-y-6;
}

.card {
  @apply bg-white rounded-lg shadow-sm border border-gray-200;
}

.card-section {
  @apply p-6;
}

.section-title {
  @apply text-lg font-semibold text-gray-900 mb-4;
}

.section-header {
  @apply flex items-center justify-between mb-4;
}

.form-grid {
  @apply grid grid-cols-1 md:grid-cols-2 gap-4;
}

.form-group {
  @apply flex flex-col;
}

.label {
  @apply text-sm font-medium text-gray-700 mb-1;
}

.input {
  @apply px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors duration-200;
}

.select {
  @apply px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors duration-200 bg-white disabled:bg-gray-100 disabled:text-gray-400 disabled:cursor-not-allowed;
}

.articles-list {
  @apply space-y-4;
}

.article-item {
  @apply p-4 border border-gray-200 rounded-lg bg-gray-50;
}

.article-grid {
  @apply grid grid-cols-1 md:grid-cols-5 gap-4;
}

.total-display {
  @apply px-3 py-2 bg-gray-100 rounded-lg font-medium text-gray-900;
}

.totals-grid {
  @apply space-y-3;
}

.total-row {
  @apply flex justify-between items-center py-2 border-b border-gray-200;
}

.total-label {
  @apply text-gray-700 font-medium;
}

.total-value {
  @apply text-gray-900 font-semibold;
}

.total-row-final {
  @apply flex justify-between items-center py-3 bg-blue-50 rounded-lg px-4 mt-4;
}

.total-label-final {
  @apply text-lg font-bold text-blue-900;
}

.total-value-final {
  @apply text-xl font-bold text-blue-600;
}

.fade-in {
  animation: fadeIn 0.4s ease-in;
}

@keyframes fadeIn {
  from {
    opacity: 0;
    transform: translateY(10px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}
</style>
