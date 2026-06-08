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

require_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
dol_include_once('/saweeklyreport/class/weeklyreport.class.php');
dol_include_once('/saweeklyreport/lib/saweeklyreport.lib.php');

$langs->loadLangs(array('saweeklyreport@saweeklyreport', 'agenda', 'other'));

$id = GETPOSTINT('id');
$ref = GETPOST('ref', 'alpha');
$action = GETPOST('action', 'aZ09');
$cancel = GETPOST('cancel', 'alpha');
$contextpage = GETPOST('contextpage', 'aZ') ? GETPOST('contextpage', 'aZ') : 'weeklyreportagenda';
$backtopage = GETPOST('backtopage', 'alpha');

if (GETPOST('actioncode', 'array')) {
	$actioncode = GETPOST('actioncode', 'array:alpha', 3);
	if (!count($actioncode)) {
		$actioncode = '0';
	}
} else {
	$actioncode = GETPOST('actioncode', 'alpha', 3) ? GETPOST('actioncode', 'alpha', 3) : (GETPOST('actioncode') == '0' ? '0' : getDolGlobalString('AGENDA_DEFAULT_FILTER_TYPE_FOR_OBJECT'));
}
$search_rowid = GETPOST('search_rowid', 'alphanohtml');
$search_agenda_label = GETPOST('search_agenda_label', 'alphanohtml');
$search_complete = GETPOST('search_complete', 'alphanohtml');
$search_filtert = GETPOSTINT('search_filtert');
$search_dateevent_start = GETPOSTDATE('dateevent_start');
$search_dateevent_end = GETPOSTDATE('dateevent_end');

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
	$sortfield = 'a.datep,a.id';
}
if (!$sortorder) {
	$sortorder = 'DESC,DESC';
}

$object = new WeeklyReport($db);
$hookmanager->initHooks(array('weeklyreportagenda', 'globalcard'));
if ($object->fetch($id, $ref) <= 0) {
	accessforbidden();
}

if (!isModEnabled('saweeklyreport') || !isModEnabled('agenda')) {
	accessforbidden();
}
if (!$user->hasRight('saweeklyreport', 'weeklyreport', 'read')) {
	accessforbidden();
}
if (!$user->hasRight('agenda', 'myactions', 'read') && !$user->hasRight('agenda', 'allactions', 'read')) {
	accessforbidden();
}

$parameters = array('id' => $id);
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action);
if ($reshook < 0) {
	setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
}

if (empty($reshook)) {
	if ($cancel && !empty($backtopage)) {
		header('Location: '.$backtopage);
		exit;
	}

	if ($buttonremovefilter) {
		$actioncode = '';
		$search_rowid = '';
		$search_agenda_label = '';
		$search_complete = '';
		$search_filtert = '';
		$search_dateevent_start = '';
		$search_dateevent_end = '';
	}
}

$form = new Form($db);
$title = $langs->trans('WeeklyReportEventsAgenda');
$agendapageurl = dol_buildpath('/saweeklyreport/weeklyreport_agenda.php', 1);

llxHeader('', $title, '', '', 0, 0, '', '', '', 'mod-saweeklyreport page-card_agenda');

$head = weeklyreportPrepareHead($object);
print dol_get_fiche_head($head, 'agenda', $langs->trans('WeeklyReport'), -1, $object->picto);

$linkback = '<a href="'.dol_buildpath('/saweeklyreport/weeklyreport_list.php', 1).'?restore_lastsearch_values=1">'.$langs->trans('BackToList').'</a>';
dol_banner_tab($object, 'ref', $linkback, 1, 'ref', 'ref');

print '<div class="fichecenter">';
print '<div class="underbanner clearboth"></div>';
$object->info($object->id);
dol_print_object_info($object, 1);
print '</div>';

print dol_get_fiche_end();

print '<br>';

$typeelement = $object->element.(!empty($object->module) ? '@'.$object->module : '');
$out = '&origin='.urlencode($typeelement).'&originid='.urlencode((string) $object->id);
$out .= '&backtopage='.urlencode($agendapageurl.'?id='.((int) $object->id));
$createactionurl = DOL_URL_ROOT.'/comm/action/card.php?action=create&token='.newToken().$out;
$morehtmlright = '';
if ($user->hasRight('agenda', 'myactions', 'create') || $user->hasRight('agenda', 'allactions', 'create')) {
	$morehtmlright .= dolGetButtonTitle($langs->trans('AddAction'), '', 'fa fa-plus-circle', $createactionurl);
} else {
	$morehtmlright .= dolGetButtonTitle($langs->trans('AddAction'), '', 'fa fa-plus-circle', $createactionurl, '', 0);
}

$param = '&id='.((int) $object->id);
if (!empty($contextpage) && $contextpage != 'weeklyreportagenda') {
	$param .= '&contextpage='.urlencode($contextpage);
}
if ($limit > 0 && $limit != $conf->liste_limit) {
	$param .= '&limit='.((int) $limit);
}
if ($search_rowid !== '') {
	$param .= '&search_rowid='.urlencode($search_rowid);
}
if ($actioncode !== '' && $actioncode !== '-1') {
	if (is_array($actioncode)) {
		foreach ($actioncode as $tmpactioncode) {
			$param .= '&actioncode[]='.urlencode($tmpactioncode);
		}
	} else {
		$param .= '&actioncode='.urlencode($actioncode);
	}
}
if ($search_agenda_label !== '') {
	$param .= '&search_agenda_label='.urlencode($search_agenda_label);
}
if ($search_complete !== '') {
	$param .= '&search_complete='.urlencode($search_complete);
}
if ($search_filtert !== 0) {
	$param .= '&search_filtert='.((int) $search_filtert);
}
if ($search_dateevent_start !== '') {
	$param .= '&dateevent_startyear='.GETPOSTINT('dateevent_startyear');
	$param .= '&dateevent_startmonth='.GETPOSTINT('dateevent_startmonth');
	$param .= '&dateevent_startday='.GETPOSTINT('dateevent_startday');
}
if ($search_dateevent_end !== '') {
	$param .= '&dateevent_endyear='.GETPOSTINT('dateevent_endyear');
	$param .= '&dateevent_endmonth='.GETPOSTINT('dateevent_endmonth');
	$param .= '&dateevent_endday='.GETPOSTINT('dateevent_endday');
}

$massactionbutton = '';
print_barre_liste($langs->trans('WeeklyReportEventsAgenda'), $page, $agendapageurl, $param, $sortfield, $sortorder, '', 0, -1, '', 0, $morehtmlright, '', $limit, 1, 0);

$filters = array();
$filters['search_agenda_label'] = $search_agenda_label;
$filters['search_rowid'] = $search_rowid;
$filters['search_complete'] = $search_complete;
$filters['search_filtert'] = $search_filtert;

show_actions_done($conf, $langs, $db, $object, null, 0, $actioncode, '', $filters, $sortfield, $sortorder, !empty($object->module) ? $object->module : '');

llxFooter();
$db->close();
