# saweeklyreport

Module Dolibarr externe pour générer des rapports hebdomadaires Soleil Aquitain.

## Fonctionnalités

- Objet métier `WeeklyReport` avec brouillon, validation, annulation, notes, documents et onglet Agenda.
- Calcul automatique des kWc depuis les commandes clients clôturées et l’extrafield `commande_extrafields.powerplantpv_peak_power` fourni par PowerPlantPV.
- Préremplissage éditable des lignes SAV et maintenance depuis les interventions et tickets Dolibarr actifs.
- Génération PPTX éditable à partir du modèle `doctemplates/saweeklyreport/weekly_report_standard.pptx`.
- Numérotation configurable avec le masque `SAWEEKLYREPORT_WEEKLYREPORT_MASK` (`SAWR-{YYYY}-S{WW}` par défaut).
- Compatibilité Multicompany avec `entity`, `getEntity('weeklyreport')`, partage d’objet et partage de numérotation.
- API REST pour créer, lire, modifier, supprimer, actualiser et générer les rapports.

## Compatibilité

- Dolibarr v20+.
- PHP 8.0+.
- MySQL/MariaDB via l’abstraction Dolibarr.
- PowerPlantPV et Commandes clients sont recommandés pour les KPI ; Interventions, Tickets et Agenda sont utilisés lorsqu’ils sont actifs.

## Installation

Installer le dépôt dans `htdocs/custom/saweeklyreport`, puis activer le module depuis la liste des modules Dolibarr.

Le seul point d’entrée de configuration déclaré est `admin/setup.php@saweeklyreport`. Les onglets internes donnent accès aux réglages, à la compatibilité et à l’onglet À propos.
