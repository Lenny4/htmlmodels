<?php
/* Copyright (C) 2017	Christophe Battarel	<christophe.battarel@altairis.fr>
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

/**
 *      \file       /htdocs/admin/admin.php
 *		\ingroup    htmlmodels
 *		\brief      Page to setup htmlmodels module
 */

$res=@include("../../main.inc.php");					// For root directory
if (! $res) $res=@include("../../../main.inc.php");		// For "custom" directory

require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';

$langs->load("admin");
$langs->load("htmlmodels@htmlmodels");

/*
 * View
 */

llxHeader('',$langs->trans("HTMLModelsSetup"));


$linkback='<a href="'.DOL_URL_ROOT.'/admin/modules.php">'.$langs->trans("BackToModuleList").'</a>';
print_fiche_titre($langs->trans("HTMLModelsSetup"),$linkback,'setup');

if( ! $user->admin) accessforbidden();

$page = GETPOST('page');
if (empty($page)) $page = 1;

$modele = GETPOST('modele');

$action = GETPOST('action');

/*
 * Action
*/

if ($action == 'delete_page')
{
	// suppression page html
	$filename = DOL_DATA_ROOT.'/htmlmodels/contract/'.$modele.'/page'.$page.'.html';
	if (! dol_delete_file($filename,1))
	{
		print '<br/>'.$langs->trans("ErrorDeletingFile",$filename);
	}
	else $page = 1;

}

if ($action == 'delete_model')
{
	// suppression pages
	$rep = DOL_DATA_ROOT.'/htmlmodels/contract/'.$modele;
	if (! dol_delete_dir_recursive($rep))
	{
		print '<br/>'.$langs->trans("ErrorDeletingDir",$rep);
	}
	else
	{
		// suppression pdf
		$filename = dol_buildpath('/htmlmodels/core/modules/contract/doc/pdf_'.$modele.'.modules.php');
		if (! dol_delete_file($filename,1))
		{
			print '<br/>'.$langs->trans("ErrorDeletingFile",$filename);
		}

		delDocumentModel($modele, 'contract');

		$modele = '';
		$page = '';
	}


}


if ($action == "valid")
{
	$html_dir = '/htmlmodels/contract/'.$modele;
	$content = $_POST['contract_page_' . $page];
	$filename = DOL_DATA_ROOT.$html_dir.'/page'.$page.'.html';
    $result = file_put_contents($filename, $content);
	if ($result === FALSE)
	{
		print '<br/>'.$langs->trans('CannotCreatePdfModel',$filename);
		print '<br/><br/><textarea cols="80" rows="30" readonly="readonly">'.$content.'</textarea>';
	}
}

if ($action == "set_model")
{
	if ($modele == 'new')
	{
		print '<br/><form name="newmodel" method="post">';
		print '<input type="hidden" name="action" value="create_model" />';
		print $langs->trans('ModelName').' : <input type="text" name="newmodel" value="" />';
		print '&emsp;<input type="submit" /></form>';
		exit;
	}
}

if ($action == "create_model")
{
	$modele = GETPOST('newmodel');
	// création répertoire des pages
	dol_mkdir(DOL_DATA_ROOT.'/htmlmodels/contract/'.$modele);

	// création du modèle pdf
	$content = file_get_contents(dol_buildpath('/htmlmodels/core/modules/pdf_model.modules.php'));
	$content = str_replace('__model__',$modele,$content);
	$filename = dol_buildpath('/htmlmodels/core/modules/contract/doc').'/pdf_'.$modele.'.modules.php';
	$result = file_put_contents($filename, $content);
	if ($result === FALSE)
	{
		print '<br/>'.$langs->trans('CannotCreatePdfModel',$filename);
		print '<br/><br/><textarea cols="80" rows="30" readonly="readonly">'.$content.'</textarea>';
	}
}

/*
 * View
 */


// chargement liste de modeles
$html_dir = '/htmlmodels/contract/';
$MyDirectory = opendir(DOL_DATA_ROOT.$html_dir) or die('Erreur : '.$html_dir.' '.$langs->trans("NotFound"));

$modeles = array();
while($Entry = @readdir($MyDirectory)) {
	if (substr($Entry,0,1) != '.')
	{
		$modeles[] = $Entry;
	}
}
closedir($MyDirectory);

asort($modeles);

print "<br>";

print '<div style="width:210mm;">';

