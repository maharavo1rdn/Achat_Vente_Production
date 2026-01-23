import api from './api/api'

export default {
  getAll(filters = {}) {
    return api.get('/proforma-fournisseur', { params: filters })
  },
  getById(id) {
    return api.get(`/proforma-fournisseur/${id}`)
  },
  create(data) {
    return api.post('/proforma-fournisseur', data)
  },
  update(id, data) {
    return api.put(`/proforma-fournisseur/${id}`, data)
  },
  delete(id) {
    return api.delete(`/proforma-fournisseur/${id}`)
  },
  getDetails(id) {
    return api.get(`/proforma-fournisseur/${id}/details`)
  },
  valider(id, userId) {
    return api.post(`/proforma-fournisseur/${id}/valider`, { user_id: userId })
  }
}
