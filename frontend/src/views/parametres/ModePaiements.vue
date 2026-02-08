<template>
  <div class="page-container">
    <div class="page-header">
      <div>
        <h1 class="page-title">Modes de Paiement</h1>
        <p class="page-subtitle">Gérer les modes de paiement</p>
      </div>
      <div>
        <button @click="openCreate" class="btn-primary">Nouveau mode</button>
      </div>
    </div>

    <div class="card">
      <div class="table-wrapper">
        <table class="table">
          <thead>
            <tr>
              <th>ID</th>
              <th>Code</th>
              <th>Libellé</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="m in modes" :key="m.id">
              <td>{{ m.id }}</td>
              <td>{{ m.code || '-' }}</td>
              <td>{{ m.libelle || '-' }}</td>
              <td>
                <button @click="editMode(m)" class="action-btn">Éditer</button>
                <button @click="removeMode(m.id)" class="action-btn text-red-600">Supprimer</button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <div v-if="showForm" class="card mt-4">
      <div class="card-header-simple"><h2 class="card-title">{{ editing ? 'Éditer' : 'Nouveau' }}</h2></div>
      <div class="form-grid">
        <div>
          <label class="label">Code</label>
          <input v-model="form.code" class="input" />
        </div>
        <div>
          <label class="label">Libellé</label>
          <input v-model="form.libelle" class="input" />
        </div>
      </div>
      <div class="mt-4">
        <button @click="save" class="btn-primary">Sauvegarder</button>
        <button @click="cancel" class="btn-ghost">Annuler</button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import modePaiementService from '@/services/modePaiementService'

const modes = ref([])
const showForm = ref(false)
const editing = ref(false)
const form = ref({ id: null, code: '', libelle: '' })

const load = async () => {
  try {
    const resp = await modePaiementService.getAll()
    modes.value = resp.data ?? resp
  } catch (err) {
    console.error('Erreur chargement modes', err)
  }
}

const openCreate = () => { form.value = { id: null, code: '', libelle: '' }; editing.value = false; showForm.value = true }
const editMode = (m) => { form.value = { ...m }; editing.value = true; showForm.value = true }
const cancel = () => { showForm.value = false }

const save = async () => {
  try {
    if (editing.value) {
      await modePaiementService.update(form.value.id, { code: form.value.code, libelle: form.value.libelle })
    } else {
      await modePaiementService.create({ code: form.value.code, libelle: form.value.libelle })
    }
    showForm.value = false
    await load()
  } catch (err) {
    console.error('Erreur sauvegarde mode', err)
    alert(err.response?.data?.error || 'Erreur lors de la sauvegarde')
  }
}

const removeMode = async (id) => {
  if (!confirm('Confirmer suppression ?')) return
  try {
    await modePaiementService.delete(id)
    await load()
  } catch (err) {
    console.error('Erreur suppression mode', err)
    alert(err.response?.data?.error || 'Erreur lors de la suppression')
  }
}

onMounted(load)
</script>