// choix du modèle
print '<div style="float: left;">';
print '<form method="post" id="set_model">';
print '<input type="hidden" name="action" value="set_model" />';
print '<select name="modele" onchange="$(\'form#set_model\').submit();">';
print '<option value="">'.$langs->trans("SelectModel").'</option>';
foreach($modeles as $mod)
{
	print '<option value="'.$mod.'"';
	if ($mod == $modele) print ' selected="selected"';
	print '>'.$mod.'</option>';
}
print '<option value="new">'.$langs->trans("NewModel").'</option>';
print '</select>';
print '</form>';
print '</div>';

if (!empty($modele))
{
	// suppr modele
	print '<div style="float: left;">';
	print '<form method="post">';
	print '<input type="hidden" name="action" value="delete_model" />';
	print '<input type="hidden" name="modele" value="'.$modele.'" />';
	print '<input type="hidden" name="page" value="'.$page.'" />';
	print '&emsp;<input type="submit" class="button" value="'.$langs->trans('DeleteModel').'" />';
	print '</form>';
	print '</div>';

	// chargement des pages
	$html_dir = '/htmlmodels/contract/'.$modele;
	$MyDirectory = opendir(DOL_DATA_ROOT.$html_dir) or die('Erreur : '.$html_dir.' '.$langs->trans("NotFound"));
	$i=0; $p = 0;
	$pages = array();
	while($Entry = @readdir($MyDirectory)) {
		if (substr($Entry,0,4) == 'page')
		{
			$i++;
			$posext = strpos($Entry, '.html');
			$p = substr($Entry, 4, $posext - 4);
			$pages[$p] = file_get_contents(DOL_DATA_ROOT.$html_dir.'/'.$Entry);
		}
	}
	closedir($MyDirectory);

	ksort($pages);

	// nouvelle page
	if ($action == 'set_page' && $page == 'new')
	{
		end($pages);
		$page = key($pages) + 1;
		$pages[$page] = '';
	}

	// choix de la page
	print '<div style="float: left;">';
	print '<form method="post" id="set_page">';
	print '<input type="hidden" name="action" value="set_page" />';
	print '<input type="hidden" name="modele" value="'.$modele.'" />';
	print '&emsp;<select name="page" onchange="$(\'form#set_page\').submit();">';
	foreach($pages as $i => $html)
	{
		print '<option value="'.$i.'"';
		if ($i == $page) print ' selected="selected"';
		print '>'.$langs->trans("PageNb",$i).'</option>';
	}
	print '<option value="new">'.$langs->trans("NewPage").'</option>';
	print '</select>';
	print '</form>';
	print '</div>';


	// suppr de la page
	print '<div style="float: left;">';
	print '<form method="post">';
	print '<input type="hidden" name="action" value="delete_page" />';
	print '<input type="hidden" name="modele" value="'.$modele.'" />';
	print '<input type="hidden" name="page" value="'.$page.'" />';
	print '&emsp;<input type="submit" class="button" value="'.$langs->trans('DeletePage').'" />';
	print '</form>';
	print '</div>';

	print "<br>";
	print "<br>";print "<br>";print "<br>";print "<br>";print "<br>";
	// modif de la page
	print '<form method="post">';
	print '<input type="hidden" name="action" value="valid" />';
	print '<input type="hidden" name="modele" value="'.$modele.'" />';
	print '<input type="hidden" name="page" value="'.$page.'" />';

	print '<div style="text-align:right; clear:both;">';
	print '<input type="reset" class="button" value="'.$langs->trans('Cancel').'" onclick="$(\'form#set_page\').submit();"/>';
	print '&emsp;<input type="submit" class="button" value="'.$langs->trans('Record').'" />';
	print '</div>';

	print "<br>";

	dol_include_once('/htmlmodels/class/doleditor.class.php');
	$doleditor=new DolEditor('contract_page_'.$page, $pages[$page], "210mm", "297mm",'dolibarr_notes');

	print $doleditor->Create(1);

	print '<br/>';
	print '<div style="text-align:right; width:210mm;">';
	print '<input type="reset" class="button" value="'.$langs->trans('Cancel').'" onclick="$(\'form#set_page\').submit();"/>';
	print '&emsp;<input type="submit" class="button" value="'.$langs->trans('Record').'" />';
	print '</div>';
	print '</form>';
}
print '</div>';
llxFooter();
