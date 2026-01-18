<template>
  <div class="page-container">
    <!-- Loading state -->
    <div v-if="loading" class="loading-state">
      <div class="spinner"></div>
      <p class="loading-text">Chargement des devis...</p>
    </div>

    <!-- Error state -->
    <div v-else-if="error" class="error-state">
      <div class="error-icon">
        <svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
        </svg>
      </div>
      <p class="error-message">{{ error }}</p>
      <button @click="loadDevis" class="btn-primary">
        Réessayer
      </button>
    </div>

    <!-- Content -->
    <div v-else class="content-wrapper">
      <!-- Header -->
      <div class="page-header fade-in" style="animation-delay: 0.1s">
        <div>
          <h1 class="page-title">Devis de Vente</h1>
          <p class="page-subtitle">Gestion des devis clients</p>
        </div>
        <button @click="openCreateModal" class="btn-primary">
          <Plus class="w-4 h-4" />
          <span>Nouveau Devis</span>
        </button>
      </div>

      <!-- Stats Cards -->
      <div class="stats-grid fade-in" style="animation-delay: 0.15s">
        <div class="stat-card">
          <div class="stat-content">
            <p class="stat-label">Total devis</p>
            <h3 class="stat-value">{{ devisList.length }}</h3>
          </div>
        </div>
        <div class="stat-card">
          <div class="stat-content">
            <p class="stat-label">Acceptés</p>
            <h3 class="stat-value text-green-600">{{ devisAcceptes }}</h3>
          </div>
        </div>
        <div class="stat-card">
          <div class="stat-content">
            <p class="stat-label">En attente</p>
            <h3 class="stat-value text-blue-600">{{ devisEnAttente }}</h3>
          </div>
        </div>
        <div class="stat-card">
          <div class="stat-content">
            <p class="stat-label">Montant total</p>
            <h3 class="stat-value text-purple-600">{{ formatCurrency(montantTotal) }}</h3>
          </div>
        </div>
      </div>

      <!-- Filters -->
      <div class="card fade-in" style="animation-delay: 0.2s">
        <div class="filter-grid">
          <div class="filter-item">
            <label class="label">Numéro Devis</label>
            <div class="search-box">
              <Search class="w-4 h-4 text-gray-400" />
              <input
                v-model="searchQuery"
                type="text"
                class="search-input"
                placeholder="DV-..."
              />
            </div>
          </div>
          <div class="filter-item">
            <label class="label">Client</label>
            <select v-model="selectedClient" class="select">
              <option value="">Tous les clients</option>
              <option value="1">Client A</option>
              <option value="2">Client B</option>
            </select>
          </div>
          <div class="filter-item">
            <label class="label">Statut</label>
            <select v-model="selectedStatut" class="select">
              <option value="">Tous les statuts</option>
              <option value="BROUILLON">Brouillon</option>
              <option value="ENVOYE">Envoyé</option>
              <option value="ACCEPTE">Accepté</option>
              <option value="REFUSE">Refusé</option>
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
          <h2 class="card-title">Liste des Devis</h2>
          <span class="text-xs text-gray-500">{{ filteredDevis.length }} résultat(s)</span>
        </div>
        <div class="table-wrapper">
          <table class="table">
            <thead>
              <tr>
                <th>Numéro Devis</th>
                <th>Date</th>
                <th>Client</th>
                <th>Filiale</th>
                <th class="text-right">Montant TTC</th>
                <th>Personnel</th>
                <th class="text-center">Statut</th>
                <th class="text-center">Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr v-if="filteredDevis.length === 0">
                <td colspan="8" class="text-center py-8 text-gray-500">
                  Aucun devis trouvé
                </td>
              </tr>
              <tr v-else v-for="devis in filteredDevis" :key="devis.id" class="table-row">
                <td class="font-medium">{{ devis.numero_devis }}</td>
                <td class="text-gray-600">{{ formatDate(devis.date_devis) }}</td>
                <td class="font-medium">{{ devis.client }}</td>
                <td class="text-gray-600">{{ devis.filiale }}</td>
                <td class="text-right font-semibold">{{ formatCurrency(devis.montant_ttc) }}</td>
                <td class="text-xs text-gray-500">{{ devis.personnel }}</td>
                <td class="text-center">
                  <span :class="getStatutBadgeClass(devis.statut)">
                    {{ devis.statut }}
                  </span>
                </td>
                <td>
                  <div class="table-actions">
                    <button @click="viewDevis(devis)" class="action-btn" title="Voir">
                      <Eye class="w-4 h-4" />
                    </button>
                    <button 
                      v-if="devis.statut === 'ACCEPTE'" 
                      @click="convertToBC(devis)" 
                      class="action-btn text-green-600" 
                      title="Créer BC"
                    >
                      <Check class="w-4 h-4" />
                    </button>
                    <button @click="printDevis(devis)" class="action-btn" title="Imprimer">
                      <Printer class="w-4 h-4" />
                    </button>
                    <button 
                      v-if="devis.statut === 'BROUILLON'"
                      @click="deleteDevis(devis.id)" 
                      class="action-btn text-red-600" 
                      title="Supprimer"
                    >
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
import { Plus, Eye, Check, Printer, Trash2, Search } from 'lucide-vue-next'
import venteService from '@/services/venteService'

