<?php
/* Copyright (C) 2026  Pierre Ardoin <developpeur@lesmetiersdubatiment.fr> */

/**
 * Compatibility matrix for SAWeeklyReport.
 */
class SAWeeklyReportCompatibility
{
	/**
	 * Check Dolibarr version.
	 *
	 * @param	string	$version	Minimum version
	 * @return	bool
	 */
	public static function isDolibarrVersionAtLeast($version)
	{
		return defined('DOL_VERSION') && version_compare(DOL_VERSION, $version, '>=');
	}

	/**
	 * Check PHP version.
	 *
	 * @param	string	$version	Minimum version
	 * @return	bool
	 */
	public static function isPhpVersionAtLeast($version)
	{
		return version_compare(PHP_VERSION, $version, '>=');
	}

	/**
	 * Return feature definitions.
	 *
	 * @return	array<string,array<string,mixed>>
	 */
	public static function getFeatures()
	{
		return array(
			'base_module' => array(
				'label' => 'SAWeeklyReportFeatureBase',
				'description' => 'SAWeeklyReportFeatureBaseDesc',
				'min_dolibarr' => '20.0.0',
				'min_php' => '8.0.0',
				'available' => self::isDolibarrVersionAtLeast('20.0.0') && self::isPhpVersionAtLeast('8.0.0'),
				'reason' => 'RequiresDolibarr20Php80',
			),
			'pptx_generation' => array(
				'label' => 'SAWeeklyReportFeaturePptx',
				'description' => 'SAWeeklyReportFeaturePptxDesc',
				'min_dolibarr' => '20.0.0',
				'min_php' => '8.0.0',
				'available' => class_exists('ZipArchive'),
				'reason' => 'RequiresZipArchive',
			),
			'pdf_tcpdf_generation' => array(
				'label' => 'SAWeeklyReportFeaturePdfTcpdf',
				'description' => 'SAWeeklyReportFeaturePdfTcpdfDesc',
				'min_dolibarr' => '20.0.0',
				'min_php' => '8.0.0',
				'available' => is_readable(DOL_DOCUMENT_ROOT.'/core/lib/pdf.lib.php'),
				'reason' => 'RequiresTcpdf',
			),
			'powerplantpv_orders' => array(
				'label' => 'SAWeeklyReportFeaturePowerPlantPVOrders',
				'description' => 'SAWeeklyReportFeaturePowerPlantPVOrdersDesc',
				'min_dolibarr' => '20.0.0',
				'min_php' => '8.0.0',
				'available' => isModEnabled('order') && isModEnabled('powerplantpv'),
				'reason' => 'RequiresOrderAndPowerPlantPV',
			),
			'interventions' => array(
				'label' => 'SAWeeklyReportFeatureInterventions',
				'description' => 'SAWeeklyReportFeatureInterventionsDesc',
				'min_dolibarr' => '20.0.0',
				'min_php' => '8.0.0',
				'available' => isModEnabled('intervention'),
				'reason' => 'RequiresFicheInter',
			),
			'tickets' => array(
				'label' => 'SAWeeklyReportFeatureTickets',
				'description' => 'SAWeeklyReportFeatureTicketsDesc',
				'min_dolibarr' => '20.0.0',
				'min_php' => '8.0.0',
				'available' => isModEnabled('ticket'),
				'reason' => 'RequiresTicket',
			),
			'agenda' => array(
				'label' => 'SAWeeklyReportFeatureAgenda',
				'description' => 'SAWeeklyReportFeatureAgendaDesc',
				'min_dolibarr' => '20.0.0',
				'min_php' => '8.0.0',
				'available' => isModEnabled('agenda'),
				'reason' => 'RequiresAgenda',
			),
			'api' => array(
				'label' => 'SAWeeklyReportFeatureApi',
				'description' => 'SAWeeklyReportFeatureApiDesc',
				'min_dolibarr' => '20.0.0',
				'min_php' => '8.0.0',
				'available' => class_exists('DolibarrApi') || is_readable(DOL_DOCUMENT_ROOT.'/api/class/api.class.php'),
				'reason' => 'RequiresDolibarr20Php80',
			),
			'multicompany' => array(
				'label' => 'SAWeeklyReportFeatureMulticompany',
				'description' => 'SAWeeklyReportFeatureMulticompanyDesc',
				'min_dolibarr' => '20.0.0',
				'min_php' => '8.0.0',
				'available' => function_exists('getEntity'),
				'reason' => 'RequiresGetEntity',
			),
		);
	}

	/**
	 * Check if a feature is available.
	 *
	 * @param	string	$code	Feature code
	 * @return	bool
	 */
	public static function isFeatureAvailable($code)
	{
		$features = self::getFeatures();

		return !empty($features[$code]['available']);
	}

	/**
	 * Return unavailable features.
	 *
	 * @return	array<string,array<string,mixed>>
	 */
	public static function getUnavailableFeatures()
	{
		$unavailable = array();
		foreach (self::getFeatures() as $code => $feature) {
			if (empty($feature['available'])) {
				$unavailable[$code] = $feature;
			}
		}

		return $unavailable;
	}
}
