# Changelog

## 0.1.0 - 2026-06-08

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
