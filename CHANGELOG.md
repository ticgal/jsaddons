# JS Addons GLPI Plugin CHANGELOG

## 3.0.2 - 2026-10-21
## Changed
- Plugin class moved from `inc/` to `src/` (`GlpiPlugin\Jsaddons\Jsaddon`, PSR-4); display preferences, history and saved searches are migrated on upgrade
## Added
- New installations load addons only on the login page by default (existing ones keep their behaviour)
- Per-addon option "Load only on the login page" so third-party scripts do not run inside GLPI
## Bugfix
- The loader script is requested without the deprecated `/public` URL prefix (in development mode the deprecation notice was injected into the JS and broke it)
## Security
- Addon file and name are no longer editable; only known addon files are loaded (fixes arbitrary file read)
- The key is validated per addon and escaped on output (fixes stored XSS)
- The AJAX endpoint now only accepts POST and answers `application/json`
- Uninstall now drops the plugin table
- Upgrade deactivates and clears rows with an unknown addon file or an invalid key
- Only `id`, `is_active`, `login_only` and `key` are accepted by the edit form
- Keys with a trailing newline are rejected
- The public AJAX endpoint is only registered and served while the plugin is active
- The edit form warns that third-party code runs inside GLPI when "Load only on the login page" is off

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
