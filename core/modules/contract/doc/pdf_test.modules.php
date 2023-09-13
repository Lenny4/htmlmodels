<?php

dol_include_once('/htmlmodels/class/pdf_htmlmodels.class.php');


/**
 *	Class to build contracts documents with model HTMLModels
 */
class pdf_test extends pdf_htmlmodels
{
	/**
	 *	Constructor
	 *
	 *  @param		DoliDB		$db      Database handler
	 */
	function __construct($db)
	{
		global $langs;

		parent::__construct($db);

		$this->name = $langs->trans("test");
		$this->description = $langs->trans("test");

		$this->html_dir = '/htmlmodels/contract/test';

	}
}