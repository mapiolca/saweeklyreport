<?php
/* Copyright (C) 2026  Pierre Ardoin <developpeur@lesmetiersdubatiment.fr> */

require_once DOL_DOCUMENT_ROOT.'/core/class/commonobject.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
require_once __DIR__.'/weeklyreportservice.class.php';
require_once __DIR__.'/saweeklyreporttickethelper.class.php';

/**
 * Weekly operational report.
 */
class WeeklyReport extends CommonObject
{
	public $module = 'saweeklyreport';
	public $mainmodule = 'saweeklyreport';
	public $element = 'weeklyreport';
	public $TRIGGER_PREFIX = 'SAWEEKLYREPORT_WEEKLYREPORT';
	public $table_element = 'saweeklyreport_weeklyreport';
	public $picto = 'fa-chart-line';
	public $isextrafieldmanaged = 0;
	public $ismultientitymanaged = 1;

	const STATUS_DRAFT = 0;
	const STATUS_VALIDATED = 1;
	const STATUS_CANCELED = 9;
	const DOC_MODEL_PDF_TCPDF = 'pdf_weeklyreport_powerpoint';

	public $fields = array(
		'rowid' => array('type' => 'integer', 'label' => 'TechnicalID', 'enabled' => 1, 'position' => 1, 'notnull' => 1, 'visible' => 0, 'noteditable' => 1, 'index' => 1),
		'entity' => array('type' => 'integer', 'label' => 'Entity', 'enabled' => 1, 'position' => 5, 'notnull' => 1, 'visible' => -2, 'default' => 1, 'index' => 1),
		'ref' => array('type' => 'varchar(128)', 'label' => 'Ref', 'enabled' => 1, 'position' => 10, 'notnull' => 1, 'visible' => 1, 'index' => 1, 'searchall' => 1, 'showoncombobox' => 1, 'default' => '(PROV)'),
		'label' => array('type' => 'varchar(255)', 'label' => 'Label', 'enabled' => 1, 'position' => 20, 'notnull' => 0, 'visible' => 1, 'searchall' => 1, 'css' => 'minwidth300'),
		'year' => array('type' => 'integer', 'label' => 'Year', 'enabled' => 1, 'position' => 30, 'notnull' => 1, 'visible' => 1, 'index' => 1),
		'week' => array('type' => 'integer', 'label' => 'Week', 'enabled' => 1, 'position' => 31, 'notnull' => 1, 'visible' => 1, 'index' => 1),
		'period_start' => array('type' => 'date', 'label' => 'WeeklyReportPeriodStart', 'enabled' => 1, 'position' => 40, 'notnull' => 1, 'visible' => 1),
		'period_end' => array('type' => 'date', 'label' => 'WeeklyReportPeriodEnd', 'enabled' => 1, 'position' => 41, 'notnull' => 1, 'visible' => 1),
		'month_start' => array('type' => 'date', 'label' => 'WeeklyReportMonthStart', 'enabled' => 1, 'position' => 42, 'notnull' => 0, 'visible' => -1),
		'month_end' => array('type' => 'date', 'label' => 'WeeklyReportMonthEnd', 'enabled' => 1, 'position' => 43, 'notnull' => 0, 'visible' => -1),
		'meeting_duration' => array('type' => 'integer', 'label' => 'WeeklyReportMeetingDuration', 'enabled' => 1, 'position' => 50, 'notnull' => 0, 'visible' => 1, 'default' => 15),
		'week_installed_power' => array('type' => 'double(24,8)', 'label' => 'WeeklyReportWeekInstalledPower', 'enabled' => 1, 'position' => 60, 'notnull' => 0, 'visible' => 1, 'css' => 'right', 'csslist' => 'right'),
		'month_installed_power' => array('type' => 'double(24,8)', 'label' => 'WeeklyReportMonthInstalledPower', 'enabled' => 1, 'position' => 61, 'notnull' => 0, 'visible' => 1, 'css' => 'right', 'csslist' => 'right'),
		'annual_installed_power' => array('type' => 'double(24,8)', 'label' => 'WeeklyReportAnnualInstalledPower', 'enabled' => 1, 'position' => 62, 'notnull' => 0, 'visible' => 1, 'css' => 'right', 'csslist' => 'right'),
		'annual_target_power' => array('type' => 'double(24,8)', 'label' => 'WeeklyReportAnnualTargetPower', 'enabled' => 1, 'position' => 63, 'notnull' => 0, 'visible' => 1, 'css' => 'right', 'csslist' => 'right'),
		'weekly_target_power' => array('type' => 'double(24,8)', 'label' => 'WeeklyReportWeeklyTargetPower', 'enabled' => 1, 'position' => 64, 'notnull' => 0, 'visible' => 1, 'css' => 'right', 'csslist' => 'right'),
		'annual_completion_rate' => array('type' => 'double(24,8)', 'label' => 'WeeklyReportAnnualCompletionRate', 'enabled' => 1, 'position' => 65, 'notnull' => 0, 'visible' => 1, 'css' => 'right', 'csslist' => 'right'),
		'annual_average_power' => array('type' => 'double(24,8)', 'label' => 'WeeklyReportAnnualAveragePower', 'enabled' => 1, 'position' => 66, 'notnull' => 0, 'visible' => 1, 'css' => 'right', 'csslist' => 'right'),
		'workweeks_elapsed' => array('type' => 'double(24,8)', 'label' => 'WeeklyReportWorkweeksElapsed', 'enabled' => 1, 'position' => 67, 'notnull' => 0, 'visible' => 1, 'css' => 'right', 'csslist' => 'right'),
		'technician_days' => array('type' => 'double(24,8)', 'label' => 'WeeklyReportTechnicianDays', 'enabled' => 1, 'position' => 70, 'notnull' => 0, 'visible' => 1, 'css' => 'right', 'csslist' => 'right'),
		'technician_workdays' => array('type' => 'double(24,8)', 'label' => 'WeeklyReportTechnicianWorkdays', 'enabled' => 1, 'position' => 71, 'notnull' => 0, 'visible' => 1, 'default' => 5, 'css' => 'right', 'csslist' => 'right'),
		'technician_average' => array('type' => 'double(24,8)', 'label' => 'WeeklyReportTechnicianAverage', 'enabled' => 1, 'position' => 72, 'notnull' => 0, 'visible' => 1, 'css' => 'right', 'csslist' => 'right'),
		'previous_week_feedback' => array('type' => 'text', 'label' => 'WeeklyReportPreviousWeekFeedback', 'enabled' => 1, 'position' => 80, 'notnull' => 0, 'visible' => 3, 'css' => 'minwidth500'),
		'current_week_goal' => array('type' => 'text', 'label' => 'WeeklyReportCurrentWeekGoal', 'enabled' => 1, 'position' => 81, 'notnull' => 0, 'visible' => 3, 'css' => 'minwidth500'),
		'field_returns' => array('type' => 'text', 'label' => 'WeeklyReportFieldReturns', 'enabled' => 1, 'position' => 82, 'notnull' => 0, 'visible' => 3, 'css' => 'minwidth500'),
		'safety_message' => array('type' => 'text', 'label' => 'WeeklyReportSafetyMessage', 'enabled' => 1, 'position' => 83, 'notnull' => 0, 'visible' => 3, 'css' => 'minwidth500'),
		'vehicle_loading_reminder' => array('type' => 'text', 'label' => 'WeeklyReportVehicleLoadingReminder', 'enabled' => 1, 'position' => 84, 'notnull' => 0, 'visible' => 3, 'css' => 'minwidth500'),
		'source_snapshot' => array('type' => 'text', 'label' => 'WeeklyReportSourceSnapshot', 'enabled' => 1, 'position' => 90, 'notnull' => 0, 'visible' => 0),
		'note_public' => array('type' => 'html', 'label' => 'NotePublic', 'enabled' => 1, 'position' => 100, 'notnull' => 0, 'visible' => 0, 'cssview' => 'wordbreak'),
		'note_private' => array('type' => 'html', 'label' => 'NotePrivate', 'enabled' => 1, 'position' => 101, 'notnull' => 0, 'visible' => 0, 'cssview' => 'wordbreak'),
		'date_creation' => array('type' => 'datetime', 'label' => 'DateCreation', 'enabled' => 1, 'position' => 500, 'notnull' => 1, 'visible' => -2),
		'tms' => array('type' => 'timestamp', 'label' => 'DateModification', 'enabled' => 1, 'position' => 501, 'notnull' => 0, 'visible' => -2),
		'fk_user_creat' => array('type' => 'integer:User:user/class/user.class.php', 'label' => 'UserAuthor', 'enabled' => 1, 'position' => 510, 'notnull' => 1, 'visible' => -2),
		'fk_user_modif' => array('type' => 'integer:User:user/class/user.class.php', 'label' => 'UserModif', 'enabled' => 1, 'position' => 511, 'notnull' => -1, 'visible' => -2),
		'last_main_doc' => array('type' => 'varchar(255)', 'label' => 'LastMainDoc', 'enabled' => 1, 'position' => 600, 'notnull' => 0, 'visible' => 0),
		'import_key' => array('type' => 'varchar(14)', 'label' => 'ImportId', 'enabled' => 1, 'position' => 1000, 'notnull' => -1, 'visible' => -2),
		'model_pptx' => array('type' => 'varchar(255)', 'label' => 'WeeklyReportPptxModel', 'enabled' => 1, 'position' => 1010, 'notnull' => -1, 'visible' => 0),
		'status' => array('type' => 'integer', 'label' => 'Status', 'enabled' => 1, 'position' => 2000, 'notnull' => 1, 'visible' => 1, 'index' => 1, 'default' => self::STATUS_DRAFT, 'arrayofkeyval' => array(self::STATUS_DRAFT => 'Draft', self::STATUS_VALIDATED => 'Validated', self::STATUS_CANCELED => 'Canceled')),
	);

