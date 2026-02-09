<template>
  <div class="page-container">
    <!-- Loading state -->
    <div v-if="loading" class="loading-state">
      <div class="spinner"></div>
      <p class="loading-text">Chargement des proformas...</p>
    </div>

    <!-- Error state (Adapté au style) -->
    <div v-else-if="error" class="loading-state">
      <div class="text-red-500 mb-4">
        <svg class="w-12 h-12 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
            d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
        </svg>
      </div>
      <p class="text-sm text-red-600 mb-4">{{ error }}</p>
      <button @click="loadProformas" class="btn-primary">
        Réessayer
      </button>
    </div>

    <!-- Content -->
    <div v-else class="content-wrapper">
      <!-- Header -->
      <div class="page-header fade-in" style="animation-delay: 0.1s">
        <div>
          <h1 class="page-title">Proforma Fournisseur</h1>
          <p class="page-subtitle">Gestion des devis fournisseurs</p>
        </div>
        <div class="flex items-center gap-2">
          <button @click="exportPdf" class="btn btn-secondary">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
            <span>Exporter</span>
          </button>
          <button @click="openCreateModal" class="btn-primary">
            <Plus class="w-4 h-4" />
            <span>Nouveau Proforma</span>
          </button>
        </div>
      </div>

      <!-- Stats Cards -->
      <div class="stats-grid fade-in" style="animation-delay: 0.15s">
        <div class="stat-card">
          <div class="stat-content">
            <p class="stat-label">Total proformas</p>
            <h3 class="stat-value">{{ proformasAll.length }}</h3>
          </div>
        </div>
        <div class="stat-card">
          <div class="stat-content">
            <p class="stat-label">Brouillons</p>
            <h3 class="stat-value text-gray-600">{{ proformasBrouillon }}</h3>
          </div>
        </div>
        <div class="stat-card">
          <div class="stat-content">
            <p class="stat-label">Validés</p>
            <h3 class="stat-value text-green-600">{{ proformasValides }}</h3>
          </div>
        </div>
        <div class="stat-card">
          <div class="stat-content">
            <p class="stat-label">Montant total TTC</p>
            <h3 class="stat-value text-blue-600">{{ formatCurrency(montantTotal) }}</h3>
          </div>
        </div>
      </div>

      <!-- Filters -->
      <div class="card fade-in" style="animation-delay: 0.2s">
        <div class="flex items-center justify-between mb-4">
          <div class="flex items-center gap-2">
            <Filter class="w-4 h-4" />
            <h3 class="text-sm font-medium">Filtres</h3>
          </div>
          <div class="flex items-center gap-2">
            <button @click="applyFilters" class="btn-secondary text-xs">Appliquer</button>
            <button @click="resetFilters" class="text-xs text-gray-600 hover:text-gray-900">Réinitialiser</button>
          </div>
        </div>

        <div class="filter-grid">
          <div class="filter-item">
            <label class="label">Rechercher</label>
            <div class="search-box">
              <Search class="w-4 h-4 text-gray-400" />
              <input v-model="searchQuery" type="text" class="search-input" placeholder="Numéro, fournisseur..." />
            </div>
          </div>
          <div class="filter-item">
            <label class="label">Fournisseur</label>
            <select v-model="selectedFournisseur" class="select">
              <option value="">Tous les fournisseurs</option>
              <option v-for="e in entreprisesOptions.filter(ent => ent.type_entreprise === 'FOURNISSEUR')" :key="e.id" :value="e.id">{{ e.nom || e.raison_sociale || e.name }}</option>
            </select>
          </div>
          <div class="filter-item">
            <label class="label">Statut</label>
            <select v-model="selectedStatut" class="select">
              <option value="">Tous les statuts</option>
              <option v-for="s in statutsOptions" :key="s.id" :value="s.id">{{ s.libelle }}</option>
            </select>
          </div>
          <div class="filter-item">
            <label class="label">Période</label>
            <input v-model="dateFilter" type="date" class="input" />
          </div>
        </div>
      </div>

      <!-- Table -->
      <div class="card fade-in" style="animation-delay: 0.25s">
        <div class="card-header-simple">
          <h2 class="card-title">Liste des Proformas</h2>
          <span class="text-xs text-gray-500">{{ filteredProformas.length }} résultat(s)</span>
        </div>
        <div class="table-wrapper">
          <table class="table">
            <thead>
              <tr>
                <th>Numéro</th>
                <th>Date Émission</th>
                <th>Fournisseur</th>
                <th>Filiale</th>
                <th>Date Validité</th>
                <th class="text-right">Montant TTC</th>
                <th class="text-center">Statut</th>
                <th class="text-center">Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr v-if="filteredProformas.length === 0">
                <td colspan="8" class="text-center py-8 text-gray-500">
                  Aucun proforma trouvé
                </td>
              </tr>
              <tr v-else v-for="proforma in filteredProformas" :key="proforma.id" class="table-row">
                <td class="font-medium">{{ proforma.numero_proforma }}</td>
                <td class="text-gray-600">{{ formatDate(proforma.date_emission) }}</td>
                <td class="font-medium">{{ proforma.fournisseur }}</td>
                <td class="text-gray-600">{{ proforma.filiale }}</td>
                <td class="text-gray-600">{{ formatDate(proforma.date_validite) }}</td>
                <td class="text-right font-medium">{{ formatCurrency(proforma.montant_ttc) }}</td>
                <td class="text-center">
                  <span :class="getStatutBadgeClass(proforma.statut)">
                    {{ proforma.statut_libelle }}
                  </span>
                </td>
                <td>
                  <div class="table-actions">
                    <button @click="viewProforma(proforma)" class="action-btn" title="Voir">
                      <Eye class="w-4 h-4" />
                    </button>
                    <button v-if="proforma.statut === 'VALIDE'" @click="convertToBonCommande(proforma)"
                      class="action-btn text-green-600" title="Convertir en BC">
                      <Check class="w-4 h-4" />
                    </button>
                    <button
                      v-if="canValidate(proforma)"
                      @click="validerProforma(proforma)" class="action-btn text-green-500" title="Valider proforma">
                      <Check class="w-4 h-4" />
                    </button>
                    <button @click="deleteProforma(proforma.id)" class="action-btn text-red-600" title="Supprimer">
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
import { Plus, Eye, Check, Trash2, Search, Filter } from 'lucide-vue-next'
import achatService from '@/services/achatService'
import proformaFournisseurService from '@/services/proformaFournisseurService'
import entrepriseService from '@/services/entrepriseService'

