<template>
  <div class="page-container">
    <!-- Loading state -->
    <div v-if="loading" class="loading-state">
      <div class="spinner"></div>
      <p class="loading-text">Chargement du personnel...</p>
    </div>

    <!-- Error state -->
    <div v-else-if="error" class="error-state">
      <div class="error-icon">
        <svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
        </svg>
      </div>
      <p class="error-message">{{ error }}</p>
      <button @click="loadPersonnel" class="btn-primary">
        Réessayer
      </button>
    </div>

    <!-- Content -->
    <div v-else class="content-wrapper">
      <!-- Header -->
      <div class="page-header fade-in" style="animation-delay: 0.1s">
        <div>
          <h1 class="page-title">Gestion du Personnel</h1>
          <p class="page-subtitle">Employés et utilisateurs du système</p>
        </div>
        <button @click="openCreateModal" class="btn-primary">
          <Plus class="w-4 h-4" />
          <span>Nouveau Personnel</span>
        </button>
      </div>

      <!-- Stats Cards -->
      <div class="stats-grid fade-in" style="animation-delay: 0.15s">
        <div class="stat-card">
          <div class="stat-content">
            <p class="stat-label">Total employés</p>
            <h3 class="stat-value">{{ personnel.length }}</h3>
          </div>
        </div>
        <div class="stat-card">
          <div class="stat-content">
            <p class="stat-label">Actifs</p>
            <h3 class="stat-value text-green-600">{{ personnelActifs }}</h3>
          </div>
        </div>
        <div class="stat-card">
          <div class="stat-content">
            <p class="stat-label">Administrateurs</p>
            <h3 class="stat-value text-blue-600">{{ admins }}</h3>
          </div>
        </div>
        <div class="stat-card">
          <div class="stat-content">
            <p class="stat-label">Vendeurs</p>
            <h3 class="stat-value text-purple-600">{{ vendeurs }}</h3>
          </div>
        </div>
      </div>

      <!-- Filters -->
      <div class="card fade-in" style="animation-delay: 0.2s">
        <div class="filter-grid">
          <div class="filter-item">
            <label class="label">Rechercher</label>
            <div class="search-box">
              <Search class="w-4 h-4 text-gray-400" />
              <input
                v-model="searchQuery"
                type="text"
                class="search-input"
                placeholder="Nom, prénom, code..."
              />
            </div>
          </div>
          <div class="filter-item">
            <label class="label">Rôle</label>
            <select v-model="selectedRole" class="select">
              <option value="">Tous les rôles</option>
              <option v-for="r in roles" :key="r.id" :value="r.id">{{ r.libelle }}</option>
            </select>
          </div>
                <div class="filter-item">
            <label class="label">Filiale</label>
            <select v-model="selectedEntrepriseId" class="select" @change="onEntrepriseFilterChange">
              <option value="">Toutes les filiales</option>
              <option v-for="e in entreprises" :key="e.id" :value="e.id">{{ e.nom }}</option>
            </select>
          </div>
          <div class="filter-item">
            <label class="label">Site</label>
            <select v-model="selectedSiteId" class="select">
              <option value="">Tous les sites</option>
              <option v-for="s in sites" :key="s.id" :value="s.id">{{ s.nom }}</option>
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
          <h2 class="card-title">Liste du Personnel</h2>
          <span class="text-xs text-gray-500">{{ filteredPersonnel.length }} employé(s)</span>
        </div>
        <div class="table-wrapper">
          <table class="table">
            <thead>
              <tr>
                <th>Code Employé</th>
                <th>Nom & Prénom</th>
                <th>Email</th>
                <th>Téléphone</th>
                <th>Rôle</th>
                <th>Filiale</th>
                <th class="text-center">Statut</th>
                <th class="text-center">Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr v-if="filteredPersonnel.length === 0">
                <td colspan="8" class="text-center py-8 text-gray-500">
                  Aucun personnel trouvé
                </td>
              </tr>
              <tr v-else v-for="p in filteredPersonnel" :key="p.id" class="table-row">
                <td class="font-medium">{{ p.code_employe }}</td>
                <td class="font-medium">{{ (p.nom || '') }} {{ (p.prenom || '') }}</td>
                <td class="text-xs text-gray-600">{{ p.email || '-' }}</td>
                <td class="text-gray-600">{{ p.telephone || '-' }}</td>
                <td>
                  <span :class="getRoleBadgeClass(p.role_libelle)">
                    {{ p.role_libelle || '-' }}
                  </span>
                </td>
                <td class="text-xs text-gray-500">{{ p.entreprise_nom || '-' }}<span v-if="p.site_nom"> — <span class="text-xs text-gray-400">{{ p.site_nom }}</span></span></td>
                <td class="text-center">
                  <span :class="p.est_actif ? 'badge badge-success' : 'badge badge-danger'">
                    {{ p.est_actif ? 'Actif' : 'Inactif' }}
                  </span>
                </td>
                <td>
                  <div class="table-actions">
                    <button @click="editPersonnel(p)" class="action-btn" title="Modifier">
                      <Edit class="w-4 h-4" />
                    </button>
                    <button @click="resetPassword(p)" class="action-btn text-orange-600" title="Réinitialiser mot de passe">
                      <Key class="w-4 h-4" />
                    </button>
                    <button @click="deletePersonnel(p.id)" class="action-btn text-red-600" title="Supprimer">
                      <Trash2 class="w-4 h-4" />
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Pagination -->
        <div class="mt-4 flex items-center justify-between">
          <div class="text-sm text-gray-600">
            Affichage <strong>{{ (page-1)*perPage + 1 }}</strong> - <strong>{{ Math.min(page*perPage, total) }}</strong> sur <strong>{{ total }}</strong>
          </div>
          <div class="flex items-center gap-2">
            <select v-model.number="perPage" @change="changePerPage" class="select">
              <option :value="10">10</option>
              <option :value="25">25</option>
              <option :value="50">50</option>
            </select>
            <button class="btn-secondary" :disabled="page <= 1" @click="prevPage">Précédent</button>
            <button class="btn-secondary" :disabled="page >= totalPages" @click="nextPage">Suivant</button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { Plus, Edit, Trash2, Key, Search } from 'lucide-vue-next'
