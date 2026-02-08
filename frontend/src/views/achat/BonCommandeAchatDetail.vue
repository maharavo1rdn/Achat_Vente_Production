<template>
  <div class="page-container">

    <!-- Header / Navigation -->
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
                {{ isNew ? 'Nouveau Bon de Commande' : bc.numero_bc }}
              </h1>
              <span v-if="!isNew" :class="['status-pill', getStatutClass(bc.statut_code)]">
                {{ bc.statut_libelle || bc.statut }}
              </span>
            </div>
            <div class="flex items-center gap-2 text-sm text-slate-500 mt-1">
              <span class="flex items-center gap-1.5">
                <div :class="['w-2 h-2 rounded-full', isEditing ? 'bg-amber-400 animate-pulse' : 'bg-slate-300']"></div>
                {{ isEditing ? 'Mode Édition' : 'Lecture seule' }}
              </span>
              <span class="text-slate-300">|</span>
              <span>Commandé le {{ formatDate(bc.date_commande) }}</span>
            </div>
          </div>
        </div>

        <!-- Right: Actions Bar -->
        <div class="action-bar" v-if="!loading">
          <!-- Mode Lecture -->
          <template v-if="!isEditing">
            <button v-if="bc.statut_id == 1" @click="enableEditMode" class="btn btn-secondary">
              <Pencil class="w-4 h-4" />
              <span>Modifier</span>
            </button>
            <button v-if="bc.statut_id == 3" @click="convertToFacture" class="btn btn-success">
              <Check class="w-4 h-4" />
              <span>Créer Facture</span>
            </button>
          </template>

          <!-- Mode Édition -->
          <template v-if="isEditing">
            <button @click="cancelEdit" class="btn btn-ghost text-slate-500 hover:text-slate-800">
              Annuler
            </button>
            <button @click="saveBC" :disabled="saving" class="btn btn-primary shadow-lg shadow-indigo-200">
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

        <!-- SECTION 1: Informations Générales -->
        <section class="modern-card relative overflow-hidden">
          <div class="absolute top-0 left-0 w-full h-1 bg-gradient-to-r from-indigo-500 via-purple-500 to-indigo-500"></div>

          <div class="card-header">
            <div class="flex items-center gap-2.5">
              <div class="p-2 bg-indigo-50 rounded-lg text-indigo-600">
                <Info class="w-5 h-5" />
              </div>
              <div>
                <h2 class="font-bold text-lg text-slate-800">Détails du Bon de Commande</h2>
                <p class="text-xs text-slate-500">Informations administratives</p>
              </div>
            </div>
          </div>

          <div class="p-6 md:p-8">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-x-8 gap-y-6">

              <!-- Numéro BC (Readonly - auto-généré) -->
              <div class="form-group">
                <label class="label">Numéro BC</label>
                <div class="readonly-field font-mono text-indigo-900 bg-indigo-50/50 border-indigo-100">
                  {{ bc.numero_bc || '(Numéro généré automatiquement)' }}
                </div>
              </div>

              <!-- Date commande -->
              <div class="form-group">
                <label class="label">Date de commande</label>
                <input v-model="bc.date_commande" type="date" class="modern-input" :disabled="!isEditing" />
              </div>

              <!-- Fournisseur -->
              <div class="form-group">
                <label class="label required">Fournisseur</label>
                <div class="relative">
                  <select v-model="bc.entreprise_fournisseur_id" class="modern-select" :disabled="!isEditing">
                    <option :value="null">Sélectionner un fournisseur</option>
                    <option v-for="e in fournisseurs" :key="e.id" :value="e.id">{{ e.nom }}</option>
                  </select>
                  <div class="absolute inset-y-0 right-0 flex items-center px-2 pointer-events-none text-slate-400">
                    <svg class="w-4 h-4 fill-current" viewBox="0 0 20 20">
                      <path d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" />
                    </svg>
                  </div>
                </div>
              </div>

              <!-- Filiale -->
              <div class="form-group">
                <label class="label required">Filiale concernée</label>
                <div class="relative">
                  <select v-model="bc.entreprise_filiale_id" class="modern-select" :disabled="!isEditing" @change="onFilialeChange">
                    <option :value="null">Sélectionner une filiale</option>
                    <option v-for="e in filiales" :key="e.id" :value="e.id">{{ e.nom }}</option>
                  </select>
                  <div class="absolute inset-y-0 right-0 flex items-center px-2 pointer-events-none text-slate-400">
                    <svg class="w-4 h-4 fill-current" viewBox="0 0 20 20">
                      <path d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" />
                    </svg>
                  </div>
                </div>
              </div>

              <!-- Dépôt de livraison -->
              <div class="form-group">
                <label class="label">Dépôt de livraison</label>
                <div class="relative">
                  <select v-model="bc.depot_livraison_id" class="modern-select" :disabled="!isEditing">
                    <option :value="null">Sélectionner un dépôt</option>
                    <option v-for="d in depots" :key="d.id" :value="d.id">{{ d.nom }}</option>
                  </select>
                  <div class="absolute inset-y-0 right-0 flex items-center px-2 pointer-events-none text-slate-400">
                    <svg class="w-4 h-4 fill-current" viewBox="0 0 20 20">
                      <path d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" />
                    </svg>
                  </div>
                </div>
              </div>

              <!-- Personnel / Responsable -->
              <div class="form-group">
                <label class="label required">Responsable</label>
                <div class="relative">
                  <select v-model="bc.personnel_id" class="modern-select" :disabled="!isEditing">
                    <option :value="null">Sélectionner un collaborateur</option>
                    <option v-for="p in personnels" :key="p.id" :value="p.id">{{ (p.nom || '') }} {{ (p.prenom || '') }}</option>
                  </select>
                  <div class="absolute inset-y-0 right-0 flex items-center px-2 pointer-events-none text-slate-400">
                    <svg class="w-4 h-4 fill-current" viewBox="0 0 20 20">
                      <path d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" />
                    </svg>
                  </div>
                </div>
              </div>

              <!-- Proforma liée (readonly, si présente) -->
              <div v-if="bc.proforma_fournisseur_id" class="form-group">
                <label class="label">Proforma fournisseur liée</label>
                <div class="readonly-field">
                  <a @click.prevent="viewProforma(bc.proforma_fournisseur_id)" class="text-indigo-600 hover:underline cursor-pointer">
                    {{ bc.proforma_origine || `Proforma #${bc.proforma_fournisseur_id}` }}
                  </a>
                </div>
              </div>

            </div>
          </div>
        </section>

        <!-- SECTION 2: Lignes de commande -->
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
              <div class="bg-indigo-50 p-1 rounded-md group-hover:bg-indigo-600 group-hover:text-white transition-colors">
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
                        <p class="text-slate-400 font-medium">Aucune ligne dans le bon de commande</p>
                      </div>
                    </td>
                  </tr>

                  <!-- Rows -->
                  <tr v-for="(d, i) in rowsToShow" :key="i"
                    class="group hover:bg-slate-50/50 transition-colors duration-150">

                    <!-- Article Selector -->
                    <td class="td-cell pl-8 align-middle">
                      <div v-if="isEditing">
                        <select v-model="d.article_id" @change="onArticleChange(d)" class="input-table w-full">
                          <option :value="null" class="text-slate-400">Choisir un article...</option>
                          <option v-for="a in articles" :key="a.id" :value="a.id">{{ a.reference }} - {{ a.designation }}</option>
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
                        <span class="px-2.5 py-1 rounded-md bg-slate-100 font-medium text-slate-700 text-sm">{{ d.quantite }}</span>
                      </div>
                    </td>

                    <!-- Price Input -->
                    <td class="td-cell align-middle">
                      <input v-if="isEditing" v-model.number="d.prix_unitaire" type="number" min="0" step="0.01"
                        class="input-table text-right font-mono text-slate-600" placeholder="0.00" />
                      <div v-else class="text-right text-slate-600 font-mono text-sm">{{ formatCurrency(d.prix_unitaire).replace('Ar', '') }}</div>
                    </td>

                    <!-- Total Row -->
                    <td class="td-cell pr-8 align-middle text-right">
                      <span :class="['font-mono font-bold text-sm', ((d.quantite || 0) * (d.prix_unitaire || 0)) > 0 ? 'text-indigo-600' : 'text-slate-300']">
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
                  <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-1">Total TTC</p>
                  <div class="text-3xl font-bold text-indigo-900 tracking-tight">
                    {{ formatCurrency(totalTTC) }}
                  </div>
                  <p v-if="!isNew" class="text-xs text-gray-500 mt-1">Montant enregistré : <span class="font-medium">{{ formatCurrency(bc.montant_ttc) }}</span></p>
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
import { ArrowLeft, Save, Plus, Trash2, Check, Pencil, Info, Package, ShoppingCart } from 'lucide-vue-next'

