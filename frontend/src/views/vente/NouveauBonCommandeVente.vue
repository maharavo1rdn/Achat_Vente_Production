<template>
  <div class="page-container">
    <div class="content-wrapper">
      <div class="page-header">
        <div>
          <h1 class="page-title">Nouveau Bon de Commande</h1>
          <p class="page-subtitle">Créer un bon de commande de vente</p>
        </div>
        <router-link to="/ventes/liste-commande-vente" class="btn-secondary">Retour</router-link>
      </div>

      <div class="card">
        <div class="card-section">
          <h3 class="section-title">Informations générales</h3>

          <div class="form-grid">
            <div class="form-group">
              <label class="label">Client *</label>
              <select v-model="form.client_id" class="select">
                <option value="">Sélectionner un client</option>
                <option v-for="c in clients" :key="c.id" :value="c.id">{{ c.nom }}</option>
              </select>
            </div>

            <div class="form-group">
              <label class="label">Filiale *</label>
              <select v-model="form.filiale_id" class="select">
                <option value="">Sélectionner une filiale</option>
                <option v-for="f in filiales" :key="f.id" :value="f.id">{{ f.nom }}</option>
              </select>
            </div>

            <div class="form-group">
              <label class="label">Date Commande *</label>
              <input v-model="form.date_commande" type="date" class="input" />
            </div>

            <div class="form-group">
              <label class="label">Devis Origine (optionnel)</label>
              <select v-model="form.devis_origine_id" class="select">
                <option value="">Aucun</option>
                <option v-for="d in devisAcceptes" :key="d.id" :value="d.id">{{ d.numero_devis }} - {{ d.client }}</option>
              </select>
              <p class="text-xs text-gray-500 mt-2">Seuls les devis avec le statut <strong>Accepté</strong> peuvent être liés.</p>
            </div>
          </div>

          <div class="mt-4">
            <button @click="createBC" class="btn-primary" :disabled="saving">{{ saving ? 'Enregistrement...' : 'Créer Bon de commande' }}</button>
            <router-link to="/ventes/bon-commande" class="btn-secondary ml-2">Annuler</router-link>
          </div>

          <p v-if="error" class="text-sm text-red-600 mt-3">{{ error }}</p>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { useRouter } from 'vue-router'
import venteService from '@/services/venteService'
import entrepriseService from '@/services/entrepriseService'

const router = useRouter()
const form = ref({
  client_id: '',
  filiale_id: '',
  date_commande: new Date().toISOString().split('T')[0],
  devis_origine_id: ''
})

const clients = ref([])
const filiales = ref([])
const devisList = ref([])
const saving = ref(false)
const error = ref(null)

const loadData = async () => {
  try {
    const [cRes, fRes, dRes] = await Promise.all([
      entrepriseService.getAll({ type_entreprise: 'CLIENT' }),
      entrepriseService.getAll({ type_entreprise: 'INTERNE' }),
      venteService.devis.getAll()
    ])

    clients.value = cRes.data || []
    filiales.value = fRes.data || []
    devisList.value = dRes.data || []
  } catch (err) {
    console.error('Erreur chargement données création BC:', err)
  }
}

const devisAcceptes = computed(() => {
  return devisList.value.filter(d => {
    if (d.statut !== 'ACCEPTE') return false
    // if a client is selected, show only devis for that client
    if (!form.value.client_id) return true
    return String(d.entreprise_client_id) === String(form.value.client_id)
  })
})

// Clear selected devis if it no longer belongs to the chosen client
watch(() => form.value.client_id, (newClientId) => {
  if (form.value.devis_origine_id) {
    const selected = devisList.value.find(d => String(d.id) === String(form.value.devis_origine_id))
    if (!selected || String(selected.entreprise_client_id) !== String(newClientId)) {
      form.value.devis_origine_id = ''
    }
  }
})

const createBC = async () => {
  if (!form.value.client_id || !form.value.filiale_id || !form.value.date_commande) {
    error.value = 'Veuillez remplir tous les champs obligatoires'
    return
  }

  // If a devis is selected, ensure it's accepted
  if (form.value.devis_origine_id) {
    const d = devisList.value.find(x => x.id === form.value.devis_origine_id)
    if (!d || d.statut !== 'ACCEPTE') {
      error.value = "Le devis sélectionné doit être en statut 'Accepté'"
      return
    }
  }

  saving.value = true
  error.value = null

  try {
    const payload = {
      client_id: form.value.client_id,
      filiale_id: form.value.filiale_id,
      date_commande: form.value.date_commande,
      devis_origine_id: form.value.devis_origine_id || null
    }

    const res = await venteService.bonCommande.create(payload)
    // after success, navigate to BC list
    router.push('/ventes/bon-commande')
    alert(res && res.data ? `Bon de commande créé (ID: ${res.data})` : 'Bon de commande créé')
  } catch (err) {
    console.error('Erreur création BC:', err)
    error.value = err.response?.data?.message || 'Erreur lors de la création du bon de commande'
  } finally {
    saving.value = false
  }
}

onMounted(() => {
  loadData()
})
</script>

<style scoped>
.page-container { @apply min-h-screen ml-64 p-6 bg-gray-50; }
.page-header { @apply flex items-center justify-between mb-6; }
.page-title { @apply text-2xl font-bold text-gray-900; }
.page-subtitle { @apply text-sm text-gray-500; }
.card { @apply bg-white rounded-lg border border-gray-200 p-5; }
.form-grid { @apply grid grid-cols-1 md:grid-cols-2 gap-4; }
.label { @apply block text-xs font-medium text-gray-700; }
.input { @apply w-full px-4 py-2.5 border border-gray-300 rounded-lg; }
.select { @apply w-full px-4 py-2.5 border border-gray-300 rounded-lg; }
.btn-primary { @apply inline-flex items-center gap-2 bg-indigo-600 text-white px-4 py-2 rounded-lg; }
.btn-secondary { @apply inline-flex items-center gap-2 bg-white border border-gray-300 px-4 py-2 rounded-lg; }
</style>
