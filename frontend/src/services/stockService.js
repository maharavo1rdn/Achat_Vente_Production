import api from './api/api'

// Service pour la gestion du stock
export default {
  // Récupérer l'état du stock
  getStock(filialeId = null) {
    const params = filialeId ? { filiale_id: filialeId } : {}
    return api.get('/stock', { params })
  },

  // Récupérer le stock d'un article spécifique
  getStockByArticle(articleId, filialeId) {
    return api.get(`/stock/article/${articleId}`, { params: { filiale_id: filialeId } })
  },

  // Récupérer les mouvements de stock
  getMouvements(filters = {}) {
    return api.get('/stock/mouvements', { params: filters })
  },

  // Créer un mouvement de stock
  createMouvement(mouvementData) {
    return api.post('/stock/mouvements', mouvementData)
  },

  // Récupérer l'historique d'un article
  getHistoriqueArticle(articleId, filialeId) {
    return api.get(`/stock/historique/${articleId}`, { params: { filiale_id: filialeId } })
  }
}
