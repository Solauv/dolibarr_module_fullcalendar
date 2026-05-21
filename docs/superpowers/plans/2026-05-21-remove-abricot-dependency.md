# Remove ABRICOT Dependency Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Make the `fullcalendar` Dolibarr module installable, activable, and usable without the ABRICOT framework.

**Architecture:** Replace the ABRICOT bootstrap with a Dolibarr-native bootstrap, remove active `TechATM` calls from module loading, and provide a local about-page renderer. Keep the existing hook/AJAX behavior unchanged and avoid database changes because this module owns no SQL tables.

**Tech Stack:** Dolibarr module PHP, PHP 7.0-compatible syntax, Dolibarr native helpers (`main.inc.php`, `master.inc.php`, `dol_include_once`, `dol_escape_htmltag`, `getDolGlobalString`), shell verification with `rg` and `php -l`.

---

## File Structure

- Modify: `config.default.php`
  - Responsibility: load the Dolibarr runtime only; no ABRICOT include, no ABRICOT DB constants.
- Modify: `config.php`
  - Responsibility: proxy to `config.default.php`; convert `require` to `require_once`.
- Modify: `script/create-maj-base.php`
  - Responsibility: legacy activation script that remains callable but performs no ABRICOT work.
- Modify: `core/modules/modfullcalendar.class.php`
  - Responsibility: module descriptor, rights, menus, activation; remove active `TechATM` dependency.
- Create: `class/FullcalendarAboutPage.class.php`
  - Responsibility: render a local, offline-compatible about page.
- Modify: `admin/fullcalendar_about.php`
  - Responsibility: use the local about-page renderer instead of `TechATM`.
- Modify: `langs/fr_FR/fullcalendar.lang`
  - Responsibility: add local about-page translations and remove active dependence on `ATMAbout`.
- Modify: `langs/en_US/fullcalendar.lang`
  - Responsibility: add local about-page translations and remove active dependence on `ATMAbout`.
- Modify: `README`
  - Responsibility: document that ABRICOT is no longer required.
- Modify: `ChangeLog.md`
  - Responsibility: record the dependency removal.
- Create: `script/test_remove_abricot_dependency.php`
  - Responsibility: static regression test for ABRICOT and `TechATM` active dependencies.

## Task 1: Add Static Regression Test

**Files:**
- Create: `script/test_remove_abricot_dependency.php`

- [ ] **Step 1: Write the failing regression script**

Create `script/test_remove_abricot_dependency.php` with this exact content:

```php
<?php
/* Copyright (C) 2026
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 */

$moduleRoot = realpath(__DIR__.'/..');
if ($moduleRoot === false) {
	fwrite(STDERR, "[FAIL] Unable to resolve module root\n");
	exit(1);
}

$checks = array(
	'config.default.php' => array(
		"'/abricot/inc.core.php'" => "config.default.php must not require ABRICOT",
		"'AbricotNotFound'" => "config.default.php must not block startup when ABRICOT is absent",
		"'DB_HOST'" => "config.default.php must not define ABRICOT-style DB constants",
		"'DB_NAME'" => "config.default.php must not define ABRICOT-style DB constants",
		"'DB_USER'" => "config.default.php must not define ABRICOT-style DB constants",
		"'DB_PASS'" => "config.default.php must not define ABRICOT-style DB constants",
		"'DB_DRIVER'" => "config.default.php must not define ABRICOT-style DB constants",
	),
	'script/create-maj-base.php' => array(
		"'TPDOdb'" => "create-maj-base.php must not mention ABRICOT TPDOdb",
		"'TXXX'" => "create-maj-base.php must not keep ABRICOT sample object code",
	),
	'core/modules/modfullcalendar.class.php' => array(
		"'techatm.class.php'" => "module descriptor must not require TechATM",
		"'TechATM::'" => "module descriptor must not call TechATM",
		"'modules-last-version.php'" => "module descriptor must not call ATM remote version endpoint",
	),
	'admin/fullcalendar_about.php' => array(
		"'techatm.class.php'" => "about page must not require TechATM",
		"'new \\fullcalendar\\TechATM'" => "about page must not instantiate TechATM",
	),
);

$failed = false;
foreach ($checks as $relativePath => $needles) {
	$path = $moduleRoot.'/'.$relativePath;
	if (!is_readable($path)) {
		fwrite(STDERR, "[FAIL] Missing readable file: ".$relativePath."\n");
		$failed = true;
		continue;
	}

	$content = file_get_contents($path);
	if ($content === false) {
		fwrite(STDERR, "[FAIL] Unable to read file: ".$relativePath."\n");
		$failed = true;
		continue;
	}

	foreach ($needles as $needle => $message) {
		$literalNeedle = trim($needle, "'");
		if (strpos($content, $literalNeedle) !== false) {
			fwrite(STDERR, "[FAIL] ".$message." in ".$relativePath."\n");
			$failed = true;
		}
	}
}

$aboutClass = $moduleRoot.'/class/FullcalendarAboutPage.class.php';
if (!is_readable($aboutClass)) {
	fwrite(STDERR, "[FAIL] Missing class/FullcalendarAboutPage.class.php\n");
	$failed = true;
}

if ($failed) {
	exit(1);
}

echo "[OK] FullCalendar no longer has active ABRICOT dependencies\n";
exit(0);
```

