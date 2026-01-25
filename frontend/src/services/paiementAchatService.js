import api from './api/api'

export default {
  create(payload) {
    return api.post('/paiements/achat', payload)
  },
  getById(id) {
    return api.get(`/paiements/achat/${id}`)
  },
  validate(id, payload) {
    return api.post(`/paiements/achat/${id}/validate`, payload)
  },
  update(id, payload) {
    return api.put(`/paiements/achat/${id}`, payload)
  },
  filters(params) {
    return api.get('/paiements/achat/filters', { params })
  },
  list(filters) {
    return api.get('/paiements/achat', { params: filters })
  }
}
