<template>
  <div class="page-container">
    <!-- Loading state -->
    <div v-if="loadingSite" class="loading-state">
      <div class="spinner"></div>
      <p class="loading-text">Chargement de la fiche...</p>
    </div>

    <!-- Error state -->
    <div v-else-if="errorSite" class="error-state">
      <div class="error-icon">
        <svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
            d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z">
          </path>
        </svg>
      </div>
      <p class="error-message">{{ errorSite }}</p>
      <router-link to="/entreprises" class="btn-primary">
        Retour à la liste
      </router-link>
    </div>

    <!-- Content -->
    <div v-else class="content-wrapper">
      <!-- Header -->
      <div class="page-header fade-in" style="animation-delay: 0.1s">
        <div class="flex items-center gap-4">
          <router-link to="/entreprises" class="btn-icon-back" title="Retour">
            <ArrowLeft class="w-5 h-5" />
          </router-link>
          <div>
            <h1 class="page-title">Fiche Site</h1>
            <p class="page-subtitle">{{ site?.nom }}</p>
          </div>
        </div>
        <div class="flex gap-3 items-center">
          <!-- Compact-only layout (no selector) -->
        </div>
      </div>

      <div class="grid grid-cols-1 gap-6 fade-in" style="animation-delay: 0.2s">

        <!-- Info Card (Top Fiche) -->
        <div class="card flex flex-col justify-between p-4">
          <div class="card-header-simple">
            <h2 class="card-title">Informations Générales</h2>
            <div>
              <button v-if="!editing" @click="startEdit" class="action-btn" title="Modifier">
                <Edit class="w-4 h-4" />
              </button>

              <div v-else class="flex gap-2">
                <button @click="saveEdit" class="btn-primary">Enregistrer</button>
                <button @click="cancelEdit" class="btn-secondary">Annuler</button>
              </div>
            </div>
          </div>

          <div class="space-y-6">
            <!-- Header Info -->
            <div class="flex flex-col items-center py-4 border-b border-gray-100">
              <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mb-3 text-gray-500">
                <Building class="w-8 h-8" />
              </div>
              <h3 class="text-lg font-bold text-gray-900 text-center">{{ site.nom }}</h3>
              <div class="mt-2 flex gap-2">
                <span :class="site.est_actif ? 'badge badge-success' : 'badge badge-danger'">
                  {{ site.est_actif ? 'Actif' : 'Inactif' }}
                </span>
                <span class="text-sm text-gray-500">{{ site.entreprise_nom ? 'Rattaché à ' + site.entreprise_nom : '' }}</span>
              </div>
            </div>
            <div class="space-y-4">
              <div class="info-item">
                <span class="info-label">Adresse</span>
                <div>
                  <span v-if="!editing" class="info-value">{{ site.adresse || '-' }}</span>
                  <input v-else v-model="editedSite.adresse" class="input" />
                </div>
              </div>

              <div class="info-item">
                <span class="info-label">Email</span>
                <div class="flex items-center gap-2 text-gray-900">
                  <Mail class="w-4 h-4 text-gray-400" />
                  <span v-if="!editing" class="text-sm">{{ site.email || '-' }}</span>
                  <input v-else v-model="editedSite.email" class="input" type="email" />
                </div>
              </div>

              <div class="info-item">
                <span class="info-label">Téléphone</span>
                <div class="flex items-center gap-2 text-gray-900">
                  <Phone class="w-4 h-4 text-gray-400" />
                  <span v-if="!editing" class="text-sm">{{ site.telephone || '-' }}</span>
                  <input v-else v-model="editedSite.telephone" class="input" />
                </div>
              </div>

              <div class="info-item">
                <span class="info-label">Notes</span>
                <div class="flex items-start gap-2 text-gray-900">
                  <span v-if="!editing" class="text-sm">{{ site.notes || '-' }}</span>
                  <textarea v-else v-model="editedSite.notes" class="input h-24"></textarea>
                </div>
              </div> 
            </div>
          </div>
        </div>


      </div>

      <!-- Depots List -->
      <div class="card mt-4">
        <div class="card-header-simple">
          <h2 class="card-title">Dépôts rattachés</h2>
          <div class="flex items-center gap-3">
            <span class="text-xs text-gray-500">{{ depots.length }} dépôt(s)</span>
            <button @click="openCreateDepotModal" class="btn-primary text-sm">Ajouter dépôt</button>
          </div>
        </div>

        <div v-if="loadingDepots" class="py-6 flex justify-center">
          <div class="spinner w-8 h-8"></div>
        </div>

        <div v-else class="table-wrapper">
          <table class="table">
            <thead>
              <tr>
                <th>Nom</th>
                <th>Adresse</th>
                <th class="text-center">Statut</th>
                <th class="text-right">Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr v-if="depots.length === 0">
                <td colspan="4" class="text-center py-8 text-gray-500">Aucun dépôt pour ce site</td>
              </tr>
              <tr v-else v-for="d in depots" :key="d.id" class="table-row">
                <td class="font-medium">{{ d.nom }}</td>
                <td class="text-sm text-gray-600">{{ d.adresse || '-' }}</td>
                <td class="text-center"><span :class="d.est_actif ? 'badge badge-success' : 'badge badge-danger'">{{ d.est_actif ? 'Actif' : 'Inactif' }}</span></td>
                <td class="text-right"><button class="action-btn text-blue-600" @click="openDepotDetails(d)">Voir</button></td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Depot Details Modal -->
      <Modal v-model:show="showDepotModal" title="Fiche Dépôt" size="md" :closeOnOverlay="true">
        <template #body>
          <div v-if="selectedDepot">
            <h3 class="text-lg font-bold">{{ selectedDepot.nom }}</h3>
            <div class="text-sm text-gray-600">{{ selectedDepot.adresse || '-' }}</div>
            <div class="mt-3 text-sm text-gray-700"><strong>Statut:</strong> {{ selectedDepot.est_actif ? 'Actif' : 'Inactif' }}</div>
          </div>
        </template>
        <template #footer>
          <button class="btn-secondary" @click="closeDepotModal">Fermer</button>
        </template>
      </Modal>

      <!-- Create Depot Modal -->
      <Modal v-model:show="showCreateDepotModal" title="Nouveau Dépôt" size="md" :closeOnOverlay="true">
        <template #body>
          <div class="space-y-4">
            <div>
              <label class="label">Nom du dépôt *</label>
              <input v-model="newDepot.nom" class="input" placeholder="Nom du dépôt" />
            </div>

            <div>
              <label class="label">Adresse</label>
              <input v-model="newDepot.adresse" class="input" placeholder="Adresse" />
            </div>

            <div class="flex items-center gap-3">
              <label class="label inline-flex items-center gap-2">
                <input type="checkbox" v-model="newDepot.est_actif" />
                <span class="text-sm">Actif</span>
              </label>
            </div>

            <p v-if="createDepotError" class="text-sm text-red-600">{{ createDepotError }}</p>
          </div>
        </template>
        <template #footer>
          <button class="btn-secondary" @click="showCreateDepotModal = false">Annuler</button>
          <button class="btn-primary" @click="createDepot" :disabled="createDepotLoading">{{ createDepotLoading ? 'Enregistrement...' : 'Créer' }}</button>
        </template>
      </Modal>

    </div>


  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import {
  ArrowLeft, Edit, Building, Mail, Phone
} from 'lucide-vue-next'
import Modal from '@/components/elements/Modal.vue'
import siteService from '@/services/siteService'
import depotService from '@/services/depotService'

