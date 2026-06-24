<?php
/* Copyright (C) 2026  Pierre Ardoin <developpeur@lesmetiersdubatiment.fr> */

require_once DOL_DOCUMENT_ROOT.'/core/class/commonnumrefgenerator.class.php';

/**
 * Parent class for WeeklyReport document models.
 */
abstract class ModelePDFWeeklyReport
{
	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 * Return list of active generation modules.
	 *
	 * @param	DoliDB	$db					Database handler
	 * @param	int		$maxfilenamelength	Max length of value to show
	 * @return	array<string,string>
	 */
	public static function liste_modeles($db, $maxfilenamelength = 0)
	{
		global $conf, $langs;

		// phpcs:enable
		$list = array();

		$sql = "SELECT nom, libelle";
		$sql .= " FROM ".MAIN_DB_PREFIX."document_model";
		$sql .= " WHERE type = 'weeklyreport'";
		$sql .= " AND entity = ".((int) $conf->entity);
		$sql .= " ORDER BY nom ASC";
		$resql = $db->query($sql);
		if ($resql) {
			while (is_object($obj = $db->fetch_object($resql))) {
				$name = (string) $obj->nom;
				if ($name === '' || ($name !== 'weekly_report_standard' && !preg_match('/^pdf_[A-Za-z0-9_]+$/', $name))) {
					continue;
				}
				$label = !empty($obj->libelle) ? (string) $obj->libelle : $name;
				$list[$name] = ($maxfilenamelength > 0 ? dol_trunc($label, $maxfilenamelength, 'middle') : $label);
			}
			$db->free($resql);
		}
		if (is_object($langs)) {
			$langs->load('saweeklyreport@saweeklyreport');
			if (isset($list['weekly_report_standard'])) {
				$list['weekly_report_standard'] = $langs->trans('WeeklyReportPptxStandardModel');
			}
			if (isset($list['pdf_weeklyreport_powerpoint'])) {
				$list['pdf_weeklyreport_powerpoint'] = $langs->trans('WeeklyReportPdfTcpdfModel');
			}
		}

		return $list;
	}

	/**
	 * Check if a document model is active for the current entity.
	 *
	 * @param	DoliDB	$db		Database handler
	 * @param	string	$model	Model key
	 * @return	bool
	 */
	public static function isModelActive($db, $model)
	{
		global $conf;

		$model = (string) $model;
		if ($model === '') {
			return false;
		}

		$sql = "SELECT rowid";
		$sql .= " FROM ".MAIN_DB_PREFIX."document_model";
		$sql .= " WHERE type = 'weeklyreport'";
		$sql .= " AND entity = ".((int) $conf->entity);
		$sql .= " AND nom = '".$db->escape($model)."'";

		$resql = $db->query($sql);
		if (!$resql) {
			return false;
		}
		$active = ($db->num_rows($resql) > 0);
		$db->free($resql);

		return $active;
	}
}

/**
 * Parent class for WeeklyReport numbering modules.
 */
abstract class ModeleNumRefWeeklyReport extends CommonNumRefGenerator
{
	/**
	 * Return reference uniqueness entity list.
	 *
	 * @param	CommonObject|null	$object	Current object
	 * @return	string
	 */
	public static function getWeeklyReportReferenceEntityList($object = null)
	{
		global $conf;

		$entities = array();
		$scopes = array(
			getEntity('weeklyreport'),
			getEntity('weeklyreportnumber', 1, $object),
		);

		foreach ($scopes as $scope) {
			foreach (explode(',', (string) $scope) as $entity) {
				$entity = trim($entity);
				if ($entity !== '' && preg_match('/^\d+$/', $entity)) {
					$entities[(int) $entity] = (int) $entity;
				}
			}
		}

		if (empty($entities)) {
			$entities[(int) $conf->entity] = (int) $conf->entity;
		}

		ksort($entities, SORT_NUMERIC);

		return implode(',', $entities);
	}

	/**
	 * Return an example.
	 *
	 * @return	string
	 */
	abstract public function getExample();

	/**
	 * Return next value.
	 *
	 * @param	WeeklyReport	$object	Report
	 * @return	string|int
	 */
	abstract public function getNextValue($object);
}
