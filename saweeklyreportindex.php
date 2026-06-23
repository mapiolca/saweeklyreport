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

dol_include_once('/saweeklyreport/lib/saweeklyreport.lib.php');

$langs->loadLangs(array('saweeklyreport@saweeklyreport'));

if (!isModEnabled('saweeklyreport')) {
	accessforbidden();
}
if (!saweeklyreportCanDo($user, null, 'read')) {
	accessforbidden();
}

llxHeader('', $langs->trans('ModuleSAWeeklyReportName'));

print load_fiche_titre($langs->trans('ModuleSAWeeklyReportName'), '', 'fa-chart-line');
print '<div class="fichecenter">';
print '<div class="opacitymedium">'.$langs->trans('ModuleSAWeeklyReportDesc').'</div>';
print '<br>';
print '<a class="button" href="'.dol_buildpath('/saweeklyreport/weeklyreport_list.php', 1).'">'.$langs->trans('WeeklyReports').'</a>';
if (saweeklyreportCanDo($user, null, 'write')) {
	print ' <a class="button" href="'.dol_buildpath('/saweeklyreport/weeklyreport_card.php', 1).'?mode=create">'.$langs->trans('NewWeeklyReport').'</a>';
}
print '</div>';

llxFooter();
$db->close();
