<template>
    <div class="page-container">

        <!-- Loading State -->
        <div v-if="loading" class="loading-state">
            <div class="spinner"></div>
            <p class="loading-text">Synchronisation des flux...</p>
        </div>

        <!-- Content -->
        <div v-else class="content-wrapper">

            <!-- Header -->
            <div class="page-header fade-in" style="animation-delay: 0.1s">
                <div>
                    <h1 class="page-title">Journal de Caisse</h1>
                    <p class="page-subtitle">Historique global des entrées et sorties de fonds</p>
                </div>
                <!-- Action principale -->
                <button @click="newMouvement" class="btn btn-primary shadow-lg shadow-indigo-100">
                    <Plus class="w-4 h-4" />
                    <span>Nouvelle Opération</span>
                </button>
            </div>

            <!-- Stats Cards -->
            <div class="stats-grid fade-in" style="animation-delay: 0.15s">

                <!-- Total Ops -->
                <div class="stat-card">
                    <div class="stat-content">
                        <p class="stat-label">Total Opérations</p>
                        <h3 class="stat-value">{{ mouvements.length }}</h3>
                    </div>
                    <div class="p-3 bg-indigo-50 rounded-lg text-indigo-500">
                        <List class="w-5 h-5" />
                    </div>
                </div>

                <!-- Total Entrées -->
                <div class="stat-card">
                    <div class="stat-content">
                        <p class="stat-label">Flux Entrant (+)</p>
                        <h3 class="stat-value text-emerald-600">{{ formatCurrency(totalEntrees) }}</h3>
                    </div>
                    <div class="p-3 bg-emerald-50 rounded-lg text-emerald-500">
                        <TrendingUp class="w-5 h-5" />
                    </div>
                </div>

                <!-- Total Sorties -->
                <div class="stat-card">
                    <div class="stat-content">
                        <p class="stat-label">Flux Sortant (-)</p>
                        <h3 class="stat-value text-rose-600">{{ formatCurrency(totalSorties) }}</h3>
                    </div>
                    <div class="p-3 bg-rose-50 rounded-lg text-rose-500">
                        <TrendingDown class="w-5 h-5" />
                    </div>
                </div>

            </div>

            <!-- Filters -->
            <div class="card fade-in" style="animation-delay: 0.2s">
                <div
                    class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-5 border-b border-gray-100 pb-4">
                    <div class="flex items-center gap-2 text-indigo-900">
                        <Filter class="w-4 h-4" />
                        <h3 class="font-semibold text-sm">Filtres de recherche</h3>
                    </div>

                    <!-- Boutons Actions Filtres -->
                    <div class="flex items-center gap-2">
                        <button @click="resetFilters" class="btn btn-sm btn-ghost text-gray-500 hover:text-gray-800">
                            <RefreshCw class="w-3.5 h-3.5" />
                            <span>Réinitialiser</span>
                        </button>
                        <button @click="applyFilters" class="btn btn-sm btn-primary shadow-sm shadow-indigo-200">
                            <Check class="w-3.5 h-3.5" />
                            <span>Appliquer</span>
                        </button>
                    </div>
                </div>

                <div class="filter-grid">
                    <div class="filter-item">
                        <label class="label">Compte de Caisse</label>
                        <div class="relative">
                            <select v-model="filterCaisse" class="select appearance-none">
                                <option value="">Toutes les caisses</option>
                                <option v-for="c in caisses" :key="c.id" :value="c.id">{{ c.libelle }}</option>
                            </select>
                            <div
                                class="absolute inset-y-0 right-0 flex items-center px-2 pointer-events-none text-slate-400">
                                <ChevronDown class="w-4 h-4" />
                            </div>
                        </div>
                    </div>

                    <div class="filter-item">
                        <label class="label">Période (Début)</label>
                        <input type="date" v-model="filterDateDebut" class="input" />
                    </div>

                    <div class="filter-item">
                        <label class="label">Période (Fin)</label>
                        <input type="date" v-model="filterDateFin" class="input" />
                    </div>
                </div>
            </div>

            <!-- Table -->
            <div class="card fade-in" style="animation-delay: 0.25s">
                <div class="card-header-simple">
                    <h2 class="card-title">Transactions</h2>
                    <span class="text-xs text-gray-500 font-medium bg-gray-100 px-2 py-0.5 rounded-full">{{
                        filteredMouvements.length }} résultat(s)</span>
                </div>

                <div class="table-wrapper">
                    <table class="table">
                        <thead>
                            <tr>
                                <th class="w-32">Date / Heure</th>
                                <th>Caisse</th>
                                <th>Libellé / Motif</th>
                                <th class="text-right text-emerald-600">Entrée</th>
                                <th class="text-right text-rose-600">Sortie</th>
                                <th class="text-right font-bold text-slate-700">Solde Après</th>
                                <th>Responsable</th>
                                <th class="text-center w-16">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Empty State -->
                            <tr v-if="filteredMouvements.length === 0">
                                <td colspan="8" class="text-center py-16">
                                    <div class="flex flex-col items-center justify-center text-gray-400">
                                        <div class="p-4 bg-gray-50 rounded-full mb-3">
                                            <SearchX class="w-8 h-8 opacity-50" />
                                        </div>
                                        <p class="text-sm font-medium">Aucun mouvement trouvé</p>
                                    </div>
                                </td>
                            </tr>

                            <!-- Rows -->
                            <tr v-else v-for="m in filteredMouvements" :key="m.id" class="table-row group">
                                <td class="text-gray-600 text-xs whitespace-nowrap">
                                    <div class="font-medium text-slate-700">{{ formatDate(m.date_mouvement).split(' à')[0] }}</div>
                                    <div class="text-slate-400">{{ formatDate(m.date_mouvement).split(' à ')[1] }}</div>
                                </td>

                                <td class="font-medium text-sm text-slate-800">
                                    {{ m.caisse_libelle }}
                                </td>

                                <td class="text-sm text-slate-600 max-w-[200px] truncate" :title="m.libelle_operation">
                                    {{ m.libelle_operation }}
                                </td>

                                <td class="text-right font-mono text-sm">
                                    <span v-if="parseFloat(m.montant_entree) > 0"
                                        class="inline-flex items-center px-2 py-0.5 rounded bg-emerald-50 text-emerald-700 font-bold border border-emerald-100">
                                        +{{ formatCurrency(m.montant_entree) }}
                                    </span>
                                    <span v-else class="text-slate-200">-</span>
                                </td>

                                <td class="text-right font-mono text-sm">
                                    <span v-if="parseFloat(m.montant_sortie) > 0"
                                        class="inline-flex items-center px-2 py-0.5 rounded bg-rose-50 text-rose-700 font-bold border border-rose-100">
                                        -{{ formatCurrency(m.montant_sortie) }}
                                    </span>
                                    <span v-else class="text-slate-200">-</span>
                                </td>

                                <td class="text-right font-mono font-bold text-sm text-slate-700">
                                    {{ formatCurrency(m.solde_apres) }}
                                </td>

                                <td class="text-xs text-slate-500">
                                    <div class="flex items-center gap-1.5">
                                        <div
                                            class="w-5 h-5 rounded-full bg-slate-100 flex items-center justify-center text-[10px] font-bold text-slate-500">
                                            {{ (m.personnel_prenom || 'U')[0] }}
                                        </div>
                                        <span class="truncate max-w-[100px]">{{ (m.personnel_prenom || '') }} {{ (m.personnel_nom || '') }}</span>
                                    </div>
                                </td>

                                <td class="text-center">
                                    <button @click="viewMouvement(m.id)"
                                        class="action-btn text-gray-400 hover:text-indigo-600 hover:bg-indigo-50"
                                        title="Voir les détails">
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
    Plus, List, TrendingUp, TrendingDown, Filter,
    SearchX, Eye, ChevronDown, RefreshCw, Check
} from 'lucide-vue-next'
import caisseService from '@/services/caisseService'

