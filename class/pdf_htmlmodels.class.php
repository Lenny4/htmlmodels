<?php
/* Copyright (C) 2003		Rodolphe Quiedeville		<rodolphe@quiedeville.org>
 * Copyright (C) 2004-2010	Laurent Destailleur			<eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012	Regis Houssin				<regis.houssin@capnetworks.com>
 * Copyright (C) 2008		Raphael Bertrand (Resultic)	<raphael.bertrand@resultic.fr>
 * Copyright (C) 2011		Fabrice CHERRIER
 * Copyright (C) 2013       Philippe Grand	            <philippe.grand@atoo-net.com>
 * Copyright (C) 2015       Marcos García               <marcosgdf@gmail.com>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 * or see http://www.gnu.org/
 */

/**
 *	\file       htdocs/core/modules/contract/doc/pdf_htmlmodels.modules.php
 *	\ingroup    ficheinter
 *	\brief      HTMLModels contracts template class file
 */
require_once DOL_DOCUMENT_ROOT.'/core/modules/contract/modules_contract.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/pdf.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';


/**
 *	Class to build contracts documents with model HTMLModels
 */
class pdf_htmlmodels extends ModelePDFContract
{
	var $db;
	var $name;
	var $description;
	var $type;

	var $phpmin = array(4,3,0); // Minimum version of PHP required by module
	var $version = 'dolibarr';

	var $page_largeur;
	var $page_hauteur;
	var $format;
	var $marge_gauche;
	var	$marge_droite;
	var	$marge_haute;
	var	$marge_basse;

	/**
	 * Issuer
	 * @var Societe
	 */
	public $emetteur;

	/**
	 * Recipient
	 * @var Societe
	 */
	public $recipient;

	/**
	 *	Constructor
	 *
	 *  @param		DoliDB		$db      Database handler
	 */
	function __construct($db)
	{
		global $conf,$langs,$mysoc;

		$this->db = $db;
		$this->name = $langs->trans("htmlmodels");
		$this->description = $langs->trans("htmlmodels");

		// Dimension page pour format A4
		$this->type = 'pdf';
		$formatarray=pdf_getFormat();
		$this->page_largeur = $formatarray['width'];
		$this->page_hauteur = $formatarray['height'];
		$this->format = array($this->page_largeur,$this->page_hauteur);
		$this->marge_gauche=isset($conf->global->MAIN_PDF_MARGIN_LEFT)?$conf->global->MAIN_PDF_MARGIN_LEFT:10;
		$this->marge_droite=isset($conf->global->MAIN_PDF_MARGIN_RIGHT)?$conf->global->MAIN_PDF_MARGIN_RIGHT:10;
		$this->marge_haute =isset($conf->global->MAIN_PDF_MARGIN_TOP)?$conf->global->MAIN_PDF_MARGIN_TOP:10;
		$this->marge_basse =isset($conf->global->MAIN_PDF_MARGIN_BOTTOM)?$conf->global->MAIN_PDF_MARGIN_BOTTOM:10;

		$this->option_logo = 1;                    // Affiche logo
		$this->option_tva = 0;                     // Gere option tva FACTURE_TVAOPTION
		$this->option_modereg = 0;                 // Affiche mode reglement
		$this->option_condreg = 0;                 // Affiche conditions reglement
		$this->option_codeproduitservice = 0;      // Affiche code produit-service
		$this->option_multilang = 0;               // Dispo en plusieurs langues
		$this->option_draft_watermark = 1;		   //Support add of a watermark on drafts

		// Get source company
		$this->emetteur=$mysoc;
		if (empty($this->emetteur->country_code)) $this->emetteur->country_code=substr($langs->defaultlang,-2);    // By default, if not defined

	}

