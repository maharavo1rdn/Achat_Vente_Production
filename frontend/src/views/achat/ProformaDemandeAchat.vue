<template>
  <div class="page-container">
    <!-- Loading state -->
    <div v-if="loading" class="loading-state">
      <div class="spinner"></div>
      <p class="loading-text">Chargement des demandes...</p>
    </div>

    <!-- Content -->
    <div v-else class="content-wrapper">
      <!-- Header -->
      <div class="page-header fade-in" style="animation-delay: 0.1s">
        <div>
          <h1 class="page-title">Demandes d'Achat</h1>
          <p class="page-subtitle">Gestion des demandes d'achat (proforma)</p>
        </div>
        <div class="flex items-center gap-2">
          <button @click="exportPdf" class="btn btn-secondary">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
            <span>Exporter</span>
          </button>
          <button @click="createDemande" class="btn-primary">
            <Plus class="w-4 h-4" />
            <span>Nouvelle Demande</span>
          </button>
        </div>
      </div>

      <!-- Stats Cards -->
      <div class="stats-grid fade-in" style="animation-delay: 0.15s">
        <div class="stat-card">
          <div class="stat-content">
            <p class="stat-label">Total demandes</p>
            <h3 class="stat-value">{{ demandesAll.length }}</h3>
          </div>
        </div>
        <div class="stat-card">
          <div class="stat-content">
            <p class="stat-label">Validées</p>
            <h3 class="stat-value text-green-600">{{ valideesCount }}</h3>
          </div>
        </div>
        <div class="stat-card">
          <div class="stat-content">
            <p class="stat-label">Annulées</p>
            <h3 class="stat-value text-red-600">{{ annuleesCount }}</h3>
          </div>
        </div>
        <div class="stat-card">
          <div class="stat-content">
              <p class="stat-label">Montant estimé</p>
            <h3 class="stat-value text-blue-600">{{ formatCurrency(montantTotalDemandes) }}</h3>
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
            <button @click="resetFilters" class="text-xs text-gray-600 hover:text-gray-900">
              Réinitialiser
            </button>
          </div>
        </div>

        <div class="filter-grid">
          <div class="filter-item">
            <label class="label">Recherche</label>
            <div class="search-box">
              <Search class="w-4 h-4 text-gray-400" />
              <input v-model="searchQuery" type="text" placeholder="Numéro, demandeur..." class="search-input" />
            </div>
          </div>

          <div class="filter-item">
            <label class="label">Statut</label>
            <select v-model="filterStatut" class="select">
              <option value="">Tous</option>
              <option value="2">En attente</option>
              <option value="3">Validé</option>
              <option value="5">Annulé</option>
            </select>
          </div>

          <div class="filter-item">
            <label class="label">Date début</label>
            <input v-model="filterDateDebut" type="date" class="input" />
          </div>

          <div class="filter-item">
            <label class="label">Date fin</label>
            <input v-model="filterDateFin" type="date" class="input" />
          </div>
        </div>
      </div>

      <!-- Table -->
      <div class="card fade-in" style="animation-delay: 0.25s">
        <div class="card-header-simple">
          <h2 class="card-title">Liste des Demandes</h2>
          <div class="flex items-center gap-3">
            <span class="text-xs text-gray-500">{{ filteredDemandes.length }} demande(s)</span>
            <button @click="exportPdf" class="btn btn-secondary text-xs">Exporter PDF</button>
          </div>
        </div>
        <div class="table-wrapper">
          <table class="table">
            <thead>
              <tr>
                <th>Numéro DA</th>
                <th>Date demande</th>
                <th>Demandeur</th>
                <th>Entreprise</th>
                <th>Dépôt cible</th>
                <th>Date souhaitée</th>
                <th>Montant estimé</th>
                <th class="text-center">Statut</th>
                <th class="text-center">Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr v-if="filteredDemandes.length === 0">
                <td colspan="8" class="text-center py-8 text-gray-500">
                  Aucune demande trouvée
                </td>
              </tr>
              <tr v-else v-for="demande in filteredDemandes" :key="demande.id" class="table-row">
                <td class="font-medium">{{ demande.numero_da }}</td>
                <td class="text-gray-600">{{ formatDate(demande.date_demande) }}</td>
                <td class="font-medium">{{ demande.demandeur_nom }}</td>
                <td class="text-gray-600">{{ demande.entreprise_nom }}</td>
                <td class="text-xs text-gray-500">{{ demande.depot_nom || '-' }}</td>
                <td class="text-gray-600">{{ demande.date_souhaitee ? formatDate(demande.date_souhaitee) : '-' }}</td>
                <td class="text-right font-medium">{{ formatCurrency(demande.montant_ttc) }}</td>
                <td class="text-center">
                  <span :class="getStatutClass(demande.statut_code)">
                    {{ demande.statut_libelle }}
                  </span>
                </td>
                <td>
                  <div class="table-actions">
                    <button @click="viewDemande(demande)" class="action-btn" title="Voir détails">
                      <FileText class="w-4 h-4" />
                    </button>
                    <button v-if="demande.statut_id == 2" @click="editDemande(demande)" class="action-btn"
                      title="Modifier">
                      <Pencil class="w-4 h-4" />
                    </button>
                    <button v-if="demande.statut_id == 2" @click="validerDemande(demande)"
                      class="action-btn text-green-600" title="Valider">
                      <Check class="w-4 h-4" />
                    </button>
                    <button v-if="demande.statut_id == 2" @click="annulerDemande(demande)"
                      class="action-btn text-orange-600" title="Annuler">
                      <X class="w-4 h-4" />
                    </button>
                    <button v-if="demande.statut_id == 2" @click="deleteDemande(demande)"
                      class="action-btn text-red-600" title="Supprimer">
                      <Trash2 class="w-4 h-4" />
                    </button>
                    <button v-if="demande.statut_id == 3" @click="viewDemandeWithFournisseur(demande)"
                      class="action-btn" title="Générer proforma fournisseur">
                      <FilePlus class="w-4 h-4" />
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
import { ref, onMounted, computed } from 'vue'
import { useRouter } from 'vue-router'
import { Search, Plus, FileText, Pencil, Trash2, Check, X, Filter, FilePlus } from 'lucide-vue-next'
import proformaDemandeAchatService from '@/services/proformaDemandeAchatService'
import entrepriseService from '@/services/entrepriseService' 