import personnelService from '@/services/personnelService'
import entrepriseService from '@/services/entrepriseService'
import siteService from '@/services/siteService'
import { useRouter } from 'vue-router'

const router = useRouter()
const searchQuery = ref('')
const selectedRole = ref('')
const selectedEntrepriseId = ref('')
const selectedSiteId = ref('')
const selectedStatut = ref('')
const personnel = ref([])
const entreprises = ref([])
const sites = ref([])
const roles = ref([])
const loading = ref(false)
const error = ref(null)

const filteredPersonnel = computed(() => {
  return personnel.value.filter(p => {
    const matchSearch = !searchQuery.value || 
      p.nom?.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
      p.prenom?.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
      p.code_employe?.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
      p.email?.toLowerCase().includes(searchQuery.value.toLowerCase())
    
    const matchRole = !selectedRole.value || p.role_libelle === selectedRole.value
    const matchFiliale = !selectedEntrepriseId.value || p.entreprise_id == selectedEntrepriseId.value
    const matchSite = !selectedSiteId.value || (p.site_defaut_id && p.site_defaut_id == selectedSiteId.value)
    const matchStatut = !selectedStatut.value || 
      (selectedStatut.value === 'actif' && p.est_actif) ||
      (selectedStatut.value === 'inactif' && !p.est_actif)
    
    return matchSearch && matchRole && matchFiliale && matchSite && matchStatut
  })
})

const personnelActifs = computed(() => {
  return personnel.value.filter(p => p.est_actif).length
})

const admins = computed(() => {
  return personnel.value.filter(p => p.role_libelle === 'ADMIN').length
})

const vendeurs = computed(() => {
  return personnel.value.filter(p => p.role_libelle === 'VENDEUR').length
})

// Pagination state
const page = ref(1)
const perPage = ref(25)
const total = ref(0)
const totalPages = computed(() => Math.max(1, Math.ceil(total.value / perPage.value)))

let searchTimeout = null

const getRoleBadgeClass = (role) => {
  switch(role) {
    case 'ADMIN': return 'badge badge-primary'
    case 'VENDEUR': return 'badge badge-success'
    case 'MAGASINIER': return 'badge badge-info'
    case 'COMPTABLE': return 'badge badge-warning'
    default: return 'badge badge-secondary'
  }
}

