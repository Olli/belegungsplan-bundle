<?php
/**
* Contao Open Source CMS
*
* Copyright (c) Jan Karai
*
* @license LGPL-3.0+
*/
namespace Mailwurm\Belegung;
use Psr\Log\LogLevel;
use Contao\CoreBundle\Monolog\ContaoContext;
use Patchwork\Utf8;
use Contao\Contao;
use Mailwurm\Belegung\BelegungsplanObjekteModel;
use Contao\Model;
use Contao\Model\Collection;

/**
* Class ModuleBelegungsplan
*
* @property array $belegungsplan_categories
*
* @author Jan Karai <https://www.sachsen-it.de>
*/
class ModuleBelegungsplan extends \Module
{
	/**
	* Template
	* @var string
	*/
	protected $strTemplate = 'mod_belegungsplan';
	/**
	* Target pages
	* @var array
	*/
	protected $arrTargets = array();
	/**
	* Display a wildcard in the back end
	*
	* @return string
	*/
	public function generate() 
	{
		if (TL_MODE == 'BE') 
		{
			/** @var BackendTemplate|object $objTemplate */
			$objTemplate = new \BackendTemplate('be_wildcard');
			$objTemplate->wildcard = '### ' . Utf8::strtoupper($GLOBALS['TL_LANG']['FMD']['belegungsplanlist'][0]) . ' ###';
			$objTemplate->title = $this->headline;
			$objTemplate->id = $this->id;
			$objTemplate->link = $this->name;
			$objTemplate->href = 'contao/main.php?do=themes&amp;table=tl_module&amp;act=edit&amp;id=' . $this->id;
			return $objTemplate->parse();
		}
		$this->belegungsplan_category = \StringUtil::deserialize($this->belegungsplan_categories);
		$this->belegungsplan_month = \StringUtil::deserialize($this->belegungsplan_month);
		
		// Return if there are no categories
		if (!is_array($this->belegungsplan_category) || empty($this->belegungsplan_category)) 
		{
			return '';
		}
		// Return if there are no month
		if (!is_array($this->belegungsplan_month) || empty($this->belegungsplan_month)) 
		{
			return '';
		}
		return parent::generate();
	}
	/**
	* Generate the module
	*/
	protected function compile() 
	{
		/** @var PageModel $objPage */
		global $objPage;
		
		/** @var BelegungsplanObjekteModel $objBelegungsplanObjekte */
		#global $objBelegungsplanObjekte;

		$blnClearInput = false;

		$intYear = \Input::get('year');
		$intMonth = \Input::get('month');
		
		// Aktuelle Periode bei Erstaufruf der Seite
		if (!isset($_GET['year']) && !isset($_GET['month']))
		{
			$intYear = date('Y');
			$blnClearInput = true;
		}
		
		$this->Template = new \FrontendTemplate($this->strTemplate);
		$this->Template->year = $intYear;
		$this->Template->month = $intMonth;
		
		$objBelegungsplanObjekte = \BelegungsplanObjekteModel::findAll();
		$this->Template->belegungsplan_objekte = $objBelegungsplanObjekte;
		
		$this->Template->belegungsplan_category = $this->belegungsplan_category;
		$this->Template->belegungsplan_month = $this->belegungsplan_month;
		
		
		
		
		
		
		// Clear the $_GET array (see #2445)
		if ($blnClearInput)
		{
			\Input::setGet('year', null);
			\Input::setGet('month', null);
		}
	}
}
