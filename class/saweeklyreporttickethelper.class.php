<?php
/* Copyright (C) 2026  Pierre Ardoin <developpeur@lesmetiersdubatiment.fr> */

/**
 * Helpers around native ticket dictionaries.
 */
class SAWeeklyReportTicketHelper
{
	/**
	 * Return allowed dictionary metadata.
	 *
	 * @return	array<string,array{entity:string,prefix:string}>
	 */
	private static function getDictionaries()
	{
		return array(
			'c_ticket_type' => array('entity' => 'c_ticket_type', 'prefix' => 'TicketTypeShort'),
			'c_ticket_category' => array('entity' => 'c_ticket_category', 'prefix' => 'TicketCategoryShort'),
			'c_ticket_severity' => array('entity' => 'c_ticket_severity', 'prefix' => 'TicketSeverityShort'),
		);
	}

	/**
	 * Normalize a CSV or array of codes.
	 *
	 * @param	string|string[]	$codes	Raw code list
	 * @return	string[]
	 */
	public static function normalizeCodeList($codes)
	{
		if (!is_array($codes)) {
			$codes = explode(',', (string) $codes);
		}

		$clean = array();
		foreach ($codes as $code) {
			$code = trim((string) $code);
			if ($code !== '' && preg_match('/^[A-Za-z0-9_-]+$/', $code)) {
				$clean[$code] = $code;
			}
		}

		return array_values($clean);
	}

	/**
	 * Return active dictionary options.
	 *
	 * @param	DoliDB		$db			Database handler
	 * @param	Translate	$langs		Language handler
	 * @param	string		$table		Dictionary table
	 * @return	array<string,string>
	 */
	public static function getActiveDictionaryOptions($db, $langs, $table)
	{
		$dictionaries = self::getDictionaries();
		if (empty($dictionaries[$table])) {
			return array();
		}

		$meta = $dictionaries[$table];
		$options = array();
		$sql = "SELECT code, label";
		$sql .= " FROM ".$db->prefix().$table;
		$sql .= " WHERE active = 1";
		$sql .= " AND entity IN (".getEntity($meta['entity']).")";
		$sql .= " ORDER BY pos ASC, label ASC";

		$resql = $db->query($sql);
		if (!$resql) {
			return $options;
		}

		while ($obj = $db->fetch_object($resql)) {
			$code = (string) $obj->code;
			$label = (string) $obj->label;
			$transkey = $meta['prefix'].$code;
			if (is_object($langs) && $langs->trans($transkey) !== $transkey) {
				$label = $langs->trans($transkey);
			} elseif (is_object($langs) && $langs->trans($code) !== $code) {
				$label = $langs->trans($code);
			}
			$options[$code] = $label;
		}
		$db->free($resql);

		return $options;
	}

	/**
	 * Keep only active codes from a native ticket dictionary.
	 *
	 * @param	DoliDB			$db		Database handler
	 * @param	string|string[]	$codes	Raw codes
	 * @param	string			$table	Dictionary table
	 * @return	string[]
	 */
	public static function cleanTicketDictionaryCodes($db, $codes, $table)
	{
		global $langs;

		$options = self::getActiveDictionaryOptions($db, $langs, $table);
		$valid = array_flip(array_keys($options));
		$clean = array();
		foreach (self::normalizeCodeList($codes) as $code) {
			if (isset($valid[$code])) {
				$clean[$code] = $code;
			}
		}

		return array_values($clean);
	}

	/**
	 * Return active code or empty string.
	 *
	 * @param	DoliDB	$db		Database handler
	 * @param	string	$code	Raw code
	 * @param	string	$table	Dictionary table
	 * @return	string
	 */
	public static function cleanTicketDictionaryCode($db, $code, $table)
	{
		$codes = self::cleanTicketDictionaryCodes($db, array($code), $table);

		return empty($codes) ? '' : $codes[0];
	}

	/**
	 * Return label for a dictionary code.
	 *
	 * @param	DoliDB		$db		Database handler
	 * @param	Translate	$langs	Language handler
	 * @param	string		$table	Dictionary table
	 * @param	string		$code	Code
	 * @return	string
	 */
	public static function getTicketDictionaryLabel($db, $langs, $table, $code)
	{
		$options = self::getActiveDictionaryOptions($db, $langs, $table);

		return $options[$code] ?? $code;
	}

	/**
	 * Return a select2-ready native dictionary select.
	 *
	 * @param	DoliDB		$db			Database handler
	 * @param	Translate	$langs		Language handler
	 * @param	string		$table		Dictionary table
	 * @param	string		$selected	Selected code
	 * @param	string		$htmlname	HTML name
	 * @param	string		$htmlid		HTML id
	 * @param	string		$morecss	More CSS classes
	 * @param	bool		$empty		Allow empty value
	 * @return	string
	 */
	public static function selectTicketDictionary($db, $langs, $table, $selected, $htmlname, $htmlid, $morecss = '', $empty = true)
	{
		$options = self::getActiveDictionaryOptions($db, $langs, $table);

		$out = '<select class="flat'.($morecss ? ' '.$morecss : '').'" id="'.dol_escape_htmltag($htmlid).'" name="'.dol_escape_htmltag($htmlname).'">';
		if ($empty) {
			$out .= '<option value="">&nbsp;</option>';
		}
		foreach ($options as $code => $label) {
			$out .= '<option value="'.dol_escape_htmltag($code).'"'.($selected === $code ? ' selected="selected"' : '').'>'.dol_escape_htmltag($label).'</option>';
		}
		$out .= '</select>';
		if (function_exists('ajax_combobox')) {
			$out .= ajax_combobox($htmlid);
		}

		return $out;
	}
}