- [ ] **Step 2: Run the test to verify it fails**

Run:

```bash
php script/test_remove_abricot_dependency.php
```

Expected:

```text
[FAIL] config.default.php must not require ABRICOT in config.default.php
[FAIL] config.default.php must not block startup when ABRICOT is absent in config.default.php
[FAIL] create-maj-base.php must not mention ABRICOT TPDOdb in script/create-maj-base.php
[FAIL] create-maj-base.php must not keep ABRICOT sample object code in script/create-maj-base.php
[FAIL] module descriptor must not require TechATM in core/modules/modfullcalendar.class.php
[FAIL] module descriptor must not call TechATM in core/modules/modfullcalendar.class.php
[FAIL] about page must not require TechATM in admin/fullcalendar_about.php
[FAIL] about page must not instantiate TechATM in admin/fullcalendar_about.php
[FAIL] Missing class/FullcalendarAboutPage.class.php
```

The exact order can differ, but the command must exit with status `1`.

- [ ] **Step 3: Commit**

```bash
git add script/test_remove_abricot_dependency.php
git commit -m "test: cover removal of abricot dependency"
```

## Task 2: Replace Bootstrap With Dolibarr Native Loading

**Files:**
- Modify: `config.default.php`
- Modify: `config.php`
- Test: `script/test_remove_abricot_dependency.php`

- [ ] **Step 1: Replace `config.default.php` content**

Replace `config.default.php` with this exact content:

```php
<?php
/* Copyright (C) 2025 ATM Consulting
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

if (is_file('../main.inc.php')) {
	$dir = '../';
} elseif (is_file('../../../main.inc.php')) {
	$dir = '../../../';
} else {
	$dir = '../../';
}

if (!defined('INC_FROM_DOLIBARR') && defined('INC_FROM_CRON_SCRIPT')) {
	require_once $dir.'master.inc.php';
} elseif (!defined('INC_FROM_DOLIBARR')) {
	require_once $dir.'main.inc.php';
}
```

- [ ] **Step 2: Replace `config.php` content**

Replace `config.php` with this exact content:

```php
<?php
/* Copyright (C) 2025 ATM Consulting
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

require_once __DIR__.'/config.default.php';
```

- [ ] **Step 3: Run syntax checks**

Run:

```bash
php -l config.default.php
php -l config.php
```

Expected:

```text
No syntax errors detected in config.default.php
No syntax errors detected in config.php
```

- [ ] **Step 4: Run dependency regression test**

Run:

```bash
php script/test_remove_abricot_dependency.php
```

Expected: still fails because later tasks have not removed `TPDOdb`, `TechATM`, and the new about class is not present.

- [ ] **Step 5: Commit**

```bash
git add config.default.php config.php
git commit -m "refactor: use native dolibarr bootstrap"
```

## Task 3: Remove Legacy ABRICOT Migration Code

**Files:**
- Modify: `script/create-maj-base.php`
- Test: `script/test_remove_abricot_dependency.php`

- [ ] **Step 1: Replace migration script with native no-op**

Replace `script/create-maj-base.php` with this exact content:

```php
<?php
/* Copyright (C) 2026
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 */

if (!defined('INC_FROM_DOLIBARR')) {
	define('INC_FROM_CRON_SCRIPT', true);
	require_once __DIR__.'/../config.php';
}

return 1;
```

- [ ] **Step 2: Run syntax check**

Run:

```bash
php -l script/create-maj-base.php
```

Expected:

```text
No syntax errors detected in script/create-maj-base.php
```

- [ ] **Step 3: Run dependency regression test**

Run:

```bash
php script/test_remove_abricot_dependency.php
```

Expected: still fails because `TechATM` active references remain and `class/FullcalendarAboutPage.class.php` is not present.

- [ ] **Step 4: Commit**

```bash
git add script/create-maj-base.php
git commit -m "refactor: remove abricot migration skeleton"
```

