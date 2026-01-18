import api from './api/api'

// Service pour la gestion des entreprises
export default {
  // Récupérer toutes les entreprises
  getAll(filters = {}) {
    return api.get('/entreprises', { params: filters })
  },

  // Récupérer une entreprise par ID
  getById(id) {
    return api.get(`/entreprises/${id}`)
  },

  // Créer une nouvelle entreprise
  create(entrepriseData) {
    return api.post('/entreprises', entrepriseData)
  },

  // Mettre à jour une entreprise
  update(id, entrepriseData) {
    return api.put(`/entreprises/${id}`, entrepriseData)
  },

  // Supprimer une entreprise
  delete(id) {
    return api.delete(`/entreprises/${id}`)
  },

  // Récupérer les entreprises par type
  getByType(type) {
    return api.get('/entreprises', { params: { type_entreprise: type } })
  },

  // Récupérer les clients
  getClients() {
    return this.getByType('CLIENT')
  },

  // Récupérer les fournisseurs
  getFournisseurs() {
    return this.getByType('FOURNISSEUR')
  },

  // Récupérer les filiales
  getFiliales() {
    return this.getByType('INTERNE')
  }
}
