import api from "./api/api";

// Service pour la gestion des articles
export default {
  // Récupérer tous les articles
  getAll() {
    return api.get("/articles");
  },

  getAllCategories() {
    return api.get("/articles/categories");
  },

  // Récupérer un article par ID
  getById(id) {
    return api.get(`/articles/${id}`);
  },

  // Créer un nouvel article
  create(articleData) {
    return api.post("/articles", articleData);
  },

  // Mettre à jour un article
  update(id, articleData) {
    return api.put(`/articles/${id}`, articleData);
  },

  // Supprimer un article
  delete(id) {
    return api.delete(`/articles/${id}`);
  },

  // Récupérer les articles par catégorie
  getByCategorie(categorieId) {
    return api.get(`/articles/categorie/${categorieId}`);
  },

  // Récupérer le stock par article
  getStockByArticle(articleId) {
    return api.get(`/articles/${articleId}/stock`);
  },

  // Récupérer les mouvements par article
  getMouvementsByArticle(articleId) {
    return api.get(`/articles/${articleId}/mouvements`);
  },
};