## Task 4: Remove `TechATM` From Module Descriptor

**Files:**
- Modify: `core/modules/modfullcalendar.class.php`
- Test: `script/test_remove_abricot_dependency.php`

- [ ] **Step 1: Remove active `TechATM` version lookup**

In `core/modules/modfullcalendar.class.php`, replace this block:

```php
		$this->version = '2.8';
		// Url to the file with your last numberversion of this module
		require_once __DIR__ . '/../../class/techatm.class.php';
		$this->url_last_version = \fullcalendar\TechATM::getLastModuleVersionUrl($this);
```

with:

```php
		$this->version = '2.8';
		$this->url_last_version = '';
```

- [ ] **Step 2: Make SQL loading conditional during activation**

In `core/modules/modfullcalendar.class.php`, replace this block inside `init()`:

```php
		dol_include_once('/fullcalendar/config.php');
		dol_include_once('/fullcalendar/script/create-maj-base.php');

		$result=$this->_load_tables('/fullcalendar/sql/');

		return $this->_init($sql, $options);
```

with:

```php
		dol_include_once('/fullcalendar/config.php');

		if (is_dir(dol_buildpath('/fullcalendar/sql/', 0))) {
			$result = $this->_load_tables('/fullcalendar/sql/');
			if ($result < 0) {
				return -1;
			}
		}

		return $this->_init($sql, $options);
```

- [ ] **Step 3: Run syntax check**

Run:

```bash
php -l core/modules/modfullcalendar.class.php
```

Expected:

```text
No syntax errors detected in core/modules/modfullcalendar.class.php
```

- [ ] **Step 4: Run dependency regression test**

Run:

```bash
php script/test_remove_abricot_dependency.php
```

Expected: still fails because `admin/fullcalendar_about.php` still uses `TechATM` and `class/FullcalendarAboutPage.class.php` is not present.

- [ ] **Step 5: Commit**

```bash
git add core/modules/modfullcalendar.class.php
git commit -m "refactor: remove remote atm version lookup"
```

## Task 5: Add Local About Page Renderer

**Files:**
- Create: `class/FullcalendarAboutPage.class.php`
- Modify: `admin/fullcalendar_about.php`
- Modify: `langs/fr_FR/fullcalendar.lang`
- Modify: `langs/en_US/fullcalendar.lang`
- Test: `script/test_remove_abricot_dependency.php`

- [ ] **Step 1: Create local about page renderer**

Create `class/FullcalendarAboutPage.class.php` with this exact content:

```php
<?php
/* Copyright (C) 2026
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 */

namespace fullcalendar;

/**
 * Render the module about page without external network calls.
 */
class FullcalendarAboutPage
{
	const MODULE_NAME = 'fullcalendar';
	const MODULE_PICTO = 'fullcalendar@fullcalendar';
	const DOCUMENTATION_URL = '';
	const SUPPORT_URL = '';

	/**
	 * Render the about page.
	 *
	 * @param \DolibarrModules $moduleDescriptor Module descriptor
	 * @return string
	 */
	public function render($moduleDescriptor)
	{
		global $langs;

		$moduleName = !empty($moduleDescriptor->name) ? $moduleDescriptor->name : self::MODULE_NAME;
		$version = !empty($moduleDescriptor->version) ? $moduleDescriptor->version : '';
		$dolibarrMin = '';
		if (!empty($moduleDescriptor->need_dolibarr_version) && is_array($moduleDescriptor->need_dolibarr_version)) {
			$dolibarrMin = implode('.', $moduleDescriptor->need_dolibarr_version);
		}

		$content = '<div class="fichecenter">';
		$content .= '<div class="underbanner clearboth"></div>';
		$content .= '<table class="border centpercent tableforfield">';
		$content .= '<tr><td class="titlefield">'.$langs->trans('Module').'</td><td>'.dol_escape_htmltag($moduleName).'</td></tr>';
		$content .= '<tr><td>'.$langs->trans('Version').'</td><td>'.dol_escape_htmltag($version).'</td></tr>';
		if ($dolibarrMin !== '') {
			$content .= '<tr><td>'.$langs->trans('FullcalendarAboutDolibarrMinVersion').'</td><td>'.dol_escape_htmltag($dolibarrMin).'</td></tr>';
		}
		$content .= '<tr><td>'.$langs->trans('License').'</td><td>'.$langs->trans('FullcalendarAboutLicense').'</td></tr>';
		$content .= '<tr><td>'.$langs->trans('FullcalendarAboutDependencies').'</td><td>'.$langs->trans('FullcalendarAboutNoExternalDependency').'</td></tr>';
		if (self::DOCUMENTATION_URL !== '') {
			$content .= '<tr><td>'.$langs->trans('Documentation').'</td><td><a href="'.dol_escape_htmltag(self::DOCUMENTATION_URL).'" target="_blank" rel="noopener noreferrer">'.dol_escape_htmltag(self::DOCUMENTATION_URL).'</a></td></tr>';
		}
		if (self::SUPPORT_URL !== '') {
			$content .= '<tr><td>'.$langs->trans('Support').'</td><td><a href="'.dol_escape_htmltag(self::SUPPORT_URL).'" target="_blank" rel="noopener noreferrer">'.dol_escape_htmltag(self::SUPPORT_URL).'</a></td></tr>';
		}
		$content .= '</table>';
		$content .= '</div>';

		return $content;
	}
}
```

