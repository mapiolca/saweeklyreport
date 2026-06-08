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
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
dol_include_once('/saweeklyreport/class/weeklyreport.class.php');
dol_include_once('/saweeklyreport/class/weeklyreportservice.class.php');
dol_include_once('/saweeklyreport/lib/saweeklyreport.lib.php');

$langs->loadLangs(array('saweeklyreport@saweeklyreport', 'other', 'agenda', 'mails'));

$id = GETPOSTINT('id');
$ref = GETPOST('ref', 'alphanohtml');
$action = GETPOST('action', 'aZ09');
$confirm = GETPOST('confirm', 'alpha');
$cancel = GETPOST('cancel', 'alpha');
$backtopage = GETPOST('backtopage', 'alpha');

$object = new WeeklyReport($db);
$hookmanager->initHooks(array('weeklyreportcard', 'globalcard'));

if ($id > 0 || $ref !== '') {
	$result = $object->fetch($id, $ref);
	if ($result <= 0) {
		accessforbidden();
	}
}

if (!isModEnabled('saweeklyreport')) {
	accessforbidden();
}

$permissiontoread = $user->hasRight('saweeklyreport', 'weeklyreport', 'read');
$permissiontoadd = $user->hasRight('saweeklyreport', 'weeklyreport', 'write');
$permissiontodelete = $user->hasRight('saweeklyreport', 'weeklyreport', 'delete');
$permissiontovalidate = $user->hasRight('saweeklyreport', 'weeklyreport', 'validate');

if (!$permissiontoread) {
	accessforbidden();
}

$upload_dir = '';
$modulepart = 'saweeklyreport';
$relativepathwithnofile = '';
if ($object->id > 0) {
	$upload_dir = $object->getDocumentDir();
	$relativepathwithnofile = ((int) $object->entity).'/weeklyreport/'.dol_sanitizeFileName($object->ref).'/';
	include DOL_DOCUMENT_ROOT.'/core/actions_linkedfiles.inc.php';
}

$tokenok = (GETPOST('token', 'alpha') === currentToken());

$parameters = array('id' => $id);
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action);
if ($reshook < 0) {
	setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
}

