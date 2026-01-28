<template>
  <div class="page-container">

    <!-- Header / Navigation (Glassmorphism & Sticky) -->
    <header class="header-section fade-in">
      <div class="max-w-7xl mx-auto flex flex-col md:flex-row md:items-center justify-between gap-4">

        <!-- Left: Title & Context -->
        <div class="flex items-center gap-4">
          <button @click="handleBack" class="btn-back group" title="Retour à la liste">
            <ArrowLeft class="w-5 h-5 text-gray-500 group-hover:text-indigo-600 transition-colors" />
          </button>

          <div class="flex flex-col">
            <div class="flex items-center gap-3">
              <h1 class="text-2xl font-bold text-slate-800 tracking-tight">
                {{ isNew ? 'Nouveau Proforma' : proforma.numero_proforma }}
              </h1>
              <span v-if="!isNew" :class="['status-pill', getStatutClass(proforma.statut_code)]">
                {{ proforma.statut_libelle }}
              </span>
            </div>
            <div class="flex items-center gap-2 text-sm text-slate-500 mt-1">
              <span class="flex items-center gap-1.5">
                <div :class="['w-2 h-2 rounded-full', isEditing ? 'bg-amber-400 animate-pulse' : 'bg-slate-300']"></div>
                {{ isEditing ? 'Mode Édition' : 'Lecture seule' }}
              </span>
              <span class="text-slate-300">|</span>
              <span>Émis le {{ formatDate(proforma.date_emission) }}</span>
            </div>
          </div>
        </div>

        <!-- Right: Actions Bar -->
        <div class="action-bar" v-if="!loading">
          <!-- Mode Lecture -->
          <template v-if="!isEditing">
            <button v-if="proforma.statut_id == 1" @click="enableEditMode" class="btn btn-secondary">
              <Pencil class="w-4 h-4" />
              <span>Modifier</span>
            </button>
            <button v-if="canValidate" @click="validerProforma" class="btn btn-success">
              <Check class="w-4 h-4" />
              <span>Valider</span>
            </button>
            <button @click="exportDetailPdf" class="btn btn-secondary">
              <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
              <span>Exporter</span>
            </button>
          </template>

          <!-- Mode Édition -->
          <template v-if="isEditing">
            <button @click="cancelEdit" class="btn btn-ghost text-slate-500 hover:text-slate-800">
              Annuler
            </button>
            <button @click="saveProforma" :disabled="saving" class="btn btn-primary shadow-lg shadow-indigo-200">
              <Save class="w-4 h-4" v-if="!saving" />
              <div v-else class="spinner-sm"></div>
              <span>{{ saving ? 'Sauvegarde...' : 'Enregistrer' }}</span>
            </button>
          </template>
        </div>
      </div>
    </header>

    <!-- Main Content -->
    <main class="max-w-7xl mx-auto pb-12 space-y-8">

      <!-- Loading Skeleton -->
      <div v-if="loading" class="animate-pulse space-y-6 mt-6">
        <div class="h-48 bg-slate-200/70 rounded-2xl"></div>
        <div class="h-64 bg-slate-200/70 rounded-2xl"></div>
      </div>

      <div v-else class="fade-in space-y-8" style="animation-delay: 0.1s">

        <!-- SECTION 1: Informations Générales (Card Modern) -->
        <section class="modern-card relative overflow-hidden">
          <!-- Decorative top accent -->
          <div class="absolute top-0 left-0 w-full h-1 bg-gradient-to-r from-indigo-500 via-purple-500 to-indigo-500">
          </div>

          <div class="card-header">
            <div class="flex items-center gap-2.5">
              <div class="p-2 bg-indigo-50 rounded-lg text-indigo-600">
                <Info class="w-5 h-5" />
              </div>
              <div>
                <h2 class="font-bold text-lg text-slate-800">Détails du Proforma</h2>
                <p class="text-xs text-slate-500">Informations administratives et validité</p>
              </div>
            </div>
          </div>

          <div class="p-6 md:p-8">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-x-8 gap-y-6">

              <!-- Numéro (Readonly) -->
              <div class="form-group">
                <label class="label">Numéro Proforma</label>
                <div class="readonly-field font-mono text-indigo-900 bg-indigo-50/50 border-indigo-100">
                  {{ proforma.numero_proforma || '(Numéro généré automatiquement)' }}
                </div>
              </div>

              <!-- Demande d'achat liée (afficher seulement si existe) -->
              <div v-if="proformaDemande" class="form-group">
                <label class="label">Demande d'achat liée</label>
                <div class="readonly-field">
                  <a @click.prevent="viewDemandeDetail(proformaDemande.id)" class="text-indigo-600 hover:underline cursor-pointer">{{ proformaDemande.numero_da }}</a>
                </div>
              </div>

              <!-- Date émission -->
              <div class="form-group">
                <label class="label">Date émission</label>
                <input v-model="proforma.date_emission" type="date" class="modern-input" :disabled="!isEditing" />
              </div>

              <!-- Date validité -->
              <div class="form-group">
                <label class="label">Date validité</label>
                <input v-model="proforma.date_validite" type="date" class="modern-input" :disabled="!isEditing" />
              </div>

              <!-- Fournisseur -->
              <div class="form-group">
                <label class="label required">Fournisseur</label>
                <div class="relative">
                  <select v-model="proforma.entreprise_fournisseur_id" class="modern-select" :disabled="!isEditing">
                    <option :value="null">Sélectionner un fournisseur</option>
                    <option v-for="e in entreprises" :key="e.id" :value="e.id">{{ e.nom }}</option>
                  </select>
                  <div class="absolute inset-y-0 right-0 flex items-center px-2 pointer-events-none text-slate-400">
                    <svg class="w-4 h-4 fill-current" viewBox="0 0 20 20">
                      <path
                        d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" />
                    </svg>
                  </div>
                </div>
              </div>

              <!-- Filiale -->
              <div class="form-group">
                <label class="label required">Filiale concernée</label>
                <div class="relative">
                  <select v-model="proforma.entreprise_filiale_id" class="modern-select" :disabled="!isEditing">
                    <option :value="null">Sélectionner une filiale</option>
                    <option v-for="e in entreprises" :key="e.id" :value="e.id">{{ e.nom }}</option>
                  </select>
                  <div class="absolute inset-y-0 right-0 flex items-center px-2 pointer-events-none text-slate-400">
                    <svg class="w-4 h-4 fill-current" viewBox="0 0 20 20">
                      <path
                        d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" />
                    </svg>
                  </div>
                </div>
              </div>

              <!-- Personnel -->
              <div class="form-group">
                <label class="label required">Responsable</label>
                <div class="relative">
                  <select v-model="proforma.personnel_id" class="modern-select" :disabled="!isEditing">
                    <option :value="null">Sélectionner un collaborateur</option>
                    <option v-for="p in personnels" :key="p.id" :value="p.id">{{ p.nom }} {{ p.prenom }}</option>
                  </select>
                  <div class="absolute inset-y-0 right-0 flex items-center px-2 pointer-events-none text-slate-400">
                    <svg class="w-4 h-4 fill-current" viewBox="0 0 20 20">
                      <path
                        d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" />
                    </svg>
                  </div>
                </div>
              </div>

              <!-- Notes (Full width) -->
              <div class="form-group md:col-span-2 lg:col-span-3">
                <label class="label">Notes / Observations</label>
                <textarea v-model="proforma.notes" rows="2" class="modern-textarea"
                  placeholder="Remarques éventuelles..." :disabled="!isEditing"></textarea>
              </div>
            </div>
          </div>
        </section>

        <!-- SECTION 2: Lignes (Table Modern) -->
        <section class="modern-card flex flex-col min-h-[400px]">
          <div class="card-header flex items-center justify-between border-b border-slate-100">
            <div class="flex items-center gap-2.5">
              <div class="p-2 bg-emerald-50 rounded-lg text-emerald-600">
                <Package class="w-5 h-5" />
              </div>
              <div>
                <h2 class="font-bold text-lg text-slate-800">Lignes de commande</h2>
                <p class="text-xs text-slate-500">{{ filledDetails.length }} ligne(s) valide(s)</p>
              </div>
            </div>

            <button v-if="isEditing" @click="addDetail" class="btn-xs-outline group">
              <div
                class="bg-indigo-50 p-1 rounded-md group-hover:bg-indigo-600 group-hover:text-white transition-colors">
                <Plus class="w-3.5 h-3.5" />
              </div>
              <span class="text-indigo-600 group-hover:text-indigo-700 font-medium">Ajouter ligne</span>
            </button>
          </div>

          <div class="flex-grow overflow-hidden flex flex-col">
            <div class="table-container flex-grow bg-white">
              <table class="w-full text-left border-collapse">
                <thead class="bg-slate-50/80 backdrop-blur sticky top-0 z-10">
                  <tr>
                    <th class="th-cell w-[45%] pl-8">Article</th>
                    <th class="th-cell w-[15%] text-center">Quantité</th>
                    <th class="th-cell w-[20%] text-right">P.U. (Ar)</th>
                    <th class="th-cell w-[20%] text-right pr-8">Total (Ar)</th>
                    <th v-if="isEditing" class="th-cell w-10"></th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                  <!-- Empty State -->
                  <tr v-if="rowsToShow.length === 0">
                    <td :colspan="isEditing ? 5 : 4" class="py-16 text-center">
                      <div class="flex flex-col items-center justify-center gap-3">
                        <div class="w-16 h-16 bg-slate-50 rounded-full flex items-center justify-center">
                          <ShoppingCart class="w-8 h-8 text-slate-300" />
                        </div>
                        <p class="text-slate-400 font-medium">Aucune ligne dans le proforma</p>
                      </div>
                    </td>
                  </tr>

                  <!-- Rows -->
                  <tr v-for="(d, i) in rowsToShow" :key="i"
                    class="group hover:bg-slate-50/50 transition-colors duration-150">

                    <!-- Article Selector -->
                    <td class="td-cell pl-8 align-middle">
                      <div v-if="isEditing">
                        <select v-model="d.article_id" class="input-table w-full">
                          <option :value="null" class="text-slate-400">Choisir un article...</option>
                          <option v-for="a in articles" :key="a.id" :value="a.id">{{ a.reference }} - {{ a.designation
                            }}
                          </option>
                        </select>
                      </div>
                      <div v-else class="flex flex-col py-1">
                        <span class="font-medium text-slate-700 text-sm">
                          {{ getArticleLabel(d.article_id) || 'Article inconnu' }}
                        </span>
                      </div>
                    </td>

                    <!-- Qty Input -->
                    <td class="td-cell align-middle">
                      <input v-if="isEditing" v-model.number="d.quantite" type="number" min="1"
                        class="input-table text-center font-semibold text-slate-700" placeholder="0" />
                      <div v-else class="text-center">
                        <span class="px-2.5 py-1 rounded-md bg-slate-100 font-medium text-slate-700 text-sm">{{
                          d.quantite
                          }}</span>
                      </div>
                    </td>

                    <!-- Price Input -->
                    <td class="td-cell align-middle">
                      <input v-if="isEditing" v-model.number="d.prix_unitaire" type="number" min="0" step="0.01"
                        class="input-table text-right font-mono text-slate-600" placeholder="0.00" />
                      <div v-else class="text-right text-slate-600 font-mono text-sm">{{
                        formatCurrency(d.prix_unitaire).replace('Ar', '') }}</div>
                    </td>

                    <!-- Total Row -->
                    <td class="td-cell pr-8 align-middle text-right">
                      <span
                        :class="['font-mono font-bold text-sm', (d.quantite * d.prix_unitaire) > 0 ? 'text-indigo-600' : 'text-slate-300']">
                        {{ formatCurrency((d.quantite || 0) * (d.prix_unitaire || 0)).replace('Ar', '') }}
                      </span>
                    </td>

                    <!-- Delete Action -->
                    <td v-if="isEditing" class="td-cell align-middle text-center pr-4">
                      <button @click="removeDetail(i)"
                        class="p-1.5 rounded-md text-slate-300 hover:text-rose-500 hover:bg-rose-50 transition-all opacity-0 group-hover:opacity-100 focus:opacity-100">
                        <Trash2 class="w-4 h-4" />
                      </button>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>

            <!-- Footer Summary -->
            <div class="bg-slate-50 border-t border-slate-200 p-6">
              <div class="flex flex-col md:flex-row justify-end items-end md:items-center gap-6">
                <div class="text-right">
                  <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-1">Total TTC Estimé</p>
                  <div class="text-3xl font-bold text-indigo-900 tracking-tight">
                    {{ formatCurrency(totalEstime) }}
                  </div>
                  <p class="text-xs text-gray-500 mt-1">Montant enregistré : <span class="font-medium">{{
                    formatCurrency(proforma.montant_ttc) }}</span></p>
                </div>
              </div>
            </div>
          </div>
        </section>

      </div>
    </main>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { ArrowLeft, Save, X, Plus, Trash2, Check, Pencil, Info, Package, ShoppingCart } from 'lucide-vue-next'

