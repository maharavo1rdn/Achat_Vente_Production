<template>
  <div class="page-container">
    <!-- Loading state -->
    <div v-if="loading" class="loading-state">
      <div class="spinner"></div>
      <p class="loading-text">Chargement des factures...</p>
    </div>

    <!-- Error state -->
    <div v-else-if="error" class="error-state">
      <div class="error-icon">
        <svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
        </svg>
      </div>
      <p class="error-message">{{ error }}</p>
      <button @click="loadFactures" class="btn-primary">
        Réessayer
      </button>
    </div>

    <!-- Content -->
    <div v-else class="content-wrapper">
      <!-- Header -->
      <div class="page-header fade-in" style="animation-delay: 0.1s">
        <div>
          <h1 class="page-title">Factures d'Achat</h1>
          <p class="page-subtitle">Suivi des factures fournisseurs</p>
        </div>
        <button @click="openCreateModal" class="btn-primary">
          <Plus class="w-4 h-4" />
          <span>Nouvelle Facture</span>
        </button>
      </div>

      <!-- Stats Cards -->
      <div class="stats-grid fade-in" style="animation-delay: 0.15s">
        <div class="stat-card">
          <div class="stat-content">
            <p class="stat-label">Total factures</p>
            <h3 class="stat-value">{{ factures.length }}</h3>
          </div>
        </div>
        <div class="stat-card">
          <div class="stat-content">
            <p class="stat-label">Payées</p>
            <h3 class="stat-value text-green-600">{{ facturesPayees }}</h3>
          </div>
        </div>
        <div class="stat-card">
          <div class="stat-content">
            <p class="stat-label">Impayées</p>
            <h3 class="stat-value text-red-600">{{ facturesImpayees }}</h3>
          </div>
        </div>
        <div class="stat-card">
          <div class="stat-content">
            <p class="stat-label">Reste à payer</p>
            <h3 class="stat-value text-orange-600">{{ formatCurrency(totalResteAPayer) }}</h3>
          </div>
        </div>
      </div>

      <!-- Filters -->
      <div class="card fade-in" style="animation-delay: 0.2s">
        <div class="filter-grid">
          <div class="filter-item">
            <label class="label">Numéro Facture</label>
            <div class="search-box">
              <Search class="w-4 h-4 text-gray-400" />
              <input
                v-model="searchQuery"
                type="text"
                class="search-input"
                placeholder="FA-..."
              />
            </div>
          </div>
          <div class="filter-item">
            <label class="label">Fournisseur</label>
            <select v-model="selectedFournisseur" class="select">
              <option value="">Tous les fournisseurs</option>
              <option value="1">Fournisseur X</option>
              <option value="2">Fournisseur Y</option>
            </select>
          </div>
          <div class="filter-item">
            <label class="label">Statut Paiement</label>
            <select v-model="selectedStatut" class="select">
              <option value="">Tous les statuts</option>
              <option value="PAYE">Payé</option>
              <option value="IMPAYE">Impayé</option>
              <option value="PARTIEL">Partiel</option>
            </select>
          </div>
          <div class="filter-item">
            <label class="label">Date</label>
            <input v-model="dateFilter" type="date" class="input" />
          </div>
        </div>
      </div>

      <!-- Table -->
      <div class="card fade-in" style="animation-delay: 0.25s">
        <div class="card-header-simple">
          <h2 class="card-title">Liste des Factures</h2>
          <span class="text-xs text-gray-500">{{ filteredFactures.length }} résultat(s)</span>
        </div>
        <div class="table-wrapper">
          <table class="table">
            <thead>
              <tr>
                <th>Numéro Facture</th>
                <th>Date Facture</th>
                <th>Fournisseur</th>
                <th class="text-right">Montant TTC</th>
                <th class="text-right">Reste à Payer</th>
                <th class="text-center">Statut</th>
                <th>BC Origine</th>
                <th class="text-center">Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr v-if="filteredFactures.length === 0">
                <td colspan="8" class="text-center py-8 text-gray-500">
                  Aucune facture trouvée
                </td>
              </tr>
              <tr v-else v-for="facture in filteredFactures" :key="facture.id" class="table-row">
                <td class="font-medium">{{ facture.numero_facture }}</td>
                <td class="text-gray-600">{{ formatDate(facture.date_facture) }}</td>
                <td class="font-medium">{{ facture.fournisseur }}</td>
                <td class="text-right font-semibold">{{ formatCurrency(facture.montant_ttc) }}</td>
                <td class="text-right font-semibold" :class="getResteClass(facture.reste_a_payer)">
                  {{ formatCurrency(facture.reste_a_payer) }}
                </td>
                <td class="text-center">
                  <span :class="getStatutBadgeClass(facture)">
                    {{ getStatutLabel(facture) }}
                  </span>
                </td>
                <td class="text-xs text-gray-500">{{ facture.bc_origine || '-' }}</td>
                <td>
                  <div class="table-actions">
                    <button @click="viewFacture(facture)" class="action-btn" title="Voir">
                      <Eye class="w-4 h-4" />
                    </button>
                    <button 
                      v-if="facture.reste_a_payer > 0" 
                      @click="payerFacture(facture)" 
                      class="action-btn text-green-600" 
                      title="Payer"
                    >
                      <CreditCard class="w-4 h-4" />
                    </button>
                    <button @click="printFacture(facture)" class="action-btn" title="Imprimer">
                      <Printer class="w-4 h-4" />
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
import { Plus, Eye, CreditCard, Printer, Search } from 'lucide-vue-next'
import achatService from '@/services/achatService'