	/**
     *  Function to build pdf onto disk
     *
     *  @param		CommonObject	$object				Id of object to generate
     *  @param		object			$outputlangs		Lang output object
     *  @param		string			$srctemplatepath	Full path of source filename for generator using a template file
     *  @param		int				$hidedetails		Do not show line details
     *  @param		int				$hidedesc			Do not show desc
     *  @param		int				$hideref			Do not show ref
     *  @return		int									1=OK, 0=KO
	 */
	function write_file($object,$outputlangs,$srctemplatepath='',$hidedetails=0,$hidedesc=0,$hideref=0)
	{
		global $user,$langs,$conf,$hookmanager,$mysoc;

		if (! is_object($outputlangs)) $outputlangs=$langs;
		// For backward compatibility with FPDF, force output charset to ISO, because FPDF expect text to be encoded in ISO
		if (! empty($conf->global->MAIN_USE_FPDF)) $outputlangs->charset_output='ISO-8859-1';

		$outputlangs->load("main");
		$outputlangs->load("dict");
		$outputlangs->load("companies");
		$outputlangs->load("contracts");

		if ($conf->contrat->dir_output)
		{
            $object->fetch_thirdparty();

			// Definition of $dir and $file
			if ($object->specimen)
			{
				$dir = $conf->contrat->dir_output;
				$file = $dir . "/SPECIMEN.pdf";
			}
			else
			{
				$objectref = dol_sanitizeFileName($object->ref);
				$dir = $conf->contrat->dir_output . "/" . $objectref;
				$file = $dir . "/" . $objectref . ".pdf";
			}

			if (! file_exists($dir))
			{
				if (dol_mkdir($dir) < 0)
				{
					$this->error=$outputlangs->trans("ErrorCanNotCreateDir",$dir);
					return 0;
				}
			}

			if (file_exists($dir))
			{
				// Add pdfgeneration hook
				if (! is_object($hookmanager))
				{
					include_once DOL_DOCUMENT_ROOT.'/core/class/hookmanager.class.php';
					$hookmanager=new HookManager($this->db);
				}
				$hookmanager->initHooks(array('pdfgeneration'));
				$parameters=array('file'=>$file,'object'=>$object,'outputlangs'=>$outputlangs);
				global $action;
				$reshook=$hookmanager->executeHooks('beforePDFCreation',$parameters,$object,$action);    // Note that $action and $object may have been modified by some hooks


				dol_include_once('/htmlmodels/includes/html2pdf/vendor/autoload.php');

				// Logo
				$logo=$conf->mycompany->dir_output.'/logos/'.$this->emetteur->logo;
				if ($this->emetteur->logo)
				{
					if (is_readable($logo))
					{
						$height=(empty($conf->global->MAIN_DOCUMENTS_LOGO_HEIGHT)?22:$conf->global->MAIN_DOCUMENTS_LOGO_HEIGHT);
						$maxwidth=130;
						include_once DOL_DOCUMENT_ROOT.'/core/lib/images.lib.php';
						$tmp=dol_getImageSize($logo);
						if ($tmp['height'])
						{
							$width=round($height*$tmp['width']/$tmp['height']);
							if ($width > $maxwidth) $height=$height*$maxwidth/$width;
						}

						$logohtml = '<img src="'.$conf->mycompany->dir_output.'/logos/'.$this->emetteur->logo.'" style="float: right; width:'.$width.'mm; height:'.$height.'mm;" />';

						$logoheight = $height."mm";
					}
					else
					{
						$logohtml = $outputlangs->transnoentities("ErrorLogoFileNotFound",$logo);
					}
				}
				else
				{
					$logohtml=$this->emetteur->name;
				}

				// infos bancaires societe
				$this->get_bank_infos($this->emetteur, $outputlangs);

				// infos bancaires client
				$this->get_bank_infos($object->thirdparty, $outputlangs);

				$html2pdf = new HTML2PDF('P','A4','fr');

				$style = '<style>';
				$style .= 'table { border-collapse: collapse;}';
				$style .= 'td { border: 1px solid; padding: 5px;}';
				$style .= 'td.no_border { border: none;}';
				$style .= 'td.border_right { border-left: none;border-top: none;border-bottom: none;}';
				$style .= '</style>';
				$html2pdf->WriteHTML($style);


				// chargement des pages (en deux parties car readdir ne renvoie pas dans l'ordre)
				$MyDirectory = opendir(DOL_DATA_ROOT.$this->html_dir) or die('Erreur : '.$this->html_dir.' '.$langs->trans("NotFound"));
				$i=0;
				while($Entry = @readdir($MyDirectory)) {
					//print_r('début <br>');
					if (substr($Entry,0,4) == 'page')
					{
						//print_r('i entrée ='.$i.'<br>');
						$i++;
						$posext = strpos($Entry, '.html');
						$p = substr($Entry, 4, $posext - 4);
						$pages[$p] = file_get_contents(DOL_DATA_ROOT.$this->html_dir.'/'.$Entry);
						//print_r('i sortie ='.$i.'<br>');
					}
					//$np++;
					//print_r('np = '.$np.'; fin <br>');
				}
				//die;
				closedir($MyDirectory);

				ksort($pages);
				$np=0; 
				foreach($pages as $pagehtml)
				{
					$page = '<page '.($i>1?' backbottom="10mm"':'').'>';
					$page .= '<page_header>'.$logohtml.'</page_header>';
					$np++;
					if ($np == 1) {
						if ($i > 1) $page .= '<page_footer>
						<div style="width:100%; text-align:right; font-size: 7px">page [[page_cu]]/[[page_nb]]</div></page_footer>';
					}
					else {
						if ($i > 1) $page .= '<page_footer>
						<div style="width:100%; text-align:center; font-size: 7px">
							__NAME__ <br> 
							__TYPE__ - Capital de __CAPITAL__ - SIRET : __SIRET__ <br> 
							NAF-APE : __NAF__ - RCS/RM : __RCS__ - Numéro TVA : __TVA__ - Numéro EORI : __EORI__
						</div>
						<div style="width:100%; text-align:right; font-size: 7px">page [[page_cu]]/[[page_nb]]</div></page_footer>';
					}
					$page .= $pagehtml;
					$page .= '</page>';
					$page = $this->write_page($page, $object, $outputlangs);
					$html2pdf->WriteHTML($page);
				}
				$html2pdf->Output($file,'F');
				//die;

				// Add pdfgeneration hook
				if (! is_object($hookmanager))
				{
					include_once DOL_DOCUMENT_ROOT.'/core/class/hookmanager.class.php';
					$hookmanager=new HookManager($this->db);
				}
				$hookmanager->initHooks(array('pdfgeneration'));
				$parameters=array('file'=>$file,'object'=>$object,'outputlangs'=>$outputlangs);
				global $action;
				$reshook=$hookmanager->executeHooks('afterPDFCreation',$parameters,$this,$action);    // Note that $action and $object may have been modified by some hooks

				if (! empty($conf->global->MAIN_UMASK))
				@chmod($file, octdec($conf->global->MAIN_UMASK));

				return 1;
			}
			else
			{
				$this->error=$langs->trans("ErrorCanNotCreateDir",$dir);
				return 0;
			}
		}
		else
		{
			$this->error=$langs->trans("ErrorConstantNotDefined","CONTRACT_OUTPUTDIR");
			return 0;
		}
		$this->error=$langs->trans("ErrorUnknown");
		return 0;   // Erreur par defaut
	}


