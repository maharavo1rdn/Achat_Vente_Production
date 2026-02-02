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
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
            d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
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
          <h1 class="page-title">Factures de Vente</h1>
          <p class="page-subtitle">Suivi des factures clients</p>
        </div>
        <button @click="showBonCommandeModal = true" class="btn-primary">
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
            <p class="stat-label">Reste à encaisser</p>
            <h3 class="stat-value text-orange-600">{{ formatCurrency(totalResteAEncaisser) }}</h3>
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
              <input v-model="searchQuery" type="text" class="search-input" placeholder="FV-..." />
            </div>
          </div>
          <div class="filter-item">
            <label class="label">Client</label>
            <select v-model="selectedClient" class="select">
              <option value="">Tous les clients</option>
              <option 
                v-for="client in uniqueClients" 
                :key="client" 
                :value="client"
              >
                {{ client }}
              </option>
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
                <th>Client</th>
                <th class="text-right">Montant TTC</th>
                <th class="text-right">Reste à Encaisser</th>
                <th class="text-center">Statut</th>
                <th>Vendeur</th>
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
                <td class="font-medium">
                  {{ facture.numero_facture }}
                  <div v-if="facture.remarques" class="text-xs text-gray-500 mt-1">{{ facture.remarques }}</div>
                </td>
                <td class="text-gray-600">{{ formatDate(facture.date_facture) }}</td>
                <td class="font-medium">{{ facture.client_nom }}</td>
                <td class="text-right font-semibold">{{ formatCurrency(facture.montant_ttc) }}</td>
                <td class="text-right font-semibold" :class="getResteClass(facture.reste_a_payer)">
                  {{ formatCurrency(facture.reste_a_payer) }}
                </td>
                <td class="text-center">
                  <span :class="getStatutBadgeClass(facture)">
                    {{ getStatutLabel(facture) }}
                  </span>
                </td>
                <td class="text-xs text-gray-500">{{ facture.personnel_nom }} {{ facture.personnel_prenom }}</td>
                <td>
                  <div class="table-actions">
                    <button @click="viewFacture(facture)" class="action-btn" title="Voir">
                      <Eye class="w-4 h-4" />
                    </button>
                    <button v-if="facture.reste_a_payer > 0" @click="encaisserFacture(facture)"
                      class="action-btn text-green-600" title="Encaisser">
                      <CreditCard class="w-4 h-4" />
                    </button>
                    <button @click="printFacture(facture)" class="action-btn" title="Imprimer">
                      <Printer class="w-4 h-4" />
                    </button>
                    <button @click="editRemarques(facture)" class="action-btn" title="Remarques">
                      <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M8 7h8M8 11h6m-9 8a9 9 0 1118 0 9 9 0 01-18 0z" />
                      </svg>
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- Modal Sélection Bon de Commande -->
    <div v-if="showBonCommandeModal" class="modal-overlay" @click="showBonCommandeModal = false">
      <div class="modal-content" @click.stop>
        <div class="modal-header">
          <h3 class="modal-title">Créer une facture</h3>
          <button @click="showBonCommandeModal = false" class="modal-close">
            <X class="w-5 h-5" />
          </button>
        </div>
        <div class="modal-body">
          <p class="text-sm text-gray-600 mb-4">
            Sélectionnez un bon de commande validé pour créer la facture correspondante :
          </p>
          
          <div v-if="loadingBC" class="loading-state">
            <div class="spinner"></div>
            <p class="loading-text">Chargement des bons de commande...</p>
          </div>
          
          <div v-else-if="bonCommandes.length === 0" class="text-center py-8 text-gray-500">
            Aucun bon de commande validé disponible pour facturation
          </div>
          
          <div v-else class="space-y-3">
            <div 
              v-for="bc in bonCommandes" 
              :key="bc.id" 
              class="bc-item"
              @click="createFactureFromBC(bc)"
            >
              <div class="bc-info">
                <div class="bc-header">
                  <span class="bc-number">{{ bc.numero_bc }}</span>
                  <span class="bc-date">{{ formatDate(bc.date_commande) }}</span>
                </div>
                <div class="bc-details">
                  <span class="bc-client">{{ bc.client_nom }}</span>
                  <span class="bc-amount">{{ formatCurrency(bc.montant_ttc) }}</span>
                </div>
              </div>
              <button class="bc-select-btn">
                Sélectionner
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <PaymentForm v-if="showPaymentModal" :facture="currentFacture" type="vente" @close="showPaymentModal = false"
      @created="onPaymentCreated" />
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { useRouter } from 'vue-router'
import { Plus, Eye, CreditCard, Printer, Search, X } from 'lucide-vue-next'
import venteService from '@/services/venteService'

const router = useRouter()

const searchQuery = ref('')
const selectedClient = ref('')
const selectedStatut = ref('')
const dateFilter = ref('')

const factures = ref([])
const loading = ref(false)
const error = ref(null)

// Modal de sélection de bon de commande
const showBonCommandeModal = ref(false)
const bonCommandes = ref([])
const loadingBC = ref(false)

