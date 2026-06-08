<?php
/* Copyright (C) 2026  Pierre Ardoin <developpeur@lesmetiersdubatiment.fr> */

/**
 * Complete substitution array with weekly report values.
 *
 * @param	array<string,string>	$substitutionarray	Substitution array
 * @param	Translate			$outputlangs		Output language
 * @param	CommonObject		$object				Object
 * @param	array|null			$parameters			Parameters
 * @return	void
 */
function saweeklyreport_completesubstitutionarray(&$substitutionarray, $outputlangs, $object, $parameters = null)
{
	if (!is_object($object) || empty($object->element) || $object->element !== 'weeklyreport') {
		return;
	}

	$authorfullname = '';
	$authoremail = '';
	if (!empty($object->fk_user_creat)) {
		require_once DOL_DOCUMENT_ROOT.'/user/class/user.class.php';
		$author = new User($object->db);
		if ($author->fetch((int) $object->fk_user_creat) > 0) {
			$authorfullname = $author->getFullName($outputlangs);
			$authoremail = (string) $author->email;
		}
	}

	$values = array(
		'saweeklyreport_ref' => (string) $object->ref,
		'saweeklyreport_label' => (string) $object->label,
		'saweeklyreport_status' => (string) $object->getLibStatut(0),
		'saweeklyreport_year' => (string) $object->year,
		'saweeklyreport_week' => (string) $object->week,
		'saweeklyreport_week_installed_power' => (string) price($object->week_installed_power),
		'saweeklyreport_month_installed_power' => (string) price($object->month_installed_power),
		'saweeklyreport_annual_installed_power' => (string) price($object->annual_installed_power),
		'saweeklyreport_annual_target_power' => (string) price($object->annual_target_power),
		'saweeklyreport_annual_completion_rate' => (string) price($object->annual_completion_rate),
		'saweeklyreport_url' => dol_buildpath('/saweeklyreport/weeklyreport_card.php?id='.(int) $object->id, 2),
		'saweeklyreport_author_fullname' => $authorfullname,
		'saweeklyreport_author_email' => $authoremail,
	);

	foreach ($values as $key => $value) {
		$substitutionarray[$key] = (string) $value;
		$substitutionarray['__'.strtoupper($key).'__'] = (string) $value;
	}
}
