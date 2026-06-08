<?php
/* Copyright (C) 2026  Pierre Ardoin <developpeur@lesmetiersdubatiment.fr> */

$res = 0;
if (!$res && !empty($_SERVER['CONTEXT_DOCUMENT_ROOT'])) {
	$res = @include $_SERVER['CONTEXT_DOCUMENT_ROOT'].'/main.inc.php';
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

dol_include_once('/saweeklyreport/class/weeklyreport.class.php');
dol_include_once('/saweeklyreport/lib/saweeklyreport.lib.php');

$langs->loadLangs(array('saweeklyreport@saweeklyreport', 'agenda'));

$id = GETPOSTINT('id');
$object = new WeeklyReport($db);
if ($object->fetch($id) <= 0) {
	accessforbidden();
}

if (!isModEnabled('saweeklyreport') || !isModEnabled('agenda')) {
	accessforbidden();
}
if (!$user->hasRight('saweeklyreport', 'weeklyreport', 'read')) {
	accessforbidden();
}

llxHeader('', $langs->trans('WeeklyReport').' - '.$langs->trans('Agenda'), '', '', 0, 0, '', '', '', 'mod-saweeklyreport page-card_agenda');

$head = weeklyreportPrepareHead($object);
print dol_get_fiche_head($head, 'agenda', $langs->trans('WeeklyReport'), -1, $object->picto);

$linkback = '<a href="'.dol_buildpath('/saweeklyreport/weeklyreport_list.php', 1).'">'.$langs->trans('BackToList').'</a>';
dol_banner_tab($object, 'ref', $linkback, 1, 'ref', 'ref');

print '<br>';
print '<table class="noborder centpercent">';
print '<tr class="liste_titre"><th>'.$langs->trans('Date').'</th><th>'.$langs->trans('Label').'</th><th>'.$langs->trans('Type').'</th></tr>';

$sql = "SELECT id, label, datep, code";
$sql .= " FROM ".$db->prefix()."actioncomm";
$sql .= " WHERE entity IN (".getEntity('agenda').")";
$sql .= " AND fk_element = ".((int) $object->id);
$sql .= " AND elementtype IN ('weeklyreport', 'weeklyreport@saweeklyreport')";
$sql .= " ORDER BY datep DESC";
$resql = $db->query($sql);
if ($resql) {
	while ($obj = $db->fetch_object($resql)) {
		print '<tr class="oddeven">';
		print '<td>'.dol_print_date($db->jdate($obj->datep), 'dayhour').'</td>';
		print '<td><a href="'.DOL_URL_ROOT.'/comm/action/card.php?id='.((int) $obj->id).'">'.dol_escape_htmltag($obj->label).'</a></td>';
		print '<td>'.dol_escape_htmltag($obj->code).'</td>';
		print '</tr>';
	}
}
print '</table>';

print dol_get_fiche_end();

llxFooter();
$db->close();
