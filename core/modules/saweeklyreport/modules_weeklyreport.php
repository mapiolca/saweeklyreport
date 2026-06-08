<?php
/* Copyright (C) 2026  Pierre Ardoin <developpeur@lesmetiersdubatiment.fr> */

require_once DOL_DOCUMENT_ROOT.'/core/class/commonnumrefgenerator.class.php';

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
