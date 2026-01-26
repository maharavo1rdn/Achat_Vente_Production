<template>
    <div class="page-container">

        <!-- Loading State -->
        <div v-if="loading" class="loading-state">
            <div class="spinner"></div>
            <p class="loading-text">Récupération des paiements...</p>
        </div>

        <!-- Content -->
        <div v-else class="content-wrapper">

            <!-- Header -->
            <div class="page-header fade-in" style="animation-delay: 0.1s">
                <div>
                    <h1 class="page-title">Paiements Fournisseurs</h1>
                    <p class="page-subtitle">Historique et suivi des règlements sortants</p>
                </div>
                <button @click="exportPdf" class="btn btn-secondary">
                    <Download class="w-4 h-4" />
                    <span>Exporter</span>
                </button>
            </div>

            <!-- Stats Cards -->
            <div class="stats-grid fade-in" style="animation-delay: 0.15s">
                <div class="stat-card">
                    <div class="stat-content">
                        <p class="stat-label">Total Transactions</p>
                        <h3 class="stat-value">{{ paiements.length }}</h3>
                    </div>
                    <div class="p-3 bg-gray-50 rounded-lg">
                        <CreditCard class="w-5 h-5 text-gray-400" />
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-content">
                        <p class="stat-label">Volume Total (TTC)</p>
                        <h3 class="stat-value text-indigo-600">{{ formatCurrency(totalMontant) }}</h3>
                    </div>
                    <div class="p-3 bg-indigo-50 rounded-lg">
                        <Wallet class="w-5 h-5 text-indigo-500" />
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-content">
                        <p class="stat-label">Ce mois-ci</p>
                        <h3 class="stat-value text-gray-900">{{ paiementsCeMois }}</h3>
                    </div>
                    <div class="p-3 bg-gray-50 rounded-lg">
                        <Calendar class="w-5 h-5 text-gray-400" />
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-content">
                        <p class="stat-label">En attente</p>
                        <h3 class="stat-value text-orange-600">{{ paiementsEnAttente }}</h3>
                    </div>
                    <div class="p-3 bg-orange-50 rounded-lg">
                        <Clock class="w-5 h-5 text-orange-500" />
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

                    <!-- Boutons améliorés -->
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
                        <label class="label">Recherche rapide</label>
                        <div class="search-box">
                            <Search class="w-4 h-4 text-gray-400" />
                            <input v-model="searchQuery" type="text" placeholder="N° Reçu, Facture..."
                                class="search-input" />
                        </div>
                    </div>

                    <div class="filter-item">
                        <label class="label">Période (Début)</label>
                        <input v-model="filterDateDebut" type="date" class="input" />
                    </div>

                    <div class="filter-item">
                        <label class="label">Période (Fin)</label>
                        <input v-model="filterDateFin" type="date" class="input" />
                    </div>

                    <div class="filter-item">
                        <label class="label">Statut</label>
                        <div class="relative">
                            <select v-model="filterStatut" class="select appearance-none">
                                <option value="">Tous les statuts</option>
                                <option v-for="s in statutOptions" :key="s.id" :value="s.id">{{ s.libelle }}</option>
                            </select>
                            <div
                                class="absolute inset-y-0 right-0 flex items-center px-2 pointer-events-none text-gray-400">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M19 9l-7 7-7-7"></path>
                                </svg>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Table -->
            <div class="card fade-in" style="animation-delay: 0.25s">
                <div class="card-header-simple">
                    <h2 class="card-title">Journal des paiements</h2>
                    <span class="text-xs text-gray-500 font-medium bg-gray-100 px-2 py-0.5 rounded-full">{{
                        filteredPaiements.length }} résultat(s)</span>
                </div>

                <div class="table-wrapper">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>N° Reçu</th>
                                <th>Date Paiement</th>
                                <th>Référence Facture</th>
                                <th>Mode</th>
                                <th class="text-right">Montant</th>
                                <th class="text-center">Statut</th>
                                <th class="text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-if="filteredPaiements.length === 0">
                                <td colspan="7" class="text-center py-16">
                                    <div class="flex flex-col items-center justify-center text-gray-400">
                                        <div class="p-4 bg-gray-50 rounded-full mb-3">
                                            <SearchX class="w-8 h-8 opacity-50" />
                                        </div>
                                        <p class="text-sm font-medium">Aucun paiement trouvé</p>
                                        <p class="text-xs mt-1">Essayez de modifier vos filtres</p>
                                    </div>
                                </td>
                            </tr>

                            <tr v-else v-for="p in filteredPaiements" :key="p.id" class="table-row group">
                                <td class="font-bold text-gray-800 text-sm">
                                    {{ p.numero_recu || '-' }}
                                </td>
                                <td class="text-gray-600 text-sm">
                                    {{ formatDate(p.date_paiement) }}
                                </td>
                                <td>
                                    <span
                                        class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md bg-indigo-50 text-xs font-medium text-indigo-700 border border-indigo-100">
                                        <FileText class="w-3 h-3" />
                                        {{ p.numero_facture || '#' + p.facture_achat_id }}
                                    </span>
                                </td>
                                <td class="text-gray-600 text-sm">
                                    <div>{{ p.mode_paiement_libelle || '-' }}</div>
                                    <div v-if="p.caisse_libelle" class="text-xs text-gray-500 mt-1">{{ p.caisse_libelle }}</div>
                                </td>
                                <td class="text-right font-mono font-medium text-gray-900">
                                    {{ formatCurrency(p.montant) }}
                                </td>
                                <td class="text-center">
                                    <span :class="['badge', getStatutClass(p.statut_code || p.statut_libelle)]">
                                        {{ p.statut_libelle }}
                                    </span>
                                </td>
                                <td>
                                    <div class="table-actions">
                                        <router-link :to="{ name: 'paiement-achat-detail', params: { id: p.id } }"
                                            class="action-btn text-gray-400 hover:text-indigo-600 hover:bg-indigo-50"
                                            title="Voir les détails">
                                            <Eye class="w-4 h-4" />
                                        </router-link>

                                        <router-link v-if="p.statut_id !== 3"
                                            :to="{ name: 'paiement-achat-detail', params: { id: p.id } }"
                                            class="action-btn text-gray-400 hover:text-blue-600 hover:bg-blue-50"
                                            title="Modifier">
                                            <Pencil class="w-4 h-4" />
                                        </router-link>

                                        <button v-if="p.statut_id !== 3" @click="openValidateModal(p)"
                                            class="action-btn text-gray-400 hover:text-emerald-600 hover:bg-emerald-50"
                                            title="Valider">
                                            <CheckCircle class="w-4 h-4" />
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Modal de Validation (Améliorée) -->
                <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
                    <!-- Backdrop flouté -->
                    <div class="absolute inset-0 bg-slate-900/40 backdrop-blur-sm transition-opacity"
                        @click="showModal = false"></div>

                    <div
                        class="bg-white rounded-xl shadow-2xl w-full max-w-md z-50 transform transition-all overflow-hidden">
                        <!-- Modal Header -->
                        <div class="px-6 py-4 border-b border-gray-100 bg-gray-50 flex justify-between items-center">
                            <h3 class="font-bold text-gray-800 text-lg">Validation du paiement</h3>
                            <button @click="showModal = false" class="text-gray-400 hover:text-gray-600">
                                <X class="w-5 h-5" />
                            </button>
                        </div>

                        <!-- Modal Body -->
                        <div class="p-6 space-y-4">
                            <div
                                class="flex items-center gap-3 p-3 bg-indigo-50 rounded-lg border border-indigo-100 mb-4">
                                <div class="bg-white p-2 rounded-full shadow-sm">
                                    <FileText class="w-5 h-5 text-indigo-600" />
                                </div>
                                <div>
                                    <p class="text-xs text-indigo-600 font-medium uppercase">Paiement concerné</p>
                                    <p class="font-bold text-gray-900">{{ modalPaiement?.numero_facture || "#" + modalPaiement?.id }} - {{
                                        formatCurrency(modalPaiement?.montant) }}</p>
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="label required">Compte de Caisse (Source)</label>
                                <select v-model="modalPayload.caisse_id" class="select w-full">
                                    <option value="">-- Sélectionner une caisse --</option>
                                    <option v-for="c in modalCaisses" :key="c.id" :value="c.id">
                                        {{ c.libelle }} ({{ formatCurrency(c.solde_actuel) }})
                                    </option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label class="label">Validateur</label>
                                <input :value="currentUser?.nom + ' ' + currentUser?.prenom" readonly
                                    class="input w-full bg-gray-50 text-gray-500 cursor-not-allowed" />
                            </div>

                            <div class="form-group">
                                <label class="label">Libellé de l'opération</label>
                                <input v-model="modalPayload.libelle" class="input w-full"
                                    placeholder="Ex: Règlement facture..." />
                            </div>
                        </div>

                        <!-- Modal Footer -->
                        <div class="px-6 py-4 bg-gray-50 border-t border-gray-100 flex justify-end gap-3">
                            <button class="btn btn-ghost text-gray-600" @click="showModal = false">Annuler</button>
                            <button class="btn btn-primary shadow-lg shadow-indigo-200" @click="submitModalValidate">
                                <Check class="w-4 h-4 mr-2" />
                                Confirmer la validation
                            </button>
                        </div>
                    </div>
                </div>

            </div>

        </div>
    </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import {
    CreditCard, Wallet, Calendar, Clock, Filter,
    Search, Eye, Download, FileText, SearchX,
    RefreshCw, Check, Pencil, CheckCircle, X
} from 'lucide-vue-next'
import paiementAchatService from '@/services/paiementAchatService'
import caisseService from '@/services/caisseService'

