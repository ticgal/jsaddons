<?php
/*
 -------------------------------------------------------------------------
 JS Addons plugin for GLPI
 Copyright (C) 2018-2026 by the TICGAL Team.

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
 @author    the TICGAL team
 @copyright Copyright (c) 2026 TICGAL team
 @license   AGPL License 3.0 or (at your option) any later version
            http://www.gnu.org/licenses/agpl-3.0-standalone.html
 @link      https://tic.gal
 @since     2018
 ---------------------------------------------------------------------- */

function plugin_jsaddons_install(){
	$migration=new Migration(PLUGIN_JSADDONS_VERSION);

	foreach (glob(__DIR__ .'/inc/*') as $filepath) {
		if (preg_match("/inc.(.+)\.class.php/", $filepath, $matches)) {
			$classname = 'PluginJsaddons' . ucfirst($matches[1]);
			include_once($filepath);
			if (method_exists($classname, 'install')) {
				$classname::install($migration);
			}
		}
	}

	$migration->executeMigration();

	return true;
}

function plugin_jsaddons_uninstall(){
	$migration=new Migration(PLUGIN_JSADDONS_VERSION);

	foreach (glob(__DIR__ .'/inc/*') as $filepath) {
		if (preg_match("/inc.(.+)\.class.php/", $filepath, $matches)) {
			$classname = 'PluginJsaddons' . ucfirst($matches[1]);
			include_once($filepath);
			if (method_exists($classname, 'uninstall')) {
				$classname::uninstall($migration);
			}
		}
	}

	$migration->executeMigration();

	return true;
}

function plugin_jsaddons_login(){
	global $CFG_GLPI;
	$version = Plugin::getInfo('jsaddons', 'version');
	echo Html::script($CFG_GLPI['root_doc'] . "/" . PLUGIN_JSADDONS_WEB_DIR . "/public/jsaddons.js", ['version' => $version]);
}
