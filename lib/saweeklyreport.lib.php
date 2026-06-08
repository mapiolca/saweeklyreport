<?php
/* Copyright (C) 2026  Pierre Ardoin <developpeur@lesmetiersdubatiment.fr> */

/**
 * Library helpers for SAWeeklyReport.
 */

/**
 * Prepare admin tabs.
 *
 * @return	array<int,array<int,string>>
 */
function saweeklyreportAdminPrepareHead()
{
	global $langs;

	$langs->load('saweeklyreport@saweeklyreport');

	$head = array();
	$h = 0;

	$head[$h][0] = dol_buildpath('/saweeklyreport/admin/setup.php', 1);
	$head[$h][1] = $langs->trans('Settings');
	$head[$h][2] = 'settings';
	$h++;

	$head[$h][0] = dol_buildpath('/saweeklyreport/admin/compatibility.php', 1);
	$head[$h][1] = $langs->trans('Compatibility');
	$head[$h][2] = 'compatibility';
	$h++;

	$head[$h][0] = dol_buildpath('/saweeklyreport/admin/about.php', 1);
	$head[$h][1] = $langs->trans('About');
	$head[$h][2] = 'about';
	$h++;

	return $head;
}

/**
 * Prepare weekly report object tabs.
 *
 * @param	WeeklyReport	$object	Weekly report
 * @return	array<int,array<int,string>>
 */
function weeklyreportPrepareHead($object)
{
	global $langs;

	$langs->load('saweeklyreport@saweeklyreport');

	$head = array();
	$h = 0;
	$id = (int) $object->id;

	$head[$h][0] = dol_buildpath('/saweeklyreport/weeklyreport_card.php', 1).'?id='.$id;
	$head[$h][1] = $langs->trans('WeeklyReport');
	$head[$h][2] = 'card';
	$h++;

	$nbnotes = weeklyreportCountNotes($object);
	$head[$h][0] = dol_buildpath('/saweeklyreport/weeklyreport_note.php', 1).'?id='.$id;
	$head[$h][1] = $langs->trans('Notes').($nbnotes > 0 ? '<span class="badge marginleftonlyshort">'.$nbnotes.'</span>' : '');
	$head[$h][2] = 'notes';
	$h++;

	$nbdocuments = weeklyreportCountDocuments($object);
	$head[$h][0] = dol_buildpath('/saweeklyreport/weeklyreport_document.php', 1).'?id='.$id;
	$head[$h][1] = $langs->trans('Documents').($nbdocuments > 0 ? '<span class="badge marginleftonlyshort">'.$nbdocuments.'</span>' : '');
	$head[$h][2] = 'documents';
	$h++;

	if (isModEnabled('agenda')) {
		$nbevents = weeklyreportCountEvents($object);
		$head[$h][0] = dol_buildpath('/saweeklyreport/weeklyreport_agenda.php', 1).'?id='.$id;
		$head[$h][1] = $langs->trans('WeeklyReportEventsAgenda').($nbevents > 0 ? '<span class="badge marginleftonlyshort">'.$nbevents.'</span>' : '');
		$head[$h][2] = 'agenda';
		$h++;
	}

	return $head;
}

/**
 * Count notes filled on a weekly report.
 *
 * @param	WeeklyReport	$object	Weekly report
 * @return	int
 */
function weeklyreportCountNotes($object)
{
	$nb = 0;
	if (!empty($object->note_public)) {
		$nb++;
	}
	if (!empty($object->note_private)) {
		$nb++;
	}

	return $nb;
}

/**
 * Count documents and external links attached to a weekly report.
 *
 * @param	WeeklyReport	$object	Weekly report
 * @return	int
 */
function weeklyreportCountDocuments($object)
{
	global $db;

	if (empty($object->id) || empty($object->ref) || !method_exists($object, 'getDocumentDir')) {
		return 0;
	}

	require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
	require_once DOL_DOCUMENT_ROOT.'/core/class/link.class.php';

	$upload_dir = $object->getDocumentDir();
	$nbfiles = is_dir($upload_dir) ? count(dol_dir_list($upload_dir, 'files', 0, '', '(\.meta|_preview.*\.png)$')) : 0;
	$nblinks = class_exists('Link') ? (int) Link::count($db, $object->element, (int) $object->id) : 0;

	return $nbfiles + $nblinks;
}

/**
 * Count native Agenda events linked to a weekly report.
 *
 * @param	WeeklyReport	$object	Weekly report
 * @return	int
 */
function weeklyreportCountEvents($object)
{
	global $db;

	if (empty($object->id) || !function_exists('getEntity')) {
		return 0;
	}

	$elementtypes = array($object->element);
	if (!empty($object->module)) {
		$elementtypes[] = $object->element.'@'.$object->module;
	}
	$elementtypes = array_unique($elementtypes);
	$quotedtypes = array();
	foreach ($elementtypes as $elementtype) {
		$quotedtypes[] = "'".$db->escape($elementtype)."'";
	}

	$sql = "SELECT COUNT(a.id) as nb";
	$sql .= " FROM ".$db->prefix()."actioncomm as a";
	$sql .= " WHERE a.entity IN (".getEntity('agenda').")";
	$sql .= " AND a.fk_element = ".((int) $object->id);
	$sql .= " AND a.elementtype IN (".implode(',', $quotedtypes).")";

	$resql = $db->query($sql);
	if ($resql) {
		$obj = $db->fetch_object($resql);
		return (int) $obj->nb;
	}

	return 0;
}