const paiements = ref([])
const loading = ref(false)
const statutOptions = ref([])

// Filtres
const searchQuery = ref('')
const filterDateDebut = ref('')
const filterDateFin = ref('')
const filterStatut = ref('')

// Chargement des données
const load = async () => {
    loading.value = true
    try {
        const resp = await paiementAchatService.list()
        paiements.value = Array.isArray(resp.data) ? resp.data : (resp.data?.data || [])
    } catch (err) {
        console.error('Erreur chargement paiements achat', err)
    } finally {
        loading.value = false
    }
}

// Propriétés calculées
const filteredPaiements = computed(() => paiements.value)

async function applyFilters() {
    loading.value = true
    try {
        const params = {}
        if (filterDateDebut.value) params.date_debut = filterDateDebut.value
        if (filterDateFin.value) params.date_fin = filterDateFin.value
        if (filterStatut.value) params.statut_id = Number(filterStatut.value)
        if (searchQuery.value) params.search = searchQuery.value
        const resp = await paiementAchatService.list(params)
        paiements.value = Array.isArray(resp.data) ? resp.data : (resp.data?.data || [])
    } catch (err) {
        console.error("Erreur lors de l'application des filtres", err)
    } finally {
        loading.value = false
    }
}

function resetFilters() {
    searchQuery.value = ''
    filterDateDebut.value = ''
    filterDateFin.value = ''
    filterStatut.value = ''
    applyFilters()
}

