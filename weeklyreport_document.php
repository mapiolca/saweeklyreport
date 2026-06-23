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

require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/images.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
dol_include_once('/saweeklyreport/class/weeklyreport.class.php');
dol_include_once('/saweeklyreport/lib/saweeklyreport.lib.php');

$langs->loadLangs(array('saweeklyreport@saweeklyreport', 'other', 'mails'));

$id = GETPOSTINT('id');
$action = GETPOST('action', 'aZ09');
$confirm = GETPOST('confirm', 'alpha');
$sortfield = GETPOST('sortfield', 'aZ09comma') ?: 'name';
$sortorder = GETPOST('sortorder', 'aZ09comma') ?: 'ASC';

$object = new WeeklyReport($db);
if ($object->fetch($id) <= 0) {
	accessforbidden();
}

if (!isModEnabled('saweeklyreport')) {
	accessforbidden();
}

$permissiontoread = saweeklyreportCanDo($user, $object, 'read');
$permissiontoadd = saweeklyreportCanDo($user, $object, 'write');
$permissiontodelete = saweeklyreportCanDo($user, $object, 'delete');
if (!$permissiontoread) {
	accessforbidden();
}

$upload_dir = $object->getDocumentDir();
$modulepart = 'saweeklyreport';
$relativepathwithnofile = $object->getDocumentRelativeDir().'/';
if (!is_dir($upload_dir) && is_dir($object->getLegacyDocumentDir())) {
	$upload_dir = $object->getLegacyDocumentDir();
	$relativepathwithnofile = $object->getLegacyDocumentRelativeDir().'/';
}

include DOL_DOCUMENT_ROOT.'/core/actions_linkedfiles.inc.php';

$form = new Form($db);
$formfile = new FormFile($db);

llxHeader('', $langs->trans('WeeklyReport').' - '.$langs->trans('Documents'), '', '', 0, 0, '', '', '', 'mod-saweeklyreport page-card_document');

$head = weeklyreportPrepareHead($object);
print dol_get_fiche_head($head, 'documents', $langs->trans('WeeklyReport'), -1, $object->picto);

$linkback = '<a href="'.dol_buildpath('/saweeklyreport/weeklyreport_list.php', 1).'">'.$langs->trans('BackToList').'</a>';
dol_banner_tab($object, 'ref', $linkback, 1, 'ref', 'ref');

$filearray = dol_dir_list($upload_dir, 'files', 0, '', '(\.meta|_preview.*\.png)$', $sortfield, (strtolower($sortorder) == 'desc' ? SORT_DESC : SORT_ASC), 1);
$totalsize = 0;
foreach ($filearray as $file) {
	$totalsize += $file['size'];
}

print '<div class="fichecenter">';
print '<div class="underbanner clearboth"></div>';
print '<table class="border centpercent tableforfield">';
print '<tr><td class="titlefield">'.$langs->trans('NbOfAttachedFiles').'</td><td>'.count($filearray).'</td></tr>';
print '<tr><td>'.$langs->trans('TotalSizeOfAttachedFiles').'</td><td>'.$totalsize.' '.$langs->trans('bytes').'</td></tr>';
print '</table>';
print '</div>';

print dol_get_fiche_end();

$param = '&id='.(int) $object->id;
include DOL_DOCUMENT_ROOT.'/core/tpl/document_actions_post_headers.tpl.php';

llxFooter();
$db->close();
