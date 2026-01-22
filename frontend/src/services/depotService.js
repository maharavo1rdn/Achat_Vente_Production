import api from './api/api'

export default {
  getAll(filters = {}) {
    return api.get('/depots', { params: filters })
  },

  getById(id) {
    return api.get(`/depots/${id}`)
  },

  getBySite(siteId) {
    return api.get(`/depots/site/${siteId}`)
  },

  getByEntreprise(entrepriseId) {
    return api.get(`/depots/entreprise/${entrepriseId}`)
  },

  create(depotData) {
    return api.post('/depots', depotData)
  },

  update(id, depotData) {
    return api.put(`/depots/${id}`, depotData)
  },

  delete(id) {
    return api.delete(`/depots/${id}`)
  }
}
