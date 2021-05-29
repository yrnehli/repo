<?php

$packages = array_filter(
	explode("\n\n", file_get_contents("Packages")),
	function($package) {
		return (trim($package) !== "");
	}
);

foreach ($packages as &$package) {
	preg_match("/Package: (.*)/", $package, $matches);
	$identifier = $matches[1];

	if (!str_contains($package, "Depiction: ")) {
		$package .= "\nDepiction: https://henryli17.github.io/repo/depictions/?p=$identifier";
	}

	if (!str_contains($package, "SileoDepiction: ")) {
		$package .= "\nSileoDepiction: https://henryli17.github.io/repo/depictions/$identifier/sileo.json";
	}
}

file_put_contents("Packages", implode("\n\n", $packages));

function str_contains($haystack, $needle) {
	return (strpos($haystack, $needle) !== false);
}

?>