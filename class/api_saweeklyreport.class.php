<?php
/* Copyright (C) 2026  Pierre Ardoin <developpeur@lesmetiersdubatiment.fr> */

use Luracast\Restler\RestException;

dol_include_once('/saweeklyreport/class/weeklyreport.class.php');

/**
 * API class for SAWeeklyReport.
 *
 * @access protected
 * @class  DolibarrApiAccess {@requires user,external}
 */
class Saweeklyreport extends DolibarrApi
{
	/**
	 * @var WeeklyReport
	 */
	public $weeklyreport;

	/**
	 * Constructor.
	 */
	public function __construct()
	{
		global $db;

		$this->db = $db;
		$this->weeklyreport = new WeeklyReport($this->db);
	}

	/**
	 * Get a weekly report.
	 *
	 * @param	int	$id	ID
	 * @return	object
	 *
	 * @url	GET weeklyreports/{id}
	 */
	public function get($id)
	{
		$this->checkModuleAndRight('read');
		if ($this->weeklyreport->fetch($id) <= 0) {
			throw new RestException(404, 'Weekly report not found');
		}

		return $this->_cleanObjectDatas($this->weeklyreport);
	}

	/**
	 * List weekly reports.
	 *
	 * @param	string	$sortfield	Sort field
	 * @param	string	$sortorder	Sort order
	 * @param	int		$limit		Limit
	 * @param	int		$page		Page
	 * @param	string	$sqlfilters	SQL filters
	 * @return	array<int,object>
	 *
	 * @url	GET weeklyreports/
	 */
	public function index($sortfield = 't.period_start', $sortorder = 'DESC', $limit = 100, $page = 0, $sqlfilters = '')
	{
		$this->checkModuleAndRight('read');

		$reports = array();
		$sql = "SELECT t.rowid FROM ".$this->db->prefix()."saweeklyreport_weeklyreport as t";
		$sql .= " WHERE t.entity IN (".getEntity('weeklyreport').")";
		if ($sqlfilters) {
			$errormessage = '';
			$sql .= forgeSQLFromUniversalSearchCriteria($sqlfilters, $errormessage);
			if ($errormessage) {
				throw new RestException(400, $errormessage);
			}
		}
		$sql .= $this->db->order($sortfield, $sortorder);
		if ($limit) {
			$page = max(0, (int) $page);
			$sql .= $this->db->plimit($limit + 1, $limit * $page);
		}

		$resql = $this->db->query($sql);
		if (!$resql) {
			throw new RestException(503, $this->db->lasterror());
		}
		while ($obj = $this->db->fetch_object($resql)) {
			$tmp = new WeeklyReport($this->db);
			if ($tmp->fetch((int) $obj->rowid) > 0) {
				$reports[] = $this->_cleanObjectDatas($tmp);
			}
		}

		return $reports;
	}

	/**
	 * Create a report.
	 *
	 * @param	array<string,mixed>	$request_data	Data
	 * @return	int
	 *
	 * @url	POST weeklyreports/
	 */
	public function post($request_data = null)
	{
		$this->checkModuleAndRight('write');
		if (!is_array($request_data)) {
			$request_data = array();
		}
		$readonlyfields = array('id', 'rowid', 'entity', 'ref', 'date_creation', 'tms', 'fk_user_creat', 'fk_user_modif', 'last_main_doc', 'status');
		foreach ($request_data as $field => $value) {
			if (!in_array($field, $readonlyfields, true) && array_key_exists($field, $this->weeklyreport->fields)) {
				$this->weeklyreport->$field = $this->_checkValForAPI($field, $value, $this->weeklyreport);
			}
		}
		if (empty($this->weeklyreport->year) || empty($this->weeklyreport->week)) {
			throw new RestException(400, 'year and week are required');
		}
		if ($this->weeklyreport->create(DolibarrApiAccess::$user) < 0) {
			throw new RestException(500, $this->weeklyreport->error, $this->weeklyreport->errors);
		}

		return (int) $this->weeklyreport->id;
	}

	/**
	 * Update a report.
	 *
	 * @param	int					$id				ID
	 * @param	array<string,mixed>	$request_data	Data
	 * @return	object
	 *
	 * @url	PUT weeklyreports/{id}
	 */
	public function put($id, $request_data = null)
	{
		$this->checkModuleAndRight('write');
		if ($this->weeklyreport->fetch($id) <= 0) {
			throw new RestException(404, 'Weekly report not found');
		}
		if (!is_array($request_data)) {
			$request_data = array();
		}
		$readonlyfields = array('id', 'rowid', 'entity', 'ref', 'date_creation', 'tms', 'fk_user_creat', 'fk_user_modif', 'last_main_doc', 'status');
		foreach ($request_data as $field => $value) {
			if (!in_array($field, $readonlyfields, true) && array_key_exists($field, $this->weeklyreport->fields)) {
				$this->weeklyreport->$field = $this->_checkValForAPI($field, $value, $this->weeklyreport);
			}
		}
		if ($this->weeklyreport->update(DolibarrApiAccess::$user) < 0) {
			throw new RestException(500, $this->weeklyreport->error, $this->weeklyreport->errors);
		}

		return $this->get($id);
	}

