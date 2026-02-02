<template>
  <div class="page-container">
    <!-- Loading state -->
    <div v-if="loading" class="loading-state">
      <div class="spinner"></div>
      <p class="loading-text">Chargement des bons de commande...</p>
    </div>

    <!-- Error state -->
    <div v-else-if="error" class="error-state">
      <div class="error-icon">
        <svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
        </svg>
      </div>
      <p class="error-message">{{ error }}</p>
      <button @click="loadBonCommandes" class="btn-primary">
        Réessayer
      </button>
    </div>

    <!-- Content -->
    <div v-else class="content-wrapper">
      <!-- Header -->
      <div class="page-header fade-in" style="animation-delay: 0.1s">
        <div>
          <h1 class="page-title">Liste des Bons de Commande</h1>
          <p class="page-subtitle">Liste des bons de commande de vente</p>
        </div>
        <button @click="openCreateModal" class="btn-primary">
          <span>Nouveau BC</span>
        </button>
      </div>

      <!-- Stats Cards -->
      <div class="stats-grid fade-in" style="animation-delay: 0.15s">
        <div class="stat-card">
          <div class="stat-content">
            <p class="stat-label">Total BC</p>
            <h3 class="stat-value">{{ bonCommandes.length }}</h3>
          </div>
        </div>
        <div class="stat-card">
          <div class="stat-content">
            <p class="stat-label">Validés</p>
            <h3 class="stat-value text-green-600">{{ bcValides }}</h3>
          </div>
        </div>
        <div class="stat-card">
          <div class="stat-content">
            <p class="stat-label">Livrés</p>
            <h3 class="stat-value text-blue-600">{{ bcLivres }}</h3>
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
            <label class="label">Numéro BC</label>
            <div class="search-box">
              <input
                v-model="searchQuery"
                type="text"
                class="search-input"
                placeholder="BCV-..."
              />
            </div>
          </div>
          <div class="filter-item">
            <label class="label">Client</label>
            <select v-model="selectedClient" class="select">
              <option value="">Tous les clients</option>
              <option v-for="c in clients" :key="c.id" :value="c.id">{{ c.nom }}</option>
            </select>
          </div>
          <div class="filter-item">
            <label class="label">Statut</label>
            <select v-model="selectedStatut" class="select">
              <option value="">Tous les statuts</option>
              <option value="VALIDE">Validé</option>
              <option value="LIVRE">Livré</option>
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
          <h2 class="card-title">Liste des Bons de Commande</h2>
          <span class="text-xs text-gray-500">{{ filteredBonCommandes.length }} résultat(s)</span>
        </div>
        <div class="table-wrapper">
          <table class="table">
            <thead>
              <tr>
                <th>Numéro BC</th>
                <th>Date Commande</th>
                <th>Client</th>
                <th>Filiale</th>
                <th class="text-right">Montant TTC</th>
                <th class="text-center">Statut</th>
                <th>Devis Origine</th>
                <th class="text-center">Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr v-if="filteredBonCommandes.length === 0">
                <td colspan="8" class="text-center py-8 text-gray-500">
                  Aucun bon de commande trouvé
                </td>
              </tr>
              <tr v-else v-for="bc in filteredBonCommandes" :key="bc.id" class="table-row">
                <td class="font-medium">{{ bc.numero_bc }}</td>
                <td class="text-gray-600">{{ formatDate(bc.date_commande) }}</td>
                <td class="font-medium">{{ bc.client_nom }}</td>
                <td class="text-gray-600">{{ bc.filiale_nom }}</td>
                <td class="text-right font-semibold">{{ formatCurrency(bc.montant_ttc) }}</td>
                <td class="text-center">
                  <span :class="getStatutBadgeClass(bc.statut)">
                    {{ bc.statut }}
                  </span>
                </td>
                <td class="text-xs text-gray-500">{{ bc.numero_devis || '-' }}</td>
                <td>
                  <div class="table-actions">
                    <button @click="viewBC(bc)" class="action-btn" title="Voir">
                      Voir
                    </button>
                    <button 
                      v-if="bc.statut === 'VALIDE' && !facturedBonCommandes.has(bc.id)"
                      @click="convertToFacture(bc)" 
                      class="action-btn text-green-600" 
                      title="Créer facture"
                    >
                      Facturer
                    </button>
                    <span 
                      v-else-if="facturedBonCommandes.has(bc.id)"
                      class="text-xs text-gray-500 font-medium px-2 py-1"
                    >
                      Facturé
                    </span>
                    <button @click="printBC(bc)" class="action-btn" title="Imprimer">
                      Imprimer
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- Notification Toast -->
    <div 
      v-if="showNotification" 
      :class="[
        'notification-toast',
        notificationType === 'success' ? 'notification-success' : 'notification-error'
      ]"
    >
      <div class="notification-content">
        <div class="notification-icon">
          <svg v-if="notificationType === 'success'" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
          </svg>
          <svg v-else class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
          </svg>
        </div>
        <p class="notification-text">{{ notificationMessage }}</p>
        <button @click="closeNotification" class="notification-close">
          <X class="w-4 h-4" />
        </button>
      </div>
    </div>

    <!-- Modal Création Facture -->
    <div v-if="showFactureModal" class="modal-overlay" @click="showFactureModal = false">
      <div class="modal-content" @click.stop>
        <div class="modal-header">
          <h3 class="modal-title">Créer une facture</h3>
          <button @click="showFactureModal = false" class="modal-close">
            <X class="w-5 h-5" />
          </button>
        </div>
        <div class="modal-body">
          <div v-if="selectedBC" class="mb-6">
            <div class="bg-gray-50 rounded-lg p-4">
              <h4 class="font-medium text-gray-900 mb-2">Bon de commande sélectionné</h4>
              <div class="grid grid-cols-2 gap-4 text-sm">
                <div>
                  <span class="text-gray-600">Numéro BC:</span>
                  <span class="font-medium ml-2">{{ selectedBC.numero_bc }}</span>
                </div>
                <div>
                  <span class="text-gray-600">Client:</span>
                  <span class="font-medium ml-2">{{ selectedBC.client_nom }}</span>
                </div>
                <div>
                  <span class="text-gray-600">Date commande:</span>
                  <span class="font-medium ml-2">{{ formatDate(selectedBC.date_commande) }}</span>
                </div>
                <div>
                  <span class="text-gray-600">Montant BC:</span>
                  <span class="font-medium ml-2">{{ formatCurrency(selectedBC.montant_ttc) }}</span>
                </div>
              </div>
            </div>
          </div>

          <form @submit.prevent="confirmCreateFacture" class="space-y-4">
            <div class="form-group">
              <label for="date_facture" class="label">Date de facturation</label>
              <input 
                id="date_facture"
                v-model="factureForm.date_facture" 
                type="date" 
                class="input"
                required
              />
            </div>

            <div class="form-group">
              <label for="montant_ttc" class="label">Montant TTC (MGA)</label>
              <input 
                id="montant_ttc"
                v-model.number="factureForm.montant_ttc" 
                type="number" 
                step="0.01"
                class="input"
                required
              />
            </div>

            <div class="form-group">
              <label for="reste_a_payer" class="label">Reste à payer (MGA)</label>
              <input 
                id="reste_a_payer"
                v-model.number="factureForm.reste_a_payer" 
                type="number" 
                step="0.01"
                class="input"
                required
              />
              <p class="text-sm text-gray-500 mt-1">
                Montant restant à payer par le client
              </p>
            </div>

            <div class="form-actions">
              <button type="button" @click="showFactureModal = false" class="btn-secondary">
                Annuler
              </button>
              <button type="submit" :disabled="loadingFactureCreation" class="btn-primary">
                <span v-if="loadingFactureCreation">Création...</span>
                <span v-else>Créer la facture</span>
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { X } from 'lucide-vue-next'
import venteService from '@/services/venteService'
import entrepriseService from '@/services/entrepriseService'