	public $rowid;
	public $entity;
	public $ref;
	public $label;
	public $year;
	public $week;
	public $period_start;
	public $period_end;
	public $month_start;
	public $month_end;
	public $meeting_duration;
	public $week_installed_power;
	public $month_installed_power;
	public $annual_installed_power;
	public $annual_target_power;
	public $weekly_target_power;
	public $annual_completion_rate;
	public $annual_average_power;
	public $workweeks_elapsed;
	public $technician_days;
	public $technician_workdays;
	public $technician_average;
	public $previous_week_feedback;
	public $current_week_goal;
	public $field_returns;
	public $safety_message;
	public $vehicle_loading_reminder;
	public $source_snapshot;
	public $note_public;
	public $note_private;
	public $date_creation;
	public $tms;
	public $fk_user_creat;
	public $fk_user_modif;
	public $user_creation_id;
	public $user_modification_id;
	public $last_main_doc;
	public $import_key;
	public $model_pdf;
	public $model_pptx;
	public $status;

	/**
	 * @var WeeklyReportService[]
	 */
	public $lines = array();

	/**
	 * Constructor.
	 *
	 * @param	DoliDB	$db	Database handler
	 */
	public function __construct(DoliDB $db)
	{
		global $langs;

		$this->db = $db;

		if (!isModEnabled('multicompany') && isset($this->fields['entity'])) {
			$this->fields['entity']['enabled'] = 0;
		}
		foreach ($this->fields as $key => $val) {
			if (isset($val['enabled']) && empty($val['enabled'])) {
				unset($this->fields[$key]);
			}
		}
		if (is_object($langs)) {
			foreach ($this->fields as $key => $val) {
				if (!empty($val['arrayofkeyval']) && is_array($val['arrayofkeyval'])) {
					foreach ($val['arrayofkeyval'] as $key2 => $val2) {
						$this->fields[$key]['arrayofkeyval'][$key2] = $langs->trans($val2);
					}
				}
			}
		}
	}

	/**
	 * Create report.
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
		$this->prepareDefaults();
		if (empty($this->ref)) {
			$this->ref = '(PROV)';
		}
		if (!isset($this->status)) {
			$this->status = self::STATUS_DRAFT;
		}

		$this->db->begin();

		$result = $this->createCommon($user, 1);
		if ($result < 0) {
			$this->db->rollback();
			return $result;
		}

		if (preg_match('/^\(PROV[0-9]*\)$/', (string) $this->ref)) {
			$refresult = $this->assignFinalReference($user);
			if ($refresult < 0) {
				$this->db->rollback();
				return -1;
			}
		}

		if (!$notrigger) {
			$resulttrigger = $this->call_trigger($this->TRIGGER_PREFIX.'_CREATE', $user);
			if ($resulttrigger < 0) {
				$this->db->rollback();
				return -1;
			}
		}

		$this->db->commit();

		$refresh = $this->refreshData($user, 1, 1);
		if ($refresh < 0) {
			$this->errors[] = $this->error;
		}

		return $result;
	}

	/**
	 * Fetch report.
	 *
	 * @param	int			$id				ID
	 * @param	string|null	$ref			Reference
	 * @param	int			$noextrafields	Unused
	 * @param	int			$nolines		Do not fetch lines
	 * @return	int
	 */
	public function fetch($id, $ref = null, $noextrafields = 0, $nolines = 0)
	{
		$result = $this->fetchCommon($id, $ref, ' AND t.entity IN ('.getEntity($this->element).')', $noextrafields);
		if ($result > 0) {
			$this->model_pdf = $this->model_pptx;
			$this->user_creation_id = (int) $this->fk_user_creat;
			$this->user_modification_id = (int) $this->fk_user_modif;
			$this->date_modification = $this->tms;
		}
		if ($result > 0 && empty($nolines)) {
			$this->fetchLines();
		}

		return $result;
	}

	/**
	 * Fetch report by year/week.
	 *
	 * @param	int	$year			Year
	 * @param	int	$week			Week
	 * @param	int	$strictentity	Restrict to current entity
	 * @return	int
	 */
	public function fetchByYearWeek($year, $week, $strictentity = 0)
	{
		global $conf;

		$sql = "SELECT rowid FROM ".$this->db->prefix().$this->table_element;
		$sql .= " WHERE year = ".((int) $year);
		$sql .= " AND week = ".((int) $week);
		if ($strictentity) {
			$sql .= " AND entity = ".((int) $conf->entity);
		} else {
			$sql .= " AND entity IN (".getEntity($this->element).")";
		}

		$resql = $this->db->query($sql);
		if (!$resql) {
			$this->error = $this->db->lasterror();
			return -1;
		}
		$obj = $this->db->fetch_object($resql);
		if (!$obj) {
			return 0;
		}

		return $this->fetch((int) $obj->rowid);
	}

	/**
	 * Fetch service lines.
	 *
	 * @return	int
	 */
	public function fetchLines()
	{
		$this->lines = array();

		$sql = "SELECT rowid FROM ".$this->db->prefix()."saweeklyreport_weeklyreportservice";
		$sql .= " WHERE fk_weeklyreport = ".((int) $this->id);
		$sql .= " AND entity IN (".getEntity($this->element).")";
		$sql .= " ORDER BY position ASC, rowid ASC";

		$resql = $this->db->query($sql);
		if (!$resql) {
			$this->error = $this->db->lasterror();
			return -1;
		}

		while ($obj = $this->db->fetch_object($resql)) {
			$line = new WeeklyReportService($this->db);
			if ($line->fetch((int) $obj->rowid) > 0) {
				$this->lines[] = $line;
			}
		}

		return 1;
	}

	/**
	 * Update report.
	 *
	 * @param	User	$user		User
	 * @param	int		$notrigger	Disable triggers
	 * @return	int
	 */
	public function update(User $user, $notrigger = 0)
	{
		$this->prepareComputedValues();

		$result = $this->updateCommon($user, 1);
		if ($result < 0) {
			return $result;
		}

		if (!$notrigger) {
			$resulttrigger = $this->call_trigger($this->TRIGGER_PREFIX.'_UPDATE', $user);
			if ($resulttrigger < 0) {
				return -1;
			}
		}

		return $result;
	}

	/**
	 * Delete report and local service lines.
	 *
	 * @param	User	$user		User
	 * @param	int		$notrigger	Disable triggers
	 * @return	int
	 */
	public function delete(User $user, $notrigger = 0)
	{
		$this->db->begin();

		$sql = "DELETE FROM ".$this->db->prefix()."saweeklyreport_weeklyreportservice";
		$sql .= " WHERE fk_weeklyreport = ".((int) $this->id);
		$sql .= " AND entity = ".((int) $this->entity);
		$resql = $this->db->query($sql);
		if (!$resql) {
			$this->error = $this->db->lasterror();
			$this->db->rollback();
			return -1;
		}

		$result = $this->deleteCommon($user, 1);
		if ($result < 0) {
			$this->db->rollback();
			return $result;
		}

		if (!$notrigger) {
			$resulttrigger = $this->call_trigger($this->TRIGGER_PREFIX.'_DELETE', $user);
			if ($resulttrigger < 0) {
				$this->db->rollback();
				return -1;
			}
		}

		$this->db->commit();

		return $result;
	}

	/**
	 * Validate report.
	 *
	 * @param	User	$user		User
	 * @param	int		$notrigger	Disable triggers
	 * @return	int
	 */
	public function validate($user, $notrigger = 0)
	{
		return $this->setWeeklyReportStatus($user, self::STATUS_VALIDATED, 'status_validate', $notrigger);
	}

	/**
	 * Set draft status.
	 *
	 * @param	User	$user		User
	 * @param	int		$notrigger	Disable triggers
	 * @return	int
	 */
	public function setDraft($user, $notrigger = 0)
	{
		return $this->setWeeklyReportStatus($user, self::STATUS_DRAFT, 'status_setdraft', $notrigger);
	}

