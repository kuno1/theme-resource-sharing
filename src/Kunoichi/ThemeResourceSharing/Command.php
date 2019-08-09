<?php

namespace Kunoichi\ThemeResourceSharing;

use Kunoichi\ThemeResourceSharing;
use Symfony\Component\Filesystem\Filesystem;

/**
 * CLI utility for sharing theme resources.
 *
 * @package theme-resource-sharing
 */
class Command extends \WP_CLI_Command {
	
	/**
	 * Export media files
	 *
	 * ## OPTIONS
	 *
	 * : [--db=<db>]
	 *   If set, this will be name of db file. Default wordpress.sql
	 *
	 * : [--keep]
	 *   If set, files which do not exist in
	 *
	 * @synopsis [--db=<db>] [--keep]
	 *
	 * @apram array $args
	 * @param array $assoc
	 */
	public function export( $args, $assoc ) {
		$dir = $this->dir( true );
		$this->handle_wp_error( $dir );
		$synced_dir = $this->directory_list( true );
		$delete = isset( $assoc['keep'] ) && $assoc['keep'] ? false : true;
		// Copy directory all them all.
		$file_system = new Filesystem();
		foreach ( $synced_dir as $orig => $source ) {
			try {
				$file_system->mirror( $orig, $source, null, [
					'delete' => $delete,
				] );
			} catch ( \Exception $e ) {
				\WP_CLI::warning( $e->getMessage() );
			}
		}
		// Dump db file.
		$db_file = $dir . '/' . ( isset( $assoc['db'] ) ? $assoc['db'] : 'wordpress.sql' );
		$cli_command = 'db export ' . $db_file . ' --add-drop-table';
		\WP_CLI::line( 'Run: wp ' . $cli_command );
		\WP_CLI::runcommand( $cli_command );
	}
	
	/**
	 * Import resources to database.
	 *
	 * ## OPTIONS
	 *
	 * : [--site_url=<site_url>]
	 *   If set, site url will be replaced.
	 *
	 * : [--db=<db>]
	 *   If set, this will be name of db file. Default wordpress.sql
	 *
	 * : [--keep]
	 *   If set, files which do not exist in
	 *
	 * @synopsis [--site_url=<site_url>] [--keep] [--db=<db>]
	 *
	 * @apram array $args
	 * @param array $assoc
	 */
	public function import( $args, $assoc ) {
		$dir = $this->dir();
		$this->handle_wp_error( $dir );
		$synced_dir = $this->directory_list();
		$delete = isset( $assoc['keep'] ) && $assoc['keep'] ? false : true;
		$url    = isset( $assoc['site_url'] ) ? $assoc['site_url'] : false;
		// Copy directory all them all.
		$file_system = new Filesystem();
		foreach ( $synced_dir as $orig => $source ) {
			try {
				if ( ! is_dir( $source ) ) {
					throw new \Exception( sprintf( 'Source directory `%s` does not exist.', $source ) );
				}
				$file_system->mirror( $source, $orig, null, [
					'delete' => $delete,
				] );
			} catch ( \Exception $e ) {
				\WP_CLI::warning( $e->getMessage() );
			}
		}
		// Import DB file.
		$db_file = $dir . '/' . ( isset( $assoc['db'] ) ? $assoc['db'] : 'wordpress.sql' );
		if ( ! file_exists( $db_file ) ) {
			\WP_CLI::error( sprintf( 'DB file %s does not exist.', $db_file ) );
		}
		$cli_command = 'db import ' . $db_file;
		\WP_CLI::line( 'Run: wp ' . $cli_command );
		\WP_CLI::runcommand( $cli_command );
		// Check if URL is specified.
		if ( defined( 'WP_SITEURL' ) ) {
			$url = WP_SITEURL;
		}
		if ( $url ) {
			\WP_CLI::line( 'Changing site url.' );
			global $wpdb;
			$saved = $wpdb->get_var( "SELECT option_value FROM {$wpdb->options} WHERE option_name = 'siteurl'" );
			if ( $saved === $url ) {
				\WP_CLI::warning( 'URL is same. No need for replacement.' );
			} else {
				// Replace site URL.
				$cli_command = 'search-replace ' . implode( ' ', array_map( 'escapeshellarg', [ $saved, $url ] ) );
				\WP_CLI::line( 'Run: wp ' . $cli_command );
				\WP_CLI::runcommand( $cli_command );
			}
		}
		\WP_CLI::success( 'Finished importing.' );
	}
	
	/**
	 * Get directory to sync
	 *
	 * @param bool $create If true, uploads directory will be created.
	 * @return array
	 */
	private function directory_list( $create = false ) {
		// Create directory if not exists.
		$uploads_dir = $this->dir() . '/uploads';
		if ( ! is_dir( $uploads_dir ) && $create ) {
			if ( ! mkdir( $uploads_dir, 0755 ) ) {
				$this->handle_wp_error( new \WP_Error( 'failed_directory_creation', 'Failed to create uploads directory.' ) );
			}
		}
		$synced_dir = apply_filters( 'kunoichi_theme_resource_sharing_directory', [
			ABSPATH . 'wp-content/uploads' => $uploads_dir,
		] );
		return $synced_dir;
	}
	
	/**
	 * Call directory.
	 *
	 * @param bool $is_writable If true, check directory is writable. Default false.
	 * @return string|\WP_Error
	 */
	private function dir( $is_writable = false ) {
		$dir = get_stylesheet_directory() . DIRECTORY_SEPARATOR . trim( ThemeResourceSharing::$resource_dir, DIRECTORY_SEPARATOR );
		if ( ! is_dir( $dir ) && ! mkdir( $dir, 0755 ) ) {
			return new \WP_Error( 'invalid_directory', sprintf( 'Directory `%s` does not exists.', $dir ) );
		}
		if ( ! $is_writable && ! is_writable( $dir ) ) {
			return new \WP_Error( 'invalid_directory', sprintf( 'Directory `%s` is not writable.', $dir ) );
			
		}
		return $dir;
	}
	
	/**
	 * If it's WP_Error, stop process.
	 *
	 * @param \WP_Error $wp_error
	 */
	private function handle_wp_error( $wp_error ) {
		if ( ! is_wp_error( $wp_error ) ) {
			return;
		}
		\WP_CLI::error( sprintf( '[%s] %s', $wp_error->get_error_code(), $wp_error->get_error_message() ) );
	}
}
