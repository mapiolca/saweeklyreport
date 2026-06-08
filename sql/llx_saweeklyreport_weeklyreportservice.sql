-- Copyright (C) 2026  Pierre Ardoin <developpeur@lesmetiersdubatiment.fr>
--
-- Editable service/SAV lines copied from native Dolibarr objects.

CREATE TABLE llx_saweeklyreport_weeklyreportservice(
	rowid integer AUTO_INCREMENT PRIMARY KEY NOT NULL,
	entity integer DEFAULT 1 NOT NULL,
	fk_weeklyreport integer NOT NULL,
	source_element varchar(64),
	source_id integer,
	service_type varchar(64),
	ticket_category_code varchar(32),
	ticket_severity_code varchar(32),
	label varchar(255) NOT NULL,
	description text,
	status integer DEFAULT 0,
	position integer DEFAULT 0,
	date_service date,
	date_creation datetime NOT NULL,
	tms timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
	fk_user_creat integer NOT NULL,
	fk_user_modif integer
) ENGINE=innodb DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
