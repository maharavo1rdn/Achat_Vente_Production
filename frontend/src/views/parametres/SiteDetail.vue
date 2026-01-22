<template>
  <div class="page-container">
    <!-- Loading state -->
    <div v-if="loadingEntreprise" class="loading-state">
      <div class="spinner"></div>
      <p class="loading-text">Chargement de la fiche...</p>
    </div>

    <!-- Error state -->
    <div v-else-if="errorEntreprise" class="error-state">
      <div class="error-icon">
        <svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
            d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z">
          </path>
        </svg>
      </div>
      <p class="error-message">{{ errorEntreprise }}</p>
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
            <h1 class="page-title">Fiche Entreprise</h1>
            <p class="page-subtitle">{{ entreprise?.nom }}</p>
          </div>
        </div>
        <div class="flex gap-3">
          <button @click="openCreateSiteModal" class="btn-primary">
            <Plus class="w-4 h-4" />
            <span>Nouveau Site</span>
          </button>
        </div>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 fade-in" style="animation-delay: 0.2s">

        <!-- Info Card (Left Column) -->
        <div class="card lg:col-span-1 h-fit">
          <div class="card-header-simple">
            <h2 class="card-title">Informations Générales</h2>
            <button class="action-btn" title="Modifier">
              <Edit class="w-4 h-4" />
            </button>
          </div>

          <div class="space-y-6">
            <!-- Header Info -->
            <div class="flex flex-col items-center py-4 border-b border-gray-100">
              <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mb-3 text-gray-500">
                <Building class="w-8 h-8" />
              </div>
              <h3 class="text-lg font-bold text-gray-900 text-center">{{ entreprise.nom }}</h3>
              <div class="mt-2 flex gap-2">
                <span :class="getTypeBadgeClass(entreprise.type_entreprise)">
                  {{ entreprise.type_entreprise }}
                </span>
                <span :class="entreprise.est_actif ? 'badge badge-success' : 'badge badge-danger'">
                  {{ entreprise.est_actif ? 'Actif' : 'Inactif' }}
                </span>
              </div>
            </div>

            <!-- Details List -->
            <div class="space-y-4">
              <div class="info-item">
                <span class="info-label">Matricule Fiscal</span>
                <span class="info-value">{{ entreprise.matricule_fiscal || '-' }}</span>
              </div>

              <div class="info-item">
                <span class="info-label">Email</span>
                <div class="flex items-center gap-2 text-gray-900">
                  <Mail class="w-4 h-4 text-gray-400" />
                  <span class="text-sm">{{ entreprise.email || '-' }}</span>
                </div>
              </div>

              <div class="info-item">
                <span class="info-label">Téléphone</span>
                <div class="flex items-center gap-2 text-gray-900">
                  <Phone class="w-4 h-4 text-gray-400" />
                  <span class="text-sm">{{ entreprise.telephone || '-' }}</span>
                </div>
              </div>

              <div class="info-item">
                <span class="info-label">Adresse Siège</span>
                <div class="flex items-start gap-2 text-gray-900">
                  <MapPin class="w-4 h-4 text-gray-400 mt-0.5" />
                  <span class="text-sm">{{ entreprise.adresse || '-' }}</span>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Sites List (Right Column - Wider) -->
        <div class="card lg:col-span-2">
          <div class="card-header-simple">
            <h2 class="card-title">Sites rattachés</h2>
            <span class="text-xs text-gray-500">{{ sites.length }} site(s)</span>
          </div>

          <!-- Loading Sites -->
          <div v-if="loadingSites" class="py-12 flex justify-center">
            <div class="spinner w-8 h-8"></div>
          </div>

          <!-- Table -->
          <div v-else class="table-wrapper">
            <table class="table">
              <thead>
                <tr>
                  <th>Nom du site</th>
                  <th>Coordonnées</th>
                  <th class="text-center">Statut</th>
                  <th class="text-right">Actions</th>
                </tr>
              </thead>
              <tbody>
                <tr v-if="sites.length === 0">
                  <td colspan="4" class="text-center py-8 text-gray-500">
                    Aucun site enregistré pour cette entreprise
                  </td>
                </tr>
                <tr v-else v-for="site in sites" :key="site.id" class="table-row">
                  <td>
                    <div class="font-medium text-gray-900">{{ site.nom }}</div>
                    <div class="text-xs text-gray-500 flex items-center gap-1 mt-0.5">
                      <MapPin class="w-3 h-3" /> {{ site.adresse || 'Aucune adresse' }}
                    </div>
                  </td>
                  <td>
                    <div class="space-y-1">
                      <div v-if="site.email" class="text-xs flex items-center gap-1 text-gray-600">
                        <Mail class="w-3 h-3" /> {{ site.email }}
                      </div>
                      <div v-if="site.telephone" class="text-xs flex items-center gap-1 text-gray-600">
                        <Phone class="w-3 h-3" /> {{ site.telephone }}
                      </div>
                      <span v-if="!site.email && !site.telephone" class="text-xs text-gray-400">-</span>
                    </div>
                  </td>
                  <td class="text-center">
                    <span :class="site.est_actif ? 'badge badge-success' : 'badge badge-danger'">
                      {{ site.est_actif ? 'Actif' : 'Inactif' }}
                    </span>
                  </td>
                  <td>
                    <div class="flex justify-end gap-2">
                      <router-link :to="{ name: 'site-detail', params: { id: site.id } }"
                        class="action-btn text-blue-600" title="Voir détails">
                        <ExternalLink class="w-4 h-4" />
                      </router-link>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>

    <!-- Create Site Modal -->
    <Modal v-model:show="showCreateSiteModal" title="Nouveau Site" size="md" :closeOnOverlay="true">
      <template #body>
        <div class="space-y-4">
          <div>
            <label class="label">Nom du site *</label>
            <input v-model="newSite.nom" class="input" placeholder="Ex: Siège social, Entrepôt Nord..." />
          </div>

          <div>
            <label class="label">Adresse</label>
            <input v-model="newSite.adresse" class="input" placeholder="Adresse complète" />
          </div>

          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="label">Téléphone</label>
              <input v-model="newSite.telephone" class="input" placeholder="+261..." />
            </div>
            <div>
              <label class="label">Email</label>
              <input v-model="newSite.email" type="email" class="input" placeholder="site@exemple.com" />
            </div>
          </div>

          <p v-if="createSiteError" class="text-sm text-red-600 mt-2 flex items-center gap-2">
            <span class="w-1.5 h-1.5 bg-red-600 rounded-full"></span>
            {{ createSiteError }}
          </p>
        </div>
      </template>
      <template #footer>
        <button class="btn-secondary" @click="showCreateSiteModal = false">Annuler</button>
        <button class="btn-primary" @click="createSite" :disabled="createSiteLoading">
          {{ createSiteLoading ? 'Enregistrement...' : 'Créer le site' }}
        </button>
      </template>
    </Modal>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import {
  ArrowLeft, Plus, Edit, Building, Mail, Phone, MapPin, ExternalLink
} from 'lucide-vue-next'
import Modal from '@/components/elements/Modal.vue'
import entrepriseService from '@/services/entrepriseService'
import siteService from '@/services/siteService'

