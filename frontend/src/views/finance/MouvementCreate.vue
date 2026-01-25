<template>
  <div class="page-container">

    <!-- Header / Navigation -->
    <header class="header-section fade-in">
      <div class="max-w-5xl mx-auto flex items-center justify-between gap-4">
        <div class="flex items-center gap-4">
          <button @click="cancel" class="btn-back group" title="Retour">
            <ArrowLeft class="w-5 h-5 text-gray-500 group-hover:text-indigo-600 transition-colors" />
          </button>

          <div>
            <h1 class="text-xl font-bold text-slate-800 tracking-tight">Nouveau Mouvement</h1>
            <p class="text-xs text-slate-500 mt-1">Enregistrement d'un flux de trésorerie</p>
          </div>
        </div>
      </div>
    </header>

    <!-- Main Content -->
    <main class="max-w-5xl mx-auto pb-12">
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 fade-in" style="animation-delay: 0.1s">

        <!-- Formulaire (Gauche) -->
        <div class="lg:col-span-2 space-y-6">
          <div class="modern-card p-6 md:p-8">

            <div class="mb-6 border-b border-slate-100 pb-4">
              <h3 class="text-lg font-bold text-slate-800 flex items-center gap-2">
                <FileText class="w-5 h-5 text-indigo-500" />
                Détails de l'opération
              </h3>
              <p class="text-xs text-slate-500 mt-1">Remplissez les informations relatives au mouvement de fonds.</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">

              <!-- Caisse -->
              <div class="form-group md:col-span-2">
                <label class="label required">Compte de Caisse</label>
                <div class="relative">
                  <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                    <Wallet class="w-4 h-4 text-slate-400" />
                  </div>
                  <select v-model="form.caisse_id" class="modern-select pl-12">
                    <option :value="null">-- Sélectionner une caisse --</option>
                    <option v-for="c in caisses" :key="c.id" :value="c.id">
                      {{ c.libelle }} ({{ c.entreprise_nom }})
                    </option>
                  </select>
                </div>
              </div>

              <!-- Date -->
              <div class="form-group">
                <label class="label">Date de l'opération</label>
                <div class="relative">
                  <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                    <Calendar class="w-4 h-4 text-slate-400" />
                  </div>
                  <input v-model="form.date_mouvement" type="date" class="modern-input pl-12" />
                </div>
              </div>

              <!-- Personnel -->
              <div class="form-group">
                <label class="label">Responsable</label>
                <div class="relative">
                  <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                    <User class="w-4 h-4 text-slate-400" />
                  </div>
                  <select v-model="form.personnel_id" class="modern-select pl-12">
                    <option v-for="p in personnel" :key="p.id" :value="p.id">{{ p.prenom }} {{ p.nom }}</option>
                  </select>
                </div>
              </div>

              <!-- Libellé -->
              <div class="form-group md:col-span-2">
                <label class="label required">Libellé / Motif</label>
                <input v-model="form.libelle_operation" type="text" class="modern-input"
                  placeholder="Ex: Alimentation caisse, Achat fournitures..." />
              </div>

              <!-- Séparateur visuel -->
              <div class="md:col-span-2 py-2 flex items-center gap-4">
                <div class="h-px bg-slate-100 flex-1"></div>
                <span class="text-xs font-bold text-slate-400 uppercase">Saisie des montants</span>
                <div class="h-px bg-slate-100 flex-1"></div>
              </div>

              <!-- Montant Entrée -->
              <div class="form-group">
                <label class="label text-emerald-600">Montant Entrée (+)</label>
                <div class="relative group">
                  <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                    <TrendingUp class="w-4 h-4 text-emerald-500" />
                  </div>
                  <input v-model.number="form.montant_entree" type="number" step="0.01" placeholder="0.00"
                    class="modern-input pl-12 font-bold text-emerald-700 focus:border-emerald-500 focus:ring-emerald-500/20 bg-emerald-50/30" />
                  <div class="absolute inset-y-0 right-0 pr-3.5 flex items-center pointer-events-none">
                    <span class="text-xs font-bold text-emerald-600">Ar</span>
                  </div>
                </div>
              </div>

              <!-- Montant Sortie -->
              <div class="form-group">
                <label class="label text-rose-600">Montant Sortie (-)</label>
                <div class="relative group">
                  <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                    <TrendingDown class="w-4 h-4 text-rose-500" />
                  </div>
                  <input v-model.number="form.montant_sortie" type="number" step="0.01" placeholder="0.00"
                    class="modern-input pl-12 font-bold text-rose-700 focus:border-rose-500 focus:ring-rose-500/20 bg-rose-50/30" />
                  <div class="absolute inset-y-0 right-0 pr-3.5 flex items-center pointer-events-none">
                    <span class="text-xs font-bold text-rose-600">Ar</span>
                  </div>
                </div>
              </div>

            </div>

            <!-- Actions -->
            <div class="mt-8 flex items-center gap-4 pt-6 border-t border-slate-100">
              <button @click="submit" :disabled="saving"
                class="btn btn-primary flex-1 justify-center py-2.5 text-sm shadow-lg shadow-indigo-200">
                <CheckCircle2 class="w-4 h-4" v-if="!saving" />
                <div v-else class="spinner-sm"></div>
                <span>{{ saving ? 'Traitement...' : 'Enregistrer le mouvement' }}</span>
              </button>
              <button @click="cancel" class="btn btn-ghost px-6">Annuler</button>
            </div>

          </div>
        </div>

        <!-- Récapitulatif (Droite) -->
        <div class="lg:col-span-1">
          <div class="modern-card bg-slate-50 border-slate-200 h-full sticky top-24">
            <div class="p-6">
              <h3 class="font-bold text-sm text-slate-800 flex items-center gap-2 mb-6">
                <Activity class="w-4 h-4 text-indigo-500" />
                Aperçu de la transaction
              </h3>

              <div class="space-y-4">
                <!-- Entrée -->
                <div class="bg-white p-3.5 rounded-xl border border-emerald-100 shadow-sm transition-all"
                  :class="{ 'ring-2 ring-emerald-500/20': form.montant_entree > 0 }">
                  <div class="flex justify-between items-center mb-1">
                    <p class="text-[10px] text-emerald-600 uppercase font-bold tracking-wider">Entrée</p>
                    <TrendingUp class="w-3 h-3 text-emerald-400" />
                  </div>
                  <p class="text-lg font-bold text-emerald-700 font-mono">
                    +{{ formatCurrency(form.montant_entree || 0) }}
                  </p>
                </div>

                <!-- Sortie -->
                <div class="bg-white p-3.5 rounded-xl border border-rose-100 shadow-sm transition-all"
                  :class="{ 'ring-2 ring-rose-500/20': form.montant_sortie > 0 }">
                  <div class="flex justify-between items-center mb-1">
                    <p class="text-[10px] text-rose-600 uppercase font-bold tracking-wider">Sortie</p>
                    <TrendingDown class="w-3 h-3 text-rose-400" />
                  </div>
                  <p class="text-lg font-bold text-rose-700 font-mono">
                    -{{ formatCurrency(form.montant_sortie || 0) }}
                  </p>
                </div>

                <!-- Net -->
                <div class="bg-slate-800 p-4 rounded-xl shadow-lg mt-6">
                  <p class="text-[10px] text-slate-400 uppercase font-bold tracking-wider mb-1">Impact sur le solde</p>
                  <div class="flex items-center gap-2">
                    <p class="text-xl font-bold font-mono"
                      :class="netAmount >= 0 ? 'text-emerald-400' : 'text-rose-400'">
                      {{ netAmount >= 0 ? '+' : '' }}{{ formatCurrency(netAmount) }}
                    </p>
                  </div>
                </div>

              </div>
            </div>

            <div class="mt-auto p-4 bg-slate-100/50 rounded-b-xl border-t border-slate-200">
              <p class="text-[10px] text-slate-400 text-center leading-relaxed">
                Le mouvement sera créé avec le statut "En attente" et devra être validé pour impacter le solde réel.
              </p>
            </div>
          </div>
        </div>

      </div>
    </main>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import {
  ArrowLeft, CheckCircle2, Wallet, Calendar, User,
  FileText, TrendingUp, TrendingDown, Activity
} from 'lucide-vue-next'
import caisseService from '@/services/caisseService'
import personnelService from '@/services/personnelService'

