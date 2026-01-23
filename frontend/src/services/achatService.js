import api from './api/api'

// Service pour la gestion des achats (Proforma, BC, Factures)
export default {
  // Legacy / shop-specific actions for proforma (only conversion left here)
  convertProformaToBonCommande(id) {
    return api.post(`/achats/proforma/${id}/convert-bc`)
  },

  // Bons de Commande Achat
  bonCommande: {
    getAll(filters = {}) {
      return api.get('/achats/bon-commande', { params: filters })
    },
    getById(id) {
      return api.get(`/achats/bon-commande/${id}`)
    },
    create(data) {
      return api.post('/achats/bon-commande', data)
    },
    update(id, data) {
      return api.put(`/achats/bon-commande/${id}`, data)
    },
    delete(id) {
      return api.delete(`/achats/bon-commande/${id}`)
    },
    convertToFacture(id) {
      return api.post(`/achats/bon-commande/${id}/convert-facture`)
    }
  },

  // Factures Achat
  facture: {
    getAll(filters = {}) {
      return api.get('/achats/factures', { params: filters })
    },
    getById(id) {
      return api.get(`/achats/factures/${id}`)
    },
    create(data) {
      return api.post('/achats/factures', data)
    },
    update(id, data) {
      return api.put(`/achats/factures/${id}`, data)
    },
    delete(id) {
      return api.delete(`/achats/factures/${id}`)
    },
    payer(id, montant, caisseId) {
      return api.post(`/achats/factures/${id}/payer`, { montant, caisse_id: caisseId })
    }
  }
}