const loadPersonnel = async (p = page.value, pp = perPage.value) => {
  loading.value = true
  error.value = null
  try {
    const params = {
      page: p,
      per_page: pp
    }
    if (searchQuery.value) params.search = searchQuery.value
    if (selectedRole.value) params.role_id = selectedRole.value
    if (selectedEntrepriseId.value) params.entreprise_id = selectedEntrepriseId.value
    if (selectedSiteId.value) params.site_id = selectedSiteId.value
    if (selectedStatut.value) params.est_actif = selectedStatut.value === 'actif' ? 'true' : 'false'

    const response = await personnelService.getAll(params)
    personnel.value = response.data.data || []
    total.value = response.data.total || 0
    page.value = response.data.page || p
    perPage.value = response.data.per_page || pp
  } catch (err) {
    error.value = 'Erreur lors du chargement du personnel'
    console.error('Erreur chargement personnel:', err)
  } finally {
    loading.value = false
  }
}

const loadEntreprises = async () => {
  try {
    const resp = await entrepriseService.getAll()
    entreprises.value = resp.data || []
  } catch (err) {
    console.error('Erreur chargement entreprises:', err)
  }
}

const loadSitesForEntreprise = async (entrepriseId) => {
  try {
    if (!entrepriseId) { sites.value = []; return }
    const resp = await siteService.getByEntreprise(entrepriseId)
    sites.value = resp.data || []
  } catch (err) {
    console.error('Erreur chargement sites:', err)
    sites.value = []
  }
}

const onEntrepriseFilterChange = async () => {
  await loadSitesForEntreprise(selectedEntrepriseId.value)
  selectedSiteId.value = ''
  page.value = 1
  await loadPersonnel()
}

const openCreateModal = () => {
  router.push({ name: 'personnel-new' })
}

const editPersonnel = (p) => {
  router.push({ name: 'personnel-detail', params: { id: p.id } })
}

const resetPassword = async (p) => {
  if (!confirm(`Réinitialiser le mot de passe de ${p.nom || ''} ${p.prenom || ''} ?`)) return
  
  try {
    await personnelService.resetPassword(p.id, 'password123')
    alert('Mot de passe réinitialisé avec succès')
  } catch (err) {
    alert('Erreur lors de la réinitialisation du mot de passe')
    console.error('Erreur reset password:', err)
  }
}

const deletePersonnel = async (id) => {
  if (!confirm('Êtes-vous sûr de vouloir supprimer cet employé ?')) return
  
  try {
    await personnelService.delete(id)
    // reload page to reflect server-side data
    await loadPersonnel()
    alert('Personnel supprimé avec succès')
  } catch (err) {
    alert('Erreur lors de la suppression')
    console.error('Erreur suppression personnel:', err)
  }
}

onMounted(() => {
  loadPersonnel()
  loadEntreprises()
  loadRoles()
})

const loadRoles = async () => {
  try {
    const resp = await personnelService.getRoles()
    roles.value = resp.data || []
  } catch (err) {
    console.error('Erreur chargement roles', err)
  }
}

const prevPage = () => {
  if (page.value <= 1) return
  page.value = page.value - 1
  loadPersonnel(page.value, perPage.value)
}

const nextPage = () => {
  if (page.value >= totalPages.value) return
  page.value = page.value + 1
  loadPersonnel(page.value, perPage.value)
}

const changePerPage = () => {
  page.value = 1
  loadPersonnel(page.value, perPage.value)
}

// Watchers for automatic filtering
watch(searchQuery, (val) => {
  clearTimeout(searchTimeout)
  searchTimeout = setTimeout(() => {
    page.value = 1
    loadPersonnel()
  }, 350)
})

watch([selectedRole, selectedSiteId, selectedStatut], () => {
  page.value = 1
  loadPersonnel()
})

watch(selectedEntrepriseId, async () => {
  page.value = 1
  await loadSitesForEntreprise(selectedEntrepriseId.value)
  await loadPersonnel()
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

.select {
  @apply w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-gray-900 focus:border-gray-900 outline-none text-sm bg-white disabled:bg-gray-100 disabled:text-gray-400 disabled:cursor-not-allowed;
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

.badge-primary {
  @apply bg-gray-900 text-white;
}

.badge-success {
  @apply bg-green-100 text-green-700;
}

.badge-info {
  @apply bg-blue-100 text-blue-700;
}

.badge-warning {
  @apply bg-orange-100 text-orange-700;
}

.badge-secondary {
  @apply bg-gray-100 text-gray-700;
}

.badge-danger {
  @apply bg-red-100 text-red-700;
}
</style>