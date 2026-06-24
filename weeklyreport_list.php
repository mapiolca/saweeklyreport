<?php
/* Copyright (C) 2026  Pierre Ardoin <developpeur@lesmetiersdubatiment.fr> */

$res = 0;
if (!$res && !empty($_SERVER['CONTEXT_DOCUMENT_ROOT'])) {
	$res = @include $_SERVER['CONTEXT_DOCUMENT_ROOT'].'/main.inc.php';
}
$tmp = empty($_SERVER['SCRIPT_FILENAME']) ? '' : $_SERVER['SCRIPT_FILENAME'];
$tmp2 = realpath(__FILE__);
$i = strlen($tmp) - 1;
$j = strlen($tmp2) - 1;
while ($i > 0 && $j > 0 && isset($tmp[$i]) && isset($tmp2[$j]) && $tmp[$i] == $tmp2[$j]) {
	$i--;
	$j--;
}
if (!$res && $i > 0 && file_exists(substr($tmp, 0, ($i + 1)).'/main.inc.php')) {
	$res = @include substr($tmp, 0, ($i + 1)).'/main.inc.php';
}
if (!$res && $i > 0 && file_exists(dirname(substr($tmp, 0, ($i + 1))).'/main.inc.php')) {
	$res = @include dirname(substr($tmp, 0, ($i + 1))).'/main.inc.php';
}
if (!$res && file_exists('../main.inc.php')) {
	$res = @include '../main.inc.php';
}
if (!$res && file_exists('../../main.inc.php')) {
	$res = @include '../../main.inc.php';
}
if (!$res && file_exists('../../../main.inc.php')) {
	$res = @include '../../../main.inc.php';
}
if (!$res) {
	die('Include of main fails');
}

require_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
dol_include_once('/saweeklyreport/class/weeklyreport.class.php');
dol_include_once('/saweeklyreport/lib/saweeklyreport.lib.php');

$langs->loadLangs(array('saweeklyreport@saweeklyreport', 'other'));

if (!isModEnabled('saweeklyreport')) {
	accessforbidden();
}
if (!saweeklyreportCanDo($user, null, 'read')) {
	accessforbidden();
}

$form = new Form($db);
$object = new WeeklyReport($db);

$action = GETPOST('action', 'aZ09') ?: 'list';
$contextpage = GETPOST('contextpage', 'aZ') ? GETPOST('contextpage', 'aZ') : 'weeklyreportlist';
$optioncss = GETPOST('optioncss', 'aZ');
$limit = GETPOSTINT('limit') ? GETPOSTINT('limit') : $conf->liste_limit;
$sortfield = GETPOST('sortfield', 'aZ09comma');
$sortorder = GETPOST('sortorder', 'aZ09comma');
$buttonsearch = GETPOST('button_search_x', 'alpha') || GETPOST('button_search.x', 'alpha') || GETPOST('button_search', 'alpha');
$buttonremovefilter = GETPOST('button_removefilter_x', 'alpha') || GETPOST('button_removefilter.x', 'alpha') || GETPOST('button_removefilter', 'alpha');
$page = GETPOSTISSET('pageplusone') ? (GETPOSTINT('pageplusone') - 1) : GETPOSTINT('page');
if (empty($page) || $page < 0 || $buttonsearch || $buttonremovefilter) {
	$page = 0;
}
$offset = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;
if (!$sortfield) {
	$sortfield = 't.period_start';
}
if (!$sortorder) {
	$sortorder = 'DESC';
}