	/**
	 * Cancel report.
	 *
	 * @param	User	$user		User
	 * @param	int		$notrigger	Disable triggers
	 * @return	int
	 */
	public function cancel($user, $notrigger = 0)
	{
		return $this->setWeeklyReportStatus($user, self::STATUS_CANCELED, 'status_cancel', $notrigger);
	}

	/**
	 * Set report status and trigger a CRUD update.
	 *
	 * @param	User	$user		User
	 * @param	int		$status		New status
	 * @param	string	$reason		Stable trigger reason
	 * @param	int		$notrigger	Disable triggers
	 * @return	int
	 */
	private function setWeeklyReportStatus($user, $status, $reason, $notrigger = 0)
	{
		if (empty($this->id)) {
			$this->error = 'ErrorObjectMustBeFetched';
			return -1;
		}

		$oldcopy = clone $this;
		$this->oldcopy = $oldcopy;
		$this->status = (int) $status;

		$this->db->begin();
		$result = $this->update($user, 1);
		if ($result < 0) {
			$this->db->rollback();
			return -1;
		}

		if (!$notrigger) {
			$this->context['trigger_reason'] = $reason;
			$this->context['changed_fields'] = array('status');
			$this->context['old_status'] = (int) $oldcopy->status;
			$this->context['new_status'] = (int) $this->status;

			$resulttrigger = $this->call_trigger($this->TRIGGER_PREFIX.'_UPDATE', $user);
			if ($resulttrigger < 0) {
				$this->db->rollback();
				return -1;
			}
		}

		$this->db->commit();

		return 1;
	}

	/**
	 * Prepare defaults before insert.
	 *
	 * @return	void
	 */
	public function prepareDefaults()
	{
		global $conf;

		$now = dol_now();
		if (empty($this->year)) {
			$this->year = (int) date('o', $now);
		}
		if (empty($this->week)) {
			$this->week = (int) date('W', $now);
		}

		$bounds = $this->getIsoWeekBounds((int) $this->year, (int) $this->week);
		if (empty($this->period_start)) {
			$this->period_start = $bounds['start'];
		}
		if (empty($this->period_end)) {
			$this->period_end = $bounds['end'];
		}
		if (empty($this->month_start) || empty($this->month_end)) {
			$monthbounds = $this->getIsoWeekMonthBounds((int) $this->year, (int) $this->week);
			$this->month_start = $monthbounds['start'];
			$this->month_end = $monthbounds['end'];
		}
		if (empty($this->label)) {
			$this->label = 'Semaine '.sprintf('%02d', (int) $this->week).' - '.((int) $this->year);
		}
		if (empty($this->meeting_duration)) {
			$this->meeting_duration = getDolGlobalInt('SAWEEKLYREPORT_MEETING_DURATION', 15);
		}
		if ($this->annual_target_power === null || $this->annual_target_power === '') {
			$this->annual_target_power = (float) getDolGlobalString('SAWEEKLYREPORT_ANNUAL_TARGET_POWER', '846');
		}
		if ($this->weekly_target_power === null || $this->weekly_target_power === '') {
			$this->weekly_target_power = (float) getDolGlobalString('SAWEEKLYREPORT_WEEKLY_TARGET_POWER', '18');
		}
		if ($this->technician_workdays === null || $this->technician_workdays === '') {
			$this->technician_workdays = 5;
		}
		if ($this->safety_message === null || $this->safety_message === '') {
			$this->safety_message = getDolGlobalString('SAWEEKLYREPORT_DEFAULT_SAFETY_MESSAGE', 'Rappel : port du harnais obligatoire en toiture. Pas de précipitation en fin de chantier.');
		}
		if ($this->vehicle_loading_reminder === null || $this->vehicle_loading_reminder === '') {
			$this->vehicle_loading_reminder = getDolGlobalString('SAWEEKLYREPORT_DEFAULT_LOADING_REMINDER', 'Pour rappel : réaliser le chargement des véhicules la veille du chantier.');
		}
		if (empty($this->model_pptx)) {
			$this->model_pptx = getDolGlobalString('SAWEEKLYREPORT_WEEKLYREPORT_ADDON_PDF');
			if (empty($this->model_pptx)) {
				$this->model_pptx = getDolGlobalString('SAWEEKLYREPORT_WEEKLYREPORT_ADDON_PPTX', 'weekly_report_standard');
			}
		}
		$this->model_pdf = $this->model_pptx;

		$this->prepareComputedValues();
	}

	/**
	 * Recompute derived local values.
	 *
	 * @return	void
	 */
	public function prepareComputedValues()
	{
		$this->technician_average = ((float) $this->technician_workdays > 0 ? (float) $this->technician_days / (float) $this->technician_workdays : 0);
		$this->annual_completion_rate = ((float) $this->annual_target_power > 0 ? ((float) $this->annual_installed_power / (float) $this->annual_target_power) * 100 : 0);
		$this->annual_average_power = ((float) $this->workweeks_elapsed > 0 ? (float) $this->annual_installed_power / (float) $this->workweeks_elapsed : 0);
	}

	/**
	 * Refresh calculated values and service lines.
	 *
	 * @param	User	$user					User
	 * @param	int		$replaceServiceLines	Replace editable service lines
	 * @param	int		$notrigger				Disable triggers
	 * @return	int
	 */
	public function refreshData(User $user, $replaceServiceLines = 1, $notrigger = 0)
	{
		if (empty($this->id)) {
			$this->error = 'ErrorObjectMustBeFetched';
			return -1;
		}

		$this->prepareDefaults();

		$periodstart = $this->getSqlDate($this->period_start);
		$periodendnext = $this->getNextSqlDate($this->period_end);
		$monthstart = $this->getSqlDate($this->month_start);
		$monthendnext = $this->getNextSqlDate($this->month_end);
		$yearstart = sprintf('%04d-01-01', (int) $this->year);
		$yearendnext = $periodendnext;

		$weektotal = $this->fetchOrderPowerTotal($periodstart, $periodendnext);
		if ($weektotal < 0) {
			return -1;
		}
		$monthtotal = $this->fetchOrderPowerTotal($monthstart, $monthendnext);
		if ($monthtotal < 0) {
			return -1;
		}
		$yeartotal = $this->fetchOrderPowerTotal($yearstart, $yearendnext);
		if ($yeartotal < 0) {
			return -1;
		}

		$this->week_installed_power = (float) $weektotal;
		$this->month_installed_power = (float) $monthtotal;
		$this->annual_installed_power = (float) $yeartotal;
		$this->workweeks_elapsed = $this->countWorkweeksElapsed();
		$this->prepareComputedValues();

		$services = $this->fetchNativeServiceSources($periodstart, $periodendnext);
		$snapshot = array(
			'generated_at' => dol_print_date(dol_now(), 'dayhourlog'),
			'period_start' => $periodstart,
			'period_end' => $this->getSqlDate($this->period_end),
			'power_source' => 'commande.date_cloture + commande_extrafields.powerplantpv_peak_power',
			'week_installed_power' => $this->week_installed_power,
			'month_installed_power' => $this->month_installed_power,
			'annual_installed_power' => $this->annual_installed_power,
			'service_sources' => $services,
		);
		$this->source_snapshot = json_encode($snapshot, JSON_UNESCAPED_UNICODE);

		$this->db->begin();

		$result = $this->update($user, 1);
		if ($result < 0) {
			$this->db->rollback();
			return -1;
		}

		if (!empty($replaceServiceLines)) {
			$result = $this->replaceServiceLines($services, $user);
			if ($result < 0) {
				$this->db->rollback();
				return -1;
			}
		}

		if (!$notrigger) {
			$resulttrigger = $this->call_trigger($this->TRIGGER_PREFIX.'_UPDATE', $user);
			if ($resulttrigger < 0) {
				$this->db->rollback();
				return -1;
			}
		}

		$this->db->commit();
		if ($this->linkNativeSources($services, $user) < 0) {
			return -1;
		}
		$this->fetch((int) $this->id);

		return 1;
	}

	/**
	 * Replace service lines from source snapshot.
	 *
	 * @param	array<int,array<string,mixed>>	$services	Services
	 * @param	User							$user		User
	 * @return	int
	 */
	private function replaceServiceLines($services, User $user)
	{
		$sql = "DELETE FROM ".$this->db->prefix()."saweeklyreport_weeklyreportservice";
		$sql .= " WHERE fk_weeklyreport = ".((int) $this->id);
		$sql .= " AND entity = ".((int) $this->entity);
		$resql = $this->db->query($sql);
		if (!$resql) {
			$this->error = $this->db->lasterror();
			return -1;
		}

		$position = 1;
		foreach ($services as $service) {
			$line = new WeeklyReportService($this->db);
			$line->entity = (int) $this->entity;
			$line->fk_weeklyreport = (int) $this->id;
			$line->source_element = (string) $service['source_element'];
			$line->source_id = (int) $service['source_id'];
			$line->service_type = (string) $service['service_type'];
			$line->ticket_category_code = (string) ($service['ticket_category_code'] ?? '');
			$line->ticket_severity_code = (string) ($service['ticket_severity_code'] ?? '');
			$line->label = (string) $service['label'];
			$line->description = (string) $service['description'];
			$line->status = (int) $service['status'];
			$line->position = $position++;
			$line->date_service = $this->getTimestampFromSqlDate((string) $service['date_service']);
			$result = $line->create($user, 1);
			if ($result < 0) {
				$this->setErrorsFromObject($line);
				return -1;
			}
		}

		return 1;
	}

