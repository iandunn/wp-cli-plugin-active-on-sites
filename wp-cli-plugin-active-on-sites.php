<?php
/**
 * Plugin Name: WP-CLI Plugin Active on Sites
 * Plugin URI:  https://github.com/iandunn/wp-cli-plugin-active-on-sites
 * Description: A WP-CLI command to list all sites in a Multisite network that have activated a given plugin
 * Version:     0.1
 * Author:      Ian Dunn
 * Author URI:  http://iandunn.name
 * License:     GPLv2
 *
 * @package wp-cli-plugin-active-on-sites
 */

/*
 * TODO
 * - Write unit tests
 */

namespace WP_CLI\Plugin\Active_On_Sites;

use WP_CLI;

if ( ! defined( 'WP_CLI' ) ) {
	return;
}

if ( ! function_exists( __NAMESPACE__ . '\invoke' ) ) {
	WP_CLI::add_command( 'plugin active-on-sites', __NAMESPACE__ . '\invoke' );

	/**
	 * List all sites in a Multisite network that have activated a given plugin.
	 *
	 * ## OPTIONS
	 *
	 * <plugin_slug>
	 * : The plugin to locate
	 *
	 * [--field=<field>]
	 * : Prints the value of a single field for each site. See the `fields` parameter for a list of available fields.
	 *
	 * [--fields=<fields>]
	 * : Limit the output to specific object fields.
	 * ---
	 * default: blog_id, url
	 * optional: admin_email
	 * ---
	 *
	 * [--format=<format>]
	 * : Render output in a particular format.
	 * ---
	 * default: table
	 * options:
	 *   - table
	 *   - csv
	 *   - ids
	 *   - json
	 *   - count
	 *   - yaml
	 * ---
	 *
	 * ## EXAMPLES
	 *
	 * wp plugin active-on-sites buddypress
	 *
	 * @param array $args       Positional arguments
	 * @param array $assoc_args Flags and options
	 */
	function invoke( array $args, array $assoc_args ): void {
		[ $target_plugin ] = $args;

		WP_CLI::line();
		pre_flight_checks( $target_plugin );
		$found_sites = find_sites_with_plugin( $target_plugin );

		WP_CLI::line();
		display_results( $target_plugin, $found_sites, $assoc_args );
	}

	/**
	 * Check for errors, unmet requirements, etc
	 *
	 * @param string $target_plugin The slug of the plugin to check.
	 */
	function pre_flight_checks( string $target_plugin ): void {
		if ( ! is_multisite() ) {
			WP_CLI::error( 'This only works on Multisite installations. Use `wp plugin list` on regular installations.' );
		}

		$installed_plugins = array_map( dirname( ... ), array_keys( get_plugins() ) );

		if ( ! in_array( $target_plugin, $installed_plugins, true ) ) {
			WP_CLI::error( "$target_plugin is not installed." );
		}

		$network_activated_plugins = array_keys( get_site_option( 'active_sitewide_plugins', [] ) );
		$network_activated_plugins = array_map( dirname( ... ), $network_activated_plugins );

		if ( in_array( $target_plugin, $network_activated_plugins, true ) ) {
			WP_CLI::warning( "$target_plugin is network-activated." );
			WP_CLI::halt( 0 );
		}
	}

	/**
	 * Find the sites that have the plugin activated
	 *
	 * @param string $target_plugin The slug of the plugin to check.
	 */
	function find_sites_with_plugin( string $target_plugin ): array {
		$sites       = get_sites( [ 'number' => 10000 ] );
		$found_sites = [];
		$notify      = new \cli\progress\Bar( 'Checking sites', count( $sites ) );

		foreach ( $sites as $site ) {
			switch_to_blog( $site->blog_id );

			$active_plugins     = get_option( 'active_plugins', [] );
			$active_admin_email = get_option( 'admin_email' );

			if ( is_array( $active_plugins ) ) {
				$active_plugins = array_map( dirname( ... ), $active_plugins );
				if ( in_array( $target_plugin, $active_plugins, true ) ) {
					$found_sites[] = [
						'blog_id'     => $site->blog_id,
						'url'         => trailingslashit( get_site_url( $site->blog_id ) ),
						'admin_email' => $active_admin_email,
					];
				}
			}

			restore_current_blog();
			$notify->tick();
		}
		$notify->finish();

		return $found_sites;
	}

	/**
	 * Display a list of sites where the plugin is active
	 *
	 * @param string $target_plugin The slug of the plugin to check.
	 * @param array  $found_sites   Sites where the plugin is active.
	 * @param array  $assoc_args    Flags and options.
	 */
	function display_results( string $target_plugin, array $found_sites, array $assoc_args ): void {
		if ( ! $found_sites ) {
			WP_CLI::line( "$target_plugin is not active on any sites." );
			return;
		}

		$formatter_args = $assoc_args;

		if ( isset( $formatter_args['fields'] ) ) {
			$formatter_args['fields'] = explode( ',', $formatter_args['fields'] );
		} else {
			$formatter_args['fields'] = [ 'blog_id', 'url' ];
		}

		WP_CLI::line( "Sites where $target_plugin is active:" );

		$formatter = new \WP_CLI\Formatter( $formatter_args );
		$formatter->display_items( $found_sites );
	}
}