const totalMontant = computed(() => {
    return filteredPaiements.value
        .filter(p => Number(p.statut_id) === 3)
        .reduce((acc, curr) => acc + (Number(curr.montant) || 0), 0)
})

const paiementsCeMois = computed(() => {
    const now = new Date()
    const currentMonth = now.getMonth()
    const currentYear = now.getFullYear()

    return paiements.value.filter(p => {
        if (!p.date_paiement) return false
        const d = new Date(p.date_paiement)
        return d.getMonth() === currentMonth && d.getFullYear() === currentYear
    }).length
})

const paiementsEnAttente = computed(() => {
    return paiements.value.filter(p =>
        p.statut_code === 'BROUILLON' || p.statut_libelle === 'Brouillon'
    ).length
})

// Modal logic
const showModal = ref(false)
const modalPaiement = ref(null)
const modalCaisses = ref([])
const modalPayload = ref({ caisse_id: null, personnel_id: null, libelle: '' })
const currentUser = ref(JSON.parse(localStorage.getItem('user') || '{}'))

const loadModalCaisses = async () => {
    try {
        const entrepriseId = currentUser.value.entreprise_id || null
        const resp = await caisseService.getCaisses(entrepriseId)
        modalCaisses.value = resp.data ?? resp
    } catch (err) {
        console.error('Erreur chargement caisses', err)
    }
}

const openValidateModal = async (p) => {
    if (p.caisse_mouvement_id) {
        try {
            await paiementAchatService.validate(p.id, { user_id: currentUser.value.id })
            alert('Paiement validé')
            await load()
        } catch (err) {
            console.error(err)
            alert(err.response?.data?.error || 'Erreur validation')
        }
        return
    }

    modalPaiement.value = p
    modalPayload.value.personnel_id = currentUser.value.id || null
    modalPayload.value.libelle = `Paiement facture ${p.numero_facture || ''}`
    await loadModalCaisses()
    showModal.value = true
}

const submitModalValidate = async () => {
    if (!modalPayload.value.caisse_id) { alert('Veuillez choisir une caisse'); return }
    try {
        await paiementAchatService.validate(modalPaiement.value.id, Object.assign({}, modalPayload.value, { user_id: currentUser.value.id }))
        alert('Paiement validé')
        showModal.value = false
        await load()
    } catch (err) {
        console.error(err)
        alert(err.response?.data?.error || 'Erreur validation')
    }
}

const formatCurrency = (v) => {
    if (v === undefined || v === null) return '-'
    return new Intl.NumberFormat('fr-MG', {
        style: 'currency',
        currency: 'MGA',
        maximumFractionDigits: 0
    }).format(v)
}

