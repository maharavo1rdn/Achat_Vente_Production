<template>
  <div class="page-container">
    <!-- Loading state -->
    <div v-if="loading" class="loading-state">
      <div class="spinner"></div>
      <p class="loading-text">Chargement des entreprises...</p>
    </div>

    <!-- Error state -->
    <div v-else-if="error" class="error-state">
      <div class="error-icon">
        <svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
        </svg>
      </div>
      <p class="error-message">{{ error }}</p>
      <button @click="loadEntreprises" class="btn-primary">
        Réessayer
      </button>
    </div>

    <!-- Content -->
    <div v-else class="content-wrapper">
      <!-- Header -->
      <div class="page-header fade-in" style="animation-delay: 0.1s">
        <div>
          <h1 class="page-title">Gestion des Entreprises</h1>
          <p class="page-subtitle">Clients, fournisseurs et filiales</p>
        </div>
        <button @click="openCreateModal" class="btn-primary">
          <Plus class="w-4 h-4" />
          <span>Nouvelle Entreprise</span>
        </button>
      </div>

      <!-- Stats Cards -->
      <div class="stats-grid fade-in" style="animation-delay: 0.15s">
        <div class="stat-card">
          <div class="stat-content">
            <p class="stat-label">Total entreprises</p>
            <h3 class="stat-value">{{ entreprises.length }}</h3>
          </div>
        </div>
        <div class="stat-card">
          <div class="stat-content">
            <p class="stat-label">Clients</p>
            <h3 class="stat-value text-green-600">{{ clientsCount }}</h3>
          </div>
        </div>
        <div class="stat-card">
          <div class="stat-content">
            <p class="stat-label">Fournisseurs</p>
            <h3 class="stat-value text-orange-600">{{ fournisseursCount }}</h3>
          </div>
        </div>
        <div class="stat-card">
          <div class="stat-content">
            <p class="stat-label">Filiales internes</p>
            <h3 class="stat-value text-blue-600">{{ internesCount }}</h3>
          </div>
        </div>
      </div>

      <!-- Filters -->
      <div class="card fade-in" style="animation-delay: 0.2s">
        <div class="filter-grid-3">
          <div class="filter-item">
            <label class="label">Rechercher</label>
            <div class="search-box">
              <Search class="w-4 h-4 text-gray-400" />
              <input
                v-model="searchQuery"
                type="text"
                class="search-input"
                placeholder="Nom, matricule..."
              />
            </div>
          </div>
          <div class="filter-item">
            <label class="label">Type</label>
            <select v-model="selectedType" class="select">
              <option value="">Tous les types</option>
              <option value="CLIENT">Client</option>
              <option value="FOURNISSEUR">Fournisseur</option>
              <option value="INTERNE">Interne (Filiale)</option>
              <option value="PARTENAIRE">Partenaire</option>
            </select>
          </div>
          <div class="filter-item">
            <label class="label">Statut</label>
            <select v-model="selectedStatut" class="select">
              <option value="">Tous</option>
              <option value="actif">Actif</option>
              <option value="inactif">Inactif</option>
            </select>
          </div>
        </div>
      </div>

      <!-- Table -->
      <div class="card fade-in" style="animation-delay: 0.25s">
        <div class="card-header-simple">
          <h2 class="card-title">Liste des Entreprises</h2>
          <span class="text-xs text-gray-500">{{ filteredEntreprises.length }} entreprise(s)</span>
        </div>
        <div class="table-wrapper">
          <table class="table">
            <thead>
              <tr>
                <th>Nom</th>
                <th>Matricule Fiscal</th>
                <th>Type</th>
                <th>Email</th>
                <th>Téléphone</th>
                <th>Adresse</th>
                <th class="text-center">Statut</th>
                <th class="text-center">Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr v-if="filteredEntreprises.length === 0">
                <td colspan="8" class="text-center py-8 text-gray-500">
                  Aucune entreprise trouvée
                </td>
              </tr>
              <tr v-else v-for="entreprise in filteredEntreprises" :key="entreprise.id" class="table-row">
                <td class="font-medium">{{ entreprise.nom }}</td>
                <td class="text-gray-600">{{ entreprise.matricule_fiscal || '-' }}</td>
                <td>
                  <span :class="getTypeBadgeClass(entreprise.type_entreprise)">
                    {{ entreprise.type_entreprise }}
                  </span>
                </td>
                <td class="text-xs text-gray-600">{{ entreprise.email || '-' }}</td>
                <td class="text-gray-600">{{ entreprise.telephone || '-' }}</td>
                <td class="text-xs text-gray-500">{{ entreprise.adresse || '-' }}</td>
                <td class="text-center">
                  <span :class="entreprise.est_actif ? 'badge badge-success' : 'badge badge-danger'">
                    {{ entreprise.est_actif ? 'Actif' : 'Inactif' }}
                  </span>
                </td>
                <td>
                  <div class="table-actions">
                    <button @click="openEntrepriseDetails(entreprise)" class="action-btn" title="Voir">
                      <Eye class="w-4 h-4" />
                    </button>
                    <button @click="editEntreprise(entreprise)" class="action-btn" title="Modifier">
                      <Edit class="w-4 h-4" />
                    </button>
                    <button @click="deleteEntreprise(entreprise.id)" class="action-btn text-red-600" title="Supprimer">
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

    <!-- Modal: Nouvelle Entreprise -->
    <Modal v-model:show="showCreateEntrepriseModal" title="Nouvelle Entreprise" size="md" :closeOnOverlay="true">
      <template #body>
        <div class="space-y-4">
          <div>
            <label class="label">Nom de l'entreprise *</label>
            <input v-model="newEntreprise.nom" class="input" placeholder="Nom complet" />
          </div>

          <div>
            <label class="label">Type d'entreprise *</label>
            <select v-model="newEntreprise.type_entreprise" class="select">
              <option value="CLIENT">Client</option>
              <option value="FOURNISSEUR">Fournisseur</option>
              <option value="INTERNE">Interne (Filiale)</option>
              <option value="PARTENAIRE">Partenaire</option>
            </select>
          </div>

          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="label">Matricule Fiscal</label>
              <input v-model="newEntreprise.matricule_fiscal" class="input" placeholder="Ex: 123456789" />
            </div>
            <div>
              <label class="label">Téléphone</label>
              <input v-model="newEntreprise.telephone" class="input" placeholder="Ex: +261 34 12 345 67" />
            </div>
          </div>

          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="label">Email</label>
              <input v-model="newEntreprise.email" type="email" class="input" placeholder="email@exemple.com" />
            </div>
            <div>
              <label class="label">Adresse</label>
              <input v-model="newEntreprise.adresse" class="input" placeholder="Adresse complète" />
            </div>
          </div>

          <p v-if="createEntrepriseError" class="text-sm text-red-600">{{ createEntrepriseError }}</p>
        </div>
      </template>
      <template #footer>
        <button class="btn-secondary" @click="showCreateEntrepriseModal = false">Annuler</button>
        <button class="btn-primary" @click="createEntreprise" :disabled="createEntrepriseLoading">
          {{ createEntrepriseLoading ? 'Enregistrement...' : 'Créer' }}
        </button>
      </template>
    </Modal>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { Plus, Edit, Trash2, Eye, Search } from 'lucide-vue-next'