const searchQuery = ref('')
const selectedClient = ref('')
const selectedStatut = ref('')
const dateFilter = ref('')

const bonCommandes = ref([])
const loading = ref(false)
const error = ref(null)
const clients = ref([])

// Modal création facture
const showFactureModal = ref(false)
const selectedBC = ref(null)
const loadingFactureCreation = ref(false)
const facturedBonCommandes = ref(new Set())
const showNotification = ref(false)
const notificationMessage = ref('')
const notificationType = ref('success') // 'success', 'error', 'warning'
const factureForm = ref({
  date_facture: new Date().toISOString().split('T')[0],
  montant_ttc: 0,
  reste_a_payer: 0
})

const loadBonCommandes = async () => {
  try {
    loading.value = true
    error.value = null
    const response = await venteService.bonCommande.getAll()
    bonCommandes.value = response.data || []
    
    // Vérifier quels BCs ont déjà été facturés
    await checkFacturedStatus(bonCommandes.value)
    
  } catch (err) {
    error.value = err.response?.data?.message || 'Erreur lors du chargement des bons de commande'
    console.error('Erreur chargement BC:', err)
  } finally {
    loading.value = false
  }
}

const loadClients = async () => {
  try {
    const res = await entrepriseService.getAll({ type_entreprise: 'CLIENT' })
    clients.value = res.data || []
  } catch (err) {
    console.error('Erreur chargement clients:', err)
  }
}