import achatService from '@/services/achatService'
import entrepriseService from '@/services/entrepriseService'
import personnelService from '@/services/personnelService'
import articleService from '@/services/articleService'
import depotService from '@/services/depotService'

const route = useRoute()
const router = useRouter()

const bcId = computed(() => route.params.id)
const isNew = computed(() => route.name === 'bon-commande-achat-new')
const editMode = ref(false)
const isEditing = computed(() => isNew.value || editMode.value)

const bc = ref({
  numero_bc: '',
  date_commande: new Date().toISOString().split('T')[0],
  entreprise_fournisseur_id: null,
  entreprise_filiale_id: null,
  personnel_id: null,
  statut_id: 1,
  statut_code: 'BROUILLON',
  statut_libelle: 'Brouillon',
  montant_ttc: 0,
  depot_livraison_id: null,
  proforma_fournisseur_id: null,
  details: []
})

const loading = ref(false)
const saving = ref(false)

const fournisseurs = ref([])
const filiales = ref([])
const personnels = ref([])
const articles = ref([])
const depots = ref([])

const filledDetails = computed(() => bc.value.details.filter(d => d.article_id && d.quantite && d.quantite > 0))
const rowsToShow = computed(() => isEditing.value ? bc.value.details : filledDetails.value)
const totalTTC = computed(() => filledDetails.value.reduce((acc, it) => acc + ((Number(it.quantite) || 0) * (Number(it.prix_unitaire) || 0)), 0))

