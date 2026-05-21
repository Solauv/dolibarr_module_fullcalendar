# Audit Report - Remove ABRICOT Dependency

## Scope

Audited the implementation against:

- `docs/blueprint-remove-abricot.md`
- `docs/superpowers/plans/2026-05-21-remove-abricot-dependency.md`
- Current git diff for the feature branch/worktree

## Contract Check

Status: compliant after the corrections listed below and after restoring the existing TechATM about page behavior.

- `config.default.php` no longer loads `/abricot/inc.core.php`.
- ABRICOT-style `DB_*` constants were removed from the bootstrap.
- `script/create-maj-base.php` no longer references `TPDOdb` or ABRICOT sample code.
- `core/modules/modfullcalendar.class.php` no longer depends on ABRICOT, but still uses `TechATM::getLastModuleVersionUrl()` by project choice.
- `admin/fullcalendar_about.php` still uses the existing `TechATM` about page by project choice.
- No database table, trigger, hook, or `fk_soc` ownership change was introduced.
- Version and `ChangeLog.md` were updated.

## Findings

### F1 - HTML output hardening incomplete in `FullcalendarAboutPage`

Severity: Medium

File: `class/FullcalendarAboutPage.class.php`

The new renderer escapes dynamic module values, but prints several translated labels/values directly through `$langs->trans()`. Dolibarr language files are trusted project assets, but the project rule for this audit is stricter: every HTML output must be escaped unless intentionally rendering HTML. These strings are plain labels, so they should be escaped with `dol_escape_htmltag()`.

Correction applied, then superseded:

- `FullcalendarAboutPage` was hardened, then removed after restoring the existing `TechATM` about page.

### F2 - Unused class constant

Severity: Low

File: `class/FullcalendarAboutPage.class.php`

`MODULE_PICTO` is declared but never used. The coding rules require no unused symbols.

Correction applied, then superseded:

- `MODULE_PICTO` was removed, then the whole local renderer was removed after restoring the existing `TechATM` about page.

## Security Checklist

- SQLi: no new SQL was introduced.
- `SELECT *`: no new SELECT was introduced.
- GETPOST: no new GETPOST call was introduced.
- CSRF: no new write/delete action was introduced.
- XSS: no new local about-page renderer remains after restoring `TechATM`.
- Transactions: no new multi-step database write was introduced.
- `$db->free()`: no new SELECT was introduced.
- Logs: no new error path requiring `dol_syslog()` was introduced.

## Regression Checklist

- Interface contract: `admin/fullcalendar_about.php` uses the pre-existing `TechATM::getAboutPage()` flow.
- Existing `modfullcalendar::init()` signature is unchanged.
- Existing hooks `agenda` and `agendalist` remain unchanged.
- No SQL schema or native Dolibarr table behavior is affected.
- `TechATM` remains an active project-specific integration by project choice. The ABRICOT dependency remains removed.

## Verification Already Run

- `php -l config.default.php`
- `php -l config.php`
- `php -l script/create-maj-base.php`
- `php -l core/modules/modfullcalendar.class.php`
- `php -l admin/fullcalendar_about.php`
- `php -l script/test_remove_abricot_dependency.php`
- `php script/test_remove_abricot_dependency.php`
- Active ABRICOT runtime scan excluding docs and the regression-test fixture

## Required Corrections

No remaining correction required for ABRICOT removal. TechATM has been intentionally restored.

## Corrections Applied

- F1/F2 superseded: `FullcalendarAboutPage` was removed when the TechATM about page was restored.
