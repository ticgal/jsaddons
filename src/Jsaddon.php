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
use Config;
use DBConnection;
use Glpi\Application\View\TemplateRenderer;
use Migration;
use Plugin;
use Session;

class Jsaddon extends CommonDBTM
{
	static $rightname     = 'config';

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
		return __('JS Addons');
	}

	static function getMenuContent()
	{
		$menu = [];
		if (Config::canUpdate()) {
			$menu['title'] = self::getMenuName();
			$menu['page']  = PLUGIN_JSADDONS_WEB_DIR . '/front/jsaddon.php';
			$menu['icon']  = self::getIcon();
		}
		if (count($menu)) {
			return $menu;
		}

		return false;
	}

	function rawSearchOptions()
	{
		$tab = parent::rawSearchOptions();

		$tab[] = [
			'id' => 10,
			'table' => $this->getTable(),
			'field' => 'is_active',
			'name' => __('Active'),
			'datatype' => 'bool'
		];

		return $tab;
	}

	public static function getIcon()
	{
		return 'fas fa-file-code';
	}

	public function showForm($ID, $options = [])
	{
		$this->initForm($ID, $options);
		TemplateRenderer::getInstance()->display('@jsaddons/jsaddon.form.html.twig', [
			'item'   => $this,
			'params' => $options,
		]);

		return true;
	}

	static function getScript(bool $login_only = false)
	{
		global $DB;

		$query = [
			'SELECT' => ['*'],
			'FROM' => self::getTable(),
			'WHERE' => [
				'is_active' => 1,
			]
		];
		$script = [];
		$dir = realpath(Plugin::getPhpDir('jsaddons') . "/public");
		$iterator = $DB->request($query);
		foreach ($iterator as $row) {
			// Addons restricted to the login page are not served to authenticated sessions
			if (!empty($row['login_only']) && !$login_only) {
				continue;
			}
			if (!isset(self::KEY_RULES[$row['filename']])) {
				continue;
			}
			$key = (string)$row['key'];
			if (!self::isValidKey($row['filename'], $key)) {
				continue;
			}
			$file = realpath($dir . "/" . $row['filename']);
			if ($file !== false && str_starts_with($file, $dir . DIRECTORY_SEPARATOR)) {
				$content = file_get_contents($file);
				$script[] = str_replace("##KEY##", htmlspecialchars($key, ENT_QUOTES), $content);
			}
		}
		return $script;
	}

	public static function isValidKey(string $filename, string $key): bool
	{
		return isset(self::KEY_RULES[$filename]) && preg_match(self::KEY_RULES[$filename], $key) === 1;
	}

	public function prepareInputForUpdate($input)
	{
		// The addon file and name are fixed at install time, never editable
		unset($input['filename'], $input['name']);

		if (isset($input['key']) && $input['key'] !== '') {
			$filename = $this->fields['filename'] ?? '';
			if (!self::isValidKey((string)$filename, (string)$input['key'])) {
				Session::addMessageAfterRedirect(__('Invalid key'), false, ERROR);
				return false;
			}
		}

		return $input;
	}

	private static function renameLegacyItemtype(): void
	{
		global $DB;

		foreach (['glpi_displaypreferences', 'glpi_logs', 'glpi_savedsearches'] as $table) {
			if ($DB->tableExists($table)) {
				$DB->update($table, ['itemtype' => self::class], ['itemtype' => 'PluginJsaddonsJsaddon']);
			}
		}
	}

	private static function sanitizeStoredRows(): void
	{
		global $DB;

		$table = self::getTable();
		$files = [
			'Metricool'        => 'metricool.js',
			'Tawk.to'          => 'tawkto.js',
			'Google Analytics' => 'gtag.js',
		];
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
				}
				$DB->update($table, $values, ['id' => $row['id']]);
				Session::addMessageAfterRedirect(
					sprintf(__('JS Addons: addon "%s" had an invalid value and was deactivated'), $row['name']),
					false,
					WARNING
				);
			}
		}
	}

	public static function install(Migration $migration)
	{
		global $DB;

		$default_charset = DBConnection::getDefaultCharset();
		$default_collation = DBConnection::getDefaultCollation();
		$default_key_sign = DBConnection::getDefaultPrimaryKeySignOption();

		$table = self::getTable();

		if (!$DB->tableExists($table)) {
			$migration->displayMessage("Installing $table");

			$query = "CREATE TABLE IF NOT EXISTS `$table` (
				`id` int {$default_key_sign} NOT NULL AUTO_INCREMENT,
				`name` varchar(255) DEFAULT NULL,
				`filename` varchar(255) DEFAULT NULL,
				`is_active` tinyint(1) NOT NULL DEFAULT '0',
				`login_only` tinyint(1) NOT NULL DEFAULT '1',
				`date_creation` timestamp NULL DEFAULT NULL,
				`date_mod` timestamp NULL DEFAULT NULL,
				`key` varchar(255) NULL,
				PRIMARY KEY (`id`)
			) ENGINE=InnoDB DEFAULT CHARSET={$default_charset} COLLATE={$default_collation} ROW_FORMAT=DYNAMIC;";

			$DB->doQuery($query);

			$jsaddons = new self();
			$list = [
				[
					'name' => 'Metricool',
					'filename' => 'metricool.js'
				],
				[
					'name' => 'Tawk.to',
					'filename' => 'tawkto.js',
				],
				[
					'name' => 'Google Analytics',
					'filename' => 'gtag.js',
				]
			];
			foreach ($list as $key => $value) {
				$jsaddons->add([
					'name' => $value['name'],
					'filename' => $value['filename'],
					'login_only' => 1
				]);
			}
		} else {
			self::renameLegacyItemtype();
			$migration->addField($table, 'login_only', 'bool', ['value' => 0, 'after' => 'is_active']);
			$migration->migrationOneTable($table);
			self::sanitizeStoredRows();
		}
	}

	public static function uninstall(Migration $migration)
	{
		$migration->dropTable(self::getTable());
	}
}