$search_ref = trim(GETPOST('search_ref', 'alphanohtml'));
$search_label = trim(GETPOST('search_label', 'alphanohtml'));
$search_year = GETPOSTINT('search_year');
$search_week = GETPOSTINT('search_week');
$search_status = GETPOST('search_status', 'intcomma');
if ($search_status === '-1') {
	$search_status = '';
}
$search_period_start_dtstart = dol_mktime(0, 0, 0, GETPOSTINT('search_period_start_dtstartmonth'), GETPOSTINT('search_period_start_dtstartday'), GETPOSTINT('search_period_start_dtstartyear'));
$search_period_start_dtend = dol_mktime(23, 59, 59, GETPOSTINT('search_period_start_dtendmonth'), GETPOSTINT('search_period_start_dtendday'), GETPOSTINT('search_period_start_dtendyear'));
$search_period_end_dtstart = dol_mktime(0, 0, 0, GETPOSTINT('search_period_end_dtstartmonth'), GETPOSTINT('search_period_end_dtstartday'), GETPOSTINT('search_period_end_dtstartyear'));
$search_period_end_dtend = dol_mktime(23, 59, 59, GETPOSTINT('search_period_end_dtendmonth'), GETPOSTINT('search_period_end_dtendday'), GETPOSTINT('search_period_end_dtendyear'));

if ($buttonremovefilter) {
	$search_ref = '';
	$search_label = '';
	$search_year = 0;
	$search_week = 0;
	$search_status = '';
	$search_period_start_dtstart = '';
	$search_period_start_dtend = '';
	$search_period_end_dtstart = '';
	$search_period_end_dtend = '';
}

$arrayfields = array(
	't.ref' => array('label' => 'Ref', 'checked' => '1', 'enabled' => '1', 'position' => 10),
	't.label' => array('label' => 'Label', 'checked' => '1', 'enabled' => '1', 'position' => 20),
	't.year' => array('label' => 'Year', 'checked' => '1', 'enabled' => '1', 'position' => 30, 'csslist' => 'center'),
	't.week' => array('label' => 'Week', 'checked' => '1', 'enabled' => '1', 'position' => 40, 'csslist' => 'center'),
	't.period_start' => array('label' => 'WeeklyReportPeriodStart', 'checked' => '1', 'enabled' => '1', 'position' => 50, 'csslist' => 'center'),
	't.period_end' => array('label' => 'WeeklyReportPeriodEnd', 'checked' => '1', 'enabled' => '1', 'position' => 60, 'csslist' => 'center'),
	't.week_installed_power' => array('label' => 'WeeklyReportWeekInstalledPower', 'checked' => '1', 'enabled' => '1', 'position' => 70, 'csslist' => 'right'),
	't.month_installed_power' => array('label' => 'WeeklyReportMonthInstalledPower', 'checked' => '0', 'enabled' => '1', 'position' => 80, 'csslist' => 'right'),
	't.annual_installed_power' => array('label' => 'WeeklyReportAnnualInstalledPower', 'checked' => '1', 'enabled' => '1', 'position' => 90, 'csslist' => 'right'),
	't.annual_target_power' => array('label' => 'WeeklyReportAnnualTargetPower', 'checked' => '0', 'enabled' => '1', 'position' => 100, 'csslist' => 'right'),
	't.annual_completion_rate' => array('label' => 'WeeklyReportAnnualCompletionRate', 'checked' => '0', 'enabled' => '1', 'position' => 110, 'csslist' => 'right'),
	't.status' => array('label' => 'Status', 'checked' => '1', 'enabled' => '1', 'position' => 120, 'csslist' => 'center'),
	't.tms' => array('label' => 'DateModificationShort', 'checked' => '0', 'enabled' => '1', 'position' => 130, 'csslist' => 'center'),
);

$arrayfields = dol_sort_array($arrayfields, 'position');
$hookmanager->initHooks(array('weeklyreportlist'));
$parameters = array('arrayfields' => &$arrayfields);
$reshook = $hookmanager->executeHooks('completeArrayFields', $parameters, $object, $action);
if ($reshook < 0) {
	setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
}
$arrayfields = dol_sort_array($arrayfields, 'position');

$parameters = array('arrayfields' => &$arrayfields);
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action);
if ($reshook < 0) {
	setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
}
if (empty($reshook)) {
	include DOL_DOCUMENT_ROOT.'/core/actions_changeselectedfields.inc.php';
}

