<template>
  <div class="page-container">

    <!-- Loading State -->
    <div v-if="loading" class="loading-state">
      <div class="spinner"></div>
      <p class="loading-text">Synchronisation des caisses...</p>
    </div>

    <!-- Error State -->
    <div v-else-if="error" class="loading-state">
      <div class="text-rose-500 mb-4">
        <svg class="w-12 h-12 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
            d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
        </svg>
      </div>
      <p class="text-sm text-rose-600 mb-4">{{ error }}</p>
      <button @click="loadCaisseData" class="btn btn-primary">
        <RefreshCw class="w-4 h-4 mr-2" /> Réessayer
      </button>
    </div>

    <!-- Content -->
    <div v-else class="content-wrapper">

      <!-- Header / Navigation (Sticky) -->
      <div class="page-header fade-in" style="animation-delay: 0.1s">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 w-full">
          <div>
            <h1 class="page-title">Gestion de Trésorerie</h1>
            <p class="page-subtitle">Supervision des flux financiers et soldes</p>
          </div>

          <div class="flex items-center gap-3">
            <button @click="openEntreeModal" class="btn btn-success shadow-lg shadow-emerald-100">
              <ArrowDownCircle class="w-4 h-4" />
              <span>Entrée de fonds</span>
            </button>
            <button @click="openSortieModal" class="btn btn-danger shadow-lg shadow-rose-100">
              <ArrowUpCircle class="w-4 h-4" />
              <span>Sortie de fonds</span>
            </button>
          </div>
        </div>
      </div>

      <!-- Section: Comptes de Caisse (Cards) -->
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 fade-in" style="animation-delay: 0.15s">
        <div v-for="caisse in caisses" :key="caisse.id"
          class="modern-card hover:shadow-md transition-all duration-300 border-l-4 border-l-indigo-500">
          <div class="p-5">
            <div class="flex justify-between items-start mb-4">
              <div>
                <h3 class="font-bold text-slate-800 text-lg">{{ caisse.libelle }}</h3>
                <p class="text-xs text-slate-500 uppercase tracking-wider mt-1">{{ caisse.entreprise_nom }}</p>
              </div>
              <div class="p-2 bg-indigo-50 rounded-lg text-indigo-600">
                <Wallet class="w-5 h-5" />
              </div>
            </div>

            <div class="flex flex-col gap-1 mb-4">
              <span class="text-xs text-slate-400 font-medium uppercase">Solde Actuel</span>
              <span class="text-2xl font-bold text-slate-900 tracking-tight">
                {{ formatCurrency(caisse.solde_actuel) }}
              </span>
            </div>

            <div class="pt-4 border-t border-slate-100 flex justify-end">
              <button @click="viewCaisseDetails(caisse)"
                class="text-sm font-medium text-indigo-600 hover:text-indigo-800 flex items-center gap-1 transition-colors">
                Voir l'historique
                <ChevronRight class="w-4 h-4" />
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Section: Global Stats -->
      <div class="stats-grid fade-in" style="animation-delay: 0.2s">
        <!-- Total Mouvements -->
        <div class="stat-card">
          <div class="stat-content">
            <p class="stat-label">Flux total</p>
            <h3 class="stat-value">{{ mouvements.length }} <span class="text-sm font-normal text-gray-400">ops</span>
            </h3>
          </div>
          <div class="p-3 bg-gray-50 rounded-lg text-gray-400">
            <Activity class="w-5 h-5" />
          </div>
        </div>

        <!-- Total Entrées -->
        <div class="stat-card">
          <div class="stat-content">
            <p class="stat-label">Total Entrées</p>
            <h3 class="stat-value text-emerald-600">{{ formatCurrency(totalEntrees) }}</h3>
          </div>
          <div class="p-3 bg-emerald-50 rounded-lg text-emerald-500">
            <TrendingUp class="w-5 h-5" />
          </div>
        </div>

        <!-- Total Sorties -->
        <div class="stat-card">
          <div class="stat-content">
            <p class="stat-label">Total Sorties</p>
            <h3 class="stat-value text-rose-600">{{ formatCurrency(totalSorties) }}</h3>
          </div>
          <div class="p-3 bg-rose-50 rounded-lg text-rose-500">
            <TrendingDown class="w-5 h-5" />
          </div>
        </div>

        <!-- Solde Global -->
        <div class="stat-card">
          <div class="stat-content">
            <p class="stat-label">Trésorerie Globale</p>
            <h3 class="stat-value text-indigo-600">{{ formatCurrency(soldeTotal) }}</h3>
          </div>
          <div class="p-3 bg-indigo-50 rounded-lg text-indigo-500">
            <Scale class="w-5 h-5" />
          </div>
        </div>
      </div>

      <!-- Section: Filters -->
      <div class="card fade-in" style="animation-delay: 0.25s">
        <div class="flex items-center justify-between mb-4">
          <div class="flex items-center gap-2">
            <Filter class="w-4 h-4 text-gray-500" />
            <h3 class="text-sm font-medium">Filtres</h3>
          </div>
          <div class="flex items-center gap-2">
            <button @click="applyFilters" class="btn btn-sm btn-primary">Appliquer</button>
            <button @click="resetFilters" class="text-xs text-gray-500 hover:text-gray-900 transition-colors">Réinitialiser</button>
          </div>
        </div>

        <div class="filter-grid">
          <div class="filter-item">
            <label class="label">Compte de caisse</label>
            <div class="relative">
              <select v-model="selectedCaisse" class="select appearance-none">
                <option value="">Toutes les caisses</option>
                <option v-for="caisse in caisses" :key="caisse.id" :value="caisse.id">
                  {{ caisse.libelle }}
                </option>
              </select>
              <div class="absolute inset-y-0 right-0 flex items-center px-2 pointer-events-none text-slate-400">
                <ChevronDown class="w-4 h-4" />
              </div>
            </div>
          </div>

          <div class="filter-item">
            <label class="label">Type de flux</label>
            <div class="relative">
              <select v-model="selectedType" class="select appearance-none">
                <option value="">Tous les mouvements</option>
                <option value="ENTREE">Entrées uniquement</option>
                <option value="SORTIE">Sorties uniquement</option>
              </select>
              <div class="absolute inset-y-0 right-0 flex items-center px-2 pointer-events-none text-slate-400">
                <ChevronDown class="w-4 h-4" />
              </div>
            </div>
          </div>

          <div class="filter-item">
            <label class="label">Du</label>
            <input v-model="dateDebut" type="date" class="input" />
          </div>

          <div class="filter-item">
            <label class="label">Au</label>
            <input v-model="dateFin" type="date" class="input" />
          </div>
        </div>
      </div>

      <!-- Section: Journal Table -->
      <div class="card fade-in" style="animation-delay: 0.3s">
        <div class="card-header-simple">
          <h2 class="card-title">Journal des mouvements</h2>
          <span class="text-xs text-gray-500">{{ filteredMouvements.length }} opération(s)</span>
        </div>

        <div class="table-wrapper">
          <table class="table">
            <thead>
              <tr>
                <th class="w-32">Date / Heure</th>
                <th>Caisse</th>
                <th>Libellé / Motif</th>
                <th class="text-right text-gray-400 font-normal">Solde Avant</th>
                <th class="text-right">Entrée</th>
                <th class="text-right">Sortie</th>
                <th class="text-right font-bold text-gray-700">Solde Après</th>
                <th>Personnel</th>
                <th class="text-center w-16"></th>
              </tr>
            </thead>
            <tbody>
              <tr v-if="filteredMouvements.length === 0">
                <td colspan="9" class="text-center py-12">
                  <div class="flex flex-col items-center justify-center text-gray-400">
                    <SearchX class="w-10 h-10 mb-2 opacity-50" />
                    <p class="text-sm">Aucun mouvement ne correspond aux critères</p>
                  </div>
                </td>
              </tr>

              <tr v-else v-for="mvt in filteredMouvements" :key="mvt.id" class="table-row">
                <td class="text-gray-600 text-xs whitespace-nowrap">
                  <div class="font-medium text-slate-700">{{ formatDateTime(mvt.date_mouvement).split(' à ')[0] }}</div>
                  <div class="text-slate-400">{{ formatDateTime(mvt.date_mouvement).split(' à ')[1] }}</div>
                </td>

                <td class="font-medium text-sm text-slate-800">
                  {{ mvt.caisse }}
                </td>

                <td class="text-sm text-slate-600 max-w-[200px] truncate" :title="mvt.libelle_operation">
                  {{ mvt.libelle_operation }}
                </td>

                <td class="text-right text-xs text-slate-400 font-mono">
                  {{ formatCurrency(mvt.solde_avant) }}
                </td>

                <td class="text-right font-mono text-sm">
                  <span v-if="mvt.montant_entree > 0"
                    class="inline-flex items-center text-emerald-600 bg-emerald-50 px-2 py-0.5 rounded font-bold">
                    +{{ formatCurrency(mvt.montant_entree) }}
                  </span>
                  <span v-else class="text-slate-200">-</span>
                </td>

                <td class="text-right font-mono text-sm">
                  <span v-if="mvt.montant_sortie > 0"
                    class="inline-flex items-center text-rose-600 bg-rose-50 px-2 py-0.5 rounded font-bold">
                    -{{ formatCurrency(mvt.montant_sortie) }}
                  </span>
                  <span v-else class="text-slate-200">-</span>
                </td>

                <td class="text-right font-mono font-bold text-sm text-slate-700">
                  {{ formatCurrency(mvt.solde_apres) }}
                </td>

                <td class="text-xs text-slate-500">
                  <div class="flex items-center gap-1.5">
                    <div
                      class="w-5 h-5 rounded-full bg-slate-100 flex items-center justify-center text-[10px] font-bold text-slate-500">
                      {{ (mvt.personnel_nom || 'U')[0] }}
                    </div>
                    <span class="truncate max-w-[100px]">{{ (mvt.personnel_nom || '') + ' ' + (mvt.personnel_prenom || '')
                      }}</span>
                  </div>
                </td>

                <td class="text-center">
                  <button @click="viewMouvement(mvt)" class="action-btn" title="Voir les détails">
                    <Eye class="w-4 h-4" />
                  </button>
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
import {
  ArrowDownCircle, ArrowUpCircle, Wallet, Eye, RefreshCw,
  Activity, TrendingUp, TrendingDown, Scale, Filter, ChevronRight, ChevronDown, SearchX
} from 'lucide-vue-next'
import caisseService from '@/services/caisseService'