const router = useRouter()

const loading = ref(true)
const mouvements = ref([])
const caisses = ref([])
const filterCaisse = ref('')
const filterDateDebut = ref('')
const filterDateFin = ref('')

const user = computed(() => {
    const u = localStorage.getItem('user')
    return u ? JSON.parse(u) : null
})

// Use filtered list for display
const filteredMouvements = computed(() => mouvements.value)

async function applyFilters() {
    loading.value = true
    try {
        const params = {}
        if (filterCaisse.value) params.caisse_id = filterCaisse.value
        if (filterDateDebut.value) params.date_debut = filterDateDebut.value
        if (filterDateFin.value) params.date_fin = filterDateFin.value
        const mr = await caisseService.getMouvements(params)
        const mdata = mr.data ?? mr
        mouvements.value = Array.isArray(mdata) ? mdata : []
    } catch (err) {
        console.error('Erreur application des filtres:', err)
    } finally {
        loading.value = false
    }
}

function resetFilters() {
    filterCaisse.value = ''
    filterDateDebut.value = ''
    filterDateFin.value = ''
    applyFilters()
}

const totalEntrees = computed(() => {
    return filteredMouvements.value
        .filter(m => parseFloat(m.montant_entree) > 0)
        .reduce((sum, m) => sum + parseFloat(m.montant_entree || 0), 0)
})

const totalSorties = computed(() => {
    return filteredMouvements.value
        .filter(m => parseFloat(m.montant_sortie) > 0)
        .reduce((sum, m) => sum + parseFloat(m.montant_sortie || 0), 0)
})

