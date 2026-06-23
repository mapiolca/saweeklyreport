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
require_once DOL_DOCUMENT_ROOT.'/core/lib/ajax.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formticket.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/doleditor.class.php';
dol_include_once('/ticket/class/ticket.class.php');
dol_include_once('/saweeklyreport/class/saweeklyreporttickethelper.class.php');
require_once '../lib/saweeklyreport.lib.php';

$langs->loadLangs(array('admin', 'ticket', 'saweeklyreport@saweeklyreport'));

if (empty($user->admin)) {
	accessforbidden();
}

$action = GETPOST('action', 'aZ09');
$form = new Form($db);
$setupurl = dol_buildpath('/saweeklyreport/admin/setup.php', 1);

if ($action === 'updatesettings') {
	if (GETPOST('token', 'alpha') !== currentToken()) {
		accessforbidden('Invalid token');
	}

	$settings = array(
		'SAWEEKLYREPORT_ANNUAL_TARGET_POWER' => GETPOST('SAWEEKLYREPORT_ANNUAL_TARGET_POWER', 'alphanohtml'),
		'SAWEEKLYREPORT_WEEKLY_TARGET_POWER' => GETPOST('SAWEEKLYREPORT_WEEKLY_TARGET_POWER', 'alphanohtml'),
		'SAWEEKLYREPORT_MEETING_DURATION' => GETPOSTINT('SAWEEKLYREPORT_MEETING_DURATION'),
		'SAWEEKLYREPORT_WEEKLYREPORT_ADDON' => GETPOST('SAWEEKLYREPORT_WEEKLYREPORT_ADDON', 'aZ09'),
		'SAWEEKLYREPORT_WEEKLYREPORT_MASK' => GETPOST('SAWEEKLYREPORT_WEEKLYREPORT_MASK', 'alphanohtml'),
		'SAWEEKLYREPORT_WEEKLYREPORT_ADDON_PPTX' => GETPOST('SAWEEKLYREPORT_WEEKLYREPORT_ADDON_PPTX', 'aZ09'),
		'SAWEEKLYREPORT_DEFAULT_SAFETY_MESSAGE' => dol_htmlcleanlastbr(GETPOST('SAWEEKLYREPORT_DEFAULT_SAFETY_MESSAGE', 'restricthtml')),
		'SAWEEKLYREPORT_DEFAULT_LOADING_REMINDER' => dol_htmlcleanlastbr(GETPOST('SAWEEKLYREPORT_DEFAULT_LOADING_REMINDER', 'restricthtml')),
	);
	if (isModEnabled('ticket')) {
		$settings['SAWEEKLYREPORT_TICKET_TYPE_CODES'] = implode(',', SAWeeklyReportTicketHelper::cleanTicketDictionaryCodes($db, GETPOST('SAWEEKLYREPORT_TICKET_TYPE_CODES', 'array'), 'c_ticket_type'));
	} else {
		$settings['SAWEEKLYREPORT_TICKET_TYPE_CODES'] = getDolGlobalString('SAWEEKLYREPORT_TICKET_TYPE_CODES');
	}

	$error = 0;
	foreach ($settings as $key => $value) {
		$result = dolibarr_set_const($db, $key, (string) $value, 'chaine', 0, '', (int) $conf->entity);
		if ($result < 0) {
			$error++;
		}
	}
	if ($error) {
		setEventMessages($langs->trans('Error'), null, 'errors');
	} else {
		setEventMessages($langs->trans('SetupSaved'), null, 'mesgs');
	}

	header('Location: '.$setupurl);
	exit;
}

$title = $langs->trans('SAWeeklyReportSetup');
$linkback = saweeklyreportAdminModuleListLink();

llxHeader('', $title);

print load_fiche_titre($title, $linkback, 'fa-chart-line');
$head = saweeklyreportAdminPrepareHead();
print dol_get_fiche_head($head, 'settings', $title, -1, 'fa-chart-line');

print '<form method="POST" action="'.dol_escape_htmltag($setupurl).'">';
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" name="action" value="updatesettings">';