	/**
	 * Add an existing ticket to the report without modifying the ticket.
	 *
	 * @param	int		$ticketid	Ticket ID
	 * @param	User	$user		User
	 * @return	int
	 */
	public function addTicketLine($ticketid, User $user)
	{
		if ($ticketid <= 0 || empty($this->id)) {
			$this->error = 'ErrorBadParameter';
			return -1;
		}
		if (!isModEnabled('ticket') || (empty($user->admin) && !$user->hasRight('ticket', 'read'))) {
			$this->error = 'ErrorForbidden';
			return -1;
		}

		$ticketdata = SAWeeklyReportTicketHelper::getTicketData($this->db, $ticketid);
		if (empty($ticketdata)) {
			$this->error = 'ErrorRecordNotFound';
			return -1;
		}

		$sql = "SELECT rowid FROM ".$this->db->prefix()."saweeklyreport_weeklyreportservice";
		$sql .= " WHERE fk_weeklyreport = ".((int) $this->id);
		$sql .= " AND entity = ".((int) $this->entity);
		$sql .= " AND source_element = 'ticket'";
		$sql .= " AND source_id = ".((int) $ticketid);
		$resql = $this->db->query($sql);
		if (!$resql) {
			$this->error = $this->db->lasterror();
			return -1;
		}
		if ($this->db->num_rows($resql) > 0) {
			return 1;
		}

		$line = new WeeklyReportService($this->db);
		$line->entity = (int) $this->entity;
		$line->fk_weeklyreport = (int) $this->id;
		$line->source_element = 'ticket';
		$line->source_id = (int) $ticketid;
		$line->service_type = (string) $ticketdata['type_code'];
		$line->ticket_category_code = (string) $ticketdata['category_code'];
		$line->ticket_severity_code = (string) $ticketdata['severity_code'];
		$line->label = (string) $ticketdata['label'];
		$line->description = (string) $ticketdata['description'];
		$line->status = (int) $ticketdata['status'];
		$line->position = count($this->lines) + 1;
		$line->date_service = $this->getTimestampFromSqlDate((string) $ticketdata['date_service']);

		$this->db->begin();
		$result = $line->create($user, 1);
		if ($result < 0) {
			$this->setErrorsFromObject($line);
			$this->db->rollback();
			return -1;
		}
		if (!$this->isNativeSourceLinked('ticket', $ticketid)) {
			$result = $this->add_object_linked('ticket', $ticketid, $user, 1);
			if ($result <= 0) {
				$this->error = $this->db->lasterror();
				$this->db->rollback();
				return -1;
			}
		}

		$oldcopy = clone $this;
		$this->oldcopy = $oldcopy;
		$this->context['trigger_reason'] = 'ticket_link';
		$this->context['linked_ticket_id'] = (int) $ticketid;
		$resulttrigger = $this->call_trigger($this->TRIGGER_PREFIX.'_UPDATE', $user);
		if ($resulttrigger < 0) {
			$this->db->rollback();
			return -1;
		}

		$this->db->commit();
		$this->fetch((int) $this->id);

		return 1;
	}

	/**
	 * Detach a service line from the report without modifying the source object.
	 *
	 * @param	int		$lineid	Line ID
	 * @param	User	$user	User
	 * @return	int
	 */
	public function detachServiceLine($lineid, User $user)
	{
		if ($lineid <= 0 || empty($this->id)) {
			$this->error = 'ErrorBadParameter';
			return -1;
		}

		$line = new WeeklyReportService($this->db);
		if ($line->fetch($lineid) <= 0 || (int) $line->fk_weeklyreport !== (int) $this->id) {
			$this->error = 'ErrorRecordNotFound';
			return -1;
		}

		$this->db->begin();
		$sourceelement = (string) $line->source_element;
		$sourceid = (int) $line->source_id;
		if ($line->delete($user, 1) < 0) {
			$this->setErrorsFromObject($line);
			$this->db->rollback();
			return -1;
		}
		if ($sourceelement !== '' && $sourceid > 0) {
			if ($this->deleteNativeSourceLink($sourceelement, $sourceid) < 0) {
				$this->db->rollback();
				return -1;
			}
		}

		$oldcopy = clone $this;
		$this->oldcopy = $oldcopy;
		$this->context['trigger_reason'] = 'service_line_detach';
		$this->context['detached_source_element'] = $sourceelement;
		$this->context['detached_source_id'] = $sourceid;
		$resulttrigger = $this->call_trigger($this->TRIGGER_PREFIX.'_UPDATE', $user);
		if ($resulttrigger < 0) {
			$this->db->rollback();
			return -1;
		}

		$this->db->commit();
		$this->fetch((int) $this->id);

		return 1;
	}

	/**
	 * Link native source objects to the report.
	 *
	 * @param	array<int,array<string,mixed>>	$services	Services
	 * @param	User							$user		User
	 * @return	int
	 */
	private function linkNativeSources($services, User $user)
	{
		foreach ($services as $service) {
			$sourceelement = (string) ($service['source_element'] ?? '');
			$sourceid = (int) ($service['source_id'] ?? 0);
			if ($sourceid <= 0 || !in_array($sourceelement, array('ticket', 'fichinter'), true)) {
				continue;
			}
			if ($this->isNativeSourceLinked($sourceelement, $sourceid)) {
				continue;
			}
			$result = $this->add_object_linked($sourceelement, $sourceid, $user, 1);
			if ($result <= 0) {
				$this->error = $this->db->lasterror();
				return -1;
			}
		}

		return 1;
	}

	/**
	 * Check if a native source is already linked to this report.
	 *
	 * @param	string	$sourceelement	Source element
	 * @param	int		$sourceid		Source id
	 * @return	bool
	 */
	private function isNativeSourceLinked($sourceelement, $sourceid)
	{
		$targettype = $this->getElementType();

		$sql = "SELECT rowid FROM ".$this->db->prefix()."element_element";
		$sql .= " WHERE (fk_source = ".((int) $sourceid)." AND sourcetype = '".$this->db->escape($sourceelement)."'";
		$sql .= " AND fk_target = ".((int) $this->id)." AND targettype = '".$this->db->escape($targettype)."')";
		$sql .= " OR (fk_source = ".((int) $this->id)." AND sourcetype = '".$this->db->escape($targettype)."'";
		$sql .= " AND fk_target = ".((int) $sourceid)." AND targettype = '".$this->db->escape($sourceelement)."')";

		$resql = $this->db->query($sql);

		return ($resql && $this->db->num_rows($resql) > 0);
	}

	/**
	 * Delete native object link for a detached source.
	 *
	 * @param	string	$sourceelement	Source element
	 * @param	int		$sourceid		Source id
	 * @return	int
	 */
	private function deleteNativeSourceLink($sourceelement, $sourceid)
	{
		$targettype = $this->getElementType();

		$sql = "DELETE FROM ".$this->db->prefix()."element_element";
		$sql .= " WHERE ((fk_source = ".((int) $sourceid)." AND sourcetype = '".$this->db->escape($sourceelement)."'";
		$sql .= " AND fk_target = ".((int) $this->id)." AND targettype = '".$this->db->escape($targettype)."')";
		$sql .= " OR (fk_source = ".((int) $this->id)." AND sourcetype = '".$this->db->escape($targettype)."'";
		$sql .= " AND fk_target = ".((int) $sourceid)." AND targettype = '".$this->db->escape($sourceelement)."'))";

		$resql = $this->db->query($sql);
		if (!$resql) {
			$this->error = $this->db->lasterror();
			return -1;
		}

		return 1;
	}

