# Changelog

## 1.1 - 2026-06-24

- Passage du descripteur du module en version `1.1`.
- Intégration du modèle PPTX `weekly_report_standard` dans le gestionnaire natif des modèles de document, aux côtés du modèle PDF TCPDF.
- Ajout du modèle de numérotation `advanced` avec masque personnalisable `SAWEEKLYREPORT_WEEKLYREPORT_ADVANCED_MASK` et simplification du modèle `standard`.
- Réorganisation de la page de réglages avec les sections de numérotation et de modèles de document en haut de page.
- Amélioration de la fiche rapport : édition champ par champ, placement natif des icônes d’édition, champs calculés en lecture seule et section Communications et objectifs.
- Amélioration du PDF TCPDF : meilleure prise en charge best-effort des émojis, traductions du pied de page et réserve de bas de page renforcée.
- Amélioration de l’onglet Événements/Agenda : pagination native, sélecteur du nombre de lignes, affichage du libellé sous la référence et utilisateurs de création/modification corrigés.
- Normalisation des modèles statiques `document_model` afin qu’ils ne soient pas interprétés comme des modèles à répertoire de scan ODT.

## 0.1.0 - 2026-06-08

- Correction du modèle PDF TCPDF : gestion best-effort des émojis, chargement des traductions du pied de page et pagination Dolibarr standard.
- Ajout des blocs natifs de réglage pour la numérotation et les modèles de document PDF des rapports hebdomadaires.
- Correction des droits documentaires avec accès administrateur, hook `checkSecureAccess` et chemins `weeklyreport/<ref>` compatibles avec l'ancien format `entity/weeklyreport/<ref>`.
- Ajout du modèle de document PDF TCPDF `pdf_weeklyreport_powerpoint` basé sur les mêmes données que le PowerPoint.
- Ajout de la puissance crête posée dans le bloc PowerPoint/PDF "Pose semaine dernière".
- Ajout de l'édition champ par champ, du support DolEditor/WYSIWYG et du stockage HTML UTF-8/utf8mb4 pour les textes.
- Passage des transitions validation/remise en brouillon/génération documentaire sur le trigger CRUD `SAWEEKLYREPORT_WEEKLYREPORT_UPDATE` avec contexte.
- Refonte de la section SAV et maintenance pour lier/dissocier des tickets existants sans modifier les données natives du ticket.

- Correction des liens d'affichage sans token pour respecter la protection CSRF Dolibarr.

- Ajout du filtrage des tickets par type de demande, des snapshots groupe/sévérité et des liens natifs vers tickets/interventions.

- Création du module `saweeklyreport`.
- Ajout de l’objet `WeeklyReport`, des lignes SAV, des pages fiche/liste/documents/notes/agenda et des réglages admin.
- Ajout des calculs PowerPlantPV depuis les commandes clôturées.
- Ajout de la génération PPTX éditable par remplacement OpenXML.
- Ajout des droits, triggers, API REST, substitutions et intégration Multicompany.
