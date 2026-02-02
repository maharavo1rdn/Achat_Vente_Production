// New file: src/services/statCommercialService.js

import api from './api/api'

export default {
  getPerformanceCommercial(params = {}) {
    return api.get('/commercial/stats/performance', { params })
  },
  getTauxFidelisation(params = {}) {
    return api.get('/commercial/stats/fidelisation', { params })
  },
  getNouveauxClients(params = {}) {
    return api.get('/commercial/stats/nouveaux-clients', { params })
  }
}