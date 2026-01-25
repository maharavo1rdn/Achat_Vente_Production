<template>
    <div class="page-container">

        <!-- Loading State -->
        <div v-if="loading" class="loading-state">
            <div class="spinner"></div>
            <p class="loading-text">Chargement de la trésorerie...</p>
        </div>

        <!-- Error State -->
        <div v-else-if="error" class="loading-state">
            <div class="text-rose-500 mb-4">
                <AlertCircle class="w-12 h-12 mx-auto" />
            </div>
            <p class="text-sm text-rose-600 mb-4">{{ error }}</p>
            <button @click="loadCaisses" class="btn btn-primary">
                Réessayer
            </button>
        </div>

        <!-- Content -->
        <div v-else class="content-wrapper">

            <!-- Header -->
            <div class="page-header fade-in" style="animation-delay: 0.1s">
                <div>
                    <h1 class="page-title">Situation des Caisses</h1>
                    <p class="page-subtitle">Vue d'ensemble des comptes de trésorerie</p>
                </div>
                <!-- Bouton d'action global (Optionnel, ex: Export) -->
                <!-- <button class="btn btn-secondary">...</button> -->
            </div>

            <!-- Stats Cards -->
            <div class="stats-grid fade-in" style="animation-delay: 0.15s">
                <!-- Compteur -->
                <div class="stat-card">
                    <div class="stat-content">
                        <p class="stat-label">Comptes actifs</p>
                        <h3 class="stat-value">{{ caisses.length }}</h3>
                    </div>
                    <div class="p-3 bg-indigo-50 rounded-lg text-indigo-500">
                        <Wallet class="w-5 h-5" />
                    </div>
                </div>

                <!-- Solde Total -->
                <div class="stat-card">
                    <div class="stat-content">
                        <p class="stat-label">Trésorerie Globale</p>
                        <h3 class="stat-value" :class="soldeTotalClass">
                            {{ formatCurrency(soldeTotal) }}
                        </h3>
                    </div>
                    <div class="p-3 bg-emerald-50 rounded-lg text-emerald-500">
                        <TrendingUp class="w-5 h-5" />
                    </div>
                </div>

                <!-- Entreprise (Info contextuelle) -->
                <div class="stat-card">
                    <div class="stat-content">
                        <p class="stat-label">Périmètre</p>
                        <h3 class="text-lg font-bold text-slate-700 truncate" :title="user?.entreprise_nom">
                            {{ caisses[0]?.entreprise_nom || 'Entreprise' }}
                        </h3>
                    </div>
                    <div class="p-3 bg-blue-50 rounded-lg text-blue-500">
                        <Building2 class="w-5 h-5" />
                    </div>
                </div>
            </div>

            <!-- Table -->
            <div class="card fade-in" style="animation-delay: 0.25s">
                <div class="card-header-simple">
                    <h2 class="card-title">Comptes disponibles</h2>
                    <span class="text-xs text-gray-500">{{ caisses.length }} caisse(s) répertoriée(s)</span>
                </div>

                <div class="table-wrapper">
                    <table class="table">
                        <thead>
                            <tr>
                                <th class="w-32">Code</th>
                                <th>Libellé Caisse</th>
                                <th>Entité / Entreprise</th>
                                <th class="text-right">Solde Disponible</th>
                                <th class="text-center w-20">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Empty State -->
                            <tr v-if="caisses.length === 0">
                                <td colspan="5" class="text-center py-12">
                                    <div class="flex flex-col items-center justify-center text-gray-400">
                                        <SearchX class="w-10 h-10 mb-2 opacity-50" />
                                        <p class="text-sm">Aucune caisse configurée</p>
                                    </div>
                                </td>
                            </tr>

                            <!-- Rows -->
                            <tr v-else v-for="c in caisses" :key="c.id" class="table-row group">
                                <td class="font-mono text-xs font-bold text-slate-500 bg-slate-50/50 rounded-l-lg">
                                    {{ c.code_caisse }}
                                </td>

                                <td class="font-medium text-slate-800">
                                    {{ c.libelle }}
                                </td>

                                <td class="text-slate-600 text-sm">
                                    <div class="flex items-center gap-2">
                                        <Building2 class="w-3.5 h-3.5 text-slate-400" />
                                        {{ c.entreprise_nom }}
                                    </div>
                                </td>

                                <td class="text-right">
                                    <span class="font-bold font-mono" :class="getSoldeClass(c.solde_actuel)">
                                        {{ formatCurrency(c.solde_actuel) }}
                                    </span>
                                </td>

                                <td class="text-center">
                                    <button @click="viewCaisse(c.id)" class="action-btn" title="Voir le journal">
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
import { Wallet, TrendingUp, Building2, SearchX, Eye, AlertCircle } from 'lucide-vue-next'
import caisseService from '@/services/caisseService'

