import api from './api/api'

// Service pour la gestion de la caisse
export default {
  // Récupérer toutes les caisses
  getCaisses(filialeId = null) {
    const params = filialeId ? { filiale_id: filialeId } : {}
    return api.get('/caisse', { params })
  },

  // Récupérer une caisse par ID
  getCaisseById(id) {
    return api.get(`/caisse/${id}`)
  },

  // Créer une nouvelle caisse
  createCaisse(caisseData) {
    return api.post('/caisse', caisseData)
  },

  // Mettre à jour une caisse
  updateCaisse(id, caisseData) {
    return api.put(`/caisse/${id}`, caisseData)
  },

  // Récupérer les mouvements de caisse
  getMouvements(filters = {}) {
    return api.get('/caisse/mouvements', { params: filters })
  },

  // Récupérer un mouvement par ID
  getMouvementById(id) {
    return api.get(`/caisse/mouvements/${id}`)
  },

  // Mettre à jour un mouvement
  updateMouvement(id, data) {
    return api.put(`/caisse/mouvements/${id}`, data)
  },

  // Valider un mouvement
  validateMouvement(id) {
    return api.post(`/caisse/mouvements/${id}/validate`)
  },

  // Créer une entrée de caisse
  createEntree(caisseId, data) {
    return api.post(`/caisse/${caisseId}/entree`, data)
  },

  // Créer une sortie de caisse
  createSortie(caisseId, data) {
    return api.post(`/caisse/${caisseId}/sortie`, data)
  },

  // Créer un mouvement avec entrée et/ou sortie
  createMouvement(caisseId, data) {
    return api.post(`/caisse/${caisseId}/mouvement`, data)
  },

  // Récupérer le solde actuel
  getSolde(caisseId) {
    return api.get(`/caisse/${caisseId}/solde`)
  }
}