$sql = "SELECT t.rowid, t.ref, t.label, t.year, t.week, t.period_start, t.period_end, t.week_installed_power, t.month_installed_power, t.annual_installed_power, t.annual_target_power, t.annual_completion_rate, t.status, t.tms";
$parameters = array();
$reshook = $hookmanager->executeHooks('printFieldListSelect', $parameters, $object, $action);
$sql .= $hookmanager->resPrint;
$sqlfields = $sql;
$sql .= " FROM ".$db->prefix()."saweeklyreport_weeklyreport as t";
$parameters = array();
$reshook = $hookmanager->executeHooks('printFieldListFrom', $parameters, $object, $action);
$sql .= $hookmanager->resPrint;
$sql .= " WHERE t.entity IN (".getEntity('weeklyreport').")";
if ($search_ref !== '') {
	$sql .= natural_search('t.ref', $search_ref);
}
if ($search_label !== '') {
	$sql .= natural_search('t.label', $search_label);
}
if ($search_year > 0) {
	$sql .= " AND t.year = ".((int) $search_year);
}
if ($search_week > 0) {
	$sql .= " AND t.week = ".((int) $search_week);
}
if ($search_status !== '' && $search_status !== null) {
	$sql .= " AND t.status = ".((int) $search_status);
}
if (!empty($search_period_start_dtstart)) {
	$sql .= " AND t.period_start >= '".$db->idate($search_period_start_dtstart)."'";
}
if (!empty($search_period_start_dtend)) {
	$sql .= " AND t.period_start <= '".$db->idate($search_period_start_dtend)."'";
}
if (!empty($search_period_end_dtstart)) {
	$sql .= " AND t.period_end >= '".$db->idate($search_period_end_dtstart)."'";
}
if (!empty($search_period_end_dtend)) {
	$sql .= " AND t.period_end <= '".$db->idate($search_period_end_dtend)."'";
}
$parameters = array();
$reshook = $hookmanager->executeHooks('printFieldListWhere', $parameters, $object, $action);
$sql .= $hookmanager->resPrint;

$nbtotalofrecords = '';
if (!getDolGlobalInt('MAIN_DISABLE_FULL_SCANLIST')) {
	$sqlforcount = preg_replace('/^'.preg_quote($sqlfields, '/').'/', 'SELECT COUNT(*) as nbtotalofrecords', $sql);
	$resqlcount = $db->query($sqlforcount);
	if ($resqlcount) {
		$objforcount = $db->fetch_object($resqlcount);
		$nbtotalofrecords = (int) $objforcount->nbtotalofrecords;
		$db->free($resqlcount);
		if (($page * $limit) > $nbtotalofrecords) {
			$page = 0;
			$offset = 0;
		}
	} else {
		dol_print_error($db);
	}
}

$sql .= $db->order($sortfield, $sortorder);
if ($limit) {
	$sql .= $db->plimit($limit + 1, $offset);
}

$resql = $db->query($sql);
if (!$resql) {
	dol_print_error($db);
	exit;
}
$num = $db->num_rows($resql);

$listurl = dol_buildpath('/saweeklyreport/weeklyreport_list.php', 1);
$title = $langs->trans('WeeklyReports');

llxHeader('', $title, '', '', 0, 0, '', '', '', 'mod-saweeklyreport page-list bodyforlist');

