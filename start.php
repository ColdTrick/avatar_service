<?php
/**
 * Main file for the Avatar Service plugin
 */

require_once(dirname(__FILE__) . '/lib/functions.php');

// register default Elgg event
elgg_register_event_handler('init', 'system', 'avatar_service_init');

/**
 * Initializes the plugin
 *
 * @return void
 */
function avatar_service_init() {
	
	// page handler for search actions and results
	elgg_register_page_handler('avatar_service', '\ColdTrick\AvatarService\PageHandler::avatarService');
	
	// walled garden hook
	elgg_register_plugin_hook_handler('public_pages', 'walled_garden', '\ColdTrick\AvatarService\Access::publicPages');
}
