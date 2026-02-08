<template>
    <div class="page-container">

        <!-- Loading State -->
        <div v-if="loading" class="loading-state">
            <div class="spinner"></div>
            <p class="loading-text">Synchronisation du compte de caisse...</p>
        </div>

        <!-- Content -->
        <div v-else class="content-wrapper">

            <!-- Header / Navigation -->
            <header class="header-section fade-in">
                <div class="max-w-6xl mx-auto flex flex-col md:flex-row md:items-center justify-between gap-4">

                    <!-- Left: Title & Context -->
                    <div class="flex items-center gap-4">
                        <button @click="goBack" class="btn-back group" title="Retour aux caisses">
                            <ArrowLeft class="w-5 h-5 text-gray-500 group-hover:text-indigo-600 transition-colors" />
                        </button>

                        <div class="flex flex-col">
                            <div class="flex items-center gap-3">
                                <h1 class="text-xl font-bold text-slate-800 tracking-tight">
                                    {{ caisse?.libelle || 'Compte de Caisse' }}
                                </h1>
                                <span
                                    class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider border border-slate-200 bg-slate-50 text-slate-500">
                                    {{ caisse?.code_caisse }}
                                </span>
                            </div>
                            <div class="flex items-center gap-2 text-xs text-slate-500 mt-1">
                                <Building2 class="w-3 h-3" />
                                <span>{{ caisse?.entreprise_nom || 'Entreprise non définie' }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Right: Actions -->
                    <div class="flex items-center gap-3">
                        <button @click="createMouvement" class="btn btn-primary shadow-lg shadow-indigo-100">
                            <Plus class="w-4 h-4" />
                            <span>Nouveau Mouvement</span>
                        </button>
                    </div>
                </div>
            </header>

            <!-- Main Content -->
            <main class="max-w-6xl mx-auto pb-12 space-y-8 fade-in" style="animation-delay: 0.1s">

                <!-- SECTION 1: Caisse Overview (Hero Card) -->
                <section class="modern-card relative overflow-hidden bg-gradient-to-br from-white to-slate-50">
                    <!-- Decorative Top Border -->
                    <div
                        class="absolute top-0 left-0 w-full h-1 bg-gradient-to-r from-emerald-500 via-indigo-500 to-emerald-500">
                    </div>

                    <div class="p-6 md:p-8">
                        <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-6">

                            <!-- Info Block -->
                            <div class="flex-1 space-y-4">
                                <div class="flex items-center gap-2 text-indigo-900 font-semibold mb-2">
                                    <Info class="w-5 h-5" />
                                    <h2>Situation Financière</h2>
                                </div>

                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-12 gap-y-4">
                                    <div class="detail-group">
                                        <span class="label">Identifiant Caisse</span>
                                        <span class="value font-mono">{{ caisse?.code_caisse }}</span>
                                    </div>
                                    <div class="detail-group">
                                        <span class="label">Dernière mise à jour</span>
                                        <span class="value">{{ formatDate(new Date()) }}</span>
                                        <!-- Ou date last update si dispo -->
                                    </div>
                                </div>
                            </div>

                            <!-- Balance Block (Hero) -->
                            <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm min-w-[280px]">
                                <div class="flex items-center justify-between mb-2">
                                    <span class="text-xs font-bold text-slate-400 uppercase tracking-wide">Solde
                                        Disponible</span>
                                    <div class="p-2 bg-emerald-50 rounded-lg text-emerald-600">
                                        <Wallet class="w-5 h-5" />
                                    </div>
                                </div>
                                <div class="text-3xl font-bold tracking-tight"
                                    :class="getSoldeClass(caisse?.solde_actuel)">
                                    {{ formatCurrency(caisse?.solde_actuel) }}
                                </div>
                            </div>

                        </div>
                    </div>
                </section>

                <!-- SECTION 2: Movements History -->
                <section class="modern-card flex flex-col">
                    <div class="card-header flex items-center justify-between border-b border-slate-100 px-6 py-4">
                        <div class="flex items-center gap-2.5">
                            <div class="p-2 bg-indigo-50 rounded-lg text-indigo-600">
                                <List class="w-5 h-5" />
                            </div>
                            <div>
                                <h2 class="font-bold text-lg text-slate-800">Historique des transactions</h2>
                                <p class="text-xs text-slate-500">{{ mouvements.length }} opération(s) enregistrée(s)
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="table w-full text-left border-collapse">
                            <thead>
                                <tr
                                    class="bg-slate-50/80 border-b border-slate-200 text-xs uppercase text-slate-500 font-bold tracking-wide">
                                    <th class="px-6 py-3">Date / Heure</th>
                                    <th class="px-6 py-3">Libellé Opération</th>
                                    <th class="px-6 py-3 text-right text-emerald-600">Entrée</th>
                                    <th class="px-6 py-3 text-right text-rose-600">Sortie</th>
                                    <th class="px-6 py-3 text-right">Solde Après</th>
                                    <th class="px-6 py-3">Responsable</th>
                                    <th class="px-6 py-3 text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">

                                <!-- Empty State -->
                                <tr v-if="mouvements.length === 0">
                                    <td colspan="7" class="py-16 text-center">
                                        <div class="flex flex-col items-center justify-center gap-3 text-slate-400">
                                            <SearchX class="w-10 h-10 opacity-50" />
                                            <p class="text-sm font-medium">Aucun mouvement enregistré pour cette caisse
                                            </p>
                                        </div>
                                    </td>
                                </tr>

                                <!-- Rows -->
                                <tr v-else v-for="m in mouvements" :key="m.id"
                                    class="group hover:bg-slate-50/80 transition-colors duration-150">
                                    <td class="px-6 py-3 text-sm text-slate-600 whitespace-nowrap">
                                        {{ formatDate(m.date_mouvement) }}
                                    </td>

                                    <td class="px-6 py-3 text-sm font-medium text-slate-800">
                                        {{ m.libelle_operation }}
                                    </td>

                                    <td class="px-6 py-3 text-right font-mono text-sm">
                                        <span v-if="parseFloat(m.montant_entree) > 0"
                                            class="inline-flex items-center px-2 py-0.5 rounded bg-emerald-50 text-emerald-700 font-bold border border-emerald-100">
                                            +{{ formatCurrency(m.montant_entree) }}
                                        </span>
                                        <span v-else class="text-slate-200">-</span>
                                    </td>

                                    <td class="px-6 py-3 text-right font-mono text-sm">
                                        <span v-if="parseFloat(m.montant_sortie) > 0"
                                            class="inline-flex items-center px-2 py-0.5 rounded bg-rose-50 text-rose-700 font-bold border border-rose-100">
                                            -{{ formatCurrency(m.montant_sortie) }}
                                        </span>
                                        <span v-else class="text-slate-200">-</span>
                                    </td>

                                    <td class="px-6 py-3 text-right font-mono text-sm font-bold text-slate-700">
                                        {{ formatCurrency(m.solde_apres) }}
                                    </td>

                                    <td class="px-6 py-3 text-xs text-slate-500">
                                        <div class="flex items-center gap-2">
                                            <div
                                                class="w-6 h-6 rounded-full bg-slate-100 flex items-center justify-center text-[10px] font-bold text-slate-500">
                                                {{ (m.personnel_prenom?.[0] || 'U') }}
                                            </div>
                                            <span class="truncate max-w-[120px]">{{ (m.personnel_prenom || '') }} {{
                                                (m.personnel_nom || '') }}</span>
                                        </div>
                                    </td>

                                    <td class="px-6 py-3 text-center">
                                        <button @click="viewMouvement(m.id)"
                                            class="p-2 rounded-lg text-slate-400 hover:text-indigo-600 hover:bg-indigo-50 transition-all active:scale-95"
                                            title="Voir les détails">
                                            <Eye class="w-4 h-4" />
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </section>

            </main>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import {
    ArrowLeft, Wallet, SearchX, Eye, Plus,
    Info, Building2, List
} from 'lucide-vue-next'
import caisseService from '@/services/caisseService'

const router = useRouter()
const route = useRoute()

const loading = ref(true)
const caisse = ref(null)
const mouvements = ref([])

async function loadCaisse() {
    loading.value = true
    try {
        const caisseId = parseInt(route.params.id)

        // Handling potential unwrapping of response.data based on your previous examples
        const caisseResp = await caisseService.getCaisseById(caisseId)
        caisse.value = caisseResp.data ?? caisseResp

        const mvtResp = await caisseService.getMouvements({ caisse_id: caisseId })
        mouvements.value = Array.isArray(mvtResp.data) ? mvtResp.data : (mvtResp.data?.data || [])
    } catch (err) {
        console.error('Erreur chargement caisse:', err)
        // alert(err.response?.data?.error || 'Erreur lors du chargement de la caisse') // Prefer UI error state if possible
    } finally {
        loading.value = false
    }
}

function goBack() {
    router.push({ name: 'caisses' })
}

function viewMouvement(mouvementId) {
    router.push({ name: 'mouvement-caisse-detail', params: { id: mouvementId } })
}

function createMouvement() {
    if (!caisse.value || !caisse.value.id) return
    router.push({ name: 'mouvement-caisse-new', query: { caisse_id: caisse.value.id } })
}

function getSoldeClass(solde) {
    const s = parseFloat(solde || 0)
    if (s > 0) return 'text-emerald-600'
    if (s < 0) return 'text-rose-600'
    return 'text-slate-600'
}

function formatCurrency(value) {
    const num = parseFloat(value || 0)
    return new Intl.NumberFormat('fr-MG', {
        style: 'currency', currency: 'MGA', maximumFractionDigits: 0
    }).format(num)
}

function formatDate(dateString) {
    if (!dateString) return '-'
    const date = new Date(dateString)
    return new Intl.DateTimeFormat('fr-FR', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    }).format(date)
}