if (empty($reshook)) {
	if ($cancel) {
		header('Location: '.dol_buildpath('/saweeklyreport/weeklyreport_list.php', 1));
		exit;
	}

	if ($action === 'add' && $permissiontoadd) {
		if (!$tokenok) {
			accessforbidden('Invalid token');
		}
		$year = GETPOSTINT('year');
		$week = GETPOSTINT('week');
		if ($year < 2000 || $week < 1 || $week > 53) {
			setEventMessages($langs->trans('ErrorFieldRequired', $langs->trans('Week')), null, 'errors');
			$action = 'create';
		} else {
			$existing = new WeeklyReport($db);
			if ($existing->fetchByYearWeek($year, $week, 1) > 0) {
				setEventMessages($langs->trans('WeeklyReportAlreadyExists'), null, 'warnings');
				header('Location: '.dol_buildpath('/saweeklyreport/weeklyreport_card.php', 1).'?id='.(int) $existing->id);
				exit;
			}
			$object->year = $year;
			$object->week = $week;
			$object->label = GETPOST('label', 'alphanohtml');
			$result = $object->create($user);
			if ($result < 0) {
				setEventMessages($object->error, $object->errors, 'errors');
				$action = 'create';
			} else {
				header('Location: '.dol_buildpath('/saweeklyreport/weeklyreport_card.php', 1).'?id='.(int) $object->id);
				exit;
			}
		}
	}

	if ($action === 'update' && $permissiontoadd && $object->id > 0) {
		if (!$tokenok) {
			accessforbidden('Invalid token');
		}
		$object->label = GETPOST('label', 'alphanohtml');
		$object->meeting_duration = GETPOSTINT('meeting_duration');
		$object->annual_target_power = price2num(GETPOST('annual_target_power', 'alphanohtml'));
		$object->weekly_target_power = price2num(GETPOST('weekly_target_power', 'alphanohtml'));
		$object->workweeks_elapsed = price2num(GETPOST('workweeks_elapsed', 'alphanohtml'));
		$object->technician_days = price2num(GETPOST('technician_days', 'alphanohtml'));
		$object->technician_workdays = price2num(GETPOST('technician_workdays', 'alphanohtml'));
		$object->previous_week_feedback = GETPOST('previous_week_feedback', 'restricthtml');
		$object->field_returns = GETPOST('field_returns', 'restricthtml');
		$object->current_week_goal = GETPOST('current_week_goal', 'restricthtml');
		$object->safety_message = GETPOST('safety_message', 'restricthtml');
		$object->vehicle_loading_reminder = GETPOST('vehicle_loading_reminder', 'restricthtml');
		$result = $object->update($user);
		if ($result < 0) {
			setEventMessages($object->error, $object->errors, 'errors');
			$action = 'edit';
		} else {
			setEventMessages($langs->trans('RecordSaved'), null, 'mesgs');
			header('Location: '.dol_buildpath('/saweeklyreport/weeklyreport_card.php', 1).'?id='.(int) $object->id);
			exit;
		}
	}

	if ($action === 'updateservices' && $permissiontoadd && $object->id > 0) {
		if (!$tokenok) {
			accessforbidden('Invalid token');
		}
		$lineids = GETPOST('lineid', 'array:int');
		$labels = GETPOST('service_label', 'array');
		$types = GETPOST('service_type', 'array');
		$descriptions = GETPOST('service_description', 'array');
		$error = 0;
		foreach ((array) $lineids as $index => $lineid) {
			$line = new WeeklyReportService($db);
			if ($line->fetch((int) $lineid) > 0 && (int) $line->fk_weeklyreport === (int) $object->id) {
				$line->label = dol_string_nohtmltag((string) ($labels[$index] ?? ''));
				$line->service_type = dol_string_nohtmltag((string) ($types[$index] ?? ''));
				$line->description = dol_string_nohtmltag((string) ($descriptions[$index] ?? ''));
				$line->position = $index + 1;
				if ($line->update($user, 1) < 0) {
					$error++;
				}
			}
		}
		if ($error) {
			setEventMessages($langs->trans('Error'), null, 'errors');
		} else {
			setEventMessages($langs->trans('RecordSaved'), null, 'mesgs');
		}
		header('Location: '.dol_buildpath('/saweeklyreport/weeklyreport_card.php', 1).'?id='.(int) $object->id);
		exit;
	}

	if ($action === 'addserviceline' && $permissiontoadd && $object->id > 0) {
		if (!$tokenok) {
			accessforbidden('Invalid token');
		}
		$line = new WeeklyReportService($db);
		$line->entity = (int) $object->entity;
		$line->fk_weeklyreport = (int) $object->id;
		$line->service_type = GETPOST('new_service_type', 'alphanohtml');
		$line->label = GETPOST('new_service_label', 'alphanohtml');
		$line->description = GETPOST('new_service_description', 'restricthtml');
		$line->position = count($object->lines) + 1;
		if ($line->label === '' || $line->create($user, 1) < 0) {
			setEventMessages($line->error ?: $langs->trans('ErrorFieldRequired', $langs->trans('Label')), $line->errors, 'errors');
		}
		header('Location: '.dol_buildpath('/saweeklyreport/weeklyreport_card.php', 1).'?id='.(int) $object->id);
		exit;
	}

	if ($action === 'deleteserviceline' && $permissiontoadd && $object->id > 0) {
		if (!$tokenok) {
			accessforbidden('Invalid token');
		}
		$line = new WeeklyReportService($db);
		if ($line->fetch(GETPOSTINT('lineid')) > 0 && (int) $line->fk_weeklyreport === (int) $object->id) {
			$line->delete($user, 1);
		}
		header('Location: '.dol_buildpath('/saweeklyreport/weeklyreport_card.php', 1).'?id='.(int) $object->id);
		exit;
	}

	if ($action === 'refreshdata' && $permissiontoadd && $object->id > 0) {
		if (!$tokenok) {
			accessforbidden('Invalid token');
		}
		$result = $object->refreshData($user, 1);
		if ($result < 0) {
			setEventMessages($object->error, $object->errors, 'errors');
		} else {
			setEventMessages($langs->trans('WeeklyReportDataRefreshed'), null, 'mesgs');
		}
		header('Location: '.dol_buildpath('/saweeklyreport/weeklyreport_card.php', 1).'?id='.(int) $object->id);
		exit;
	}

	if ($action === 'generatepptx' && $permissiontoadd && $object->id > 0) {
		if (!$tokenok) {
			accessforbidden('Invalid token');
		}
		$result = $object->generatePptx($langs);
		if ($result < 0) {
			setEventMessages($langs->trans($object->error), $object->errors, 'errors');
		} else {
			setEventMessages($langs->trans('WeeklyReportPptxGenerated'), null, 'mesgs');
		}
		header('Location: '.dol_buildpath('/saweeklyreport/weeklyreport_card.php', 1).'?id='.(int) $object->id);
		exit;
	}

	if ($action === 'validate' && $permissiontovalidate && $object->id > 0) {
		if (!$tokenok) {
			accessforbidden('Invalid token');
		}
		$object->validate($user);
		header('Location: '.dol_buildpath('/saweeklyreport/weeklyreport_card.php', 1).'?id='.(int) $object->id);
		exit;
	}

	if ($action === 'setdraft' && $permissiontovalidate && $object->id > 0) {
		if (!$tokenok) {
			accessforbidden('Invalid token');
		}
		$object->setDraft($user);
		header('Location: '.dol_buildpath('/saweeklyreport/weeklyreport_card.php', 1).'?id='.(int) $object->id);
		exit;
	}

	if ($action === 'cancelreport' && $permissiontovalidate && $object->id > 0) {
		if (!$tokenok) {
			accessforbidden('Invalid token');
		}
		$object->cancel($user);
		header('Location: '.dol_buildpath('/saweeklyreport/weeklyreport_card.php', 1).'?id='.(int) $object->id);
		exit;
	}

	if ($action === 'confirm_delete' && $confirm === 'yes' && $permissiontodelete && $object->id > 0) {
		if (!$tokenok) {
			accessforbidden('Invalid token');
		}
		$object->delete($user);
		header('Location: '.dol_buildpath('/saweeklyreport/weeklyreport_list.php', 1));
		exit;
	}
}

