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
dol_include_once('/saweeklyreport/class/weeklyreport.class.php');

$langs->loadLangs(array('saweeklyreport@saweeklyreport', 'other'));

if (!isModEnabled('saweeklyreport')) {
	accessforbidden();
}
if (!$user->hasRight('saweeklyreport', 'weeklyreport', 'read')) {
	accessforbidden();
}

$form = new Form($db);
$object = new WeeklyReport($db);
$hookmanager->initHooks(array('weeklyreportlist'));

$action = GETPOST('action', 'aZ09');
$limit = GETPOSTINT('limit') ? GETPOSTINT('limit') : $conf->liste_limit;
$sortfield = GETPOST('sortfield', 'aZ09comma');
$sortorder = GETPOST('sortorder', 'aZ09comma');
$page = GETPOSTISSET('pageplusone') ? (GETPOSTINT('pageplusone') - 1) : GETPOSTINT('page');
if ($page < 0 || GETPOST('button_search', 'alpha') || GETPOST('button_removefilter', 'alpha')) {
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

$search_year = GETPOSTINT('search_year');
$search_week = GETPOSTINT('search_week');
$search_status = GETPOST('search_status', 'intcomma');
if (GETPOST('button_removefilter', 'alpha')) {
	$search_year = 0;
	$search_week = 0;
	$search_status = '';
}

$sqlwhere = " WHERE t.entity IN (".getEntity('weeklyreport').")";
if ($search_year > 0) {
	$sqlwhere .= " AND t.year = ".$search_year;
}
if ($search_week > 0) {
	$sqlwhere .= " AND t.week = ".$search_week;
}
if ($search_status !== '' && $search_status !== null) {
	$sqlwhere .= " AND t.status = ".((int) $search_status);
}

$sqlcount = "SELECT COUNT(t.rowid) as nb FROM ".$db->prefix()."saweeklyreport_weeklyreport as t".$sqlwhere;
$rescount = $db->query($sqlcount);
$num = 0;
if ($rescount) {
	$objcount = $db->fetch_object($rescount);
	$num = (int) $objcount->nb;
}

$sql = "SELECT t.rowid, t.ref, t.label, t.year, t.week, t.period_start, t.period_end, t.week_installed_power, t.annual_installed_power, t.status";
$sql .= " FROM ".$db->prefix()."saweeklyreport_weeklyreport as t";
$sql .= $sqlwhere;
$sql .= $db->order($sortfield, $sortorder);
$sql .= $db->plimit($limit + 1, $offset);

$resql = $db->query($sql);
$listurl = dol_buildpath('/saweeklyreport/weeklyreport_list.php', 1);

llxHeader('', $langs->trans('WeeklyReports'), '', '', 0, 0, '', '', '', 'mod-saweeklyreport page-list');

$param = '';
if ($search_year > 0) {
	$param .= '&search_year='.$search_year;
}
if ($search_week > 0) {
	$param .= '&search_week='.$search_week;
}
if ($search_status !== '') {
	$param .= '&search_status='.$search_status;
}

$newcardbutton = '';
if ($user->hasRight('saweeklyreport', 'weeklyreport', 'write')) {
	$newcardbutton = dolGetButtonTitle($langs->trans('NewWeeklyReport'), '', 'fa fa-plus-circle', dol_buildpath('/saweeklyreport/weeklyreport_card.php', 1).'?action=create', '', 1);
}

print_barre_liste($langs->trans('WeeklyReports'), $page, $listurl, $param, $sortfield, $sortorder, '', $num, $num, $object->picto, 0, $newcardbutton, '', $limit);

print '<form method="POST" action="'.dol_escape_htmltag($listurl).'">';
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" name="sortfield" value="'.dol_escape_htmltag($sortfield).'">';
print '<input type="hidden" name="sortorder" value="'.dol_escape_htmltag($sortorder).'">';

print '<div class="div-table-responsive">';
print '<table class="tagtable liste centpercent">';
print '<tr class="liste_titre_filter">';
print '<td><input class="flat width75" name="search_year" value="'.dol_escape_htmltag($search_year ?: '').'"></td>';
print '<td><input class="flat width50" name="search_week" value="'.dol_escape_htmltag($search_week ?: '').'"></td>';
print '<td colspan="4"></td>';
print '<td>'.$form->selectarray('search_status', array(WeeklyReport::STATUS_DRAFT => $langs->trans('Draft'), WeeklyReport::STATUS_VALIDATED => $langs->trans('Validated'), WeeklyReport::STATUS_CANCELED => $langs->trans('Canceled')), $search_status, 1).'</td>';
print '<td class="liste_titre maxwidthsearch">';
print '<button type="submit" class="liste_titre button_search" name="button_search" value="x">'.img_search().'</button>';
print '<button type="submit" class="liste_titre button_removefilter" name="button_removefilter" value="x">'.img_picto($langs->trans('RemoveFilter'), 'eraser').'</button>';
print '</td>';
print '</tr>';
print '<tr class="liste_titre">';
print_liste_field_titre('Year', $listurl, 't.year', '', $param, '', $sortfield, $sortorder);
print_liste_field_titre('Week', $listurl, 't.week', '', $param, '', $sortfield, $sortorder);
print_liste_field_titre('Ref', $listurl, 't.ref', '', $param, '', $sortfield, $sortorder);
print_liste_field_titre('Label', $listurl, 't.label', '', $param, '', $sortfield, $sortorder);
print_liste_field_titre('WeeklyReportPeriod', $listurl, 't.period_start', '', $param, '', $sortfield, $sortorder);
print_liste_field_titre('WeeklyReportWeekInstalledPower', $listurl, 't.week_installed_power', '', $param, 'class="right"', $sortfield, $sortorder);
print_liste_field_titre('Status', $listurl, 't.status', '', $param, '', $sortfield, $sortorder);
print '<th></th>';
print '</tr>';

if ($resql) {
	while ($obj = $db->fetch_object($resql)) {
		$tmpobject = new WeeklyReport($db);
		$tmpobject->id = (int) $obj->rowid;
		$tmpobject->ref = $obj->ref;
		$tmpobject->status = (int) $obj->status;
		print '<tr class="oddeven">';
		print '<td>'.((int) $obj->year).'</td>';
		print '<td>'.((int) $obj->week).'</td>';
		print '<td>'.$tmpobject->getNomUrl(1).'</td>';
		print '<td>'.dol_escape_htmltag($obj->label).'</td>';
		print '<td>'.dol_print_date($db->jdate($obj->period_start), 'day').' - '.dol_print_date($db->jdate($obj->period_end), 'day').'</td>';
		print '<td class="right">'.price($obj->week_installed_power).' kWc</td>';
		print '<td>'.$tmpobject->getLibStatut(3).'</td>';
		print '<td></td>';
		print '</tr>';
	}
}
print '</table>';
print '</div>';
print '</form>';

llxFooter();
$db->close();
