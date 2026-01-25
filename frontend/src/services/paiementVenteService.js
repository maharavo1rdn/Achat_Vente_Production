import api from './api/api'

export default {
  create(payload) {
    return api.post('/paiements/vente', payload)
  },
  getById(id) {
    return api.get(`/paiements/vente/${id}`)
  },
  validate(id, payload) {
    return api.post(`/paiements/vente/${id}/validate`, payload)
  },
  update(id, payload) {
    return api.put(`/paiements/vente/${id}`, payload)
  },
  filters(params) {
    return api.get('/paiements/vente/filters', { params })
  },
  list(filters) {
    return api.get('/paiements/vente', { params: filters })
  }
}
