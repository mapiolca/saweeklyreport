# saweeklyreport

Module Dolibarr externe pour générer des rapports hebdomadaires Soleil Aquitain.

Version courante : **1.1**.

## Fonctionnalités

- Objet métier `WeeklyReport` avec brouillon, validation, annulation, notes, documents et onglet Agenda.
- Calcul automatique des kWc depuis les commandes clients clôturées et l’extrafield `commande_extrafields.powerplantpv_peak_power` fourni par PowerPlantPV.
- Préremplissage des lignes SAV et maintenance depuis les interventions et tickets Dolibarr actifs, avec tickets liés en lecture seule et dissociables de la fiche.
- Génération PPTX éditable à partir du modèle `doctemplates/saweeklyreport/weekly_report_standard.pptx`.
- Génération PDF native TCPDF avec le modèle `pdf_weeklyreport_powerpoint`, sans conversion LibreOffice.
- Gestion native des modèles de document Dolibarr pour sélectionner, activer et désactiver les modèles PDF et PPTX.
- Textes éditables avec DolEditor lorsque le module WYSIWYG Dolibarr est activé, avec prise en charge HTML UTF-8/utf8mb4 et émojis selon la police disponible.
- Numérotation native avec modèles `standard` et `advanced`, incluant un masque personnalisable via `SAWEEKLYREPORT_WEEKLYREPORT_ADVANCED_MASK`.
- Compatibilité Multicompany avec `entity`, `getEntity('weeklyreport')`, partage d’objet et partage de numérotation.
- Onglets natifs Notes, Fichiers joints et Événements/Agenda avec libellé sous référence et pagination de l’agenda.
- API REST pour créer, lire, modifier, supprimer, actualiser et générer les rapports.

## Compatibilité

- Dolibarr v20+.
- PHP 8.0+.
- MySQL/MariaDB via l’abstraction Dolibarr.
- PowerPlantPV et Commandes clients sont recommandés pour les KPI ; Interventions, Tickets et Agenda sont utilisés lorsqu’ils sont actifs.

## Installation

Installer le dépôt dans `htdocs/custom/saweeklyreport`, puis activer le module depuis la liste des modules Dolibarr.

Le seul point d’entrée de configuration déclaré est `admin/setup.php@saweeklyreport`. Les onglets internes donnent accès aux réglages, à la compatibilité et à l’onglet À propos.