- [ ] **Step 2: Replace `TechATM` usage in `admin/fullcalendar_about.php`**

In `admin/fullcalendar_about.php`, replace this block:

```php
// About page goes here
require_once __DIR__ . '/../class/techatm.class.php';
$techATM = new \fullcalendar\TechATM($db);

require_once __DIR__ . '/../core/modules/modfullcalendar.class.php';
$moduleDescriptor = new modfullcalendar($db);

print $techATM->getAboutPage($moduleDescriptor);
```

with:

```php
require_once __DIR__ . '/../class/FullcalendarAboutPage.class.php';
require_once __DIR__ . '/../core/modules/modfullcalendar.class.php';

$moduleDescriptor = new modfullcalendar($db);
$aboutPage = new \fullcalendar\FullcalendarAboutPage();

print $aboutPage->render($moduleDescriptor);
```

- [ ] **Step 3: Add French translations**

In `langs/fr_FR/fullcalendar.lang`, delete the `ATMAbout = ...` line and add these lines after `fullcalendarAbout = A propos du module fullcalendar`:

```text
FullcalendarAboutDolibarrMinVersion = Version minimale de Dolibarr
FullcalendarAboutDependencies = Dépendances externes
FullcalendarAboutLicense = GNU General Public License version 3 ou ultérieure
FullcalendarAboutNoExternalDependency = Aucune dépendance au framework ABRICOT n'est requise.
```

- [ ] **Step 4: Add English translations**

In `langs/en_US/fullcalendar.lang`, delete the `ATMAbout = ...` line and add these lines after `fullcalendarAbout = About fullcalendar`:

```text
FullcalendarAboutDolibarrMinVersion = Minimum Dolibarr version
FullcalendarAboutDependencies = External dependencies
FullcalendarAboutLicense = GNU General Public License version 3 or later
FullcalendarAboutNoExternalDependency = No ABRICOT framework dependency is required.
```

- [ ] **Step 5: Run syntax checks**

Run:

```bash
php -l class/FullcalendarAboutPage.class.php
php -l admin/fullcalendar_about.php
```

Expected:

```text
No syntax errors detected in class/FullcalendarAboutPage.class.php
No syntax errors detected in admin/fullcalendar_about.php
```

- [ ] **Step 6: Run dependency regression test**

Run:

```bash
php script/test_remove_abricot_dependency.php
```

Expected:

```text
[OK] FullCalendar no longer has active ABRICOT dependencies
```

- [ ] **Step 7: Commit**

```bash
git add class/FullcalendarAboutPage.class.php admin/fullcalendar_about.php langs/fr_FR/fullcalendar.lang langs/en_US/fullcalendar.lang
git commit -m "refactor: render about page locally"
```

## Task 6: Update Documentation

**Files:**
- Modify: `README`
- Modify: `ChangeLog.md`

- [ ] **Step 1: Update README**

Append this section to `README`:

```text

Installation
============

This module runs on a standard Dolibarr installation and does not require the ABRICOT framework.
Enable it from the Dolibarr module administration page, then configure it from the FullCalendar setup pages.
```

- [ ] **Step 2: Update changelog**

Add this entry at the top of `ChangeLog.md`, below the title if there is one:

```markdown
- CHANGE: Remove mandatory ABRICOT framework dependency from module bootstrap, activation, and about page. - *21/05/2026*
```

- [ ] **Step 3: Run documentation grep**

Run:

```bash
rg -n "ABRICOT|Abricot|abricot|ATMAbout|TechATM|TPDOdb|inc.core" README ChangeLog.md config.default.php script/create-maj-base.php core/modules/modfullcalendar.class.php admin/fullcalendar_about.php langs/fr_FR/fullcalendar.lang langs/en_US/fullcalendar.lang
```

Expected:

