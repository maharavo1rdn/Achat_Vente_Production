<template>
    <div class="page-container">

        <!-- Loading State -->
        <div v-if="loading" class="loading-state">
            <div class="spinner"></div>
            <p class="loading-text">Chargement de la transaction...</p>
        </div>

        <!-- Content -->
        <div v-else class="content-wrapper">

            <!-- Header / Navigation -->
            <header class="header-section fade-in">
                <div class="max-w-5xl mx-auto flex flex-col md:flex-row md:items-center justify-between gap-4">

                    <!-- Left: Title & Context -->
                    <div class="flex items-center gap-4">
                        <button @click="goBack" class="btn-back group" title="Retour">
                            <ArrowLeft class="w-5 h-5 text-gray-500 group-hover:text-indigo-600 transition-colors" />
                        </button>

                        <div class="flex flex-col">
                            <div class="flex items-center gap-3">
                                <h1 class="text-xl font-bold text-slate-800 tracking-tight">Mouvement de Caisse</h1>
                                <span :class="['status-pill', getStatutBadgeClass(mouvement?.statut_id ?? mouvement?.statut_code)]">
                                    {{ mouvement?.statut_libelle }}
                                </span>
                            </div>
                            <div class="flex items-center gap-2 text-xs text-slate-500 mt-1">
                                <Calendar class="w-3.5 h-3.5" />
                                <span>{{ formatDate(mouvement?.date_mouvement) }}</span>
                                <span class="text-slate-300">|</span>
                                <span class="font-mono">#{{ mouvement?.id }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Right: Actions Bar -->
                    <div class="action-bar flex items-center gap-3">

                        <!-- Actions Mode Lecture -->
                        <template v-if="!showEditForm">
                            <button v-if="mouvement?.statut_id === 2" @click="showEditForm = true"
                                class="btn btn-secondary">
                                <Pencil class="w-4 h-4" />
                                <span>Modifier</span>
                            </button>

                            <button v-if="mouvement?.statut_id === 2" @click="doValidate"
                                :disabled="validating" class="btn btn-primary shadow-lg shadow-indigo-200">
                                <div v-if="validating" class="spinner-sm"></div>
                                <CheckCircle2 v-else class="w-4 h-4" />
                                <span>{{ validating ? 'Validation...' : 'Valider' }}</span>
                            </button>
                        </template>

                        <!-- Actions Mode Édition -->
                        <template v-if="showEditForm">
                            <button @click="showEditForm = false" class="btn btn-ghost">
                                Annuler
                            </button>
                            <button @click="saveChanges" :disabled="saving" class="btn btn-primary">
                                <div v-if="saving" class="spinner-sm"></div>
                                <Save v-else class="w-4 h-4" />
                                <span>Enregistrer</span>
                            </button>
                        </template>

                    </div>
                </div>
            </header>

            <!-- Main Content -->
            <main class="max-w-5xl mx-auto pb-12 space-y-6 fade-in" style="animation-delay: 0.1s">

                <!-- MODE LECTURE -->
                <div v-if="!showEditForm" class="grid grid-cols-1 md:grid-cols-3 gap-6">

                    <!-- Carte Montant (Hero) - Couleur dynamique selon Entrée/Sortie -->
                    <div
                        class="modern-card p-6 md:col-span-1 border-slate-200 bg-gradient-to-br from-white to-slate-50 relative overflow-hidden">
                        <!-- Decorative line -->
                        <div class="absolute top-0 left-0 w-full h-1"
                            :class="isEntree ? 'bg-emerald-500' : 'bg-rose-500'"></div>

                        <div class="flex flex-col h-full justify-between relative z-10">
                            <div>
                                <p class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">
                                    {{ isEntree ? 'Montant Crédité' : 'Montant Débité' }}
                                </p>
                                <div class="text-3xl font-bold tracking-tight"
                                    :class="isEntree ? 'text-emerald-600' : 'text-rose-600'">
                                    {{ formatCurrency(isEntree ? mouvement?.montant_entree : mouvement?.montant_sortie) }}
                                </div>
                            </div>

                            <div class="mt-6 pt-6 border-t border-slate-200">
                                <div class="flex items-center gap-3">
                                    <div class="p-2 rounded-lg"
                                        :class="isEntree ? 'bg-emerald-50 text-emerald-600' : 'bg-rose-50 text-rose-600'">
                                        <TrendingUp v-if="isEntree" class="w-5 h-5" />
                                        <TrendingDown v-else class="w-5 h-5" />
                                    </div>
                                    <div>
                                        <p class="text-xs text-slate-500">Type d'opération</p>
                                        <p class="font-medium text-slate-800">{{ isEntree ? 'Entrée de fonds' : 'Sortie de fonds' }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Carte Détails -->
                    <div class="modern-card p-6 md:col-span-2">
                        <h3
                            class="font-bold text-sm text-slate-800 flex items-center gap-2 mb-6 border-b border-slate-100 pb-3">
                            <FileText class="w-4 h-4 text-slate-400" />
                            Détails de l'opération
                        </h3>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-y-6 gap-x-8">
                            <div class="detail-group sm:col-span-2">
                                <span class="detail-label">Libellé / Motif</span>
                                <span class="detail-value text-lg text-slate-900">{{ mouvement?.libelle_operation
                                    }}</span>
                            </div>

                            <div class="detail-group">
                                <span class="detail-label">Compte de Caisse</span>
                                <div class="flex items-center gap-2">
                                    <Wallet class="w-4 h-4 text-slate-400" />
                                    <span class="detail-value">{{ mouvement?.caisse_libelle }}</span>
                                </div>
                                <span class="text-xs text-slate-400 font-mono mt-0.5">{{ mouvement?.code_caisse
                                    }}</span>
                            </div>

                            <div class="detail-group">
                                <span class="detail-label">Enregistré par</span>
                                <div class="flex items-center gap-2">
                                    <User class="w-4 h-4 text-slate-400" />
                                    <span class="detail-value">{{ mouvement?.personnel_prenom }} {{
                                        mouvement?.personnel_nom
                                        }}</span>
                                </div>
                            </div>

                            <div class="detail-group">
                                <span class="detail-label">Solde Avant Opération</span>
                                <span class="detail-value font-mono text-slate-500">{{
                                    formatCurrency(mouvement?.solde_avant)
                                    }}</span>
                            </div>

                            <div class="detail-group">
                                <span class="detail-label">Solde Après Opération</span>
                                <span
                                    class="detail-value font-mono font-bold text-indigo-600 bg-indigo-50 px-2 py-0.5 rounded w-fit">
                                    {{ formatCurrency(mouvement?.solde_apres) }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- MODE ÉDITION -->
                <div v-if="showEditForm" class="modern-card p-6 md:p-8 border-indigo-200 shadow-indigo-100">
                    <h3 class="font-bold text-lg text-slate-800 mb-6 flex items-center gap-2">
                        <Pencil class="w-5 h-5 text-indigo-600" />
                        Modifier le mouvement
                    </h3>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="md:col-span-2 form-group">
                            <label class="label">Libellé de l'opération</label>
                            <input type="text" v-model="editForm.libelle_operation" class="modern-input"
                                placeholder="Description du mouvement" />
                        </div>

                        <div class="form-group">
                            <label class="label text-emerald-600">Montant Entrée (+)</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                                    <TrendingUp class="w-4 h-4 text-emerald-500" />
                                </div>
                                <input type="number" v-model="editForm.montant_entree"
                                    class="modern-input pl-11 focus:border-emerald-500 focus:ring-emerald-500/20"
                                    step="0.01" />
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="label text-rose-600">Montant Sortie (-)</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                                    <TrendingDown class="w-4 h-4 text-rose-500" />
                                </div>
                                <input type="number" v-model="editForm.montant_sortie"
                                    class="modern-input pl-11 focus:border-rose-500 focus:ring-rose-500/20"
                                    step="0.01" />
                            </div>
                        </div>
                    </div>

                    <div class="mt-4 p-4 bg-slate-50 rounded-lg text-xs text-slate-500 flex items-start gap-2">
                        <Info class="w-4 h-4 text-slate-400 flex-shrink-0 mt-0.5" />
                        <p>Attention : La modification des montants recalculera automatiquement les soldes lors de la
                            validation.
                            Assurez-vous de saisir le montant dans un seul des champs (Entrée OU Sortie).</p>
                    </div>
                </div>

            </main>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import {
    ArrowLeft, Pencil, CheckCircle2, Save, X,
    Calendar, Wallet, User, FileText,
    TrendingUp, TrendingDown, Info
} from 'lucide-vue-next'
import caisseService from '@/services/caisseService'

const router = useRouter()
const route = useRoute()

const loading = ref(true)
const validating = ref(false)
const saving = ref(false)
const mouvement = ref(null)
const showEditForm = ref(false)
const editForm = ref({
    libelle_operation: '',
    montant_entree: 0,
    montant_sortie: 0
})

// Computed helper to detect type
const isEntree = computed(() => {
    return parseFloat(mouvement.value?.montant_entree || 0) > 0
})

async function loadMouvement() {
    loading.value = true
    try {
        const mouvementId = parseInt(route.params.id)
        const resp = await caisseService.getMouvementById(mouvementId)
        mouvement.value = resp.data ?? resp
        console.log('Mouvement chargé:', mouvement.value)
        // Initialize edit form
        editForm.value = {
            libelle_operation: mouvement.value.libelle_operation,
            montant_entree: parseFloat(mouvement.value.montant_entree || 0),
            montant_sortie: parseFloat(mouvement.value.montant_sortie || 0)
        }
    } catch (err) {
        console.error('Erreur chargement mouvement:', err)
        // alert(err.response?.data?.error || 'Erreur lors du chargement') 
    } finally {
        loading.value = false
    }
}

async function saveChanges() {
    if (!editForm.value.libelle_operation.trim()) {
        alert('Le libellé de l\'opération est obligatoire')
        return
    }

    saving.value = true
    try {
        await caisseService.updateMouvement(mouvement.value.id, editForm.value)
        showEditForm.value = false
        await loadMouvement()
    } catch (err) {
        console.error('Erreur modification:', err)
        alert(err.response?.data?.error || 'Erreur lors de la modification')
    } finally {
        saving.value = false
    }
}

async function doValidate() {
    if (!confirm('Confirmer la validation de ce mouvement de caisse ?'))
        return
    
    validating.value = true
    try {
        await caisseService.validateMouvement(mouvement.value.id)
        await loadMouvement()
    } catch (err) {
        console.error('Erreur validation:', err)
        alert(err.response?.data?.error || 'Erreur lors de la validation')
    } finally {
        validating.value = false
    }
}

function goBack() {
    router.push({ name: 'mouvements-caisse' }) // Ensure this route name exists
}

function getStatutBadgeClass(s) {
    // support numeric statut_id first (recommended), else fallback to code string (case-insensitive)
    if (typeof s === 'number') {
        switch (s) {
            case 3: return 'pill-success' // VALIDE
            case 2: return 'pill-warning' // EN_ATTENTE
            case 7: return 'pill-danger' // ANNULE
            default: return 'pill-default'
        }
    }

    const code = (s || '').toString().toLowerCase()
    if (code.includes('valide') || code === 'valide') return 'pill-success'
    if (code.includes('attente') || code.includes('en_attente')) return 'pill-warning'
    if (code.includes('annul') || code.includes('annule')) return 'pill-danger'
    return 'pill-default'
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
        day: '2-digit', month: '2-digit', year: 'numeric',
        hour: '2-digit', minute: '2-digit'
    }).format(date)
}

