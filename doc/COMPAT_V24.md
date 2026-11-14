# Compatibility report — Dolibarr v24

- **Module**: fullcalendar
- **Current version**: 2.9.0 → **2.9.1** (compat release, no code change) — target: **Dolibarr v24** (24.0.x)
- **Date**: 2026-06-10
- **Branch**: FIX/COMPATV24 (cut from 2.9)
- **Sources**: core ChangeLog v24 + complementary checks (csrf-token, code-compta) + select_contacts removal

## Summary

| Severity | Count |
|---|---|
| BLOCKER | 0 |
| WARNING | 0 |
| N/A | 14 |

The module is **already compatible with Dolibarr v24** — nothing to fix. This is a compat
release recording the v24 validation (no functional change). The two items that *looked* like
candidates — `Form::select_contacts()` removal and the `interface.php` AJAX CSRF surface — are
both already handled by the module (a Dolibarr-version guard, and consistently tokenised AJAX).
See **Items** and the **Out-of-scope** notes.

## Items

| # | Source | Item | Status | Evidence |
|---|---|---|---|---|
| 1 | ChangeLog v24 | USF required on string `fetchAll()` filters | N/A | no `fetchAll()` usage |
| 2 | ChangeLog v24 | `PAYMENT_SECURITY_TOKEN_UNIQUE` removed | N/A | not used |
| 3 | ChangeLog v24 | Online signature salt change | N/A | not used |
| 4 | ChangeLog v24 | Login API off by default (`API_ENABLE_LOGIN_API`) | N/A | not used |
| 5 | ChangeLog v24 | `DEPOSIT_AS_CREDIT…` renamed | N/A | not used |
| 6 | ChangeLog v24 | `__MYCOUNTRY_ID__` substitution removed | N/A | not used |
| 7 | ChangeLog v24 | **Hook** "info_admin" renamed to "messageOfTheDay" | N/A | no `info_admin` occurrence (neither hook context nor function call) |
| 8 | ChangeLog v24 | jeditable removed | N/A | not used |
| 9 | ChangeLog v24 | Paybox removed | N/A | not used |
| 10 | Extra (csrf-token) | Non-whitelisted GET `action=` without token | N/A | the only `action=` values read on the module's own pages are `show_month`/`show_week`/`show_day` in `script/interface.php` (external-calendar rendering path, after `if (empty($listofextcals)) return;`). The JS never sends `action=show_*` itself; those values are JS-side view selectors read from the host list form (`js/fullcalendar.js.php:206-209`). The event feed uses `get=events` (no `action=`). No `?action=`/`&action=` link is built tokenless. |
| 11 | Extra (csrf-token) | POST form / mutating AJAX without token | N/A | all admin setup forms emit `<input type="hidden" name="token" value="newToken()">` (`admin/fullcalendar_setup.php`, `admin/fullcalendar_task_setup.php`). All event create/update/clone AJAX is `method:'POST'` with `token: token` (`js/fullcalendar.js.php:1025-1040`, `1120-1153`). The clone follow-up GET to core `comm/action/card.php?action=confirm_clone` also passes `token` (line 1152). |
| 12 | Extra (csrf-token) | AJAX endpoint exposure | N/A | `script/interface.php` keeps the CSRF gate **active** — it defines only `NOTOKENRENEWAL` (stops rolling-token renewal on a repeatedly-hit endpoint) and `NOSCANPOSTFORINJECTION`, **not** `NOCSRFCHECK`. Every call to it carries a valid `token` (event-source serialises the core `form#searchFormList`, which includes a token; drag/resize and CRUD send `token: token`). Adding `NOCSRFCHECK` here would be **wrong** — it would strip CSRF protection from the mutating POSTs. |
| 13 | Extra (code-compta) | Deprecated bare `->code_compta` read | N/A | no occurrence |
| 14 | Extra (select_contacts) | `Form::select_contacts()` removed in v24 | N/A | already version-guarded: `js/fullcalendar.js.php:119-124` calls `$form->select_contact(...)` for Dolibarr ≥ 20 (the v24 path) and only falls back to the removed `select_contacts()` for Dolibarr < 20. `Form::select_contact()` **exists** in v24 core (`html.form.class.php:1918`); the matching 8 positional args are valid. The removed method is dead code on v24. |

## Baseline

- `php -l` sweep over all 17 `*.php` (excl. vendor): **0 syntax errors**.
- Descriptor sanity: `numero=104851`, `rights_class='fullcalendar'`, `const_name=MAIN_MODULE_FULLCALENDAR`, coherent with `modfullcalendar`.

## Note — select_contacts / select_contact

The v24 ChangeLog removes the legacy `Form::select_contacts()` (underscore, plural). This module
already routes around it: on Dolibarr ≥ 20 it uses `Form::select_contact()` (singular), which is
still present in v24 core. `select_contact()` is itself flagged `@deprecated` in core in favour of
`selectcontacts()`, but it is **not removed** in v24 — so it works. A future migration to
`selectcontacts()` is optional hardening, not a v24 requirement.

## Out-of-scope observations (pre-existing, NOT v24 regressions — flagged, not fixed)

1. **State-changing GET on drag/resize** — `eventDrop`/`eventResize` (`js/fullcalendar.js.php:450`,
   `472`) send `put=event-move` / `put=event-resize` as **GET** (no `method:` → jQuery default).
   These are DB writes over GET. They are **token-protected** (a valid `token` rides in the query
   string) and v24 does not 403 them (no `action=`), so it is neither a compat break nor an
   exploitable CSRF hole — but converting them to `method:'POST'` would align with the other
   mutating calls. Pre-existing design choice; out of compat scope.

2. **Bundled upstream demo PHP** — `lib/fullcalendar/dist/demos/php/{get-events,get-timezones,utils}.php`
   are the FullCalendar library's own standalone demo endpoints (no Dolibarr integration, no v24
   API use). No v24 impact, but unused web-reachable demo scripts are worth pruning as hygiene.

3. **Runtime check worth doing** — the external-calendar feed path (`action=show_week`/`show_day`
   in `interface.php`) only fires when external calendars are configured. Static evidence shows the
   event feed serialises the core `searchFormList` (which carries a token), so no 403 is expected at
   CSRF level 3; a quick manual check with an external calendar + week/day view confirms it.
