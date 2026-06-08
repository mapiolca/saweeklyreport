<?php
/* Copyright (C) 2026  Pierre Ardoin <developpeur@lesmetiersdubatiment.fr> */

require_once DOL_DOCUMENT_ROOT.'/core/triggers/dolibarrtriggers.class.php';

/**
 * Triggers for SAWeeklyReport.
 */
class InterfaceSAWeeklyReportTriggers extends DolibarrTriggers
{
	/**
	 * Constructor.
	 *
	 * @param	DoliDB	$db	Database handler
	 */
	public function __construct($db)
	{
		parent::__construct($db);
		$this->name = 'SAWeeklyReportTriggers';
		$this->description = 'Triggers for SAWeeklyReport module';
		$this->version = '1.0.0';
		$this->picto = 'fa-chart-line';
	}

	/**
	 * Trigger action.
	 *
	 * @param	string			$action	Action code
	 * @param	CommonObject	$object	Object
	 * @param	User			$user	User
	 * @param	Translate		$langs	Language
	 * @param	Conf			$conf	Configuration
	 * @return	int
	 */
	public function runTrigger($action, $object, User $user, Translate $langs, Conf $conf)
	{
		if (!isModEnabled('saweeklyreport')) {
			return 0;
		}
		if (strpos($action, 'SAWEEKLYREPORT_WEEKLYREPORT_') !== 0) {
			return 0;
		}

		dol_syslog(__METHOD__.' '.$action.' for object '.(empty($object->id) ? '0' : (int) $object->id), LOG_DEBUG);

		return 0;
	}
}
