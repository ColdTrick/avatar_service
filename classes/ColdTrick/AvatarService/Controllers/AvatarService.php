<?php

namespace ColdTrick\AvatarService\Controllers;

use Elgg\Database\Clauses\JoinClause;
use Elgg\Database\QueryBuilder;

class AvatarService {
	
	/**
	 * Returns an avatar icon based on an md5
	 *
	 * @param \Elgg\Request $request Request
	 * @return ResponseBuilder
	 *
	 * @throws \Elgg\BadRequestException
	 * @throws \Elgg\EntityNotFoundException
	 * @throws \Elgg\EntityNotFoundException
	 */
	public function __invoke(\Elgg\Request $request) {
		
		$md5_parts = $request->getParam('md5', '');
		list($md5) = explode('.', $md5_parts);
		
		if (empty($md5)) {
			throw new \Elgg\BadRequestException();
		}
		
		$user = $this->getUserByMd5($md5);
		if (!$user instanceof \ElggUser) {
			throw new \Elgg\EntityNotFoundException();
		}
		
		// get image size
		$size = (int) $request->getParam('s', $request->getParam('size', 80)); // size (in pixels) min 1px and max 2048px
		if ($size < 1 || $size > 2048) {
			$size = 80;
		}
						
		$image_data = $this->getIcon($user, $size);
		if (empty($image_data)) {
			// no image available
			throw new \Elgg\HttpException('No image data', ELGG_HTTP_NOT_FOUND);
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
			return elgg_ok_response('', '', null, ELGG_HTTP_NOT_MODIFIED);
		}
		
		// return image
		$content_length = strlen($image_data);
		
		// set correct headers
		header('Content-type: image/jpeg');
		header("Content-Length: {$content_length}");
		
		return elgg_ok_response($image_data);
	}
	
	/**
	 * Returns a user based on the md5 hashed email address
	 *
	 * @param string $md5
	 *
	 * @return false|\ElggUser
	 */
	protected function getUserByMd5($md5) {
		
		if (empty($md5)) {
			return false;
		}
		
		$result = elgg_get_entities([
			'type' => 'user',
			'limit' => 1,
			'joins' => [
				new JoinClause('metadata', 'md', function(QueryBuilder $qb, $joined_alias, $main_alias) {
					return $qb->compare("{$joined_alias}.entity_guid", '=', "$main_alias.guid");
				}),
			],
			'wheres' => [
				function(QueryBuilder $qb, $main_alias) use ($md5) {
					return $qb->merge([
						$qb->compare("md.name", '=', 'email', ELGG_VALUE_STRING),
						$qb->compare("md5(lower(md.value))", '=', $md5, ELGG_VALUE_STRING),
					], 'AND');
 				},
			]
		]);
		
		return $result ? $result[0] : false;
	}
	
	/**
	 * Returns the image for the given user
	 *
	 * @param \ElggUser $user user to get icon for
	 * @param int       $size pixelsize of icon
	 *
	 * @return false|string
	 */
	protected function getIcon(\ElggUser $user, $size = 80) {
		
		if ($size < 1) {
			return false;
		}
		
		$params = [
			'size' => $size,
			'user' => $user,
		];
				
		// retrieve image data
		// views need to profile the largest quality square image
		$image_data = elgg_view('avatar_service/icon/profile', $params);
		if (empty($image_data)) {
			$image_data = elgg_view('avatar_service/icon/default', $params);
		}
		
		if (empty($image_data)) {
			return false;
		}
		
		// create temp file for resizing
		$temp_file = elgg_get_temp_file();
		$temp_file->open('write');
		$temp_file->write($image_data);
		$temp_file->close();
				
		try {
			// apply resizing
			elgg_save_resized_image($temp_file->getFilenameOnFilestore(), null, [
				'w' => $size,
				'h' => $size,
				'square' => true,
				'upscale' => true,
			]);
		} catch (Exception $e) {
			// just in case
			elgg_log("Avatar service: error while resizing for {$user->getDisplayName()} in size {$size} => {$e->getMessage()}", 'ERROR');
			
			$temp_file->delete();
			
			return false;
		}
		
		// get resized image
		$result = $temp_file->grabFile();
		
		// remove temp file
		$temp_file->delete();
		
		return $result;
	}
}
