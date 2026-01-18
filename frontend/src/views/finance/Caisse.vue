<template>
  <div class="page-container">
    <!-- Loading state -->
    <div v-if="loading" class="loading-state">
      <div class="spinner"></div>
      <p class="loading-text">Chargement des caisses...</p>
    </div>

    <!-- Error state -->
    <div v-else-if="error" class="error-state">
      <div class="error-icon">
        <svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
        </svg>
      </div>
      <p class="error-message">{{ error }}</p>
      <button @click="loadCaisseData" class="btn-primary">
        Réessayer
      </button>
    </div>

    <!-- Content -->
    <div v-else class="content-wrapper">
      <!-- Header -->
      <div class="page-header fade-in" style="animation-delay: 0.1s">
        <div>
          <h1 class="page-title">Gestion de Caisse</h1>
          <p class="page-subtitle">Suivi des mouvements de trésorerie</p>
        </div>
        <div class="header-actions">
          <button @click="openEntreeModal" class="btn-success">
            <ArrowDownCircle class="w-4 h-4" />
            <span>Nouvelle Entrée</span>
          </button>
          <button @click="openSortieModal" class="btn-danger">
            <ArrowUpCircle class="w-4 h-4" />
            <span>Nouvelle Sortie</span>
          </button>
        </div>
      </div>

      <!-- Soldes des caisses -->
      <div class="caisses-grid fade-in" style="animation-delay: 0.15s">
        <div v-for="caisse in caisses" :key="caisse.id" class="caisse-card">
          <div class="caisse-header">
            <div class="caisse-info">
              <h3 class="caisse-title">{{ caisse.libelle }}</h3>
              <p class="caisse-subtitle">{{ caisse.entreprise }}</p>
            </div>
            <div class="caisse-icon">
              <Wallet class="w-5 h-5 text-green-600" />
            </div>
          </div>
          <div class="caisse-solde">
            {{ formatCurrency(caisse.solde_actuel) }}
          </div>
          <div class="caisse-footer">
            <button @click="viewCaisseDetails(caisse)" class="caisse-link">
              Voir détails
            </button>
          </div>
        </div>
      </div>

      <!-- Stats Cards -->
      <div class="stats-grid fade-in" style="animation-delay: 0.2s">
        <div class="stat-card">
          <div class="stat-content">
            <p class="stat-label">Total mouvements</p>
            <h3 class="stat-value">{{ mouvements.length }}</h3>
          </div>
        </div>
        <div class="stat-card">
          <div class="stat-content">
            <p class="stat-label">Entrées</p>
            <h3 class="stat-value text-green-600">{{ totalEntrees }}</h3>
          </div>
        </div>
        <div class="stat-card">
          <div class="stat-content">
            <p class="stat-label">Sorties</p>
            <h3 class="stat-value text-red-600">{{ totalSorties }}</h3>
          </div>
        </div>
        <div class="stat-card">
          <div class="stat-content">
            <p class="stat-label">Solde total</p>
            <h3 class="stat-value text-blue-600">{{ formatCurrency(soldeTotal) }}</h3>
          </div>
        </div>
      </div>

      <!-- Filters -->
      <div class="card fade-in" style="animation-delay: 0.25s">
        <div class="filter-grid">
          <div class="filter-item">
            <label class="label">Caisse</label>
            <select v-model="selectedCaisse" class="select">
              <option value="">Toutes les caisses</option>
              <option v-for="caisse in caisses" :key="caisse.id" :value="caisse.id">
                {{ caisse.libelle }}
              </option>
            </select>
          </div>
          <div class="filter-item">
            <label class="label">Type Mouvement</label>
            <select v-model="selectedType" class="select">
              <option value="">Tous</option>
              <option value="ENTREE">Entrées</option>
              <option value="SORTIE">Sorties</option>
            </select>
          </div>
          <div class="filter-item">
            <label class="label">Date Début</label>
            <input v-model="dateDebut" type="date" class="input" />
          </div>
          <div class="filter-item">
            <label class="label">Date Fin</label>
            <input v-model="dateFin" type="date" class="input" />
          </div>
        </div>
      </div>

      <!-- Mouvements -->
      <div class="card fade-in" style="animation-delay: 0.3s">
        <div class="card-header-simple">
          <h2 class="card-title">Journal de Caisse</h2>
          <span class="text-xs text-gray-500">{{ filteredMouvements.length }} mouvement(s)</span>
        </div>
        <div class="table-wrapper">
          <table class="table">
            <thead>
              <tr>
                <th>Date</th>
                <th>Caisse</th>
                <th>Libellé</th>
                <th class="text-center">Solde Avant</th>
                <th class="text-center">Entrée</th>
                <th class="text-center">Sortie</th>
                <th class="text-center">Solde Après</th>
                <th>Personnel</th>
                <th class="text-center">Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr v-if="filteredMouvements.length === 0">
                <td colspan="9" class="text-center py-8 text-gray-500">
                  Aucun mouvement trouvé
                </td>
              </tr>
              <tr v-else v-for="mvt in filteredMouvements" :key="mvt.id" class="table-row">
                <td class="text-gray-600">{{ formatDateTime(mvt.date_mouvement) }}</td>
                <td class="font-medium">{{ mvt.caisse }}</td>
                <td>{{ mvt.libelle_operation }}</td>
                <td class="text-center">{{ formatCurrency(mvt.solde_avant) }}</td>
                <td class="text-center">
                  <span v-if="mvt.montant_entree > 0" class="text-green-600 font-semibold">
                    +{{ formatCurrency(mvt.montant_entree) }}
                  </span>
                  <span v-else class="text-gray-400">-</span>
                </td>
                <td class="text-center">
                  <span v-if="mvt.montant_sortie > 0" class="text-red-600 font-semibold">
                    -{{ formatCurrency(mvt.montant_sortie) }}
                  </span>
                  <span v-else class="text-gray-400">-</span>
                </td>
                <td class="text-center font-semibold">{{ formatCurrency(mvt.solde_apres) }}</td>
                <td class="text-xs text-gray-500">{{ mvt.personnel }}</td>
                <td>
                  <div class="table-actions">
                    <button @click="viewMouvement(mvt)" class="action-btn" title="Voir">
                      <Eye class="w-4 h-4" />
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
import { ArrowDownCircle, ArrowUpCircle, Wallet, Eye } from 'lucide-vue-next'
import caisseService from '@/services/caisseService'

