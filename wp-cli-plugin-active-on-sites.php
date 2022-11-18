<?php

/*
Plugin Name: WP-CLI Plugin Active on Sites
Plugin URI:  https://github.com/iandunn/wp-cli-plugin-active-on-sites
Description: A WP-CLI command to list all sites in a Multisite network that have activated a given plugin
Version:     0.1
Author:      Ian Dunn
Author URI:  http://iandunn.name
License:     GPLv2
*/

/*
 * TODO
 *
 * Write unit tests
 *
 */

namespace WP_CLI\Plugin\Active_On_Sites;
use WP_CLI;

if ( ! defined( 'WP_CLI' ) ) {
	return;
}

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
 * @param array $args
 * @param array $assoc_args
 */
function invoke( $args, $assoc_args ) {
	reset_display_errors();

	list( $target_plugin ) = $args;

	WP_CLI::line();
	pre_flight_checks( $target_plugin );
	$found_sites = find_sites_with_plugin( $target_plugin );

	WP_CLI::line();
	display_results( $target_plugin, $found_sites, $assoc_args );
}

/**
 * Re-set `display_errors` after WP-CLI overrides it
 *
 * Normally WP-CLI disables `display_errors`, regardless of `WP_DEBUG`. This makes it so that `WP_DEBUG` is
 * respected again, so that errors are caught more easily during development.
 *
 * Note that any errors/notices/warnings that PHP throws before this function is called will not be shown, so
 * you should still examine the error log every once in awhile.
 *
 * @see https://github.com/wp-cli/wp-cli/issues/706#issuecomment-203610437
 */
function reset_display_errors() {
	add_filter( 'enable_wp_debug_mode_checks', '__return_true' );
	wp_debug_mode();
}

/**
 * Check for errors, unmet requirements, etc
 *
 * @param string $target_plugin
 */
function pre_flight_checks( $target_plugin ) {
	if ( ! is_multisite() ) {
		WP_CLI::error( "This only works on Multisite installations. Use `wp plugin list` on regular installations." );
	}

	$installed_plugins = array_map( 'dirname', array_keys( get_plugins() ) );

	if ( ! in_array( $target_plugin, $installed_plugins, true ) ) {
		WP_CLI::error( "$target_plugin is not installed." );
	}

	$network_activated_plugins = array_keys( get_site_option( 'active_sitewide_plugins', array() ) );
	$network_activated_plugins = array_map( 'dirname', $network_activated_plugins );

	if ( in_array( $target_plugin, $network_activated_plugins, true ) ) {
		WP_CLI::warning( "$target_plugin is network-activated." );
		exit( 0 );
	}
}

/**
 * Find the sites that have the plugin activated
 *
 * @param string $target_plugin
 *
 * @return array
 */
function find_sites_with_plugin( $target_plugin ) {
	$sites       = get_sites( array( 'number' => 10000 ) );
	$found_sites = array();
	$notify      = new \cli\progress\Bar( 'Checking sites', count( $sites ) );

	foreach ( $sites as $site ) {
		switch_to_blog( $site->blog_id );

		$active_plugins     = get_option( 'active_plugins', array() );
		$active_admin_email = get_option( 'admin_email' );

		if ( is_array( $active_plugins ) ) {
			$active_plugins = array_map( 'dirname', $active_plugins );
			if ( in_array( $target_plugin, $active_plugins, true ) ) {
				$found_sites[] = array(
					'blog_id'     => $site->blog_id,
					'url'         => trailingslashit( get_site_url( $site->blog_id ) ),
					'admin_email' => $active_admin_email,
				);
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
 * @param string $target_plugin
 * @param array  $found_sites
 * @param array $assoc_args
 */
function display_results( $target_plugin, $found_sites, $assoc_args ) {
	if ( ! $found_sites ) {
		WP_CLI::line( "$target_plugin is not active on any sites." );
		return;
	}

	if ( isset( $assoc_args['fields'] ) ) {
		$assoc_args['fields'] = explode( ',', $assoc_args['fields'] );
	} else {
		$assoc_args['fields'] = array( 'blog_id', 'url' );
	}

	WP_CLI::line( "Sites where $target_plugin is active:" );

	$formatter = new \WP_CLI\Formatter( $assoc_args );
	$formatter->display_items( $found_sites );
}
