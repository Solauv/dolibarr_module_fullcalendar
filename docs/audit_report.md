# Audit Report - Remove ABRICOT Dependency

## Scope

Audited the implementation against:

- `docs/blueprint-remove-abricot.md`
- `docs/superpowers/plans/2026-05-21-remove-abricot-dependency.md`
- Current git diff for the feature branch/worktree

## Contract Check

Status: compliant after the corrections listed below.

- `config.default.php` no longer loads `/abricot/inc.core.php`.
- ABRICOT-style `DB_*` constants were removed from the bootstrap.
- `script/create-maj-base.php` no longer references `TPDOdb` or ABRICOT sample code.
- `core/modules/modfullcalendar.class.php` no longer loads `class/techatm.class.php` or calls `TechATM::getLastModuleVersionUrl()`.
- `admin/fullcalendar_about.php` now uses `FullcalendarAboutPage`.
- No database table, trigger, hook, or `fk_soc` ownership change was introduced.
- Version and `ChangeLog.md` were updated.

## Findings

### F1 - HTML output hardening incomplete in `FullcalendarAboutPage`

Severity: Medium

File: `class/FullcalendarAboutPage.class.php`

The new renderer escapes dynamic module values, but prints several translated labels/values directly through `$langs->trans()`. Dolibarr language files are trusted project assets, but the project rule for this audit is stricter: every HTML output must be escaped unless intentionally rendering HTML. These strings are plain labels, so they should be escaped with `dol_escape_htmltag()`.

Correction required:

- Add a private helper that returns escaped translated strings.
- Use it for all labels and translated values in the generated table.

### F2 - Unused class constant

Severity: Low

File: `class/FullcalendarAboutPage.class.php`

`MODULE_PICTO` is declared but never used. The coding rules require no unused symbols.

Correction required:

- Remove `MODULE_PICTO`.

## Security Checklist

- SQLi: no new SQL was introduced.
- `SELECT *`: no new SELECT was introduced.
- GETPOST: no new GETPOST call was introduced.
- CSRF: no new write/delete action was introduced.
- XSS: one hardening gap found in `FullcalendarAboutPage`; correction required in F1.
- Transactions: no new multi-step database write was introduced.
- `$db->free()`: no new SELECT was introduced.
- Logs: no new error path requiring `dol_syslog()` was introduced.

## Regression Checklist

- Interface contract: `FullcalendarAboutPage::render()` has one call site in `admin/fullcalendar_about.php`.
- Existing `modfullcalendar::init()` signature is unchanged.
- Existing hooks `agenda` and `agendalist` remain unchanged.
- No SQL schema or native Dolibarr table behavior is affected.
- Historical `TechATM` class remains present for compatibility, but no active runtime path uses it.

## Verification Already Run

- `php -l config.default.php`
- `php -l config.php`
- `php -l script/create-maj-base.php`
- `php -l core/modules/modfullcalendar.class.php`
- `php -l class/FullcalendarAboutPage.class.php`
- `php -l admin/fullcalendar_about.php`
- `php -l script/test_remove_abricot_dependency.php`
- `php script/test_remove_abricot_dependency.php`
- Active runtime scan excluding docs and the regression-test fixture

## Required Corrections

Apply F1 and F2, then rerun syntax checks, the static regression script, and the active runtime scan.

## Corrections Applied

- F1 fixed: `FullcalendarAboutPage` now escapes translated labels and values before emitting HTML.
- F2 fixed: unused `MODULE_PICTO` constant removed.
