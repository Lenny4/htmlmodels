<?php

dol_include_once('/htmlmodels/class/pdf_htmlmodels.class.php');


/**
 *	Class to build contracts documents with model HTMLModels
 */
class pdf___model__ extends pdf_htmlmodels
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

		$this->name = $langs->trans("__model__");
		$this->description = $langs->trans("__model__");

		$this->html_dir = '/htmlmodels/contract/__model__';

	}
}