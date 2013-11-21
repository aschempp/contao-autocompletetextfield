<?php

/**
 * Contao Open Source CMS
 * Copyright (C) 2005-2013 Leo Feyer
 *
 * Formerly known as TYPOlight Open Source CMS.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 *
 *
 * PHP version 5
 * @copyright  terminal42 gmbh 2008-2013
 * @author     Andreas Schempp <andreas.schempp@terminal42.ch>
 * @license    http://opensource.org/licenses/lgpl-3.0.html
 */


/**
 * Class TextField
 *
 * Provide methods to handle text fields.
 * @copyright  Leo Feyer 2005
 * @author     Leo Feyer <leo@typolight.org>
 * @package    Controller
 */
class AutocompleteTextField extends Widget
{

	/**
	 * Submit user input
	 * @var boolean
	 */
	protected $blnSubmitInput = true;

	/**
	 * Template
	 * @var string
	 */
	protected $strTemplate = 'be_widget';

	/**
	 * Contents
	 * @var array
	 */
	protected $arrContents = array();
	
	
	/**
	 * Support multiple tags
	 * @var string
	 */
	protected $strTags = 'false';
	
	
	/**
	 * Minimum characters to start autocomplete
	 * @var string
	 */
	protected $strCharacters = '1';
	
	
	/**
	 * Options (tokens)
	 * @var array
	 */
	protected $arrOptions = array();


	/**
	 * Add specific attributes
	 * @param string
	 * @param mixed
	 */
	public function __set($strKey, $varValue)
	{
		switch ($strKey)
		{
			case 'value':
				$this->varValue = deserialize($varValue);
				break;

			case 'maxlength':
				$this->arrAttributes[$strKey] = ($varValue > 0) ? $varValue : '';
				break;

			case 'mandatory':
				$this->arrConfiguration['mandatory'] = $varValue ? true : false;
				break;
				
			case 'tags':
				$this->strTags = $varValue ? 'true' : 'false';
				break;
				
			case 'characters':
				$this->strCharacters = ($varValue > 0) ? $varValue : '1';
				break;
				
			case 'options':
				$varValue = deserialize($varValue);
				
				foreach ($varValue as $arrToken)
				{
					// "multiple" fields
					if (is_array(deserialize($arrToken['label'])))
					{
						$arrLabels = deserialize($arrToken['label']);
						foreach( $arrLabels as $strLabel )
						{
							$this->arrOptions = array_merge($this->arrOptions, trimsplit(',', $strLabel));					
						}
					}
					else
					{
						$this->arrOptions = array_merge($this->arrOptions, trimsplit(',', $arrToken['label']));
					}
				}
				$arrOptions = array_unique($this->arrOptions);
				natcasesort($arrOptions);
				$this->arrOptions = $arrOptions;
				break;

			default:
				parent::__set($strKey, $varValue);
				break;
		}
	}


	/**
	 * Trim values
	 * @param mixed
	 * @return mixed
	 */
	protected function validator($varInput)
	{
		if (is_array($varInput))
		{
			return parent::validator($varInput);
		}

		return parent::validator(trim($varInput));
	}


	/**
	 * Generate the widget and return it as string
	 * @return string
	 */
	public function generate()
	{
		// Inject autocompleter javascript
		$GLOBALS['TL_JAVASCRIPT']['autocompleter'] = 'system/modules/autocompletetextfield/assets/Autocompleter.min.js';
		$GLOBALS['TL_CSS']['autocompleter'] = 'system/modules/autocompletetextfield/assets/Autocompleter.min.css';
		
		// Generate javascript code
		$strTokens = sprintf("var ctrl_%s_tokens = ['%s'];", 
								$this->strId, 
								implode("', '", $this->arrOptions));
		
		$strAutocompleter = "<script type=\"text/javascript\">
		document.addEvent('domready', function() {
		
		%s
		
		new Autocompleter.Local('ctrl_%s', ctrl_%s_tokens, {
			'minLength': %s, // We need at least 1 character
			'selectMode': 'type-ahead', // Instant completion
			'multiple': %s // Tag support, by default comma separated
		});});
		</script>";
		
		if (!$this->multiple)
		{
			return sprintf('<input type="text" name="%s" id="ctrl_%s" class="tl_text%s" value="%s"%s onfocus="Backend.getScrollOffset();" />%s',
							$this->strName,
							$this->strId,
							(strlen($this->strClass) ? ' ' . $this->strClass : ''),
							specialchars($this->varValue),
							$this->getAttributes(),
							sprintf($strAutocompleter,
									$strTokens,
									$this->strId,
									$this->strId,
									$this->strCharacters,
									$this->strTags));
		}

		// Return if field size is missing
		if (!$this->size)
		{
			return '';
		}

		if (!is_array($this->varValue))
		{
			$this->varValue = array($this->varValue);
		}

		$arrFields = array();

		for ($i=0; $i<$this->size; $i++)
		{
			$arrFields[] = sprintf('<input type="text" name="%s[]" id="ctrl_%s" class="tl_text_%s" value="%s"%s onfocus="Backend.getScrollOffset();" />%s',
									$this->strName,
									$this->strId.'_'.$i,
									$this->size,
									specialchars($this->varValue[$i]),
									$this->getAttributes(),
									sprintf($strAutocompleter,
									$strTokens,
									$this->strId.'_'.$i,
									$this->strId,
									$this->strCharacters,
									$this->strTags));
									
			$strTokens = '';
		}

		return sprintf('<div id="ctrl_%s"%s>%s</div>',
						$this->strId,
						(strlen($this->strClass) ? ' class="' . $this->strClass . '"' : ''),
						implode(' ', $arrFields));
	}
}

?>