	function write_page($page, $object, $outputlangs)
	{
		$pos = strpos($page, '<tr class="line"');
		if ($pos !== FALSE)
		{
			$fin = strpos($page, '</tr>', $pos) + 5;
			$html = substr($page, $pos, $fin - $pos); //print('pos:'.$pos.' fin:'.$fin.' html: '.$html.'<br>'); die;
			$lines = '';
			foreach($object->lines as $i => $line)
			{
				$lines .= $this->make_line_substitutions($html, $i, $line, $outputlangs);
			}
			//die($lines);
			$page = substr_replace($page, $lines, $pos, $fin - $pos);
		
		}

		return $this->make_substitutions($page, $object, $outputlangs);
	}

	function make_line_substitutions($html, $i, $line, $outputlangs)
	{
		$substitutions_array = array('SERVICE', 'QUANTITY', 'PRICE_HT', 'TOTAL_HT','DESCRIPTION');
		foreach($substitutions_array as $chaine)
		{
			if (strpos($page, '__'.$chaine.'__') >= 0)
			{
				$value = $this->get_line_substitution($chaine, $line, $outputlangs);
				$html = str_replace('__'.$chaine.'__', $value, $html);
			}
		}
		return $html;
	}

	function make_substitutions($page, $object, $outputlangs)
	{
		$substitutions_array = array('MODEL_NAME','SOCIETE', 'SOCIETE_NOM','SOCIETE_BIC','SOCIETE_IBAN', 'SOCIETE_ICS', 'CLIENT', 'CLIENT_NOM', 'CLIENT_ADRESSE', 'CLIENT_BIC', 'CLIENT_IBAN','DATE_CONTRAT', 'REF_CONTRAT', 'CAPITAL', 'ADRESSE', 'ADRESSE_FULL', 'VILLE','CLIENT_VILLE', 'SIRET', 'TEL', 'MAIL','WEB','TOTAL_HT','TOTAL_TVA','TOTAL_TTC','NOTE_PUBLIC','CONTACT_INTERNE','CONTACT_CLIENT','CONDITIONS_REGLEMENT','PREMIERE_ECHEANCE','SOCIETE_NOTE','SIREN','PDG','TYPE','SIREN_CLIENT','SIRET_CLIENT','TYPE_CLIENT','RUM_CLIENT','CAPITAL_CLIENT','NAME',
		'NAF','RCS','TVA','EORI'); /*Modification GIDM*/
		foreach($substitutions_array as $chaine)
		{
			if (strpos($page, '__'.$chaine.'__') >= 0)
			{
				$value = $this->get_substitution($chaine, $object, $outputlangs);
				$page = str_replace('__'.$chaine.'__', $value, $page);
			}
		}
		return $page;
	}

