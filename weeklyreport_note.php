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
require_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';

$langs->loadLangs(array('saweeklyreport@saweeklyreport', 'other'));

$id = GETPOSTINT('id');
$action = GETPOST('action', 'aZ09');
$object = new WeeklyReport($db);
if ($object->fetch($id) <= 0) {
	accessforbidden();
}

if (!isModEnabled('saweeklyreport')) {
	accessforbidden();
}

$permissiontoread = saweeklyreportCanDo($user, $object, 'read');
$permissiontoadd = saweeklyreportCanDo($user, $object, 'write');
$permissionnote = $permissiontoadd;
if (!$permissiontoread) {
	accessforbidden();
}

$hookmanager->initHooks(array('weeklyreportnote', 'globalcard'));
$parameters = array();
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action);
if ($reshook < 0) {
	setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
}
if (empty($reshook)) {
	include DOL_DOCUMENT_ROOT.'/core/actions_setnotes.inc.php';
}

$form = new Form($db);

llxHeader('', $langs->trans('WeeklyReport').' - '.$langs->trans('Notes'), '', '', 0, 0, '', '', '', 'mod-saweeklyreport page-card_notes');

$head = weeklyreportPrepareHead($object);
print dol_get_fiche_head($head, 'notes', $langs->trans('WeeklyReport'), -1, $object->picto);

$linkback = '<a href="'.dol_buildpath('/saweeklyreport/weeklyreport_list.php', 1).'">'.$langs->trans('BackToList').'</a>';
$morehtmlref = weeklyreportBannerMoreHtmlRef($object);
dol_banner_tab($object, 'ref', $linkback, 1, 'ref', 'ref', $morehtmlref);

print '<div class="fichecenter">';
print '<div class="underbanner clearboth"></div>';
$cssclass = 'titlefield';
include DOL_DOCUMENT_ROOT.'/core/tpl/notes.tpl.php';
print '</div>';

print dol_get_fiche_end();

llxFooter();
$db->close();
