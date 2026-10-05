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

namespace GlpiPlugin\Jsaddons;

use CommonDBTM;
use DBConnection;
use DBmysql;
use Glpi\Application\View\TemplateRenderer;
use Migration;
use Plugin;
use Session;

class Jsaddon extends CommonDBTM
{
    public static string $rightname = 'config';

    /**
     * Addons shipped with the plugin: `filename` is a template in public/ where
     * `##KEY##` is replaced by the configured key.
     */
    private const ADDONS = [
        ['name' => 'Metricool',        'filename' => 'metricool.js'],
        ['name' => 'Tawk.to',          'filename' => 'tawkto.js'],
        ['name' => 'Google Analytics', 'filename' => 'gtag.js'],
    ];

    /**
     * The key is injected verbatim in an HTML/JS snippet served to every visitor,
     * so only the characters used by the supported providers are accepted
     * (Metricool hash, Tawk.to "property/widget", Google "G-XXXX"/"UA-X-Y").
     */
    private const KEY_PATTERN = '/^[A-Za-z0-9._\/-]*$/';

    public static function getTypeName($nb = 0)
    {
        return __('JS Addons', 'jsaddons');
    }

    public static function getIcon()
    {
        return 'ti ti-file-code';
    }

    /**
     * Same protection as the core configuration: the keys are injected in every page.
     */
    protected static function itemTypeRequiresReauthentication(): bool
    {
        return true;
    }

    /**
     * Addons are a fixed list created on install: they can only be enabled/configured.
     */
    public static function canCreate(): bool
    {
        return false;
    }

    public static function canDelete(): bool
    {
        return false;
    }

    public static function canPurge(): bool
    {
        return false;
    }

    public static function getMenuContent()
    {
        if (!static::canUpdate()) {
            return false;
        }

        return [
            'title' => self::getMenuName(),
            'page'  => self::getSearchURL(false),
            'icon'  => self::getIcon(),
        ];
    }

    public function rawSearchOptions()
    {
        $tab = parent::rawSearchOptions();

        $tab[] = [
            'id'       => 10,
            'table'    => $this->getTable(),
            'field'    => 'is_active',
            'name'     => __('Active'),
            'datatype' => 'bool',
        ];

        return $tab;
    }

    public function showForm($ID, array $options = [])
    {
        $this->initForm($ID, $options);
        $options['candel'] = false;

        TemplateRenderer::getInstance()->display('@jsaddons/jsaddon.html.twig', [
            'item'   => $this,
            'params' => $options,
        ]);

        return true;
    }

    public function prepareInputForUpdate($input)
    {
        // Only the state and the key are editable: name and filename are fixed on install
        // (filename is read from disk and served to anonymous visitors).
        unset($input['name'], $input['filename']);

        if (isset($input['key'])) {
            $input['key'] = trim((string) $input['key']);
            if (!self::isValidKey($input['key'])) {
                Session::addMessageAfterRedirect(
                    __('Invalid key: only letters, digits and the characters . _ - / are allowed.', 'jsaddons'),
                    false,
                    ERROR,
                );
                return false;
            }
        }

        if (isset($input['is_active'])) {
            $input['is_active'] = (int) (bool) $input['is_active'];
        }

        return $input;
    }

    private static function isValidKey(string $key): bool
    {
        return strlen($key) <= 255 && preg_match(self::KEY_PATTERN, $key) === 1;
    }

    /**
     * Snippets of the active addons, ready to be appended to the page <head>.
     *
     * @return string[]
     */
    public static function getScript(): array
    {
        /** @var DBmysql $DB */
        global $DB;

        $public_dir = realpath(Plugin::getPhpDir('jsaddons') . '/public');
        $script     = [];

        $iterator = $DB->request([
            'SELECT' => ['filename', 'key'],
            'FROM'   => self::getTable(),
            'WHERE'  => ['is_active' => 1],
        ]);
        foreach ($iterator as $row) {
            $key = (string) $row['key'];
            if ($key === '' || !self::isValidKey($key)) {
                continue;
            }

            $file = realpath($public_dir . '/' . basename((string) $row['filename']));
            if ($file === false || !str_starts_with($file, $public_dir . DIRECTORY_SEPARATOR)) {
                continue;
            }

            $script[] = str_replace('##KEY##', $key, (string) file_get_contents($file));
        }

        return $script;
    }

    public static function install(Migration $migration): void
    {
        /** @var DBmysql $DB */
        global $DB;

        $table = self::getTable();

        if (!$DB->tableExists($table)) {
            $migration->displayMessage("Installing $table");

            $default_charset   = DBConnection::getDefaultCharset();
            $default_collation = DBConnection::getDefaultCollation();
            $default_key_sign  = DBConnection::getDefaultPrimaryKeySignOption();

            $DB->doQuery(
                "CREATE TABLE IF NOT EXISTS `$table` (
                    `id` int {$default_key_sign} NOT NULL AUTO_INCREMENT,
                    `name` varchar(255) DEFAULT NULL,
                    `filename` varchar(255) DEFAULT NULL,
                    `is_active` tinyint NOT NULL DEFAULT '0',
                    `date_creation` timestamp NULL DEFAULT NULL,
                    `date_mod` timestamp NULL DEFAULT NULL,
                    `key` varchar(255) DEFAULT NULL,
                    PRIMARY KEY (`id`),
                    KEY `is_active` (`is_active`)
                ) ENGINE=InnoDB DEFAULT CHARSET={$default_charset} COLLATE={$default_collation} ROW_FORMAT=DYNAMIC;",
            );
        } else {
            $migration->addKey($table, 'is_active');
        }

        // Seed the shipped addons (also restores any missing one on upgrade)
        $addon = new self();
        foreach (self::ADDONS as $value) {
            if (countElementsInTable($table, ['filename' => $value['filename']]) === 0) {
                $addon->add($value);
            }
        }

        // Since 4.0.0 the itemtype is namespaced: migrate what GLPI stored with the legacy name
        // (display preferences, saved searches, logs...). The table name does not change.
        $migration->renameItemtype('PluginJsaddonsJsaddon', self::class, false);
    }

    public static function uninstall(Migration $migration): void
    {
        /** @var DBmysql $DB */
        global $DB;

        $table = self::getTable();
        if ($DB->tableExists($table)) {
            $migration->displayMessage("Uninstalling $table");
            $migration->dropTable($table);
        }

        foreach (['glpi_displaypreferences', 'glpi_savedsearches', 'glpi_logs'] as $itemtype_table) {
            $DB->delete($itemtype_table, ['itemtype' => [self::class, 'PluginJsaddonsJsaddon']]);
        }
    }
}