import achatService from '@/services/achatService'
import proformaFournisseurService from '@/services/proformaFournisseurService'
import entrepriseService from '@/services/entrepriseService'
import personnelService from '@/services/personnelService'
import articleService from '@/services/articleService'
import proformaDemandeAchatService from '@/services/proformaDemandeAchatService' 

const route = useRoute()
const router = useRouter()

const proformaId = computed(() => route.params.id)
const isNew = computed(() => route.name === 'proforma-fournisseur-new' || proformaId.value === 'new')
const editMode = ref(false)
const isEditing = computed(() => isNew.value || editMode.value)

const proforma = ref({
  numero_proforma: '',
  date_emission: new Date().toISOString().split('T')[0],
  date_validite: '',
  entreprise_fournisseur_id: null,
  entreprise_filiale_id: null,
  personnel_id: null,
  statut_id: 1,
  statut_code: 'BROUILLON', // Default
  statut_libelle: 'Brouillon', // Default
  montant_ttc: 0,
  details: []
})

const loading = ref(false)
const saving = ref(false)

const entreprises = ref([])
const personnels = ref([])
const articles = ref([])

// Demande d'achat liée (si présente)
const proformaDemande = ref(null)

const filledDetails = computed(() => proforma.value.details.filter(d => d.article_id && d.quantite && d.quantite > 0))
const rowsToShow = computed(() => isEditing.value ? proforma.value.details : filledDetails.value)
const totalEstime = computed(() => filledDetails.value.reduce((acc, it) => acc + ((it.quantite || 0) * (it.prix_unitaire || 0)), 0))