const router = useRouter()

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
    const user = JSON.parse(localStorage.getItem('user') || '{}')
    const entrepriseId = user.entreprise_id || null
    const caissesResponse = await caisseService.getCaisses(entrepriseId)
    const caData = caissesResponse.data ?? caissesResponse
    caisses.value = Array.isArray(caData) ? caData : []

    // Initial load: fetch unfiltered mouvements via backend
    await applyFilters()
  } catch (err) {
    error.value = 'Impossible de charger les données de trésorerie.'
    console.error('Erreur chargement caisse:', err)
  } finally {
    loading.value = false
  }
}

// Server-side filtering: mouvements are fetched from backend when clicking 'Appliquer'
const filteredMouvements = computed(() => mouvements.value)

async function applyFilters() {
  loading.value = true
  try {
    const params = {}
    if (selectedCaisse.value) params.caisse_id = selectedCaisse.value
    if (dateDebut.value) params.date_debut = dateDebut.value
    if (dateFin.value) params.date_fin = dateFin.value
    if (selectedType.value) params.type = selectedType.value // backend may support this

    const mr = await caisseService.getMouvements(params)
    const mdata = mr.data ?? mr
    mouvements.value = Array.isArray(mdata) ? mdata : []
  } catch (err) {
    console.error('Erreur application des filtres caisse:', err)
  } finally {
    loading.value = false
  }
}