const router = useRouter()
const currentUser = ref({})
const canValidate = (proforma) => {
  const niveau = Number(currentUser.value?.niveau_acces || 0)
  return (proforma.statut === 'BROUILLON' || proforma.statut === 'EN_ATTENTE') && niveau >= 5
}
const searchQuery = ref('')
const selectedFournisseur = ref('')
const selectedStatut = ref('')
const dateFilter = ref('')

// Data sets
const proformas = ref([]) // filtered list shown in table
const proformasAll = ref([]) // full dataset for stats and filter population
const entreprisesOptions = ref([])
const statutsOptions = ref([])

const loading = ref(false)
const error = ref(null)

// Load all proformas (for stats and filter options) and entreprises
const loadFiltersAndStats = async () => {
  try {
    const [allResp, eResp] = await Promise.all([
      proformaFournisseurService.getAll(),
      entrepriseService.getAll()
    ])
    proformasAll.value = allResp.data || []
    entreprisesOptions.value = eResp.data ?? eResp

    // build statut options dynamically from returned proformas
    const statMap = new Map()
    for (const p of proformasAll.value) {
      if (p.statut_id) statMap.set(p.statut_id, p.statut_libelle || p.statut || String(p.statut_id))
    }
    statutsOptions.value = Array.from(statMap.entries()).map(([id, libelle]) => ({ id, libelle }))
  } catch (err) {
    console.error('Erreur chargement filtres/statistiques', err)
  }
}

// Load proformas with current filters (server-side filtering)
const loadProformas = async () => {
  loading.value = true
  try {
    const params = {}
    if (selectedFournisseur.value) params.fournisseur_id = selectedFournisseur.value
    if (selectedStatut.value) params.statut_id = selectedStatut.value
    if (dateFilter.value) params.date_debut = dateFilter.value // simple period filter (start only)
    if (searchQuery.value) params.search = searchQuery.value

    const response = await proformaFournisseurService.getAll(params)
    proformas.value = response.data || []
    // alert(JSON.stringify(proformas.value));
  } catch (err) {
    error.value = err.response?.data?.message || 'Erreur lors du chargement des proformas filtrées'
    console.error('Erreur chargement proformas filtrées:', err)
  } finally {
    loading.value = false
  }
}

