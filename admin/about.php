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
if (!$res && file_exists('../../main.inc.php')) {
	$res = @include '../../main.inc.php';
}
if (!$res && file_exists('../../../main.inc.php')) {
	$res = @include '../../../main.inc.php';
}
if (!$res) {
	die('Include of main fails');
}

require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once '../lib/saweeklyreport.lib.php';
require_once '../core/modules/modSAWeeklyReport.class.php';

$langs->loadLangs(array('admin', 'saweeklyreport@saweeklyreport'));

if (empty($user->admin)) {
	accessforbidden();
}

$moduleDescriptor = new modSAWeeklyReport($db);
$title = $langs->trans('About');
$linkback = saweeklyreportAdminModuleListLink();

llxHeader('', $title);

print load_fiche_titre($title, $linkback, 'info');
$head = saweeklyreportAdminPrepareHead();
print dol_get_fiche_head($head, 'about', $title, -1, 'fa-chart-line');

print '<div class="underbanner opacitymedium">'.$langs->trans('SAWeeklyReportAboutPage').'</div>';
print '<br>';
print '<div class="fichecenter">';

print '<div class="fichehalfleft">';
print '<div class="div-table-responsive-no-min">';
print '<table class="noborder centpercent">';
print '<tr class="liste_titre"><th colspan="2">'.$langs->trans('SAWeeklyReportAboutGeneral').'</th></tr>';
print '<tr class="oddeven"><td class="titlefield">'.$langs->trans('Module').'</td><td>'.dol_escape_htmltag($langs->trans('ModuleSAWeeklyReportName')).'</td></tr>';
print '<tr class="oddeven"><td>'.$langs->trans('Version').'</td><td>'.dol_escape_htmltag($moduleDescriptor->version).'</td></tr>';
print '<tr class="oddeven"><td>'.$langs->trans('Editor').'</td><td>'.dol_escape_htmltag($moduleDescriptor->editor_name).'</td></tr>';
print '<tr class="oddeven"><td>'.$langs->trans('Description').'</td><td>'.dol_escape_htmltag($langs->trans($moduleDescriptor->description)).'</td></tr>';
print '<tr class="oddeven"><td>'.$langs->trans('Compatibility').'</td><td>Dolibarr 20+ / PHP 8.0+</td></tr>';
print '<tr class="oddeven"><td>'.$langs->trans('License').'</td><td>GPL-3.0-or-later</td></tr>';
print '</table>';
print '</div>';
print '</div>';

print '<div class="fichehalfright">';
print '<div class="div-table-responsive-no-min">';
print '<table class="noborder centpercent">';
print '<tr class="liste_titre"><th colspan="2">'.$langs->trans('SAWeeklyReportAboutResources').'</th></tr>';
print '<tr class="oddeven"><td class="titlefield">'.$langs->trans('Dependencies').'</td><td>'.$langs->trans('SAWeeklyReportDependencies').'</td></tr>';
print '<tr class="oddeven"><td>'.$langs->trans('MainFeatures').'</td><td>'.$langs->trans('SAWeeklyReportMainFeatures').'</td></tr>';
print '<tr class="oddeven"><td>'.$langs->trans('Documentation').'</td><td><a href="'.dol_buildpath('/saweeklyreport/README.md', 1).'" target="_blank" rel="noopener">README.md</a></td></tr>';
print '<tr class="oddeven"><td>'.$langs->trans('Support').'</td><td><a href="https://'.dol_escape_htmltag($moduleDescriptor->editor_url).'" target="_blank" rel="noopener">'.dol_escape_htmltag($moduleDescriptor->editor_url).'</a></td></tr>';
print '</table>';
print '</div>';
print '</div>';

print '</div>';
print dol_get_fiche_end();

llxFooter();
$db->close();