function resetFilters() {
  selectedCaisse.value = ''
  selectedType.value = ''
  dateDebut.value = ''
  dateFin.value = ''
  applyFilters()
}

const totalEntrees = computed(() => {
  return filteredMouvements.value
    .filter(m => Number(m.statut_id) === 3)
    .reduce((sum, m) => sum + parseFloat(m.montant_entree || 0), 0)
})

const totalSorties = computed(() => {
  return filteredMouvements.value
    .filter(m => Number(m.statut_id) === 3)
    .reduce((sum, m) => sum + parseFloat(m.montant_sortie || 0), 0)
})

const soldeTotal = computed(() => {
  return caisses.value.reduce((sum, c) => sum + (parseFloat(c.solde_actuel) || 0), 0)
})

const formatCurrency = (amount) => {
  const num = Number(amount)
  if (amount === undefined || amount === null || isNaN(num)) return '-'
  return new Intl.NumberFormat('fr-MG', {
    style: 'currency',
    currency: 'MGA',
    maximumFractionDigits: 0
  }).format(num)
}

const formatDateTime = (datetime) => {
  if (!datetime) return '-'
  return new Date(datetime).toLocaleString('fr-FR', {
    year: 'numeric', month: '2-digit', day: '2-digit',
    hour: '2-digit', minute: '2-digit'
  }).replace(' ', ' à ')
}