$param = '';
if ($contextpage != 'weeklyreportlist') {
	$param .= '&contextpage='.urlencode($contextpage);
}
if ($limit > 0 && $limit != $conf->liste_limit) {
	$param .= '&limit='.((int) $limit);
}
if ($optioncss != '') {
	$param .= '&optioncss='.urlencode($optioncss);
}
if ($search_ref !== '') {
	$param .= '&search_ref='.urlencode($search_ref);
}
if ($search_label !== '') {
	$param .= '&search_label='.urlencode($search_label);
}
if ($search_year > 0) {
	$param .= '&search_year='.((int) $search_year);
}
if ($search_week > 0) {
	$param .= '&search_week='.((int) $search_week);
}
if ($search_status !== '') {
	$param .= '&search_status='.urlencode($search_status);
}
if (!empty($search_period_start_dtstart)) {
	$param .= '&search_period_start_dtstartmonth='.GETPOSTINT('search_period_start_dtstartmonth');
	$param .= '&search_period_start_dtstartday='.GETPOSTINT('search_period_start_dtstartday');
	$param .= '&search_period_start_dtstartyear='.GETPOSTINT('search_period_start_dtstartyear');
}
if (!empty($search_period_start_dtend)) {
	$param .= '&search_period_start_dtendmonth='.GETPOSTINT('search_period_start_dtendmonth');
	$param .= '&search_period_start_dtendday='.GETPOSTINT('search_period_start_dtendday');
	$param .= '&search_period_start_dtendyear='.GETPOSTINT('search_period_start_dtendyear');
}
if (!empty($search_period_end_dtstart)) {
	$param .= '&search_period_end_dtstartmonth='.GETPOSTINT('search_period_end_dtstartmonth');
	$param .= '&search_period_end_dtstartday='.GETPOSTINT('search_period_end_dtstartday');
	$param .= '&search_period_end_dtstartyear='.GETPOSTINT('search_period_end_dtstartyear');
}
if (!empty($search_period_end_dtend)) {
	$param .= '&search_period_end_dtendmonth='.GETPOSTINT('search_period_end_dtendmonth');
	$param .= '&search_period_end_dtendday='.GETPOSTINT('search_period_end_dtendday');
	$param .= '&search_period_end_dtendyear='.GETPOSTINT('search_period_end_dtendyear');
}
$parameters = array('param' => &$param);
$reshook = $hookmanager->executeHooks('printFieldListSearchParam', $parameters, $object, $action);
$param .= $hookmanager->resPrint;

print '<form method="POST" id="searchFormList" action="'.dol_escape_htmltag($listurl).'">'."\n";
if ($optioncss != '') {
	print '<input type="hidden" name="optioncss" value="'.dol_escape_htmltag($optioncss).'">';
}
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" name="formfilteraction" id="formfilteraction" value="list">';
print '<input type="hidden" name="action" value="list">';
print '<input type="hidden" name="sortfield" value="'.dol_escape_htmltag($sortfield).'">';
print '<input type="hidden" name="sortorder" value="'.dol_escape_htmltag($sortorder).'">';
print '<input type="hidden" name="page" value="'.((int) $page).'">';
print '<input type="hidden" name="contextpage" value="'.dol_escape_htmltag($contextpage).'">';

$newcardbutton = '';
if (saweeklyreportCanDo($user, null, 'write')) {
	$newcardbutton = dolGetButtonTitle($langs->trans('NewWeeklyReport'), '', 'fa fa-plus-circle', dol_buildpath('/saweeklyreport/weeklyreport_card.php', 1).'?mode=create', '', 1);
}

print_barre_liste($title, $page, $listurl, $param, $sortfield, $sortorder, '', $num, $nbtotalofrecords, $object->picto, 0, $newcardbutton, '', $limit, 0, 0, 1);

$parameters = array();
$reshook = $hookmanager->executeHooks('printFieldPreListTitle', $parameters, $object, $action);
$moreforfilter = empty($reshook) ? $hookmanager->resPrint : $hookmanager->resPrint;
if (!empty($moreforfilter)) {
	print '<div class="liste_titre liste_titre_bydiv centpercent">'.$moreforfilter.'</div>';
}

$varpage = $contextpage;
$checkboxleft = getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN');
$htmlofselectarray = $form->multiSelectArrayWithCheckbox('selectedfields', $arrayfields, $varpage, $checkboxleft);
$selectedfields = $htmlofselectarray;

print '<div class="div-table-responsive">';
print '<table class="tagtable nobottomiftotal noborder liste'.($moreforfilter ? ' listwithfilterbefore' : '').'">'."\n";

