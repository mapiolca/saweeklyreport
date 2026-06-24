-- Copyright (C) 2026  Pierre Ardoin <developpeur@lesmetiersdubatiment.fr>
--
-- Native trigger dictionary entries for Agenda, Notifications and Webhooks.

DELETE FROM llx_c_action_trigger
WHERE code IN (
	'SAWEEKLYREPORT_WEEKLYREPORT_CREATE',
	'SAWEEKLYREPORT_WEEKLYREPORT_UPDATE',
	'SAWEEKLYREPORT_WEEKLYREPORT_DELETE',
	'SAWEEKLYREPORT_WEEKLYREPORT_VALIDATE',
	'SAWEEKLYREPORT_WEEKLYREPORT_UNVALIDATE',
	'SAWEEKLYREPORT_WEEKLYREPORT_CANCEL',
	'SAWEEKLYREPORT_WEEKLYREPORT_GENERATE_DOCUMENT'
);

INSERT INTO llx_c_action_trigger (code, label, description, elementtype, rang)
VALUES ('SAWEEKLYREPORT_WEEKLYREPORT_CREATE', 'Weekly report created', 'Executed when a weekly report is created', 'weeklyreport@saweeklyreport', 450005);

INSERT INTO llx_c_action_trigger (code, label, description, elementtype, rang)
VALUES ('SAWEEKLYREPORT_WEEKLYREPORT_UPDATE', 'Weekly report updated', 'Executed when a weekly report is updated; status changes and document generations are described by trigger context', 'weeklyreport@saweeklyreport', 450006);

INSERT INTO llx_c_action_trigger (code, label, description, elementtype, rang)
VALUES ('SAWEEKLYREPORT_WEEKLYREPORT_DELETE', 'Weekly report deleted', 'Executed when a weekly report is deleted', 'weeklyreport@saweeklyreport', 450007);

INSERT IGNORE INTO llx_document_model (nom, type, entity, libelle, description)
VALUES ('weekly_report_standard', 'weeklyreport', 0, 'Weekly report PowerPoint', NULL);

INSERT IGNORE INTO llx_document_model (nom, type, entity, libelle, description)
VALUES ('pdf_weeklyreport_powerpoint', 'weeklyreport', 0, 'Weekly report PDF TCPDF', NULL);

UPDATE llx_document_model
SET description = NULL
WHERE type = 'weeklyreport'
AND nom IN ('weekly_report_standard', 'pdf_weeklyreport_powerpoint')
AND description IN ('Editable PPTX weekly report template', 'TCPDF weekly report generated from the same data as the PowerPoint document');