```text
The output contains exactly two matches:
one README line containing "This module runs on a standard Dolibarr installation and does not require the ABRICOT framework."
one ChangeLog.md line containing "Remove mandatory ABRICOT framework dependency from module bootstrap, activation, and about page."
```

No active PHP file or language file should appear in this output.

- [ ] **Step 4: Commit**

```bash
git add README ChangeLog.md
git commit -m "docs: document standalone fullcalendar module"
```

## Task 7: Final Verification

**Files:**
- Verify all changed files.

- [ ] **Step 1: Run PHP syntax checks**

Run:

```bash
php -l config.default.php
php -l config.php
php -l script/create-maj-base.php
php -l core/modules/modfullcalendar.class.php
php -l class/FullcalendarAboutPage.class.php
php -l admin/fullcalendar_about.php
php -l script/test_remove_abricot_dependency.php
```

Expected:

```text
No syntax errors detected in config.default.php
No syntax errors detected in config.php
No syntax errors detected in script/create-maj-base.php
No syntax errors detected in core/modules/modfullcalendar.class.php
No syntax errors detected in class/FullcalendarAboutPage.class.php
No syntax errors detected in admin/fullcalendar_about.php
No syntax errors detected in script/test_remove_abricot_dependency.php
```

- [ ] **Step 2: Run static dependency regression**

Run:

```bash
php script/test_remove_abricot_dependency.php
```

Expected:

```text
[OK] FullCalendar no longer has active ABRICOT dependencies
```

- [ ] **Step 3: Run broad active-reference scan**

Run:

```bash
rg -n "dol_include_once\\('/abricot/inc.core.php'|TPDOdb|TObjetStd|TFormCore|TListview|ATMdb|new \\\\fullcalendar\\\\TechATM|TechATM::getLastModuleVersionUrl|ATMAbout" --glob '!lib/fullcalendar/dist/**' --glob '!lib/moment/**' .
```

Expected: no output.

- [ ] **Step 4: Inspect final diff**

Run:

```bash
git diff --stat
git diff -- config.default.php config.php script/create-maj-base.php core/modules/modfullcalendar.class.php admin/fullcalendar_about.php class/FullcalendarAboutPage.class.php langs/fr_FR/fullcalendar.lang langs/en_US/fullcalendar.lang README ChangeLog.md script/test_remove_abricot_dependency.php
```

Expected:

```text
config.default.php removes ABRICOT include and DB_* constants.
config.php uses require_once.
script/create-maj-base.php is a no-op native script.
modfullcalendar.class.php no longer requires or calls TechATM.
admin/fullcalendar_about.php uses FullcalendarAboutPage.
FullcalendarAboutPage.class.php exists and has no network calls.
Language files use FullcalendarAbout* keys instead of ATMAbout.
README and ChangeLog mention the standalone behavior.
```

- [ ] **Step 5: Manual Dolibarr smoke test**

In a Dolibarr instance where ABRICOT is disabled or absent:

```text
1. Open Home > Setup > Modules/Applications.
2. Enable the FullCalendar module.
3. Open /custom/fullcalendar/admin/fullcalendar_setup.php.
4. Open /custom/fullcalendar/admin/fullcalendar_about.php.
5. Open /comm/action/index.php and confirm the calendar assets still load.
6. If FULLCALENDAR_ENABLE_TASKS is enabled, open /custom/fullcalendar/task.php.
```

Expected:

```text
The module enables without an ABRICOT error.
The setup page opens.
The about page opens without remote ATM content.
The agenda calendar still loads.
The task calendar opens when the feature and rights are enabled.
```

- [ ] **Step 6: Commit verification adjustments if any**

If Task 7 required a small correction, commit only that correction:

```bash
git add config.default.php config.php script/create-maj-base.php core/modules/modfullcalendar.class.php class/FullcalendarAboutPage.class.php admin/fullcalendar_about.php script/test_remove_abricot_dependency.php langs/fr_FR/fullcalendar.lang langs/en_US/fullcalendar.lang README ChangeLog.md
git commit -m "fix: complete abricot removal verification"
```

If no correction was required, do not create an empty commit.

## Self-Review

- Spec coverage: The plan covers bootstrap removal, activation cleanup, legacy migration cleanup, about page autonomy, translations, documentation, static checks, syntax checks, and manual Dolibarr smoke tests.
- Placeholder scan: The plan contains no `TBD`, no `TODO`, no "implement later", and each code-changing step includes exact code or exact replacement blocks.
- Type consistency: `FullcalendarAboutPage::render($moduleDescriptor)` is created in Task 5 and used with the same namespace and method signature from `admin/fullcalendar_about.php`.
