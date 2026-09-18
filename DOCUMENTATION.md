# Documentation projet - ERP Achat, Vente et Production

## Vue d'ensemble

Ce projet est une application de gestion d'entreprise orientee achat, vente, stock, caisse et suivi d'activite. Il sert a centraliser les operations commerciales et logistiques d'une organisation qui gere plusieurs entreprises, sites, depots, fournisseurs, clients, articles et mouvements financiers.

L'objectif principal est de suivre tout le cycle de vie des marchandises et des documents commerciaux : depuis la demande d'achat jusqu'a la reception en stock, puis depuis le devis client jusqu'a la facturation, la sortie de stock et l'encaissement.

Le projet se presente comme un ERP interne : il ne se limite pas a enregistrer des factures, il relie les achats, les ventes, les stocks, les caisses, les paiements et les statistiques dans un meme environnement.

## Probleme traite

Dans une activite commerciale ou de production, les informations sont souvent dispersees : les achats sont suivis d'un cote, les ventes d'un autre, les stocks dans un fichier separe, et la caisse encore ailleurs. Cela rend difficile de savoir rapidement :

- quels articles sont disponibles ;
- quels fournisseurs ou clients sont concernes par chaque operation ;
- quelles factures restent a payer ;
- combien vaut le stock actuel ;
- quelle caisse a recu ou sorti de l'argent ;
- quels produits tournent le plus ;
- ou se trouve le stock entre les differents depots.

Ce projet repond a ce besoin en mettant tous ces elements dans un seul systeme.

## Utilisateurs concernes

L'application s'adresse principalement aux personnes qui participent a la gestion quotidienne d'une entreprise :

- les responsables achats, qui suivent les demandes, les proformas fournisseurs, les bons de commande et les factures d'achat ;
- les commerciaux, qui creent les devis, les commandes clients et les factures de vente ;
- les gestionnaires de stock, qui controlent les entrees, sorties, depots, articles et niveaux de stock ;
- les responsables financiers, qui suivent les paiements, les caisses, les dettes fournisseurs et les creances clients ;
- les administrateurs, qui gerent les entreprises, sites, depots, personnels, modes de paiement et parametres de base ;
- la direction, qui consulte les tableaux de bord et les indicateurs de performance.

## Organisation geree par le projet

Le projet prend en compte une organisation structuree autour de plusieurs niveaux :

- un groupe peut contenir plusieurs entreprises ;
- une entreprise peut representer une filiale, un fournisseur ou un client ;
- chaque entreprise peut avoir des sites ;
- chaque site peut avoir des depots ;
- les articles sont suivis par depot ;
- les caisses sont rattachees aux entreprises ;
- les personnels sont rattaches a des roles, entreprises et sites.

Cette organisation permet de suivre l'activite de maniere detaillee, notamment lorsque le stock ou les operations sont repartis sur plusieurs lieux.

## Modules principaux

### Tableau de bord

Le tableau de bord donne une vision rapide de la situation generale. Il regroupe des indicateurs sur le stock, la finance et l'activite commerciale.

Il permet notamment de visualiser :

- la rotation des articles ;
- la valeur du stock immobilise ;
- les articles en rupture ou proches de la rupture ;
- les encours clients ;
- les dettes fournisseurs ;
- la tresorerie disponible ;
- les performances commerciales ;
- les nouveaux clients et la fidelisation.

Ce module sert de point d'entree pour comprendre l'etat global de l'entreprise.

### Gestion des articles et du stock

Le projet permet de gerer un catalogue d'articles avec leurs references, designations, categories, unites, prix d'achat et prix de vente.

Le stock est suivi par article et par depot. Chaque mouvement de stock conserve l'etat avant et apres operation, ce qui permet de garder une trace claire des entrees et sorties.

Le systeme gere aussi la valorisation du stock selon plusieurs methodes :

- CMUP : cout moyen unitaire pondere ;
- FIFO : premier entre, premier sorti ;
- LIFO : dernier entre, premier sorti.

Cela donne une vision a la fois quantitative et comptable du stock.

### Processus d'achat

Le processus d'achat couvre plusieurs etapes :

1. Creation d'une demande d'achat interne.
2. Generation ou enregistrement d'une proforma fournisseur.
3. Transformation de la proforma en bon de commande achat.
4. Transformation du bon de commande en facture d'achat.
5. Reception des articles dans le depot concerne.
6. Paiement fournisseur via une caisse.

