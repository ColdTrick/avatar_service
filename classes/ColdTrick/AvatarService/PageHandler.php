<?php

namespace ColdTrick\AvatarService;

class PageHandler {
	
	/**
	 * Page handler for the /avatar_service url
	 *
	 * @param array $page url parts
	 *
	 * @return false|void
	 */
	public static function avatarService($page) {
		
		$md5_parts = elgg_extract(0, $page);
		if (empty($md5_parts)) {
			// invalid input
			return false;
		}
		
		// strip optional extension
		list($md5) = explode('.', $md5_parts);
		
		// get image size
		$size = (int) get_input('s', get_input('size', 80)); // size (in pixels) min 1px and max 2048px
		if ($size < 1 || $size > 2048) {
			$size = 80;
		}
		
		// get image
		$params = [
			'size' => $size,
			'user' => avatar_service_get_user_by_md5($md5)
		];
		
		$image_data = avatar_service_get_image($params);
		if (empty($image_data)) {
			// no image available
			return false;
		}
		
		// generate ETag
		$etag = md5($image_data);
		
		// generic headers
		header('Expires: ' . gmdate('D, d M Y H:i:s \G\M\T', strtotime('+6 months')), true);
		header('Pragma: public');
		header('Cache-Control: public');
		header("ETag: \"$etag\"");
		
		// If is the same ETag, content didn't changed.
		if (isset($_SERVER['HTTP_IF_NONE_MATCH']) && trim($_SERVER['HTTP_IF_NONE_MATCH']) == "\"$etag\"") {
			header("HTTP/1.1 304 Not Modified");
			exit();
		}
		
		// return image
		$content_length = strlen($image_data);
		
		// set correct headers
		header('Content-type: image/jpeg');
		header("Content-Length: {$content_length}");
		
		echo $image_data;
		
		exit();
	}
}
