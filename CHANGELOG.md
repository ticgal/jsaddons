# JS Addons GLPI Plugin CHANGELOG

## 4.0.0-beta.1 - 2026-10-05
### Added
- GLPI 12 support (GLPI 12.0.x only)
- Re-authentication required to manage the addons, as for the core configuration
- Spanish and Galician translations
- Per-addon option "Load only on the login page" so third-party scripts do not run inside GLPI

### Changed
- Classes moved to `src/` with the `GlpiPlugin\Jsaddons` namespace (stored itemtypes are migrated on upgrade)
- Addon form rendered with Twig; Tabler icon
- Assets served without the `/public/` prefix
- New installations load addons only on the login page by default (existing ones keep their behaviour)

### Security
- Addon file and name are no longer editable; only known addon files are loaded (fixes arbitrary file read)
- The key is validated per addon (provider-specific format) and escaped on output (fixes stored XSS)
- The AJAX endpoint now only accepts POST and answers `application/json`, and is only served while the plugin is active
- Uninstall now drops the plugin table and its orphan rows in `glpi_displaypreferences`/`glpi_savedsearches`/`glpi_logs`
- Upgrade deactivates and clears rows with an unknown addon file or an invalid key
- Only `id`, `is_active`, `login_only` and `key` are accepted by the edit form
- The edit form warns that third-party code runs inside GLPI when "Load only on the login page" is off

### Fixed
- Installation failing with "COLLATION 'utf8mb4_unicode_ci' is not valid for CHARACTER SET 'utf8mb3'" (table charset)
- Plugin table and data not removed on uninstall
- The loader script is requested without the deprecated `/public` URL prefix (in development mode the deprecation notice was injected into the JS and broke it)

## 3.0.2 - 2026-10-21
## Changed
- Plugin class moved from `inc/` to `src/` (`GlpiPlugin\Jsaddons\Jsaddon`, PSR-4); display preferences, history and saved searches are migrated on upgrade

## 3.0.1 - 2026-09-01
## Bugfix
- Fix JS route
- Fixed partial session destruction when the password expired

## 3.0.0 - 2026-02-03
## Added
- GLPI 11 support

## 2.0.0 - 2022-07-27
### Features
- GLPI 10 Compatibility #10338

## 1.0.0
### Features
Analytics:
* Metricool
* Google Analytics

Chat:
* Tawk.to