onMounted(() => {
  loadDropdowns()
  if (isNew.value) {
    const user = JSON.parse(localStorage.getItem('user') || '{}')
    if (user.id) bc.value.personnel_id = Number(user.id)
    fillDefaultDetails(5)
    editMode.value = true
  } else {
    loadBC()
  }
})

const loadDropdowns = async () => {
  try {
    const [entResp, persResp, artResp, depResp] = await Promise.all([
      entrepriseService.getAll(),
      personnelService.getAll(),
      articleService.getAll(),
      depotService.getAll()
    ])
    const allEntreprises = entResp.data ?? entResp
    fournisseurs.value = allEntreprises.filter(e => e.type_entreprise === 'FOURNISSEUR' || e.type_entreprise === 'MIXTE')
    filiales.value = allEntreprises.filter(e => e.type_entreprise === 'INTERNE' || e.type_entreprise === 'MIXTE')
    if (fournisseurs.value.length === 0) fournisseurs.value = allEntreprises
    if (filiales.value.length === 0) filiales.value = allEntreprises

    personnels.value = persResp.data?.data ?? persResp.data ?? persResp
    articles.value = artResp.data ?? artResp
    depots.value = depResp.data ?? depResp
  } catch (error) {
    console.error('Erreur chargement référentiels', error)
  }
}

const loadBC = async () => {
  loading.value = true
  try {
    const resp = await achatService.bonCommande.getById(bcId.value)
    bc.value = resp.data
    if (!bc.value.details) bc.value.details = []
    if (route.query.edit === '1') {
      editMode.value = true
    }
  } catch (err) {
    console.error('Erreur chargement BC', err)
    alert('Erreur lors du chargement du bon de commande')
  } finally {
    loading.value = false
  }
}

const handleBack = () => router.push({ name: 'bon-commande-achat' })
const enableEditMode = () => editMode.value = true
const addDetail = () => bc.value.details.push({ article_id: null, quantite: 1, prix_unitaire: 0 })
const fillDefaultDetails = (count = 5) => {
  while (bc.value.details.length < count) {
    bc.value.details.push({ article_id: null, quantite: 1, prix_unitaire: 0 })
  }
}
const removeDetail = (i) => bc.value.details.splice(i, 1)

