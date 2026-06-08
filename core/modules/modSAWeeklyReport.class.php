<?php
/* Copyright (C) 2026  Pierre Ardoin <developpeur@lesmetiersdubatiment.fr>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 */

include_once DOL_DOCUMENT_ROOT.'/core/modules/DolibarrModules.class.php';

/**
 * Module descriptor for SAWeeklyReport.
 */
class modSAWeeklyReport extends DolibarrModules
{
	/**
	 * Constructor.
	 *
	 * @param	DoliDB	$db	Database handler
	 */
	public function __construct($db)
	{
		global $conf;

		$this->db = $db;
		$this->numero = 999001;
		$this->rights_class = 'saweeklyreport';
		$this->family = 'JPSUN';
		$this->module_position = '91';
		$this->name = preg_replace('/^mod/i', '', get_class($this));
		$this->description = 'ModuleSAWeeklyReportDesc';
		$this->descriptionlong = 'ModuleSAWeeklyReportDesc';
		$this->editor_name = 'Les Métiers du Bâtiment';
		$this->editor_url = 'lesmetiersdubatiment.fr';
		$this->editor_squarred_logo = '';
		$this->version = '0.1.0';
		$this->const_name = 'MAIN_MODULE_'.strtoupper($this->name);
		$this->picto = 'fa-chart-line';

		$this->module_parts = array(
			'triggers' => 1,
			'login' => 0,
			'substitutions' => 1,
			'menus' => 0,
			'tpl' => 0,
			'barcode' => 0,
			'models' => 1,
			'printing' => 0,
			'theme' => 0,
			'css' => array(),
			'js' => array(),
			'hooks' => array(
				'data' => array(
					'weeklyreportcard',
					'weeklyreportlist',
					'globalcard',
					'multicompanyexternalmodulesharing',
					'multicompanyexternalmodules',
					'multicompanysharingoptions',
				),
				'entity' => '0',
			),
			'moduleforexternal' => 0,
			'websitetemplates' => 0,
			'captcha' => 0,
		);

		$this->dirs = array('/saweeklyreport/temp');
		$this->config_page_url = array('setup.php@saweeklyreport');
		$this->hidden = getDolGlobalInt('MODULE_SAWEEKLYREPORT_DISABLED');
		$this->depends = array();
		$this->requiredby = array();
		$this->conflictwith = array();
		$this->langfiles = array('saweeklyreport@saweeklyreport');
		$this->phpmin = array(8, 0);
		$this->need_dolibarr_version = array(20, 0);
		$this->need_javascript_ajax = 0;
		$this->warnings_activation = array();
		$this->warnings_activation_ext = array();

		$this->const = array(
			array('SAWEEKLYREPORT_WEEKLYREPORT_ADDON', 'chaine', 'mod_weeklyreport_standard', 'Weekly report numbering module', 0, 'current', 1),
			array('SAWEEKLYREPORT_WEEKLYREPORT_MASK', 'chaine', 'SAWR-{YYYY}-S{WW}', 'Weekly report numbering mask', 0, 'current', 1),
			array('SAWEEKLYREPORT_WEEKLYREPORT_ADDON_PPTX', 'chaine', 'weekly_report_standard', 'Weekly report PPTX model', 0, 'current', 1),
			array('SAWEEKLYREPORT_ANNUAL_TARGET_POWER', 'chaine', '846', 'Annual kWc target', 0, 'current', 1),
			array('SAWEEKLYREPORT_WEEKLY_TARGET_POWER', 'chaine', '18', 'Weekly kWc target', 0, 'current', 1),
			array('SAWEEKLYREPORT_MEETING_DURATION', 'chaine', '15', 'Meeting duration in minutes', 0, 'current', 1),
			array('SAWEEKLYREPORT_PREFILL_FICHINTER', 'chaine', '1', 'Prefill interventions', 0, 'current', 1),
			array('SAWEEKLYREPORT_PREFILL_TICKET', 'chaine', '1', 'Prefill tickets', 0, 'current', 1),
			array('SAWEEKLYREPORT_DEFAULT_SAFETY_MESSAGE', 'chaine', 'Rappel : port du harnais obligatoire en toiture. Pas de précipitation en fin de chantier.', 'Default safety message', 0, 'current', 1),
			array('SAWEEKLYREPORT_DEFAULT_LOADING_REMINDER', 'chaine', 'Pour rappel : réaliser le chargement des véhicules la veille du chantier.', 'Default loading reminder', 0, 'current', 1),
			array('MAIN_AGENDA_ACTIONAUTO_SAWEEKLYREPORT_WEEKLYREPORT_CREATE', 'chaine', '1', 'Agenda event for weekly report creation', 0, 'current', 1),
			array('MAIN_AGENDA_ACTIONAUTO_SAWEEKLYREPORT_WEEKLYREPORT_VALIDATE', 'chaine', '1', 'Agenda event for weekly report validation', 0, 'current', 1),
			array('MAIN_AGENDA_ACTIONAUTO_SAWEEKLYREPORT_WEEKLYREPORT_GENERATE_DOCUMENT', 'chaine', '1', 'Agenda event for weekly report PPTX generation', 0, 'current', 1),
		);

		if (!isModEnabled('saweeklyreport')) {
			$conf->saweeklyreport = new stdClass();
			$conf->saweeklyreport->enabled = 0;
		}

		$this->tabs = array();
		$this->dictionaries = array();
		$this->boxes = array();
		$this->cronjobs = array();

		$this->rights = array();
		$r = 0;
		$this->rights[$r][0] = $this->numero.'01';
		$this->rights[$r][1] = 'SAWeeklyReportPermissionRead';
		$this->rights[$r][4] = 'weeklyreport';
		$this->rights[$r][5] = 'read';
		$r++;
		$this->rights[$r][0] = $this->numero.'02';
		$this->rights[$r][1] = 'SAWeeklyReportPermissionWrite';
		$this->rights[$r][4] = 'weeklyreport';
		$this->rights[$r][5] = 'write';
		$r++;
		$this->rights[$r][0] = $this->numero.'03';
		$this->rights[$r][1] = 'SAWeeklyReportPermissionDelete';
		$this->rights[$r][4] = 'weeklyreport';
		$this->rights[$r][5] = 'delete';
		$r++;
		$this->rights[$r][0] = $this->numero.'04';
		$this->rights[$r][1] = 'SAWeeklyReportPermissionValidate';
		$this->rights[$r][4] = 'weeklyreport';
		$this->rights[$r][5] = 'validate';
		$r++;
		$this->rights[$r][0] = $this->numero.'05';
		$this->rights[$r][1] = 'SAWeeklyReportPermissionExport';
		$this->rights[$r][4] = 'weeklyreport';
		$this->rights[$r][5] = 'export';
		$r++;
		$this->rights[$r][0] = $this->numero.'06';
		$this->rights[$r][1] = 'SAWeeklyReportPermissionApi';
		$this->rights[$r][4] = 'weeklyreport';
		$this->rights[$r][5] = 'api';
		$r++;

		$this->menu = array();
		$r = 0;
		$this->menu[$r++] = array(
			'fk_menu' => 'fk_mainmenu=home',
			'type' => 'left',
			'titre' => 'ModuleSAWeeklyReportName',
			'prefix' => img_picto('', $this->picto, 'class="pictofixedwidth valignmiddle paddingright"'),
			'mainmenu' => 'home',
			'leftmenu' => 'saweeklyreport',
			'url' => '/saweeklyreport/saweeklyreportindex.php',
			'langs' => 'saweeklyreport@saweeklyreport',
			'position' => 1000 + $r,
			'enabled' => "isModEnabled('saweeklyreport')",
			'perms' => '$user->hasRight("saweeklyreport", "weeklyreport", "read")',
			'target' => '',
			'user' => 2,
			'object' => 'WeeklyReport',
		);
		$this->menu[$r++] = array(
			'fk_menu' => 'fk_mainmenu=home,fk_leftmenu=saweeklyreport',
			'type' => 'left',
			'titre' => 'WeeklyReportList',
			'mainmenu' => 'home',
			'leftmenu' => 'weeklyreport_list',
			'url' => '/saweeklyreport/weeklyreport_list.php',
			'langs' => 'saweeklyreport@saweeklyreport',
			'position' => 1000 + $r,
			'enabled' => "isModEnabled('saweeklyreport')",
			'perms' => '$user->hasRight("saweeklyreport", "weeklyreport", "read")',
			'target' => '',
			'user' => 2,
			'object' => 'WeeklyReport',
		);
		$this->menu[$r++] = array(
			'fk_menu' => 'fk_mainmenu=home,fk_leftmenu=saweeklyreport',
			'type' => 'left',
			'titre' => 'NewWeeklyReport',
			'mainmenu' => 'home',
			'leftmenu' => 'weeklyreport_new',
			'url' => '/saweeklyreport/weeklyreport_card.php?action=create',
			'langs' => 'saweeklyreport@saweeklyreport',
			'position' => 1000 + $r,
			'enabled' => "isModEnabled('saweeklyreport')",
			'perms' => '$user->hasRight("saweeklyreport", "weeklyreport", "write")',
			'target' => '',
			'user' => 2,
			'object' => 'WeeklyReport',
		);

		$r = 0;
		$this->export_code[$r] = $this->rights_class.'_'.$r;
		$this->export_label[$r] = 'WeeklyReports';
		$this->export_icon[$r] = $this->picto;
		$this->export_fields_array[$r] = array(
			't.ref' => 'Ref',
			't.label' => 'Label',
			't.year' => 'Year',
			't.week' => 'Week',
			't.period_start' => 'WeeklyReportPeriodStart',
			't.period_end' => 'WeeklyReportPeriodEnd',
			't.week_installed_power' => 'WeeklyReportWeekInstalledPower',
			't.month_installed_power' => 'WeeklyReportMonthInstalledPower',
			't.annual_installed_power' => 'WeeklyReportAnnualInstalledPower',
			't.annual_target_power' => 'WeeklyReportAnnualTargetPower',
			't.status' => 'Status',
		);
		$this->export_TypeFields_array[$r] = array(
			't.ref' => 'Text',
			't.label' => 'Text',
			't.year' => 'Numeric',
			't.week' => 'Numeric',
			't.period_start' => 'Date',
			't.period_end' => 'Date',
			't.week_installed_power' => 'Numeric',
			't.month_installed_power' => 'Numeric',
			't.annual_installed_power' => 'Numeric',
			't.annual_target_power' => 'Numeric',
			't.status' => 'Numeric',
		);
		$this->export_entities_array[$r] = array(
			't.ref' => 'weeklyreport',
			't.label' => 'weeklyreport',
			't.year' => 'weeklyreport',
			't.week' => 'weeklyreport',
			't.period_start' => 'weeklyreport',
			't.period_end' => 'weeklyreport',
			't.week_installed_power' => 'weeklyreport',
			't.month_installed_power' => 'weeklyreport',
			't.annual_installed_power' => 'weeklyreport',
			't.annual_target_power' => 'weeklyreport',
			't.status' => 'weeklyreport',
		);
		$this->export_sql_start[$r] = 'SELECT DISTINCT ';
		$this->export_sql_end[$r] = ' FROM '.$this->db->prefix().'saweeklyreport_weeklyreport as t';
		$this->export_sql_end[$r] .= ' WHERE t.entity IN ('.getEntity('weeklyreport').')';
	}