const canValidate = computed(() => (JSON.parse(localStorage.getItem('user') || '{}').niveau_acces || 0) >= 5)

onMounted(() => {
  loadDropdowns()
  loadProforma()
  if (isNew.value) {
    // Par défaut, date de validité = aujourd'hui
    proforma.value.date_validite = new Date().toISOString().split('T')[0]
    // Pré-remplir le responsable par l'utilisateur connecté si disponible
    const user = JSON.parse(localStorage.getItem('user') || '{}')
    if (user.id) proforma.value.personnel_id = Number(user.id)

    fillDefaultDetails(5)
    editMode.value = true
  }
})

const loadDropdowns = async () => {
  try {
    const [e, p, a] = await Promise.all([
      entrepriseService.getAll(),
      personnelService.getAll(),
      articleService.getAll()
    ])
    entreprises.value = e.data ?? e
    // Le service personnel peut renvoyer { data: { data: [...] } }
    personnels.value = p.data?.data ?? p.data ?? p
    articles.value = a.data ?? a

    // Si on est en création, assurer que le champ responsable est pré-rempli
    const user = JSON.parse(localStorage.getItem('user') || '{}')
    if (isNew.value && user.id) {
      proforma.value.personnel_id = Number(user.id)
    }
  } catch (error) {
    console.error('Erreur chargement référentiels', error)
  }
}