$form = new Form($db);
$formfile = new FormFile($db);
$cardurl = dol_buildpath('/saweeklyreport/weeklyreport_card.php', 1);
$title = $langs->trans('WeeklyReport');
if ($action === 'create') {
	$title = $langs->trans('NewWeeklyReport');
}

llxHeader('', $title, '', '', 0, 0, '', '', '', 'mod-saweeklyreport page-card');

if ($action === 'create') {
	if (!$permissiontoadd) {
		accessforbidden();
	}
	$now = dol_now();
	$currentyear = (int) date('o', $now);
	$currentweek = (int) date('W', $now);
	print load_fiche_titre($title, '', $object->picto);
	print '<form method="POST" action="'.dol_escape_htmltag($cardurl).'">';
	print '<input type="hidden" name="token" value="'.newToken().'">';
	print '<input type="hidden" name="action" value="add">';
	print dol_get_fiche_head(array(), '');
	print '<table class="border centpercent tableforfieldcreate">';
	print '<tr><td class="titlefieldcreate fieldrequired">'.$langs->trans('Year').'</td><td><input class="flat width75" name="year" value="'.dol_escape_htmltag($currentyear).'"></td></tr>';
	print '<tr><td class="fieldrequired">'.$langs->trans('Week').'</td><td><input class="flat width75" name="week" value="'.dol_escape_htmltag($currentweek).'"></td></tr>';
	print '<tr><td>'.$langs->trans('Label').'</td><td><input class="flat minwidth300" name="label" value=""></td></tr>';
	print '</table>';
	print dol_get_fiche_end();
	print $form->buttonsSaveCancel('Create');
	print '</form>';
} elseif ($object->id > 0) {
	$head = weeklyreportPrepareHead($object);
	print dol_get_fiche_head($head, 'card', $langs->trans('WeeklyReport'), -1, $object->picto);

	$formconfirm = '';
	if ($action === 'delete') {
		$formconfirm = $form->formconfirm($cardurl.'?id='.(int) $object->id.'&token='.newToken(), $langs->trans('Delete'), $langs->trans('ConfirmDeleteObject'), 'confirm_delete', '', 0, 1);
	}
	print $formconfirm;

	$linkback = '<a href="'.dol_buildpath('/saweeklyreport/weeklyreport_list.php', 1).'">'.$langs->trans('BackToList').'</a>';
	dol_banner_tab($object, 'ref', $linkback, 1, 'ref', 'ref');

	if ($action === 'edit') {
		print '<form method="POST" action="'.dol_escape_htmltag($cardurl).'">';
		print '<input type="hidden" name="token" value="'.newToken().'">';
		print '<input type="hidden" name="action" value="update">';
		print '<input type="hidden" name="id" value="'.((int) $object->id).'">';
	}

	print '<div class="fichecenter">';
	print '<div class="fichehalfleft">';
	print '<table class="border centpercent tableforfield">';
	print '<tr><td class="titlefield">'.$langs->trans('Label').'</td><td>'.($action === 'edit' ? '<input class="flat minwidth300" name="label" value="'.dol_escape_htmltag($object->label).'">' : dol_escape_htmltag($object->label)).'</td></tr>';
	print '<tr><td>'.$langs->trans('Year').'</td><td>'.((int) $object->year).'</td></tr>';
	print '<tr><td>'.$langs->trans('Week').'</td><td>'.((int) $object->week).'</td></tr>';
	print '<tr><td>'.$langs->trans('WeeklyReportPeriod').'</td><td>'.dol_print_date($object->period_start, 'day').' - '.dol_print_date($object->period_end, 'day').'</td></tr>';
	print '<tr><td>'.$langs->trans('Status').'</td><td>'.$object->getLibStatut(4).'</td></tr>';
	print '<tr><td>'.$langs->trans('WeeklyReportMeetingDuration').'</td><td>'.($action === 'edit' ? '<input class="flat width75 right" name="meeting_duration" value="'.dol_escape_htmltag($object->meeting_duration).'">' : ((int) $object->meeting_duration).' '.$langs->trans('Minutes')).'</td></tr>';
	print '</table>';
	print '</div>';

	print '<div class="fichehalfright">';
	print '<table class="border centpercent tableforfield">';
	print '<tr><td class="titlefield">'.$langs->trans('WeeklyReportWeekInstalledPower').'</td><td class="right">'.price($object->week_installed_power).' kWc</td></tr>';
	print '<tr><td>'.$langs->trans('WeeklyReportMonthInstalledPower').'</td><td class="right">'.price($object->month_installed_power).' kWc</td></tr>';
	print '<tr><td>'.$langs->trans('WeeklyReportAnnualInstalledPower').'</td><td class="right">'.price($object->annual_installed_power).' kWc</td></tr>';
	print '<tr><td>'.$langs->trans('WeeklyReportAnnualTargetPower').'</td><td class="right">'.($action === 'edit' ? '<input class="flat width100 right" name="annual_target_power" value="'.dol_escape_htmltag(price($object->annual_target_power)).'">' : price($object->annual_target_power).' kWc').'</td></tr>';
	print '<tr><td>'.$langs->trans('WeeklyReportWeeklyTargetPower').'</td><td class="right">'.($action === 'edit' ? '<input class="flat width100 right" name="weekly_target_power" value="'.dol_escape_htmltag(price($object->weekly_target_power)).'">' : price($object->weekly_target_power).' kWc').'</td></tr>';
	print '<tr><td>'.$langs->trans('WeeklyReportAnnualCompletionRate').'</td><td class="right">'.price($object->annual_completion_rate).'%</td></tr>';
	print '<tr><td>'.$langs->trans('WeeklyReportWorkweeksElapsed').'</td><td class="right">'.($action === 'edit' ? '<input class="flat width100 right" name="workweeks_elapsed" value="'.dol_escape_htmltag(price($object->workweeks_elapsed)).'">' : price($object->workweeks_elapsed)).'</td></tr>';
	print '<tr><td>'.$langs->trans('WeeklyReportAnnualAveragePower').'</td><td class="right">'.price($object->annual_average_power).' kWc/sem.</td></tr>';
	print '</table>';
	print '</div>';
	print '</div>';
	print '<div class="clearboth"></div>';

	print '<br>';
	print '<table class="border centpercent tableforfield">';
	print '<tr><td class="titlefield">'.$langs->trans('WeeklyReportTechnicianDays').'</td><td>'.($action === 'edit' ? '<input class="flat width100 right" name="technician_days" value="'.dol_escape_htmltag(price($object->technician_days)).'">' : price($object->technician_days)).'</td><td>'.$langs->trans('WeeklyReportTechnicianWorkdays').'</td><td>'.($action === 'edit' ? '<input class="flat width100 right" name="technician_workdays" value="'.dol_escape_htmltag(price($object->technician_workdays)).'">' : price($object->technician_workdays)).'</td><td>'.$langs->trans('WeeklyReportTechnicianAverage').'</td><td>'.price($object->technician_average).'</td></tr>';
	print '</table>';

	print '<br>';
	print '<table class="border centpercent tableforfield">';
	foreach (array('previous_week_feedback', 'field_returns', 'current_week_goal', 'safety_message', 'vehicle_loading_reminder') as $field) {
		print '<tr><td class="titlefield">'.$langs->trans($object->fields[$field]['label']).'</td><td>';
		if ($action === 'edit') {
			print '<textarea class="flat centpercent" rows="3" name="'.$field.'">'.dol_escape_htmltag($object->$field).'</textarea>';
		} else {
			print dol_nl2br(dol_escape_htmltag($object->$field));
		}
		print '</td></tr>';
	}
	print '</table>';

	if ($action === 'edit') {
		print '<div class="center">'.$form->buttonsSaveCancel().'</div>';
		print '</form>';
	}

	print '<br>';
	print load_fiche_titre($langs->trans('WeeklyReportServiceLines'), '', 'fa-wrench');
	if ($permissiontoadd) {
		print '<form method="POST" action="'.dol_escape_htmltag($cardurl).'">';
		print '<input type="hidden" name="token" value="'.newToken().'">';
		print '<input type="hidden" name="action" value="updateservices">';
		print '<input type="hidden" name="id" value="'.((int) $object->id).'">';
	}
	print '<table class="noborder centpercent">';
	print '<tr class="liste_titre"><th>'.$langs->trans('Type').'</th><th>'.$langs->trans('Label').'</th><th>'.$langs->trans('Description').'</th><th>'.$langs->trans('Source').'</th><th></th></tr>';
	foreach ($object->lines as $index => $line) {
		print '<tr class="oddeven">';
		print '<td>';
		if ($permissiontoadd) {
			print '<input type="hidden" name="lineid[]" value="'.((int) $line->id).'">';
			print '<input class="flat maxwidth150" name="service_type[]" value="'.dol_escape_htmltag($line->service_type).'">';
		} else {
			print dol_escape_htmltag($line->service_type);
		}
		print '</td><td>';
		print ($permissiontoadd ? '<input class="flat minwidth200" name="service_label[]" value="'.dol_escape_htmltag($line->label).'">' : dol_escape_htmltag($line->label));
		print '</td><td>';
		print ($permissiontoadd ? '<textarea class="flat centpercent" rows="2" name="service_description[]">'.dol_escape_htmltag($line->description).'</textarea>' : dol_nl2br(dol_escape_htmltag($line->description)));
		print '</td><td>'.dol_escape_htmltag($line->source_element).($line->source_id ? ' #'.((int) $line->source_id) : '').'</td><td class="right">';
		if ($permissiontoadd) {
			print '<a class="reposition" href="'.$cardurl.'?id='.((int) $object->id).'&action=deleteserviceline&lineid='.((int) $line->id).'&token='.newToken().'">'.img_delete().'</a>';
		}
		print '</td></tr>';
	}
	print '</table>';
	if ($permissiontoadd) {
		print '<div class="center"><input type="submit" class="button" value="'.$langs->trans('Save').'"></div>';
		print '</form>';

		print '<form method="POST" action="'.dol_escape_htmltag($cardurl).'">';
		print '<input type="hidden" name="token" value="'.newToken().'">';
		print '<input type="hidden" name="action" value="addserviceline">';
		print '<input type="hidden" name="id" value="'.((int) $object->id).'">';
		print '<table class="noborder centpercent">';
		print '<tr class="oddeven"><td class="titlefield">'.$langs->trans('Add').'</td><td><input class="flat maxwidth150" name="new_service_type" placeholder="'.$langs->trans('Type').'"></td><td><input class="flat minwidth300" name="new_service_label" placeholder="'.$langs->trans('Label').'"></td><td><input class="flat minwidth300" name="new_service_description" placeholder="'.$langs->trans('Description').'"></td><td><input type="submit" class="button small" value="'.$langs->trans('Add').'"></td></tr>';
		print '</table>';
		print '</form>';
	}

	print dol_get_fiche_end();

	if ($action !== 'edit') {
		print '<br>';
		$relativepath = rtrim($relativepathwithnofile, '/');
		$urlsource = $cardurl.'?id='.((int) $object->id);
		print $formfile->showdocuments($modulepart, $relativepath, $upload_dir, $urlsource, 0, $permissiontoadd, '', 1, 0, 0, 28, 0, 'id='.((int) $object->id), '', '', $langs->defaultlang, '', $object, 0, 'remove_file');

		if (isModEnabled('agenda') && ($user->hasRight('agenda', 'myactions', 'read') || $user->hasRight('agenda', 'allactions', 'read'))) {
			include_once DOL_DOCUMENT_ROOT.'/core/class/html.formactions.class.php';
			$formactions = new FormActions($db);
			$maxevent = 10;
			$morehtmlcenter = dolGetButtonTitle($langs->trans('SeeAll'), '', 'fa fa-bars imgforviewmode', dol_buildpath('/saweeklyreport/weeklyreport_agenda.php', 1).'?id='.((int) $object->id));
			$typeelement = $object->element.(!empty($object->module) ? '@'.$object->module : '');
			print '<br>';
			$formactions->showactions($object, $typeelement, 0, 1, '', $maxevent, '', $morehtmlcenter);
		}
	}

	if ($action !== 'edit') {
		print '<div class="tabsAction">';
		if ($permissiontoadd) {
			print '<a class="butAction" href="'.$cardurl.'?id='.((int) $object->id).'&action=edit">'.$langs->trans('Modify').'</a>';
			print '<a class="butAction" href="'.$cardurl.'?id='.((int) $object->id).'&action=refreshdata&token='.newToken().'">'.$langs->trans('WeeklyReportRefreshData').'</a>';
			print '<a class="butAction" href="'.$cardurl.'?id='.((int) $object->id).'&action=generatepptx&token='.newToken().'">'.$langs->trans('WeeklyReportGeneratePptx').'</a>';
		}
		if ($permissiontovalidate && (int) $object->status === WeeklyReport::STATUS_DRAFT) {
			print '<a class="butAction" href="'.$cardurl.'?id='.((int) $object->id).'&action=validate&token='.newToken().'">'.$langs->trans('Validate').'</a>';
		}
		if ($permissiontovalidate && (int) $object->status === WeeklyReport::STATUS_VALIDATED) {
			print '<a class="butAction" href="'.$cardurl.'?id='.((int) $object->id).'&action=setdraft&token='.newToken().'">'.$langs->trans('SetToDraft').'</a>';
		}
		if ($permissiontovalidate && (int) $object->status !== WeeklyReport::STATUS_CANCELED) {
			print '<a class="butActionDelete" href="'.$cardurl.'?id='.((int) $object->id).'&action=cancelreport&token='.newToken().'">'.$langs->trans('Cancel').'</a>';
		}
		if ($permissiontodelete) {
			print '<a class="butActionDelete" href="'.$cardurl.'?id='.((int) $object->id).'&action=delete">'.$langs->trans('Delete').'</a>';
		}
		print '</div>';
	}
} else {
	print load_fiche_titre($langs->trans('WeeklyReport'), '', $object->picto);
	print '<div class="opacitymedium">'.$langs->trans('NoRecordFound').'</div>';
}

llxFooter();
$db->close();
