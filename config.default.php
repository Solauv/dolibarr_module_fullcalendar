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

$dir = '';
$candidateDirs = array(
	__DIR__.'/../',
	__DIR__.'/../../',
	__DIR__.'/../../../',
);

foreach ($candidateDirs as $candidateDir) {
	if (is_file($candidateDir.'main.inc.php')) {
		$dir = $candidateDir;
		break;
	}
}

if ($dir === '') {
	$dir = __DIR__.'/../../';
}


if (!defined('INC_FROM_DOLIBARR') && defined('INC_FROM_CRON_SCRIPT')) {
	require_once $dir.'master.inc.php';
} elseif (!defined('INC_FROM_DOLIBARR')) {
	require_once $dir.'main.inc.php';
}