const loadProforma = async () => {
  if (isNew.value) return
  loading.value = true
  try {
    const resp = await proformaFournisseurService.getById(proformaId.value)
    proforma.value = resp.data
    if (!proforma.value.details) proforma.value.details = []
    if (route.query.edit === '1') {
      editMode.value = true
    }

    // Charger la demande d'achat liée si elle existe
    if (proforma.value.proforma_demande_achat_id) {
      try {
        const respDA = await proformaDemandeAchatService.getById(proforma.value.proforma_demande_achat_id)
        proformaDemande.value = respDA.data
      } catch (err) {
        console.error('Erreur chargement DA liée', err)
        proformaDemande.value = null
      }
    } else {
      proformaDemande.value = null
    }
  } catch (err) {
    console.error('Erreur chargement proforma', err)
  } finally {
    loading.value = false
  }
}

const handleBack = () => router.push({ name: 'proforma-fournisseur' })
const viewDemandeDetail = (id) => { router.push({ name: 'proforma-demande-achat-detail', params: { id } }) }
const enableEditMode = () => editMode.value = true
const addDetail = () => proforma.value.details.push({ article_id: null, quantite: 1, prix_unitaire: 0 })
const fillDefaultDetails = (count = 5) => { while (proforma.value.details.length < count) proforma.value.details.push({ article_id: null, quantite: 1, prix_unitaire: 0 }) }
const removeDetail = (i) => proforma.value.details.splice(i, 1)
const cancelEdit = () => {
  if (isNew.value) handleBack()
  else {
    editMode.value = false
    loadProforma()
  }
}

