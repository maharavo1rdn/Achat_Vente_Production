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
                {{ isNew ? 'Nouvelle Demande' : demande.numero_da }}
              </h1>
              <span v-if="!isNew" :class="['status-pill', getStatutClass(demande.statut_code)]">
                {{ demande.statut_libelle }}
              </span>
            </div>
            <div class="flex items-center gap-2 text-sm text-slate-500 mt-1">
              <span class="flex items-center gap-1.5">
                <div :class="['w-2 h-2 rounded-full', isEditing ? 'bg-amber-400 animate-pulse' : 'bg-slate-300']"></div>
                {{ isEditing ? 'Mode Édition' : 'Lecture seule' }}
              </span>
              <span class="text-slate-300">|</span>
              <span>Créé le {{ formatDate(demande.date_demande) }}</span>
            </div>
          </div>
        </div>

        <!-- Right: Actions Bar -->
        <div class="action-bar" v-if="!loading">
          <template v-if="!isEditing && demande.statut_id == 2">
            <button @click="validerDemande" class="btn btn-success">
              <Check class="w-4 h-4" />
              <span>Valider</span>
            </button>
            <button @click="annulerDemande" class="btn btn-danger">
              <X class="w-4 h-4" />
              <span>Annuler</span>
            </button>
            <button @click="enableEditMode" class="btn btn-secondary">
              <Pencil class="w-4 h-4" />
              <span>Modifier</span>
            </button>
          </template>

          <template v-if="!isEditing && demande.statut_id == 3">
            <button @click="handleGenerateProforma" class="btn btn-outline">
              <FilePlus class="w-4 h-4" />
              <span>Générer Proforma Fournisseur</span>
            </button>
          </template>

          <template v-if="isEditing">
            <button @click="cancelEdit" class="btn btn-ghost text-slate-500 hover:text-slate-800">
              Annuler
            </button>
            <button @click="saveDemande" :disabled="saving" class="btn btn-primary shadow-lg shadow-indigo-200">
              <Save class="w-4 h-4" v-if="!saving" />
              <div v-else class="spinner-sm"></div>
              <span>{{ saving ? 'Sauvegarde...' : 'Enregistrer' }}</span>
            </button>
          </template>
        </div>
      </div>
    </header>

    <!-- Content Area -->
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
                <h2 class="font-bold text-lg text-slate-800">Informations & Logistique</h2>
                <p class="text-xs text-slate-500">Détails administratifs de la demande</p>
              </div>
            </div>
          </div>

          <div class="p-6 md:p-8">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-x-8 gap-y-6">

              <!-- Numéro DA (Readonly) -->
              <div v-if="!isNew" class="form-group">
                <label class="label">Référence</label>
                <div class="readonly-field font-mono text-indigo-900 bg-indigo-50/50 border-indigo-100">
                  #{{ demande.numero_da }}
                </div>
              </div>

              <!-- Date Demande -->
              <div class="form-group">
                <label class="label">Date demande</label>
                <input v-model="demande.date_demande" type="date" class="modern-input" :disabled="!isEditing" />
              </div>

              <!-- Date souhaitée -->
              <div class="form-group">
                <label class="label">Livraison souhaitée</label>
                <input v-model="demande.date_souhaitee" type="date" class="modern-input" :disabled="!isEditing" />
              </div>

              <!-- Demandeur -->
              <div class="form-group">
                <label class="label required">Demandeur</label>
                <div class="relative">
                  <select v-model="demande.personnel_demandeur_id" class="modern-select" :disabled="!isEditing">
                    <option :value="null">Sélectionner un collaborateur</option>
                    <option v-for="p in personnels" :key="p.id" :value="p.id">
                      {{ p.nom }} {{ p.prenom }}
                    </option>
                  </select>
                  <div class="absolute inset-y-0 right-0 flex items-center px-2 pointer-events-none text-slate-400">
                    <svg class="w-4 h-4 fill-current" viewBox="0 0 20 20">
                      <path
                        d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" />
                    </svg>
                  </div>
                </div>
              </div>

              <!-- Entreprise -->
              <div class="form-group">
                <label class="label required">Entreprise</label>
                <div class="relative">
                  <select v-model="demande.entreprise_id" class="modern-select" :disabled="!isEditing">
                    <option :value="null">Sélectionner une entreprise</option>
                    <option v-for="e in entreprises" :key="e.id" :value="e.id">
                      {{ e.nom }}
                    </option>
                  </select>
                  <div class="absolute inset-y-0 right-0 flex items-center px-2 pointer-events-none text-slate-400">
                    <svg class="w-4 h-4 fill-current" viewBox="0 0 20 20">
                      <path
                        d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" />
                    </svg>
                  </div>
                </div>
              </div>

              <!-- Dépôt -->
              <div class="form-group">
                <label class="label">Dépôt cible</label>
                <div class="relative">
                  <select v-model="demande.depot_cible_id" class="modern-select" :disabled="!isEditing">
                    <option :value="null">-- Aucun --</option>
                    <option v-for="d in depots" :key="d.id" :value="d.id">
                      {{ d.nom }}
                    </option>
                  </select>
                  <div class="absolute inset-y-0 right-0 flex items-center px-2 pointer-events-none text-slate-400">
                    <svg class="w-4 h-4 fill-current" viewBox="0 0 20 20">
                      <path
                        d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" />
                    </svg>
                  </div>
                </div>
              </div>

              <!-- Motif (Full width) -->
              <div class="form-group md:col-span-2 lg:col-span-3">
                <label class="label">Motif de l'achat</label>
                <textarea v-model="demande.motif_achat" rows="2" class="modern-textarea"
                  placeholder="Justification technique ou opérationnelle..." :disabled="!isEditing"></textarea>
              </div>
            </div>
          </div>
        </section>

        <!-- SECTION 2: Articles (Table Modern) -->
        <section class="modern-card flex flex-col min-h-[400px]">
          <div class="card-header flex items-center justify-between border-b border-slate-100">
            <div class="flex items-center gap-2.5">
              <div class="p-2 bg-emerald-50 rounded-lg text-emerald-600">
                <Package class="w-5 h-5" />
              </div>
              <div>
                <h2 class="font-bold text-lg text-slate-800">Panier d'articles</h2>
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
                    <th class="th-cell w-[45%] pl-8">Désignation</th>
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
                        <p class="text-slate-400 font-medium">Aucun article dans la liste</p>
                      </div>
                    </td>
                  </tr>

                  <!-- Rows -->
                  <tr v-for="(detail, index) in rowsToShow" :key="index"
                    class="group hover:bg-slate-50/50 transition-colors duration-150">

                    <!-- Article Selector -->
                    <td class="td-cell pl-8 align-middle">
                      <div v-if="isEditing">
                        <select v-model="detail.article_id" class="input-table w-full">
                          <option :value="null" class="text-slate-400">Choisir un article...</option>
                          <option v-for="a in articles" :key="a.id" :value="a.id">
                            {{ a.reference }} - {{ a.designation }}
                          </option>
                        </select>
                      </div>
                      <div v-else class="flex flex-col py-1">
                        <span class="font-medium text-slate-700 text-sm">
                          {{ getArticleLabel(detail.article_id) || 'Non spécifié' }}
                        </span>
                      </div>
                    </td>

                    <!-- Qty Input -->
                    <td class="td-cell align-middle">
                      <input v-if="isEditing" v-model.number="detail.quantite_demandee" type="number" min="1"
                        class="input-table text-center font-semibold text-slate-700" placeholder="0" />
                      <div v-else class="text-center">
                        <span class="px-2.5 py-1 rounded-md bg-slate-100 font-medium text-slate-700 text-sm">
                          {{ detail.quantite_demandee }}
                        </span>
                      </div>
                    </td>

                    <!-- Price Input -->
                    <td class="td-cell align-middle">
                      <input v-if="isEditing" v-model.number="detail.prix_estime" type="number" min="0" step="0.01"
                        class="input-table text-right font-mono text-slate-600" placeholder="0.00" />
                      <div v-else class="text-right text-slate-600 font-mono text-sm">
                        {{ formatCurrency(detail.prix_estime).replace('Ar', '') }}
                      </div>
                    </td>

                    <!-- Total Row -->
                    <td class="td-cell pr-8 align-middle text-right">
                      <span
                        :class="['font-mono font-bold text-sm', (detail.quantite_demandee * detail.prix_estime) > 0 ? 'text-indigo-600' : 'text-slate-300']">
                        {{ formatCurrency((detail.quantite_demandee || 0) * (detail.prix_estime || 0)).replace('Ar', '')
                        }}
                      </span>
                    </td>

                    <!-- Delete Action -->
                    <td v-if="isEditing" class="td-cell align-middle text-center pr-4">
                      <button @click="removeDetail(index)"
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
                  <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-1">Total Estimé Global</p>
                  <div class="text-3xl font-bold text-indigo-900 tracking-tight">
                    {{ formatCurrency(totalEstime) }}
                  </div>
                  <p class="text-xs text-gray-500 mt-1">Montant enregistré : <span class="font-medium">{{ formatCurrency(demande.montant_ttc) }}</span></p>
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
import { ref, onMounted, computed, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import {
  ArrowLeft, Save, X, Plus, Trash2, Check, Pencil,
  Info, MapPin, Package, ShoppingCart
} from 'lucide-vue-next'

// Services import (simulated for component structure)
import proformaDemandeAchatService from '@/services/proformaDemandeAchatService'
import personnelService from '@/services/personnelService'
import entrepriseService from '@/services/entrepriseService'
import depotService from '@/services/depotService'
import articleService from '@/services/articleService'

const route = useRoute()
const router = useRouter()

// Computed Properties
const demandeId = computed(() => route.params.id)
const isNew = computed(() => route.name === 'proforma-demande-achat-new' || demandeId.value === 'new')
const isEditingQuery = computed(() => route.query.edit === '1')
const editMode = ref(false)
const isEditing = computed(() => isNew.value || editMode.value)

const defaultDateSouhaitee = new Date(Date.now() + 7 * 24 * 60 * 60 * 1000).toISOString().split('T')[0]

const demande = ref({
  numero_da: '',
  date_demande: new Date().toISOString().split('T')[0],
  personnel_demandeur_id: null,
  entreprise_id: null,
  depot_cible_id: null,
  date_souhaitee: defaultDateSouhaitee,
  motif_achat: '',
  statut_id: 2,
  statut_code: 'EN_ATTENTE',
  details: []
})

const loading = ref(false)
const saving = ref(false)

// Dropdowns data
const personnels = ref([])
const entreprises = ref([])
const depots = ref([])
const articles = ref([])

// Total Calculation
const filledDetails = computed(() => {
  return demande.value.details.filter(d => d.article_id && d.quantite_demandee && d.quantite_demandee > 0)
})

const rowsToShow = computed(() => {
  return isEditing.value ? demande.value.details : filledDetails.value
})

const totalEstime = computed(() => {
  return filledDetails.value.reduce((acc, item) => {
    return acc + ((item.quantite_demandee || 0) * (item.prix_estime || 0))
  }, 0)
})

// Lifecycle
onMounted(() => {
  loadDemande()
  loadDropdowns()
  if (isNew.value) {
    fillDefaultDetails(10)
    editMode.value = true
  } else if (isEditingQuery.value) {
    editMode.value = true
  }
})

watch(() => route.name, (name) => {
  if (name === 'proforma-demande-achat-new') {
    const user = JSON.parse(localStorage.getItem('user') || '{}')
    demande.value = {
      numero_da: '',
      date_demande: new Date().toISOString().split('T')[0],
      personnel_demandeur_id: user.id ? Number(user.id) : null,
      entreprise_id: user.entreprise_id ? Number(user.entreprise_id) : null,
      depot_cible_id: null,
      date_souhaitee: defaultDateSouhaitee,
      motif_achat: '',
      statut_id: 2,
      statut_code: 'EN_ATTENTE',
      details: []
    }
    fillDefaultDetails(10)
    editMode.value = true
  }
})

watch(() => demande.value.entreprise_id, async (newVal) => {
  const entId = newVal ? Number(newVal) : null
  if (!entId) {
    depots.value = []
    demande.value.depot_cible_id = null
    return
  }
  try {
    const resp = await depotService.getByEntreprise(entId)
    depots.value = resp.data ?? resp
    if (demande.value.depot_cible_id && !depots.value.find(d => d.id == demande.value.depot_cible_id)) {
      demande.value.depot_cible_id = null
    }
  } catch (error) {
    console.error('Erreur chargement dépôts pour entreprise', error)
  }
})

// Methods
const loadDropdowns = async () => {
  try {
    const [p, e, d, a] = await Promise.all([
      personnelService.getAll(),
      entrepriseService.getAll(),
      depotService.getAll(),
      articleService.getAll()
    ])

    personnels.value = p.data.data ?? p
    entreprises.value = e.data ?? e
    articles.value = a.data ?? a

    const user = JSON.parse(localStorage.getItem('user') || '{}')
    const entrepriseToLoad = demande.value.entreprise_id ?? (user.entreprise_id ? Number(user.entreprise_id) : null)

    if (entrepriseToLoad) {
      const depResp = await depotService.getByEntreprise(entrepriseToLoad)
      depots.value = depResp.data ?? depResp
    } else {
      depots.value = d.data ?? d
    }

    if (isNew.value) {
      if (user.id) demande.value.personnel_demandeur_id = Number(user.id)
      if (user.entreprise_id) demande.value.entreprise_id = Number(user.entreprise_id)
    }
  } catch (error) {
    console.error('Erreur chargement données référentielles', error)
  }
}

const loadDemande = async () => {
  if (isNew.value) {
    const user = JSON.parse(localStorage.getItem('user') || '{}')
    if (user.entreprise_id) demande.value.entreprise_id = Number(user.entreprise_id)
    if (user.id) demande.value.personnel_demandeur_id = Number(user.id)
    demande.value.date_souhaitee = defaultDateSouhaitee
    return
  }

  loading.value = true
  try {
    const response = await proformaDemandeAchatService.getById(demandeId.value)
    demande.value = response.data
    if (!demande.value.details) demande.value.details = []
  } catch (error) {
    console.error('Erreur chargement demande', error)
  } finally {
    loading.value = false
  }
}

const enableEditMode = () => {
  editMode.value = true
}

const handleBack = () => {
  router.push({ name: 'proforma-demande-achat' })
}

const addDetail = () => {
  demande.value.details.push({
    article_id: null,
    quantite_demandee: 1,
    prix_estime: 0
  })
}

const fillDefaultDetails = (count = 10) => {
  while (demande.value.details.length < count) {
    demande.value.details.push({
      article_id: null,
      quantite_demandee: 1,
      prix_estime: 0
    })
  }
}

const removeDetail = (index) => {
  demande.value.details.splice(index, 1)
}

const saveDemande = async () => {
  if (!validateForm()) return

  saving.value = true
  try {
    const payload = Object.assign({}, demande.value, { details: filledDetails.value })

    if (isNew.value) {
      const response = await proformaDemandeAchatService.create(payload)
      router.push({ name: 'proforma-demande-achat-detail', params: { id: response.data.id } })
      editMode.value = false
    } else {
      await proformaDemandeAchatService.update(demandeId.value, payload)
      editMode.value = false
      await loadDemande()
    }
  } catch (error) {
    console.error('Erreur sauvegarde', error)
    alert('Une erreur est survenue lors de l\'enregistrement.')
  } finally {
    saving.value = false
  }
}

const validateForm = () => {
  if (!demande.value.personnel_demandeur_id) return alertAndReturn('Le demandeur est obligatoire')
  if (!demande.value.entreprise_id) return alertAndReturn('L\'entreprise est obligatoire')
  const hasAtLeastOneValidDetail = demande.value.details.some(d => d.article_id && d.quantite_demandee && d.quantite_demandee > 0)
  if (!hasAtLeastOneValidDetail) return alertAndReturn('Au moins un article est requis')
  return true
}

const alertAndReturn = (msg) => {
  alert(msg)
  return false
}

const cancelEdit = () => {
  if (isNew.value) {
    handleBack()
  } else {
    editMode.value = false
    loadDemande()
  }
}

const validerDemande = async () => {
  if (!confirm('Confirmer la validation de cette demande ?')) return
  try {
    const user = JSON.parse(localStorage.getItem('user') || '{}')
    await proformaDemandeAchatService.valider(demandeId.value, user.id)
    await loadDemande()
  } catch (error) {
    console.error(error)
    alert(error.response?.data?.error || 'Erreur lors de la validation')
  }
}

const generateProforma = async () => {
  const fournisseurId = prompt('ID du fournisseur (entreprise) à utiliser pour générer la proforma :')
  if (!fournisseurId) return

  if (!confirm('Générer une proforma fournisseur pour cette demande ?')) return

  try {
    const user = JSON.parse(localStorage.getItem('user') || '{}')
    const payload = {
      entreprise_fournisseur_id: Number(fournisseurId),
      user_id: user.id
    }
    const resp = await proformaDemandeAchatService.genererProforma(demandeId.value, payload)
    alert('Proforma créée (ID: ' + resp.data.proforma_id + ')')
    await loadDemande()
  } catch (error) {
    console.error('Erreur génération proforma :', error)
    alert(error.response?.data?.error || 'Erreur lors de la génération')
  }
}

const annulerDemande = async () => {
  if (!confirm('Confirmer l\'annulation de cette demande ?')) return
  try {
    await proformaDemandeAchatService.annuler(demandeId.value)
    await loadDemande()
  } catch (error) {
    console.error(error)
  }
}

const getArticleLabel = (id) => {
  const art = articles.value.find(a => a.id == id)
  return art ? `${art.reference} - ${art.designation}` : ''
}

const formatDate = (date) => {
  if (!date) return '-'
  return new Date(date).toLocaleDateString('fr-FR')
}

const formatCurrency = (val) => {
  if (val === undefined || val === null) return '-'
  return new Intl.NumberFormat('fr-MG', { style: 'currency', currency: 'MGA' }).format(val)
}

const getStatutClass = (code) => {
  const map = {
    'EN_ATTENTE': 'pill-warning',
    'VALIDÉ': 'pill-success',
    'ANNULÉ': 'pill-danger',
    'EN_COURS': 'pill-info'
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
/* This removes the native spinners and their potential black background */
input[type=number]::-webkit-inner-spin-button,
input[type=number]::-webkit-outer-spin-button {
  -webkit-appearance: none;
  margin: 0;
  background: transparent !important;
  /* Force transparency just in case */
}

input[type=number] {
  -moz-appearance: textfield;
  /* Firefox */
}
</style>