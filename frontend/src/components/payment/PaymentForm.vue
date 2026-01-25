<template>
  <div class="modal-backdrop">
    <div class="modal-card">
      <div class="modal-header">
        <h3 class="modal-title">Enregistrer un paiement</h3>
        <button @click="$emit('close')" class="btn-ghost">✕</button>
      </div>

      <div class="modal-body">
        <div class="form-grid">
          <div>
            <label class="label">Montant</label>
            <input v-model.number="form.montant" type="number" class="input" />
            <p v-if="errorMessage" class="text-red-600 text-sm mt-1">{{ errorMessage }}</p>
          </div>

          <div>
            <label class="label">Mode de paiement</label>
            <select v-model="form.mode_paiement_id" class="select">
              <option v-for="m in modes" :key="m.id" :value="m.id">{{ m.libelle }}</option>
            </select>
          </div>

          <div>
            <label class="label">Référence externe</label>
            <input v-model="form.reference_externe" type="text" class="input" />
          </div>

          <div>
            <label class="label">Date</label>
            <input v-model="form.date_paiement" type="date" class="input" />
          </div>
        </div>
      </div>

      <div class="modal-footer">
        <button @click="submit" :disabled="saving" class="btn-primary">
          Enregistrer
        </button>
        <button @click="$emit('close')" class="btn-ghost">Annuler</button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import paiementVenteService from '@/services/paiementVenteService'
import paiementAchatService from '@/services/paiementAchatService'
import modePaiementService from '@/services/modePaiementService'
import { useRouter } from 'vue-router'

const props = defineProps({ facture: Object, type: { type: String, default: 'vente' } })
const emit = defineEmits(['close', 'created'])
const router = useRouter()

const modes = ref([])
const saving = ref(false)

const form = ref({
  montant: props.facture?.reste_a_payer ?? 0,
  mode_paiement_id: null,
  reference_externe: '',
  date_paiement: new Date().toISOString().split('T')[0]
})

const errorMessage = ref('')

const validateForm = () => {
  errorMessage.value = ''
  const reste = props.facture?.reste_a_payer ?? 0
  if (!form.value.montant || form.value.montant <= 0) {
    errorMessage.value = 'Le montant doit être supérieur à 0'
    return false
  }
  if (form.value.montant > reste) {
    errorMessage.value = 'Le montant dépasse le reste à payer de la facture'
    return false
  }
  if (!form.value.mode_paiement_id) {
    errorMessage.value = 'Veuillez sélectionner un mode de paiement'
    return false
  }
  return true
}

onMounted(async () => {
  try {
    const resp = await modePaiementService.getAll()
    modes.value = resp.data ?? resp
    if (modes.value.length) form.value.mode_paiement_id = modes.value[0].id
  } catch (err) {
    console.error('Erreur chargement modes paiement', err)
  }
})

const submit = async () => {
  if (!validateForm()) return
  saving.value = true
  try {
    let resp
    if (props.type === 'vente') {
      const payload = {
        facture_vente_id: props.facture.id,
        mode_paiement_id: form.value.mode_paiement_id,
        montant: Number(form.value.montant),
        reference_externe: form.value.reference_externe,
        date_paiement: form.value.date_paiement
      }
      resp = await paiementVenteService.create(payload)
      const id = Array.isArray(resp.data) ? resp.data[0] : resp.data.id ?? resp.data
      emit('created', id)
      router.push({ name: 'paiement-vente-detail', params: { id } })
    } else {
      const payload = {
        facture_achat_id: props.facture.id,
        mode_paiement_id: form.value.mode_paiement_id,
        montant: Number(form.value.montant),
        reference_externe: form.value.reference_externe,
        date_paiement: form.value.date_paiement
      }
      resp = await paiementAchatService.create(payload)
      const id = Array.isArray(resp.data) ? resp.data[0] : resp.data.id ?? resp.data
      emit('created', id)
      router.push({ name: 'paiement-achat-detail', params: { id } })
    }
  } catch (err) {
    console.error('Erreur création paiement', err)
    alert(err.response?.data?.error || 'Erreur lors de la création du paiement')
  } finally {
    saving.value = false
  }
}
</script>

<style scoped>
.modal-backdrop {
  @apply fixed inset-0 bg-black/40 flex items-center justify-center z-50;
}
.modal-card {
  @apply bg-white rounded-lg w-full max-w-lg p-6 shadow-lg;
}
.modal-header {
  @apply flex items-center justify-between mb-4;
}
.modal-title {
  @apply text-lg font-semibold;
}
.form-grid {
  @apply grid grid-cols-1 md:grid-cols-2 gap-4;
}
</style>