const selectedCaisse = ref('')
const selectedType = ref('')
const dateDebut = ref('')
const dateFin = ref('')
const caisses = ref([])
const mouvements = ref([])
const loading = ref(false)
const error = ref(null)

const loadCaisseData = async () => {
  loading.value = true
  error.value = null
  try {
    const [caissesResponse, mouvementsResponse] = await Promise.all([
      caisseService.getAllCaisses(),
      caisseService.getAllMouvements()
    ])
    caisses.value = caissesResponse.data || []
    mouvements.value = mouvementsResponse.data || []
  } catch (err) {
    error.value = 'Erreur lors du chargement des données de caisse'
    console.error('Erreur chargement caisse:', err)
  } finally {
    loading.value = false
  }
}

const filteredMouvements = computed(() => {
  return mouvements.value.filter(mvt => {
    const matchCaisse = !selectedCaisse.value || mvt.caisse.includes(caisses.value.find(c => c.id == selectedCaisse.value)?.libelle)
    const matchType = !selectedType.value || 
      (selectedType.value === 'ENTREE' && mvt.montant_entree > 0) ||
      (selectedType.value === 'SORTIE' && mvt.montant_sortie > 0)
    
    return matchCaisse && matchType
  })
})

const totalEntrees = computed(() => {
  return mouvements.value.filter(m => m.montant_entree > 0).length
})

const totalSorties = computed(() => {
  return mouvements.value.filter(m => m.montant_sortie > 0).length
})

const soldeTotal = computed(() => {
  return caisses.value.reduce((sum, c) => sum + (c.solde_actuel || 0), 0)
})

const formatCurrency = (amount) => {
  return new Intl.NumberFormat('fr-MG', {
    style: 'currency',
    currency: 'MGA',
    minimumFractionDigits: 0
  }).format(amount)
}

const formatDateTime = (datetime) => {
  return new Date(datetime).toLocaleString('fr-FR', {
    year: 'numeric',
    month: '2-digit',
    day: '2-digit',
    hour: '2-digit',
    minute: '2-digit'
  })
}

const openEntreeModal = () => {
  console.log('Open entrée modal')
}

const openSortieModal = () => {
  console.log('Open sortie modal')
}

const viewMouvement = (mvt) => {
  console.log('View mouvement:', mvt)
}

const viewCaisseDetails = (caisse) => {
  console.log('View caisse details:', caisse)
}

onMounted(() => {
  loadCaisseData()
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

.header-actions {
  @apply flex items-center gap-3;
}

.btn-success {
  @apply flex items-center gap-2 px-4 py-2.5 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-all text-sm font-medium;
}

.btn-danger {
  @apply flex items-center gap-2 px-4 py-2.5 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-all text-sm font-medium;
}

.caisses-grid {
  @apply grid grid-cols-1 md:grid-cols-3 gap-4;
}

.caisse-card {
  @apply bg-white rounded-lg border border-gray-200 p-5 hover:shadow-lg transition-all;
}

.caisse-header {
  @apply flex items-start justify-between mb-4;
}

.caisse-info {
  @apply flex-1;
}

.caisse-title {
  @apply text-base font-semibold text-gray-900;
}

.caisse-subtitle {
  @apply text-xs text-gray-500 mt-0.5;
}

.caisse-icon {
  @apply w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center;
}

.caisse-solde {
  @apply text-2xl font-bold text-gray-900 mb-3;
}

.caisse-footer {
  @apply pt-3 border-t border-gray-100;
}

.caisse-link {
  @apply text-xs text-gray-600 hover:text-gray-900 font-medium transition-colors;
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
</style>