const route = useRoute()
const router = useRouter()

const caisses = ref([])
const personnel = ref([])
const saving = ref(false)

const form = ref({
  caisse_id: null,
  libelle_operation: '',
  montant_entree: 0,
  montant_sortie: 0,
  personnel_id: null,
  date_mouvement: new Date().toISOString().split('T')[0],
  statut_id: 2 // EN_ATTENTE
})

// Computed for summary
const netAmount = computed(() => {
  return (Number(form.value.montant_entree) || 0) - (Number(form.value.montant_sortie) || 0)
})

onMounted(async () => {
  try {
    const user = JSON.parse(localStorage.getItem('user') || '{}')
    if (user && user.entreprise_id) {
      const cr = await caisseService.getCaisses(user.entreprise_id)
      caisses.value = cr.data ?? cr

      // Auto-select caisse from query or default
      if (route.query.caisse_id) {
        form.value.caisse_id = Number(route.query.caisse_id)
      } else if (caisses.value.length) {
        form.value.caisse_id = caisses.value[0].id
      }

      // Pre-fill type if specified
      if (route.query.type === 'entree') {
        form.value.montant_entree = 0
        form.value.montant_sortie = 0
      } else if (route.query.type === 'sortie') {
        form.value.montant_entree = 0
        form.value.montant_sortie = 0
      }

      // Load personnel
      try {
        const pr = await personnelService.getByFiliale(user.entreprise_id)
        personnel.value = pr.data ?? pr
        if (user && user.id) form.value.personnel_id = user.id
        else if (personnel.value.length) form.value.personnel_id = personnel.value[0].id
      } catch (e) {
        console.warn('Impossible de charger le personnel', e)
      }
    }
  } catch (err) {
    console.error('Erreur initialisation création mouvement', err)
  }
})

