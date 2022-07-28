<?php
/*
 -------------------------------------------------------------------------
 JS Addons plugin for GLPI
 Copyright (C) 2018 by the TICgal Team.

 https://github.com/ticgal/jsaddons
 -------------------------------------------------------------------------

 LICENSE

 This file is part of the JS Addons plugin.

 JS Addons plugin is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation; either version 3 of the License, or
 (at your option) any later version.

 JS Addons plugin is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with JS Addons. If not, see <http://www.gnu.org/licenses/>.
 --------------------------------------------------------------------------
 @package   JS Addons
 @author    the TICgal team
 @copyright Copyright (c) 2018 TICgal team
 @license   AGPL License 3.0 or (at your option) any later version
            http://www.gnu.org/licenses/agpl-3.0-standalone.html
 @link      https://tic.gal
 @since     2020-2022
 ---------------------------------------------------------------------- */
 
include ('../../../inc/includes.php');

$plugin=new Plugin();
if (!$plugin->isInstalled('jsaddons') || !$plugin->isActivated('jsaddons')) {
	Html::displayNotFoundError();
}
$jsaddons= new PluginJsaddonsJsaddon();
if (isset($_POST['update'])) {

   //Check UPDATE
   $jsaddons->check($_POST['id'], UPDATE);
   //Do object update
   $jsaddons->update($_POST);
   //Redirect to object form
   Html::back();

} else {

   $jsaddons->checkGlobal(READ);

   Html::header(
		PluginJsaddonsJsaddon::getTypeName(2),
		'',
		'config',
		'pluginjsaddonsjsaddon'
	);
   $jsaddons->display($_GET);
   Html::footer();
}
