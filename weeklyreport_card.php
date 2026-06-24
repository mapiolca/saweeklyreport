<?php
/* Copyright (C) 2026  Pierre Ardoin <developpeur@lesmetiersdubatiment.fr> */

if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && in_array($_GET['action'], array('create', 'edit', 'editfield', 'delete'), true) && empty($_GET['token'])) {
	$_GET['mode'] = $_GET['action'];
	$_REQUEST['mode'] = $_GET['action'];
	unset($_GET['action'], $_REQUEST['action']);
}

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
require_once DOL_DOCUMENT_ROOT.'/core/class/doleditor.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/ajax.lib.php';
dol_include_once('/saweeklyreport/class/weeklyreport.class.php');
dol_include_once('/saweeklyreport/class/weeklyreportservice.class.php');
dol_include_once('/saweeklyreport/class/saweeklyreporttickethelper.class.php');
dol_include_once('/saweeklyreport/core/modules/saweeklyreport/modules_weeklyreport.php');
dol_include_once('/saweeklyreport/lib/saweeklyreport.lib.php');

/**
 * Return editable fields allowed from the card.
 *
 * @return	array<string,string>
 */
function saweeklyreportEditableFields()
{
	return array(
		'label' => 'string',
		'meeting_duration' => 'integer',
		'technician_days' => 'number',
		'technician_workdays' => 'number',
		'previous_week_feedback' => 'html',
		'field_returns' => 'html',
		'current_week_goal' => 'html',
		'safety_message' => 'html',
		'vehicle_loading_reminder' => 'html',
	);
}

/**
 * Render a Dolibarr editor when WYSIWYG is enabled, otherwise a native textarea.
 *
 * @param	string	$htmlname	Field name
 * @param	string	$value		Current value
 * @param	int		$rows		Rows
 * @return	string
 */
function saweeklyreportRenderEditor($htmlname, $value, $rows = ROWS_3)
{
	$editor = new DolEditor($htmlname, $value, '', 140, 'dolibarr_notes', 'In', false, false, isModEnabled('fckeditor'), $rows, '100%');

	return $editor->Create(1);
}

/**
 * Render inline edition icon.
 *
 * @param	string			$cardurl	URL
 * @param	WeeklyReport	$object		Report
 * @param	string			$field		Field
 * @param	bool			$allowed	Allowed
 * @param	string			$action		Current action
 * @param	string			$editfield	Inline field
 * @return	string
 */
function saweeklyreportEditFieldButton($cardurl, $object, $field, $allowed, $action = '', $editfield = '')
{
	global $langs;

	if (!$allowed || ($action === 'editfield' && $editfield === $field)) {
		return '';
	}

	$url = $cardurl.'?id='.((int) $object->id).'&action=editfield&field='.urlencode($field);

	return '<a class="editfielda reposition" href="'.dol_escape_htmltag($url).'">'.img_edit($langs->trans('Edit'), 1).'</a>';
}

/**
 * Render field label with the inline edition icon aligned on the right side of the label cell.
 *
 * @param	string			$labelhtml	Label HTML
 * @param	string			$cardurl	URL
 * @param	WeeklyReport	$object		Report
 * @param	string			$field		Field
 * @param	bool			$allowed	Allowed
 * @param	string			$action		Current action
 * @param	string			$editfield	Inline field
 * @return	string
 */
function saweeklyreportRenderEditableFieldLabel($labelhtml, $cardurl, $object, $field, $allowed, $action = '', $editfield = '')
{
	$button = saweeklyreportEditFieldButton($cardurl, $object, $field, $allowed, $action, $editfield);
	if ($button === '') {
		return $labelhtml;
	}

	return '<table class="nobordernopadding centpercent"><tr><td class="nowrap">'.$labelhtml.'</td><td class="right">'.$button.'</td></tr></table>';
}

/**
 * Render either a standalone inline edit form, the bulk edit input, or the display value.
 *
 * @param	string			$cardurl			Card URL
 * @param	WeeklyReport	$object				Report
 * @param	string			$field				Field
 * @param	string			$inputhtml			Input HTML
 * @param	string			$displayhtml		Display HTML
 * @param	bool			$inlineeditallowed	Can edit inline
 * @param	string			$action				Current action
 * @param	string			$editfield			Inline field
 * @return	string
 */
