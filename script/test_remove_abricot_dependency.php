<?php
/* Copyright (C) 2026
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 */

if (PHP_SAPI !== 'cli') {
	http_response_code(403);
	exit;
}

$moduleRoot = realpath(__DIR__.'/..');
if ($moduleRoot === false) {
	fwrite(STDERR, "[FAIL] Unable to resolve module root\n");
	exit(1);
}

$checks = array(
	'config.default.php' => array(
		"'/abricot/inc.core.php'" => 'config.default.php must not require ABRICOT',
		"'AbricotNotFound'" => 'config.default.php must not block startup when ABRICOT is absent',
		"'DB_HOST'" => 'config.default.php must not define ABRICOT-style DB constants',
		"'DB_NAME'" => 'config.default.php must not define ABRICOT-style DB constants',
		"'DB_USER'" => 'config.default.php must not define ABRICOT-style DB constants',
		"'DB_PASS'" => 'config.default.php must not define ABRICOT-style DB constants',
		"'DB_DRIVER'" => 'config.default.php must not define ABRICOT-style DB constants',
	),
	'script/create-maj-base.php' => array(
		"'TPDOdb'" => 'create-maj-base.php must not mention ABRICOT TPDOdb',
		"'TXXX'" => 'create-maj-base.php must not keep ABRICOT sample object code',
	),
);

$failed = false;
foreach ($checks as $relativePath => $needles) {
	$path = $moduleRoot.'/'.$relativePath;
	if (!is_readable($path)) {
		fwrite(STDERR, '[FAIL] Missing readable file: '.$relativePath."\n");
		$failed = true;
		continue;
	}

	$content = file_get_contents($path);
	if ($content === false) {
		fwrite(STDERR, '[FAIL] Unable to read file: '.$relativePath."\n");
		$failed = true;
		continue;
	}

	foreach ($needles as $needle => $message) {
		$literalNeedle = trim($needle, "'");
		if (strpos($content, $literalNeedle) !== false) {
			fwrite(STDERR, '[FAIL] '.$message.' in '.$relativePath."\n");
			$failed = true;
		}
	}
}

if ($failed) {
	exit(1);
}

echo "[OK] FullCalendar no longer has active ABRICOT dependencies\n";
exit(0);
