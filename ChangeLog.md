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
- Amélioration du sélecteur de tickets, du bandeau des onglets transverses et du retour à la ligne dans le bloc PowerPoint "Pose semaine dernière".
- Alignement de la pagination du pied de page PDF TCPDF sur le rendu natif Dolibarr.
- Unification du modèle documentaire par défaut avec `SAWEEKLYREPORT_WEEKLYREPORT_ADDON_DOC` afin d’éviter deux modèles par défaut simultanés.
- Suppression de l’ajout manuel de lignes SAV libres sur la fiche, au profit de la sélection de tickets existants via Select2.
- Ajustement du modèle PPTX standard : titre “Retours terrain” et alignement des bordures droites des blocs bas.

## 1.0.0 - 2026-06-08
- Création du module `saweeklyreport`.
