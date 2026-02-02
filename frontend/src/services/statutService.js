import api from './api/api'

export default {
  getAll() {
    return api.get('/ventes/statuts')
  }
}
