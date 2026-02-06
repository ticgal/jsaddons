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

define('PLUGIN_JSADDONS_VERSION','3.0.0');

define('PLUGIN_JSADDONS_MIN_GLPI','11.0');
define('PLUGIN_JSADDONS_MAX_GLPI','12.0');

define('PLUGIN_JSADDONS_WEB_DIR','plugins/jsaddons');

function plugin_version_jsaddons(){
	return [
		'name'=>'JS Addons',
		'version'=>PLUGIN_JSADDONS_VERSION,
		'author'=>'<a href="https://tic.gal">TICgal</a>',
		'homepage' => 'https://tic.gal/en/jsaddons',
		'requirements' => [
			'glpi' => [
				'min' => PLUGIN_JSADDONS_MIN_GLPI,
				'max' => PLUGIN_JSADDONS_MAX_GLPI,
			]
		]
	];
}

function plugin_init_jsaddons(){
	global $PLUGIN_HOOKS,$CFG_GLPI;
	$PLUGIN_HOOKS['csrf_compliant']['jsaddons']=true;
	$plugin=new Plugin();
	if ($plugin->isActivated('jsaddons')) {
		$PLUGIN_HOOKS['menu_toadd']['jsaddons'] = [
			'config' => 'PluginJsaddonsJsaddon',
		];
		$PLUGIN_HOOKS['add_javascript']['jsaddons'][]="js/jsaddons.js";
		$PLUGIN_HOOKS['display_login']['jsaddons']="plugin_jsaddons_login";
	}
}
