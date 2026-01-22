import { createRouter, createWebHistory } from "vue-router"

import Dashboard from "../views/Dashboard.vue"

import Articles from "../views/stock/Articles.vue"
import ArticleDetail from "../views/stock/ArticleDetail.vue"
import Stock from "../views/stock/Stock.vue"

import ProformaFournisseur from "../views/achat/ProformaFournisseur.vue"
import BonCommandeAchat from "../views/achat/BonCommandeAchat.vue"
import FactureAchat from "../views/achat/FactureAchat.vue"

import DevisVente from "../views/vente/DevisVente.vue"
import BonCommandeVente from "../views/vente/BonCommandeVente.vue"
import FactureVente from "../views/vente/FactureVente.vue"

import Caisse from "../views/finance/Caisse.vue"

import Entreprises from "../views/parametres/Entreprises.vue"
import EntrepriseDetail from "../views/parametres/EntrepriseDetail.vue"
import SiteDetail from "../views/parametres/SiteDetail.vue"
import Personnel from "../views/parametres/Personnel.vue"
import Login from "../views/Login.vue"

const router = createRouter({
  history: createWebHistory(),
  routes: [
    { path: "/", name: "dashboard", component: Dashboard },
    { path: "/articles", name: "articles", component: Articles },
    { path: "/articles/:id", name: "article-detail", component: ArticleDetail },
    { path: "/stock", name: "stock", component: Stock },
    { path: "/entreprises", name: "entreprises", component: Entreprises },
    { path: "/entreprises/:id", name: "entreprise-detail", component: EntrepriseDetail },
    { path: "/sites/:id", name: "site-detail", component: SiteDetail },
    { path: "/personnel", name: "personnel", component: Personnel },
    { path: "/personnel/new", name: "personnel-new", component: () => import('../views/parametres/PersonnelDetail.vue') },
    { path: "/personnel/:id", name: "personnel-detail", component: () => import('../views/parametres/PersonnelDetail.vue') },
    
    { path: "/achats/proforma", name: "proforma-fournisseur", component: ProformaFournisseur },
    { path: "/achats/bon-commande", name: "bon-commande-achat", component: BonCommandeAchat },
    { path: "/achats/factures", name: "facture-achat", component: FactureAchat },
    
    { path: "/ventes/devis", name: "devis-vente", component: DevisVente },
    { path: "/ventes/bon-commande", name: "bon-commande-vente", component: BonCommandeVente },
    { path: "/ventes/factures", name: "facture-vente", component: FactureVente },
    
    { path: "/caisse", name: "caisse", component: Caisse },
    { path: "/login", name: "login", component: Login, meta: { hideSidebar: true } },
  ],
})

// Simple navigation guard: redirect to /login if not authenticated
router.beforeEach((to, from, next) => {
  const publicPages = ['login']
  const authRequired = !publicPages.includes(to.name)
  const user = localStorage.getItem('user')

  if (authRequired && !user) {
    next({ name: 'login' })
  } else {
    next()
  }
})

export default router;