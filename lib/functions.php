<?php
/**
 * All helper functions are bundled here
 */

/**
 * Returns a user based on the md5 hashed email address
 *
 * @param string $md5
 *
 * @return false|\ElggUser
 */
function avatar_service_get_user_by_md5($md5) {
	
	if (empty($md5)) {
		return false;
	}
	
	$dbprefix = elgg_get_config('dbprefix');
	
	$query = "SELECT ue.guid
		FROM {$dbprefix}users_entity ue
		WHERE md5(lower(ue.email)) = '{$md5}'";
	
	$result = get_data($query);
	if (empty($result)) {
		return false;
	}
	
	$guid = $result[0]->guid;
	
	return get_user($guid);
}

/**
 * Returns the image for the given set of parameters
 *
 * @param array $params parameters for fetching the image
 *   - user => the ElggUser to fetch the image for (required)
 *   - size => int the size of the image requested (required)
 *
 * @return false|string
 */
function avatar_service_get_image($params) {
	
	$user = elgg_extract('user', $params);
	if (!($user instanceof ElggUser)) {
		return false;
	}
	
	$size = (int) elgg_extract('size', $params);
	if ($size < 1) {
		return false;
	}
	
	$image_data = '';
	
	// retrieve image data
	// views need to profile the largest quality square image
	if ($user) {
		$image_data = elgg_view('avatar_service/icon/profile', $params);
	}
	
	if (empty($image_data)) {
		$image_data = elgg_view('avatar_service/icon/default', $params);
	}
	
	if (empty($image_data)) {
		return false;
	}
	
	// create temp file for resizing
	$temp_path = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
	$tmpfname = tempnam($temp_path, 'avatar_service');
	file_put_contents($tmpfname, $image_data);
	
	$params = [
		'w' => $size,
		'h' => $size,
		'square' => true,
		'upscale' => true,
	];
	
	try {
		// apply resizing
		elgg_save_resized_image($tmpfname, null, $params);
	} catch (Exception $e) {
		// just in case
		elgg_log("Avatar service: error while resizing for {$user->getDisplayName()} in size {$size} => {$e->getMessage()}", 'ERROR');
		
		// remove temp file
		unlink($tmpfname);
		
		return false;
	}
	
	// get resized image
	$result = file_get_contents($tmpfname);
	
	// remove temp file
	unlink($tmpfname);
	
	return $result;
}
