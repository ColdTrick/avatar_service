<?php

namespace ColdTrick\AvatarService;

class Access {
	
	/**
	 * Extends the walled garden public pages with the avatar_service page handler
	 *
	 * @param string $hook        hook name
	 * @param string $type        hook type
	 * @param array  $returnvalue current return value
	 * @param array  $params      parameters
	 *
	 * @return array
	 */
	public static function publicPages($hook, $type, $returnvalue, $params) {
		
		$returnvalue[] = 'avatar_service/.*';
		
		return $returnvalue;
	}
}
