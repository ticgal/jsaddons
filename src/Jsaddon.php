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
     * Allowed addon files and the format of the key each one accepts.
     */
    private const KEY_RULES = [
        'gtag.js'      => '/^(G-[A-Z0-9]{4,12}|UA-\d{4,10}-\d{1,3}|AW-\d{6,12})$/D',
        'tawkto.js'    => '#^[a-f0-9]{24}/[a-z0-9]{1,16}$#iD',
        'metricool.js' => '/^[a-z0-9]{16,64}$/iD',
    ];

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
        // Only the state and the key are editable: name, filename and the
        // creation/modification timestamps are fixed at install time
        $input = array_intersect_key($input, array_flip(['id', 'is_active', 'login_only', 'key']));

        if (isset($input['key'])) {
            if (!is_scalar($input['key'])) {
                Session::addMessageAfterRedirect(__('Invalid key', 'jsaddons'), false, ERROR);
                return false;
            }
            $input['key'] = (string) $input['key'];
            if ($input['key'] !== '') {
                $filename = $this->fields['filename'] ?? '';
                if (!self::isValidKey((string) $filename, $input['key'])) {
                    Session::addMessageAfterRedirect(__('Invalid key', 'jsaddons'), false, ERROR);
                    return false;
                }
            }
        }

        if (isset($input['is_active'])) {
            $input['is_active'] = (int) (bool) $input['is_active'];
        }

        if (isset($input['login_only'])) {
            $input['login_only'] = (int) (bool) $input['login_only'];
        }

        return $input;
    }

    public static function isValidKey(string $filename, string $key): bool
    {
        return isset(self::KEY_RULES[$filename])
            && strlen($key) <= 255
            && preg_match(self::KEY_RULES[$filename], $key) === 1;
    }

    private static function sanitizeStoredRows(): void
    {
        /** @var DBmysql $DB */
        global $DB;

        $table = self::getTable();
        $files = array_column(self::ADDONS, 'filename', 'name');
        foreach ($DB->request(['FROM' => $table]) as $row) {
            $filename = (string) $row['filename'];
            $key      = (string) $row['key'];
            $bad_file = !isset(self::KEY_RULES[$filename]);
            $bad_key  = $key !== '' && !self::isValidKey($filename, $key);
            if ($bad_file || $bad_key) {
                $values = [
                    'key'       => null,
                    'is_active' => 0,
                ];
                // Restore the addon file from its name so it can be enabled again
                if ($bad_file && isset($files[$row['name']])) {
                    $values['filename'] = $files[$row['name']];
                } elseif ($bad_file) {
                    // Unknown addon (e.g. a pre-4.0.0 row whose name/filename could be
                    // set to anything): wipe the name too so nothing untrusted survives
                    // to be rendered on a later upgrade
                    $values['name'] = __('Unknown addon', 'jsaddons');
                }
                $DB->update($table, $values, ['id' => $row['id']]);
                Session::addMessageAfterRedirect(
                    sprintf(
                        __('JS Addons: addon "%s" had an invalid value and was deactivated'),
                        htmlescape($row['name']),
                    ),
                    false,
                    WARNING,
                );
            }
        }
    }

    /**
     * Snippets of the active addons, ready to be appended to the page <head>.
     *
     * @param bool $login_only true when called from the (anonymous) login page
     *
     * @return string[]
     */
    public static function getScript(bool $login_only = false): array
    {
        /** @var DBmysql $DB */
        global $DB;

        $public_dir = realpath(Plugin::getPhpDir('jsaddons') . '/public');
        $script     = [];

        $iterator = $DB->request([
            'SELECT' => ['filename', 'key', 'login_only'],
            'FROM'   => self::getTable(),
            'WHERE'  => ['is_active' => 1],
        ]);
        foreach ($iterator as $row) {
            // Addons restricted to the login page are not served to authenticated sessions
            if (!empty($row['login_only']) && !$login_only) {
                continue;
            }

            $filename = (string) $row['filename'];
            $key      = (string) $row['key'];
            if (!self::isValidKey($filename, $key)) {
                continue;
            }

            $file = realpath($public_dir . '/' . basename($filename));
            if ($file === false || !str_starts_with($file, $public_dir . DIRECTORY_SEPARATOR)) {
                continue;
            }

            // ##KEY## sits inside a double-quoted JS string literal in every shipped
            // template: escape it for that context, not for HTML (the per-provider
            // regex above already restricts the charset, this is defense in depth)
            $js_safe_key = substr(json_encode($key, JSON_THROW_ON_ERROR), 1, -1);
            $script[] = str_replace('##KEY##', $js_safe_key, (string) file_get_contents($file));
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
                    `login_only` tinyint NOT NULL DEFAULT '1',
                    `date_creation` timestamp NULL DEFAULT NULL,
                    `date_mod` timestamp NULL DEFAULT NULL,
                    `key` varchar(255) DEFAULT NULL,
                    PRIMARY KEY (`id`),
                    KEY `is_active` (`is_active`)
                ) ENGINE=InnoDB DEFAULT CHARSET={$default_charset} COLLATE={$default_collation} ROW_FORMAT=DYNAMIC;",
            );
        } else {
            // Older installs did not restrict the addon to the login page: keep that
            // behaviour on upgrade (value 0), new rows below explicitly opt in (value 1)
            $migration->addField($table, 'login_only', 'bool', ['value' => 0, 'after' => 'is_active']);
            $migration->addKey($table, 'is_active');
            $migration->migrationOneTable($table);
        }

        // Seed the shipped addons (also restores any missing one on upgrade)
        $addon = new self();
        foreach (self::ADDONS as $value) {
            if (countElementsInTable($table, ['filename' => $value['filename']]) === 0) {
                $addon->add($value + ['login_only' => 1]);
            }
        }

        // Since 4.0.0 the itemtype is namespaced: migrate what GLPI stored with the legacy name
        // (display preferences, saved searches, logs...). The table name does not change.
        $migration->renameItemtype('PluginJsaddonsJsaddon', self::class, false);

        self::sanitizeStoredRows();
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
