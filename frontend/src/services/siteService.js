import api from './api/api'

export default {
  getAll(filters = {}) {
    return api.get('/sites', { params: filters })
  },

  getById(id) {
    return api.get(`/sites/${id}`)
  },

  getByEntreprise(entrepriseId) {
    return api.get(`/sites/entreprise/${entrepriseId}`)
  },

  create(siteData) {
    return api.post('/sites', siteData)
  },

  update(id, siteData) {
    return api.put(`/sites/${id}`, siteData)
  },

  delete(id) {
    return api.delete(`/sites/${id}`)
  }
}