const route = useRoute()
const entrepriseId = Number(route.params.id)

const entreprise = ref(null)
const sites = ref([])

const loadingEntreprise = ref(true)
const errorEntreprise = ref(null)
const loadingSites = ref(false)

const showCreateSiteModal = ref(false)
const createSiteLoading = ref(false)
const createSiteError = ref(null)
const newSite = ref({
  nom: '',
  adresse: '',
  telephone: '',
  email: '',
  entreprise_id: entrepriseId,
  est_actif: true
})

const getTypeBadgeClass = (type) => {
  switch (type) {
    case 'CLIENT': return 'badge badge-success'
    case 'FOURNISSEUR': return 'badge badge-warning'
    case 'INTERNE': return 'badge badge-primary'
    case 'PARTENAIRE': return 'badge badge-info'
    default: return 'badge badge-secondary'
  }
}

const loadEntreprise = async () => {
  loadingEntreprise.value = true
  errorEntreprise.value = null
  try {
    const resp = await entrepriseService.getById(entrepriseId)
    entreprise.value = resp.data
  } catch (err) {
    errorEntreprise.value = "Impossible de charger les informations de l'entreprise."
    console.error(err)
  } finally {
    loadingEntreprise.value = false
  }
}

const loadSites = async () => {
  loadingSites.value = true
  try {
    const resp = await siteService.getByEntreprise(entrepriseId)
    sites.value = resp.data || []
  } catch (err) {
    console.error('Erreur chargement sites:', err)
    sites.value = []
  } finally {
    loadingSites.value = false
  }
}

const openCreateSiteModal = () => {
  createSiteError.value = null
  newSite.value = {
    nom: '',
    adresse: '',
    telephone: '',
    email: '',
    entreprise_id: entrepriseId,
    est_actif: true
  }
  showCreateSiteModal.value = true
}

const createSite = async () => {
  createSiteError.value = null
  if (!newSite.value.nom) {
    createSiteError.value = 'Le nom du site est obligatoire'
    return
  }

  createSiteLoading.value = true
  try {
    await siteService.create(newSite.value)
    showCreateSiteModal.value = false
    await loadSites()
  } catch (err) {
    console.error('Erreur création site:', err)
    createSiteError.value = 'Une erreur est survenue lors de la création.'
  } finally {
    createSiteLoading.value = false
  }
}

onMounted(async () => {
  await loadEntreprise()
  if (!errorEntreprise.value) {
    await loadSites()
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