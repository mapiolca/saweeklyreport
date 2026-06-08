<?php
/* Copyright (C) 2026  Pierre Ardoin <developpeur@lesmetiersdubatiment.fr> */

require_once DOL_DOCUMENT_ROOT.'/core/class/commonobject.class.php';

/**
 * Service/SAV line copied into a weekly report.
 */
class WeeklyReportService extends CommonObject
{
	public $module = 'saweeklyreport';
	public $element = 'weeklyreportservice';
	public $table_element = 'saweeklyreport_weeklyreportservice';
	public $picto = 'fa-wrench';
	public $isextrafieldmanaged = 0;
	public $ismultientitymanaged = 1;

	public $fields = array(
		'rowid' => array('type' => 'integer', 'label' => 'TechnicalID', 'enabled' => 1, 'position' => 1, 'notnull' => 1, 'visible' => 0, 'noteditable' => 1, 'index' => 1),
		'entity' => array('type' => 'integer', 'label' => 'Entity', 'enabled' => 1, 'position' => 5, 'notnull' => 1, 'visible' => -2, 'default' => 1, 'index' => 1),
		'fk_weeklyreport' => array('type' => 'integer', 'label' => 'WeeklyReport', 'enabled' => 1, 'position' => 10, 'notnull' => 1, 'visible' => 0, 'index' => 1),
		'source_element' => array('type' => 'varchar(64)', 'label' => 'SourceElement', 'enabled' => 1, 'position' => 20, 'notnull' => 0, 'visible' => 1),
		'source_id' => array('type' => 'integer', 'label' => 'SourceID', 'enabled' => 1, 'position' => 21, 'notnull' => 0, 'visible' => 1),
		'service_type' => array('type' => 'varchar(64)', 'label' => 'WeeklyReportServiceType', 'enabled' => 1, 'position' => 30, 'notnull' => 0, 'visible' => 1),
		'label' => array('type' => 'varchar(255)', 'label' => 'Label', 'enabled' => 1, 'position' => 40, 'notnull' => 1, 'visible' => 1, 'css' => 'minwidth300', 'searchall' => 1),
		'description' => array('type' => 'text', 'label' => 'Description', 'enabled' => 1, 'position' => 50, 'notnull' => 0, 'visible' => 3),
		'status' => array('type' => 'integer', 'label' => 'Status', 'enabled' => 1, 'position' => 60, 'notnull' => 0, 'visible' => 1),
		'position' => array('type' => 'integer', 'label' => 'Position', 'enabled' => 1, 'position' => 70, 'notnull' => 0, 'visible' => 1),
		'date_service' => array('type' => 'date', 'label' => 'Date', 'enabled' => 1, 'position' => 80, 'notnull' => 0, 'visible' => 1),
		'date_creation' => array('type' => 'datetime', 'label' => 'DateCreation', 'enabled' => 1, 'position' => 500, 'notnull' => 1, 'visible' => -2),
		'tms' => array('type' => 'timestamp', 'label' => 'DateModification', 'enabled' => 1, 'position' => 501, 'notnull' => 0, 'visible' => -2),
		'fk_user_creat' => array('type' => 'integer:User:user/class/user.class.php', 'label' => 'UserAuthor', 'enabled' => 1, 'position' => 510, 'notnull' => 1, 'visible' => -2),
		'fk_user_modif' => array('type' => 'integer:User:user/class/user.class.php', 'label' => 'UserModif', 'enabled' => 1, 'position' => 511, 'notnull' => -1, 'visible' => -2),
	);

	public $rowid;
	public $entity;
	public $fk_weeklyreport;
	public $source_element;
	public $source_id;
	public $service_type;
	public $label;
	public $description;
	public $status;
	public $position;
	public $date_service;
	public $date_creation;
	public $tms;
	public $fk_user_creat;
	public $fk_user_modif;

	/**
	 * Constructor.
	 *
	 * @param	DoliDB	$db	Database handler
	 */
	public function __construct(DoliDB $db)
	{
		$this->db = $db;
	}

	/**
	 * Create line.
	 *
	 * @param	User	$user		User
	 * @param	int		$notrigger	Disable triggers
	 * @return	int
	 */
	public function create(User $user, $notrigger = 0)
	{
		global $conf;

		if (empty($this->entity)) {
			$this->entity = (int) $conf->entity;
		}

		return $this->createCommon($user, $notrigger);
	}

	/**
	 * Fetch line.
	 *
	 * @param	int		$id		ID
	 * @param	string	$ref	Unused
	 * @return	int
	 */
	public function fetch($id, $ref = null)
	{
		return $this->fetchCommon($id, $ref, ' AND t.entity IN ('.getEntity('weeklyreport').')');
	}

	/**
	 * Update line.
	 *
	 * @param	User	$user		User
	 * @param	int		$notrigger	Disable triggers
	 * @return	int
	 */
	public function update(User $user, $notrigger = 0)
	{
		return $this->updateCommon($user, $notrigger);
	}

	/**
	 * Delete line.
	 *
	 * @param	User	$user		User
	 * @param	int		$notrigger	Disable triggers
	 * @return	int
	 */
	public function delete(User $user, $notrigger = 0)
	{
		return $this->deleteCommon($user, $notrigger);
	}
}
