<?php

unlink("Packages.bz2");
exec("dpkg-scanpackages -m ./assets/deb > Packages");
$packages = getPackages(file_get_contents("Packages"));
generateDepictions($packages);
addDepictions($packages);
file_put_contents("Packages", implode("\n\n", $packages));
exec("bzip2 Packages");
die("Done\n");

function getPackages($packagesText) {
	foreach (explode("\n\n", $packagesText) as $package) {
		if (trim($package) === "") {
			continue;
		}
	
		preg_match("/Package: (.*)/", $package, $matches);
		$identifier = $matches[1];
	
		$packages[$identifier] = $package;
	}

	return $packages;
}

function generateDepictions($packages) {
	foreach ($packages as $identifier => $package) {
		if (!file_exists("depictions/$identifier/changelog.json")) {
			die("Missing changelog: $identifier\n");
		}
		
		preg_match("/Name: (.*)/", $package, $nameMatches);
		preg_match("/Description: (.*)/", $package, $descriptionMatches);

		$depiction = [
			'name' => $nameMatches[1],
			'description' => $descriptionMatches[1],
			'screenshotUrl' => (file_exists("depictions/$identifier/screenshot.png")) ? "https://henryli17.github.io/depictions/$identifier/screenshot.png" : "",
			'changelog' => json_decode(file_get_contents("depictions/$identifier/changelog.json"))
		];

		$htmlDepiction = generateHtmlDepiction($depiction);
		$sileoDepiction = generateSileoDepiction($depiction);

		file_put_contents("depictions/$identifier/depiction.html", $htmlDepiction);
		file_put_contents("depictions/$identifier/sileo.json", $sileoDepiction);
	}
}

function generateHtmlDepiction($depiction) {
	$changelogHtml = "";

	foreach ($depiction['changelog'] as $version => $changes) {
		$changesHtml = "";

		foreach ($changes as $change) {
			$changesHtml .= "<p>&#8226; $change</p>";
		}

		$changelogHtml .= "
			<li class='list-group-item'>
				<p>
					<b>$version</b>
				</p>
				$changesHtml
			</li>
		";
	}

	return str_replace(
		["***NAME***", "***DESCRIPTION***", "***SCREENSHOTURL***", "***CHANGELOGHTML***"],
		[$depiction['name'], $depiction['description'], $depiction['screenshotUrl'], $changelogHtml],
		file_get_contents("assets/html/depictionTemplate.html")
	);
}

function generateSileoDepiction($depiction) {
	$changelogViews = [];

	foreach ($depiction['changelog'] as $version => $changes) {
		$changelogViews[] = [
			"title" => $version,
			"useBoldText" => true,
			"useBottomMargin" => true,
			"class" => "DepictionSubheaderView"
		];

		foreach ($changes as $i => $change) {
			$changelogViews[] = [
				"markdown" => "\t\n\u2022 $change",
				"useSpacing" => false,
				"class" => "DepictionMarkdownView"
			];

			if ($i < count($changes) - 1) {
				$changelogViews[] = [
					"spacing" => 16,
					"class" => "DepictionSpacerView"
				];
			}
		}
	}

	return str_replace(
		["***NAME***", "***DESCRIPTION***", "***SCREENSHOTURL***", "***CHANGELOGVIEWSJSON***"],
		[$depiction['name'], $depiction['description'], $depiction['screenshotUrl'], json_encode($changelogViews, JSON_PRETTY_PRINT)],
		file_get_contents("assets/json/sileoDepictionTemplate.json")
	);
}

function addDepictions($packages) {	
	foreach ($packages as $identifier => &$package) {
		if (!str_contains($package, "Depiction: ")) {
			$package .= "\nDepiction: https://henryli17.github.io/repo/depictions/$identifier/depiction.html";
		}
	
		if (!str_contains($package, "SileoDepiction: ")) {
			$package .= "\nSileoDepiction: https://henryli17.github.io/repo/depictions/$identifier/sileo.json";
		}
	}
	
	return $packages;
}

function str_contains($haystack, $needle) {
	return (strpos($haystack, $needle) !== false);
}	

?>