	function get_line_substitution($chaine, $line, $outputlangs)
	{
		global $conf;

		switch($chaine)
		{
			case 'SERVICE':
				$txtpredefinedservice='';
				$txtpredefinedservice = $line->product_ref;
				if ($objectligne->product_label)
				{
					$txtpredefinedservice .= ' - ';
					$txtpredefinedservice .= $line->product_label;
				}
				if (empty($txtpredefinedservice)) $txtpredefinedservice = $line->description;
				return $txtpredefinedservice;

			case 'DESCRIPTION':
				$txtpredefinedservice='';
				$txtpredefinedservice = $line->product_ref;
				if ($objectligne->product_label)
				{
					$txtpredefinedservice .= ' - ';
					$txtpredefinedservice .= $line->product_label;
				}
				return (empty($txtpredefinedservice)?'':$line->description);

			case 'QUANTITY':
				return $line->qty;

			case 'PRICE_HT':
				return price($line->price_ht, 0, $outputlangs, 1, -1, -1, $conf->currency);

			case 'TOTAL_HT':
				return price($line->total_ht, 0, $outputlangs, 1, -1, -1, $conf->currency);
		}

	}

	function get_substitution($chaine, $object, $outputlangs)
	{
		global $conf;

		switch($chaine)
		{
			case 'PDG':
				return $conf->global->MAIN_INFO_SOCIETE_MANAGERS;

			case 'TYPE':
				return getFormeJuridiqueLabel($conf->global->MAIN_INFO_SOCIETE_FORME_JURIDIQUE);

			case 'MODEL_NAME':
				return $this->name;

			case 'SOCIETE_ICS':
				return $conf->global->PRELEVEMENT_ICS;

			case 'SOCIETE':
			case 'SOCIETE_NOM':
				return strtoupper($this->emetteur->name);

			case 'CLIENT':
			case 'CLIENT_NOM':
				return strtoupper(pdfBuildThirdpartyName($object->thirdparty, $outputlangs));

			case 'DATE_CONTRAT':
			case 'DATE_CONTRACT':
				return dol_print_date($object->date_contrat,"day",false,$outputlangs,true);

			case 'REF_CONTRAT':
			case 'REF_CONTRACT':
				return $object->ref;

			case 'SIREN':
				return $this->emetteur->idprof1;

			case 'SIRET':
				return $this->emetteur->idprof2;

			case 'CAPITAL':
				$tmpamounttoshow = price2num($this->emetteur->capital); // This field is a free string
				if (is_numeric($tmpamounttoshow) && $tmpamounttoshow > 0) return price($tmpamounttoshow, 0, $outputlangs, 1, -1, -1, $conf->currency);
				break;

			case 'SIREN_CLIENT':
				return $object->thirdparty->idprof1;

			case 'SIRET_CLIENT':
				return $object->thirdparty->idprof2;

			case 'CAPITAL_CLIENT':
				$tmpamounttoshow = price2num($object->thirdparty->capital); // This field is a free string
				if (is_numeric($tmpamounttoshow) && $tmpamounttoshow > 0) return price($tmpamounttoshow, 0, $outputlangs, 1, -1, -1, $conf->currency);
				break;

			case 'TYPE_CLIENT':
				return $object->thirdparty->forme_juridique;

			case 'RUM_CLIENT':
				return $object->thirdparty->rum;

			case 'SOCIETE_BIC':
				return $this->emetteur->bic;

			case 'SOCIETE_IBAN':
				return $this->emetteur->iban;

			case 'CLIENT_BIC':
				return $object->thirdparty->bic;

			case 'CLIENT_IBAN':
				return $object->thirdparty->iban;

			case 'ADRESSE_FULL':
				return dol_nl2br(pdf_build_address($outputlangs, $this->emetteur, $object->thirdparty));

			case 'ADRESSE':
				return dol_format_address($this->emetteur, false, "<br/>", $outputlangs);

			case 'CLIENT_ADRESSE':
				return dol_format_address($object->thirdparty, false, "<br/>", $outputlangs);

			case 'CLIENT_VILLE':
				return $object->thirdparty->town;

			case 'VILLE':
				return $this->emetteur->town;

			case 'TEL':
				return $this->emetteur->phone;

			case 'MAIL':
				return $this->emetteur->email;

			case 'WEB':
				return $this->emetteur->url;

			case 'TOTAL_HT':
				return price($object->total_ht, 0, $outputlangs, 1, -1, -1, $conf->currency);

			case 'TOTAL_TVA':
				return price($object->total_tva, 0, $outputlangs, 1, -1, -1, $conf->currency);

			case 'TOTAL_TTC':
				return price($object->total_ttc, 0, $outputlangs, 1, -1, -1, $conf->currency);

			case 'NOTE_PUBLIC':
				return $object->note_public;

			case 'CONTACT_INTERNE':
				$arrayidcontact=$object->getIdContact('internal','SALESREPSIGN');
				if (count($arrayidcontact) > 0)
				{
					$object->fetch_user($arrayidcontact[0]);
					return $object->user->getFullName($outputlangs);
				}
				break;

			case 'CONTACT_CLIENT':
				$arrayidcontact=$object->getIdContact('external','SALESREPSIGN');
				if (count($arrayidcontact) > 0)
				{
					$object->fetch_contact($arrayidcontact[0]);
					return $object->contact->getFullName($outputlangs);
				}
				break;

			case 'CONDITIONS_REGLEMENT':
				$sql = "SELECT rowid, code, libelle as label";
				$sql.= " FROM ".MAIN_DB_PREFIX.'c_payment_term';
				$sql.= " WHERE rowid=".$object->thirdparty->cond_reglement_id;

				$resql = $this->db->query($sql);
				if ($resql)
				{
					$obj = $this->db->fetch_object($resql);

					// Si traduction existe, on l'utilise, sinon on prend le libelle par defaut
					$label=($outputlangs->trans("PaymentConditionShort".$obj->code)!=("PaymentConditionShort".$obj->code)?$outputlangs->trans("PaymentConditionShort".$obj->code):($obj->label!='-'?$obj->label:''));

					return $label;
				}
				break;

			case 'PREMIERE_ECHEANCE':
				$object->fetch_optionals($object->id);
				return date('d/m/Y',$object->array_options['options_1echeance']);
				break;

			case 'SOCIETE_NOTE':
				return (! empty($conf->global->MAIN_INFO_SOCIETE_NOTE) ? nl2br($conf->global->MAIN_INFO_SOCIETE_NOTE) : '');

			case 'NAME': /*modification GIDM*/
				return $this->emetteur->name;
			case 'NAF': /*modification GIDM*/
				return $this->emetteur->idprof3;
			case 'RCS': /*modification GIDM*/
				return $this->emetteur->idprof4;
			case 'TVA': /*modification GIDM*/
				return $this->emetteur->tva_intra;
			case 'EORI': /*modification GIDM*/
				return $this->emetteur->idprof5;
		
		}

	}

