import api from './api/api'

// Service pour la gestion des ventes (Devis, BC, Factures)
export default {
  // Devis Vente
  devis: {
    getAll(filters = {}) {
      return api.get('/ventes/devis', { params: filters })
    },
    getById(id) {
      return api.get(`/ventes/devis/${id}`)
    },
    create(data) {
      return api.post('/ventes/devis', data)
    },
    update(id, data) {
      return api.put(`/ventes/devis/${id}`, data)
    },
    updateStatut(id, statutCode) {
      return api.patch(`/ventes/devis/${id}/statut`, { statut_code: statutCode })
    },
    delete(id) {
      return api.delete(`/ventes/devis/${id}`)
    },
    convertToBonCommande(id) {
      return api.post(`/ventes/devis/${id}/convert-bc`)
    }
  },

  // Bons de Commande Vente
  bonCommande: {
    getAll(filters = {}) {
      return api.get('/ventes/bon-commande', { params: filters })
    },
    getById(id) {
      return api.get(`/ventes/bon-commande/${id}`)
    },
    create(data) {
      return api.post('/ventes/bon-commande', data)
    },
    update(id, data) {
      return api.put(`/ventes/bon-commande/${id}`, data)
    },
    delete(id) {
      return api.delete(`/ventes/bon-commande/${id}`)
    },
    convertToFacture(id) {
      return api.post(`/ventes/bon-commande/${id}/convert-facture`)
    },
    convertToFactureWithCustomData(id, customData) {
      return api.post(`/ventes/bon-commande/${id}/convert-facture-custom`, customData)
    },
    checkIfFactured(id) {
      return api.get(`/ventes/bon-commande/${id}/is-factured`)
    }
  },

  // Factures Vente
  facture: {
    getAll(filters = {}) {
      return api.get('/ventes/factures', { params: filters })
    },
    getById(id) {
      return api.get(`/ventes/factures/${id}`)
    },
    create(data) {
      return api.post('/ventes/factures', data)
    },
    update(id, data) {
      return api.put(`/ventes/factures/${id}`, data)
    },
    delete(id) {
      return api.delete(`/ventes/factures/${id}`)
    },
    encaisser(id, montant, caisseId) {
      return api.post(`/ventes/factures/${id}/encaisser`, { montant, caisse_id: caisseId })
    },
    livrer(id) {
      return api.post(`/ventes/factures/${id}/livrer`)
    }
  }
}