Ce circuit permet de relier les besoins internes, les fournisseurs, les documents commerciaux, les entrees en stock et les sorties de tresorerie.

### Processus de vente

Le processus de vente couvre le cycle client :

1. Creation d'un devis de vente.
2. Transformation du devis en bon de commande client.
3. Creation d'une facture de vente.
4. Sortie de stock depuis le depot d'expedition.
5. Encaissement du paiement client.

L'application permet ainsi de suivre la relation client depuis la proposition commerciale jusqu'au paiement final.

### Gestion de la caisse et des paiements

Le projet inclut un suivi des caisses et des mouvements financiers.

Les caisses permettent d'enregistrer :

- les entrees d'argent, notamment les paiements clients ;
- les sorties d'argent, notamment les reglements fournisseurs ;
- les mouvements manuels de caisse ;
- les soldes par caisse ;
- le journal de caisse.

Les paiements sont associes aux factures d'achat ou de vente. Cela permet de connaitre les factures reglees, partiellement reglees ou encore ouvertes.

### Gestion des entreprises, sites et depots

Le systeme permet de gerer les differents acteurs et lieux de l'organisation :

- entreprises clientes ;
- entreprises fournisseurs ;
- entreprises filiales ;
- sites ;
- depots ;
- personnel ;
- roles ;
- modes de paiement.

Ce module sert de base administrative pour alimenter les operations d'achat, vente, stock et finance.

### Statistiques et analyse

Le projet propose des vues statistiques pour mieux analyser l'activite.

Pour les achats, il permet d'observer :

- les depenses par fournisseur ;
- les delais moyens de livraison ;
- le taux de service fournisseur.

Pour les ventes, il permet d'observer :

- le chiffre d'affaires ;
- l'evolution des ventes ;
- le panier moyen ;
- le taux de conversion ;
- les performances par societe ou commercial.

Ces donnees aident a prendre des decisions sur les fournisseurs, les stocks, les ventes et la tresorerie.

## Flux metier global

Le fonctionnement general peut se resumer ainsi :

Une entreprise identifie un besoin d'achat. Elle cree une demande d'achat, compare ou enregistre une proforma fournisseur, puis valide un bon de commande. Quand la marchandise arrive, une facture d'achat est creee et le stock augmente dans le depot concerne. Ensuite, le paiement fournisseur diminue la caisse choisie.

De l'autre cote, un client demande un prix ou une commande. L'entreprise cree un devis, le transforme en commande, puis en facture. Lors de la validation, les articles sortent du stock. Quand le client paie, la caisse est alimentee.

Entre ces deux flux, le systeme maintient une vision continue du stock, des paiements, des dettes, des creances et de la performance.

## Valeur apportee

Le projet apporte une meilleure maitrise de l'activite grace a :

- une centralisation des donnees commerciales, logistiques et financieres ;
- une tracabilite des documents d'achat et de vente ;
- un suivi clair des stocks par depot ;
- une valorisation comptable du stock ;
- une vision des encaissements et decaissements ;
- une identification des factures non payees ;
- des tableaux de bord pour piloter l'activite ;
- une organisation adaptee aux entreprises multi-sites et multi-depots.

## Perimetre fonctionnel

Le projet couvre actuellement :

- la gestion des articles ;
- la gestion du stock ;
- les mouvements de stock ;
- les demandes d'achat ;
- les proformas fournisseurs ;
- les bons de commande achat ;
- les factures achat ;
- les devis vente ;
- les bons de commande vente ;
- les factures vente ;
- les paiements clients ;
- les paiements fournisseurs ;
- les caisses ;
- les mouvements de caisse ;
- les entreprises, sites et depots ;
- le personnel ;
- les modes de paiement ;
- les statistiques achat, vente, stock, finance et commercial.

## Vision du projet

La vision du projet est de fournir un outil unique pour piloter les operations essentielles d'une entreprise commerciale ou de production. Il doit permettre aux utilisateurs de comprendre rapidement ce qui entre, ce qui sort, ce qui reste en stock, ce qui est du, ce qui est encaisse et ce qui doit etre surveille.

En pratique, l'application joue le role d'un centre de controle de l'activite : elle transforme des operations quotidiennes, parfois dispersees, en informations structurees, suivies et exploitables.