// Export PDF (fiche proforma fournisseur)
const exportDetailPdf = () => {
  const base = import.meta.env.VITE_API_BASE_URL || '/api'
  const params = new URLSearchParams()
  const user = JSON.parse(localStorage.getItem('user') || '{}')
  if (user?.entreprise_id) params.set('entreprise_id', user.entreprise_id)
  if (user?.id) params.set('user_id', user.id)
  const url = `${base}/proforma-fournisseur/${route.params.id}/export${params.toString() ? ('?' + params.toString()) : ''}`
  window.open(url, '_blank')
}

const saveProforma = async () => {
  saving.value = true
  try {
    const payload = Object.assign({}, proforma.value, { details: filledDetails.value, montant_ttc: totalEstime.value })
    // Ne pas envoyer numero_proforma lors de la création — il est généré côté serveur
    if (isNew.value) {
      delete payload.numero_proforma
      const res = await proformaFournisseurService.create(payload)
      // Rediriger vers la fiche créée (le numéro sera affiché après rechargement)
      router.push({ name: 'proforma-fournisseur-detail', params: { id: res.data.id } })
    } else {
      await proformaFournisseurService.update(proformaId.value, payload)
      // Si la page était ouverte automatiquement en édition via ?edit=1 (ex: génération depuis DA),
      // on retire ce paramètre avant de recharger la fiche pour ne pas réactiver l'édition.
      if (route.query.edit === '1') {
        await router.replace({ name: 'proforma-fournisseur-detail', params: { id: proformaId.value } })
      }
      editMode.value = false
      await loadProforma()
    }
  } catch (err) {
    console.error('Erreur sauvegarde', err)
    alert(err.response?.data?.error || 'Erreur lors de la sauvegarde')
  } finally { saving.value = false }
}

const validerProforma = async () => {
  if (!confirm('Valider cette proforma ?')) return
  try {
    const user = JSON.parse(localStorage.getItem('user') || '{}')
    await proformaFournisseurService.valider(proformaId.value, user.id)
    await loadProforma()
    alert('Proforma validée')
  } catch (err) {
    console.error(err)
    alert(err.response?.data?.error || 'Erreur lors de la validation')
  }
}

// Helpers
const getArticleLabel = id => { const a = articles.value.find(x => x.id == id); return a ? `${a.reference} - ${a.designation}` : '' }
const formatDate = d => d ? new Date(d).toLocaleDateString('fr-FR') : '-'
const formatCurrency = val => { if (val === undefined || val === null) return '-'; return new Intl.NumberFormat('fr-MG', { style: 'currency', currency: 'MGA' }).format(val) }

// Mapped to CSS pill classes
const getStatutClass = code => {
  const map = {
    'BROUILLON': 'pill-warning',
    'VALIDE': 'pill-success',
    'VALIDÉ': 'pill-success',
    'ANNULÉ': 'pill-danger'
  }
  return map[code] || 'pill-default'
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
  @apply bg-white rounded-2xl shadow-lg shadow-slate-200/50 border border-slate-100;
}

.card-header {
  @apply px-6 py-5 md:px-8 bg-white/50;
}

/* --- FORMS (Inputs & Selects) --- */
.form-group {
  @apply flex flex-col gap-2;
}

.label {
  @apply text-xs font-bold text-slate-500 uppercase tracking-wide ml-1;
}

.label.required::after {
  content: " *";
  @apply text-rose-500;
}

.modern-input,
.modern-select,
.modern-textarea {
  @apply w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm font-medium text-slate-700 transition-all duration-200;
  @apply focus:bg-white focus:ring-2 focus:ring-indigo-500/10 focus:border-indigo-500 outline-none;
  @apply disabled:opacity-60 disabled:cursor-not-allowed disabled:bg-slate-100;
}

