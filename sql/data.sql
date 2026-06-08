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
VALUES ('SAWEEKLYREPORT_WEEKLYREPORT_UPDATE', 'Weekly report updated', 'Executed when a weekly report is updated', 'weeklyreport@saweeklyreport', 450006);

INSERT INTO llx_c_action_trigger (code, label, description, elementtype, rang)
VALUES ('SAWEEKLYREPORT_WEEKLYREPORT_DELETE', 'Weekly report deleted', 'Executed when a weekly report is deleted', 'weeklyreport@saweeklyreport', 450007);

INSERT INTO llx_c_action_trigger (code, label, description, elementtype, rang)
VALUES ('SAWEEKLYREPORT_WEEKLYREPORT_VALIDATE', 'Weekly report validated', 'Executed when a weekly report is validated', 'weeklyreport@saweeklyreport', 450008);

INSERT INTO llx_c_action_trigger (code, label, description, elementtype, rang)
VALUES ('SAWEEKLYREPORT_WEEKLYREPORT_UNVALIDATE', 'Weekly report back to draft', 'Executed when a weekly report is set back to draft', 'weeklyreport@saweeklyreport', 450009);

INSERT INTO llx_c_action_trigger (code, label, description, elementtype, rang)
VALUES ('SAWEEKLYREPORT_WEEKLYREPORT_CANCEL', 'Weekly report canceled', 'Executed when a weekly report is canceled', 'weeklyreport@saweeklyreport', 450010);

INSERT INTO llx_c_action_trigger (code, label, description, elementtype, rang)
VALUES ('SAWEEKLYREPORT_WEEKLYREPORT_GENERATE_DOCUMENT', 'Weekly report PPTX generated', 'Executed when a weekly report PPTX document is generated', 'weeklyreport@saweeklyreport', 450011);