const validateForm = () => {
  if (!form.value.caisse_id) {
    alert('Veuillez sélectionner une caisse')
    return false
  }
  if (!form.value.libelle_operation || !form.value.libelle_operation.trim()) {
    alert('Le libellé de l\'opération est obligatoire')
    return false
  }
  const montantEntree = Number(form.value.montant_entree) || 0
  const montantSortie = Number(form.value.montant_sortie) || 0

  if (montantEntree <= 0 && montantSortie <= 0) {
    alert('Vous devez saisir au moins un montant positif (entrée ou sortie)')
    return false
  }
  return true
}

const submit = async () => {
  if (!validateForm()) return
  saving.value = true
  try {
    const payload = {
      libelle_operation: form.value.libelle_operation,
      montant_entree: Number(form.value.montant_entree) || 0,
      montant_sortie: Number(form.value.montant_sortie) || 0,
      personnel_id: form.value.personnel_id,
      date_mouvement: form.value.date_mouvement,
      statut_id: form.value.statut_id
    }

    const resp = await caisseService.createMouvement(form.value.caisse_id, payload)
    const newId = resp?.data ?? resp

    router.push({ name: 'mouvement-caisse-detail', params: { id: newId } })
  } catch (err) {
    console.error('Erreur création mouvement', err)
    alert(err.response?.data?.error || 'Erreur lors de la création du mouvement')
  } finally {
    saving.value = false
  }
}

const cancel = () => router.back()

function formatCurrency(value) {
  const num = parseFloat(value || 0)
  return new Intl.NumberFormat('fr-MG', {
    style: 'currency', currency: 'MGA', maximumFractionDigits: 0
  }).format(num).replace('MGA', '').trim()
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