	/**
	 * Delete a report.
	 *
	 * @param	int	$id	ID
	 * @return	array<string,array<string,int|string>>
	 *
	 * @url	DELETE weeklyreports/{id}
	 */
	public function delete($id)
	{
		$this->checkModuleAndRight('delete');
		if ($this->weeklyreport->fetch($id) <= 0) {
			throw new RestException(404, 'Weekly report not found');
		}
		if ($this->weeklyreport->delete(DolibarrApiAccess::$user) < 0) {
			throw new RestException(500, $this->weeklyreport->error, $this->weeklyreport->errors);
		}

		return array('success' => array('code' => 200, 'message' => 'Weekly report deleted'));
	}

	/**
	 * Refresh report data.
	 *
	 * @param	int	$id	ID
	 * @return	object
	 *
	 * @url	POST weeklyreports/{id}/refresh
	 */
	public function refresh($id)
	{
		$this->checkModuleAndRight('write');
		if ($this->weeklyreport->fetch($id) <= 0) {
			throw new RestException(404, 'Weekly report not found');
		}
		if ($this->weeklyreport->refreshData(DolibarrApiAccess::$user, 1) < 0) {
			throw new RestException(500, $this->weeklyreport->error, $this->weeklyreport->errors);
		}

		return $this->get($id);
	}

	/**
	 * Generate PPTX.
	 *
	 * @param	int	$id	ID
	 * @return	object
	 *
	 * @url	POST weeklyreports/{id}/generate
	 */
	public function generate($id)
	{
		global $langs;

		$this->checkModuleAndRight('write');
		if ($this->weeklyreport->fetch($id) <= 0) {
			throw new RestException(404, 'Weekly report not found');
		}
		if ($this->weeklyreport->generatePptx($langs) < 0) {
			throw new RestException(500, $this->weeklyreport->error, $this->weeklyreport->errors);
		}

		return $this->get($id);
	}

	/**
	 * Validate a report.
	 *
	 * @param	int	$id	ID
	 * @return	object
	 *
	 * @url	POST weeklyreports/{id}/validate
	 */
	public function validate($id)
	{
		$this->checkModuleAndRight('validate');
		if ($this->weeklyreport->fetch($id) <= 0) {
			throw new RestException(404, 'Weekly report not found');
		}
		if ($this->weeklyreport->validate(DolibarrApiAccess::$user) < 0) {
			throw new RestException(500, $this->weeklyreport->error, $this->weeklyreport->errors);
		}

		return $this->get($id);
	}

	/**
	 * Set a report back to draft.
	 *
	 * @param	int	$id	ID
	 * @return	object
	 *
	 * @url	POST weeklyreports/{id}/unvalidate
	 */
	public function unvalidate($id)
	{
		$this->checkModuleAndRight('validate');
		if ($this->weeklyreport->fetch($id) <= 0) {
			throw new RestException(404, 'Weekly report not found');
		}
		if ($this->weeklyreport->setDraft(DolibarrApiAccess::$user) < 0) {
			throw new RestException(500, $this->weeklyreport->error, $this->weeklyreport->errors);
		}

		return $this->get($id);
	}

	/**
	 * List editable service lines for a report.
	 *
	 * @param	int	$id	ID
	 * @return	array<int,object>
	 *
	 * @url	GET weeklyreports/{id}/services
	 */
	public function services($id)
	{
		$this->checkModuleAndRight('read');
		if ($this->weeklyreport->fetch($id) <= 0) {
			throw new RestException(404, 'Weekly report not found');
		}

		$lines = array();
		foreach ($this->weeklyreport->lines as $line) {
			$lines[] = $this->_cleanObjectDatas($line);
		}

		return $lines;
	}

	/**
	 * Check module and permission.
	 *
	 * @param	string	$right	Right
	 * @return	void
	 */
	private function checkModuleAndRight($right)
	{
		if (!isModEnabled('saweeklyreport')) {
			throw new RestException(403, 'Module disabled');
		}
		if (!empty(DolibarrApiAccess::$user->socid)) {
			throw new RestException(403);
		}
		if (!DolibarrApiAccess::$user->hasRight('saweeklyreport', 'weeklyreport', 'api')) {
			throw new RestException(403);
		}
		if (!DolibarrApiAccess::$user->hasRight('saweeklyreport', 'weeklyreport', $right)) {
			throw new RestException(403);
		}
	}

	/**
	 * Clean object data for API output.
	 *
	 * @param	object	$object	Object
	 * @return	object
	 */
	protected function _cleanObjectDatas($object)
	{
		$object = parent::_cleanObjectDatas($object);
		unset($object->note_private);

		return $object;
	}
}
