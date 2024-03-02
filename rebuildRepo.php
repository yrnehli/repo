<?php

define("REPO_BASE_URL", "https://yrnehli.github.io/repo");
unlink("Packages.bz2");
exec("dpkg-scanpackages -m ./assets/deb > Packages");
$packages = getPackages(file_get_contents("Packages"));
generateDepictions($packages);
addDepictions($packages);
file_put_contents("Packages", implode("\n\n", $packages));
exec("bzip2 Packages");
die("Done\n");

function getPackages($packagesText) {
	$packages = [];

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
			'screenshotUrl' => (file_exists("depictions/$identifier/screenshot.png")) ? REPO_BASE_URL . "/depictions/$identifier/screenshot.png" : "",
			'screenshotPageUrl' => (file_exists("depictions/$identifier/screenshot.png")) ? REPO_BASE_URL . "/depictions/$identifier/screenshot.html" : "",
			'changelog' => json_decode(file_get_contents("depictions/$identifier/changelog.json"))
		];

		file_put_contents("depictions/$identifier/index.html", generateHtmlDepiction($depiction));
		file_put_contents("depictions/$identifier/sileo.json", generateSileoDepiction($depiction));

		if ($depiction['screenshotPageUrl'] !== "") {
			file_put_contents("depictions/$identifier/screenshot.html", generateScreenshotPage($depiction['screenshotUrl']));
		}
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
		["***NAME***", "***DESCRIPTION***", "***SCREENSHOT_PAGE_URL***", "***CHANGELOG_HTML***"],
		[$depiction['name'], $depiction['description'], $depiction['screenshotPageUrl'], $changelogHtml],
		file_get_contents("assets/template/depictionTemplate.html")
	);
}

function generateSileoDepiction($depiction) {
	$screenshotSize = ($depiction['screenshotUrl'] !== "") ? "{160, 275.41333333333336}" : "{0, 0}";
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
				"markdown" => "\t\n• $change",
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

	$changelogViews[] = [
		"spacing" => 32,
		"class" => "DepictionSpacerView"
	];

	return str_replace(
		["***NAME***", "***DESCRIPTION***", "***SCREENSHOT_URL***", "***SCREENSHOT_SIZE****", "***CHANGELOG_VIEWS_JSON****"],
		[$depiction['name'], $depiction['description'], $depiction['screenshotUrl'], $screenshotSize, json_encode($changelogViews, JSON_PRETTY_PRINT) . ","],
		file_get_contents("assets/template/sileoDepictionTemplate.json")
	);
}

function generateScreenshotPage($screenshotUrl) {
	return str_replace(
		"***SCREENSHOT_URL***",
		$screenshotUrl,
		file_get_contents("assets/template/screenshotTemplate.html")
	);
}

function addDepictions(&$packages) {	
	foreach ($packages as $identifier => &$package) {
		$package = preg_replace_callback("/Depiction: .*/", function() use ($identifier) {
			return "Depiction: " . REPO_BASE_URL . "/depictions/$identifier";
		}, $package);

		$package = preg_replace_callback("/SileoDepiction: .*/", function() use ($identifier) {
			return  "SileoDepiction: " . REPO_BASE_URL . "/depictions/$identifier/sileo.json";
		}, $package);

		if (!str_contains($package, "Depiction: ")) {
			$package .= "\nDepiction: " . REPO_BASE_URL . "/depictions/$identifier";
		}
	
		if (!str_contains($package, "SileoDepiction: ")) {
			$package .= "\nSileoDepiction: " . REPO_BASE_URL . "/depictions/$identifier/sileo.json";
		}
	}
}

?>