const filteredProformas = computed(() => {
  return proformas.value
})

const proformasBrouillon = computed(() => {
  return proformasAll.value.filter(p => p.statut_id == 1).length
})

const proformasValides = computed(() => {
  return proformasAll.value.filter(p => p.statut_id == 3).length
})

const montantTotal = computed(() => {
  return proformasAll.value.reduce((sum, p) => sum + (Number(p.montant_ttc) || 0), 0)
})

const getStatutBadgeClass = (statut) => {
  switch (statut) {
    case 'BROUILLON': return 'badge badge-secondary'
    case 'VALIDE': return 'badge badge-success'
    default: return 'badge badge-secondary'
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
  return new Date(date).toLocaleDateString('fr-FR')
}

const openCreateModal = () => {
  // rediriger vers la page de création de proforma fournisseur
  router.push({ name: 'proforma-fournisseur-new' })
}

const viewProforma = (proforma) => {
  router.push({ name: 'proforma-fournisseur-detail', params: { id: proforma.id } })
}

const convertToBonCommande = async (proforma) => {
  if (!confirm('Convertir ce proforma en bon de commande ?')) return

  try {
    await achatService.convertProformaToBonCommande(proforma.id)
    await loadProformas()
    alert('Proforma converti en bon de commande avec succès')
  } catch (err) {
    error.value = err.response?.data?.message || 'Erreur lors de la conversion'
    console.error('Erreur conversion:', err)
  }
}

const applyFilters = () => {
  loadProformas()
}

const resetFilters = () => {
  searchQuery.value = ''
  selectedFournisseur.value = ''
  selectedStatut.value = ''
  dateFilter.value = ''
  loadProformas()
}

// Export PDF (liste proformas)
const exportPdf = () => {
  const base = import.meta.env.VITE_API_BASE_URL || '/api'
  const params = new URLSearchParams()
  if (searchQuery.value) params.set('search', searchQuery.value)
  if (selectedFournisseur.value) params.set('fournisseur_id', selectedFournisseur.value)
  if (selectedStatut.value) params.set('statut_id', selectedStatut.value)
  if (dateFilter.value) params.set('date_debut', dateFilter.value)
  const user = currentUser.value || {}
  if (user?.entreprise_id) params.set('entreprise_id', user.entreprise_id)
  const url = `${base}/proforma-fournisseur/export${params.toString() ? ('?' + params.toString()) : ''}`
  window.open(url, '_blank')
}
const deleteProforma = async (id) => {
  if (!confirm('Êtes-vous sûr de vouloir supprimer ce proforma ?')) return

  try {
    await proformaFournisseurService.delete(id)
    await loadProformas()
  } catch (err) {
    error.value = err.response?.data?.message || 'Erreur lors de la suppression'
    console.error('Erreur suppression:', err)
  }
}

const validerProforma = async (proforma) => {
  if (!confirm(`Valider la proforma ${proforma.numero_proforma} ?`)) return

  try {
    const user = currentUser.value || {}
    await proformaFournisseurService.valider(proforma.id, user.id)
    await loadProformas()
    alert('Proforma validée')
  } catch (err) {
    error.value = err.response?.data?.message || 'Erreur lors de la validation'
    console.error('Erreur validation:', err)
    alert(err.response?.data?.error || 'Erreur lors de la validation')
  }
}

onMounted(() => {
  try { currentUser.value = JSON.parse(localStorage.getItem('user') || '{}') } catch (e) { currentUser.value = {} }
  loadFiltersAndStats()
  loadProformas()
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
  @apply w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-gray-900 focus:border-gray-900 outline-none text-sm bg-white;
}

.select {
  @apply w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-gray-900 focus:border-gray-900 outline-none text-sm bg-white;
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

/* Bouton primary ajouté au cas où il manquerait dans les styles globaux */
.btn-primary {
  @apply flex items-center gap-2 px-4 py-2.5 bg-gray-900 text-white rounded-lg text-sm font-medium hover:bg-gray-800 transition-colors;
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

.badge-info {
  @apply bg-blue-100 text-blue-700;
}

.badge-secondary {
  @apply bg-gray-100 text-gray-700;
}
</style>