onMounted(() => {
    loadCaisse()
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
        @apply ml-0 p-3;
    }
}

/* --- HEADER --- */
.header-section {
    @apply sticky top-0 z-40 bg-white/80 backdrop-blur-md border-b border-slate-200/60 px-6 py-4 -mx-6 -mt-6 mb-8;
}

.btn-back {
    @apply p-2 rounded-full hover:bg-slate-100 transition-all active:scale-95;
}

/* --- CARDS --- */
.modern-card {
    @apply bg-white rounded-2xl shadow-lg shadow-slate-200/50 border border-slate-100 overflow-hidden;
}

/* --- BUTTONS --- */
.btn {
    @apply flex items-center gap-2 px-4 py-2 rounded-xl text-sm font-bold transition-all duration-200 active:scale-95 border;
}

.btn-primary {
    @apply bg-indigo-600 text-white border-transparent hover:bg-indigo-700;
}

/* --- LABELS & VALUES --- */
.detail-group {
    @apply flex flex-col gap-1;
}

.label {
    @apply text-[11px] font-bold text-slate-400 uppercase tracking-wide;
}

.value {
    @apply text-sm font-semibold text-slate-800 break-words;
}

/* --- UTILITIES --- */
.loading-state {
    @apply flex flex-col items-center justify-center py-20;
}

.spinner {
    @apply w-10 h-10 border-4 border-slate-200 border-t-indigo-600 rounded-full animate-spin;
}

.loading-text {
    @apply mt-4 text-sm font-medium text-slate-500;
}

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