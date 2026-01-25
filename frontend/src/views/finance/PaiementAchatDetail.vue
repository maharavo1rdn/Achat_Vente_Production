<template>
    <div class="page-container">

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
                            <h1 class="text-xl font-bold text-slate-800 tracking-tight">Détail Paiement Fournisseur</h1>
                            <span :class="['status-pill', getStatutClass(paiement.statut_libelle)]">
                                {{ paiement.statut_libelle }}
                            </span>
                        </div>
                        <div class="flex items-center gap-2 text-xs text-slate-500 mt-1">
                            <span class="font-mono bg-slate-100 px-1.5 py-0.5 rounded text-slate-600">
                                #{{ paiement.numero_recu || paiement.id }}
                            </span>
                            <span>&bull;</span>
                            <span>Enregistré le {{ formatDate(paiement.created_at || paiement.date_paiement) }}</span>
                        </div>
                    </div>
                </div>

                <!-- Right: Actions Bar -->
                <div class="action-bar flex items-center gap-3">
                    <!-- Actions Mode Lecture -->
                    <template v-if="!editing && !showValidate">
                        <button v-if="canEdit" @click="startEdit" class="btn btn-secondary">
                            <Pencil class="w-4 h-4" />
                            <span>Modifier</span>
                        </button>
                        <button v-if="canValidate" @click="openValidate" :disabled="validating"
                            class="btn btn-primary shadow-lg shadow-indigo-200">
                            <template v-if="validating">
                                <svg class="animate-spin w-4 h-4 mr-2 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"></path></svg>
                                <span>Validation en cours...</span>
                            </template>
                            <template v-else>
                                <CheckCircle2 class="w-4 h-4" />
                                <span>Valider le décaissement</span>
                            </template>
                        </button>
                    </template>

                    <!-- Actions Mode Édition -->
                    <template v-if="editing">
                        <button @click="cancelEdit" class="btn btn-ghost">Annuler</button>
                        <button @click="saveEdit" class="btn btn-primary">
                            <Save class="w-4 h-4" />
                            <span>Enregistrer</span>
                        </button>
                    </template>
                </div>
            </div>
        </header>

        <!-- Main Content -->
        <main class="max-w-5xl mx-auto pb-12 space-y-6 fade-in" style="animation-delay: 0.1s">

            <!-- MODE LECTURE -->
            <div v-if="!editing && !showValidate" class="grid grid-cols-1 md:grid-cols-3 gap-6">

                <!-- Carte Montant (Hero) -->
                <div class="modern-card p-6 md:col-span-1 bg-gradient-to-br from-white to-slate-50 border-slate-200">
                    <div class="flex flex-col h-full justify-between">
                        <div>
                            <p class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Montant du
                                règlement</p>
                            <div class="text-3xl font-bold text-slate-800 tracking-tight">
                                {{ formatCurrency(paiement.montant) }}
                            </div>
                        </div>
                        <div class="mt-6 pt-6 border-t border-slate-200">
                            <div class="flex items-center gap-3">
                                <div class="p-2 bg-slate-100 rounded-lg text-slate-600">
                                    <CreditCard class="w-5 h-5" />
                                </div>
                                <div>
                                    <p class="text-xs text-slate-500">Mode de paiement</p>
                                    <p class="font-medium text-slate-800">{{ paiement.mode_paiement_libelle || '-' }}
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
                        Informations Générales
                    </h3>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-y-6 gap-x-8">
                        <div class="detail-group">
                            <span class="detail-label">Facture Achat</span>
                            <span class="detail-value font-mono text-indigo-600 bg-indigo-50 px-2 py-0.5 rounded w-fit">
                                {{ paiement.numero_facture || '#' + paiement.facture_achat_id }}
                            </span>
                        </div>

                        <div class="detail-group">
                            <span class="detail-label">Date du paiement</span>
                            <span class="detail-value flex items-center gap-2">
                                <Calendar class="w-4 h-4 text-slate-400" />
                                {{ formatDate(paiement.date_paiement) }}
                            </span>
                        </div>

                        <div class="detail-group sm:col-span-2">
                            <span class="detail-label">Référence Externe</span>
                            <span class="detail-value">{{ paiement.reference_externe || 'Aucune référence' }}</span>
                        </div>
                    </div>

                    <!-- Section Mouvement Caisse (Si validé) -->
                    <div v-if="paiement.caisse_mouvement" class="mt-8 pt-6 border-t border-slate-100">
                        <h4
                            class="font-bold text-xs text-emerald-600 flex items-center gap-2 mb-4 uppercase tracking-wide">
                            <Check class="w-3.5 h-3.5" /> Mouvement de caisse validé
                        </h4>
                        <div
                            class="bg-slate-50 rounded-xl p-4 border border-slate-200 grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <p class="text-xs text-slate-500">Caisse concernée</p>
                                <p class="font-medium text-slate-800">{{ paiement.caisse_mouvement.caisse_libelle }}</p>
                            </div>
                            <div>
                                <p class="text-xs text-slate-500">Type de mouvement</p>
                                <span class="inline-flex items-center gap-1 text-sm font-medium"
                                    :class="Number(paiement.caisse_mouvement.montant_entree) > 0 ? 'text-emerald-600' : 'text-rose-600'">
                                    {{ Number(paiement.caisse_mouvement.montant_entree) > 0 ? 'Entrée (+)' : 'Sortie(-)' }}
                                </span>
                            </div>
                            <div class="sm:col-span-2">
                                <p class="text-xs text-slate-500">Montant Mouvement</p>
                                <p class="font-mono font-bold text-slate-700">
                                    {{ formatCurrency(paiement.caisse_mouvement.entree ||
                                        paiement.caisse_mouvement.sortie) }}
                                </p>
                            </div>
                            <div class="sm:col-span-2">
                                <p class="text-xs text-slate-500">Fait(e) par</p>
                                <div class="flex items-center gap-2 mt-1">
                                    <div
                                        class="w-6 h-6 rounded-full bg-slate-200 flex items-center justify-center text-slate-600 text-xs font-bold">
                                        <User class="w-3 h-3" />
                                    </div>
                                    <span class="text-sm text-slate-700">{{ personnelFullName(paiement.caisse_mouvement)
                                        }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- MODE ÉDITION -->
            <div v-if="editing" class="modern-card p-6 md:p-8 border-indigo-200 shadow-indigo-100">
                <h3 class="font-bold text-lg text-slate-800 mb-6 flex items-center gap-2">
                    <Pencil class="w-5 h-5 text-indigo-600" />
                    Modifier le paiement
                </h3>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Montant -->
                    <div class="md:col-span-2">
                        <label class="label">Montant</label>
                        <div class="relative group">
                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                <span
                                    class="text-slate-400 font-bold text-lg group-focus-within:text-indigo-600 transition-colors">Ar</span>
                            </div>
                            <input v-model.number="editModel.montant" type="number" class="amount-input" />
                        </div>
                    </div>

                    <!-- Mode -->
                    <div class="form-group">
                        <label class="label">Mode de paiement</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                                <CreditCard class="w-4 h-4 text-slate-500" />
                            </div>
                            <select v-model="editModel.mode_paiement_id" class="modern-select pl-12">
                                <option v-for="m in modes" :key="m.id" :value="m.id">{{ m.libelle }}</option>
                            </select>
                        </div>
                    </div>

                    <!-- Date -->
                    <div class="form-group">
                        <label class="label">Date paiement</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                                <Calendar class="w-4 h-4 text-slate-500" />
                            </div>
                            <input v-model="editModel.date_paiement" type="date" class="modern-input pl-12" />
                        </div>
                    </div>

                    <!-- Référence -->
                    <div class="form-group">
                        <label class="label">Référence externe</label>
                        <input v-model="editModel.reference_externe" type="text" class="modern-input"
                            placeholder="Ex: Chèque n°..." />
                    </div>

                    <!-- N° Reçu -->
                    <div class="form-group">
                        <label class="label">Numéro de Reçu</label>
                        <input v-model="editModel.numero_recu" type="text" class="modern-input" />
                    </div>
                </div>
            </div>

            <!-- MODE VALIDATION -->
            <div v-if="showValidate && !paiement.caisse_mouvement" class="modern-card p-6 md:p-8 bg-slate-50 border-slate-200">
                <div class="flex items-start justify-between mb-6">
                    <div>
                        <h3 class="font-bold text-lg text-slate-800 flex items-center gap-2">
                            <CheckCircle2 class="w-5 h-5 text-emerald-600" />
                            Validation et Décaissement
                        </h3>
                        <p class="text-sm text-slate-500 mt-1">Cette action va enregistrer une sortie de caisse.</p>
                    </div>
                    <button @click="cancelValidate" class="text-slate-400 hover:text-slate-600">
                        <X class="w-5 h-5" />
                    </button>
                </div>

                <div
                    class="grid grid-cols-1 md:grid-cols-2 gap-6 bg-white p-6 rounded-xl border border-slate-200 shadow-sm">
                    <div class="form-group md:col-span-2">
                        <label class="label required">Sélectionner la Caisse (Source)</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                                <Wallet class="w-4 h-4 text-slate-500" />
                            </div>
                            <select v-model="validatePayload.caisse_id" class="modern-select pl-12">
                                <option :value="null">-- Choisir une caisse --</option>
                                <option v-for="c in caisses" :key="c.id" :value="c.id">
                                    {{ c.nom }} (Solde: {{ formatCurrency(c.solde_actuel) }})
                                </option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="label">Libellé de l'opération</label>
                        <input v-model="validatePayload.libelle" class="modern-input"
                            placeholder="Règlement facture..." />
                    </div>

                    <!-- Personnel -->
                    <div class="form-group">
                        <label class="label">Validateur</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                                <User class="w-4 h-4 text-slate-500" />
                            </div>
                            <input :value="currentUser?.nom || 'Utilisateur connecté'" readonly
                                class="modern-input pl-12 bg-slate-100 text-slate-500 cursor-not-allowed" />
                        </div>
                    </div>
                </div>

                <div class="flex justify-end gap-3 mt-6">
                    <button @click="cancelValidate" class="btn btn-ghost">Annuler</button>
                    <button @click="doValidate" :disabled="validating" class="btn btn-primary bg-emerald-600 hover:bg-emerald-700">
                        <template v-if="validating">
                            <svg class="animate-spin w-4 h-4 mr-2 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"></path></svg>
                            <span>Validation en cours...</span>
                        </template>
                        <template v-else>
                            Confirmer la sortie
                        </template>
                    </button>
                </div>
            </div>

        </main>
    </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import {
    ArrowLeft, Pencil, Save, CheckCircle2, X, Wallet,
    CreditCard, Calendar, FileText, Check, User
} from 'lucide-vue-next'

import paiementAchatService from '@/services/paiementAchatService'
import caisseService from '@/services/caisseService'

const route = useRoute()
const router = useRouter()
const paiement = ref({})
const caisses = ref([])
const modes = ref([])
const showValidate = ref(false)
const validatePayload = ref({ caisse_id: null, personnel_id: null, libelle: '' })
const editing = ref(false)
const editModel = ref({ montant: 0, mode_paiement_id: null, reference_externe: '', numero_recu: '', date_paiement: '' })
const validating = ref(false) // UI state while validating
// Computed
const canEdit = computed(() => paiement.value.statut_id !== 3) // 3 = Validé
const canValidate = computed(() => paiement.value.statut_id !== 3 && !editing.value)
const currentUser = ref(JSON.parse(localStorage.getItem('user') || '{}'))

// Methods
const load = async () => {
    try {
        const resp = await paiementAchatService.getById(route.params.id)
        paiement.value = resp.data ?? resp

        // Populate edit model
        editModel.value = {
            montant: paiement.value.montant || 0,
            mode_paiement_id: paiement.value.mode_paiement_id || null,
            reference_externe: paiement.value.reference_externe || '',
            numero_recu: paiement.value.numero_recu || '',
            date_paiement: paiement.value.date_paiement ? new Date(paiement.value.date_paiement).toISOString().split('T')[0] : ''
        }
    } catch (err) {
        console.error('Erreur chargement paiement', err)
    }
}

const loadCaisses = async () => {
    try {
        const resp = await caisseService.getCaisses()
        caisses.value = resp.data ?? resp
    } catch (err) {
        console.error('Erreur chargement caisses', err)
    }
}

const loadFilters = async () => {
    try {
        const resp = await paiementAchatService.filters()
        modes.value = resp.data?.modes || []
        if (modes.value.length === 0) {
            const mService = await import('@/services/modePaiementService')
            const mResp = await mService.default.getAll()
            modes.value = mResp.data ?? mResp
        }
    } catch (err) {
        console.error('Erreur chargement filtres', err)
    }
}

const personnelFullName = (m) => {
    if (!m) return '-'
    const n = ((m.personnel_nom || '') + ' ' + (m.personnel_prenom || '')).trim()
    return n || '-'
}

const startEdit = () => { editing.value = true }

const cancelEdit = () => {
    editing.value = false
    load()
}

const saveEdit = async () => {
    try {
        await paiementAchatService.update(paiement.value.id, editModel.value)
        editing.value = false
        await load()
    } catch (err) {
        console.error(err)
        alert(err.response?.data?.error || 'Erreur lors de la sauvegarde')
    }
}

const openValidate = async () => {
    if (!currentUser.value || !currentUser.value.id) { alert('Vous devez être connecté pour valider'); return }

    // If a caisse movement exists, validate directly
    if (paiement.value.caisse_mouvement) {
                validating.value = true
        let success = false
        try {
            console.log('Validating paiement achat (direct) id=', paiement.value.id)
            await paiementAchatService.validate(paiement.value.id, { user_id: currentUser.value.id })
            alert('Paiement validé')
            success = true
        } catch (err) {
            console.error(err)
            alert(err.response?.data?.error || 'Erreur lors de la validation')
        } finally {
            validating.value = false
        }
        if (success) router.push({ name: 'paiements-achat' })
        return
    }

    validatePayload.value.personnel_id = currentUser.value.id || null
    validatePayload.value.libelle = `Règlement Facture ${paiement.value.numero_facture || ''}`
    showValidate.value = true
}

const cancelValidate = () => showValidate.value = false

const doValidate = async () => {
    if (!currentUser.value || !currentUser.value.id) { alert('Vous devez être connecté pour valider'); return }
    if (!validatePayload.value.caisse_id) {
        alert("Veuillez sélectionner une caisse"); return;
    }
    validating.value = true
    try {
        await paiementAchatService.validate(paiement.value.id, Object.assign({}, validatePayload.value, { user_id: currentUser.value.id }))
        showValidate.value = false
        await load()
    } catch (err) {
        console.error(err)
        alert(err.response?.data?.error || 'Erreur lors de la validation')
    } finally {
        validating.value = false
    }
}

const goBack = () => {
    router.push({ name: 'paiements-achat' })
}

const formatCurrency = (v) => {
    if (v === undefined || v === null) return '-'
    return new Intl.NumberFormat('fr-MG', { style: 'currency', currency: 'MGA' }).format(v)
}

const formatDate = (d) => {
    if (!d) return '-'
    return new Date(d).toLocaleDateString('fr-FR', { day: '2-digit', month: '2-digit', year: 'numeric' })
}

const getStatutClass = (status) => {
    const s = (status || '').toUpperCase()
    if (s.includes('VALID')) return 'pill-success'
    if (s.includes('ANNUL')) return 'pill-danger'
    return 'pill-warning'
}

onMounted(() => {
    load()
    loadCaisses()
    loadFilters()
})
</script>

<style scoped >
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

.label.required::after {
    content: " *";
    @apply text-rose-500;
}

.modern-input,
.modern-select {
    @apply w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm font-medium text-slate-700 transition-all duration-200;
    @apply focus:bg-white focus:ring-4 focus:ring-indigo-500/10 focus:border-indigo-500 outline-none;
}

.pl-12 {
    padding-left: 3rem !important;
}

.modern-select {
    @apply appearance-none;
}

/* --- HERO AMOUNT INPUT --- */
.amount-input {
    @apply w-full pl-12 pr-4 py-3 text-2xl font-bold text-slate-800 bg-white border-2 border-slate-200 rounded-2xl transition-all shadow-sm;
    @apply focus:border-indigo-600 focus:ring-0 outline-none placeholder:text-slate-300;
}

/* Fix input number spinner */
input[type=number]::-webkit-inner-spin-button,
input[type=number]::-webkit-outer-spin-button {
    -webkit-appearance: none;
    margin: 0;
    background: transparent !important;
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

/* --- STATUS PILLS --- */
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

/* --- UTILITIES --- */
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