	/**
	 * Fetch kWc total from closed customer orders.
	 *
	 * @param	string	$start		SQL date inclusive
	 * @param	string	$endnext	SQL date exclusive
	 * @return	float|int
	 */
	private function fetchOrderPowerTotal($start, $endnext)
	{
		global $user;

		if (is_object($user) && !$user->hasRight('commande', 'lire')) {
			return 0.0;
		}
		if (!$this->hasRequiredOrderPowerColumns()) {
			return 0.0;
		}

		$sql = "SELECT SUM(COALESCE(ef.powerplantpv_peak_power, 0)) as total";
		$sql .= $this->getOrderPowerFromSql();
		$sql .= $this->getOrderPowerWhereSql();
		$sql .= " AND c.fk_statut >= ".$this->getDeliveredOrderStatus();
		$sql .= " AND c.date_cloture IS NOT NULL";
		$sql .= " AND c.date_cloture >= '".$this->db->escape($start)."'";
		$sql .= " AND c.date_cloture < '".$this->db->escape($endnext)."'";
		$sql .= " AND ef.powerplantpv_peak_power IS NOT NULL";

		$resql = $this->db->query($sql);
		if (!$resql) {
			$this->error = $this->db->lasterror();
			dol_syslog(__METHOD__.' '.$this->error, LOG_WARNING);
			return -1;
		}
		$obj = $this->db->fetch_object($resql);

		return (float) ($obj->total ?? 0);
	}

	/**
	 * Check required order columns.
	 *
	 * @return	bool
	 */
	private function hasRequiredOrderPowerColumns()
	{
		static $cache = null;

		if ($cache !== null) {
			return $cache;
		}

		$required = array(
			MAIN_DB_PREFIX.'commande' => array('rowid', 'entity', 'fk_statut', 'date_cloture'),
			MAIN_DB_PREFIX.'commande_extrafields' => array('fk_object', 'powerplantpv_peak_power'),
		);
		foreach ($required as $table => $columns) {
			foreach ($columns as $column) {
				$sql = "SHOW COLUMNS FROM ".$table." LIKE '".$this->db->escape($column)."'";
				$resql = $this->db->query($sql);
				if (!$resql || $this->db->num_rows($resql) <= 0) {
					$cache = false;
					return false;
				}
			}
		}

		$cache = true;

		return true;
	}

	/**
	 * Return delivered order status.
	 *
	 * @return	int
	 */
	private function getDeliveredOrderStatus()
	{
		dol_include_once('/commande/class/commande.class.php');
		if (defined('Commande::STATUS_CLOSED')) {
			return (int) constant('Commande::STATUS_CLOSED');
		}

		return 3;
	}

	/**
	 * Return order FROM clause with native thirdparty restriction.
	 *
	 * @return	string
	 */
	private function getOrderPowerFromSql()
	{
		global $user;

		$sql = " FROM ".$this->db->prefix()."commande as c";
		$sql .= " LEFT JOIN ".$this->db->prefix()."commande_extrafields as ef ON ef.fk_object = c.rowid";
		if (is_object($user) && empty($user->socid) && !$user->hasRight('societe', 'client', 'voir')) {
			$sql .= " INNER JOIN ".$this->db->prefix()."societe_commerciaux as sc ON c.fk_soc = sc.fk_soc AND sc.fk_user = ".((int) $user->id);
		}

		return $sql;
	}

	/**
	 * Return order WHERE clause.
	 *
	 * @return	string
	 */
	private function getOrderPowerWhereSql()
	{
		global $user;

		$sql = " WHERE c.entity IN (".getEntity('commande').")";
		if (is_object($user) && !empty($user->socid)) {
			$sql .= " AND c.fk_soc = ".((int) $user->socid);
		}

		return $sql;
	}

	/**
	 * Fetch native intervention/ticket services in period.
	 *
	 * @param	string	$start		SQL date inclusive
	 * @param	string	$endnext	SQL date exclusive
	 * @return	array<int,array<string,mixed>>
	 */
	private function fetchNativeServiceSources($start, $endnext)
	{
		global $user;

		$services = array();

		if (getDolGlobalInt('SAWEEKLYREPORT_PREFILL_FICHINTER', 1) && isModEnabled('intervention') && is_object($user) && (!empty($user->admin) || $user->hasRight('ficheinter', 'lire')) && $this->tableExists('fichinter')) {
			$sql = "SELECT rowid, ref, description, datei, fk_statut";
			$sql .= " FROM ".$this->db->prefix()."fichinter";
			$sql .= " WHERE entity IN (".getEntity('fichinter').")";
			$sql .= " AND datei >= '".$this->db->escape($start)."'";
			$sql .= " AND datei < '".$this->db->escape($endnext)."'";
			$sql .= " ORDER BY datei ASC, ref ASC";
			$resql = $this->db->query($sql);
			if ($resql) {
				while ($obj = $this->db->fetch_object($resql)) {
					$services[] = array(
						'source_element' => 'fichinter',
						'source_id' => (int) $obj->rowid,
						'service_type' => 'maintenance',
						'ticket_category_code' => '',
						'ticket_severity_code' => '',
						'label' => trim('Maintenance : '.$obj->ref),
						'description' => $this->textFromHtml((string) $obj->description),
						'status' => (int) $obj->fk_statut,
						'date_service' => (string) $obj->datei,
					);
				}
			}
		}

		if (getDolGlobalInt('SAWEEKLYREPORT_PREFILL_TICKET', 1) && isModEnabled('ticket') && is_object($user) && (!empty($user->admin) || $user->hasRight('ticket', 'read')) && $this->tableExists('ticket')) {
			$tickettypecodes = SAWeeklyReportTicketHelper::cleanTicketDictionaryCodes($this->db, getDolGlobalString('SAWEEKLYREPORT_TICKET_TYPE_CODES'), 'c_ticket_type');
			$sql = "SELECT rowid, ref, subject, message, datec, fk_statut, type_code, category_code, severity_code";
			$sql .= " FROM ".$this->db->prefix()."ticket";
			$sql .= " WHERE entity IN (".getEntity('ticket').")";
			$sql .= " AND datec >= '".$this->db->escape($start)."'";
			$sql .= " AND datec < '".$this->db->escape($endnext)."'";
			if (!empty($tickettypecodes)) {
				$quotedtypes = array();
				foreach ($tickettypecodes as $tickettypecode) {
					$quotedtypes[] = "'".$this->db->escape($tickettypecode)."'";
				}
				$sql .= " AND type_code IN (".implode(',', $quotedtypes).")";
			}
			$sql .= " ORDER BY datec ASC, ref ASC";
			$resql = $this->db->query($sql);
			if ($resql) {
				while ($obj = $this->db->fetch_object($resql)) {
					$subject = trim((string) $obj->subject);
					$services[] = array(
						'source_element' => 'ticket',
						'source_id' => (int) $obj->rowid,
						'service_type' => (string) $obj->type_code,
						'ticket_category_code' => (string) $obj->category_code,
						'ticket_severity_code' => (string) $obj->severity_code,
						'label' => trim('Dépannage : '.($subject !== '' ? $subject : $obj->ref)),
						'description' => $this->textFromHtml((string) $obj->message),
						'status' => (int) $obj->fk_statut,
						'date_service' => substr((string) $obj->datec, 0, 10),
					);
				}
			}
		}

		return $services;
	}

	/**
	 * Check table existence.
	 *
	 * @param	string	$table	Table without prefix
	 * @return	bool
	 */
	private function tableExists($table)
	{
		$sql = "SHOW TABLES LIKE '".$this->db->escape($this->db->prefix().$table)."'";
		$resql = $this->db->query($sql);

		return ($resql && $this->db->num_rows($resql) > 0);
	}

	/**
	 * Return shared document data for PPTX and PDF renderers.
	 *
	 * @param	Translate	$outputlangs	Output language
	 * @return	array<string,string>
	 */
	public function getDocumentData($outputlangs)
	{
		$period = 'Semaine '.sprintf('%02d', (int) $this->week).' - '.((int) $this->year);
		$service = $this->getServiceSummary();
		$techsummary = $this->formatNumber($this->technician_days, 1).' j/h - '.$this->formatNumber($this->technician_average, 1).' tech./jour';
		$peakpowerlabel = is_object($outputlangs) ? $outputlangs->transnoentitiesnoconv('WeeklyReportPeakPowerInstalled') : 'Puissance crête posée';
		if ($peakpowerlabel === 'WeeklyReportPeakPowerInstalled') {
			$peakpowerlabel = 'Puissance crête posée';
		}
		$previousweek = $peakpowerlabel.' : '.$this->formatPower($this->week_installed_power);
		$previouscomment = $this->cleanOutputText($this->previous_week_feedback);
		if ($previouscomment !== '') {
			$previousweek .= ' - '.$previouscomment;
		}

		return array(
			'report_title' => 'BILAN MENSUEL SOLEIL AQUITAIN',
			'report_period' => $period,
			'report_subtitle' => 'Réunion équipe techniciens - '.((int) $this->meeting_duration).' min',
			'report_tagline' => 'Production - Retours terrain - Objectifs',
			'week_title' => 'BILAN SEMAINE '.sprintf('%02d', (int) $this->week).' - '.((int) $this->year),
			'week_installed_power' => $this->formatPower($this->week_installed_power),
			'week_label' => 'S'.sprintf('%02d', (int) $this->week),
			'technician_summary' => $techsummary,
			'month_installed_power' => $this->formatPower($this->month_installed_power),
			'month_label' => $this->getMonthLabel($outputlangs),
			'month_delta' => '(+'.$this->formatPower($this->week_installed_power).' en S'.sprintf('%02d', (int) $this->week).')',
			'weekly_target_power' => $this->formatPower($this->weekly_target_power),
			'annual_installed_power' => $this->formatPower($this->annual_installed_power),
			'annual_completion_rate' => 'Taux de réalisation : '.$this->formatNumber($this->annual_completion_rate, 1).'%',
			'annual_progress' => $this->formatNumber($this->annual_installed_power, 0).' / '.$this->formatNumber($this->annual_target_power, 0).' kWc',
			'annual_average_power' => 'Cadence : '.$this->formatNumber($this->annual_average_power, 1).' kWc/sem.',
			'annual_average_detail' => '('.$this->formatNumber($this->annual_installed_power, 0).' kWc / '.$this->formatNumber($this->workweeks_elapsed, 1).' sem. ouvrées)',
			'previous_week_feedback' => $previousweek,
			'field_returns' => $this->cleanOutputText($this->field_returns),
			'current_week_goal' => $this->cleanOutputText($this->current_week_goal),
			'service_summary' => $service,
			'technician_detail' => $techsummary.' - '.$this->cleanOutputText($this->vehicle_loading_reminder),
			'safety_message' => $this->cleanOutputText($this->safety_message),
		);
	}

