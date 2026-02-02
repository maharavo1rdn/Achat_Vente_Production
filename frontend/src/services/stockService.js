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

  // Récupérer le stock valorisé (par dépôt / site / filiale)
  getValorise(filters = {}) {
    return api.get('/stock/valorise', { params: filters })
  },

  // Récupérer le stock consolidé au niveau groupe
  getConsolideGroupe() {
    return api.get('/stock/consolide-groupe')
  },

  // Récupérer la structure organisationnelle (groupes, entreprises, sites, dépôts)
  getStructureOrganisation() {
    return api.get('/stock/structure')
  },

  // Créer un mouvement de stock
  createMouvement(mouvementData) {
    return api.post('/stock/mouvements', mouvementData)
  },

  // Récupérer l'historique d'un article
  getHistoriqueArticle(articleId, filialeId) {
    return api.get(`/stock/historique/${articleId}`, { params: { filiale_id: filialeId } })
  },

  // Récupérer les informations d'un dépôt avec sa méthode de valorisation
  getDepotInfo(depotId) {
    return api.get(`/stock/depot/${depotId}/info`)
  },

  // Récupérer le stock d'un dépôt spécifique
  getStockByDepot(depotId) {
    return api.get(`/stock/depot/${depotId}/articles`)
  },

  // Récupérer les lots d'un dépôt spécifique
  getLotsByDepot(depotId) {
    return api.get(`/stock/depot/${depotId}/lots`)
  },

  // Récupérer les mouvements d'un article dans un dépôt
  getMouvementsByArticle(articleId, depotId) {
    return api.get(`/stock/mouvements/article/${articleId}`, { params: { depot_id: depotId } })
  }
}
