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
	 * Return native ticket data used by the weekly report.
	 *
	 * @param	DoliDB	$db			Database handler
	 * @param	int		$ticketid	Ticket ID
	 * @return	array<string,string|int>
	 */
	public static function getTicketData($db, $ticketid)
	{
		if ($ticketid <= 0 || !isModEnabled('ticket')) {
			return array();
		}

		dol_include_once('/ticket/class/ticket.class.php');
		if (!class_exists('Ticket')) {
			return array();
		}

		$ticket = new Ticket($db);
		if ($ticket->fetch($ticketid) <= 0) {
			return array();
		}
		$allowedentities = array_map('intval', explode(',', getEntity('ticket')));
		if (!empty($ticket->entity) && !in_array((int) $ticket->entity, $allowedentities, true)) {
			return array();
		}

		$subject = trim((string) $ticket->subject);
		$label = $subject !== '' ? $subject : (string) $ticket->ref;

		return array(
			'id' => (int) $ticket->id,
			'ref' => (string) $ticket->ref,
			'type_code' => (string) $ticket->type_code,
			'category_code' => (string) $ticket->category_code,
			'severity_code' => (string) $ticket->severity_code,
			'label' => $label,
			'description' => self::textFromHtml((string) $ticket->message),
			'status' => (int) $ticket->fk_statut,
			'date_service' => !empty($ticket->datec) ? dol_print_date($ticket->datec, '%Y-%m-%d') : '',
		);
	}

	/**
	 * Return read-only data for a report service line.
	 *
	 * @param	DoliDB				$db		Database handler
	 * @param	Translate			$langs	Language handler
	 * @param	WeeklyReportService	$line	Service line
	 * @return	array<string,string>
	 */
	public static function getServiceLineDisplayData($db, $langs, $line)
	{
		$data = array();
		if ((string) $line->source_element === 'ticket' && (int) $line->source_id > 0) {
			$data = self::getTicketData($db, (int) $line->source_id);
		}

		$typecode = (string) ($data['type_code'] ?? $line->service_type);
		$categorycode = (string) ($data['category_code'] ?? $line->ticket_category_code);
		$severitycode = (string) ($data['severity_code'] ?? $line->ticket_severity_code);

		return array(
			'type' => self::getTicketDictionaryLabel($db, $langs, 'c_ticket_type', $typecode),
			'group' => self::getTicketDictionaryLabel($db, $langs, 'c_ticket_category', $categorycode),
			'severity' => self::getTicketDictionaryLabel($db, $langs, 'c_ticket_severity', $severitycode),
			'label' => (string) ($data['label'] ?? $line->label),
			'description' => self::textFromHtml((string) ($data['description'] ?? $line->description)),
			'origin' => (string) ($data['ref'] ?? ((string) $line->source_element.((int) $line->source_id > 0 ? ' #'.((int) $line->source_id) : ''))),
		);
	}

	/**
	 * Convert HTML to plain text.
	 *
	 * @param	string	$html	HTML
	 * @return	string
	 */
	private static function textFromHtml($html)
	{
		$html = str_replace(array('<br>', '<br/>', '<br />', '</p>'), "\n", $html);
		$text = html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8');

		return trim($text);
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

	/**
	 * Return a select2-ready ticket select with readable ref/subject labels.
	 *
	 * @param	DoliDB		$db			Database handler
	 * @param	Translate	$langs		Language handler
	 * @param	int|string	$selected	Selected ticket id
	 * @param	string		$htmlname	HTML name
	 * @param	int			$limit		Maximum number of tickets
	 * @param	string		$morecss	More CSS classes
	 * @param	string|int	$showempty	Empty option behavior
	 * @return	string
	 */
	public static function selectTickets($db, $langs, $selected = '', $htmlname = 'ticketid', $limit = 50, $morecss = 'minwidth300', $showempty = '1')
	{
		if (!isModEnabled('ticket')) {
			return '';
		}

		$selectedid = (int) $selected;
		$limit = max(1, (int) $limit);
		$htmlid = preg_replace('/[^A-Za-z0-9_:-]/', '_', $htmlname);
		if (empty($htmlid)) {
			$htmlid = 'ticketid';
		}

		$out = '<select class="flat'.($morecss ? ' '.$morecss : '').'" id="'.dol_escape_htmltag($htmlid).'" name="'.dol_escape_htmltag($htmlname).'">';
		if ($showempty) {
			$textifempty = '';
			if (!is_numeric($showempty)) {
				$textifempty = $langs->trans((string) $showempty);
			}
			$out .= '<option value="0"'.($selectedid <= 0 ? ' selected="selected"' : '').'>'.dol_escape_htmltag($textifempty).'</option>';
		}

		$sql = "SELECT t.rowid, t.ref, t.subject";
		$sql .= " FROM ".$db->prefix()."ticket AS t";
		$sql .= " WHERE t.entity IN (".getEntity('ticket').")";
		$sql .= " ORDER BY t.ref ASC";
		$sql .= $db->plimit($limit, 0);

		$resql = $db->query($sql);
		if ($resql) {
			while (is_object($obj = $db->fetch_object($resql))) {
				$ref = (string) $obj->ref;
				$subject = trim((string) $obj->subject);
				$label = $subject !== '' ? $ref.' - '.$subject : $ref;
				$out .= '<option value="'.((int) $obj->rowid).'"'.(((int) $obj->rowid === $selectedid) ? ' selected="selected"' : '').'>'.dol_escape_htmltag($label).'</option>';
			}
			$db->free($resql);
		} else {
			dol_syslog(__METHOD__.': '.$db->lasterror(), LOG_ERR);
		}

		$out .= '</select>';
		if (!function_exists('ajax_combobox')) {
			require_once DOL_DOCUMENT_ROOT.'/core/lib/ajax.lib.php';
		}
		if (function_exists('ajax_combobox')) {
			$out .= ajax_combobox($htmlid);
		}

		return $out;
	}
}