async function loadData() {
    loading.value = true
    try {
        const entrepriseId = user.value?.entreprise_id

        // Load caisses for filter
        if (entrepriseId) {
            const cr = await caisseService.getCaisses(entrepriseId)
            const cdata = cr.data ?? cr
            caisses.value = Array.isArray(cdata) ? cdata : []
        }

        // Initial load
        await applyFilters()
    } catch (err) {
        console.error('Erreur chargement:', err)
    } finally {
        loading.value = false
    }
}

function viewMouvement(id) {
    router.push({ name: 'mouvement-caisse-detail', params: { id } })
}

function newMouvement() {
    const query = {}
    if (filterCaisse.value) query.caisse_id = filterCaisse.value
    router.push({ name: 'mouvement-caisse-new', query })
}

function formatCurrency(value) {
    const num = parseFloat(value || 0)
    return new Intl.NumberFormat('fr-MG', {
        style: 'currency',
        currency: 'MGA',
        maximumFractionDigits: 0
    }).format(num)
}

function formatDate(dateString) {
    if (!dateString) return '-'
    const date = new Date(dateString)
    return new Intl.DateTimeFormat('fr-FR', {
        day: '2-digit', month: '2-digit', year: 'numeric',
        hour: '2-digit', minute: '2-digit'
    }).format(date).replace(' ', ' à ')
}

onMounted(() => {
    loadData()
})
</script>

<style scoped>
/* --- BASE LAYOUT --- */
.page-container {
    @apply min-h-screen ml-64 p-6 bg-[#f8fafc] font-sans text-slate-900;
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
    @apply sticky top-0 z-30 bg-white/80 backdrop-blur-md border-b border-gray-200 px-6 py-4 -mx-6 -mt-6 mb-8 flex items-center justify-between shadow-sm;
}

.page-title {
    @apply text-2xl font-bold text-gray-900 tracking-tight;
}

.page-subtitle {
    @apply text-sm text-gray-500 mt-1;
}

/* --- CARDS & PANELS --- */
.card {
    @apply bg-white rounded-xl border border-gray-200 p-5 shadow-sm mb-6;
}

.card-header-simple {
    @apply mb-4 pb-3 border-b border-gray-100 flex items-center justify-between;
}

.card-title {
    @apply text-base font-bold text-gray-800;
}

/* --- STATS GRID --- */
.stats-grid {
    @apply grid grid-cols-1 md:grid-cols-3 gap-6 mb-6;
}

.stat-card {
    @apply bg-white rounded-xl border border-gray-200 p-5 flex justify-between items-start shadow-sm hover:shadow-md transition-all;
}

.stat-content {
    @apply space-y-1;
}

.stat-label {
    @apply text-xs font-bold text-gray-400 uppercase tracking-wide;
}

.stat-value {
    @apply text-2xl font-bold text-slate-800;
}

/* --- FILTERS --- */
.filter-grid {
    @apply grid grid-cols-1 md:grid-cols-3 gap-4;
}

.filter-item {
    @apply space-y-1.5;
}

.label {
    @apply block text-xs font-bold text-gray-500 uppercase ml-1;
}

.input,
.select {
    @apply w-full px-3 py-2 bg-gray-50 border border-gray-200 rounded-lg text-sm font-medium text-gray-700 transition-all focus:bg-white focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 outline-none disabled:bg-gray-100 disabled:text-gray-400 disabled:cursor-not-allowed;
}

/* --- TABLE --- */
.table-wrapper {
    @apply overflow-x-auto;
}

.table {
    @apply w-full;
}

.table thead {
    @apply bg-gray-50 border-b border-gray-100;
}

.table th {
    @apply px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider;
}

.table td {
    @apply px-4 py-3 text-sm border-b border-gray-50;
}

.table-row {
    @apply hover:bg-gray-50/80 transition-colors;
}

/* --- BUTTONS --- */
.btn {
    @apply flex items-center justify-center gap-2 rounded-lg font-medium transition-all active:scale-95;
}

.btn-sm {
    @apply px-3 py-1.5 text-xs;
}

.btn-primary {
    @apply bg-indigo-600 text-white hover:bg-indigo-700 border border-transparent;
}

.btn-ghost {
    @apply bg-transparent text-gray-500 hover:bg-gray-100 hover:text-gray-900;
}

.action-btn {
    @apply p-1.5 rounded-md transition-colors;
}

/* --- LOADING --- */
.loading-state {
    @apply flex flex-col items-center justify-center py-20;
}

.spinner {
    @apply w-10 h-10 border-4 border-slate-200 border-t-indigo-600 rounded-full animate-spin;
}

.loading-text {
    @apply mt-4 text-sm font-medium text-gray-500;
}

/* --- ANIMATIONS --- */
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