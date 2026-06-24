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
dol_include_once('/saweeklyreport/class/weeklyreport.class.php');
dol_include_once('/saweeklyreport/core/modules/saweeklyreport/modules_weeklyreport.php');
require_once '../lib/saweeklyreport.lib.php';

$langs->loadLangs(array('admin', 'ticket', 'saweeklyreport@saweeklyreport'));

if (empty($user->admin)) {
	accessforbidden();
}

$action = GETPOST('action', 'aZ09');
$form = new Form($db);
$setupurl = dol_buildpath('/saweeklyreport/admin/setup.php', 1);
$documenttype = 'weeklyreport';
$dirmodels = array_merge(array('/'), (array) $conf->modules_parts['models']);

if (in_array($action, array('setmod', 'updateMask', 'set', 'del', 'setdoc'), true)) {
	if (GETPOST('token', 'alpha') !== currentToken()) {
		accessforbidden('Invalid token');
	}

	$error = 0;
	$value = GETPOST('value', 'alphanohtml');
	$label = GETPOST('label', 'alphanohtml');
	$scandir = GETPOST('scan_dir', 'alphanohtml');

	if ($action === 'setmod') {
		$value = GETPOST('value', 'aZ09');
		if (!preg_match('/^mod_weeklyreport_[A-Za-z0-9_]+$/', $value)) {
			$error++;
		} else {
			$filefound = '';
			foreach ($dirmodels as $reldir) {
				$candidate = dol_buildpath($reldir.'core/modules/saweeklyreport/'.$value.'.php', 0);
				if (is_readable($candidate)) {
					$filefound = $candidate;
					break;
				}
			}
			if ($filefound === '') {
				$error++;
			} else {
				require_once $filefound;
				if (!class_exists($value)) {
					$error++;
				} else {
					$module = new $value();
					$tmpobject = new WeeklyReport($db);
					if (method_exists($module, 'canBeActivated') && !$module->canBeActivated($tmpobject)) {
						$error++;
					} elseif (dolibarr_set_const($db, 'SAWEEKLYREPORT_WEEKLYREPORT_ADDON', $value, 'chaine', 0, '', (int) $conf->entity) <= 0) {
						$error++;
					}
				}
			}
		}
	} elseif ($action === 'updateMask') {
		$maskconst = GETPOST('maskconstWeeklyReport', 'aZ09');
		$maskvalue = GETPOST('maskWeeklyReport', 'alphanohtml');
		if ($maskconst !== 'SAWEEKLYREPORT_WEEKLYREPORT_ADVANCED_MASK' || dolibarr_set_const($db, $maskconst, $maskvalue, 'chaine', 0, '', (int) $conf->entity) <= 0) {
			$error++;
		}
	} elseif ($action === 'set') {
		if (!preg_match('/^pdf_[A-Za-z0-9_]+$/', $value) || addDocumentModel($value, $documenttype, $label, $scandir) <= 0) {
			$error++;
		}
	} elseif ($action === 'del') {
		if (!preg_match('/^pdf_[A-Za-z0-9_]+$/', $value) || delDocumentModel($value, $documenttype) <= 0) {
			$error++;
		} elseif (getDolGlobalString('SAWEEKLYREPORT_WEEKLYREPORT_ADDON_PDF') === $value) {
			dolibarr_del_const($db, 'SAWEEKLYREPORT_WEEKLYREPORT_ADDON_PDF', (int) $conf->entity);
		}
	} elseif ($action === 'setdoc') {
		if (!preg_match('/^pdf_[A-Za-z0-9_]+$/', $value) || dolibarr_set_const($db, 'SAWEEKLYREPORT_WEEKLYREPORT_ADDON_PDF', $value, 'chaine', 0, '', (int) $conf->entity) <= 0) {
			$error++;
		} else {
			$conf->global->SAWEEKLYREPORT_WEEKLYREPORT_ADDON_PDF = $value;
			delDocumentModel($value, $documenttype);
			if (addDocumentModel($value, $documenttype, $label, $scandir) <= 0) {
				$error++;
			}
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

if ($action === 'updatesettings') {
	if (GETPOST('token', 'alpha') !== currentToken()) {
		accessforbidden('Invalid token');
	}

	$settings = array(
		'SAWEEKLYREPORT_ANNUAL_TARGET_POWER' => GETPOST('SAWEEKLYREPORT_ANNUAL_TARGET_POWER', 'alphanohtml'),
		'SAWEEKLYREPORT_WEEKLY_TARGET_POWER' => GETPOST('SAWEEKLYREPORT_WEEKLY_TARGET_POWER', 'alphanohtml'),
		'SAWEEKLYREPORT_MEETING_DURATION' => GETPOSTINT('SAWEEKLYREPORT_MEETING_DURATION'),
		'SAWEEKLYREPORT_WEEKLYREPORT_ADDON_PPTX' => GETPOST('SAWEEKLYREPORT_WEEKLYREPORT_ADDON_PPTX', 'aZ09'),
		'SAWEEKLYREPORT_DEFAULT_SAFETY_MESSAGE' => dol_htmlcleanlastbr(GETPOST('SAWEEKLYREPORT_DEFAULT_SAFETY_MESSAGE', 'restricthtml')),
		'SAWEEKLYREPORT_DEFAULT_LOADING_REMINDER' => dol_htmlcleanlastbr(GETPOST('SAWEEKLYREPORT_DEFAULT_LOADING_REMINDER', 'restricthtml')),
		'SAWEEKLYREPORT_FREE_TEXT' => dol_htmlcleanlastbr(GETPOST('SAWEEKLYREPORT_FREE_TEXT', 'restricthtml')),
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

print load_fiche_titre($langs->trans('SAWeeklyReportNumberingModules'), '', '');
print '<div class="div-table-responsive-no-min">';
print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans('Name').'</td>';
print '<td>'.$langs->trans('Description').'</td>';
print '<td class="nowrap">'.$langs->trans('Example').'</td>';
print '<td class="center" width="60">'.$langs->trans('Status').'</td>';
print '<td class="center" width="16">'.$langs->trans('ShortInfo').'</td>';
print '</tr>';

$numberingfound = 0;
clearstatcache();
foreach ($dirmodels as $reldir) {
	$dir = dol_buildpath($reldir.'core/modules/saweeklyreport/', 0);
	if (!is_dir($dir)) {
		continue;
	}
	$handle = opendir($dir);
	if (!is_resource($handle)) {
		continue;
	}
	while (($file = readdir($handle)) !== false) {
		if (!preg_match('/^mod_weeklyreport_[A-Za-z0-9_]+\.php$/', $file)) {
			continue;
		}
		$classname = substr($file, 0, -4);
		require_once $dir.$file;
		if (!class_exists($classname)) {
			continue;
		}
		$module = new $classname();
		if (method_exists($module, 'isEnabled') && !$module->isEnabled()) {
			continue;
		}
		$moduleversion = isset($module->version) ? (string) $module->version : '';
		if ($moduleversion === 'development' && getDolGlobalInt('MAIN_FEATURES_LEVEL') < 2) {
			continue;
		}
		if ($moduleversion === 'experimental' && getDolGlobalInt('MAIN_FEATURES_LEVEL') < 1) {
			continue;
		}
		$numberingfound++;
		$modulename = !empty($module->name) ? (string) $module->name : $classname;
		print '<tr class="oddeven">';
		print '<td>'.dol_escape_htmltag($modulename).'</td>';
		print '<td>'.(method_exists($module, 'info') ? $module->info($langs) : dol_escape_htmltag($classname)).'</td>';
		$example = method_exists($module, 'getExample') ? $module->getExample() : '';
		print '<td class="nowrap">';
		if (preg_match('/^Error/', (string) $example)) {
			$langs->load('errors');
			print '<span class="error">'.$langs->trans((string) $example).'</span>';
		} elseif ($example === 'NotConfigured') {
			print '<span class="opacitymedium">'.$langs->trans((string) $example).'</span>';
		} else {
			print dol_escape_htmltag((string) $example);
		}
		print '</td>';
		print '<td class="center">';
		if (getDolGlobalString('SAWEEKLYREPORT_WEEKLYREPORT_ADDON', 'mod_weeklyreport_standard') === $classname) {
			print img_picto($langs->trans('Activated'), 'switch_on');
		} else {
			print '<a class="reposition" href="'.$setupurl.'?action=setmod&token='.newToken().'&value='.urlencode($classname).'">';
			print img_picto($langs->trans('Disabled'), 'switch_off');
			print '</a>';
		}
		print '</td>';
		$tmpobject = new WeeklyReport($db);
		$tmpobject->year = (int) date('o', dol_now());
		$tmpobject->week = (int) date('W', dol_now());
		$tmpobject->entity = (int) $conf->entity;
		$nextvalue = method_exists($module, 'getNextValue') ? $module->getNextValue($tmpobject) : '';
		$htmltooltip = $langs->trans('Version').': <b>'.dol_escape_htmltag(method_exists($module, 'getVersion') ? $module->getVersion() : $moduleversion).'</b><br>';
		$htmltooltip .= $langs->trans('NextValue').': '.dol_escape_htmltag((string) $nextvalue);
		print '<td class="center">'.$form->textwithpicto('', $htmltooltip, 1, 'info').'</td>';
		print '</tr>';
	}
	closedir($handle);
}
if ($numberingfound === 0) {
	print '<tr class="oddeven"><td colspan="5"><span class="opacitymedium">'.$langs->trans('NoRecordFound').'</span></td></tr>';
}
print '</table>';
print '</div>';

print '<br>';
print load_fiche_titre($langs->trans('SAWeeklyReportDocumentModels'), '', '');
$def = array();
$sql = "SELECT nom";
$sql .= " FROM ".$db->prefix()."document_model";
$sql .= " WHERE type = '".$db->escape($documenttype)."'";
$sql .= " AND entity = ".((int) $conf->entity);
$resql = $db->query($sql);
if ($resql) {
	while ($obj = $db->fetch_object($resql)) {
		$def[] = (string) $obj->nom;
	}
	$db->free($resql);
} else {
	dol_print_error($db);
}

print '<div class="div-table-responsive-no-min">';
print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans('Name').'</td>';
print '<td>'.$langs->trans('Description').'</td>';
print '<td class="center" width="60">'.$langs->trans('Status').'</td>';
print '<td class="center" width="60">'.$langs->trans('Default').'</td>';
print '<td class="center" width="38">'.$langs->trans('ShortInfo').'</td>';
print '</tr>';

$documentmodelfound = 0;
foreach ($dirmodels as $reldir) {
	foreach (array('', '/doc') as $valdir) {
		$realpath = $reldir.'core/modules/saweeklyreport'.$valdir;
		$dir = dol_buildpath($realpath, 0);
		if (!is_dir($dir)) {
			continue;
		}
		$handle = opendir($dir);
		if (!is_resource($handle)) {
			continue;
		}
		$filelist = array();
		while (($file = readdir($handle)) !== false) {
			$filelist[] = $file;
		}
		closedir($handle);
		sort($filelist);
		foreach ($filelist as $file) {
			if (!preg_match('/^pdf_[A-Za-z0-9_]+\.modules\.php$/', $file)) {
				continue;
			}
			$classname = substr($file, 0, -12);
			require_once $dir.'/'.$file;
			if (!class_exists($classname)) {
				continue;
			}
			$module = new $classname($db);
			$moduleversion = isset($module->version) ? (string) $module->version : '';
			if ($moduleversion === 'development' && getDolGlobalInt('MAIN_FEATURES_LEVEL') < 2) {
				continue;
			}
			if ($moduleversion === 'experimental' && getDolGlobalInt('MAIN_FEATURES_LEVEL') < 1) {
				continue;
			}
			$documentmodelfound++;
			$name = $classname;
			$label = !empty($module->name) ? (string) $module->name : $name;
			$description = method_exists($module, 'info') ? $module->info($langs) : (isset($module->description) ? (string) $module->description : $label);
			$scan = !empty($module->scandir) ? (string) $module->scandir : '';
			print '<tr class="oddeven">';
			print '<td>'.dol_escape_htmltag($label).'</td>';
			print '<td>'.$description.'</td>';
			print '<td class="center">';
			if (in_array($name, $def, true)) {
				print '<a class="reposition" href="'.$setupurl.'?action=del&token='.newToken().'&value='.urlencode($name).'">';
				print img_picto($langs->trans('Enabled'), 'switch_on');
				print '</a>';
			} else {
				print '<a class="reposition" href="'.$setupurl.'?action=set&token='.newToken().'&value='.urlencode($name).'&scan_dir='.urlencode($scan).'&label='.urlencode($label).'">';
				print img_picto($langs->trans('Disabled'), 'switch_off');
				print '</a>';
			}
			print '</td>';
			print '<td class="center">';
			if (getDolGlobalString('SAWEEKLYREPORT_WEEKLYREPORT_ADDON_PDF') === $name) {
				print img_picto($langs->trans('Default'), 'on');
			} else {
				print '<a class="reposition" href="'.$setupurl.'?action=setdoc&token='.newToken().'&value='.urlencode($name).'&scan_dir='.urlencode($scan).'&label='.urlencode($label).'">';
				print img_picto($langs->trans('Disabled'), 'off');
				print '</a>';
			}
			print '</td>';
			$htmltooltip = $langs->trans('Name').': '.dol_escape_htmltag($label);
			$moduletype = !empty($module->type) ? (string) $module->type : $langs->trans('Unknown');
			$htmltooltip .= '<br>'.$langs->trans('Type').': '.dol_escape_htmltag($moduletype);
			if ($moduletype === 'pdf') {
				$pagewidth = isset($module->page_largeur) ? (string) $module->page_largeur : '';
				$pageheight = isset($module->page_hauteur) ? (string) $module->page_hauteur : '';
				$htmltooltip .= '<br>'.$langs->trans('Width').'/'.$langs->trans('Height').': '.dol_escape_htmltag($pagewidth).'/'.dol_escape_htmltag($pageheight);
			}
			$htmltooltip .= '<br>'.$langs->trans('Path').': '.dol_escape_htmltag(preg_replace('/^\//', '', $realpath).'/'.$file);
			print '<td class="center">'.$form->textwithpicto('', $htmltooltip, 1, 'info').'</td>';
			print '</tr>';
		}
	}
}
if ($documentmodelfound === 0) {
	print '<tr class="oddeven"><td colspan="5"><span class="opacitymedium">'.$langs->trans('NoRecordFound').'</span></td></tr>';
}
print '</table>';
print '</div>';

print '<br>';

print '<form method="POST" action="'.dol_escape_htmltag($setupurl).'">';
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" name="action" value="updatesettings">';

print '<table class="noborder centpercent">';
print '<tr class="liste_titre"><th colspan="2">'.$langs->trans('SAWeeklyReportKpiSettings').'</th></tr>';
print '<tr class="oddeven"><td class="titlefield">'.$langs->trans('WeeklyReportAnnualTargetPower').'</td><td><input class="flat width100 right" name="SAWEEKLYREPORT_ANNUAL_TARGET_POWER" value="'.dol_escape_htmltag(getDolGlobalString('SAWEEKLYREPORT_ANNUAL_TARGET_POWER', '846')).'"> kWc</td></tr>';
print '<tr class="oddeven"><td>'.$langs->trans('WeeklyReportWeeklyTargetPower').'</td><td><input class="flat width100 right" name="SAWEEKLYREPORT_WEEKLY_TARGET_POWER" value="'.dol_escape_htmltag(getDolGlobalString('SAWEEKLYREPORT_WEEKLY_TARGET_POWER', '18')).'"> kWc</td></tr>';
print '<tr class="oddeven"><td>'.$langs->trans('WeeklyReportMeetingDuration').'</td><td><input class="flat width100 right" name="SAWEEKLYREPORT_MEETING_DURATION" value="'.dol_escape_htmltag(getDolGlobalString('SAWEEKLYREPORT_MEETING_DURATION', '15')).'"> '.$langs->trans('Minutes').'</td></tr>';
print '<tr class="liste_titre"><th colspan="2">'.$langs->trans('SAWeeklyReportModelsSettings').'</th></tr>';
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
print '<tr class="oddeven"><td>'.$langs->trans('SAWeeklyReportPdfFreeText').'</td><td>';
$editor = new DolEditor('SAWEEKLYREPORT_FREE_TEXT', getDolGlobalString('SAWEEKLYREPORT_FREE_TEXT'), '', 140, 'dolibarr_notes', 'In', false, false, isModEnabled('fckeditor'), ROWS_3, '100%');
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
