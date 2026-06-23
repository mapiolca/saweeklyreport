<?php
/* Copyright (C) 2026  Pierre Ardoin <developpeur@lesmetiersdubatiment.fr> */

dol_include_once('/saweeklyreport/core/modules/saweeklyreport/modules_weeklyreport.php');
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/pdf.lib.php';

/**
 * TCPDF document model for weekly reports.
 */
class pdf_weeklyreport_powerpoint extends ModelePDFWeeklyReport
{
	/**
	 * @var DoliDB
	 */
	public $db;

	/**
	 * @var string
	 */
	public $name;

	/**
	 * @var string
	 */
	public $description;

	/**
	 * @var int
	 */
	public $update_main_doc_field = 1;

	/**
	 * @var string
	 */
	public $type = 'pdf';

	/**
	 * @var array{0:int,1:int}
	 */
	public $phpmin = array(8, 0);

	/**
	 * @var string
	 */
	public $version = 'dolibarr';

	/**
	 * @var Societe
	 */
	public $emetteur;

	/**
	 * @var float
	 */
	public $page_largeur;

	/**
	 * @var float
	 */
	public $page_hauteur;

	/**
	 * @var array{0:float,1:float}
	 */
	public $format;

	/**
	 * @var int
	 */
	public $marge_gauche;

	/**
	 * @var int
	 */
	public $marge_droite;

	/**
	 * @var int
	 */
	public $marge_haute;

	/**
	 * @var int
	 */
	public $marge_basse;

	/**
	 * @var string
	 */
	public $error = '';

	/**
	 * @var string[]
	 */
	public $errors = array();

	/**
	 * @var array<string,string>
	 */
	public $result = array();

	/**
	 * @var string
	 */
	public $watermark = '';

	/**
	 * @var array<int,bool>
	 */
	private $printedfooters = array();

