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

	/**
	 * Declare native notification supported events.
	 *
	 * @param	array<string,mixed>	$parameters		Hook parameters
	 * @param	CommonObject		$object			Current object
	 * @param	string				$action			Current action
	 * @param	HookManager			$hookmanager	Hook manager
	 * @return	int
	 */
	public function notifsupported($parameters, &$object, &$action, $hookmanager)
	{
		$this->results = array(
			'arrayofnotifsupported' => array(
				'SAWEEKLYREPORT_WEEKLYREPORT_CREATE',
				'SAWEEKLYREPORT_WEEKLYREPORT_UPDATE',
				'SAWEEKLYREPORT_WEEKLYREPORT_DELETE',
			),
		);

		return 0;
	}

	/**
	 * Secure document access for normalized and legacy weekly report paths.
	 *
	 * @param	array<string,mixed>	$parameters		Hook parameters
	 * @param	CommonObject		$object			Current object
	 * @param	string				$action			Current action
	 * @param	HookManager			$hookmanager	Hook manager
	 * @return	int
	 */
	public function checkSecureAccess($parameters, &$object, &$action, $hookmanager)
	{
		if ((string) ($parameters['modulepart'] ?? '') !== 'saweeklyreport') {
			return 0;
		}

		dol_include_once('/saweeklyreport/class/weeklyreport.class.php');
		dol_include_once('/saweeklyreport/lib/saweeklyreport.lib.php');
		if (!class_exists('WeeklyReport') || !function_exists('saweeklyreportCanDo')) {
			return 0;
		}

		$requestfile = str_replace('\\', '/', (string) GETPOST('file', 'restricthtml'));
		$requestfile = trim($requestfile, '/');
		$parts = explode('/', $requestfile);
		$legacy = false;
		if (count($parts) >= 3 && preg_match('/^\d+$/', $parts[0]) && $parts[1] === 'weeklyreport') {
			$legacy = true;
			$ref = (string) $parts[2];
			$fileparts = array_slice($parts, 3);
		} elseif (count($parts) >= 2 && $parts[0] === 'weeklyreport') {
			$ref = (string) $parts[1];
			$fileparts = array_slice($parts, 2);
		} else {
			return 0;
		}
		if ($ref === '' || empty($fileparts) || in_array('..', $fileparts, true)) {
			return 0;
		}

		$report = new WeeklyReport($this->db);
		if ($report->fetch(0, $ref) <= 0) {
			return 0;
		}

		$fuser = is_object($parameters['fuser'] ?? null) ? $parameters['fuser'] : $GLOBALS['user'];
		$mode = (string) ($parameters['mode'] ?? 'read');
		$neededright = ($mode === 'write') ? 'write' : 'read';
		if (!saweeklyreportCanDo($fuser, $report, $neededright)) {
			return 0;
		}

		$base = $legacy ? $report->getLegacyDocumentDir() : $report->getDocumentDir();
		$fullpath = $base.'/'.implode('/', $fileparts);
		$realbase = realpath($base);
		$realfile = realpath($fullpath);
		if ($realbase === false || $realfile === false || strpos(str_replace('\\', '/', $realfile), str_replace('\\', '/', $realbase).'/') !== 0) {
			return 0;
		}

		$this->results = array(
			'accessallowed' => 1,
			'original_file' => $realfile,
			'sqlprotectagainstexternals' => '',
		);

		return 1;
	}
}
