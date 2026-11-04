<?php
// Students: replace "Matt" below with your own name.
// You only need to change it here, and the title/header will update automatically.
$siteOwner = "Krish"; // <----

// array used to build Table of Contents to the folders used for this course
// most won't lead anywhere eventful until the work is implemented
$pages = array(
	"m01" => "Module 01",
	"m02" => "Module 02",
	"m03" => "Module 03",
	"m04" => "Module 04",
	"m05" => "Module 05",
	"m06" => "Module 06",
	"m07" => "Module 07",
	"m08" => "Module 08",
	"m09" => "Module 09",
	"m10" => "Module 10",
	"project" => "Project",
);
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<?php /* "<?= ... ?>" is short for "echo this value here" inside HTML.*/ ?>
	<?php /* htmlspecialchars(...) safely shows text so special characters do not break the page. */ ?>
	<title><?= htmlspecialchars($siteOwner) ?>'s IT202 Site</title>
</head>
<body>
	<h1><?= htmlspecialchars($siteOwner) ?>'s IT202 Site</h1>
	<p>Site table of contents:</p>

	<ul>
		<?php foreach ($pages as $path => $label) : ?>
			<li><a href="<?= htmlspecialchars($path) ?>/"><?= htmlspecialchars($label) ?></a></li>
		<?php endforeach; ?>
	</ul>
</body>
</html>
