import api from './api/api'

export default {
  getAllStats() {
    return api.get('/finance/stats')
  },
  getEncoursClients() {
    return api.get('/finance/stats/encours-clients')
  },
  getEncoursFournisseurs() {
    return api.get('/finance/stats/encours-fournisseurs')
  },
  getTresorerie() {
    return api.get('/finance/stats/tresorerie')
  },
  getBFR() {
    return api.get('/finance/stats/bfr')
  }
}