import Modal from '@/components/elements/Modal.vue'
import entrepriseService from '@/services/entrepriseService'

const searchQuery = ref('')
const selectedType = ref('')
const selectedStatut = ref('')
const entreprises = ref([])
const loading = ref(false)
const error = ref(null)

const filteredEntreprises = computed(() => {
  return entreprises.value.filter(e => {
    const matchSearch = !searchQuery.value || 
      e.nom?.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
      (e.matricule_fiscal && e.matricule_fiscal.toLowerCase().includes(searchQuery.value.toLowerCase()))
    
    const matchType = !selectedType.value || e.type_entreprise === selectedType.value
    const matchStatut = !selectedStatut.value || 
      (selectedStatut.value === 'actif' && e.est_actif) ||
      (selectedStatut.value === 'inactif' && !e.est_actif)
    
    return matchSearch && matchType && matchStatut
  })
})

const clientsCount = computed(() => {
  return entreprises.value.filter(e => e.type_entreprise === 'CLIENT').length
})

const fournisseursCount = computed(() => {
  return entreprises.value.filter(e => e.type_entreprise === 'FOURNISSEUR').length
})

const internesCount = computed(() => {
  return entreprises.value.filter(e => e.type_entreprise === 'INTERNE').length
})

const getTypeBadgeClass = (type) => {
  switch(type) {
    case 'CLIENT': return 'badge badge-success'
    case 'FOURNISSEUR': return 'badge badge-warning'
    case 'INTERNE': return 'badge badge-primary'
    case 'PARTENAIRE': return 'badge badge-info'
    default: return 'badge badge-secondary'
  }
}

