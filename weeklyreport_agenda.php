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
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formactions.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
require_once DOL_DOCUMENT_ROOT.'/comm/action/class/actioncomm.class.php';
require_once DOL_DOCUMENT_ROOT.'/contact/class/contact.class.php';
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
if (!saweeklyreportCanDo($user, $object, 'read')) {
	accessforbidden();
}
if (empty($user->admin) && !$user->hasRight('agenda', 'myactions', 'read') && !$user->hasRight('agenda', 'allactions', 'read')) {
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
		$search_filtert = 0;
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
$morehtmlref = weeklyreportBannerMoreHtmlRef($object);
dol_banner_tab($object, 'ref', $linkback, 1, 'ref', 'ref', $morehtmlref);

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
if (!empty($search_dateevent_start)) {
	$param .= '&dateevent_startyear='.GETPOSTINT('dateevent_startyear');
	$param .= '&dateevent_startmonth='.GETPOSTINT('dateevent_startmonth');
	$param .= '&dateevent_startday='.GETPOSTINT('dateevent_startday');
}
if (!empty($search_dateevent_end)) {
	$param .= '&dateevent_endyear='.GETPOSTINT('dateevent_endyear');
	$param .= '&dateevent_endmonth='.GETPOSTINT('dateevent_endmonth');
	$param .= '&dateevent_endday='.GETPOSTINT('dateevent_endday');
}

$filters = array();
$filters['search_agenda_label'] = $search_agenda_label;
$filters['search_rowid'] = $search_rowid;
$filters['search_complete'] = $search_complete;
$filters['search_filtert'] = $user->hasRight('agenda', 'allactions', 'read') ? $search_filtert : (int) $user->id;

$start_year = GETPOSTINT('dateevent_startyear');
$start_month = GETPOSTINT('dateevent_startmonth');
$start_day = GETPOSTINT('dateevent_startday');
$end_year = GETPOSTINT('dateevent_endyear');
$end_month = GETPOSTINT('dateevent_endmonth');
$end_day = GETPOSTINT('dateevent_endday');
$tms_start = '';
$tms_end = '';
if (!empty($start_year) && !empty($start_month) && !empty($start_day)) {
	$tms_start = dol_mktime(0, 0, 0, $start_month, $start_day, $start_year, 'tzuserrel');
}
if (!empty($end_year) && !empty($end_month) && !empty($end_day)) {
	$tms_end = dol_mktime(23, 59, 59, $end_month, $end_day, $end_year, 'tzuserrel');
}
if ($buttonremovefilter) {
	$tms_start = '';
	$tms_end = '';
}

$elementtypes = array($object->element);
if (!empty($object->module)) {
	$elementtypes[] = $object->element.'@'.$object->module;
}
$quotedtypes = array();
foreach (array_unique($elementtypes) as $elementtype) {
	$quotedtypes[] = "'".$db->escape($elementtype)."'";
}

$sqlselect = "SELECT a.id, a.label as label, a.datep as dp, a.datep2 as dp2, a.percent as percent";
$sqlselect .= ", a.fk_element, a.elementtype, a.fk_contact, a.code, a.fulldayevent";
$sqlselect .= ", c.code as acode, c.libelle as alabel, c.picto as apicto";
$sqlselect .= ", u.rowid as user_id, u.login as user_login, u.photo as user_photo, u.firstname as user_firstname, u.lastname as user_lastname";
$sqlfrom = " FROM ".$db->prefix()."actioncomm as a";
$sqlfrom .= " LEFT JOIN ".$db->prefix()."user as u ON u.rowid = a.fk_user_action";
$sqlfrom .= " LEFT JOIN ".$db->prefix()."c_actioncomm as c ON a.fk_action = c.id";
$sqlwhere = " WHERE a.entity IN (".getEntity('agenda').")";
$sqlwhere .= " AND a.fk_element = ".((int) $object->id);
$sqlwhere .= " AND a.elementtype IN (".implode(',', $quotedtypes).")";

if (!empty($tms_start) && !empty($tms_end)) {
	$sqlwhere .= " AND ((a.datep BETWEEN '".$db->idate($tms_start)."' AND '".$db->idate($tms_end)."') OR (a.datep2 BETWEEN '".$db->idate($tms_start)."' AND '".$db->idate($tms_end)."'))";
} elseif (empty($tms_start) && !empty($tms_end)) {
	$sqlwhere .= " AND ((a.datep <= '".$db->idate($tms_end)."') OR (a.datep2 <= '".$db->idate($tms_end)."'))";
} elseif (!empty($tms_start) && empty($tms_end)) {
	$sqlwhere .= " AND ((a.datep >= '".$db->idate($tms_start)."') OR (a.datep2 >= '".$db->idate($tms_start)."'))";
}

if (is_array($actioncode) && !empty($actioncode)) {
	$tmpconditions = array();
	foreach ($actioncode as $code) {
		if ((string) $code === '-1' || (string) $code === '') {
			continue;
		}
		$tmpcondition = '';
		addEventTypeSQL($tmpcondition, $code, '');
		if ($tmpcondition !== '') {
			$tmpconditions[] = trim($tmpcondition);
		}
	}
	if (!empty($tmpconditions)) {
		$sqlwhere .= " AND (".implode(' OR ', $tmpconditions).")";
	}
} elseif (!empty($actioncode) && $actioncode != '-1') {
	addEventTypeSQL($sqlwhere, $actioncode);
}

addOtherFilterSQL($sqlwhere, '', dol_now('tzuser'), $filters);

$allowedsortfields = array('a.id', 'a.datep,a.id', 'a.percent', 'a.label', 'c.libelle');
if (!in_array($sortfield, $allowedsortfields, true)) {
	$sortfield = 'a.datep,a.id';
}
$sortorder = strtoupper($sortorder);
if (!in_array($sortorder, array('ASC', 'DESC', 'ASC,ASC', 'DESC,DESC', 'ASC,DESC', 'DESC,ASC'), true)) {
	$sortorder = ($sortfield === 'a.datep,a.id') ? 'DESC,DESC' : 'DESC';
}

$nbtotalofrecords = 0;
$sqlcount = "SELECT COUNT(a.id) as nb".$sqlfrom.$sqlwhere;
$resqlcount = $db->query($sqlcount);
if ($resqlcount) {
	$objcount = $db->fetch_object($resqlcount);
	$nbtotalofrecords = is_object($objcount) ? (int) $objcount->nb : 0;
	$db->free($resqlcount);
} else {
	dol_print_error($db);
}
if ($limit > 0 && ($page * $limit) > $nbtotalofrecords) {
	$page = 0;
	$offset = 0;
}

$sql = $sqlselect.$sqlfrom.$sqlwhere;
$sql .= $db->order($sortfield, $sortorder);
if ($limit) {
	$sql .= $db->plimit($limit + 1, $offset);
}
$resql = $db->query($sql);
$num = $resql ? $db->num_rows($resql) : 0;
if (!$resql) {
	dol_print_error($db);
}

$massactionbutton = '';
$formactions = new FormActions($db);
$actionstatic = new ActionComm($db);
$userstatic = new User($db);
$contactstatic = new Contact($db);
$userlinkcache = array();
$contactlinkcache = array();
$colspan = 9;

print '<form name="listactionsfilter" class="listactionsfilter" action="'.dol_escape_htmltag($agendapageurl).'" method="POST">';
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" name="id" value="'.((int) $object->id).'">';
print '<input type="hidden" name="sortfield" value="'.dol_escape_htmltag($sortfield).'">';
print '<input type="hidden" name="sortorder" value="'.dol_escape_htmltag($sortorder).'">';
if (!empty($contextpage) && $contextpage != 'weeklyreportagenda') {
	print '<input type="hidden" name="contextpage" value="'.dol_escape_htmltag($contextpage).'">';
}

print_barre_liste($langs->trans('WeeklyReportEventsAgenda'), $page, $agendapageurl, $param, $sortfield, $sortorder, $massactionbutton, $num, $nbtotalofrecords, 'object_action', 0, $morehtmlright, '', $limit, 1, 0);

print '<div class="div-table-responsive-no-min">';
print '<table class="noborder centpercent">';
print '<tr class="liste_titre_filter">';
if (!empty($conf->main_checkbox_left_column)) {
	print '<td class="liste_titre width50 middle">';
	print $form->showFilterAndCheckAddButtons($massactionbutton ? 1 : 0, 'checkforselect', 1);
	print '</td>';
}
print '<td class="liste_titre"><input type="text" class="width50" name="search_rowid" value="'.dol_escape_htmltag($search_rowid).'"></td>';
print '<td class="liste_titre center">'.$form->selectDateToDate($tms_start, $tms_end, 'dateevent', 1).'</td>';
print '<td class="liste_titre">'.$form->select_dolusers(((int) $filters['search_filtert'] > 0 ? (int) $filters['search_filtert'] : ''), 'search_filtert', 1, null, 0, '', '', '0', 0, 0, '', 2, '', 'minwidth100 maxwidth250 widthcentpercentminusx').'</td>';
print '<td class="liste_titre">'.$formactions->select_type_actions($actioncode, 'actioncode', '', getDolGlobalString('AGENDA_USE_EVENT_TYPE') ? -1 : 1, 0, getDolGlobalString('AGENDA_USE_MULTISELECT_TYPE') ? 1 : 0, 1, 'selecttype combolargeelem minwidth75 maxwidth125', 1).'</td>';
print '<td class="liste_titre maxwidth100onsmartphone"><input type="text" class="maxwidth200" name="search_agenda_label" value="'.dol_escape_htmltag($search_agenda_label).'"></td>';
print '<td class="liste_titre"></td>';
print '<td class="liste_titre"></td>';
print '<td class="liste_titre parentonrightofpage">'.$formactions->form_select_status_action('formaction', $search_complete, 1, 'search_complete', 1, 2, 'search_status width100 onrightofpage', 1).'</td>';
if (empty($conf->main_checkbox_left_column)) {
	print '<td class="liste_titre center">';
	print $form->showFilterAndCheckAddButtons($massactionbutton ? 1 : 0, 'checkforselect', 1);
	print '</td>';
}
print '</tr>';

print '<tr class="liste_titre">';
if (!empty($conf->main_checkbox_left_column)) {
	print getTitleFieldOfList('', 0, $agendapageurl, '', '', $param, '', $sortfield, $sortorder, 'maxwidthsearch ');
}
print getTitleFieldOfList('Ref', 0, $agendapageurl, 'a.id', '', $param, '', $sortfield, $sortorder);
print getTitleFieldOfList('Date', 0, $agendapageurl, 'a.datep,a.id', '', $param, '', $sortfield, $sortorder, 'center ');
print getTitleFieldOfList('Owner');
print getTitleFieldOfList('Type', 0, $agendapageurl, 'c.libelle', '', $param, '', $sortfield, $sortorder);
print getTitleFieldOfList('Title', 0, $agendapageurl, 'a.label', '', $param, '', $sortfield, $sortorder);
print getTitleFieldOfList('ActionOnContact', 0, $agendapageurl, '', '', $param, '', $sortfield, $sortorder, 'tdoverflowmax125 ', 0, '', 0);
print getTitleFieldOfList('LinkedObject', 0, $agendapageurl, '', '', $param, '', $sortfield, $sortorder);
print getTitleFieldOfList('Status', 0, $agendapageurl, 'a.percent', '', $param, '', $sortfield, $sortorder, 'center ');
if (empty($conf->main_checkbox_left_column)) {
	print getTitleFieldOfList('', 0, $agendapageurl, '', '', $param, '', $sortfield, $sortorder, 'maxwidthsearch center ');
}
print '</tr>';

$i = 0;
$nbdisplayed = 0;
if ($resql) {
	$imaxinloop = ($limit ? min($num, $limit) : $num);
	while ($i < $imaxinloop) {
		$obj = $db->fetch_object($resql);
		if (!is_object($obj)) {
			break;
		}
		$actionstatic = new ActionComm($db);
		$resultfetch = $actionstatic->fetch((int) $obj->id);
		if ($resultfetch <= 0) {
			setEventMessages($actionstatic->error, $actionstatic->errors, 'errors');
			$i++;
			continue;
		}
		$actionstatic->fetchResources();
		if (empty($actionstatic->type_code)) {
			$actionstatic->type_code = $obj->acode;
		}
		$actionstatic->type_picto = $obj->apicto;

		$datep = $db->jdate($obj->dp);
		$datef = $db->jdate($obj->dp2);
		print '<tr class="oddeven">';
		if (!empty($conf->main_checkbox_left_column)) {
			print '<td></td>';
		}
		print '<td class="nowraponall">'.$actionstatic->getNomUrl(1, -1).'</td>';
		print '<td class="center nowraponall nopaddingtopimp nopaddingbottomimp">'.dolOutputDates($datep, $datef, (int) $obj->fulldayevent, 0, '', 'tzuserrel', 1).'</td>';
		print '<td class="tdoverflowmax125">';
		if ((int) $obj->user_id > 0) {
			if (!isset($userlinkcache[(int) $obj->user_id])) {
				$userstatic = new User($db);
				$userstatic->fetch((int) $obj->user_id);
				$userlinkcache[(int) $obj->user_id] = $userstatic->id > 0 ? $userstatic->getNomUrl(-1, '', 0, 0, 16, 0, 'firstelselast', '') : '';
			}
			print $userlinkcache[(int) $obj->user_id];
		}
		print '</td>';
		$labeltype = $actionstatic->getTypeLabel(0);
		$labeltypelong = $actionstatic->getTypeLabel(2);
		print '<td class="tdoverflowmax125" title="'.dol_escape_htmltag($labeltypelong).'">';
		print $actionstatic->getTypePicto();
		if ($labeltype !== '') {
			print ' '.dol_escape_htmltag($labeltype);
		}
		if (preg_match('/PRIVATE/', (string) $actionstatic->code)) {
			print ' '.img_picto($langs->trans('Private'), 'lock', 'class="valignmiddle"');
		}
		print '</td>';
		print '<td class="tdoverflowmax300" title="'.dol_escape_htmltag((string) $obj->label).'">'.$actionstatic->getNomUrl(0, 120).'</td>';
		print '<td class="valignmiddle">';
		if (!empty($actionstatic->socpeopleassigned) && is_array($actionstatic->socpeopleassigned)) {
			foreach ($actionstatic->socpeopleassigned as $cid => $cvalue) {
				$cid = (int) $cid;
				if ($cid <= 0) {
					continue;
				}
				if (!isset($contactlinkcache[$cid])) {
					$contactstatic = new Contact($db);
					$resultcontact = $contactstatic->fetch($cid);
					$contactlinkcache[$cid] = $resultcontact > 0 ? $contactstatic->getNomUrl(-2, '', 0, '', -1, 0, 'paddingright') : '';
				}
				print $contactlinkcache[$cid];
			}
		} elseif ((int) $obj->fk_contact > 0) {
			$cid = (int) $obj->fk_contact;
			if (!isset($contactlinkcache[$cid])) {
				$contactstatic = new Contact($db);
				$resultcontact = $contactstatic->fetch($cid);
				$contactlinkcache[$cid] = $resultcontact > 0 ? $contactstatic->getNomUrl(-1, '', 10) : '';
			}
			print $contactlinkcache[$cid];
		} else {
			print '&nbsp;';
		}
		print '</td>';
		print '<td class="tdoverflowmax200 nowraponall">'.$object->getNomUrl(1).'</td>';
		print '<td class="nowrap center">'.$actionstatic->LibStatut((int) $obj->percent, 2, 0, $datep).'</td>';
		if (empty($conf->main_checkbox_left_column)) {
			print '<td></td>';
		}
		print '</tr>';
		$nbdisplayed++;
		$i++;
	}
	$db->free($resql);
}
if ($nbdisplayed === 0) {
	print '<tr class="oddeven"><td colspan="'.$colspan.'"><span class="opacitymedium">'.$langs->trans('NoRecordFound').'</span></td></tr>';
}
print '</table>';
print '</div>';
print '</form>';

llxFooter();
$db->close();
