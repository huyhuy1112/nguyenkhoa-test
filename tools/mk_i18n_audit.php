<?php
/**
 * Audit missing vi_vn translation keys used in layouts/v7.
 * Run: php tools/mk_i18n_audit.php
 */

function loadStrings(string $file): array {
	if (!file_exists($file)) {
		return [];
	}
	$languageStrings = [];
	$jsLanguageStrings = [];
	$customStrings = [];
	include $file;
	$out = isset($languageStrings) && is_array($languageStrings) ? $languageStrings : [];
	if (isset($customStrings) && is_array($customStrings)) {
		$out = array_merge($out, $customStrings);
	}
	return $out;
}

function moduleStrings(string $lang, string $module): array {
	$module = str_replace(':', '.', $module);
	$base = loadStrings("languages/$lang/$module.php");
	$custom = loadStrings("languages/custom/$lang/$module.php");
	$vtiger = loadStrings("languages/$lang/Vtiger.php");
	$vtCustom = loadStrings("languages/custom/$lang/Vtiger.php");
	return array_merge($vtiger, $vtCustom, $base, $custom);
}

function hasTranslation(string $lang, string $module, string $key): bool {
	$strings = moduleStrings($lang, $module);
	return isset($strings[$key]) && trim((string) $strings[$key]) !== '' && $strings[$key] !== $key;
}

$keys = [];
$it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator('layouts/v7'));
foreach ($it as $f) {
	$path = (string) $f;
	if (!preg_match('/\.(tpl|js)$/', $path)) {
		continue;
	}
	$c = file_get_contents($path);
	if (preg_match_all("/vtranslate\(\s*['\"](LBL_[A-Z0-9_]+)['\"]\s*,\s*['\"]([^)\"']+)['\"]/", $c, $m, PREG_SET_ORDER)) {
		foreach ($m as $x) {
			$keys[$x[2]][$x[1]] = true;
		}
	}
	if (preg_match_all("/app\.vtranslate\(\s*['\"](LBL_[A-Z0-9_]+)['\"](?:\s*,\s*['\"]([^'\"]+)['\"])?/", $c, $m2, PREG_SET_ORDER)) {
		foreach ($m2 as $x) {
			$mod = !empty($x[2]) ? $x[2] : 'Vtiger';
			$keys[$mod][$x[1]] = true;
		}
	}
}

$missingUi = [];
foreach ($keys as $mod => $lbls) {
	$modClean = preg_replace('/^Settings:/', 'Settings.', $mod);
	foreach (array_keys($lbls) as $k) {
		if (!hasTranslation('vi_vn', $modClean, $k)) {
			$missingUi[$modClean][$k] = true;
		}
	}
}

$total = array_sum(array_map('count', $missingUi));
echo "Missing in layouts/v7 UI: $total keys\n";
foreach ($missingUi as $mod => $lbls) {
	echo "\n$mod (" . count($lbls) . "):\n";
	foreach (array_keys($lbls) as $k) {
		echo "  $k\n";
	}
}