const searchQuery = ref('')
const selectedFournisseur = ref('')
const selectedStatut = ref('')
const dateFilter = ref('')

const factures = ref([])
const loading = ref(false)
const error = ref(null)

const loadFactures = async () => {
  try {
    loading.value = true
    error.value = null
    const response = await achatService.facture.getAll()
    factures.value = response.data || []
  } catch (err) {
    error.value = err.response?.data?.message || 'Erreur lors du chargement des factures'
    console.error('Erreur chargement factures:', err)
  } finally {
    loading.value = false
  }
}

const filteredFactures = computed(() => {
  return factures.value.filter(facture => {
    const matchSearch = !searchQuery.value || 
      facture.numero_facture?.toLowerCase().includes(searchQuery.value.toLowerCase())
    
    const matchFournisseur = !selectedFournisseur.value || facture.fournisseur === selectedFournisseur.value
    const matchStatut = !selectedStatut.value || getStatutLabel(facture) === selectedStatut.value
    
    return matchSearch && matchFournisseur && matchStatut
  })
})

const facturesPayees = computed(() => {
  return factures.value.filter(f => f.reste_a_payer === 0).length
})

const facturesImpayees = computed(() => {
  return factures.value.filter(f => f.reste_a_payer === f.montant_ttc).length
})

const totalResteAPayer = computed(() => {
  return factures.value.reduce((sum, f) => sum + (f.reste_a_payer || 0), 0)
})

const getStatutLabel = (facture) => {
  if (facture.reste_a_payer === 0) return 'PAYE'
  if (facture.reste_a_payer === facture.montant_ttc) return 'IMPAYE'
  return 'PARTIEL'
}

const getStatutBadgeClass = (facture) => {
  if (facture.reste_a_payer === 0) return 'badge badge-success'
  if (facture.reste_a_payer === facture.montant_ttc) return 'badge badge-danger'
  return 'badge badge-warning'
}

const getResteClass = (reste) => {
  if (reste === 0) return 'text-green-600'
  return 'text-red-600'
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

const openCreateModal = () => {
  console.log('Open create modal')
}

const viewFacture = (facture) => {
  console.log('View facture:', facture)
}

const payerFacture = async (facture) => {
  if (!confirm(`Payer ${formatCurrency(facture.reste_a_payer)} pour cette facture ?`)) return
  
  const montant = facture.reste_a_payer
  const caisseId = 1 // À remplacer par une sélection de caisse
  
  try {
    await achatService.facture.payer(facture.id, montant, caisseId)
    await loadFactures()
    alert('Paiement enregistré avec succès')
  } catch (err) {
    error.value = err.response?.data?.message || 'Erreur lors du paiement'
    console.error('Erreur paiement:', err)
  }
}

const printFacture = (facture) => {
  console.log('Print facture:', facture)
  // Logique d'impression
}

onMounted(() => {
  loadFactures()
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
  @apply mb-4 pb-3 border-b border-gray-200 flex items-center justify-between;
}

.card-title {
  @apply text-base font-semibold text-gray-900;
}

.filter-grid {
  @apply grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4;
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

.input {
  @apply w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-gray-900 focus:border-gray-900 outline-none text-sm;
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
</style>