<?php
/**
 * Plugin Name: WP-CLI Plugin Active on Sites
 * Plugin URI:  https://github.com/iandunn/wp-cli-plugin-active-on-sites
 * Description: A WP-CLI command to list all sites in a Multisite network that have activated a given plugin
 * Version:     1.0.0
 * Author:      Ian Dunn
 * Author URI:  https://iandunn.name
 * License:     GPL-2.0-or-later
 *
 * @package wp-cli-plugin-active-on-sites
 */

namespace WP_CLI\Plugin\Active_On_Sites;

use WP_CLI;
use function WP_CLI\Utils\make_progress_bar, WP_CLI\Utils\get_flag_value;

if ( ! defined( 'WP_CLI' ) ) {
	return;
}

if ( ! function_exists( __NAMESPACE__ . '\invoke' ) ) {
	/**
	 * List all sites in a Multisite network that have activated a given plugin.
	 *
	 * ## OPTIONS
	 *
	 * [<plugin_slug>]
	 * : The plugin to locate. Required unless --none is used.
	 *
	 * [--none]
	 * : List all installed plugins that are not active on any site.
	 *
	 * [--field=<field>]
	 * : Prints the value of a single field for each item.
	 *
	 * [--fields=<fields>]
	 * : Limit the output to specific object fields. Defaults to blog_id,url when
	 *   locating a plugin, or slug,name with --none.
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
	 * wp plugin active-on-sites --none
	 *
	 * @param array $args       Positional arguments
	 * @param array $assoc_args Flags and options
	 */
	function invoke( array $args, array $assoc_args ): void {
		WP_CLI::line();

		if ( ! is_multisite() ) {
			WP_CLI::error( 'This only works on Multisite installations. Use `wp plugin list` on regular installations.' );
		}

		$none_flag     = get_flag_value( $assoc_args, 'none', false );
		$target_plugin = $args[0] ?? null;

		if ( ! $target_plugin && ! $none_flag ) {
			WP_CLI::error( 'Please provide a plugin slug, or use --none to list all plugins not active on any site.' );
		}

		if ( $none_flag ) {
			$inactive_plugins = find_plugins_inactive_on_all_sites();
			display_inactive_results( $inactive_plugins, $assoc_args );

		} else {
			validate_target_plugin( $target_plugin );

			$found_sites = find_sites_with_plugin( $target_plugin );
			display_plugin_sites( $target_plugin, $found_sites, $assoc_args );
		}
	}

	/**
	 * Find all installed plugins that are not active on any site.
	 *
	 * Iterates all sites once to build a set of active slugs ( O(n sites) ),
	 * then subtracts from installed plugins.
	 *
	 * @return array[] Each entry has 'slug' and 'name' keys.
	 */
	function find_plugins_inactive_on_all_sites(): array {
		$slugs_active_on_any_site = [];
		$installed_plugins        = get_plugins();
		$network_activated_slugs  = get_network_activated_slugs();
		$sites                    = get_sites( [ 'number' => 10000 ] );
		$notify                   = make_progress_bar( 'Checking sites', count( $sites ) );

		foreach ( $sites as $site ) {
			switch_to_blog( absint( $site->blog_id ) );

			foreach ( (array) get_option( 'active_plugins', [] ) as $plugin_file ) {
				$slugs_active_on_any_site[ dirname( $plugin_file ) ] = true;
			}

			restore_current_blog();
			$notify->tick();
		}
		$notify->finish();

		$inactive = [];

		foreach ( $installed_plugins as $plugin_file => $plugin_data ) {
			$slug = dirname( $plugin_file );

			if ( in_array( $slug, $network_activated_slugs, true ) ) {
				continue;
			}

			if ( isset( $slugs_active_on_any_site[ $slug ] ) ) {
				continue;
			}

			$inactive[] = [
				'slug' => $slug,
				'name' => $plugin_data['Name'],
			];
		}

		return $inactive;
	}

	/**
	 * Display the plugins that are not active on any site.
	 *
	 * @param array $inactive_plugins Plugins with no site activations.
	 * @param array $assoc_args       Flags and options.
	 */
	function display_inactive_results( array $inactive_plugins, array $assoc_args ): void {
		WP_CLI::line();

		if ( ! $inactive_plugins ) {
			WP_CLI::line( 'No plugins found that are inactive on all sites.' );
			return;
		}

		$formatter_args = $assoc_args;

		if ( isset( $formatter_args['fields'] ) ) {
			$formatter_args['fields'] = explode( ',', $formatter_args['fields'] );
		} else {
			$formatter_args['fields'] = [ 'slug', 'name' ];
		}

		WP_CLI::line( 'Plugins not active on any site:' );

		$formatter = new \WP_CLI\Formatter( $formatter_args );
		$formatter->display_items( $inactive_plugins );
	}

	/**
	 * Validate that the target plugin is installed and not network-activated.
	 *
	 * @param string $target_plugin The slug of the plugin to check.
	 */
	function validate_target_plugin( string $target_plugin ): void {
		$installed_plugins = array_map( dirname( ... ), array_keys( get_plugins() ) );

		if ( ! in_array( $target_plugin, $installed_plugins, true ) ) {
			WP_CLI::error( "$target_plugin is not installed." );
		}

		if ( in_array( $target_plugin, get_network_activated_slugs(), true ) ) {
			WP_CLI::warning( "$target_plugin is network-activated." );
			WP_CLI::halt( 0 );
		}
	}

	/**
	 * Find the sites that have the plugin activated.
	 *
	 * @param string $target_plugin The slug of the plugin to check.
	 */
	function find_sites_with_plugin( string $target_plugin ): array {
		$found_sites = [];
		$sites       = get_sites( [ 'number' => 10000 ] );
		$notify      = make_progress_bar( 'Checking sites', count( $sites ) );

		foreach ( $sites as $site ) {
			$blog_id = absint( $site->blog_id );

			switch_to_blog( $blog_id );

			$active_plugins     = get_option( 'active_plugins', [] );
			$active_admin_email = get_option( 'admin_email' );

			if ( is_array( $active_plugins ) ) {
				$active_plugins = array_map( dirname( ... ), $active_plugins );

				if ( in_array( $target_plugin, $active_plugins, true ) ) {
					$found_sites[] = [
						'blog_id'     => $blog_id,
						'url'         => trailingslashit( get_site_url( $blog_id ) ),
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
	 * Display the sites where the plugin is active.
	 *
	 * @param string $target_plugin The slug of the plugin to check.
	 * @param array  $found_sites   Sites where the plugin is active.
	 * @param array  $assoc_args    Flags and options.
	 */
	function display_plugin_sites( string $target_plugin, array $found_sites, array $assoc_args ): void {
		WP_CLI::line();

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

	/**
	 * Get the folder slugs of all network-activated plugins.
	 *
	 * @return string[]
	 */
	function get_network_activated_slugs(): array {
		$network_activated = array_keys( get_site_option( 'active_sitewide_plugins', [] ) );

		return array_map( dirname( ... ), $network_activated );
	}

	WP_CLI::add_command( 'plugin active-on-sites', __NAMESPACE__ . '\invoke' );
}
