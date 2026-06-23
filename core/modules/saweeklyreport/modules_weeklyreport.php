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
		global $langs;

		// phpcs:enable
		$list = array();

		include_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
		if (function_exists('getListOfModels')) {
			$tmp = getListOfModels($db, 'weeklyreport', $maxfilenamelength);
			if (is_array($tmp)) {
				$list = $tmp;
			}
		}

		if (empty($list)) {
			$model = getDolGlobalString('SAWEEKLYREPORT_WEEKLYREPORT_ADDON_PPTX', 'weekly_report_standard');
			$list[$model] = $model;
		}
		if (is_object($langs)) {
			$langs->load('saweeklyreport@saweeklyreport');
			$list['weekly_report_standard'] = $langs->trans('WeeklyReportPptxStandardModel');
			$list['pdf_weeklyreport_powerpoint'] = $langs->trans('WeeklyReportPdfTcpdfModel');
		} else {
			$list['weekly_report_standard'] = 'weekly_report_standard';
			$list['pdf_weeklyreport_powerpoint'] = 'pdf_weeklyreport_powerpoint';
		}

		return $list;
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