const route = useRoute()
const router = useRouter()
const siteId = Number(route.params.id)

const site = ref(null)

const loadingSite = ref(true)
const errorSite = ref(null)

const editing = ref(false)
const editedSite = ref(null)

// Depots state
const depots = ref([])
const loadingDepots = ref(false)
const showDepotModal = ref(false)
const selectedDepot = ref(null)

const loadDepots = async () => {
  loadingDepots.value = true
  try {
    const resp = await depotService.getBySite(siteId)
    depots.value = resp.data || []
  } catch (err) {
    console.error('Erreur chargement dépôts:', err)
    depots.value = []
  } finally {
    loadingDepots.value = false
  }
}

const openDepotDetails = (depot) => {
  selectedDepot.value = depot
  showDepotModal.value = true
}

const closeDepotModal = () => {
  selectedDepot.value = null
  showDepotModal.value = false
}

const getTypeBadgeClass = (type) => {
  // Keep for potential reuse
  switch (type) {
    case 'CLIENT': return 'badge badge-success'
    case 'FOURNISSEUR': return 'badge badge-warning'
    case 'INTERNE': return 'badge badge-primary'
    case 'PARTENAIRE': return 'badge badge-info'
    default: return 'badge badge-secondary'
  }
}

const loadSite = async () => {
  loadingSite.value = true
  errorSite.value = null
  try {
    const resp = await siteService.getById(siteId)
    site.value = resp.data
    if (editing.value) editedSite.value = { ...site.value }
  } catch (err) {
    errorSite.value = "Impossible de charger les informations du site."
    console.error(err)
  } finally {
    loadingSite.value = false
  }
}

