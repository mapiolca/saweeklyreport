<?php
/* Copyright (C) 2026  Pierre Ardoin <developpeur@lesmetiersdubatiment.fr> */

dol_include_once('/saweeklyreport/core/modules/saweeklyreport/modules_weeklyreport.php');

/**
 * Standard weekly report numbering.
 */
class mod_weeklyreport_standard extends ModeleNumRefWeeklyReport
{
	public $version = 'dolibarr';
	public $prefix = 'SAWR';
	public $error = '';
	public $name = 'standard';

	/**
	 * Return description.
	 *
	 * @param	Translate	$langs	Language
	 * @return	string
	 */
	public function info($langs)
	{
		return $langs->trans('SAWeeklyReportNumRefStandardDesc', $this->prefix);
	}

	/**
	 * Return example.
	 *
	 * @return	string
	 */
	public function getExample()
	{
		return $this->prefix.'-2026-S23';
	}

	/**
	 * Check activation.
	 *
	 * @param	CommonObject	$object	Object
	 * @return	bool
	 */
	public function canBeActivated($object)
	{
		return true;
	}

	/**
	 * Return next reference.
	 *
	 * @param	WeeklyReport	$object	Report
	 * @return	string|int
	 */
	public function getNextValue($object)
	{
		global $conf;

		$now = dol_now();
		$year = !empty($object->year) ? (int) $object->year : (int) date('o', $now);
		$week = !empty($object->week) ? (int) $object->week : (int) date('W', $now);
		$mask = getDolGlobalString('SAWEEKLYREPORT_WEEKLYREPORT_MASK', $this->prefix.'-{YYYY}-S{WW}');
		$ref = strtr($mask, array(
			'{YYYY}' => sprintf('%04d', $year),
			'{YY}' => substr(sprintf('%04d', $year), -2),
			'{WW}' => sprintf('%02d', $week),
			'{W}' => (string) $week,
			'{ENTITY}' => (string) (int) $conf->entity,
		));
		$ref = preg_replace('/[^A-Za-z0-9_\-.]/', '', (string) $ref);

		return ($ref !== '' ? $ref : $this->prefix.'-'.$year.'-S'.sprintf('%02d', $week));
	}
}
