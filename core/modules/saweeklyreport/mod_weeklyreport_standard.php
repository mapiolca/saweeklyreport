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
		$now = dol_now();
		$year = !empty($object->year) ? (int) $object->year : (int) date('o', $now);
		$week = !empty($object->week) ? (int) $object->week : (int) date('W', $now);

		return $this->prefix.'-'.$year.'-S'.sprintf('%02d', $week);
	}
}
