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
		$usednativehelper = false;

		include_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
		if (function_exists('getListOfModels')) {
			$tmp = getListOfModels($db, 'weeklyreport', $maxfilenamelength);
			if (is_array($tmp)) {
				$list = $tmp;
				unset($list[0]);
				$usednativehelper = true;
			}
		}
		if ($usednativehelper) {
			$sql = "SELECT nom";
			$sql .= " FROM ".MAIN_DB_PREFIX."document_model";
			$sql .= " WHERE type = 'weeklyreport'";
			$sql .= " AND entity IN (0,".((int) $conf->entity).")";
			$resql = $db->query($sql);
			if ($resql) {
				while (is_object($obj = $db->fetch_object($resql))) {
					$name = (string) $obj->nom;
					if (!isset($list[$name]) && ($name === 'weekly_report_standard' || preg_match('/^pdf_[A-Za-z0-9_]+$/', $name))) {
						$list[$name] = $name;
					}
				}
				$db->free($resql);
			}
		}

		if (!$usednativehelper) {
			$pptxmodel = getDolGlobalString('SAWEEKLYREPORT_WEEKLYREPORT_ADDON_PPTX', 'weekly_report_standard');
			if ($pptxmodel !== '') {
				$list[$pptxmodel] = $pptxmodel;
			}
			$pdfmodel = getDolGlobalString('SAWEEKLYREPORT_WEEKLYREPORT_ADDON_PDF', 'pdf_weeklyreport_powerpoint');
			if ($pdfmodel !== '') {
				$list[$pdfmodel] = $pdfmodel;
			}
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
