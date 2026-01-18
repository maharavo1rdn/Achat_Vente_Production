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
import Personnel from "../views/parametres/Personnel.vue"

const router = createRouter({
  history: createWebHistory(),
  routes: [
    { path: "/", name: "dashboard", component: Dashboard },
    { path: "/articles", name: "articles", component: Articles },
    { path: "/articles/:id", name: "article-detail", component: ArticleDetail },
    { path: "/stock", name: "stock", component: Stock },
    { path: "/entreprises", name: "entreprises", component: Entreprises },
    { path: "/personnel", name: "personnel", component: Personnel },
    
    { path: "/achats/proforma", name: "proforma-fournisseur", component: ProformaFournisseur },
    { path: "/achats/bon-commande", name: "bon-commande-achat", component: BonCommandeAchat },
    { path: "/achats/factures", name: "facture-achat", component: FactureAchat },
    
    { path: "/ventes/devis", name: "devis-vente", component: DevisVente },
    { path: "/ventes/bon-commande", name: "bon-commande-vente", component: BonCommandeVente },
    { path: "/ventes/factures", name: "facture-vente", component: FactureVente },
    
    { path: "/caisse", name: "caisse", component: Caisse },
  ],
})

export default router