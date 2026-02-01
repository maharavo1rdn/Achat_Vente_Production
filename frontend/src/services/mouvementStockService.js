// src/services/mouvementService.js
import api from './api/api'

// Service pour la gestion des mouvements de stock
export default {
  // ==================================================
  // SITUATION ET ANALYSE
  // ==================================================
  
  // Récupérer la situation globale du stock
  getSituationGlobale(filters = {}) {
    return api.get('/mouvements-stock/situation-globale', { params: filters })
  },

  // Récupérer la situation du stock par dépôt
  getSituationParDepot(filters = {}) {
    return api.get('/mouvements-stock/situation-depot', { params: filters })
  },

  // Récupérer les dernières sorties
  getDernieresSorties(limit = 10, filters = {}) {
    return api.get('/mouvements-stock/dernieres-sorties', { 
      params: { limit, ...filters } 
    })
  },

  // Récupérer les dernières transactions (tous types)
  getDernieresTransactions(limit = 20, filters = {}) {
    return api.get('/mouvements-stock/dernieres-transactions', { 
      params: { limit, ...filters } 
    })
  },

  // ==================================================
  // GESTION FIFO
  // ==================================================
  
  // Calculer les dépôts FIFO pour une sortie
  getDepotFIFOPourSortie(articleId, quantite) {
    return api.get(`/mouvements-stock/fifo/${articleId}`, { 
      params: { quantite } 
    })
  },

  // Créer une sortie avec FIFO
  creerSortieAvecFIFO(sortieData) {
    return api.post('/mouvements-stock/sortie-fifo', sortieData)
  },

  // ==================================================
  // GESTION DES MOUVEMENTS
  // ==================================================
  
  // Récupérer les mouvements détaillés
  getMouvementsDetailles(filters = {}) {
    // Note: La route GET /mouvements-stock doit exister
    return api.get('/mouvements-stock', { params: filters })
  },

  // Créer un mouvement simple
  createMouvement(mouvementData) {
    return api.post('/mouvements-stock', mouvementData)
  },

  // Récupérer les mouvements par article
  getMouvementsParArticle(articleId, filters = {}) {
    return api.get(`/mouvements-stock/article/${articleId}`, { params: filters })
  },

  // Récupérer les mouvements par dépôt
  getMouvementsParDepot(depotId, filters = {}) {
    return api.get(`/mouvements-stock/depot/${depotId}`, { params: filters })
  },

  // Récupérer les mouvements par type
  getMouvementsParType(type, filters = {}) {
    return api.get(`/mouvements-stock/type/${type}`, { params: filters })
  },

  // Récupérer les mouvements par période
  getMouvementsParPeriode(dateDebut, dateFin, filters = {}) {
    return api.get('/mouvements-stock/periode', { 
      params: { date_debut: dateDebut, date_fin: dateFin, ...filters } 
    })
  },

  // Rechercher des mouvements
  searchMouvements(searchParams = {}) {
    return api.get('/mouvements-stock/search', { params: searchParams })
  },

  // ==================================================
  // STATISTIQUES ET RAPPORTS
  // ==================================================
  
  // Récupérer les statistiques des mouvements
  getStatistiquesMouvements(filters = {}) {
    return api.get('/mouvements-stock/statistiques', { params: filters })
  },

  // ==================================================
  // INFORMATIONS DE STOCK
  // ==================================================
  
  // Récupérer le stock actuel par dépôt
  getStockActuelParDepot(articleId, depotId) {
    return api.get(`/mouvements-stock/stock-actuel/${articleId}/${depotId}`)
  },

  // ==================================================
  // UTILITAIRES
  // ==================================================
  
  // Formatage des types de mouvement
  getTypeMouvementLabel(type) {
    const types = {
      'ACHAT': 'Achat',
      'VENTE': 'Vente',
      'INVENTAIRE': 'Inventaire',
      'TRANSFERT': 'Transfert'
    }
    return types[type] || type
  },

  // Formatage des quantités
  formatQuantite(quantite, type) {
    const sign = type === 'VENTE' ? '-' : '+'
    return `${sign}${parseFloat(quantite).toFixed(2)}`
  },

  // Calcul du stock disponible
  calculerStockDisponible(entrees, sorties) {
    const totalEntrees = entrees.reduce((sum, e) => sum + parseFloat(e), 0)
    const totalSorties = sorties.reduce((sum, s) => sum + parseFloat(s), 0)
    return totalEntrees - totalSorties
  },

  // Validation des données de mouvement
  validateMouvementData(data) {
    const errors = []
    
    if (!data.type_mouvement) {
      errors.push('Le type de mouvement est requis')
    }
    
    if (!data.article_id) {
      errors.push("L'article est requis")
    }
    
    if (!data.depot_id) {
      errors.push('Le dépôt est requis')
    }
    
    if (!data.personnel_id) {
      errors.push('Le personnel est requis')
    }
    
    const hasEntree = data.quantite_entree && data.quantite_entree > 0
    const hasSortie = data.quantite_sortie && data.quantite_sortie > 0
    
    if (!hasEntree && !hasSortie) {
      errors.push('Au moins une quantité doit être spécifiée')
    }
    
    if (hasEntree && hasSortie) {
      errors.push('Un mouvement ne peut pas avoir à la fois une entrée et une sortie')
    }
    
    if (data.prix_unitaire_mouvement && data.prix_unitaire_mouvement < 0) {
      errors.push('Le prix unitaire ne peut pas être négatif')
    }
    
    return {
      isValid: errors.length === 0,
      errors
    }
  }
}