const startEdit = () => {
  editedSite.value = { ...site.value }
  editing.value = true
}

const cancelEdit = () => {
  editedSite.value = null
  editing.value = false
}

// Create depot state and actions
const showCreateDepotModal = ref(false)
const createDepotLoading = ref(false)
const createDepotError = ref(null)
const newDepot = ref({
  nom: '',
  adresse: '',
  est_actif: true
})

const openCreateDepotModal = () => {
  createDepotError.value = null
  newDepot.value = { nom: '', adresse: '', est_actif: true }
  showCreateDepotModal.value = true
}

const createDepot = async () => {
  createDepotError.value = null
  if (!newDepot.value.nom) {
    createDepotError.value = 'Le nom du dépôt est obligatoire'
    return
  }
  createDepotLoading.value = true
  try {
    await depotService.create({ ...newDepot.value, site_id: siteId })
    showCreateDepotModal.value = false
    await loadDepots()
    alert('Dépôt créé avec succès')
  } catch (err) {
    console.error('Erreur création dépôt:', err)
    createDepotError.value = 'Erreur lors de la création du dépôt'
  } finally {
    createDepotLoading.value = false
  }
}

const saveEdit = async () => {
  try {
    await siteService.update(siteId, editedSite.value)
    await loadSite()
    editing.value = false
    alert('Site mis à jour avec succès')
  } catch (err) {
    console.error('Erreur mise à jour site:', err)
    alert('Erreur lors de la mise à jour du site')
  }
}







onMounted(async () => {
  await loadSite()
  await loadDepots()
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

.page-header {
  @apply flex items-center justify-between mb-6;
}

.page-title {
  @apply text-2xl font-bold text-gray-900;
}

.page-subtitle {
  @apply text-sm text-gray-500 mt-1;
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

.card {
  @apply bg-white rounded-lg border border-gray-200 p-5 shadow-sm;
}

.card-header-simple {
  @apply mb-4 pb-3 border-b border-gray-200 flex items-center justify-between;
}

.card-title {
  @apply text-base font-semibold text-gray-900;
}

.loading-state,
.error-state {
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

.error-icon {
  @apply text-red-600 mb-4;
}

.error-message {
  @apply text-sm text-red-600 mb-4;
}

.label {
  @apply block text-xs font-medium text-gray-700 mb-1.5;
}

/* FIX: Input Background forced to White */
.input {
  @apply w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-gray-900 focus:border-gray-900 outline-none text-sm bg-white text-gray-900 placeholder:text-gray-400 transition-all;
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
  @apply px-6 py-4 text-sm text-gray-900 align-middle;
}

.table tbody tr {
  @apply border-b border-gray-100;
}

.table-row {
  @apply hover:bg-gray-50 transition-colors;
}

.btn-primary {
  @apply flex items-center gap-2 px-4 py-2 bg-gray-900 text-white rounded-lg hover:bg-gray-800 transition-colors text-sm font-medium disabled:opacity-50 disabled:cursor-not-allowed;
}

.btn-secondary {
  @apply px-4 py-2 bg-white border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors text-sm font-medium;
}

.btn-icon-back {
  @apply p-2 rounded-lg text-gray-500 hover:bg-gray-100 hover:text-gray-900 transition-all border border-transparent hover:border-gray-200;
}

.action-btn {
  @apply p-2 rounded-lg text-gray-400 hover:bg-gray-100 hover:text-gray-900 transition-all;
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

.info-item {
  @apply flex flex-col gap-1;
}

.info-label {
  @apply text-xs font-medium text-gray-500 uppercase tracking-wide;
}

.info-value {
  @apply text-sm text-gray-900 font-medium;
}
</style>