const searchQuery = ref('')
const selectedClient = ref('')
const selectedStatut = ref('')
const dateFilter = ref('')

const devisList = ref([])
const loading = ref(false)
const error = ref(null)

const loadDevis = async () => {
  try {
    loading.value = true
    error.value = null
    const response = await venteService.devis.getAll()
    devisList.value = response.data || []
  } catch (err) {
    error.value = err.response?.data?.message || 'Erreur lors du chargement des devis'
    console.error('Erreur chargement devis:', err)
  } finally {
    loading.value = false
  }
}

const filteredDevis = computed(() => {
  return devisList.value.filter(devis => {
    const matchSearch = !searchQuery.value || 
      devis.numero_devis?.toLowerCase().includes(searchQuery.value.toLowerCase())
    
    const matchClient = !selectedClient.value || devis.client === selectedClient.value
    const matchStatut = !selectedStatut.value || devis.statut === selectedStatut.value
    
    return matchSearch && matchClient && matchStatut
  })
})

const devisAcceptes = computed(() => {
  return devisList.value.filter(d => d.statut === 'ACCEPTE').length
})

const devisEnAttente = computed(() => {
  return devisList.value.filter(d => d.statut === 'ENVOYE').length
})

const montantTotal = computed(() => {
  return devisList.value.reduce((sum, d) => sum + (d.montant_ttc || 0), 0)
})

const getStatutBadgeClass = (statut) => {
  switch(statut) {
    case 'BROUILLON': return 'badge badge-secondary'
    case 'ENVOYE': return 'badge badge-info'
    case 'ACCEPTE': return 'badge badge-success'
    case 'REFUSE': return 'badge badge-danger'
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

const openCreateModal = () => {
  console.log('Open create modal')
}

const viewDevis = (devis) => {
  console.log('View devis:', devis)
}

const convertToBC = async (devis) => {
  if (!confirm('Convertir ce devis en bon de commande ?')) return
  
  try {
    await venteService.devis.convertToBonCommande(devis.id)
    await loadDevis()
    alert('Devis converti en bon de commande avec succès')
  } catch (err) {
    error.value = err.response?.data?.message || 'Erreur lors de la conversion'
    console.error('Erreur conversion:', err)
  }
}

const printDevis = (devis) => {
  console.log('Print devis:', devis)
  // Logique d'impression
}

const deleteDevis = async (id) => {
  if (!confirm('Êtes-vous sûr de vouloir supprimer ce devis ?')) return
  
  try {
    await venteService.devis.delete(id)
    await loadDevis()
  } catch (err) {
    error.value = err.response?.data?.message || 'Erreur lors de la suppression'
    console.error('Erreur suppression:', err)
  }
}

onMounted(() => {
  loadDevis()
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

.badge-info {
  @apply bg-blue-100 text-blue-700;
}

.badge-secondary {
  @apply bg-gray-100 text-gray-700;
}
</style>