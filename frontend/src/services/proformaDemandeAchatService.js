import api from './api/api'

export default {
  getAll(filters = {}) {
    return api.get('/proforma-demande-achat', { params: filters })
  },

  getById(id) {
    return api.get(`/proforma-demande-achat/${id}`)
  },

  create(data) {
    return api.post('/proforma-demande-achat', data)
  },

  update(id, data) {
    return api.put(`/proforma-demande-achat/${id}`, data)
  },

  delete(id) {
    return api.delete(`/proforma-demande-achat/${id}`)
  },

  getDetails(id) {
    return api.get(`/proforma-demande-achat/${id}/details`)
  },

  valider(id, userId) {
    return api.post(`/proforma-demande-achat/${id}/valider`, { user_id: userId })
  },

  annuler(id) {
    return api.post(`/proforma-demande-achat/${id}/annuler`)
  },

  genererProforma(id, payload) {
    return api.post(`/proforma-demande-achat/${id}/generer-proforma`, payload)
  }
}