const cancelEdit = () => {
  if (isNew.value) handleBack()
  else {
    editMode.value = false
    loadBC()
  }
}

const onFilialeChange = async () => {
  if (bc.value.entreprise_filiale_id) {
    try {
      const resp = await depotService.getByEntreprise(bc.value.entreprise_filiale_id)
      depots.value = resp.data ?? resp
    } catch (e) {
      console.error('Erreur chargement dépôts', e)
    }
  }
}

const onArticleChange = (detail) => {
  const art = articles.value.find(a => a.id == detail.article_id)
  if (art && art.prix_achat_ref) {
    detail.prix_unitaire = Number(art.prix_achat_ref)
  }
}

const viewProforma = (id) => {
  router.push({ name: 'proforma-fournisseur-detail', params: { id } })
}

const saveBC = async () => {
  if (!bc.value.entreprise_fournisseur_id) {
    alert('Veuillez sélectionner un fournisseur')
    return
  }
  if (!bc.value.entreprise_filiale_id) {
    alert('Veuillez sélectionner une filiale')
    return
  }
  if (!bc.value.personnel_id) {
    alert('Veuillez sélectionner un responsable')
    return
  }
  if (filledDetails.value.length === 0) {
    alert('Veuillez ajouter au moins une ligne de commande')
    return
  }

  saving.value = true
  try {
    const payload = {
      ...bc.value,
      details: filledDetails.value,
      montant_ttc: totalTTC.value,
      statut_id: bc.value.statut_id || 1
    }

    if (isNew.value) {
      delete payload.numero_bc
      const res = await achatService.bonCommande.create(payload)
      router.push({ name: 'bon-commande-achat-detail', params: { id: res.data.id } })
    } else {
      await achatService.bonCommande.update(bcId.value, payload)
      if (route.query.edit === '1') {
        await router.replace({ name: 'bon-commande-achat-detail', params: { id: bcId.value } })
      }
      editMode.value = false
      await loadBC()
    }
  } catch (err) {
    console.error('Erreur sauvegarde', err)
    alert(err.response?.data?.error || 'Erreur lors de la sauvegarde')
  } finally {
    saving.value = false
  }
}

const convertToFacture = async () => {
  if (!confirm('Convertir ce bon de commande en facture achat ?')) return
  try {
    await achatService.bonCommande.convertToFacture(bcId.value)
    alert('Bon de commande converti en facture avec succès')
    await loadBC()
  } catch (err) {
    console.error('Erreur conversion', err)
    alert(err.response?.data?.error || 'Erreur lors de la conversion')
  }
}

// Helpers
const getArticleLabel = (id) => {
  const a = articles.value.find(x => x.id == id)
  return a ? `${a.reference} - ${a.designation}` : ''
}

const formatDate = (d) => d ? new Date(d).toLocaleDateString('fr-FR') : '-'

const formatCurrency = (val) => {
  if (val === undefined || val === null) return '-'
  const num = Number(val)
  return new Intl.NumberFormat('fr-MG', { style: 'currency', currency: 'MGA', minimumFractionDigits: 0 }).format(isNaN(num) ? 0 : num)
}

const getStatutClass = (code) => {
  const map = {
    'BROUILLON': 'pill-warning',
    'VALIDE': 'pill-success',
    'VALIDÉ': 'pill-success',
    'LIVRE': 'pill-info',
    'LIVRÉ': 'pill-info',
    'ANNULÉ': 'pill-danger',
    'ANNULE': 'pill-danger'
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

/* --- FORMS --- */
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

.modern-select {
  @apply appearance-none pr-10;
}

.readonly-field {
  @apply w-full px-4 py-2.5 rounded-xl text-sm border flex items-center;
}

/* --- TABLE --- */
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

.btn-ghost {
  @apply bg-transparent border-transparent text-slate-500 hover:bg-slate-100;
}

.btn-xs-outline {
  @apply flex items-center gap-2 text-xs transition-opacity;
}

/* --- UTILITIES --- */
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

input[type=number]::-webkit-inner-spin-button,
input[type=number]::-webkit-outer-spin-button {
  -webkit-appearance: none;
  margin: 0;
  background: transparent !important;
}

input[type=number] {
  -moz-appearance: textfield;
}
</style>