const router = useRouter()

const loading = ref(true)
const error = ref(null)
const caisses = ref([])

const user = computed(() => {
    const u = localStorage.getItem('user')
    return u ? JSON.parse(u) : null
})

const soldeTotal = computed(() => {
    return caisses.value.reduce((sum, c) => sum + parseFloat(c.solde_actuel || 0), 0)
})

const soldeTotalClass = computed(() => {
    return soldeTotal.value >= 0 ? 'text-emerald-600' : 'text-rose-600'
})

async function loadCaisses() {
    loading.value = true
    error.value = null
    try {
        const entrepriseId = user.value?.entreprise_id
        if (entrepriseId) {
            const resp = await caisseService.getCaisses(entrepriseId)
            const data = resp.data ?? resp
            caisses.value = Array.isArray(data) ? data : []
        }
    } catch (err) {
        console.error('Erreur chargement caisses:', err)
        error.value = err.response?.data?.error || 'Impossible de charger la liste des caisses.'
    } finally {
        loading.value = false
    }
}

function viewCaisse(id) {
    router.push({ name: 'caisse-detail', params: { id } })
}

function getSoldeClass(solde) {
    const s = parseFloat(solde || 0)
    if (s > 0) return 'text-emerald-600'
    if (s < 0) return 'text-rose-600'
    return 'text-slate-400' // Gris si 0
}

function formatCurrency(value) {
    const num = parseFloat(value || 0)
    return new Intl.NumberFormat('fr-MG', {
        style: 'currency',
        currency: 'MGA',
        maximumFractionDigits: 0 // Souvent 0 décimales pour MGA, ajuster si besoin
    }).format(num)
}

onMounted(() => {
    loadCaisses()
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
    @apply text-2xl font-bold text-slate-800 tracking-tight;
}

.page-subtitle {
    @apply text-sm text-slate-500 mt-1;
}

/* --- CARDS & PANELS --- */
.card {
    @apply bg-white rounded-xl border border-slate-200 p-5 shadow-sm;
}

.card-header-simple {
    @apply mb-4 pb-3 border-b border-slate-100 flex items-center justify-between;
}

.card-title {
    @apply text-base font-bold text-slate-800;
}

/* --- STATS GRID --- */
.stats-grid {
    @apply grid grid-cols-1 md:grid-cols-3 gap-6 mb-6;
}

.stat-card {
    @apply bg-white rounded-xl border border-slate-200 p-5 flex justify-between items-start shadow-sm hover:shadow-md transition-all;
}

.stat-content {
    @apply space-y-1;
}

.stat-label {
    @apply text-xs font-bold text-slate-400 uppercase tracking-wide;
}

.stat-value {
    @apply text-2xl font-bold text-slate-800;
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

/* --- BUTTONS --- */
.btn {
    @apply flex items-center justify-center gap-2 px-4 py-2 rounded-lg text-sm font-bold transition-all active:scale-95;
}

.btn-primary {
    @apply bg-indigo-600 text-white hover:bg-indigo-700 shadow-sm;
}

.action-btn {
    @apply p-2 rounded-lg text-slate-400 hover:bg-indigo-50 hover:text-indigo-600 transition-all;
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