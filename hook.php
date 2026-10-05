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

use GlpiPlugin\Jsaddons\Jsaddon;

function plugin_jsaddons_install()
{
    $migration = new Migration(PLUGIN_JSADDONS_VERSION);

    Jsaddon::install($migration);

    $migration->executeMigration();

    return true;
}

function plugin_jsaddons_uninstall()
{
    $migration = new Migration(PLUGIN_JSADDONS_VERSION);

    Jsaddon::uninstall($migration);

    $migration->executeMigration();

    return true;
}

function plugin_jsaddons_login()
{
    /** @var array $CFG_GLPI */
    global $CFG_GLPI;

    echo Html::script(
        $CFG_GLPI['root_doc'] . '/plugins/jsaddons/jsaddons.js',
        ['version' => PLUGIN_JSADDONS_VERSION],
    );
}