/**
 * Return standard setup back link.
 *
 * @return	string
 */
function saweeklyreportAdminModuleListLink()
{
	global $langs;

	return '<a href="'.DOL_URL_ROOT.'/admin/modules.php?search_keyword='.urlencode('saweeklyreport').'">'
		.img_picto($langs->trans('BackToModuleList'), 'back', 'class="pictofixedwidth"')
		.'<span class="hideonsmartphone">'.$langs->trans('BackToModuleList').'</span></a>';
}

/**
 * Build native "link to object" controls for sources that are not thirdparty scoped.
 *
 * @param	CommonObject	$object				Object
 * @param	int			$permissiontoadd	Can add links
 * @return	array{linktoelem:string,htmltoenteralink:string}
 */
function saweeklyreportBuildNativeSourceLinkBlock($object, $permissiontoadd)
{
	global $conf, $langs, $user;

	$result = array('linktoelem' => '', 'htmltoenteralink' => '');
	if (empty($permissiontoadd) || empty($object->id) || getDolGlobalString('MAIN_HIDE_LINK_BY_REF_IN_LINKTO')) {
		return $result;
	}

	$possiblelinks = array();
	if (isModEnabled('ticket') && is_object($user) && $user->hasRight('ticket', 'read')) {
		$possiblelinks['ticket'] = 'LinkToTicket';
	}
	if (isModEnabled('intervention') && is_object($user) && $user->hasRight('ficheinter', 'lire')) {
		$possiblelinks['fichinter'] = 'LinkToIntervention';
	}
	if (empty($possiblelinks)) {
		return $result;
	}

	$linktoelemlist = '';
	$htmltoenteralink = '';
	foreach ($possiblelinks as $element => $labelkey) {
		$domid = 'saweeklyreport_'.$element.'list';
		$htmltoenteralink .= '<div id="'.dol_escape_htmltag($domid).'"'.(empty($conf->use_javascript_ajax) ? '' : ' style="display:none"').'>';
		$htmltoenteralink .= '<br>'."\n";
		$htmltoenteralink .= '<!-- form to add a native source link by reference -->'."\n";
		$htmltoenteralink .= '<form action="'.dol_escape_htmltag($_SERVER['PHP_SELF']).'" method="POST" name="formlinkedbyref'.$element.'">';
		$htmltoenteralink .= '<input type="hidden" name="token" value="'.newToken().'">';
		$htmltoenteralink .= '<input type="hidden" name="action" value="addlinkbyref">';
		$htmltoenteralink .= '<input type="hidden" name="id" value="'.((int) $object->id).'">';
		$htmltoenteralink .= '<input type="hidden" name="addlink" value="'.dol_escape_htmltag($element).'">';
		$htmltoenteralink .= '<table class="noborder">';
		$htmltoenteralink .= '<tr class="liste_titre">';
		$htmltoenteralink .= '<td class="center"><input type="text" placeholder="'.dol_escape_htmltag($langs->trans('Ref')).'" name="reftolinkto" value="'.dol_escape_htmltag(GETPOST('reftolinkto', 'alpha')).'">&nbsp;';
		$htmltoenteralink .= '<input type="submit" class="button smallpaddingimp valignmiddle" value="'.$langs->trans('ToLink').'">&nbsp;';
		$htmltoenteralink .= '<input type="submit" class="button smallpaddingimp" name="cancel" value="'.$langs->trans('Cancel').'"></td>';
		$htmltoenteralink .= '</tr>';
		$htmltoenteralink .= '</table>';
		$htmltoenteralink .= '</form>';
		$htmltoenteralink .= '</div>';

		$linktoelemlist .= '<li><a href="#linkto'.$element.'" class="saweeklyreportlinkto dropdowncloseonclick" rel="'.dol_escape_htmltag($domid).'">'.$langs->trans($labelkey).'</a></li>';
	}

	$linktoelem = '<dl class="dropdown" id="saweeklyreportlinktoobjectname">';
	if (!empty($conf->use_javascript_ajax)) {
		$linktoelem .= '<dt><a href="#saweeklyreportlinktoobjectname"><span class="fas fa-link paddingrightonly"></span>'.$langs->trans('LinkTo').'...</a></dt>';
	}
	$linktoelem .= '<dd><div class="multiselectlinkto"><ul class="ulselectedfields">'.$linktoelemlist.'</ul></div></dd></dl>';

	if (!empty($conf->use_javascript_ajax)) {
		$htmltoenteralink .= '<script nonce="'.getNonce().'">
			jQuery(document).ready(function() {
				jQuery(".saweeklyreportlinkto").click(function() {
					jQuery("#"+jQuery(this).attr("rel")).toggle();
				});
			});
		</script>';
	}

	$result['linktoelem'] = $linktoelem;
	$result['htmltoenteralink'] = $htmltoenteralink;

	return $result;
}
