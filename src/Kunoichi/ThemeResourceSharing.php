<?php

namespace Kunoichi;

/**
 * Register commands.
 *
 * @package Kunoichi
 */
class ThemeResourceSharing {
	
	public static $resource_dir = '';
	
	/**
	 * Constructor prohibit.
	 */
	private function __construct() {}
	
	/**
	 * Enable sharing resource.
	 *
	 * @param string $resource_dir
	 */
	public static function enable( $resource_dir = 'resource' ) {
		if ( defined( 'WP_CLI' ) && WP_CLI ) {
			// This is CLI, so register plugins.
			self::$resource_dir = $resource_dir;
			\WP_CLI::add_command( 'theme-resource', ThemeResourceSharing\Command::class );
		}
		
	}
}
