<?php
/* Copyright (C) 2017	Christophe Battarel	<christophe@battarel.com>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**     \defgroup   htmlmodels     Module htmlmodels
 *      \brief      Module to manage htmlmodels
 *      \file       htdocs/includes/modules/modHTMLModels.class.php
 *      \ingroup    return
 *      \brief      Description and activation file for module htmlmodels
 */
include_once DOL_DOCUMENT_ROOT .'/core/modules/DolibarrModules.class.php';


/**
 * 	Class to describe module Return
 */
class modHtmlModels extends DolibarrModules
{
	/**
	 * 	Constructor
	 *
	 * 	@param	DoliDB	$db		Database handler
	 */
	function __construct($db)
	{
		global $conf;

		$this->db = $db;

		// Id for module (must be unique).
		// Use here a free id (See in Home -> System information -> Dolibarr for list of used modules id).
		$this->numero = 140042;
		// Key text used to identify module (for permissions, menus, etc...)
		$this->rights_class = 'htmlmodels';

		// Family can be 'crm','financial','hr','projects','products','ecm','technic','other'
		// It is used to group modules in module setup page
		$this->family = "other";
		// Module label (no space allowed), used if translation string 'ModuleXXXName' not found (where XXX is value of numeric property 'numero' of module)
		$this->name = preg_replace('/^mod/i','',get_class($this));
		// Module description, used if translation string 'ModuleXXXDesc' not found (where XXX is value of numeric property 'numero' of module)
		$this->description = "Modèles HTML";
		// Possible values for version are: 'development', 'experimental', 'dolibarr' or version
		$this->version = '1.0';
		// Key used in llx_const table to save module status enabled/disabled (where MYMODULE is value of property name of module in uppercase)
		$this->const_name = 'MAIN_MODULE_'.strtoupper($this->name);
		// Where to store the module in setup page (0=common,1=interface,2=other)
		$this->special = 2;
		// Name of png file (without png) used for this module.
		// Png file must be in theme/yourtheme/img directory under name object_pictovalue.png.
		$this->picto='htmlmodels@htmlmodels';

		// Data directories to create when module is enabled.
		$this->dirs = array("/htmlmodels/contract");

		// Config pages. Put here list of php page names stored in admin directory used to setup module.
		$this->config_page_url = array("admin.php@htmlmodels");

		// Dependencies
		$this->depends = array();		// List of modules id that must be enabled if this module is enabled
		$this->requiredby = array();				// List of modules id to disable if this one is disabled
		$this->phpmin = array(5,1);					// Minimum version of PHP required by module
		$this->need_dolibarr_version = array(3,7);	// Minimum version of Dolibarr required by module
		$this->langfiles = array("htmlmodels@htmlmodels");

		// Constants
		$this->const = array();

		// New pages on tabs
		$this->tabs = array();

		// hooks
		$this->module_parts = array(
			'models' => 1  // Set here all hooks context managed by module
		);

		// Boxes
		$this->boxes = array();			// List of boxes
		$r=0;

		// Permissions
		$this->rights = array();		// Permission array used by this module
		$r=0;


		// Main menu entries
		$this->menu = array();			// List of menus to add
	}

	/**
     *	Function called when module is enabled.
     *	The init function add constants, boxes, permissions and menus (defined in constructor) into Dolibarr database.
     *	It also creates data directories.
     *
     *	@return     int             1 if OK, 0 if KO
     */
	function init()
  	{
		global $conf, $db, $langs;

		// specif GIDM : retrieve old pages
		$nbpages = $conf->global->GIDM_CONTRACT_NBPAGES;
		if ( ! empty($nbpages))
		{

			require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
			$langs->load('htmlmodels@htmlmodels');

			// création répertoire des pages
			$targetdir = DOL_DATA_ROOT.'/htmlmodels/contract/assistance';
			dol_mkdir($targetdir);

			for ($i = 1; $i <= $nbpages; $i++)
			{
				file_put_contents($targetdir.'/page'.$i.'.html', $conf->global->{'GIDM_CONTRACT_'.$i});
			}

		}

    	$this->load_tables();

        $sql = array();
        return $this->_init($sql);
  	}

	/**
	 *	Function called when module is disabled.
	 *	Remove from database constants, boxes and permissions from Dolibarr database.
	 *	Data directories are not deleted.
	 *
	 *	@return     int             1 if OK, 0 if KO
 	 */
	function remove()
	{
    	$sql = array();


    	return $this->_remove($sql);
  	}


	/**
	 * 	Create tables and keys required by module
	 * 	Files mymodule.sql and mymodule.key.sql with create table and create keys
	 * 	commands must be stored in directory /mymodule/sql/
	 * 	This function is called by this->init.
	 *
	 * 	@return		int		<=0 if KO, >0 if OK
	 */
  	function load_tables()
	{
		global $conf, $db, $langs;

		return;
	}
}

?>
