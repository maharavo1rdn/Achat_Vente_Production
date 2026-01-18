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
  getMouvements(caisseId = null, filters = {}) {
    const url = caisseId ? `/caisse/${caisseId}/mouvements` : '/caisse/mouvements'
    return api.get(url, { params: filters })
  },

  // Créer une entrée de caisse
  createEntree(caisseId, data) {
    return api.post(`/caisse/${caisseId}/entree`, data)
  },

  // Créer une sortie de caisse
  createSortie(caisseId, data) {
    return api.post(`/caisse/${caisseId}/sortie`, data)
  },

  // Récupérer le solde actuel
  getSolde(caisseId) {
    return api.get(`/caisse/${caisseId}/solde`)
  }
}