function saweeklyreportRenderEditableField($cardurl, $object, $field, $inputhtml, $displayhtml, $inlineeditallowed, $action, $editfield)
{
	global $langs;

	if ($action === 'edit') {
		return $inputhtml;
	}
	if ($action === 'editfield' && $editfield === $field) {
		$html = '<form method="POST" action="'.dol_escape_htmltag($cardurl).'">';
		$html .= '<input type="hidden" name="token" value="'.newToken().'">';
		$html .= '<input type="hidden" name="action" value="updatefield">';
		$html .= '<input type="hidden" name="id" value="'.((int) $object->id).'">';
		$html .= '<input type="hidden" name="field" value="'.dol_escape_htmltag($field).'">';
		$html .= $inputhtml;
		$html .= '<div class="center"><input type="submit" class="button small" value="'.dol_escape_htmltag($langs->trans('Save')).'"> ';
		$html .= '<a class="button small" href="'.dol_escape_htmltag($cardurl.'?id='.((int) $object->id)).'">'.dol_escape_htmltag($langs->trans('Cancel')).'</a></div>';
		$html .= '</form>';

		return $html;
	}

	return $displayhtml;
}

/**
 * Clean a submitted editable field value.
 *
 * @param	string	$field	Field
 * @param	string	$type	Type
 * @return	string|int|float
 */
function saweeklyreportGetSubmittedFieldValue($field, $type)
{
	if ($type === 'integer') {
		return GETPOSTINT($field);
	}
	if ($type === 'number') {
		return price2num(GETPOST($field, 'alphanohtml'));
	}
	if ($type === 'html') {
		return dol_htmlcleanlastbr(GETPOST($field, 'restricthtml'));
	}

	return GETPOST($field, 'alphanohtml');
}

$langs->loadLangs(array('saweeklyreport@saweeklyreport', 'other', 'agenda', 'mails', 'ticket', 'interventions'));

$id = GETPOSTINT('id');
$ref = GETPOST('ref', 'alphanohtml');
$action = GETPOST('action', 'aZ09');
$mode = GETPOST('mode', 'aZ09');
$confirm = GETPOST('confirm', 'alpha');
$cancel = GETPOST('cancel', 'alpha');
$backtopage = GETPOST('backtopage', 'alpha');
$editfield = GETPOST('field', 'aZ09');
if ($action === '' && in_array($mode, array('create', 'edit', 'editfield', 'delete'), true)) {
	$action = $mode;
}

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

$permissiontoread = saweeklyreportCanDo($user, $object, 'read');
$permissiontoadd = saweeklyreportCanDo($user, $object, 'write');
$permissiontodelete = saweeklyreportCanDo($user, $object, 'delete');
$permissiontovalidate = saweeklyreportCanDo($user, $object, 'validate');
$permissiontoexport = saweeklyreportCanDo($user, $object, 'export');
$permissiontogeneratedoc = ($permissiontoadd || $permissiontoexport);

if (!$permissiontoread) {
	accessforbidden();
}

$upload_dir = '';
$modulepart = 'saweeklyreport';
$relativepathwithnofile = '';
if ($object->id > 0) {
	$upload_dir = $object->getDocumentDir();
	$relativepathwithnofile = $object->getDocumentRelativeDir().'/';
	if (!is_dir($upload_dir) && is_dir($object->getLegacyDocumentDir())) {
		$upload_dir = $object->getLegacyDocumentDir();
		$relativepathwithnofile = $object->getLegacyDocumentRelativeDir().'/';
	}
	include DOL_DOCUMENT_ROOT.'/core/actions_linkedfiles.inc.php';
}

$tokenok = (GETPOST('token', 'alpha') === currentToken());

$parameters = array('id' => $id);
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action);
if ($reshook < 0) {
	setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
}

