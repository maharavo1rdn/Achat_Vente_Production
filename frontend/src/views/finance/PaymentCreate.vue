<template>
    <div class="page-container">

        <!-- Header / Navigation -->
        <header class="header-section fade-in">
            <div class="max-w-5xl mx-auto flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div class="flex items-center gap-4">
                    <button @click="cancel" class="btn-back group" title="Retour">
                        <ArrowLeft class="w-5 h-5 text-gray-500 group-hover:text-indigo-600 transition-colors" />
                    </button>

                    <div>
                        <div class="flex items-center gap-2">
                            <h1 class="text-xl font-bold text-slate-800 tracking-tight">Encaisser un paiement</h1>
                            <span
                                :class="['type-badge', type === 'vente' ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700']">
                                {{ type === 'vente' ? 'Client' : 'Fournisseur' }}
                            </span>
                        </div>
                        <p class="text-xs text-slate-500 mt-1">Saisie du règlement pour la facture #{{
                            invoiceData?.numero_facture || factureId }}</p>
                    </div>
                </div>
            </div>
        </header>

        <!-- Main Content -->
        <main class="max-w-5xl mx-auto pb-12">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 fade-in" style="animation-delay: 0.1s">

                <!-- Left Col: Payment Form -->
                <div class="lg:col-span-2 space-y-6">
                    <div class="modern-card p-6 md:p-8">

                        <!-- Amount Input -->
                        <div class="mb-6">
                            <label class="label mb-2 block">Montant à régler</label>
                            <div class="relative group">
                                <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                    <span
                                        class="text-slate-400 font-bold text-lg group-focus-within:text-indigo-600 transition-colors">Ar</span>
                                </div>
                                <input v-model.number="form.montant" type="number" class="amount-input"
                                    placeholder="0.00" />
                            </div>
                            <!-- Validation Message -->
                            <div v-if="errorMessage"
                                class="flex items-center gap-2 mt-2 text-rose-600 bg-rose-50 p-2 rounded-lg text-xs font-medium">
                                <AlertCircle class="w-3.5 h-3.5" />
                                <span>{{ errorMessage }}</span>
                            </div>
                        </div>

                        <!-- Details Grid -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">

                            <!-- Mode de paiement -->
                            <div class="form-group">
                                <label class="label">Moyen de paiement</label>
                                <div class="relative">
                                    <!-- Icône positionnée à gauche -->
                                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                                        <CreditCard class="w-4 h-4 text-slate-500" />
                                    </div>
                                    <!-- Padding-left (pl-12) augmenté pour éviter le chevauchement -->
                                    <select v-model="form.mode_paiement_id" class="modern-select pl-12">
                                        <option v-for="m in modes" :key="m.id" :value="m.id">{{ m.libelle }}</option>
                                    </select>
                                </div>
                            </div>

                            <!-- Caisse -->
                            <div class="form-group">
                                <label class="label">Caisse</label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                                        <FileText class="w-4 h-4 text-slate-500" />
                                    </div>
                                    <select v-model="form.caisse_id" class="modern-select pl-12">
                                        <option v-for="c in caisses" :key="c.id" :value="c.id">{{ c.libelle }} ({{
                                            c.entreprise_nom }})</option>
                                        <option v-if="caisses.length === 0" disabled>Aucune caisse disponible</option>
                                    </select>
                                </div>
                                <p class="text-xs text-slate-400 mt-1">La caisse sélectionnée sera utilisée pour créer
                                    un mouvement en statut « En attente »</p>
                            </div>

                            <!-- Date -->
                            <div class="form-group">
                                <label class="label">Date de transaction</label>
                                <div class="relative">
                                    <!-- Icône positionnée à gauche -->
                                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                                        <Calendar class="w-4 h-4 text-slate-500" />
                                    </div>
                                    <!-- Padding-left (pl-12) augmenté pour éviter le chevauchement -->
                                    <input v-model="form.date_paiement" type="date" class="modern-input pl-12" />
                                </div>
                            </div>

                            <!-- Reference -->
                            <div class="form-group md:col-span-2">
                                <label class="label">Référence externe (Chèque, virement...)</label>
                                <input v-model="form.reference_externe" type="text" class="modern-input"
                                    placeholder="Ex: CHQ-882939 ou VIR-2024-X" />
                            </div>
                        </div>

                        <!-- Actions -->
                        <div class="mt-8 flex items-center gap-4 pt-6 border-t border-slate-100">
                            <button @click="submit" :disabled="saving"
                                class="btn btn-primary flex-1 justify-center py-2.5 text-sm shadow-lg shadow-indigo-200">
                                <CheckCircle2 class="w-4 h-4" v-if="!saving" />
                                <div v-else class="spinner-sm"></div>
                                <span>{{ saving ? 'Traitement...' : 'Confirmer le paiement' }}</span>
                            </button>
                            <button @click="cancel" class="btn btn-ghost px-6">
                                Annuler
                            </button>
                        </div>

                    </div>
                </div>

                <!-- Right Col: Invoice Summary -->
                <div class="lg:col-span-1">
                    <div class="modern-card bg-slate-50 border-slate-200 h-full">
                        <div class="p-6">
                            <h3 class="font-bold text-sm text-slate-800 flex items-center gap-2 mb-6">
                                <FileText class="w-4 h-4 text-indigo-500" />
                                Récapitulatif
                            </h3>

                            <div class="space-y-4">
                                <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm">
                                    <p class="text-[10px] text-slate-500 uppercase font-bold tracking-wider mb-1">
                                        Facture concernée</p>
                                    <p class="text-base font-mono font-bold text-slate-800">
                                        {{ invoiceData?.numero_facture || invoiceData?.numero_facture_fournisseur || '#' + factureId }}
                                    </p>
                                </div>

                                <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm">
                                    <p class="text-[10px] text-slate-500 uppercase font-bold tracking-wider mb-1">Reste
                                        à payer</p>
                                    <p class="text-xl font-bold text-indigo-600">
                                        {{ formatCurrency(originalResteAPayer) }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </main>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import {
    ArrowLeft, CreditCard, Calendar, CheckCircle2,
    FileText, AlertCircle
} from 'lucide-vue-next'
import modePaiementService from '@/services/modePaiementService'
import paiementVenteService from '@/services/paiementVenteService'
import paiementAchatService from '@/services/paiementAchatService'
import caisseService from '@/services/caisseService'

// Props définies par le routeur
const props = defineProps({
  type: { type: String, default: 'vente' },
  factureId: { type: Number, required: true }
})

const route = useRoute()
const router = useRouter()

// Utiliser les props
const type = props.type
const factureId = props.factureId

const modes = ref([])
const caisses = ref([])
const errorMessage = ref('')
const saving = ref(false)
const invoiceData = ref(null)
const originalResteAPayer = ref(0)

const form = ref({
    montant: 0,
    mode_paiement_id: null,
    caisse_id: null,
    reference_externe: '',
    date_paiement: new Date().toISOString().split('T')[0]
})

onMounted(async () => {
    try {
        // 1. Load modes
        const resp = await modePaiementService.getAll()
        modes.value = resp.data ?? resp
        if (modes.value.length) form.value.mode_paiement_id = modes.value[0].id

        // 2. Load caisses for current user's entreprise
        const user = JSON.parse(localStorage.getItem('user') || '{}')
        if (user && user.entreprise_id) {
            try {
                const cr = await caisseService.getCaisses(user.entreprise_id)
                caisses.value = cr.data ?? cr
                if (caisses.value.length) form.value.caisse_id = caisses.value[0].id
            } catch (e) {
                console.warn('Impossible de charger les caisses', e)
            }
        }

        // 3. Load facture details
        let f;
        console.log(type);
        if (type === 'vente') {
            console.log("ici");
            f = await import('@/services/venteService').then(m => m.default.facture.getById(factureId))
        } else {
            f = await import('@/services/achatService').then(m => m.default.facture.getById(factureId))
        }

        // Store invoice data
        invoiceData.value = f.data ?? f
        originalResteAPayer.value = invoiceData.value.reste_a_payer || 0
        form.value.montant = originalResteAPayer.value

    } catch (err) {
        console.error('Erreur initialisation paiement', err)
        errorMessage.value = "Impossible de charger les informations de la facture."
    }
})

const validateForm = () => {
    errorMessage.value = ''
    if (!factureId || factureId <= 0) {
        errorMessage.value = 'Facture invalide. Impossible d\'enregistrer le paiement.';
        return false
    }
    if (!form.value.montant || form.value.montant <= 0) {
        errorMessage.value = 'Le montant doit être supérieur à 0';
        return false
    }
    if (!form.value.mode_paiement_id) {
        errorMessage.value = 'Veuillez choisir un mode de paiement';
        return false
    }
    return true
}

const submit = async () => {
    if (!validateForm()) return
    saving.value = true
    try {
        const user = JSON.parse(localStorage.getItem('user') || '{}')

        const commonPayload = {
            mode_paiement_id: form.value.mode_paiement_id,
            montant: Number(form.value.montant),
            reference_externe: form.value.reference_externe,
            date_paiement: form.value.date_paiement,
            caisse_id: form.value.caisse_id ?? null,
            personnel_id: user?.id ?? null
        }

        let resp;
        let detailRoute;
        console.log('Creating paymen with id:', factureId, 'and type:', type)
        if (type === 'vente') {
            resp = await paiementVenteService.create({ ...commonPayload, facture_vente_id: factureId })
            detailRoute = 'paiement-vente-detail'
        } else {
            resp = await paiementAchatService.create({ ...commonPayload, facture_achat_id: factureId })
            detailRoute = 'paiement-achat-detail'
        }

        const id = Array.isArray(resp.data) ? resp.data[0] : (resp.data.id ?? resp.data)

        // Redirect with success
        router.push({ name: detailRoute, params: { id } })

    } catch (err) {
        console.error('Erreur création paiement', err)
        errorMessage.value = err.response?.data?.error || 'Une erreur est survenue lors de l\'enregistrement.'
    } finally {
        saving.value = false
    }
}

const cancel = () => router.back()

const formatCurrency = (val) => {
    if (val === undefined || val === null) return '0 Ar'
    return new Intl.NumberFormat('fr-MG', { style: 'currency', currency: 'MGA' }).format(val)
}
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

.type-badge {
    @apply px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider border border-current opacity-80;
}

/* --- CARDS --- */
.modern-card {
    @apply bg-white rounded-2xl shadow-lg shadow-slate-200/50 border border-slate-100 overflow-hidden;
}

/* --- INPUTS --- */
.form-group {
    @apply flex flex-col gap-1.5;
}

.label {
    @apply text-[11px] font-bold text-slate-500 uppercase tracking-wide ml-1;
}

.modern-input,
.modern-select {
    /* Padding vertical réduit, mais padding-left (pl) augmenté pour les icônes */
    @apply w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm font-medium text-slate-700 transition-all duration-200;
    @apply focus:bg-white focus:ring-4 focus:ring-indigo-500/10 focus:border-indigo-500 outline-none;
}

/* Utilitaire spécifique pour le padding gauche quand il y a une icône */
.pl-12 {
    padding-left: 3rem !important;
    /* Force le padding pour éviter le chevauchement */
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
    @apply flex items-center gap-2 px-5 py-2.5 rounded-xl text-sm font-bold transition-all duration-200 active:scale-95 border;
}

.btn-primary {
    @apply bg-indigo-600 text-white border-transparent hover:bg-indigo-700;
}

.btn-ghost {
    @apply bg-transparent border-transparent text-slate-500 hover:bg-slate-100;
}

/* --- UTILITIES --- */
.spinner-sm {
    @apply w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin;
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