	function get_bank_infos(&$object, $outputlangs)
	{
		global $conf;

		if ($object == $this->emetteur)
		{
			require_once DOL_DOCUMENT_ROOT.'/compta/bank/class/account.class.php';

			$bankid=(empty($object->fk_account)?$conf->global->FACTURE_RIB_NUMBER:$object->fk_account);
			if (! empty($object->fk_bank)) $bankid=$object->fk_bank;   // For backward compatibility when object->fk_account is forced with object->fk_bank
			$account = new Account($this->db);
			$account->fetch($bankid);
		}
		else
		{
			require_once DOL_DOCUMENT_ROOT . '/societe/class/companybankaccount.class.php';
			$account = new CompanyBankAccount($this->db);
			$account->fetch(0,$object->id);
			if ($conf->prelevement->enabled)
			{
				require_once DOL_DOCUMENT_ROOT.'/compta/prelevement/class/bonprelevement.class.php';
				$prelevement = new BonPrelevement($this->db);
				$account->rum = $prelevement->buildRumNumber($object->code_client, $account->datec, $account->id);
			}
		}

		if (! empty($account->iban))
		{
			//Remove whitespaces to ensure we are dealing with the format we expect
			$ibanDisplay_temp = str_replace(' ', '', $outputlangs->convToOutputCharset($account->iban));
			$ibanDisplay = "";

			$nbIbanDisplay_temp = dol_strlen($ibanDisplay_temp);
			for ($i = 0; $i < $nbIbanDisplay_temp; $i++)
			{
				$ibanDisplay .= $ibanDisplay_temp[$i];
				if($i%4 == 3 && $i > 0)	$ibanDisplay .= " ";
			}

			// Use correct name of bank id according to country
			require_once DOL_DOCUMENT_ROOT.'/core/class/html.formbank.class.php';
			$ibankey = FormBank::getIBANLabel($account);
			$object->iban = $outputlangs->transnoentities($ibankey).': ' . $ibanDisplay;
		}

		if (! empty($account->bic))
		{
			// Use correct name of bank id according to country
			$bickey="BICNumber";
			if ($account->getCountryCode() == 'IN') $bickey="SWIFT";
			$object->bic = $outputlangs->transnoentities($bickey).': ' . $outputlangs->convToOutputCharset($account->bic);
		}

		if (!empty($account->rum))
		{
			$object->rum = $account->rum;
		}
	}

}