	/**
	 * Constructor.
	 *
	 * @param	DoliDB	$db	Database handler
	 */
	public function __construct($db)
	{
		global $langs, $mysoc;

		$this->db = $db;
		$langs->load('saweeklyreport@saweeklyreport');
		$this->name = 'pdf_weeklyreport_powerpoint';
		$this->description = $langs->trans('WeeklyReportPdfTcpdfModel');

		$formatarray = pdf_getFormat();
		$this->page_largeur = (float) $formatarray['width'];
		$this->page_hauteur = (float) $formatarray['height'];
		$this->format = array($this->page_largeur, $this->page_hauteur);
		$this->marge_gauche = getDolGlobalInt('MAIN_PDF_MARGIN_LEFT', 10);
		$this->marge_droite = getDolGlobalInt('MAIN_PDF_MARGIN_RIGHT', 10);
		$this->marge_haute = getDolGlobalInt('MAIN_PDF_MARGIN_TOP', 10);
		$this->marge_basse = getDolGlobalInt('MAIN_PDF_MARGIN_BOTTOM', 10);
		$this->emetteur = $mysoc;
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 * Build and write the PDF document.
	 *
	 * @param	WeeklyReport	$object				Source object
	 * @param	Translate		$outputlangs		Output language
	 * @param	string			$srctemplatepath	Unused
	 * @param	int				$hidedetails		Unused
	 * @param	int				$hidedesc			Unused
	 * @param	int				$hideref			Unused
	 * @return	int
	 */
	public function write_file($object, $outputlangs, $srctemplatepath = '', $hidedetails = 0, $hidedesc = 0, $hideref = 0)
	{
		// phpcs:enable
		global $conf, $hookmanager, $langs, $mysoc, $user;

		if (!is_object($outputlangs)) {
			$outputlangs = $langs;
		}
		$outputlangs->loadLangs(array('main', 'saweeklyreport@saweeklyreport'));
		if (empty($object->id)) {
			$this->error = 'ErrorObjectMustBeFetched';
			return -1;
		}

		$object->fetchLines();
		$dir = $object->getDocumentDir();
		if (!file_exists($dir) && dol_mkdir($dir) < 0) {
			$this->error = $langs->transnoentities('ErrorCanNotCreateDir', $dir);
			return -1;
		}

		$file = $dir.'/'.$object->getDocumentBaseFilename().'.pdf';
		if (!is_object($hookmanager)) {
			include_once DOL_DOCUMENT_ROOT.'/core/class/hookmanager.class.php';
			$hookmanager = new HookManager($this->db);
		}
		$hookmanager->initHooks(array('pdfgeneration'));
		$parameters = array('file' => $file, 'object' => $object, 'outputlangs' => $outputlangs);
		$action = '';
		$reshook = $hookmanager->executeHooks('beforePDFCreation', $parameters, $object, $action);
		if ($reshook < 0) {
			$this->error = $hookmanager->error;
			$this->errors = $hookmanager->errors;
			return -1;
		}

		$pdf = pdf_getInstance($this->format);
		$defaultfontsize = pdf_getPDFFontSize($outputlangs);
		$this->printedfooters = array();
		$heightforfooter = $this->getFooterHeight();
		if (class_exists('TCPDF')) {
			$pdf->setPrintHeader(false);
			$pdf->setPrintFooter(false);
		}
		$pdf->SetMargins($this->marge_gauche, $this->marge_haute, $this->marge_droite);
		$pdf->SetAutoPageBreak(true, $heightforfooter);
		$pdf->SetFont(pdf_getPDFFont($outputlangs), '', $defaultfontsize);
		$pdf->SetTitle($this->txt($outputlangs, $object->ref));
		$pdf->SetSubject($this->txt($outputlangs, $outputlangs->transnoentities('WeeklyReport')));
		$pdf->SetCreator('Dolibarr '.DOL_VERSION);
		$pdf->SetAuthor($this->txt($outputlangs, is_object($user) ? $user->getFullName($outputlangs) : ''));
		if (getDolGlobalString('MAIN_DISABLE_PDF_COMPRESSION')) {
			$pdf->SetCompression(false);
		}

		$pdf->Open();
		$pdf->AddPage();
		$pdf->setPageOrientation('', true, $heightforfooter);

		$data = $object->getDocumentData($outputlangs);
		$rows = $object->getDocumentServiceRows($outputlangs);
		$this->renderHeader($pdf, $object, $outputlangs, $data, $defaultfontsize);
		$this->renderMetrics($pdf, $outputlangs, $data, $defaultfontsize);
		$this->renderTextSection($pdf, $object, $outputlangs, $outputlangs->transnoentities('WeeklyReportPreviousWeekFeedback'), $data['previous_week_feedback'], $defaultfontsize);
		$this->renderTextSection($pdf, $object, $outputlangs, $outputlangs->transnoentities('WeeklyReportFieldReturns'), $data['field_returns'], $defaultfontsize);
		$this->renderTextSection($pdf, $object, $outputlangs, $outputlangs->transnoentities('WeeklyReportCurrentWeekGoal'), $data['current_week_goal'], $defaultfontsize);
		$this->renderServiceRows($pdf, $object, $outputlangs, $rows, $defaultfontsize);
		$this->renderTextSection($pdf, $object, $outputlangs, $outputlangs->transnoentities('WeeklyReportSafetyMessage'), $data['safety_message'], $defaultfontsize);
		$this->renderTextSection($pdf, $object, $outputlangs, $outputlangs->transnoentities('WeeklyReportVehicleLoadingReminder'), $data['technician_detail'], $defaultfontsize);

		$pagecount = $pdf->getNumPages();
		for ($page = 1; $page <= $pagecount; $page++) {
			$pdf->setPage($page);
			$this->renderPageFootOnce($pdf, $object, $outputlangs, ($page < $pagecount ? 1 : 0));
		}

		$pdf->Output($file, 'F');
		$mainumask = getDolGlobalString('MAIN_UMASK');
		if ($mainumask !== '') {
			@chmod($file, octdec($mainumask));
		}
		$this->result = array('fullpath' => $file);

		$hookmanager->executeHooks('afterPDFCreation', $parameters, $object, $action);

		return 1;
	}

	/**
	 * Render document header.
	 *
	 * @param	TCPDF		$pdf				PDF
	 * @param	WeeklyReport	$object			Report
	 * @param	Translate	$outputlangs		Output language
	 * @param	array<string,string>	$data		Document data
	 * @param	int			$defaultfontsize	Font size
	 * @return	void
	 */
	private function renderHeader(&$pdf, $object, $outputlangs, $data, $defaultfontsize)
	{
		global $conf;

		$y = $this->marge_haute;
		if (!empty($this->emetteur->logo)) {
			$logodir = getMultidirOutput($object, 'mycompany');
			if (empty($logodir) || strpos((string) $logodir, 'error-') === 0) {
				$logodir = $conf->mycompany->dir_output;
			}
			$logo = $logodir.'/logos/'.$this->emetteur->logo;
			if (is_readable($logo)) {
				$pdf->Image($logo, $this->marge_gauche, $y, 0, 15);
			}
		}

		$pdf->SetTextColor(20, 38, 58);
		$pdf->SetFont('', 'B', $defaultfontsize + 6);
		$pdf->SetXY($this->marge_gauche + 45, $y);
		$pdf->MultiCell($this->page_largeur - $this->marge_gauche - $this->marge_droite - 45, 8, $this->txt($outputlangs, $data['report_title']), 0, 'R');
		$pdf->SetFont('', '', $defaultfontsize);
		$pdf->SetX($this->marge_gauche + 45);
		$pdf->MultiCell($this->page_largeur - $this->marge_gauche - $this->marge_droite - 45, 6, $this->txt($outputlangs, $data['week_title'].' - '.$object->ref), 0, 'R');
		$pdf->Ln(8);
		$pdf->SetDrawColor(90, 112, 140);
		$pdf->Line($this->marge_gauche, $pdf->GetY(), $this->page_largeur - $this->marge_droite, $pdf->GetY());
		$pdf->Ln(5);
	}

	/**
	 * Render KPI metrics.
	 *
	 * @param	TCPDF				$pdf				PDF
	 * @param	Translate			$outputlangs		Output language
	 * @param	array<string,string>	$data				Document data
	 * @param	int					$defaultfontsize	Font size
	 * @return	void
	 */
	private function renderMetrics(&$pdf, $outputlangs, $data, $defaultfontsize)
	{
		$width = ($this->page_largeur - $this->marge_gauche - $this->marge_droite - 6) / 3;
		$y = $pdf->GetY();
		$x = $this->marge_gauche;
		$this->renderMetricBox($pdf, $outputlangs, $x, $y, $width, $outputlangs->transnoentities('WeeklyReportWeekInstalledPower'), $data['week_installed_power'], $defaultfontsize);
		$this->renderMetricBox($pdf, $outputlangs, $x + $width + 3, $y, $width, $outputlangs->transnoentities('WeeklyReportMonthInstalledPower'), $data['month_installed_power'], $defaultfontsize);
		$this->renderMetricBox($pdf, $outputlangs, $x + (($width + 3) * 2), $y, $width, $outputlangs->transnoentities('WeeklyReportAnnualInstalledPower'), $data['annual_installed_power'], $defaultfontsize);
		$pdf->SetY($y + 25);
		$pdf->Ln(4);
	}

	/**
	 * Render one KPI box.
	 *
	 * @param	TCPDF		$pdf				PDF
	 * @param	Translate	$outputlangs		Output language
	 * @param	float		$x					X
	 * @param	float		$y					Y
	 * @param	float		$width				Width
	 * @param	string		$label				Label
	 * @param	string		$value				Value
	 * @param	int			$defaultfontsize	Font size
	 * @return	void
	 */
	private function renderMetricBox(&$pdf, $outputlangs, $x, $y, $width, $label, $value, $defaultfontsize)
	{
		$pdf->SetFillColor(244, 247, 250);
		$pdf->SetDrawColor(205, 214, 225);
		$pdf->RoundedRect($x, $y, $width, 22, 1.5, '1111', 'DF');
		$pdf->SetXY($x + 3, $y + 3);
		$pdf->SetFont('', '', $defaultfontsize - 1);
		$pdf->SetTextColor(80, 92, 110);
		$pdf->MultiCell($width - 6, 5, $this->txt($outputlangs, $label), 0, 'L');
		$pdf->SetX($x + 3);
		$pdf->SetFont('', 'B', $defaultfontsize + 4);
		$pdf->SetTextColor(20, 38, 58);
		$pdf->MultiCell($width - 6, 8, $this->txt($outputlangs, $value), 0, 'L');
	}

	/**
	 * Render text section.
	 *
	 * @param	TCPDF		$pdf				PDF
	 * @param	WeeklyReport	$object			Report
	 * @param	Translate	$outputlangs		Output language
	 * @param	string		$title				Title
	 * @param	string		$text				Text
	 * @param	int			$defaultfontsize	Font size
	 * @return	void
	 */
	private function renderTextSection(&$pdf, $object, $outputlangs, $title, $text, $defaultfontsize)
	{
		$text = trim((string) $text);
		if ($text === '') {
			$text = $outputlangs->transnoentities('None');
		}
		$width = $this->page_largeur - $this->marge_gauche - $this->marge_droite;
		$height = max(18, $pdf->getStringHeight($width, $this->txt($outputlangs, $text)) + 12);
		$this->ensureSpace($pdf, $object, $outputlangs, $height);
		$pdf->SetFont('', 'B', $defaultfontsize + 1);
		$pdf->SetTextColor(20, 38, 58);
		$pdf->MultiCell($width, 6, $this->txt($outputlangs, $title), 0, 'L');
		$pdf->SetFont('', '', $defaultfontsize);
		$pdf->SetTextColor(30, 30, 30);
		$pdf->MultiCell($width, 5, $this->txt($outputlangs, $text), 0, 'L');
		$pdf->Ln(3);
	}

	/**
	 * Render service table.
	 *
	 * @param	TCPDF					$pdf				PDF
	 * @param	WeeklyReport			$object				Report
	 * @param	Translate				$outputlangs		Output language
	 * @param	array<int,array<string,string>>	$rows	Rows
	 * @param	int						$defaultfontsize	Font size
	 * @return	void
	 */
	private function renderServiceRows(&$pdf, $object, $outputlangs, $rows, $defaultfontsize)
	{
		$this->ensureSpace($pdf, $object, $outputlangs, 35);
		$fullwidth = $this->page_largeur - $this->marge_gauche - $this->marge_droite;
		$pdf->SetFont('', 'B', $defaultfontsize + 1);
		$pdf->SetTextColor(20, 38, 58);
		$pdf->MultiCell($fullwidth, 6, $this->txt($outputlangs, $outputlangs->transnoentities('WeeklyReportServiceLines')), 0, 'L');

		if (empty($rows)) {
			$pdf->SetFont('', '', $defaultfontsize);
			$pdf->MultiCell($fullwidth, 6, $this->txt($outputlangs, $outputlangs->transnoentities('NoRecordFound')), 0, 'L');
			$pdf->Ln(3);
			return;
		}

		$widths = array(24, 24, 24, 48, $fullwidth - 120);
		$headers = array('Type', 'WeeklyReportTicketGroup', 'WeeklyReportTicketSeverity', 'Label', 'Description');
		$pdf->SetFillColor(230, 235, 242);
		$pdf->SetDrawColor(210, 218, 228);
		$pdf->SetFont('', 'B', $defaultfontsize - 1);
		foreach ($headers as $i => $header) {
			$pdf->Cell($widths[$i], 7, $this->txt($outputlangs, $outputlangs->transnoentities($header)), 1, 0, 'L', true);
		}
		$pdf->Ln();
		$pdf->SetFont('', '', $defaultfontsize - 1);
		foreach ($rows as $row) {
			$texts = array($row['type'], $row['group'], $row['severity'], $row['label'], $row['description']);
			$height = 6;
			foreach ($texts as $i => $text) {
				$height = max($height, $pdf->getStringHeight($widths[$i], $this->txt($outputlangs, $text)) + 2);
			}
			$this->ensureSpace($pdf, $object, $outputlangs, $height + 2);
			$x = $this->marge_gauche;
			$y = $pdf->GetY();
			foreach ($texts as $i => $text) {
				$pdf->SetXY($x, $y);
				$pdf->MultiCell($widths[$i], $height, $this->txt($outputlangs, $text), 1, 'L');
				$x += $widths[$i];
			}
			$pdf->SetY($y + $height);
		}
		$pdf->Ln(3);
	}

	/**
	 * Add a page when the next block would overlap the footer area.
	 *
	 * @param	TCPDF		$pdf			PDF
	 * @param	WeeklyReport	$object		Report
	 * @param	Translate	$outputlangs	Output language
	 * @param	float		$neededheight	Needed height
	 * @return	void
	 */
	private function ensureSpace(&$pdf, $object, $outputlangs, $neededheight)
	{
		$heightforfooter = $this->getFooterHeight();
		$limit = $this->page_hauteur - $heightforfooter;
		if ($pdf->GetY() + $neededheight > $limit) {
			$this->renderPageFootOnce($pdf, $object, $outputlangs, 1);
			$pdf->AddPage();
			$pdf->setPageOrientation('', true, $heightforfooter);
		}
	}

	/**
	 * Return reserved footer height.
	 *
	 * @return	int
	 */
	private function getFooterHeight()
	{
		$freetextheight = getDolGlobalInt('MAIN_PDF_FREETEXT_HEIGHT', 5);
		$companyheight = getDolGlobalInt('MAIN_GENERATE_DOCUMENTS_SHOW_FOOT_DETAILS') ? 18 : 28;

		return (int) ($this->marge_basse + $companyheight + max(12, $freetextheight));
	}

	/**
	 * Render page footer once.
	 *
	 * @param	TCPDF		$pdf			PDF
	 * @param	WeeklyReport	$object		Report
	 * @param	Translate	$outputlangs	Output language
	 * @param	int			$hidefreetext	Hide free text
	 * @return	void
	 */
	private function renderPageFootOnce(&$pdf, $object, $outputlangs, $hidefreetext)
	{
		$page = (int) $pdf->getPage();
		if (!empty($this->printedfooters[$page])) {
			return;
		}

		$this->_pagefoot($pdf, $object, $outputlangs, $hidefreetext);
		$this->printedfooters[$page] = true;
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.PublicUnderscore
	/**
	 * Show page footer.
	 *
	 * @param	TCPDF		$pdf			PDF
	 * @param	WeeklyReport	$object		Report
	 * @param	Translate	$outputlangs	Output language
	 * @param	int			$hidefreetext	Hide free text
	 * @return	int
	 */
	protected function _pagefoot(&$pdf, $object, $outputlangs, $hidefreetext = 0)
	{
		// phpcs:enable
		$showdetails = getDolGlobalInt('MAIN_GENERATE_DOCUMENTS_SHOW_FOOT_DETAILS');
		return pdf_pagefoot($pdf, $outputlangs, 'SAWEEKLYREPORT_FREE_TEXT', $this->emetteur, $this->marge_basse, $this->marge_gauche, $this->page_hauteur, $object, $showdetails, $hidefreetext, $this->page_largeur, $this->watermark);
	}

	/**
	 * Convert text to output charset.
	 *
	 * @param	Translate	$outputlangs	Output language
	 * @param	string		$text			Text
	 * @return	string
	 */
	private function txt($outputlangs, $text)
	{
		return $outputlangs->convToOutputCharset((string) $text);
	}
}