const formatDate = (d) => {
    if (!d) return '-'
    return new Date(d).toLocaleDateString('fr-FR', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric'
    })
}

const getStatutClass = (status) => {
    const s = (status || '').toUpperCase()
    if (s.includes('VALID')) return 'badge-success'
    if (s.includes('ANNUL')) return 'badge-danger'
    if (s.includes('BROUILLON') || s.includes('ATTENTE')) return 'badge-warning'
    return 'badge-secondary'
}

const loadFilters = async () => {
    try {
        const resp = await paiementAchatService.filters()
        statutOptions.value = resp.data?.statuts || []
    } catch (err) {
        console.error('Erreur filtres', err)
    }
}

const exportPdf = () => {
    const base = import.meta.env.VITE_API_BASE_URL || '/api'
    const params = new URLSearchParams()
    if (filterDateDebut.value) params.set('date_debut', filterDateDebut.value)
    if (filterDateFin.value) params.set('date_fin', filterDateFin.value)
    if (filterStatut.value) params.set('statut_id', filterStatut.value)
    if (searchQuery.value) params.set('search', searchQuery.value)
    if (currentUser.value?.entreprise_id) params.set('entreprise_id', currentUser.value.entreprise_id)
    if (currentUser.value?.id) params.set('user_id', currentUser.value.id)

    const url = `${base}/paiements/achat/export${params.toString() ? ('?' + params.toString()) : ''}`
    window.open(url, '_blank')
}

onMounted(() => { load(); loadFilters() })
</script>

<style scoped>
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
    @apply flex items-center justify-between mb-6;
}

.page-title {
    @apply text-2xl font-bold text-gray-900 tracking-tight;
}

.page-subtitle {
    @apply text-sm text-gray-500 mt-1;
}

/* --- STATS --- */
.stats-grid {
    @apply grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4;
}

.stat-card {
    @apply bg-white rounded-xl border border-gray-200 p-5 hover:shadow-md transition-all flex justify-between items-start;
}

.stat-content {
    @apply space-y-1;
}

.stat-label {
    @apply text-xs font-bold text-gray-400 uppercase tracking-wide;
}

.stat-value {
    @apply text-xl font-bold text-gray-900;
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

/* --- FILTERS --- */
.filter-grid {
    @apply grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4;
}

.filter-item {
    @apply space-y-1.5;
}

.label {
    @apply block text-xs font-bold text-gray-500 uppercase ml-1;
}

.label.required::after {
    content: " *";
    @apply text-red-500;
}

.search-box {
    @apply flex items-center gap-2 px-3 py-2 bg-gray-50 border border-gray-200 rounded-lg focus-within:ring-2 focus-within:ring-indigo-500/20 focus-within:border-indigo-500 transition-all;
}

.search-input {
    @apply flex-1 bg-transparent border-none outline-none text-sm text-gray-700 placeholder-gray-400;
}

.input,
.select {
    @apply w-full px-3 py-2 bg-gray-50 border border-gray-200 rounded-lg text-sm font-medium text-gray-700 focus:bg-white focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 outline-none transition-all;
}

/* --- BUTTONS --- */
.btn {
    @apply flex items-center justify-center gap-2 rounded-lg font-medium transition-all active:scale-95;
}

.btn-sm {
    @apply px-3 py-1.5 text-xs;
}

.btn-md {
    @apply px-4 py-2 text-sm;
}

.btn-primary {
    @apply bg-indigo-600 text-white hover:bg-indigo-700 border border-transparent;
}

.btn-secondary {
    @apply bg-white text-gray-700 border border-gray-300 hover:bg-gray-50 hover:text-gray-900;
}

.btn-ghost {
    @apply bg-transparent text-gray-500 hover:bg-gray-100 hover:text-gray-900;
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

.table-actions {
    @apply flex items-center justify-center gap-1;
}

.action-btn {
    @apply p-1.5 rounded-md transition-colors;
}

/* --- BADGES --- */
.badge {
    @apply inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wide border;
}

.badge-success {
    @apply bg-emerald-50 text-emerald-700 border-emerald-200;
}

.badge-danger {
    @apply bg-rose-50 text-rose-700 border-rose-200;
}

.badge-warning {
    @apply bg-amber-50 text-amber-700 border-amber-200;
}

.badge-secondary {
    @apply bg-gray-50 text-gray-600 border-gray-200;
}

/* --- UTILS --- */
.loading-state {
    @apply flex flex-col items-center justify-center py-20;
}

.spinner {
    @apply w-10 h-10 border-4 border-gray-200 border-t-indigo-600 rounded-full animate-spin;
}

.loading-text {
    @apply mt-4 text-sm font-medium text-gray-500;
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