onMounted(() => {
    loadMouvement()
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

/* --- DETAILS --- */
.detail-group {
    @apply flex flex-col gap-1;
}

.detail-label {
    @apply text-[11px] font-bold text-slate-400 uppercase tracking-wide;
}

.detail-value {
    @apply text-sm font-semibold text-slate-800 break-words;
}

/* --- INPUTS --- */
.form-group {
    @apply flex flex-col gap-1.5;
}

.label {
    @apply text-[11px] font-bold text-slate-500 uppercase tracking-wide ml-1;
}

.modern-input {
    @apply w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm font-medium text-slate-700 transition-all duration-200;
    @apply focus:bg-white focus:ring-4 focus:ring-indigo-500/10 focus:border-indigo-500 outline-none;
}

.pl-11 {
    padding-left: 2.75rem !important;
}

/* fix number inputs */
input[type=number]::-webkit-inner-spin-button,
input[type=number]::-webkit-outer-spin-button {
    -webkit-appearance: none;
    margin: 0;
}

input[type=number] {
    -moz-appearance: textfield;
}

/* --- BUTTONS --- */
.btn {
    @apply flex items-center gap-2 px-4 py-2 rounded-xl text-sm font-bold transition-all duration-200 active:scale-95 border;
}

.btn-primary {
    @apply bg-indigo-600 text-white border-transparent hover:bg-indigo-700;
}

.btn-secondary {
    @apply bg-white text-slate-700 border-slate-200 hover:bg-slate-50;
}

.btn-ghost {
    @apply bg-transparent border-transparent text-slate-500 hover:bg-slate-100;
}

/* --- BADGES --- */
.status-pill {
    @apply px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-widest border;
}

.pill-success {
    @apply bg-emerald-50 text-emerald-700 border-emerald-200;
}

.pill-warning {
    @apply bg-amber-50 text-amber-700 border-amber-200;
}

.pill-danger {
    @apply bg-rose-50 text-rose-700 border-rose-200;
}

.pill-default {
    @apply bg-slate-100 text-slate-600 border-slate-200;
}

/* --- UTILITIES --- */
.spinner-sm {
    @apply w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin;
}

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