const router = useRouter()
const demandes = ref([]) // filtered list
const demandesAll = ref([]) // full dataset for stats
const loading = ref(false)
const error = ref(null)

const viewDemandeWithFournisseur = (demande) => {
  router.push({ name: 'proforma-demande-achat-detail', params: { id: demande.id }, query: { showFournisseur: '1' } })
}  

const searchQuery = ref('')
const filterStatut = ref('')
const filterDateDebut = ref('')
const filterDateFin = ref('')

const filteredDemandes = computed(() => {
  return demandes.value
})

const montantTotalDemandes = computed(() => demandesAll.value.reduce((sum, d) => sum + (Number(d.montant_ttc) || 0), 0))
const valideesCount = computed(() => demandesAll.value.filter(d => d.statut_id == 3).length)
const annuleesCount = computed(() => demandesAll.value.filter(d => d.statut_id == 5).length)

const loadFiltersAndStats = async () => {
  try {
    const response = await proformaDemandeAchatService.getAll()
    demandesAll.value = response.data || []
  } catch (err) {
    console.error('Erreur chargement stats:', err)
  }
}

const loadDemandes = async () => {
  loading.value = true
  error.value = null
  try {
    const params = {}
    if (filterStatut.value) params.statut_id = filterStatut.value
    if (filterDateDebut.value) params.date_debut = filterDateDebut.value
    if (filterDateFin.value) params.date_fin = filterDateFin.value
    if (searchQuery.value) params.search = searchQuery.value

    const response = await proformaDemandeAchatService.getAll(params)
    demandes.value = response.data || []
  } catch (err) {
    error.value = err.response?.data?.message || 'Erreur lors du chargement des demandes'
    console.error('Erreur chargement demandes:', err)
  } finally {
    loading.value = false
  }
}

