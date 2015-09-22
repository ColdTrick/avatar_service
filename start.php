<?php
/**
 * Main file for the Avatar Service plugin
 */

// register default Elgg event
elgg_register_event_handler('init','system','avatar_service_init');

/**
 * Initializes the plugin
 *
 * @return void
 */
function avatar_service_init() {
	// page handler for search actions and results
	elgg_register_page_handler('avatar_service', 'avatar_service_page_handler');
}

/**
 * Page handler for the avatar_service url
 *
 * @param array $page url parts
 *
 * @return true
 */
function avatar_service_page_handler($page) {
	$md5_parts = elgg_extract(0, $page);
	$md5 = '';
		
	// strip optional extension
	if (!empty($md5_parts)) {
		list($md5) = explode('.', $md5_parts);
	}
	
	$size = (int) get_input('s', get_input('size', 80)); // size (in pixels) min 1px and max 2048px
	if ($size < 1 || $size > 2048) {
		$size = 80;
	}
	
	$params = [
		'size' => $size,
		'user' => avatar_service_get_user_by_md5($md5)
	];
	
	$image_data = avatar_service_get_image($params);
	$content_length = strlen($image_data);
	
	// If is the same ETag, content didn't changed.
	$etag = md5($image_data);
	if (isset($_SERVER['HTTP_IF_NONE_MATCH']) && trim($_SERVER['HTTP_IF_NONE_MATCH']) == "\"$etag\"") {
		header("HTTP/1.1 304 Not Modified");
		return true;
	}
	
	header('Content-type: image/jpeg');
	header('Expires: ' . gmdate('D, d M Y H:i:s \G\M\T', strtotime('+6 months')), true);
	header('Pragma: public');
	header('Cache-Control: public');
	header("Content-Length: {$content_length}");
	header("ETag: \"$etag\"");
	echo $image_data;
	
	return true;
}

/**
 * Returns a user based on the md5 hashed email address
 *
 * @param string $md5
 *
 * @return \ElggUser, false
 */
function avatar_service_get_user_by_md5($md5) {
	if (empty($md5)) {
		return false;
	}
	
	$dbprefix = elgg_get_config('dbprefix');
	
	$query = "SELECT ue.guid FROM {$dbprefix}users_entity ue WHERE md5(lower(ue.email)) = '{$md5}'";
	
	$result = get_data($query);
	if (!$result) {
		return false;
	}
	
	$guid = $result[0]->guid;
	
	return get_user($guid);
}

/**
 * Returns the image for the given set of parameters
 *
 * @param array $params parameters for fetching the image
 *
 * @return string
 */
function avatar_service_get_image($params) {
	$user = elgg_extract('user', $params);
	$size = elgg_extract('size', $params);
	
	$image_data = '';
	
	// retrieve image data
	// views need to profile the largest quality square image
	if ($user) {
		$image_data = elgg_view('avatar_service/icon/profile', $params);
	}
	
	if (empty($image_data)) {
		$image_data = elgg_view('avatar_service/icon/default', $params);
	}
	
	// create temp file for resizing
	$tmpfname = tempnam(sys_get_temp_dir(), 'elgg_avatar_service');
	
	$handle = fopen($tmpfname, 'w');
	fwrite($handle, $image_data);
	fclose($handle);
		
	// apply resizing
	$result = get_resized_image_from_existing_file($tmpfname, $size, $size, true, 0, 0, 0, 0, true);
	
	// remove temp file
	unlink($tmpfname);
		
	return $result;
}