	/**
	 * Return service rows for document output.
	 *
	 * @param	Translate	$outputlangs	Output language
	 * @return	array<int,array<string,string>>
	 */
	public function getDocumentServiceRows($outputlangs)
	{
		global $langs;

		$langobj = is_object($outputlangs) ? $outputlangs : $langs;
		$rows = array();
		foreach ($this->lines as $line) {
			if ((string) $line->source_element === 'ticket') {
				$rows[] = SAWeeklyReportTicketHelper::getServiceLineDisplayData($this->db, $langobj, $line);
				continue;
			}

			$rows[] = array(
				'type' => (string) $line->service_type,
				'group' => '',
				'severity' => '',
				'label' => (string) $line->label,
				'description' => $this->cleanOutputText($line->description),
				'origin' => (string) $line->source_element.((int) $line->source_id > 0 ? ' #'.((int) $line->source_id) : ''),
			);
		}

		return $rows;
	}

	/**
	 * Generate PPTX document.
	 *
	 * @param	Translate	$outputlangs	Output language
	 * @return	int
	 */
	public function generatePptx($outputlangs)
	{
		global $user;

		if (!class_exists('ZipArchive')) {
			$this->error = 'ErrorZipArchiveUnavailable';
			return -1;
		}
		if (empty($this->id)) {
			$this->error = 'ErrorObjectMustBeFetched';
			return -1;
		}

		$this->fetchLines();

		$template = $this->getPptxTemplatePath();
		if ($template === '' || !is_readable($template)) {
			$this->error = 'ErrorPptxTemplateNotReadable';
			return -1;
		}

		$outputdir = $this->getDocumentDir();
		dol_mkdir($outputdir);
		$filename = $this->getDocumentBaseFilename().'.pptx';
		$outputfile = $outputdir.'/'.$filename;

		if (!dol_copy($template, $outputfile, 0, 1)) {
			$this->error = 'ErrorFailToCopyFile';
			return -1;
		}

		$replacements = $this->buildPptxReplacementMap($outputlangs);
		$missing = $this->findMissingPptxPlaceholders($outputfile, array_keys($replacements));
		if (!empty($missing)) {
			dol_delete_file($outputfile);
			$this->error = 'ErrorMissingPptxPlaceholders';
			$this->errors[] = implode(', ', $missing);
			return -1;
		}

		$zip = new ZipArchive();
		if ($zip->open($outputfile) !== true) {
			dol_delete_file($outputfile);
			$this->error = 'ErrorOpenPptx';
			return -1;
		}

		for ($i = 0; $i < $zip->numFiles; $i++) {
			$entry = $zip->getNameIndex($i);
			if (!preg_match('#^ppt/(slides|notesSlides)/.+\.xml$#', $entry)) {
				continue;
			}
			$xml = $zip->getFromName($entry);
			if ($xml === false) {
				continue;
			}
			foreach ($replacements as $placeholder => $value) {
				$xml = str_replace($placeholder, $this->xmlText($value), $xml);
			}
			$zip->addFromString($entry, $xml);
		}
		$zip->close();

		if ($this->recordGeneratedDocument($user, $filename, 'document_pptx_generation') < 0) {
			return -1;
		}

		return 1;
	}

	/**
	 * Generate TCPDF document.
	 *
	 * @param	Translate	$outputlangs	Output language
	 * @param	int			$hidedetails	Unused
	 * @param	int			$hidedesc		Unused
	 * @param	int			$hideref		Unused
	 * @return	int
	 */
	public function generatePdfTcpdf($outputlangs, $hidedetails = 0, $hidedesc = 0, $hideref = 0)
	{
		global $user;

		dol_include_once('/saweeklyreport/core/modules/saweeklyreport/doc/'.self::DOC_MODEL_PDF_TCPDF.'.modules.php');
		if (!class_exists(self::DOC_MODEL_PDF_TCPDF)) {
			$this->error = 'ErrorPdfModelNotReadable';
			return -1;
		}

		$modelclass = self::DOC_MODEL_PDF_TCPDF;
		$generator = new $modelclass($this->db);
		$result = $generator->write_file($this, $outputlangs, '', $hidedetails, $hidedesc, $hideref);
		if ($result <= 0) {
			$this->error = !empty($generator->error) ? $generator->error : 'ErrorFailedToGeneratePDF';
			$this->errors = array_merge((array) $this->errors, (array) $generator->errors);
			return -1;
		}

		$filename = $this->getDocumentBaseFilename().'.pdf';
		if (!empty($generator->result['fullpath'])) {
			$filename = basename((string) $generator->result['fullpath']);
		}

		if ($this->recordGeneratedDocument($user, $filename, 'document_pdf_generation') < 0) {
			return -1;
		}

		return 1;
	}

	/**
	 * Standard Dolibarr document generation wrapper.
	 *
	 * @param	string		$modele			Model
	 * @param	Translate	$outputlangs	Output language
	 * @param	int			$hidedetails	Unused
	 * @param	int			$hidedesc		Unused
	 * @param	int			$hideref		Unused
	 * @param	array|null	$moreparams		Unused
	 * @return	int
	 */
	public function generateDocument($modele, $outputlangs, $hidedetails = 0, $hidedesc = 0, $hideref = 0, $moreparams = null)
	{
		$model = (string) $modele;
		if ($model === '') {
			$model = !empty($this->model_pdf) ? (string) $this->model_pdf : getDolGlobalString('SAWEEKLYREPORT_WEEKLYREPORT_ADDON_PDF');
			if ($model === '') {
				$model = getDolGlobalString('SAWEEKLYREPORT_WEEKLYREPORT_ADDON_PPTX', 'weekly_report_standard');
			}
		}

		if ($model !== '') {
			$this->model_pptx = $model;
			$this->model_pdf = $model;
		}

		if ($model === self::DOC_MODEL_PDF_TCPDF || (string) $this->model_pdf === self::DOC_MODEL_PDF_TCPDF) {
			return $this->generatePdfTcpdf($outputlangs, $hidedetails, $hidedesc, $hideref);
		}

		return $this->generatePptx($outputlangs);
	}

	/**
	 * Save last document model selected from native document block.
	 *
	 * @param	User	$user		User
	 * @param	string	$modelpdf	Model key
	 * @return	int
	 */
	public function setDocModel($user, $modelpdf)
	{
		$newmodel = dol_trunc($modelpdf, 255);

		$sql = "UPDATE ".$this->db->prefix().$this->table_element;
		$sql .= " SET model_pptx = '".$this->db->escape($newmodel)."'";
		$sql .= " WHERE rowid = ".((int) $this->id);
		$sql .= " AND entity = ".((int) $this->entity);

		$resql = $this->db->query($sql);
		if ($resql) {
			$this->model_pptx = $newmodel;
			$this->model_pdf = $newmodel;
			return 1;
		}

		$this->error = $this->db->lasterror();
		return -1;
	}

