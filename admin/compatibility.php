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
require_once '../class/saweeklyreportcompatibility.class.php';

$langs->loadLangs(array('admin', 'saweeklyreport@saweeklyreport'));

if (empty($user->admin)) {
	accessforbidden();
}

$title = $langs->trans('Compatibility');
$linkback = saweeklyreportAdminModuleListLink();

llxHeader('', $title);

print load_fiche_titre($title, $linkback, 'fa-chart-line');
$head = saweeklyreportAdminPrepareHead();
print dol_get_fiche_head($head, 'compatibility', $title, -1, 'fa-chart-line');

print '<table class="noborder centpercent">';
print '<tr class="liste_titre"><th colspan="2">'.$langs->trans('SAWeeklyReportEnvironment').'</th></tr>';
print '<tr class="oddeven"><td class="titlefield">'.$langs->trans('DetectedPhpVersion').'</td><td>'.dol_escape_htmltag(PHP_VERSION).'</td></tr>';
print '<tr class="oddeven"><td>'.$langs->trans('DetectedDolibarrVersion').'</td><td>'.(defined('DOL_VERSION') ? dol_escape_htmltag(DOL_VERSION) : '').'</td></tr>';
print '<tr class="oddeven"><td>'.$langs->trans('MinimumPhpVersion').'</td><td>8.0</td></tr>';
print '<tr class="oddeven"><td>'.$langs->trans('MinimumDolibarrVersion').'</td><td>20.0</td></tr>';
print '</table>';

print '<br>';
print '<table class="noborder centpercent">';
print '<tr class="liste_titre"><th>'.$langs->trans('Feature').'</th><th>'.$langs->trans('Status').'</th><th>'.$langs->trans('Description').'</th><th>'.$langs->trans('Reason').'</th></tr>';
foreach (SAWeeklyReportCompatibility::getFeatures() as $feature) {
	$available = !empty($feature['available']);
	print '<tr class="oddeven">';
	print '<td>'.dol_escape_htmltag($langs->trans($feature['label'])).'</td>';
	print '<td>'.($available ? img_picto($langs->trans('Available'), 'on') : img_picto($langs->trans('Unavailable'), 'off')).' '.($available ? $langs->trans('Available') : $langs->trans('Unavailable')).'</td>';
	print '<td>'.dol_escape_htmltag($langs->trans($feature['description'])).'</td>';
	print '<td>'.($available ? '' : dol_escape_htmltag($langs->trans($feature['reason']))).'</td>';
	print '</tr>';
}
print '</table>';

print dol_get_fiche_end();

llxFooter();
$db->close();
