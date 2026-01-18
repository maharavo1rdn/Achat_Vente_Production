import api from './api/api'

// Service pour la gestion du personnel
export default {
  // Récupérer tout le personnel
  getAll(filters = {}) {
    return api.get('/personnel', { params: filters })
  },

  // Récupérer un personnel par ID
  getById(id) {
    return api.get(`/personnel/${id}`)
  },

  // Créer un nouveau personnel
  create(personnelData) {
    return api.post('/personnel', personnelData)
  },

  // Mettre à jour un personnel
  update(id, personnelData) {
    return api.put(`/personnel/${id}`, personnelData)
  },

  // Supprimer un personnel
  delete(id) {
    return api.delete(`/personnel/${id}`)
  },

  // Réinitialiser le mot de passe
  resetPassword(id, newPassword) {
    return api.post(`/personnel/${id}/reset-password`, { password: newPassword })
  },

  // Récupérer le personnel par rôle
  getByRole(roleId) {
    return api.get(`/personnel/role/${roleId}`)
  },

  // Récupérer le personnel par filiale
  getByFiliale(filialeId) {
    return api.get(`/personnel/filiale/${filialeId}`)
  }
}
