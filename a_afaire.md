# 🚀 Plan de Développement ERP : Gestion de Groupe (Multi-Sociétés & Multi-Dépôts)

Ce document définit les tâches de développement pour le système ERP basé sur le framework **Flight PHP** (Backend) et **Vue.js** (Frontend).

## 🏗️ Architecture Technique
- **Backend :** Flight PHP API.
- **Frontend :** Vue.js 3 (SFC).
- **Base de données :** PostgreSQL (Schéma `achat_vente_db`).
- **Valorisation :** Gestion spécifique par société (CMUP, FIFO, LIFO).

---

## 📦 MODULE 1 : Structure du Groupe & Référentiel(Maharavo)
**Responsable : Développeur 1**
### 1.1. Gestion des Entreprises (Sociétés et Filiales)
* **Saisie-Société** (optionnel)
    * **Métier :** Classe `EntrepriseModel`, fonction `save(data)`. Validation du type (CLIENT, FOURNISSEUR, INTERNE).
    * **Base :** Table `entreprise`.
    * **Intégration :** Page `FormEntreprise.vue` (Interface de saisie requise).
* **Liste-Société**
    * **Métier :** Classe `EntrepriseModel`, fonction `findAll(type)`.
    * **Base :** Table `entreprise`.
    * **Intégration :** Page `ListeEntreprise.vue`.
* **Fiche-Société**
    * **Métier :** Classe `EntrepriseModel`, fonction `findAllById(type)`, `getDetailSociete(type)` .
    * **Base :** Table `entreprise`.
    * **Intégration :** Page `FicheEntreprise.vue`.

### 1.2. Personnel et Accès
* **Fiche-Employé**
    * **Métier :** Classe `PersonnelModel`, fonction `getDetails(id)`. Gestion des rôles et niveaux d'accès.
    * **Base :** Tables `personnel`, `personnel_role`.
    * **Intégration :** Page `DetailsEmp.vue`.

---

## 🛒 MODULE 2 : Flux des Achats(Irintsoa)
**Responsable : Développeur 2**