if (empty($reshook)) {
	if ($object->id > 0) {
		$usercangeneratedoc = $permissiontogeneratedoc;
		$permissiondellink = $permissiontoadd;
		if (in_array($action, array('addlink', 'addlinkbyref', 'dellink'), true) && !$tokenok) {
			accessforbidden('Invalid token');
		}
		include DOL_DOCUMENT_ROOT.'/core/actions_dellink.inc.php';
		include DOL_DOCUMENT_ROOT.'/core/actions_builddoc.inc.php';
	}

	if ($cancel) {
		$cancelurl = dol_buildpath('/saweeklyreport/weeklyreport_list.php', 1);
		if ($object->id > 0) {
			$cancelurl = dol_buildpath('/saweeklyreport/weeklyreport_card.php', 1).'?id='.(int) $object->id;
		}
		header('Location: '.$cancelurl);
		exit;
	}

	if ($action === 'add' && $permissiontoadd) {
		if (!$tokenok) {
			accessforbidden('Invalid token');
		}
		$year = GETPOSTINT('year');
		$week = GETPOSTINT('week');
		if ($year < 2000 || $week < 1 || $week > 53) {
			setEventMessages($langs->transnoentitiesnoconv('ErrorFieldRequired', $langs->transnoentitiesnoconv('Week')), null, 'errors');
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
		if ((int) $object->status !== WeeklyReport::STATUS_DRAFT) {
			accessforbidden();
		}
		$object->label = GETPOST('label', 'alphanohtml');
		$object->meeting_duration = GETPOSTINT('meeting_duration');
		$object->technician_days = price2num(GETPOST('technician_days', 'alphanohtml'));
		$object->technician_workdays = price2num(GETPOST('technician_workdays', 'alphanohtml'));
		$object->previous_week_feedback = dol_htmlcleanlastbr(GETPOST('previous_week_feedback', 'restricthtml'));
		$object->field_returns = dol_htmlcleanlastbr(GETPOST('field_returns', 'restricthtml'));
		$object->current_week_goal = dol_htmlcleanlastbr(GETPOST('current_week_goal', 'restricthtml'));
		$object->safety_message = dol_htmlcleanlastbr(GETPOST('safety_message', 'restricthtml'));
		$object->vehicle_loading_reminder = dol_htmlcleanlastbr(GETPOST('vehicle_loading_reminder', 'restricthtml'));
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

	if ($action === 'updatefield' && $permissiontoadd && $object->id > 0) {
		if (!$tokenok) {
			accessforbidden('Invalid token');
		}
		if ((int) $object->status !== WeeklyReport::STATUS_DRAFT) {
			accessforbidden();
		}
		$editablefields = saweeklyreportEditableFields();
		if (empty($editablefields[$editfield])) {
			accessforbidden();
		}
		$object->$editfield = saweeklyreportGetSubmittedFieldValue($editfield, $editablefields[$editfield]);
		$result = $object->update($user);
		if ($result < 0) {
			setEventMessages($object->error, $object->errors, 'errors');
			$action = 'editfield';
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
		if ((int) $object->status !== WeeklyReport::STATUS_DRAFT) {
			accessforbidden();
		}
		$lineids = GETPOST('lineid', 'array:int');
		$labels = GETPOST('service_label', 'array');
		$types = GETPOST('service_type', 'array');
		$error = 0;
		$errors = array();
		foreach ((array) $lineids as $index => $lineid) {
			$line = new WeeklyReportService($db);
			if ($line->fetch((int) $lineid) > 0 && (int) $line->fk_weeklyreport === (int) $object->id) {
				if ((string) $line->source_element === 'ticket') {
					continue;
				}
				$line->label = dol_string_nohtmltag((string) ($labels[$index] ?? ''));
				$line->service_type = dol_string_nohtmltag((string) ($types[$index] ?? ''));
				$line->ticket_category_code = '';
				$line->ticket_severity_code = '';
				$line->description = dol_htmlcleanlastbr(GETPOST('service_description_'.((int) $lineid), 'restricthtml'));
				$line->position = $index + 1;
				if ($line->update($user, 1) < 0) {
					$error++;
					if (!empty($line->error)) {
						$errors[] = $line->error;
					}
					$errors = array_merge($errors, (array) $line->errors);
				}
			}
		}
		if ($error) {
			setEventMessages($langs->trans('Error'), $errors, 'errors');
		} else {
			setEventMessages($langs->trans('RecordSaved'), null, 'mesgs');
		}
		header('Location: '.dol_buildpath('/saweeklyreport/weeklyreport_card.php', 1).'?id='.(int) $object->id);
		exit;
	}

	if ($action === 'addticketline' && $permissiontoadd && $object->id > 0) {
		if (!$tokenok) {
			accessforbidden('Invalid token');
		}
		if ((int) $object->status !== WeeklyReport::STATUS_DRAFT) {
			accessforbidden();
		}
		$result = $object->addTicketLine(GETPOSTINT('ticketid'), $user);
		if ($result < 0) {
			setEventMessages($object->error, $object->errors, 'errors');
		} else {
			setEventMessages($langs->trans('RecordSaved'), null, 'mesgs');
		}
		header('Location: '.dol_buildpath('/saweeklyreport/weeklyreport_card.php', 1).'?id='.(int) $object->id);
		exit;
	}

	if ($action === 'addserviceline') {
		accessforbidden();
	}

	if ($action === 'deleteserviceline' && $permissiontoadd && $object->id > 0) {
		if (!$tokenok) {
			accessforbidden('Invalid token');
		}
		if ((int) $object->status !== WeeklyReport::STATUS_DRAFT) {
			accessforbidden();
		}
		$result = $object->detachServiceLine(GETPOSTINT('lineid'), $user);
		if ($result < 0) {
			setEventMessages($object->error, $object->errors, 'errors');
		}
		header('Location: '.dol_buildpath('/saweeklyreport/weeklyreport_card.php', 1).'?id='.(int) $object->id);
		exit;
	}

	if ($action === 'refreshdata' && $permissiontoadd && $object->id > 0) {
		if (!$tokenok) {
			accessforbidden('Invalid token');
		}
		if ((int) $object->status !== WeeklyReport::STATUS_DRAFT) {
			accessforbidden();
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

	if ($action === 'generatepptx' && $permissiontogeneratedoc && $object->id > 0) {
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
	if (in_array($action, array('edit', 'editfield'), true) && (int) $object->status !== WeeklyReport::STATUS_DRAFT) {
		$action = '';
	}
	$inlineeditallowed = ($permissiontoadd && (int) $object->status === WeeklyReport::STATUS_DRAFT && $action !== 'edit');
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
	print '<tr><td class="titlefieldmiddle nowrap">'.saweeklyreportRenderEditableFieldLabel($langs->trans('Label'), $cardurl, $object, 'label', $inlineeditallowed, $action, $editfield).'</td><td class="right">'.saweeklyreportRenderEditableField($cardurl, $object, 'label', '<input class="flat minwidth300" name="label" value="'.dol_escape_htmltag($object->label).'">', dol_escape_htmltag($object->label), $inlineeditallowed, $action, $editfield).'</td></tr>';
	print '<tr><td class="titlefieldmiddle nowrap">'.$langs->trans('Year').'</td><td class="right">'.((int) $object->year).'</td></tr>';
	print '<tr><td class="titlefieldmiddle nowrap">'.$langs->trans('Week').'</td><td class="right">'.((int) $object->week).'</td></tr>';
	print '<tr><td class="titlefieldmiddle nowrap">'.$langs->trans('WeeklyReportPeriod').'</td><td class="right">'.dol_print_date($object->period_start, 'day').' - '.dol_print_date($object->period_end, 'day').'</td></tr>';
	print '<tr><td class="titlefieldmiddle nowrap">'.saweeklyreportRenderEditableFieldLabel($langs->trans('WeeklyReportMeetingDuration'), $cardurl, $object, 'meeting_duration', $inlineeditallowed, $action, $editfield).'</td><td class="right">'.saweeklyreportRenderEditableField($cardurl, $object, 'meeting_duration', '<input class="flat width75 right" name="meeting_duration" value="'.dol_escape_htmltag($object->meeting_duration).'">', ((int) $object->meeting_duration).' '.$langs->trans('Minutes'), $inlineeditallowed, $action, $editfield).'</td></tr>';
	print '<tr><td class="titlefieldmiddle nowrap">'.saweeklyreportRenderEditableFieldLabel($langs->trans('WeeklyReportTechnicianDays'), $cardurl, $object, 'technician_days', $inlineeditallowed, $action, $editfield).'</td><td class="right">'.saweeklyreportRenderEditableField($cardurl, $object, 'technician_days', '<input class="flat width100 right" name="technician_days" value="'.dol_escape_htmltag(price($object->technician_days)).'">', price($object->technician_days), $inlineeditallowed, $action, $editfield).'</td></tr>';
	print '<tr><td class="titlefieldmiddle nowrap">'.saweeklyreportRenderEditableFieldLabel($langs->trans('WeeklyReportTechnicianWorkdays'), $cardurl, $object, 'technician_workdays', $inlineeditallowed, $action, $editfield).'</td><td class="right">'.saweeklyreportRenderEditableField($cardurl, $object, 'technician_workdays', '<input class="flat width100 right" name="technician_workdays" value="'.dol_escape_htmltag(price($object->technician_workdays)).'">', price($object->technician_workdays), $inlineeditallowed, $action, $editfield).'</td></tr>';
	print '<tr><td class="titlefieldmiddle nowrap">'.$langs->trans('WeeklyReportTechnicianAverage').'</td><td class="right">'.price($object->technician_average).'</td></tr>';
	print '</table>';
	print '</div>';

	print '<div class="fichehalfright">';
	print '<table class="border centpercent tableforfield">';
	print '<tr><td class="titlefieldmiddle nowrap">'.$langs->trans('WeeklyReportWeekInstalledPower').'</td><td class="right">'.price($object->week_installed_power).' kWc</td></tr>';
	print '<tr><td class="titlefieldmiddle nowrap">'.$langs->trans('WeeklyReportMonthInstalledPower').'</td><td class="right">'.price($object->month_installed_power).' kWc</td></tr>';
	print '<tr><td class="titlefieldmiddle nowrap">'.$langs->trans('WeeklyReportAnnualInstalledPower').'</td><td class="right">'.price($object->annual_installed_power).' kWc</td></tr>';
	print '<tr><td class="titlefieldmiddle nowrap">'.$langs->trans('WeeklyReportAnnualTargetPower').'</td><td class="right">'.price($object->annual_target_power).' kWc</td></tr>';
	print '<tr><td class="titlefieldmiddle nowrap">'.$langs->trans('WeeklyReportWeeklyTargetPower').'</td><td class="right">'.price($object->weekly_target_power).' kWc</td></tr>';
	print '<tr><td class="titlefieldmiddle nowrap">'.$langs->trans('WeeklyReportAnnualCompletionRate').'</td><td class="right">'.price($object->annual_completion_rate).'%</td></tr>';
	print '<tr><td class="titlefieldmiddle nowrap">'.$langs->trans('WeeklyReportWorkweeksElapsed').'</td><td class="right">'.price($object->workweeks_elapsed).'</td></tr>';
	print '<tr><td class="titlefieldmiddle nowrap">'.$langs->trans('WeeklyReportAnnualAveragePower').'</td><td class="right">'.price($object->annual_average_power).' kWc/sem.</td></tr>';
	print '</table>';
	print '</div>';
	print '</div>';
	print '<div class="clearboth"></div>';

	print '<br>';
	print load_fiche_titre($langs->trans('WeeklyReportCommunicationsObjectives'), '', 'meeting');
	print '<table class="border centpercent tableforfield">';
	foreach (array('previous_week_feedback', 'field_returns', 'current_week_goal', 'safety_message', 'vehicle_loading_reminder') as $field) {
		print '<tr><td class="titlefield">'.saweeklyreportRenderEditableFieldLabel($langs->trans($object->fields[$field]['label']), $cardurl, $object, $field, $inlineeditallowed, $action, $editfield).'</td><td>';
		$editorhtml = saweeklyreportRenderEditor($field, (string) $object->$field, ROWS_3);
		$displayhtml = saweeklyreportRenderHtmlValue((string) $object->$field);
		print saweeklyreportRenderEditableField($cardurl, $object, $field, $editorhtml, $displayhtml, $inlineeditallowed, $action, $editfield);
		print '</td></tr>';
	}
	print '</table>';

	if ($action === 'edit') {
		print '<div class="center">'.$form->buttonsSaveCancel().'</div>';
		print '</form>';
	}

	print '<br>';
	print load_fiche_titre($langs->trans('WeeklyReportServiceLines'), '', 'fa-wrench');
	$caneditservices = ($permissiontoadd && (int) $object->status === WeeklyReport::STATUS_DRAFT);
	$hasmanualservicelines = false;
	foreach ($object->lines as $line) {
		if ((string) $line->source_element !== 'ticket') {
			$hasmanualservicelines = true;
			break;
		}
	}
	if ($caneditservices && $hasmanualservicelines) {
		print '<form method="POST" action="'.dol_escape_htmltag($cardurl).'">';
		print '<input type="hidden" name="token" value="'.newToken().'">';
		print '<input type="hidden" name="action" value="updateservices">';
		print '<input type="hidden" name="id" value="'.((int) $object->id).'">';
	}
	print '<table class="noborder centpercent">';
	print '<tr class="liste_titre"><th>'.$langs->trans('Type').'</th><th>'.$langs->trans('WeeklyReportTicketGroup').'</th><th>'.$langs->trans('WeeklyReportTicketSeverity').'</th><th>'.$langs->trans('Label').'</th><th>'.$langs->trans('Description').'</th><th>'.$langs->trans('Origin').'</th><th></th></tr>';
	if (empty($object->lines)) {
		print '<tr class="oddeven"><td colspan="7"><span class="opacitymedium">'.$langs->trans('NoRecordFound').'</span></td></tr>';
	}
	foreach ($object->lines as $index => $line) {
		$isticketline = ((string) $line->source_element === 'ticket');
		$displaydata = $isticketline ? SAWeeklyReportTicketHelper::getServiceLineDisplayData($db, $langs, $line) : array(
			'type' => (string) $line->service_type,
			'group' => '',
			'severity' => '',
			'label' => (string) $line->label,
			'description' => (string) $line->description,
			'origin' => $line->getSourceNomUrl(1),
		);
		print '<tr class="oddeven">';
		print '<td>';
		if ($caneditservices && !$isticketline) {
			print '<input type="hidden" name="lineid[]" value="'.((int) $line->id).'">';
			print '<input class="flat maxwidth150" name="service_type[]" value="'.dol_escape_htmltag($line->service_type).'">';
		} else {
			print dol_escape_htmltag((string) $displaydata['type']);
		}
		print '</td><td>';
		print dol_escape_htmltag((string) $displaydata['group']);
		print '</td><td>';
		print dol_escape_htmltag((string) $displaydata['severity']);
		print '</td><td>';
		if ($caneditservices && !$isticketline) {
			print '<input class="flat minwidth200" name="service_label[]" value="'.dol_escape_htmltag($line->label).'">';
		} else {
			print dol_escape_htmltag((string) $displaydata['label']);
		}
		print '</td><td>';
		if ($caneditservices && !$isticketline) {
			print saweeklyreportRenderEditor('service_description_'.((int) $line->id), (string) $line->description, ROWS_2);
		} else {
			print saweeklyreportRenderHtmlValue((string) $displaydata['description']);
		}
		print '</td><td>'.($isticketline ? $line->getSourceNomUrl(1) : (string) $displaydata['origin']).'</td><td class="right">';
		if ($caneditservices) {
			print '<a class="reposition" href="'.$cardurl.'?id='.((int) $object->id).'&action=deleteserviceline&lineid='.((int) $line->id).'&token='.newToken().'" title="'.dol_escape_htmltag($langs->trans('SAWeeklyReportDetachTicket')).'">'.img_picto($langs->trans('SAWeeklyReportDetachTicket'), 'unlink').'</a>';
		}
		print '</td></tr>';
	}
	print '</table>';
	if ($caneditservices && $hasmanualservicelines) {
		print '<div class="center"><input type="submit" class="button" value="'.$langs->trans('Save').'"></div>';
		print '</form>';
	}

	if ($caneditservices) {
		if (isModEnabled('ticket') && ($user->admin || $user->hasRight('ticket', 'read'))) {
			print '<form method="POST" action="'.dol_escape_htmltag($cardurl).'">';
			print '<input type="hidden" name="token" value="'.newToken().'">';
			print '<input type="hidden" name="action" value="addticketline">';
			print '<input type="hidden" name="id" value="'.((int) $object->id).'">';
			print '<table class="noborder centpercent">';
			print '<tr class="oddeven"><td class="titlefield">'.$langs->trans('WeeklyReportAddExistingTicket').'</td><td>'.SAWeeklyReportTicketHelper::selectTickets($db, $langs, '', 'ticketid', 50, 'minwidth300', '1').'</td><td><input type="submit" class="button small" value="'.$langs->trans('Add').'"></td></tr>';
			print '</table>';
			print '</form>';
		}

	}

	print dol_get_fiche_end();

	if ($action !== 'edit') {
		print '<div class="tabsAction">';
		if ($permissiontoadd && (int) $object->status === WeeklyReport::STATUS_DRAFT) {
			print '<a class="butAction" href="'.$cardurl.'?id='.((int) $object->id).'&action=refreshdata&token='.newToken().'">'.$langs->trans('WeeklyReportRefreshData').'</a>';
		}
		if ($permissiontovalidate && (int) $object->status === WeeklyReport::STATUS_VALIDATED) {
			print '<a class="butAction" href="'.$cardurl.'?id='.((int) $object->id).'&action=setdraft&token='.newToken().'">'.$langs->trans('Modify').'</a>';
		}
		if ($permissiontovalidate && (int) $object->status === WeeklyReport::STATUS_DRAFT) {
			print '<a class="butAction" href="'.$cardurl.'?id='.((int) $object->id).'&action=validate&token='.newToken().'">'.$langs->trans('Validate').'</a>';
		}
		if ($permissiontovalidate && (int) $object->status !== WeeklyReport::STATUS_CANCELED) {
			print '<a class="butActionDelete" href="'.$cardurl.'?id='.((int) $object->id).'&action=cancelreport&token='.newToken().'">'.$langs->trans('Cancel').'</a>';
		}
		if ($permissiontodelete) {
			print '<a class="butActionDelete" href="'.$cardurl.'?id='.((int) $object->id).'&mode=delete">'.$langs->trans('Delete').'</a>';
		}
		print '</div>';

		print '<div class="fichecenter"><div class="fichehalfleft">';
		print '<a name="builddoc"></a>';
		$relativepath = rtrim($relativepathwithnofile, '/');
		$urlsource = $cardurl.'?id='.((int) $object->id);
		$activeDocumentModels = class_exists('ModelePDFWeeklyReport') ? ModelePDFWeeklyReport::liste_modeles($db) : array();
		$genallowed = ($permissiontogeneratedoc && !empty($activeDocumentModels));
		$delallowed = $permissiontoadd;
		$modelselected = !empty($object->model_pptx) ? (string) $object->model_pptx : getDolGlobalString('SAWEEKLYREPORT_WEEKLYREPORT_ADDON_DOC');
		if ($modelselected === '' || empty($activeDocumentModels[$modelselected])) {
			$modelselected = getDolGlobalString('SAWEEKLYREPORT_WEEKLYREPORT_ADDON_DOC');
		}
		if ($modelselected === '' || empty($activeDocumentModels[$modelselected])) {
			$modelselected = '';
		}
		print $formfile->showdocuments($modulepart, $relativepath, $upload_dir, $urlsource, $genallowed, $delallowed, $modelselected, 0, 0, 0, 28, 0, 'id='.((int) $object->id), '', '', $langs->defaultlang, '', $object, 0, 'remove_file');

		$tmparray = saweeklyreportBuildNativeSourceLinkBlock($object, $permissiontoadd);
		print $tmparray['htmltoenteralink'];
		$form->showLinkedObjectBlock($object, $tmparray['linktoelem']);

		print '</div><div class="fichehalfright">';
		if (isModEnabled('agenda') && ($user->admin || $user->hasRight('agenda', 'myactions', 'read') || $user->hasRight('agenda', 'allactions', 'read'))) {
			include_once DOL_DOCUMENT_ROOT.'/core/class/html.formactions.class.php';
			$formactions = new FormActions($db);
			$maxevent = function_exists('getDolUserInt') ? getDolUserInt('MAIN_SIZE_SHORTLIST_LIMIT', getDolGlobalInt('MAIN_SIZE_SHORTLIST_LIMIT', 5)) : getDolGlobalInt('MAIN_SIZE_SHORTLIST_LIMIT', 5);
			if ($maxevent <= 0) {
				$maxevent = 5;
			}
			$morehtmlcenter = dolGetButtonTitle($langs->trans('SeeAll'), '', 'fa fa-bars imgforviewmode', dol_buildpath('/saweeklyreport/weeklyreport_agenda.php', 1).'?id='.((int) $object->id));
			$typeelement = $object->element.(!empty($object->module) ? '@'.$object->module : '');
			$formactions->showactions($object, $typeelement, 0, 1, '', $maxevent, '', $morehtmlcenter);
		}
		print '</div></div>';
	}
} else {
	print load_fiche_titre($langs->trans('WeeklyReport'), '', $object->picto);
	print '<div class="opacitymedium">'.$langs->trans('NoRecordFound').'</div>';
}

llxFooter();
$db->close();