/* Removing default appearance for selects to use custom chevron */
.modern-select {
  @apply appearance-none pr-10;
}

.readonly-field {
  @apply w-full px-4 py-2.5 rounded-xl text-sm border flex items-center;
}

/* --- TABLE STYLING --- */
.table-container {
  @apply overflow-x-auto min-h-[300px];
}

.th-cell {
  @apply py-3 text-xs font-bold text-slate-400 uppercase tracking-wider border-b border-slate-200;
}

.td-cell {
  @apply py-2 border-b border-slate-50;
}

.input-table {
  @apply w-full px-3 py-1.5 bg-transparent border border-transparent rounded-lg text-sm transition-all focus:bg-white focus:border-indigo-300 focus:shadow-sm focus:ring-2 focus:ring-indigo-500/10 outline-none hover:bg-slate-100/50;
}

/* --- BADGES & PILLS --- */
.status-pill {
  @apply px-3 py-1 rounded-full text-[10px] font-bold uppercase tracking-widest border shadow-sm;
}

.pill-warning {
  @apply bg-amber-50 text-amber-700 border-amber-200;
}

.pill-success {
  @apply bg-emerald-50 text-emerald-700 border-emerald-200;
}

.pill-danger {
  @apply bg-rose-50 text-rose-700 border-rose-200;
}

.pill-info {
  @apply bg-sky-50 text-sky-700 border-sky-200;
}

.pill-default {
  @apply bg-slate-100 text-slate-600 border-slate-200;
}

/* --- BUTTONS --- */
.action-bar {
  @apply flex items-center gap-3;
}

.btn {
  @apply flex items-center gap-2 px-5 py-2.5 rounded-xl text-sm font-bold transition-all duration-200 active:scale-95 border;
}

.btn-primary {
  @apply bg-indigo-600 text-white border-transparent hover:bg-indigo-700 hover:shadow-indigo-500/20;
}

.btn-secondary {
  @apply bg-white text-slate-700 border-slate-200 hover:bg-slate-50 hover:border-slate-300;
}

.btn-success {
  @apply bg-emerald-600 text-white border-transparent hover:bg-emerald-700 shadow-lg shadow-emerald-200;
}

.btn-danger {
  @apply bg-white text-rose-600 border-rose-200 hover:bg-rose-50 hover:border-rose-300;
}

.btn-ghost {
  @apply bg-transparent border-transparent text-slate-500 hover:bg-slate-100;
}

.btn-xs-outline {
  @apply flex items-center gap-2 text-xs transition-opacity;
}

/* --- UTILITIES & ANIMATIONS --- */
.spinner-sm {
  @apply w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin;
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

.fade-in {
  animation: fadeInUp 0.5s cubic-bezier(0.16, 1, 0.3, 1) forwards;
}

/* --- SPECIAL FIX: INPUT NUMBER (The Black Spinner Fix) --- */
input[type=number]::-webkit-inner-spin-button,
input[type=number]::-webkit-outer-spin-button {
  -webkit-appearance: none;
  margin: 0;
  background: transparent !important;
}

input[type=number] {
  -moz-appearance: textfield;
}

/* --- CUSTOM RADIO STYLE --- */
input[type="radio"] {
  -webkit-appearance: none;
  appearance: none;
  width: 1rem;
  height: 1rem;
  border: 1px solid #e6e9ef;
  border-radius: 9999px;
  background: #ffffff;
  display: inline-block;
  vertical-align: middle;
  position: relative;
  box-shadow: inset 0 0 0 0 rgba(0, 0, 0, 0.0);
}

input[type="radio"]:hover {
  box-shadow: 0 0 0 4px rgba(79, 70, 229, 0.06);
}

input[type="radio"]:focus-visible {
  outline: 2px solid rgba(79, 70, 229, 0.14);
  outline-offset: 2px;
}

input[type="radio"]:checked {
  background: #4f46e5;
  border-color: #4f46e5;
}

input[type="radio"]:checked::after {
  content: "";
  display: block;
  width: 0.45rem;
  height: 0.45rem;
  border-radius: 9999px;
  background: #ffffff;
  position: absolute;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
}
</style>