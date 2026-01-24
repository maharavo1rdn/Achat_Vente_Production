import api from './api/api'

export default {
  getTauxRotationStock(params = {}) {
    return api.get('/stock/stats/rotation', { params })
  },
  getValeurStockImmobilise(params = {}) {
    return api.get('/stock/stats/valeur-immobilise', { params })
  },
  getArticlesRupture(params = {}) {
    return api.get('/stock/stats/articles-rupture', { params })
  },
  getDureeStockMoyenne(params = {}) {
    return api.get('/stock/stats/duree-moyenne', { params })
  }
}