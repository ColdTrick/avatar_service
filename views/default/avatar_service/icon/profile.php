<?php

$user = elgg_extract('user', $vars);
if (!($user instanceof ElggUser)) {
	return;
}

$icontime = $user->icontime;
if (empty($icontime)) {
	return;
}

$user_guid = $user->getGUID();

$filehandler = new ElggFile();
$filehandler->owner_guid = $user_guid;
$filehandler->setFilename("profile/{$user_guid}master.jpg");

if ($filehandler->exists()) {
	$image_data = $filehandler->grabFile();
}

if (empty($image_data)) {
	return;
}

$x1 = $user->x1;
$x2 = $user->x2;
$y1 = $user->y1;
$y2 = $user->y2;

if ($x1 === null) {
	return $image_data;
}

// apply user cropping config

// create temp file for resizing
$tmpfname = tempnam(elgg_get_data_path(), 'elgg_avatar_service');

$handle = fopen($tmpfname, 'w');
fwrite($handle, $image_data);
fclose($handle);

// apply resizing
$result = get_resized_image_from_existing_file($tmpfname, 2048, 2048, true, $x1, $y1, $x2, $y2, false);

// remove temp file
unlink($tmpfname);

echo $result;