print '<tr class="liste_titre_filter">';
if ($checkboxleft) {
	print '<td class="liste_titre center maxwidthsearch">'.$form->showFilterButtons('left').'</td>';
}
if (!empty($arrayfields['t.ref']['checked'])) {
	print '<td class="liste_titre"><input type="text" class="flat maxwidth75" name="search_ref" value="'.dol_escape_htmltag($search_ref).'"></td>';
}
if (!empty($arrayfields['t.label']['checked'])) {
	print '<td class="liste_titre"><input type="text" class="flat maxwidth100" name="search_label" value="'.dol_escape_htmltag($search_label).'"></td>';
}
if (!empty($arrayfields['t.year']['checked'])) {
	print '<td class="liste_titre center"><input type="text" class="flat width75 center" name="search_year" value="'.dol_escape_htmltag($search_year ?: '').'"></td>';
}
if (!empty($arrayfields['t.week']['checked'])) {
	print '<td class="liste_titre center"><input type="text" class="flat width50 center" name="search_week" value="'.dol_escape_htmltag($search_week ?: '').'"></td>';
}
if (!empty($arrayfields['t.period_start']['checked'])) {
	print '<td class="liste_titre center">';
	print '<div class="nowrap">'.$form->selectDate($search_period_start_dtstart ? $search_period_start_dtstart : '', 'search_period_start_dtstart', 0, 0, 1, '', 1, 0, 0, '', '', '', '', 1, '', $langs->trans('From')).'</div>';
	print '<div class="nowrap">'.$form->selectDate($search_period_start_dtend ? $search_period_start_dtend : '', 'search_period_start_dtend', 0, 0, 1, '', 1, 0, 0, '', '', '', '', 1, '', $langs->trans('to')).'</div>';
	print '</td>';
}
if (!empty($arrayfields['t.period_end']['checked'])) {
	print '<td class="liste_titre center">';
	print '<div class="nowrap">'.$form->selectDate($search_period_end_dtstart ? $search_period_end_dtstart : '', 'search_period_end_dtstart', 0, 0, 1, '', 1, 0, 0, '', '', '', '', 1, '', $langs->trans('From')).'</div>';
	print '<div class="nowrap">'.$form->selectDate($search_period_end_dtend ? $search_period_end_dtend : '', 'search_period_end_dtend', 0, 0, 1, '', 1, 0, 0, '', '', '', '', 1, '', $langs->trans('to')).'</div>';
	print '</td>';
}
foreach (array('t.week_installed_power', 't.month_installed_power', 't.annual_installed_power', 't.annual_target_power', 't.annual_completion_rate', 't.tms') as $emptyfilterfield) {
	if (!empty($arrayfields[$emptyfilterfield]['checked'])) {
		print '<td class="liste_titre"></td>';
	}
}
if (!empty($arrayfields['t.status']['checked'])) {
	print '<td class="liste_titre center parentonrightofpage">'.$form->selectarray('search_status', array(WeeklyReport::STATUS_DRAFT => $langs->trans('Draft'), WeeklyReport::STATUS_VALIDATED => $langs->trans('Validated'), WeeklyReport::STATUS_CANCELED => $langs->trans('Canceled')), $search_status, 1, 0, 0, '', 1, 0, 0, '', 'maxwidth100 search_status width100 onrightofpage', 1).'</td>';
}
$parameters = array('arrayfields' => $arrayfields);
$reshook = $hookmanager->executeHooks('printFieldListOption', $parameters, $object, $action);
print $hookmanager->resPrint;
if (!$checkboxleft) {
	print '<td class="liste_titre center maxwidthsearch">'.$form->showFilterButtons().'</td>';
}
print '</tr>'."\n";

