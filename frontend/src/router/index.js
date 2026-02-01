import { createRouter, createWebHistory } from "vue-router"

import Dashboard from "../views/Dashboard.vue"

import Articles from "../views/stock/Articles.vue"
import ArticleDetail from "../views/stock/ArticleDetail.vue"
import Stock from "../views/stock/Stock.vue"

import ProformaFournisseur from "../views/achat/ProformaFournisseur.vue"
import ProformaFournisseurDetail from "../views/achat/ProformaFournisseurDetail.vue"
import BonCommandeAchat from "../views/achat/BonCommandeAchat.vue"
import FactureAchat from "../views/achat/FactureAchat.vue"
import ProformaDemandeAchat from "../views/achat/ProformaDemandeAchat.vue"
import ProformaDemandeAchatDetail from "../views/achat/ProformaDemandeAchatDetail.vue"

import DevisVente from "../views/vente/DevisVente.vue"
import BonCommandeVente from "../views/vente/BonCommandeVente.vue"
import FactureVente from "../views/vente/FactureVente.vue"

import Caisse from "../views/finance/Caisse.vue"
import Caisses from "../views/finance/Caisses.vue"
import CaisseDetail from "../views/finance/CaisseDetail.vue"
import MouvementsCaisse from "../views/finance/MouvementsCaisse.vue"
import MouvementCaisseDetail from "../views/finance/MouvementCaisseDetail.vue"
import MouvementCreate from "../views/finance/MouvementCreate.vue"
import PaiementsVente from "../views/finance/PaiementsVente.vue"
import PaiementVenteDetail from "../views/finance/PaiementVenteDetail.vue"
import PaiementsAchat from "../views/finance/PaiementsAchat.vue"
import PaiementAchatDetail from "../views/finance/PaiementAchatDetail.vue"

import Entreprises from "../views/parametres/Entreprises.vue"
import EntrepriseDetail from "../views/parametres/EntrepriseDetail.vue"
import SiteDetail from "../views/parametres/SiteDetail.vue"
import Personnel from "../views/parametres/Personnel.vue"
import StatsVentes from "../views/StatsVentes.vue"
import Login from "../views/Login.vue"
import Analytics from "../views/analytics/Analytics.vue"

const router = createRouter({
  history: createWebHistory(),
  routes: [
    { path: "/", name: "dashboard", component: Dashboard },
    { path: "/analytics", name: "analytics", component: Analytics },
    { path: "/articles", name: "articles", component: Articles },
    { path: "/articles/:id", name: "article-detail", component: ArticleDetail },
    { path: "/stock", name: "stock", component: Stock },
    { path: "/entreprises", name: "entreprises", component: Entreprises },
    { path: "/parametres/mode-paiements", name: "mode-paiements", component: () => import('@/views/parametres/ModePaiements.vue') },
    { path: "/entreprises/:id", name: "entreprise-detail", component: EntrepriseDetail },
    { path: "/sites/:id", name: "site-detail", component: SiteDetail },
    { path: "/personnel", name: "personnel", component: Personnel },
    { path: "/personnel/new", name: "personnel-new", component: () => import('../views/parametres/PersonnelDetail.vue') },
    { path: "/personnel/:id", name: "personnel-detail", component: () => import('../views/parametres/PersonnelDetail.vue') },
    
    { path: "/achats/proforma", name: "proforma-fournisseur", component: ProformaFournisseur },
    { path: "/achats/proforma/new", name: "proforma-fournisseur-new", component: ProformaFournisseurDetail },
    { path: "/achats/proforma/:id", name: "proforma-fournisseur-detail", component: ProformaFournisseurDetail },
    { path: "/achats/demande-achat", name: "proforma-demande-achat", component: ProformaDemandeAchat },
    { path: "/achats/demande-achat/new", name: "proforma-demande-achat-new", component: ProformaDemandeAchatDetail },
    { path: "/achats/demande-achat/:id", name: "proforma-demande-achat-detail", component: ProformaDemandeAchatDetail },
    { path: "/achats/bon-commande", name: "bon-commande-achat", component: BonCommandeAchat },
    { path: "/achats/factures", name: "facture-achat", component: FactureAchat },
    
    { path: "/ventes/devis", name: "devis-vente", component: DevisVente },
    { path: "/ventes/bon-commande", name: "bon-commande-vente", component: BonCommandeVente },
    { path: "/ventes/factures", name: "facture-vente", component: FactureVente },
    
    { path: "/caisse", name: "caisse", component: Caisse },
    { path: "/caisses", name: "caisses", component: Caisses },
    { path: "/caisses/:id", name: "caisse-detail", component: CaisseDetail },
    { path: "/mouvements-caisse", name: "mouvements-caisse", component: MouvementsCaisse },
    { path: "/mouvements-caisse/new", name: "mouvement-caisse-new", component: MouvementCreate },
    { path: "/mouvements-caisse/:id", name: "mouvement-caisse-detail", component: MouvementCaisseDetail },
    { path: "/paiements/vente", name: "paiements-vente", component: PaiementsVente },
    { path: "/paiements/vente/:id", name: "paiement-vente-detail", component: PaiementVenteDetail },
    { path: "/paiements/achat", name: "paiements-achat", component: PaiementsAchat },
    { path: "/paiements/achat/:id", name: "paiement-achat-detail", component: PaiementAchatDetail },
    { path: "/paiements/vente/new/:factureId", name: "paiement-vente-new", component: () => import('@/views/finance/PaymentCreate.vue'), props: (r) => ({ type: 'vente', factureId: Number(r.params.factureId) }) },
    { path: "/paiements/achat/new/:factureId", name: "paiement-achat-new", component: () => import('@/views/finance/PaymentCreate.vue'), props: (r) => ({ type: 'achat', factureId: Number(r.params.factureId) }) },
    { path: "/stats/ventes", name: "stats-ventes", component: StatsVentes },
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