const createDemande = () => {
  router.push({ name: 'proforma-demande-achat-new' })
}

const viewDemande = (demande) => {
  router.push({ name: 'proforma-demande-achat-detail', params: { id: demande.id } })
}

const editDemande = (demande) => {
  router.push({ name: 'proforma-demande-achat-detail', params: { id: demande.id }, query: { edit: 1 } })
}

const deleteDemande = async (demande) => {
  if (!confirm(`Voulez-vous vraiment supprimer la demande ${demande.numero_da} ?`)) return

  try {
    await proformaDemandeAchatService.delete(demande.id)
    await loadDemandes()
  } catch (error) {
    console.error('Erreur lors de la suppression:', error)
    alert('Erreur lors de la suppression')
  }
}

const validerDemande = async (demande) => {
  if (!confirm(`Voulez-vous vraiment valider la demande ${demande.numero_da} ?`)) return

  try {
    const user = JSON.parse(localStorage.getItem('user') || '{}')
    await proformaDemandeAchatService.valider(demande.id, user.id)
    await loadDemandes()
  } catch (error) {
    console.error('Erreur lors de la validation:', error)
    alert(error.response?.data?.error || 'Erreur lors de la validation')
  }
}

const annulerDemande = async (demande) => {
  if (!confirm(`Voulez-vous vraiment annuler la demande ${demande.numero_da} ?`)) return

  try {
    await proformaDemandeAchatService.annuler(demande.id)
    await loadDemandes()
  } catch (error) {
    console.error('Erreur lors de l\'annulation:', error)
    alert('Erreur lors de l\'annulation')
  }
}



const applyFilters = () => {
  loadDemandes()
}

const resetFilters = () => {
  searchQuery.value = ''
  filterStatut.value = ''
  filterDateDebut.value = ''
  filterDateFin.value = ''
  loadDemandes()
}

// Export PDF (liste)
const exportPdf = () => {
  const base = import.meta.env.VITE_API_BASE_URL || '/api'
  const params = new URLSearchParams()
  if (filterDateDebut.value) params.set('date_debut', filterDateDebut.value)
  if (filterDateFin.value) params.set('date_fin', filterDateFin.value)
  if (filterStatut.value) params.set('statut_id', filterStatut.value)
  if (searchQuery.value) params.set('search', searchQuery.value)
  const user = JSON.parse(localStorage.getItem('user') || '{}')
  if (user?.entreprise_id) params.set('entreprise_id', user.entreprise_id)
  const url = `${base}/proforma-demande-achat/export${params.toString() ? ('?' + params.toString()) : ''}`
  window.open(url, '_blank')
}

const getStatutClass = (statutCode) => {
  const classes = {
    'EN_ATTENTE': 'badge badge-warning',
    'VALIDÉ': 'badge badge-success',
    'ANNULÉ': 'badge badge-danger',
    'EN_COURS': 'badge badge-info'
  }
  return classes[statutCode] || 'badge badge-secondary'
}

const clearFilters = () => {
  searchQuery.value = ''
  filterStatut.value = ''
  filterDateDebut.value = ''
  filterDateFin.value = ''
}

const formatDate = (date) => {
  return new Date(date).toLocaleDateString('fr-FR')
}

const formatCurrency = (val) => {
  if (val === undefined || val === null) return '-'
  return new Intl.NumberFormat('fr-MG', { style: 'currency', currency: 'MGA' }).format(val)
}

onMounted(() => {
  loadFiltersAndStats()
  loadDemandes()
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