$totalarray = array('nbfield' => 0);
print '<tr class="liste_titre">';
if ($checkboxleft) {
	print getTitleFieldOfList($selectedfields, 0, $listurl, '', '', '', '', $sortfield, $sortorder, 'center maxwidthsearch ')."\n";
	$totalarray['nbfield']++;
}
foreach ($arrayfields as $key => $val) {
	if (empty($val['checked'])) {
		continue;
	}
	$cssforfield = empty($val['csslist']) ? '' : $val['csslist'];
	print getTitleFieldOfList($val['label'], 0, $listurl, $key, '', $param, ($cssforfield ? 'class="'.$cssforfield.'"' : ''), $sortfield, $sortorder, ($cssforfield ? $cssforfield.' ' : ''))."\n";
	$totalarray['nbfield']++;
}
$parameters = array('arrayfields' => $arrayfields, 'param' => $param, 'sortfield' => $sortfield, 'sortorder' => $sortorder, 'totalarray' => &$totalarray);
$reshook = $hookmanager->executeHooks('printFieldListTitle', $parameters, $object, $action);
print $hookmanager->resPrint;
if (!$checkboxleft) {
	print getTitleFieldOfList($selectedfields, 0, $listurl, '', '', '', '', $sortfield, $sortorder, 'center maxwidthsearch ')."\n";
	$totalarray['nbfield']++;
}
print '</tr>'."\n";

$i = 0;
if ($num) {
	while ($i < $num) {
		$obj = $db->fetch_object($resql);
		if (!$obj) {
			break;
		}
		if ($limit && $i >= $limit) {
			break;
		}

		$tmpobject = new WeeklyReport($db);
		$tmpobject->id = (int) $obj->rowid;
		$tmpobject->ref = $obj->ref;
		$tmpobject->status = (int) $obj->status;

		print '<tr class="oddeven">';
		if ($checkboxleft) {
			print '<td></td>';
		}
		if (!empty($arrayfields['t.ref']['checked'])) {
			print '<td>'.$tmpobject->getNomUrl(1).'</td>';
		}
		if (!empty($arrayfields['t.label']['checked'])) {
			print '<td>'.dol_escape_htmltag($obj->label).'</td>';
		}
		if (!empty($arrayfields['t.year']['checked'])) {
			print '<td class="center">'.((int) $obj->year).'</td>';
		}
		if (!empty($arrayfields['t.week']['checked'])) {
			print '<td class="center">'.((int) $obj->week).'</td>';
		}
		if (!empty($arrayfields['t.period_start']['checked'])) {
			print '<td class="center">'.dol_print_date($db->jdate($obj->period_start), 'day').'</td>';
		}
		if (!empty($arrayfields['t.period_end']['checked'])) {
			print '<td class="center">'.dol_print_date($db->jdate($obj->period_end), 'day').'</td>';
		}
		if (!empty($arrayfields['t.week_installed_power']['checked'])) {
			print '<td class="right">'.price($obj->week_installed_power).' kWc</td>';
		}
		if (!empty($arrayfields['t.month_installed_power']['checked'])) {
			print '<td class="right">'.price($obj->month_installed_power).' kWc</td>';
		}
		if (!empty($arrayfields['t.annual_installed_power']['checked'])) {
			print '<td class="right">'.price($obj->annual_installed_power).' kWc</td>';
		}
		if (!empty($arrayfields['t.annual_target_power']['checked'])) {
			print '<td class="right">'.price($obj->annual_target_power).' kWc</td>';
		}
		if (!empty($arrayfields['t.annual_completion_rate']['checked'])) {
			print '<td class="right">'.price($obj->annual_completion_rate).'%</td>';
		}
		if (!empty($arrayfields['t.status']['checked'])) {
			print '<td class="center">'.$tmpobject->getLibStatut(5).'</td>';
		}
		if (!empty($arrayfields['t.tms']['checked'])) {
			print '<td class="center">'.dol_print_date($db->jdate($obj->tms), 'dayhour').'</td>';
		}
		$parameters = array('arrayfields' => $arrayfields, 'obj' => $obj);
		$reshook = $hookmanager->executeHooks('printFieldListValue', $parameters, $tmpobject, $action);
		print $hookmanager->resPrint;
		if (!$checkboxleft) {
			print '<td></td>';
		}
		print '</tr>';
		$i++;
	}
} else {
	print '<tr class="oddeven"><td colspan="'.((int) $totalarray['nbfield']).'"><span class="opacitymedium">'.$langs->trans('NoRecordFound').'</span></td></tr>';
}

print '</table>';
print '</div>';
print '</form>';

llxFooter();
$db->close();
