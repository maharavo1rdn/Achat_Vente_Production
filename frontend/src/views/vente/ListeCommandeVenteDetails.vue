<template>
  <div class="page-container">
    <!-- Loading -->
    <div v-if="loading" class="loading-state">
      <div class="spinner"></div>
      <p class="loading-text">Chargement du bon de commande...</p>
    </div>

    <!-- Error -->
    <div v-else-if="error" class="error-state">
      <div class="error-icon">
        <svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
        </svg>
      </div>
      <p class="error-message">{{ error }}</p>
      <button @click="loadBC" class="btn-primary">Réessayer</button>
    </div>

    <!-- Content -->
    <div v-else class="content-wrapper">
      <div class="page-header fade-in" style="animation-delay: 0.1s">
        <div>
          <h1 class="page-title">Bon de Commande</h1>
          <p class="page-subtitle">Détails du bon de commande</p>
        </div>
        <div class="header-actions">
          <button @click="goBack" class="btn-secondary">
            <ArrowLeft class="w-4 h-4" />
            <span>Retour</span>
          </button>
          <button v-if="bc && bc.statut === 'LIVRE'" @click="convertToFacture(bc)" class="btn-primary">
            <Check class="w-4 h-4" />
            <span>Convertir</span>
          </button>
          <button @click="printBC(bc)" class="btn-secondary">
            <Printer class="w-4 h-4" />
            <span>Imprimer</span>
          </button>
        </div>
      </div>

      <div class="card fade-in" style="animation-delay: 0.15s">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
          <div>
            <p class="label">Numéro</p>
            <p class="font-medium">{{ bc.numero_bc || '-' }}</p>
          </div>
          <div>
            <p class="label">Client</p>
            <p class="font-medium">{{ bc.client_nom || '-' }}</p>
          </div>
          <div>
            <p class="label">Date</p>
            <p class="font-medium">{{ formatDate(bc.date_commande) }}</p>
          </div>
        </div>

        <div class="mt-4">
          <p class="label">Montant TTC</p>
          <p class="text-xl font-bold">{{ formatCurrency(bc.montant_ttc || 0) }}</p>
        </div>
      </div>

      <div class="card fade-in" style="animation-delay: 0.2s">
        <h3 class="section-title">Articles</h3>
        <div v-if="!bc.details || bc.details.length === 0" class="empty-state">
          <p class="empty-text">Aucun article.</p>
        </div>
        <div v-else class="articles-table-wrapper">
          <table class="articles-table">
            <thead>
              <tr>
                <th>Article</th>
                <th>Quantité</th>
                <th>Prix unitaire</th>
                <th>Total</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="(d, i) in bc.details" :key="i" class="article-row">
                <td>{{ d.designation || d.article_designation || d.article || '-' }}</td>
                <td>{{ d.quantite || d.qty || 0 }}</td>
                <td>{{ formatCurrency(d.prix_unitaire || d.prix || 0) }}</td>
                <td>{{ formatCurrency((d.quantite || d.qty || 0) * (d.prix_unitaire || d.prix || 0)) }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { ArrowLeft, Printer, Check } from 'lucide-vue-next'
import venteService from '@/services/venteService'

const route = useRoute()
const router = useRouter()
const id = route.params.id

const bc = ref({})
const loading = ref(false)
const error = ref(null)

const loadBC = async () => {
  loading.value = true
  error.value = null
  try {
    const res = await venteService.bonCommande.getById(id)
    bc.value = res.data || {}
  } catch (err) {
    error.value = err.response?.data?.message || err.message || 'Erreur lors du chargement'
    console.error('Erreur chargement BC detail:', err)
  } finally {
    loading.value = false
  }
}

const goBack = () => router.back()

const convertToFacture = async (bcItem) => {
  if (!confirm('Convertir ce bon de commande en facture ?')) return
  try {
    await venteService.bonCommande.convertToFacture(bcItem.id)
    alert('Bon de commande converti en facture')
    goBack()
  } catch (err) {
    error.value = err.response?.data?.message || err.message || 'Erreur lors de la conversion'
    console.error('Erreur conversion:', err)
  }
}

const printBC = (bcItem) => {
  console.log('Imprimer BC', bcItem)
}

const formatCurrency = (amount) => {
  return new Intl.NumberFormat('fr-MG', { style: 'currency', currency: 'MGA', minimumFractionDigits: 0 }).format(amount)
}

const formatDate = (date) => date ? new Date(date).toLocaleDateString('fr-FR') : '-'

onMounted(() => loadBC())
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

.header-actions {
  @apply flex items-center gap-3;
}

.btn-primary {
  @apply inline-flex items-center gap-2 px-4 py-2 bg-gray-900 text-white text-sm font-medium rounded-lg hover:bg-gray-800 transition-colors;
}

.btn-secondary {
  @apply inline-flex items-center gap-2 px-4 py-2 bg-white text-gray-700 text-sm font-medium rounded-lg border border-gray-300 hover:bg-gray-50 transition-colors;
}

.card {
  @apply bg-white rounded-lg border border-gray-200 p-6;
}

.section-title {
  @apply text-lg font-semibold text-gray-900;
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
</style>