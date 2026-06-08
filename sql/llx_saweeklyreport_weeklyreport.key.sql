-- Copyright (C) 2026  Pierre Ardoin <developpeur@lesmetiersdubatiment.fr>

ALTER TABLE llx_saweeklyreport_weeklyreport ADD INDEX idx_saweeklyreport_weeklyreport_entity (entity);
ALTER TABLE llx_saweeklyreport_weeklyreport ADD UNIQUE INDEX uk_saweeklyreport_weeklyreport_ref_entity (ref, entity);
ALTER TABLE llx_saweeklyreport_weeklyreport ADD UNIQUE INDEX uk_saweeklyreport_weeklyreport_year_week_entity (year, week, entity);
ALTER TABLE llx_saweeklyreport_weeklyreport ADD INDEX idx_saweeklyreport_weeklyreport_period_start (period_start);
ALTER TABLE llx_saweeklyreport_weeklyreport ADD INDEX idx_saweeklyreport_weeklyreport_period_end (period_end);
ALTER TABLE llx_saweeklyreport_weeklyreport ADD INDEX idx_saweeklyreport_weeklyreport_status (status);