### 2.1. Bons de Commande Achat (BC)
* **Saisie-BC-Achat**
    * **Métier :** Classe `AchatModel`, fonction `createBC(header, details)`. 
        * Calcul automatique du montant TTC.
    * **Base :** Tables `bon_commande_achat`, `bon_commande_achat_details`.
    * **Intégration :** Page `SaisieBCAchat.vue` (Interface de saisie avec grille d'articles dynamique).
* **Liste-BC-Achat**
    * **Métier :** Classe `AchatModel`, fonction `listBCByFiliale(filiale_id)`.
    * **Base :** Table `bon_commande_achat`, jointure `statut`.
    * **Intégration :** Page `ListeBCAchat.vue`.

### 2.2. Facturation Fournisseur
* **Fiche-Facture-Achat**
    * **Métier :** Classe `AchatModel`, fonction `getFacture(id)`. Calcul du `reste_a_payer`.
    * **Base :** Table `facture_achat`, `facture_achat_details`.
    * **Intégration :** Page `FicheFactureAchat.vue`.

### 2.3. Bon de reception (BR)
* **Saisie-BR**
    * **Métier :** Classe `AchatModel`, fonction `createBR(header, details)`. 
        * Calcul automatique du montant TTC.
        * Effectuer le mvtStock lié grace a un bouton.
    * **Base :** Table `bon_reception`, `bon_reception_details`.
    * **Intégration :** Page `SaisieBR.vue`.
* **Liste-BR**
    * **Métier :** Classe `AchatModel`, fonction `listeBR(id)`.
    * **Base :** Table `bon_reception`.
    * **Intégration :** Page `ListeBR.vue`.
* **Fiche-BR**
    * **Métier :** Classe `AchatModel`, fonction `getFacture(id)`. Calcul du `reste_a_payer`.
    * **Base :** Table `bon_reception`, `bon_reception_details`.
    * **Intégration :** Page `FicheBR.vue`.

---

## 💰 MODULE 3 : Flux des Ventes(Ando)
**Responsable : Développeur 3**

### 3.1. Devis et Commandes Client
* **Saisie-Devis** (NULLABLE)
    * **Métier :** Classe `VenteModel`, fonction `saveDevis(data)`.
    * **Base :** Tables `devis_vente`, `devis_vente_details`.
    * **Intégration :** Page `SaisieDevis.vue`.
* **Liste-Commande-Vente**
    * **Métier :** Classe `VenteModel`, fonction `getAllOrders()`. Filtres par statut (BROUILLON, VALIDE).
    * **Base :** Table `bon_commande_vente`.
    * **Intégration :** Page `ListeBCVente.vue`.
* **Fiche-Commande-Vente**
    * **Métier :** Classe `VenteModel`, fonction `getBCVenteDetails()`.
    * **Base :** Table `bon_commande_vente`, `bon_commande_vente_details`.
    * **Intégration :** Page `FicheBCVente.vue`.

### 3.2. Facturation Client
* **Saisie-Facture-Vente**
    * **Métier :** Classe `VenteModel`, fonction `generateFactureFromBC(bc_id)`.
        * Calcul automatique du montant TTC.
        * Effectuer le mvtStock lié grace a un bouton.
    * **Base :** Table `facture_vente`, `facture_vente_details`.
    * **Intégration :** Page `SaisieFactureVente.vue` (Récupération des données du BC).
* **Liste-Commande-Vente**
    * **Métier :** Classe `VenteModel`, fonction `getAllOrders()`. Filtres par statut (BROUILLON, VALIDE).
    * **Base :** Table `facture_vente`.
    * **Intégration :** Page `ListeFactureVente.vue`.
* **Fiche-Commande-Vente**
    * **Métier :** Classe `VenteModel`, fonction `getFCVenteDetails(fc_id)`.
    * **Base :** Table `facture_vente`, `facture_vente_details`.
    * **Intégration :** Page `FicheFactureVente.vue`.

---

## 📦 MODULE 4 : Stocks et Valorisation (CMUP, FIFO, LIFO)(Irintsoa/Ando)
**Responsable : Développeur 4**

### 4.1. Mouvements et Lots(Irintsoa)
* **Saisie-Mouvement**
    * **Métier :** Classe `StockManager`.
        * **Si Entrée :** Créer un `lot_stock` avec `prix_unitaire_achat`.
        * **Si Sortie :** Appliquer la méthode de la société (FIFO: `date_entree` ASC, LIFO: DESC).
    * **Base :** Tables `mouvement_stock`, `lot_stock`, `sortie_lot_detail`.
    * **Intégration :** Intégration backend lors de la validation des factures (Pas d'interface de saisie manuelle sauf inventaire).

### 4.2. État du Stock (Inventaire)(Ando)
* **Liste-Stock-Valorise**
    * **Métier :** Classe `StockModel`. Calcul du `cmup_actuel` et `valeur_stock_total` selon la méthode de valorisation de la société.
        * Afficher l'Etat de stock en general du groupe.
        * Filtrable par societe / magasin / depot.
    * **Base :** Table `stock`, `methode_valorisation_stock`.
    * **Intégration :** Page `EtatStock.vue`.
* **Etat-De-Stock**
    * **Métier :** Classe `StockModel`. Calcul du `cmup_actuel` et `valeur_stock_total` selon la méthode de valorisation de la société.
        * Filtrable par societe / magasin / depot.
    * **Base :** Table `stock`, `methode_valorisation_stock`.
    * **Intégration :** Page `EtatStock.vue`.

---

## 🏦 MODULE 5 : Caisse et Paiements(Maharavo)
**Responsable : Développeur 5**

### 5.1. Gestion de la Caisse
* **Liste-Mouvements-Caisse**
    * **Métier :** Classe `CaisseModel`, fonction `getJournal(caisse_id)`. Suivi `solde_avant` / `solde_apres`.
            * Filtrable par societe / magasin / caisse.
    * **Base :** Table `caisse_mouvement`.
    * **Intégration :** Page `JournalCaisse.vue`.

### 5.2. Paiements Complexes (Multi-modes)
* **Saisie-Paiement-Vente**
    * **Métier :** Classe `PaiementModel`, fonction `processPayment()`.
        * Créer l'entête `paiement_vente`.
        * Ventiler les montants par mode dans `paiement_vente_details` (Espèce, Chèque, etc.).
    * **Base :** Tables `paiement_vente`, `paiement_vente_details`, `mode_paiement`.
    * **Intégration :** Page `SaisiePaiement.vue` (Formulaire dynamique pour ajouter plusieurs modes).

---

## 📝 Directives Générales pour les Développeurs
1. **Interfaces :** Chaque développeur est responsable de la création de ses pages `.vue` (Saisie, Liste, Fiche) sauf mention contraire.
2. **Métier :** Les classes PHP doivent être situées dans le dossier `/models` et les contrôleurs dans `/controllers`.
3. **Sécurité :** Vérifier systématiquement `entreprise_id` pour assurer le cloisonnement des données entre les sociétés du groupe.


# 📊 Module Statistiques et Tableaux de Bord(Bijou/Ambinintsoa)(Au moins 2 stats par modules ci dessous)
**Responsable : Développeur 6 ou Lead Développeur**

## 📈 Statistiques et KPI Clés

### 6.1. Tableau de Bord Direction (Executive Dashboard)(Ambinintsoa)
* **Stat-Dashboard-Direction**
    * **Métier :** Classe `StatistiqueModel`, fonctions :
        * `getCA_Total(periode)` : Chiffre d'affaires global du groupe
        * `getMargeBrute_Total(periode)` : Marge brute totale
        * `getTop5_Clients(periode)` : Top 5 clients par CA
        * `getTop5_Articles(periode)` : Top 5 articles vendus (quantité et CA)
        * `getTaux_Rentabilite()` : Rentabilité par société/filiale
    * **Base :** Tables `facture_vente`, `facture_achat`, `entreprise`, `article`
    * **Intégration :** Page `DashboardDirection.vue` (Widgets avec graphiques)

### 6.2. Statistiques Achats(Ambinintsoa)
* **Stat-Achats-Fournisseurs**
    * **Métier :** Classe `StatAchatModel`, fonctions :
        * `getDepensesParFournisseur(mois)` : Répartition des dépenses par fournisseur
        * `getDelaiMoyenLivraison()` : Délai moyen entre BC et BR
        * `getTauxServiceFournisseur()` : Taux de service (BR/BC)
    * **Base :** Tables `bon_commande_achat`, `bon_reception`, `facture_achat`
    * **Intégration :** Page `StatsAchats.vue`

### 6.3. Statistiques Ventes(Ambinintsoa)
* **Stat-Ventes-Performances**
    * **Métier :** Classe `StatVenteModel`, fonctions :
        * `getCA_ParSociete(periode)` : CA par société du groupe
        * `getEvolutionCA(mois)` : Courbe d'évolution du CA
        * `getPanierMoyen()` : Panier moyen par client/société
        * `getTauxConversion()` : Taux conversion Devis → Facture
    * **Base :** Tables `devis_vente`, `facture_vente`, `entreprise`
    * **Intégration :** Page `StatsVentes.vue`

### 6.4. Statistiques Stocks(Bijou)
* **Stat-Stocks-Rotation**
    * **Métier :** Classe `StatStockModel`, fonctions :
        * `getTauxRotationStock()` : Rotation des stocks par article/catégorie
        * `getValeurStockImmobilise()` : Valeur du stock immobilisé
        * `getArticlesRupture()` : Articles proches de la rupture
        * `getDureeStockMoyenne()` : Durée moyenne de stockage
    * **Base :** Tables `mouvement_stock`, `lot_stock`, `article`
    * **Intégration :** Page `StatsStocks.vue`

### 6.5. Statistiques Financières(Bijou)
* **Stat-Tresorerie**
    * **Métier :** Classe `StatFinanceModel`, fonctions :
        * `getEncoursClients()` : Encours clients par société
        * `getEncoursFournisseurs()` : Encours fournisseurs
        * `getBFR()` : Besoin en fonds de roulement
        * `getTresorerieNet()` : Trésorerie nette par caisse
    * **Base :** Tables `facture_vente`, `facture_achat`, `paiement_vente`, `caisse_mouvement`
    * **Intégration :** Page `StatsFinances.vue`

### 6.6. Statistiques Commerciales(Bijou)
* **Stat-Commercial**
    * **Métier :** Classe `StatCommercialModel`, fonctions :
        * `getPerformanceCommercial()` : Performance par commercial/équipe
        * `getTauxFidelisation()` : Taux de fidélisation clients
        * `getNouveauxClients(periode)` : Acquisition nouveaux clients
    * **Base :** Tables `facture_vente`, `entreprise`, `personnel`
    * **Intégration :** Page `StatsCommercial.vue`


### 6.7. Statistiques Ressources Humaines(OPTIONNEL)
* **Stat-RH-Performances**
    * **Métier :** Classe `StatRHModel`, fonctions :
        * `getEffectifParSociete()` : Effectif total et répartition par société
        * `getTauxAbsenteeisme(periode)` : Taux d'absentéisme par département
        * `getTurnover()` : Taux de rotation du personnel
        * `getCoutMoyenParEmploye()` : Coût moyen (salaires + charges)
        * `getPerformanceEquipe()` : Performance par équipe/commercial
        * `getFormationsRealisees()` : Nombre et types de formations réalisées
    * **Base :** Tables `personnel`, `personnel_role`, `pointage`, `formation`
    * **Intégration :** Page `StatsRH.vue`

### 6.8. Statistiques Productivité(OPTIONNEL)
* **Stat-Productivite**
    * **Métier :** Classe `StatProductiviteModel`, fonctions :
        * `getProductiviteCommercial()` : CA généré par commercial
        * `getTauxRealisationObjectifs()` : Taux de réalisation des objectifs
        * `getTempsMoyenTraitement()` : Temps moyen de traitement des commandes
        * `getEfficaciteAchats()` : Économies réalisées par les achats
    * **Base :** Tables `personnel`, `facture_vente`, `bon_commande_achat`
    * **Intégration :** Page `StatsProductivite.vue`

## 📊 Types de Visualisations Requises
- **Graphiques en barres** : Comparaison CA par société/mois
- **Courbes** : Évolution temporelle
- **Camemberts** : Répartition marché/fournisseurs
- **Tableaux** : Top N avec indicateurs
- **Indicateurs KPI** : Cartes avec variation (vs période précédente)

## Integration avec POWERBI(Bijou)