	/**
	 * Enable module.
	 *
	 * @param	string	$options	Options
	 * @return	int
	 */
	public function init($options = '')
	{
		$result = $this->_load_tables('/saweeklyreport/sql/');
		if ($result < 0) {
			return -1;
		}

		$this->remove($options);
		if ($this->syncMulticompanySharingDefinition(true) < 0) {
			return -1;
		}

		return $this->_init(array(), $options);
	}

	/**
	 * Disable module.
	 *
	 * @param	string	$options	Options
	 * @return	int
	 */
	public function remove($options = '')
	{
		$this->syncMulticompanySharingDefinition(false);

		return $this->_remove(array(), $options);
	}

	/**
	 * Persist or remove Multicompany sharing payload.
	 *
	 * @param	bool	$enable	Enable
	 * @return	int
	 */
	private function syncMulticompanySharingDefinition($enable)
	{
		global $conf;

		dol_include_once('/saweeklyreport/class/actions_saweeklyreport.class.php');
		if (!class_exists('ActionsSAWeeklyReport')) {
			return 0;
		}

		$current = getDolGlobalString('MULTICOMPANY_EXTERNAL_MODULES_SHARING');
		$sharing = array();
		if ($current !== '') {
			$decoded = json_decode($current, true);
			if (is_array($decoded)) {
				$sharing = $decoded;
			}
		}

		$definition = ActionsSAWeeklyReport::getMulticompanySharingDefinition();
		if ($enable) {
			$sharing = array_replace_recursive($sharing, $definition);
		} else {
			unset($sharing[ActionsSAWeeklyReport::MULTICOMPANY_SHARING_ROOT_KEY]);
		}

		$json = json_encode($sharing);
		$result = dolibarr_set_const(
			$this->db,
			'MULTICOMPANY_EXTERNAL_MODULES_SHARING',
			$json,
			'chaine',
			0,
			'',
			(int) $conf->entity
		);

		return ($result < 0 ? -1 : 1);
	}
}