const loadFactures = async () => {
  try {
    loading.value = true
    error.value = null
    const response = await venteService.facture.getAll()
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
    
    const matchClient = !selectedClient.value || facture.client_nom === selectedClient.value
    const matchStatut = !selectedStatut.value || getStatutLabel(facture) === selectedStatut.value

    return matchSearch && matchClient && matchStatut
  })
})

const facturesPayees = computed(() => {
  return factures.value.filter(f => f.reste_a_payer === 0).length
})

const facturesImpayees = computed(() => {
  return factures.value.filter(f => f.reste_a_payer === f.montant_ttc).length
})

const totalResteAEncaisser = computed(() => {
  return factures.value.reduce((sum, f) => sum + (f.reste_a_payer || 0), 0)
})

const uniqueClients = computed(() => {
  const clients = [...new Set(factures.value.map(f => f.client_nom).filter(Boolean))]
  return clients.sort()
})

const getStatutLabel = (facture) => {
  if (parseFloat(facture.reste_a_payer) === 0) return 'PAYE'
  if (parseFloat(facture.reste_a_payer) === parseFloat(facture.montant_ttc)) return 'IMPAYE'
  return 'PARTIEL'
}

const getStatutBadgeClass = (facture) => {
  if (parseFloat(facture.reste_a_payer) === 0) return 'badge badge-success'
  if (parseFloat(facture.reste_a_payer) === parseFloat(facture.montant_ttc)) return 'badge badge-danger'
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

const loadBonCommandes = async () => {
  try {
    loadingBC.value = true
    const response = await venteService.bonCommande.getAll()
    // Filtrer les bons de commande qui peuvent être facturés (statut VALIDE et pas encore facturés)
    bonCommandes.value = response.data.filter(bc => 
      bc.statut === 'VALIDE' && !hasExistingFacture(bc.id)
    ) || []
  } catch (err) {
    console.error('Erreur chargement bons de commande:', err)
  } finally {
    loadingBC.value = false
  }
}

const hasExistingFacture = (bcId) => {
  return factures.value.some(f => f.bon_commande_vente_id === bcId)
}

const createFactureFromBC = async (bonCommande) => {
  if (!confirm(`Créer une facture à partir du bon de commande ${bonCommande.numero_bc} ?`)) return
  
  try {
    await venteService.bonCommande.convertToFacture(bonCommande.id)
    await loadFactures()
    showBonCommandeModal.value = false
    alert('Facture créée avec succès')
  } catch (err) {
    error.value = err.response?.data?.message || 'Erreur lors de la création de la facture'
    console.error('Erreur création facture:', err)
  }
}

const printFacture = (facture) => {
  console.log('Print facture:', facture)
  // Logique d'impression
}

const viewFacture = (facture) => {
  console.log('View facture:', facture)
}

import PaymentForm from '@/components/payment/PaymentForm.vue'
const showPaymentModal = ref(false)
const currentFacture = ref(null)

const encaisserFacture = (facture) => {
  router.push({ name: 'paiement-vente-new', params: { factureId: facture.id } })
}

const onPaymentCreated = async (id) => {
  await loadFactures()
}

const editRemarques = async (facture) => {
  const newText = window.prompt('Remarques:', facture.remarques || '')
  if (newText === null) return
  try {
    await venteService.facture.update(facture.id, { remarques: newText })
    await loadFactures()
  } catch (err) {
    console.error('Erreur mise à jour remarques:', err)
    alert('Erreur lors de la mise à jour des remarques')
  }
}

onMounted(() => {
  loadFactures()
})

// Watcher pour charger les bons de commande quand la modal s'ouvre
watch(showBonCommandeModal, (newValue) => {
  if (newValue) {
    loadBonCommandes()
  }
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

.modal-overlay {
  @apply fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50;
}

.modal-content {
  @apply bg-white rounded-lg shadow-xl max-w-2xl w-full mx-4 max-h-[80vh] overflow-hidden;
}

.modal-header {
  @apply flex items-center justify-between p-6 border-b border-gray-200;
}

.modal-title {
  @apply text-lg font-semibold text-gray-900;
}

.modal-close {
  @apply p-1 rounded-full text-gray-500 hover:text-gray-700 hover:bg-gray-100;
}

.modal-body {
  @apply p-6 overflow-y-auto max-h-96;
}

.bc-item {
  @apply border border-gray-200 rounded-lg p-4 cursor-pointer hover:border-gray-300 hover:shadow-sm transition-all flex items-center justify-between;
}

.bc-info {
  @apply flex-1;
}

.bc-header {
  @apply flex items-center justify-between mb-2;
}

.bc-number {
  @apply font-semibold text-gray-900;
}

.bc-date {
  @apply text-sm text-gray-500;
}

.bc-details {
  @apply flex items-center justify-between text-sm;
}

.bc-client {
  @apply text-gray-700;
}

.bc-amount {
  @apply font-semibold text-gray-900;
}

.bc-select-btn {
  @apply px-4 py-2 bg-gray-900 text-white rounded-lg text-sm font-medium hover:bg-gray-800 transition-colors;
}
</style>