print '<table class="noborder centpercent">';
print '<tr class="liste_titre"><th colspan="2">'.$langs->trans('SAWeeklyReportKpiSettings').'</th></tr>';
print '<tr class="oddeven"><td class="titlefield">'.$langs->trans('WeeklyReportAnnualTargetPower').'</td><td><input class="flat width100 right" name="SAWEEKLYREPORT_ANNUAL_TARGET_POWER" value="'.dol_escape_htmltag(getDolGlobalString('SAWEEKLYREPORT_ANNUAL_TARGET_POWER', '846')).'"> kWc</td></tr>';
print '<tr class="oddeven"><td>'.$langs->trans('WeeklyReportWeeklyTargetPower').'</td><td><input class="flat width100 right" name="SAWEEKLYREPORT_WEEKLY_TARGET_POWER" value="'.dol_escape_htmltag(getDolGlobalString('SAWEEKLYREPORT_WEEKLY_TARGET_POWER', '18')).'"> kWc</td></tr>';
print '<tr class="oddeven"><td>'.$langs->trans('WeeklyReportMeetingDuration').'</td><td><input class="flat width100 right" name="SAWEEKLYREPORT_MEETING_DURATION" value="'.dol_escape_htmltag(getDolGlobalString('SAWEEKLYREPORT_MEETING_DURATION', '15')).'"> '.$langs->trans('Minutes').'</td></tr>';
print '<tr class="liste_titre"><th colspan="2">'.$langs->trans('SAWeeklyReportModelsSettings').'</th></tr>';
print '<tr class="oddeven"><td>'.$langs->trans('WeeklyReportNumberingModel').'</td><td>';
print $form->selectarray('SAWEEKLYREPORT_WEEKLYREPORT_ADDON', array('mod_weeklyreport_standard' => 'mod_weeklyreport_standard'), getDolGlobalString('SAWEEKLYREPORT_WEEKLYREPORT_ADDON', 'mod_weeklyreport_standard'), 0, 0, 0, '', 0, 0, 0, '', 'minwidth300');
print ajax_combobox('SAWEEKLYREPORT_WEEKLYREPORT_ADDON');
print '</td></tr>';
print '<tr class="oddeven"><td>'.$langs->trans('WeeklyReportNumberingMask').'</td><td><input class="flat minwidth300" name="SAWEEKLYREPORT_WEEKLYREPORT_MASK" value="'.dol_escape_htmltag(getDolGlobalString('SAWEEKLYREPORT_WEEKLYREPORT_MASK', 'SAWR-{YYYY}-S{WW}')).'"></td></tr>';
print '<tr class="oddeven"><td>'.$langs->trans('WeeklyReportPptxModel').'</td><td>';
print $form->selectarray('SAWEEKLYREPORT_WEEKLYREPORT_ADDON_PPTX', array('weekly_report_standard' => 'weekly_report_standard'), getDolGlobalString('SAWEEKLYREPORT_WEEKLYREPORT_ADDON_PPTX', 'weekly_report_standard'), 0, 0, 0, '', 0, 0, 0, '', 'minwidth300');
print ajax_combobox('SAWEEKLYREPORT_WEEKLYREPORT_ADDON_PPTX');
print '</td></tr>';
print '<tr class="liste_titre"><th colspan="2">'.$langs->trans('SAWeeklyReportPrefillSettings').'</th></tr>';
print '<tr class="oddeven"><td>'.$langs->trans('SAWeeklyReportPrefillFichinter').'</td><td>'.ajax_constantonoff('SAWEEKLYREPORT_PREFILL_FICHINTER', array(), (int) $conf->entity).'</td></tr>';
print '<tr class="oddeven"><td>'.$langs->trans('SAWeeklyReportPrefillTicket').'</td><td>'.ajax_constantonoff('SAWEEKLYREPORT_PREFILL_TICKET', array(), (int) $conf->entity).'</td></tr>';
print '<tr class="oddeven"><td>'.$langs->trans('SAWeeklyReportTicketTypesToPrefill').'</td><td>';
if (isModEnabled('ticket')) {
	$formticket = new FormTicket($db);
	$formticket->selectTypesTickets(getDolGlobalString('SAWEEKLYREPORT_TICKET_TYPE_CODES'), 'SAWEEKLYREPORT_TICKET_TYPE_CODES', '', 2, 0, 1, 0, 'minwidth300 maxwidth500', 1);
	print ' <span class="opacitymedium">'.$langs->trans('SAWeeklyReportTicketTypesToPrefillHelp').'</span>';
} else {
	print '<span class="opacitymedium">'.$langs->trans('RequiresTicket').'</span>';
}
print '</td></tr>';
print '<tr class="liste_titre"><th colspan="2">'.$langs->trans('SAWeeklyReportAgendaSettings').'</th></tr>';
if (isModEnabled('agenda')) {
	print '<tr class="oddeven"><td>'.$langs->trans('SAWeeklyReportAgendaCreate').'</td><td>'.ajax_constantonoff('MAIN_AGENDA_ACTIONAUTO_SAWEEKLYREPORT_WEEKLYREPORT_CREATE', array(), (int) $conf->entity).'</td></tr>';
	print '<tr class="oddeven"><td>'.$langs->trans('SAWeeklyReportAgendaUpdate').'</td><td>'.ajax_constantonoff('MAIN_AGENDA_ACTIONAUTO_SAWEEKLYREPORT_WEEKLYREPORT_UPDATE', array(), (int) $conf->entity).'</td></tr>';
	print '<tr class="oddeven"><td>'.$langs->trans('SAWeeklyReportAgendaDelete').'</td><td>'.ajax_constantonoff('MAIN_AGENDA_ACTIONAUTO_SAWEEKLYREPORT_WEEKLYREPORT_DELETE', array(), (int) $conf->entity).'</td></tr>';
} else {
	print '<tr class="oddeven"><td colspan="2" class="opacitymedium">'.$langs->trans('RequiresAgenda').'</td></tr>';
}
print '<tr class="liste_titre"><th colspan="2">'.$langs->trans('SAWeeklyReportDefaultTexts').'</th></tr>';
print '<tr class="oddeven"><td>'.$langs->trans('WeeklyReportSafetyMessage').'</td><td>';
$editor = new DolEditor('SAWEEKLYREPORT_DEFAULT_SAFETY_MESSAGE', getDolGlobalString('SAWEEKLYREPORT_DEFAULT_SAFETY_MESSAGE'), '', 140, 'dolibarr_notes', 'In', false, false, isModEnabled('fckeditor'), ROWS_3, '100%');
print $editor->Create(1);
print '</td></tr>';
print '<tr class="oddeven"><td>'.$langs->trans('WeeklyReportVehicleLoadingReminder').'</td><td>';
$editor = new DolEditor('SAWEEKLYREPORT_DEFAULT_LOADING_REMINDER', getDolGlobalString('SAWEEKLYREPORT_DEFAULT_LOADING_REMINDER'), '', 140, 'dolibarr_notes', 'In', false, false, isModEnabled('fckeditor'), ROWS_3, '100%');
print $editor->Create(1);
print '</td></tr>';
print '</table>';

print '<div class="center">';
print '<input type="submit" class="button button-save" value="'.$langs->trans('Save').'">';
print '</div>';
print '</form>';

print dol_get_fiche_end();

llxFooter();
$db->close();
