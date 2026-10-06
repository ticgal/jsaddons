# JS Addons GLPI Plugin CHANGELOG

## 4.0.0-beta.1 - 2026-10-05
### Added
- GLPI 12 support (GLPI 12.0.x only)
- Re-authentication required to manage the addons, as for the core configuration
- Spanish and Galician translations

### Changed
- Classes moved to `src/` with the `GlpiPlugin\Jsaddons` namespace (stored itemtypes are migrated on upgrade)
- Addon form rendered with Twig; Tabler icon
- Assets served without the `/public/` prefix

### Security
- The addon file name can no longer be changed from the form, and only the snippets shipped with the plugin are served
- Keys are validated (letters, digits and `. _ - /`) before being saved and before being served

### Fixed
- Installation failing with "COLLATION 'utf8mb4_unicode_ci' is not valid for CHARACTER SET 'utf8mb3'" (table charset)
- Plugin table and data not removed on uninstall

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
