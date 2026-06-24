<?php
/* Copyright (C) 2026  Pierre Ardoin <developpeur@lesmetiersdubatiment.fr> */

dol_include_once('/saweeklyreport/core/modules/saweeklyreport/modules_weeklyreport.php');

/**
 * Advanced weekly report numbering.
 */
class mod_weeklyreport_advanced extends ModeleNumRefWeeklyReport
{
	public $version = 'dolibarr';
	public $prefix = 'SAWR';
	public $error = '';
	public $name = 'advanced';

	/**
	 * Return description and mask form.
	 *
	 * @param	Translate	$langs	Language
	 * @return	string
	 */
	public function info($langs)
	{
		global $db;

		if (!class_exists('Form')) {
			require_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';
		}

		$langs->load('bills');
		$form = new Form($db);

		$text = $langs->trans('GenericNumRefModelDesc')."<br>\n";
		$text .= '<form action="'.dol_escape_htmltag($_SERVER['PHP_SELF']).'" method="POST">';
		$text .= '<input type="hidden" name="token" value="'.newToken().'">';
		$text .= '<input type="hidden" name="action" value="updateMask">';
		$text .= '<input type="hidden" name="maskconstWeeklyReport" value="SAWEEKLYREPORT_WEEKLYREPORT_ADVANCED_MASK">';
		$text .= '<input type="hidden" name="page_y" value="">';

		$text .= '<table class="nobordernopadding centpercent">';

		$tooltip = $langs->trans('GenericMaskCodes', $langs->transnoentities('WeeklyReport'), $langs->transnoentities('WeeklyReport'));
		$tooltip .= $langs->trans('GenericMaskCodes1');
		$tooltip .= '<br>';
		$tooltip .= $langs->trans('GenericMaskCodes2');
		$tooltip .= '<br>';
		$tooltip .= $langs->trans('GenericMaskCodes3');
		$tooltip .= $langs->trans('GenericMaskCodes4a', $langs->transnoentities('WeeklyReport'), $langs->transnoentities('WeeklyReport'));
		$tooltip .= $langs->trans('GenericMaskCodes5');
		$tooltip .= '<br>'.$langs->trans('SAWeeklyReportAdvancedMaskWeekHelp');

		$mask = getDolGlobalString('SAWEEKLYREPORT_WEEKLYREPORT_ADVANCED_MASK', $this->prefix.'-{yyyy}-S{ww}-{000}');
		$text .= '<tr><td>'.$langs->trans('Mask').':</td>';
		$text .= '<td class="right">'.$form->textwithpicto('<input type="text" class="flat minwidth175" name="maskWeeklyReport" value="'.dol_escape_htmltag($mask).'">', $tooltip, 1, 'help', 'valignmiddle', 0, 3, $this->name).'</td>';
		$text .= '<td class="left" rowspan="2">&nbsp; <input type="submit" class="button button-edit reposition smallpaddingimp" name="Button" value="'.$langs->trans('Save').'"></td>';
		$text .= '</tr>';

		$text .= '</table>';
		$text .= '</form>';

		return $text;
	}

	/**
	 * Return an example.
	 *
	 * @return	string
	 */
	public function getExample()
	{
		global $db, $conf;

		$object = new WeeklyReport($db);
		$object->year = 2026;
		$object->week = 23;
		$object->entity = (int) $conf->entity;

		$numexample = $this->getNextValue($object);
		if (!$numexample) {
			return $this->error !== '' ? $this->error : 'NotConfigured';
		}

		return (string) $numexample;
	}

	/**
	 * Check activation.
	 *
	 * @param	CommonObject	$object	Object
	 * @return	bool
	 */
	public function canBeActivated($object)
	{
		return true;
	}

	/**
	 * Return next reference.
	 *
	 * @param	WeeklyReport	$object	Report
	 * @return	string|int
	 */
	public function getNextValue($object)
	{
		global $db;

		require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';

		$mask = getDolGlobalString('SAWEEKLYREPORT_WEEKLYREPORT_ADVANCED_MASK', $this->prefix.'-{yyyy}-S{ww}-{000}');
		if ($mask === '') {
			$this->error = 'NotConfigured';
			return 0;
		}

		$now = dol_now();
		$year = !empty($object->year) ? (int) $object->year : (int) date('o', $now);
		$week = !empty($object->week) ? (int) $object->week : (int) date('W', $now);
		$mask = $this->replaceWeekTokens($mask, $week);
		$date = $this->getIsoWeekTimestamp($year, $week);
		$entities = self::getWeeklyReportReferenceEntityList($object);

		$numfinal = get_next_value($db, $mask, 'saweeklyreport_weeklyreport', 'ref', '', '', $date, 'next', false, null, $entities);
		if (is_string($numfinal) && preg_match('/^Error/', $numfinal)) {
			$this->error = $numfinal;
			return 0;
		}

		return $numfinal;
	}

	/**
	 * Replace weekly report specific tokens before native mask processing.
	 *
	 * @param	string	$mask	Mask
	 * @param	int		$week	ISO week
	 * @return	string
	 */
	private function replaceWeekTokens($mask, $week)
	{
		return strtr($mask, array(
			'{ww}' => sprintf('%02d', $week),
			'{WW}' => sprintf('%02d', $week),
			'{w}' => (string) $week,
			'{W}' => (string) $week,
		));
	}

	/**
	 * Return a timestamp for an ISO year/week pair.
	 *
	 * @param	int	$year	ISO year
	 * @param	int	$week	ISO week
	 * @return	int
	 */
	private function getIsoWeekTimestamp($year, $week)
	{
		try {
			$datetime = new DateTime();
			$datetime->setISODate($year, max(1, min(53, $week)), 1);

			return $datetime->getTimestamp();
		} catch (Exception $e) {
			return dol_now();
		}
	}
}
