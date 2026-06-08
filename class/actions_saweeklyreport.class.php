<?php
/* Copyright (C) 2026  Pierre Ardoin <developpeur@lesmetiersdubatiment.fr> */

/**
 * Hooks for SAWeeklyReport.
 */
class ActionsSAWeeklyReport
{
	public const MULTICOMPANY_SHARING_ROOT_KEY = 'saweeklyreport';

	/**
	 * @var DoliDB
	 */
	public $db;

	/**
	 * @var array<string,mixed>
	 */
	public $results = array();

	/**
	 * @var string
	 */
	public $resprints = '';

	/**
	 * @var string
	 */
	public $error = '';

	/**
	 * @var string[]
	 */
	public $errors = array();

	/**
	 * Constructor.
	 *
	 * @param	DoliDB	$db	Database handler
	 */
	public function __construct($db)
	{
		$this->db = $db;
	}

	/**
	 * Build Multicompany sharing payload.
	 *
	 * @return	array<string,array<string,mixed>>
	 */
	public static function getMulticompanySharingDefinition()
	{
		return array(
			self::MULTICOMPANY_SHARING_ROOT_KEY => array(
				'sharingelements' => array(
					'weeklyreport' => array(
						'type' => 'element',
						'icon' => 'chart-line',
						'lang' => 'saweeklyreport@saweeklyreport',
						'tooltip' => 'WeeklyReportSharingInfo',
						'enable' => '! empty($conf->saweeklyreport->enabled)',
						'input' => array(
							'global' => array(
								'showhide' => true,
								'hide' => true,
								'del' => true,
							),
						),
					),
					'weeklyreportnumber' => array(
						'type' => 'objectnumber',
						'icon' => 'hashtag',
						'lang' => 'saweeklyreport@saweeklyreport',
						'tooltip' => 'WeeklyReportNumberSharingInfo',
						'enable' => '! empty($conf->saweeklyreport->enabled)',
						'input' => array(
							'global' => array(
								'showhide' => true,
								'hide' => true,
								'del' => true,
							),
						),
					),
				),
				'sharingmodulename' => array(
					'weeklyreport' => 'saweeklyreport',
					'weeklyreportnumber' => 'saweeklyreport',
				),
			),
		);
	}

	/**
	 * Register sharing definition.
	 *
	 * @return	void
	 */
	private function registerMulticompanySharingDefinition()
	{
		global $langs;

		$langs->loadLangs(array('saweeklyreport@saweeklyreport'));
		if (!is_array($this->results)) {
			$this->results = array();
		}

		$this->results = array_replace_recursive($this->results, self::getMulticompanySharingDefinition());
	}

	/**
	 * Multicompany hook.
	 *
	 * @param	array<string,mixed>	$parameters		Hook parameters
	 * @param	CommonObject		$object			Current object
	 * @param	string				$action			Current action
	 * @param	HookManager			$hookmanager	Hook manager
	 * @return	int
	 */
	public function multicompanyExternalModulesSharing($parameters, &$object, &$action, $hookmanager)
	{
		$this->registerMulticompanySharingDefinition();

		return 0;
	}

	/**
	 * Backward-compatible Multicompany hook alias.
	 *
	 * @param	array<string,mixed>	$parameters		Hook parameters
	 * @param	CommonObject		$object			Current object
	 * @param	string				$action			Current action
	 * @param	HookManager			$hookmanager	Hook manager
	 * @return	int
	 */
	public function multicompanyExternalModuleSharing($parameters, &$object, &$action, $hookmanager)
	{
		$this->registerMulticompanySharingDefinition();

		return 0;
	}

	/**
	 * Additional Multicompany hook alias.
	 *
	 * @param	array<string,mixed>	$parameters		Hook parameters
	 * @param	CommonObject		$object			Current object
	 * @param	string				$action			Current action
	 * @param	HookManager			$hookmanager	Hook manager
	 * @return	int
	 */
	public function multicompanySharingOptions($parameters, &$object, &$action, $hookmanager)
	{
		$this->registerMulticompanySharingDefinition();

		return 0;
	}
}