const checkFacturedStatus = async (bonCommandesList) => {
  const facturedSet = new Set()
  
  for (const bc of bonCommandesList) {
    try {
      const response = await venteService.bonCommande.checkIfFactured(bc.id)
      if (response.isFactured) {
        facturedSet.add(bc.id)
      }
    } catch (err) {
      console.error(`Erreur vérification facturation BC ${bc.id}:`, err)
    }
  }
  
  facturedBonCommandes.value = facturedSet
}

const filteredBonCommandes = computed(() => {
  return bonCommandes.value.filter(bc => {
    const matchSearch = !searchQuery.value || 
      (bc.numero_bc && bc.numero_bc.toLowerCase().includes(searchQuery.value.toLowerCase()))
    const matchClient = !selectedClient.value || bc.entreprise_client_id === parseInt(selectedClient.value)
    const matchStatut = !selectedStatut.value || bc.statut === selectedStatut.value
    const matchDate = !dateFilter.value || (bc.date_commande && bc.date_commande.startsWith(dateFilter.value))

    return matchSearch && matchClient && matchStatut && matchDate
  })
})

const bcValides = computed(() => bonCommandes.value.filter(bc => bc.statut === 'VALIDE').length)
const bcLivres = computed(() => bonCommandes.value.filter(bc => bc.statut === 'LIVRE').length)
const montantTotal = computed(() => bonCommandes.value.reduce((sum, bc) => sum + (bc.montant_ttc || 0), 0))

