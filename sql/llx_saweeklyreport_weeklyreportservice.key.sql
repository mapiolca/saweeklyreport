-- Copyright (C) 2026  Pierre Ardoin <developpeur@lesmetiersdubatiment.fr>

ALTER TABLE llx_saweeklyreport_weeklyreportservice ADD INDEX idx_saweeklyreport_weeklyreportservice_entity (entity);
ALTER TABLE llx_saweeklyreport_weeklyreportservice ADD INDEX idx_saweeklyreport_weeklyreportservice_fk_report (fk_weeklyreport);
ALTER TABLE llx_saweeklyreport_weeklyreportservice ADD INDEX idx_saweeklyreport_weeklyreportservice_source (source_element, source_id);
ALTER TABLE llx_saweeklyreport_weeklyreportservice ADD INDEX idx_saweeklyreport_weeklyreportservice_position (position);
