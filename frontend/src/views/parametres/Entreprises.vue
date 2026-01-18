<template>
  <div class="page-container">
    <!-- Header -->
    <div class="page-header">
      <div>
        <h1 class="page-title">Gestion des Entreprises</h1>
        <p class="page-subtitle">Clients, fournisseurs et filiales</p>
      </div>
      <button @click="openCreateModal" class="btn-primary">
        <Plus class="w-5 h-5 mr-2" />
        Nouvelle Entreprise
      </button>
    </div>

    <!-- Filters -->
    <div class="card mb-6">
      <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div>
          <label class="label">Rechercher</label>
          <input v-model="searchQuery" type="text" class="input" placeholder="Nom, matricule..." />
        </div>
        <div>
          <label class="label">Type</label>
          <select v-model="selectedType" class="select">
            <option value="">Tous les types</option>
            <option value="CLIENT">Client</option>
            <option value="FOURNISSEUR">Fournisseur</option>
            <option value="INTERNE">Interne (Filiale)</option>
            <option value="PARTENAIRE">Partenaire</option>
          </select>
        </div>
        <div>
          <label class="label">Statut</label>
          <select v-model="selectedStatut" class="select">
            <option value="">Tous</option>
            <option value="actif">Actif</option>
            <option value="inactif">Inactif</option>
          </select>
        </div>
      </div>
    </div>

    <!-- Table -->
    <div class="card">
      <!-- Loading state -->
      <div v-if="loading" class="text-center py-8">
        <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600 mx-auto"></div>
        <p class="mt-2 text-gray-600">Chargement des entreprises...</p>
      </div>

      <!-- Error state -->
      <div v-else-if="error" class="text-center py-8">
        <div class="text-red-600 mb-2">
          <svg class="w-8 h-8 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
          </svg>
        </div>
        <p class="text-red-600">{{ error }}</p>
        <button @click="loadEntreprises" class="mt-2 px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
          Réessayer
        </button>
      </div>

      <!-- Data table -->
      <table v-else class="table">
        <thead>
          <tr>
            <th>Nom</th>
            <th>Type</th>
            <th>Matricule Fiscal</th>
            <th>Téléphone</th>
            <th>Email</th>
            <th>Adresse</th>
            <th>Statut</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="entreprise in filteredEntreprises" :key="entreprise.id">
            <td class="font-medium">{{ entreprise.nom }}</td>
            <td>
              <span :class="getTypeBadgeClass(entreprise.type_entreprise)" class="badge">
                {{ entreprise.type_entreprise }}
              </span>
            </td>
            <td>{{ entreprise.matricule_fiscal || '-' }}</td>
            <td>{{ entreprise.telephone || '-' }}</td>
            <td class="text-sm">{{ entreprise.email || '-' }}</td>
            <td class="text-sm">{{ entreprise.adresse || '-' }}</td>
            <td>
              <span :class="entreprise.est_actif ? 'badge-success' : 'badge-danger'" class="badge">
                {{ entreprise.est_actif ? 'Actif' : 'Inactif' }}
              </span>
            </td>
            <td>
              <div class="flex gap-2">
                <button @click="editEntreprise(entreprise)" class="text-blue-600 hover:text-blue-800">
                  <Edit class="w-4 h-4" />
                </button>
                <button @click="deleteEntreprise(entreprise.id)" class="text-red-600 hover:text-red-800">
                  <Trash2 class="w-4 h-4" />
                </button>
              </div>
            </td>
          </tr>
          <tr v-if="filteredEntreprises.length === 0">
            <td colspan="8" class="text-center py-8 text-gray-500">
              Aucune entreprise trouvée
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { Plus, Edit, Trash2 } from 'lucide-vue-next'
import entrepriseService from '@/services/entrepriseService'

const searchQuery = ref('')
const selectedType = ref('')
const selectedStatut = ref('')
const entreprises = ref([])
const loading = ref(false)
const error = ref(null)

const filteredEntreprises = computed(() => {
  return entreprises.value.filter(e => {
    const matchSearch = !searchQuery.value || 
      e.nom?.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
      (e.matricule_fiscal && e.matricule_fiscal.toLowerCase().includes(searchQuery.value.toLowerCase()))
    
    const matchType = !selectedType.value || e.type_entreprise === selectedType.value
    const matchStatut = !selectedStatut.value || 
      (selectedStatut.value === 'actif' && e.est_actif) ||
      (selectedStatut.value === 'inactif' && !e.est_actif)
    
    return matchSearch && matchType && matchStatut
  })
})

const getTypeBadgeClass = (type) => {
  switch(type) {
    case 'CLIENT': return 'badge-success'
    case 'FOURNISSEUR': return 'badge-warning'
    case 'INTERNE': return 'badge-primary'
    case 'PARTENAIRE': return 'badge-info'
    default: return 'badge-secondary'
  }
}

const loadEntreprises = async () => {
  loading.value = true
  error.value = null
  try {
    const response = await entrepriseService.getAll()
    entreprises.value = response.data || []
  } catch (err) {
    error.value = 'Erreur lors du chargement des entreprises'
    console.error('Erreur chargement entreprises:', err)
  } finally {
    loading.value = false
  }
}

const openCreateModal = () => {
  console.log('Open create modal')
}

const editEntreprise = (entreprise) => {
  console.log('Edit entreprise:', entreprise)
}

const deleteEntreprise = async (id) => {
  if (confirm('Êtes-vous sûr de vouloir supprimer cette entreprise ?')) {
    try {
      await entrepriseService.delete(id)
      entreprises.value = entreprises.value.filter(e => e.id !== id)
      alert('Entreprise supprimée avec succès')
    } catch (err) {
      alert('Erreur lors de la suppression')
      console.error('Erreur suppression entreprise:', err)
    }
  }
}

// Charger les données au montage du composant
onMounted(() => {
  loadEntreprises()
})
</script>

<style scoped>
.page-container {
  @apply p-8 ml-64;
}

.page-header {
  @apply flex items-center justify-between mb-8;
}

.page-title {
  @apply text-3xl font-bold text-gray-900 mb-2;
}

.page-subtitle {
  @apply text-gray-600;
}
</style>