const getStatutBadgeClass = (statut) => {
  switch(statut) {
    case 'VALIDE': return 'badge badge-success'
    case 'LIVRE': return 'badge badge-info'
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
  return date ? new Date(date).toLocaleDateString('fr-FR') : '-'
}

const openCreateModal = () => {
  // Navigate to BC creation
  router.push('/ventes/bon-commande/nouveau')
}

const router = useRouter()

const viewBC = (bc) => {
  router.push({ name: 'liste-commande-vente-detail', params: { id: bc.id } })
}

const convertToFacture = (bc) => {
  selectedBC.value = bc
  // Pré-remplir le formulaire avec les données du BC
  factureForm.value = {
    date_facture: new Date().toISOString().split('T')[0],
    montant_ttc: bc.montant_ttc || 0,
    reste_a_payer: bc.montant_ttc || 0
  }
  showFactureModal.value = true
}

const confirmCreateFacture = async () => {
  if (!selectedBC.value) return
  
  try {
    loadingFactureCreation.value = true
    
    // Utiliser la nouvelle méthode de conversion avec données personnalisées
    const customData = {
      date_facture: factureForm.value.date_facture,
      montant_ttc: factureForm.value.montant_ttc,
      reste_a_payer: factureForm.value.reste_a_payer
    }
    
    await venteService.bonCommande.convertToFactureWithCustomData(selectedBC.value.id, customData)
    await loadBonCommandes()
    
    showFactureModal.value = false
    selectedBC.value = null
    
    // Afficher notification de succès
    showSuccessNotification('Facture créée avec succès')
    
  } catch (err) {
    const errorMessage = err.response?.data?.error || err.response?.data?.message || 'Erreur lors de la création de la facture'
    
    // Afficher notification d'erreur
    if (err.response?.status === 400) {
      showErrorNotification(errorMessage)
    } else {
      showErrorNotification('Erreur technique lors de la création de la facture')
    }
    
    console.error('Erreur création facture:', err)
  } finally {
    loadingFactureCreation.value = false
  }
}

const printBC = (bc) => {
  console.log('Imprimer BC', bc)
}

const showSuccessNotification = (message) => {
  notificationMessage.value = message
  notificationType.value = 'success'
  showNotification.value = true
  setTimeout(() => {
    showNotification.value = false
  }, 5000)
}

const showErrorNotification = (message) => {
  notificationMessage.value = message
  notificationType.value = 'error'
  showNotification.value = true
  setTimeout(() => {
    showNotification.value = false
  }, 8000)
}

const closeNotification = () => {
  showNotification.value = false
}

onMounted(() => {
  loadClients()
  loadBonCommandes()
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
  @apply w-full border-0 outline-none text-sm;
}

.table {
  @apply w-full border-collapse text-sm;
}

.table thead {
  @apply bg-gray-50 border-b border-gray-200;
}

.table th,
.table td {
  @apply px-4 py-3 text-left;
}

.table-row {
  @apply hover:bg-gray-50 transition-colors;
}

.table-actions {
  @apply flex items-center gap-2;
}

.action-btn {
  @apply inline-flex items-center justify-center px-2 py-1 rounded-md border border-gray-200 bg-white hover:shadow-sm text-sm;
}

.badge {
  @apply inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium;
}

.badge-success {
  @apply bg-green-100 text-green-800;
}

.badge-info {
  @apply bg-blue-100 text-blue-800;
}

.badge-secondary {
  @apply bg-gray-100 text-gray-800;
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

.form-group {
  @apply space-y-2;
}

.form-actions {
  @apply flex items-center justify-end gap-3 pt-4 border-t border-gray-200;
}

.btn-secondary {
  @apply px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition-colors;
}

.btn-primary {
  @apply px-4 py-2 bg-gray-900 text-white rounded-lg font-medium hover:bg-gray-800 transition-colors;
}

.btn-primary:disabled {
  @apply opacity-50 cursor-not-allowed;
}

/* Notification Toast */
.notification-toast {
  @apply fixed top-4 right-4 z-50 max-w-md w-full transform transition-all duration-300 ease-in-out;
  animation: slideInRight 0.3s ease-out;
}

.notification-success {
  @apply bg-green-50 border border-green-200 rounded-lg shadow-lg;
}

.notification-error {
  @apply bg-red-50 border border-red-200 rounded-lg shadow-lg;
}

.notification-content {
  @apply flex items-start p-4 space-x-3;
}

.notification-icon {
  @apply flex-shrink-0;
}

.notification-success .notification-icon {
  @apply text-green-500;
}

.notification-error .notification-icon {
  @apply text-red-500;
}

.notification-text {
  @apply flex-1 text-sm font-medium;
}

.notification-success .notification-text {
  @apply text-green-800;
}

.notification-error .notification-text {
  @apply text-red-800;
}

.notification-close {
  @apply flex-shrink-0 text-gray-400 hover:text-gray-600 transition-colors duration-200;
}

@keyframes slideInRight {
  from {
    transform: translateX(100%);
    opacity: 0;
  }
  to {
    transform: translateX(0);
    opacity: 1;
  }
}</style>