	/**
	 * Build replacement map.
	 *
	 * @param	Translate	$outputlangs	Output language
	 * @return	array<string,string>
	 */
	private function buildPptxReplacementMap($outputlangs)
	{
		$data = $this->getDocumentData($outputlangs);

		return array(
			'{{REPORT_TITLE}}' => $data['report_title'],
			'{{REPORT_PERIOD}}' => $data['report_period'],
			'{{REPORT_SUBTITLE}}' => $data['report_subtitle'],
			'{{REPORT_TAGLINE}}' => $data['report_tagline'],
			'{{WEEK_TITLE}}' => $data['week_title'],
			'{{WEEK_INSTALLED_POWER}}' => $data['week_installed_power'],
			'{{WEEK_LABEL}}' => $data['week_label'],
			'{{TECHNICIAN_SUMMARY}}' => $data['technician_summary'],
			'{{MONTH_INSTALLED_POWER}}' => $data['month_installed_power'],
			'{{MONTH_LABEL}}' => $data['month_label'],
			'{{MONTH_DELTA}}' => $data['month_delta'],
			'{{WEEKLY_TARGET_POWER}}' => $data['weekly_target_power'],
			'{{ANNUAL_INSTALLED_POWER}}' => $data['annual_installed_power'],
			'{{ANNUAL_COMPLETION_RATE}}' => $data['annual_completion_rate'],
			'{{ANNUAL_PROGRESS}}' => $data['annual_progress'],
			'{{ANNUAL_AVERAGE_POWER}}' => $data['annual_average_power'],
			'{{ANNUAL_AVERAGE_DETAIL}}' => $data['annual_average_detail'],
			'{{PREVIOUS_WEEK_FEEDBACK}}' => $data['previous_week_feedback'],
			'{{FIELD_RETURNS}}' => $data['field_returns'],
			'{{CURRENT_WEEK_GOAL}}' => $data['current_week_goal'],
			'{{SERVICE_SUMMARY}}' => $data['service_summary'],
			'{{TECHNICIAN_DETAIL}}' => $data['technician_detail'],
			'{{SAFETY_MESSAGE}}' => $data['safety_message'],
		);
	}

	/**
	 * Find missing placeholders in PPTX.
	 *
	 * @param	string		$file			PPTX file
	 * @param	string[]	$placeholders	Placeholders
	 * @return	string[]
	 */
	private function findMissingPptxPlaceholders($file, $placeholders)
	{
		$found = array();
		$zip = new ZipArchive();
		if ($zip->open($file) !== true) {
			return $placeholders;
		}
		for ($i = 0; $i < $zip->numFiles; $i++) {
			$entry = $zip->getNameIndex($i);
			if (!preg_match('#^ppt/(slides|notesSlides)/.+\.xml$#', $entry)) {
				continue;
			}
			$xml = $zip->getFromName($entry);
			if ($xml === false) {
				continue;
			}
			foreach ($placeholders as $placeholder) {
				if (strpos($xml, $placeholder) !== false) {
					$found[$placeholder] = true;
				}
			}
		}
		$zip->close();

		$missing = array();
		foreach ($placeholders as $placeholder) {
			if (empty($found[$placeholder])) {
				$missing[] = $placeholder;
			}
		}

		return $missing;
	}

	/**
	 * Return service summary for PPTX.
	 *
	 * @return	string
	 */
	private function getServiceSummary()
	{
		global $langs;

		if (empty($this->lines)) {
			return 'Aucune intervention SAV ou maintenance identifiée sur la période';
		}

		$items = array();
		foreach ($this->lines as $line) {
			if ((string) $line->source_element === 'ticket') {
				$displaydata = SAWeeklyReportTicketHelper::getServiceLineDisplayData($this->db, $langs, $line);
				$items[] = $this->cleanOutputText((string) $displaydata['label']);
			} else {
				$items[] = $this->cleanOutputText($line->label);
			}
		}

		return implode(' • ', array_slice($items, 0, 8));
	}

	/**
	 * Return template path.
	 *
	 * @return	string
	 */
	private function getPptxTemplatePath()
	{
		$model = $this->getPptxTemplateModel();
		$model = preg_replace('/[^a-zA-Z0-9_\-]/', '', (string) $model);

		$candidates = array(
			dol_buildpath('/saweeklyreport/doctemplates/saweeklyreport/'.$model.'.pptx', 0),
			DOL_DATA_ROOT.'/doctemplates/saweeklyreport/'.$model.'.pptx',
		);
		foreach ($candidates as $candidate) {
			if (is_readable($candidate)) {
				return $candidate;
			}
		}

		return '';
	}

	/**
	 * Return PPTX template model, ignoring non-PPTX document models.
	 *
	 * @return	string
	 */
	private function getPptxTemplateModel()
	{
		$model = !empty($this->model_pptx) ? (string) $this->model_pptx : getDolGlobalString('SAWEEKLYREPORT_WEEKLYREPORT_ADDON_PPTX', 'weekly_report_standard');
		if ($model === self::DOC_MODEL_PDF_TCPDF || strpos($model, 'pdf_') === 0) {
			$model = getDolGlobalString('SAWEEKLYREPORT_WEEKLYREPORT_ADDON_PPTX', 'weekly_report_standard');
		}

		return $model;
	}

	/**
	 * Return sanitized document base filename.
	 *
	 * @return	string
	 */
	public function getDocumentBaseFilename()
	{
		return dol_sanitizeFileName($this->ref);
	}

	/**
	 * Return document path relative to module output root.
	 *
	 * @return	string
	 */
	public function getDocumentRelativeDir()
	{
		return 'weeklyreport/'.$this->getDocumentBaseFilename();
	}

	/**
	 * Return legacy document path relative to module output root.
	 *
	 * @return	string
	 */
	public function getLegacyDocumentRelativeDir()
	{
		return ((int) $this->entity).'/'.$this->getDocumentRelativeDir();
	}

	/**
	 * Return legacy document base directory.
	 *
	 * @return	string
	 */
	public function getLegacyDocumentBaseDir()
	{
		global $conf;

		return rtrim((string) $conf->saweeklyreport->dir_output, '/');
	}

	/**
	 * Return document base directory for the report owner entity.
	 *
	 * @return	string
	 */
	public function getDocumentBaseDir()
	{
		global $conf;

		$base = '';
		if (function_exists('getMultidirOutput')) {
			$base = (string) getMultidirOutput($this, 'saweeklyreport', 0);
		}
		if ($base === '' || strpos($base, 'error-') === 0) {
			$entity = !empty($this->entity) ? (int) $this->entity : (int) $conf->entity;
			if (!empty($conf->saweeklyreport->multidir_output[$entity])) {
				$base = $conf->saweeklyreport->multidir_output[$entity];
			} else {
				$base = $conf->saweeklyreport->dir_output;
			}
		}

		return rtrim($base, '/');
	}

	/**
	 * Return document directory.
	 *
	 * @return	string
	 */
	public function getDocumentDir()
	{
		return $this->getDocumentBaseDir().'/'.$this->getDocumentRelativeDir();
	}

	/**
	 * Return legacy document directory.
	 *
	 * @return	string
	 */
	public function getLegacyDocumentDir()
	{
		return $this->getLegacyDocumentBaseDir().'/'.$this->getLegacyDocumentRelativeDir();
	}

	/**
	 * Update last document and trigger a CRUD update.
	 *
	 * @param	User	$user		User
	 * @param	string	$filename	File name
	 * @param	string	$reason		Stable trigger reason
	 * @return	int
	 */
	private function recordGeneratedDocument($user, $filename, $reason)
	{
		$oldcopy = clone $this;
		$this->oldcopy = $oldcopy;
		$this->last_main_doc = $this->getDocumentRelativeDir().'/'.$filename;

		$this->db->begin();
		if ($this->update($user, 1) < 0) {
			$this->db->rollback();
			return -1;
		}

		$this->context['trigger_reason'] = $reason;
		$this->context['changed_fields'] = array('last_main_doc');
		$this->context['old_last_main_doc'] = (string) $oldcopy->last_main_doc;
		$this->context['new_last_main_doc'] = (string) $this->last_main_doc;

		$resulttrigger = $this->call_trigger($this->TRIGGER_PREFIX.'_UPDATE', $user);
		if ($resulttrigger < 0) {
			$this->db->rollback();
			return -1;
		}

		$this->db->commit();

		return 1;
	}

	/**
	 * Return object link.
	 *
	 * @param	int		$withpicto	Include picto
	 * @param	string	$option		Option
	 * @param	int		$notooltip	No tooltip
	 * @param	string	$morecss	Extra CSS
	 * @param	int		$save_lastsearch_value	Save search
	 * @return	string
	 */
	public function getNomUrl($withpicto = 0, $option = '', $notooltip = 0, $morecss = '', $save_lastsearch_value = -1)
	{
		$result = '';
		$url = dol_buildpath('/saweeklyreport/weeklyreport_card.php', 1).'?id='.((int) $this->id);
		if ($option !== 'nolink') {
			$result .= '<a href="'.$url.'"'.($morecss ? ' class="'.$morecss.'"' : '').'>';
		}
		if ($withpicto) {
			$result .= img_object('', $this->picto, (($withpicto != 2) ? 'class="paddingright"' : ''));
		}
		if ($withpicto != 2) {
			$result .= dol_escape_htmltag($this->ref);
		}
		if ($option !== 'nolink') {
			$result .= '</a>';
		}

		return $result;
	}

	/**
	 * Return status label.
	 *
	 * @param	int	$mode	Mode
	 * @return	string
	 */
	public function getLibStatut($mode = 0)
	{
		return $this->LibStatut($this->status, $mode);
	}