const router = useRouter()

const loadEntreprises = async () => {
  loading.value = true
  error.value = null
  try {
    const response = await entrepriseService.getAll()
    entreprises.value = response.data || []
  } catch (err) {
    error.value = 'Erreur lors du chargement des entreprises'
    console.error('Erreur chargement entreprises:', err)
  } finally {
    loading.value = false
  }
}

// Create Entreprise Modal
const showCreateEntrepriseModal = ref(false)
const createEntrepriseLoading = ref(false)
const createEntrepriseError = ref(null)
const newEntreprise = ref({
  nom: '',
  type_entreprise: 'CLIENT',
  matricule_fiscal: '',
  email: '',
  telephone: '',
  adresse: '',
  est_actif: true
})

const openCreateModal = () => {
  createEntrepriseError.value = null
  newEntreprise.value = {
    nom: '',
    type_entreprise: 'CLIENT',
    matricule_fiscal: '',
    email: '',
    telephone: '',
    adresse: '',
    est_actif: true
  }
  showCreateEntrepriseModal.value = true
}

const createEntreprise = async () => {
  createEntrepriseError.value = null
  if (!newEntreprise.value.nom) {
    createEntrepriseError.value = 'Le nom est requis'
    return
  }
  createEntrepriseLoading.value = true
  try {
    await entrepriseService.create(newEntreprise.value)
    showCreateEntrepriseModal.value = false
    await loadEntreprises()
    alert('Entreprise créée avec succès')
  } catch (err) {
    console.error('Erreur création entreprise:', err)
    createEntrepriseError.value = 'Erreur lors de la création'
  } finally {
    createEntrepriseLoading.value = false
  }
}

const openEntrepriseDetails = (entreprise) => {
  router.push({ name: 'entreprise-detail', params: { id: entreprise.id } })
}

const editEntreprise = (entreprise) => {
  // Navigate to detail page and open in edit mode
  router.push({ name: 'entreprise-detail', params: { id: entreprise.id }, query: { edit: '1' } })
}

const deleteEntreprise = async (id) => {
  if (!confirm('Êtes-vous sûr de vouloir supprimer cette entreprise ?')) return
  
  try {
    await entrepriseService.delete(id)
    entreprises.value = entreprises.value.filter(e => e.id !== id)
    alert('Entreprise supprimée avec succès')
  } catch (err) {
    alert('Erreur lors de la suppression')
    console.error('Erreur suppression entreprise:', err)
  }
}

onMounted(() => {
  loadEntreprises()
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

.filter-grid-3 {
  @apply grid grid-cols-1 md:grid-cols-3 gap-4;
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

.badge-warning {
  @apply bg-orange-100 text-orange-700;
}

.badge-primary {
  @apply bg-gray-900 text-white;
}

.badge-info {
  @apply bg-blue-100 text-blue-700;
}

.badge-secondary {
  @apply bg-gray-100 text-gray-700;
}

.badge-danger {
  @apply bg-red-100 text-red-700;
}
</style>