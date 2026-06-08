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
	$head[$h][1] = $langs->trans('Card');
	$head[$h][2] = 'card';
	$h++;

	$head[$h][0] = dol_buildpath('/saweeklyreport/weeklyreport_document.php', 1).'?id='.$id;
	$head[$h][1] = $langs->trans('Documents');
	$head[$h][2] = 'documents';
	$h++;

	$head[$h][0] = dol_buildpath('/saweeklyreport/weeklyreport_note.php', 1).'?id='.$id;
	$head[$h][1] = $langs->trans('Notes');
	$head[$h][2] = 'notes';
	$h++;

	if (isModEnabled('agenda')) {
		$head[$h][0] = dol_buildpath('/saweeklyreport/weeklyreport_agenda.php', 1).'?id='.$id;
		$head[$h][1] = $langs->trans('Agenda');
		$head[$h][2] = 'agenda';
		$h++;
	}

	return $head;
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