	/**
	 * Return status label.
	 *
	 * @param	int	$status	Status
	 * @param	int	$mode	Mode
	 * @return	string
	 */
	public function LibStatut($status, $mode = 0)
	{
		global $langs;

		$labels = array(
			self::STATUS_DRAFT => $langs->transnoentitiesnoconv('Draft'),
			self::STATUS_VALIDATED => $langs->transnoentitiesnoconv('Validated'),
			self::STATUS_CANCELED => $langs->transnoentitiesnoconv('Canceled'),
		);
		$short = $labels;
		$type = 'status0';
		if ((int) $status === self::STATUS_VALIDATED) {
			$type = 'status4';
		} elseif ((int) $status === self::STATUS_CANCELED) {
			$type = 'status9';
		}

		return dolGetStatus($labels[(int) $status] ?? '', $short[(int) $status] ?? '', '', $type, $mode);
	}

	/**
	 * Return next number.
	 *
	 * @return	string
	 */
	public function getNextNumRef()
	{
		global $conf, $langs;

		$langs->load('saweeklyreport@saweeklyreport');
		$module = getDolGlobalString('SAWEEKLYREPORT_WEEKLYREPORT_ADDON', 'mod_weeklyreport_standard');
		$file = $module.'.php';
		$loaded = false;
		$dirmodels = array_merge(array('/'), (array) $conf->modules_parts['models']);
		foreach ($dirmodels as $reldir) {
			$path = dol_buildpath($reldir.'core/modules/saweeklyreport/'.$file, 0);
			if (is_readable($path)) {
				require_once $path;
				$loaded = true;
				break;
			}
		}
		if (!$loaded || !class_exists($module)) {
			$this->error = $langs->trans('ErrorNumberingModuleNotSetup', $this->element);
			return '';
		}

		$obj = new $module();
		$numref = $obj->getNextValue($this);
		if ($numref != '' && $numref != '-1') {
			return $numref;
		}
		$this->error = $obj->error;

		return '';
	}

	/**
	 * Assign final reference.
	 *
	 * @param	User	$user	User
	 * @return	int
	 */
	private function assignFinalReference(User $user)
	{
		global $langs;

		$maxtries = 5;
		for ($attempt = 0; $attempt < $maxtries; $attempt++) {
			$nextref = $this->getNextNumRef();
			if ($nextref === '') {
				return -1;
			}
			$used = $this->isReferenceUsedInSharedEntities($nextref);
			if ($used < 0) {
				return -1;
			}
			if ($used > 0) {
				$nextref .= '-'.($attempt + 2);
			}
			$sql = "UPDATE ".$this->db->prefix().$this->table_element;
			$sql .= " SET ref = '".$this->db->escape($nextref)."'";
			$sql .= " WHERE rowid = ".((int) $this->id);
			$resql = $this->db->query($sql);
			if ($resql) {
				$this->ref = $nextref;
				return 0;
			}
			$this->error = $this->db->lasterror();
			if (!preg_match('/duplicate|duplicata|unique/i', $this->error)) {
				return -1;
			}
		}

		$this->error = $langs->trans('ErrorRefAlreadyExists');

		return -1;
	}

	/**
	 * Check reference in shared entities.
	 *
	 * @param	string	$ref	Reference
	 * @return	int
	 */
	private function isReferenceUsedInSharedEntities($ref)
	{
		$sql = "SELECT rowid FROM ".$this->db->prefix().$this->table_element;
		$sql .= " WHERE ref = '".$this->db->escape($ref)."'";
		$sql .= " AND entity IN (".$this->getReferenceEntityList().")";
		if (!empty($this->id)) {
			$sql .= " AND rowid <> ".((int) $this->id);
		}
		$resql = $this->db->query($sql);
		if (!$resql) {
			$this->error = $this->db->lasterror();
			return -1;
		}

		return ($this->db->num_rows($resql) > 0 ? 1 : 0);
	}

	/**
	 * Return reference entity list.
	 *
	 * @return	string
	 */
	private function getReferenceEntityList()
	{
		if (!class_exists('ModeleNumRefWeeklyReport')) {
			dol_include_once('/saweeklyreport/core/modules/saweeklyreport/modules_weeklyreport.php');
		}
		if (class_exists('ModeleNumRefWeeklyReport')) {
			return ModeleNumRefWeeklyReport::getWeeklyReportReferenceEntityList($this);
		}

		return getEntity($this->element);
	}

	/**
	 * Return ISO week bounds as timestamps.
	 *
	 * @param	int	$year	Year
	 * @param	int	$week	Week
	 * @return	array<string,int>
	 */
	private function getIsoWeekBounds($year, $week)
	{
		$start = new DateTime();
		$start->setISODate($year, $week, 1);
		$start->setTime(0, 0, 0);
		$end = clone $start;
		$end->modify('+6 days');

		return array('start' => $start->getTimestamp(), 'end' => $end->getTimestamp());
	}

	/**
	 * Return month bounds for the ISO week Thursday.
	 *
	 * @param	int	$year	Year
	 * @param	int	$week	Week
	 * @return	array<string,int>
	 */
	private function getIsoWeekMonthBounds($year, $week)
	{
		$thursday = new DateTime();
		$thursday->setISODate($year, $week, 4);
		$start = new DateTime($thursday->format('Y-m-01'));
		$end = clone $start;
		$end->modify('last day of this month');

		return array('start' => $start->getTimestamp(), 'end' => $end->getTimestamp());
	}

	/**
	 * Count elapsed workweeks from year start to period end.
	 *
	 * @return	float
	 */
	private function countWorkweeksElapsed()
	{
		$start = new DateTime(((int) $this->year).'-01-01');
		$end = new DateTime($this->getSqlDate($this->period_end));
		$workdays = 0;
		while ($start <= $end) {
			$day = (int) $start->format('N');
			if ($day <= 5) {
				$workdays++;
			}
			$start->modify('+1 day');
		}

		return round($workdays / 5, 1);
	}

	/**
	 * Return SQL date from value.
	 *
	 * @param	int|string	$value	Date value
	 * @return	string
	 */
	private function getSqlDate($value)
	{
		if (is_numeric($value)) {
			return dol_print_date((int) $value, '%Y-%m-%d');
		}
		if (preg_match('/^(\d{4}-\d{2}-\d{2})/', (string) $value, $matches)) {
			return $matches[1];
		}

		return dol_print_date(dol_now(), '%Y-%m-%d');
	}

	/**
	 * Return next SQL date.
	 *
	 * @param	int|string	$value	Date value
	 * @return	string
	 */
	private function getNextSqlDate($value)
	{
		$date = new DateTime($this->getSqlDate($value));
		$date->modify('+1 day');

		return $date->format('Y-m-d');
	}

	/**
	 * Return timestamp from SQL date value.
	 *
	 * @param	string	$value	SQL date value
	 * @return	int
	 */
	private function getTimestampFromSqlDate($value)
	{
		if (preg_match('/^(\d{4}-\d{2}-\d{2})/', $value, $matches)) {
			return (int) strtotime($matches[1].' 00:00:00');
		}

		return 0;
	}

	/**
	 * Format power with kWc suffix.
	 *
	 * @param	float|int|string	$value	Value
	 * @return	string
	 */
	private function formatPower($value)
	{
		return $this->formatNumber($value, 1).' kWc';
	}

	/**
	 * Format number for report.
	 *
	 * @param	float|int|string	$value		Number
	 * @param	int					$decimals	Decimals
	 * @return	string
	 */
	private function formatNumber($value, $decimals = 1)
	{
		return number_format((float) $value, $decimals, ',', ' ');
	}

	/**
	 * Return month label.
	 *
	 * @param	Translate	$outputlangs	Language
	 * @return	string
	 */
	private function getMonthLabel($outputlangs)
	{
		$timestamp = is_numeric($this->month_start) ? (int) $this->month_start : strtotime($this->getSqlDate($this->month_start));

		return dol_print_date($timestamp, '%B');
	}

	/**
	 * Strip HTML and normalize whitespace.
	 *
	 * @param	string	$text	Text
	 * @return	string
	 */
	private function cleanOutputText($text)
	{
		$text = $this->textFromHtml((string) $text);
		$text = preg_replace('/\s+/', ' ', $text);

		return trim((string) $text);
	}

	/**
	 * Convert HTML to text.
	 *
	 * @param	string	$html	HTML
	 * @return	string
	 */
	private function textFromHtml($html)
	{
		$html = str_replace(array('<br>', '<br/>', '<br />', '</p>'), "\n", $html);
		$text = html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8');

		return trim($text);
	}

	/**
	 * Escape replacement as XML text.
	 *
	 * @param	string	$text	Text
	 * @return	string
	 */
	private function xmlText($text)
	{
		return htmlspecialchars((string) $text, ENT_XML1 | ENT_COMPAT, 'UTF-8');
	}
}