const openEntreeModal = () => {
  router.push({ name: 'mouvement-caisse-new', query: { type: 'entree' } })
}

const openSortieModal = () => {
  router.push({ name: 'mouvement-caisse-new', query: { type: 'sortie' } })
}

const viewMouvement = (mvt) => {
  router.push({ name: 'mouvement-caisse-detail', params: { id: mvt.id } })
}

const viewCaisseDetails = (caisse) => {
  router.push({ name: 'caisse-detail', params: { id: caisse.id } })
}

onMounted(() => {
  loadCaisseData()
})
</script>

<style scoped>
/* --- BASE LAYOUT --- */
.page-container {
  @apply min-h-screen ml-64 p-6 bg-gray-50 font-sans text-slate-900;
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

/* --- HEADER --- */
.page-header {
  @apply sticky top-0 z-30 bg-white/80 backdrop-blur-md border-b border-gray-200 px-6 py-4 -mx-6 -mt-6 mb-8 flex flex-col md:flex-row items-center justify-between shadow-sm;
}

.page-title {
  @apply text-2xl font-bold text-slate-800 tracking-tight;
}

.page-subtitle {
  @apply text-sm text-slate-500 mt-1;
}

/* --- CARDS & PANELS --- */
.modern-card {
  @apply bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden relative;
}

.card {
  @apply bg-white rounded-xl border border-slate-200 p-5 shadow-sm;
}

.card-header-simple {
  @apply mb-4 pb-3 border-b border-slate-100 flex items-center justify-between;
}

.card-title {
  @apply text-base font-bold text-slate-800;
}

/* --- BUTTONS --- */
.btn {
  @apply flex items-center justify-center gap-2 px-4 py-2.5 rounded-lg text-sm font-bold transition-all active:scale-95 border;
}

.btn-primary {
  @apply bg-indigo-600 text-white border-transparent hover:bg-indigo-700;
}

.btn-success {
  @apply bg-emerald-600 text-white border-transparent hover:bg-emerald-700;
}

.btn-danger {
  @apply bg-rose-600 text-white border-transparent hover:bg-rose-700;
}

.action-btn {
  @apply p-2 rounded-lg text-slate-400 hover:bg-indigo-50 hover:text-indigo-600 transition-colors;
}

/* --- STATS GRID --- */
.stats-grid {
  @apply grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6;
}

.stat-card {
  @apply bg-white rounded-xl border border-slate-200 p-5 flex justify-between items-start shadow-sm hover:shadow-md transition-shadow;
}

.stat-label {
  @apply text-xs font-bold text-slate-400 uppercase tracking-wide;
}

.stat-value {
  @apply text-2xl font-bold text-slate-800 mt-1;
}

/* --- FILTERS --- */
.filter-grid {
  @apply grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4;
}

.filter-item {
  @apply space-y-1.5;
}

.label {
  @apply block text-xs font-bold text-slate-500 uppercase;
}

.input,
.select {
  @apply w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-lg text-sm font-medium text-slate-700 transition-all focus:bg-white focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 outline-none disabled:bg-gray-100 disabled:text-gray-400 disabled:cursor-not-allowed;
}

/* --- TABLE --- */
.table-wrapper {
  @apply overflow-x-auto;
}

.table {
  @apply w-full;
}

.table thead {
  @apply bg-slate-50 border-b border-slate-200;
}

.table th {
  @apply px-4 py-3 text-left text-xs font-bold text-slate-500 uppercase tracking-wider;
}

.table td {
  @apply px-4 py-3 text-sm border-b border-slate-50;
}

.table-row {
  @apply hover:bg-slate-50 transition-colors;
}

/* --- LOADING --- */
.loading-state {
  @apply flex flex-col items-center justify-center py-20;
}

.spinner {
  @apply w-10 h-10 border-4 border-slate-200 border-t-indigo-600 rounded-full animate-spin;
}

.loading-text {
  @apply mt-4 text-sm font-medium text-slate-500;
}

/* --- UTILS --- */
.fade-in {
  animation: fadeInUp 0.5s cubic-bezier(0.16, 1, 0.3, 1) forwards;
  opacity: 0;
}

@keyframes fadeInUp {
  from {
    opacity: 0;
    transform: translateY(10px);
  }

  to {
    opacity: 1;
    transform: translateY(0);
  }
}
</style>