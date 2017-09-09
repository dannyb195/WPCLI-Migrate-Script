<?php
/**
 * Plugin Name: WPCLI Migrate Script
 * Plugin URI: https://github.com/dannyb195/WPCLI-Migrate-Script
 * Description: This plugin provides a generic WPCLI function to allow for importing content via a JSON file or JSON endpoint. Custom code will need to be written to account for your incoming data structure
 * Author: Dan Beil
 * Version: .0
 * Author URI: http://addactiondan.me
 *
 * @package wpcli-migration-script
 */

/**
 *
 * WPCLI_Custom_Migrate_Command extends WP_CLI_Command allows for hitting a JSON file or endpoint for custom import code to be written
 *
 * Kudos to Chris Wiegman for his WCUS 2015 talk http://slides.chriswiegman.com/wcus15 and Daniel Bachhuber
 *
 * Usage:
 * wp migrate --json_url=http://test.me.dev/wp-json/wp/v2/posts?per_page=10
 * wp migrate --json_file=<path to local file>
 *
 * Standar WordPress to WordPress command:
 * wp migrate --json_url=http://test.me.dev/wp-json/wp/v2/posts?per_page=10 --wp2wp=true
 *
 * Demo Posts:
 * https://demo.wp-api.org/wp-json/wp/v2/posts
 *
 * Users:
 * https://demo.wp-api.org/wp-json/wp/v2/users/<user ID>
 *
 * WordPress to WordPress Import with debugging:
 * wp migrate --json_url=http://test.me.dev/wp-json/wp/v2/posts?per_page=100 --wp2wp=true --migrate_debug=true
 * Note that the WP JSON API defaults to a limit of 100 objects accessed to account for this
 * you may also use the --offset parameter to get more content
 *
 * Menu Migration:
 * wp migrate --json_url=http://<site-url>/wp-json/wp/v2/posts?per_page=1 --menus --wp2wp --skip_images
 * ** --skip_images is included here just for speed
 *
 * @package wpcli-migration-script
 * @author Dan Beil
 */

/**
 * Checking that WP CLI is installed, if not bailing here
 *
 * @package wpcli-migration-script
 */
if ( ! class_exists( 'WP_CLI_Command' ) ) {
	return;
}

/**
 * WPCLI_Custom_Migrate_Command: Main WPCLI extension and custom function class
 */
class WPCLI_Custom_Migrate_Command extends WP_CLI_Command {

	/**
	 * Placeholder for our array of acceptable arguments
	 *
	 * @var  array An array to hold our accepted arguments
	 */
	public $user_args_values;

	/**
	 * Placeholder propert for debug parameter
	 *
	 * @var string
	 */
	private $debug;

	/**
	 * Placeholder property for skipping image migration
	 *
	 * @var Boolean
	 */
	private $skip_images;

	/**
	 * Our construct
	 */
	public function __construct() {

		$this->user_args_values = array(
			'json_file', // A local JSON file location.
			'json_url', // A JSON URL endpoint.
			'wp2wp', // A WordPress to Wordpress migration.
			'migrate_debug', // Used for outputting terminal logs.
			/**
			 * We should have a debug_lite option / flag
			 *
			 * @todo  add a debug_lite option here to show success / warnings though not output the post object
			 */
			'skip_images', // Set to 'true' to skip importing images.
			'offset', // offset as expected by WP_Query.
			'menus', // If preset WP menus will be migrated, requires wp2wp=true.
		);

		require_once( __DIR__ . '/inc/class-wpcli-migration-helper.php' );
		require_once( __DIR__ . '/inc/class-wpcli-migration-user.php' );

	} // End __construct

	/**
	 * WPCLI / JSON Migrate script ... wp migrate --json_url=http://test.me.dev/wp-json/wp/v2/posts?per_page=10 ... more docs found in wpcli-migrate-script.php
	 *
	 * @param  array $args      As provided by WPCLI, non-flagged arguments ( not used ).
	 * @param  array $user_args Flagged user arguments as provided by WPCLI, accpected args are listed in the __invoke method.
	 */
	public function __invoke( $args, $user_args ) {

		if ( empty( $user_args ) ) {
			WP_CLI::error( 'You must supply arguments:
				--json_file     A local JSON file location
				--json_url      A JSON URL endpoint
				--wp2wp         A WordPress to WordPress migration
				--migrate_debug Used for outputting terminal logs
				--skip_images   Set to \'true\' to skip importing images
				--offset        Offset as expected by WP_Query
				--menus         If preset WP menus will be migrated, requires wp2wp=true
			' );
		}

		$this->debug = isset( $user_args['migrate_debug'] ) && true === $user_args['migrate_debug'] ? true : false;

		/**
		 * Acceptible user_args as define in the construct:
		 * --json_file=<file location>
		 * --json_url=<url>
		 * --wp2wp=true|false
		 * --migrate_debug=true|false
		 * --skip_images=true|false
		 * --offset=<integer>
		 * --menus=true|false
		 */
		if ( true === $this->debug ) {
			// @codingStandardsIgnoreStart
			WP_CLI::success( 'user_args' . print_r( $user_args, true ) );
			// @codingStandardsIgnoreEnd
		}

		/**
		 * Making sure the user input arguments
		 */
		if ( ! empty( $user_args ) ) {

			/**
			 * Making sure we have valid arguments
			 */
			foreach ( $user_args as $user_arg => $value ) {
				if ( ! in_array( $user_arg, $this->user_args_values, true ) ) {
					// @codingStandardsIgnoreStart
					WP_CLI::error( 'Invalid argument -> ' . sanitize_text_field( $user_arg ) . "\nValid arguments are:\n" . print_r( $this->user_args_values, true ) . 'Please feel free suggest additional functionality at https://github.com/dannyb195/WPCLI-Migrate-Script' );
					// @codingStandardsIgnoreEnd
				}

				/**
				 * Making sure we have only one JSON source
				 */
				if ( array_key_exists( 'json_file', $user_args ) && array_key_exists( 'json_url', $user_args ) ) {
					WP_CLI::error( 'Please enter only one JSON source' );
				}

				/**
				 * Placeholder note to remind me to write functionality for a custom JSON file
				 *
				 * @todo  Make sure we have a valid file or URL to hit here probably via $headers reponse
				 */

			}

			/**
			 * We know we have only one JSON source so starting the import process
			 */
			$this->import( $user_args );

			/**
			 * Getting Menus after the post import
			 */
			if ( isset( $user_args['wp2wp'] ) && true === $user_args['wp2wp'] && isset( $user_args['menus'] ) && true === $user_args['menus'] ) {
				require_once( 'inc/class-wpcli-migration-menu.php' );
				new WPCLI_Migration_Menus( $user_args );
			}
		} else {
			if ( true === $this->debug ) {
				WP_CLI::error( 'Either --json_file=<file> or --json_url=<url> must be defined' );
			}
		}

	} // End __invoke

	/**
	 * Base functionality for import process
	 *
	 * @param  array $user_args Array of options / flags sent to the WP-CLI command.
	 */
	public function import( $user_args ) {

		if ( array_key_exists( 'json_file', $user_args ) ) {
			/**
			 * We are dealing with a local JSON file
			 */

			if ( true === $this->debug ) {
				WP_CLI::log( 'we have a json file, ready to move forward and write custom code below' );
			}

			/**
			 * Start where custom code would need to be written.
			 * working with standard WP JSON API data for now
			 */

			// Hitting post json file, assuming JSON file is in this plugin's main directory.
			// @codingStandardsIgnoreStart
			$json = wp_remote_get( plugin_dir_url( __FILE__ ) . $user_args['json_file'] );
			// @codingStandardsIgnoreEnd
			$json = wp_remote_retrieve_body( $json );

			/**
			 * Decoding our JSON
			 */
			$json = json_decode( $json );

			/**
			 * Checking we have valid JSON
			 */
			if ( empty( $json ) ) {
				WP_CLI::error( 'Invalid JSON string' );
			}

			/**
			 * Dealing with WordPress to WordPress Migration
			 */
			if ( isset( $user_args['wp2wp'] ) && true === $user_args['wp2wp'] ) {

				if ( true === $this->debug ) {
					WP_CLI::log( 'we are dealing with local WordPress JSON file to import to WordPress' );
				}

				require_once( __DIR__ . '/inc/class-wpcli-migration-post.php' ); // Loading our class that handles migrating posts.
				new WPCLI_Migration_Post( $json, $user_args );

			}

			/**
			 * Do custom stuff to import data here
			 */

			/**
			 * End where custom code would be written
			 */

		} elseif ( array_key_exists( 'json_url', $user_args ) ) {
			/**
			 * We are dealing with an external URL request which should return only JSON
			 *
			 * Typically uses for a WordPress to WordPress migration
			 */

			if ( true === $this->debug ) {
				WP_CLI::log( 'we have a json url' );
			}

			/**
			 * Making sure we have a valid URL to hit
			 * if this fails we stop here via WP_CLI::error
			 */
			$this->verify_url( $user_args['json_url'] );

			/**
			 * Because the WP JSON API only allows for hitting 100 objects at a time we allow
			 * for an --offset parameter to get addition content if needed. https://github.com/WP-API/WP-API/issues/1609
			 */
			if ( isset( $user_args['offset'] ) ) {
				$user_args['json_url'] = esc_url( $user_args['json_url'] ) . '&offset=' . intval( $user_args['offset'] );
			}

			/**
			 * Hitting post json feed.
			 */
			WP_CLI::log( 'Getting data from: ' . WP_CLI::Colorize( '%G' . filter_var( $user_args['json_url'], FILTER_SANITIZE_URL ) . '%n' ) );
			// @codingStandardsIgnoreStart
			$json = wp_remote_get( esc_url( $user_args['json_url'] ) );
			// @codingStandardsIgnoreEnd
			if ( is_wp_error( $json ) ) {
				WP_CLI::error( 'WP_Error object returned ' . print_r( $json, 1 ) );
			} else {
				/**
				 * Setting up our JSON data
				 */
				$json = $json['body'];
			}

			// Turning json into array.
			$json = json_decode( $json );

			if ( false === $json ) {
				WP_CLI::error( 'Invalid JSON string' );
			} else {
				WP_CLI::success( 'Valid JSON' );
			}

			if ( isset( $user_args['skip_images'] ) && true === $user_args['skip_images'] ) {
				WP_CLI::warning( 'Skipping Images' );
			}

			/**
			 * Dealing with WordPress to WordPress Migration
			 */
			if ( isset( $user_args['wp2wp'] ) && true === $user_args['wp2wp'] ) {

				WP_CLI::log( 'we are dealing with WordPress to WordPress' );

				require_once( __DIR__ . '/inc/class-wpcli-migration-post.php' ); // Loading our class that handles migrating posts.
				new WPCLI_Migration_Post( $json, $user_args );

			} else {
				WP_CLI::warning( '--wp2wp is not set to true' );
			}

			/**
			 * End where custom code would be written
			 */
		}

	} // End import().

	/**
	 * Making sure we have a valid URL
	 *
	 * @param  string $url URL to access.
	 */
	private function verify_url( $url ) {

		$headers = get_headers( filter_var( $url, FILTER_SANITIZE_URL ) );

		if ( true === $this->debug ) {
			// @codingStandardsIgnoreStart
			WP_CLI::log( 'headers ' . print_r( $headers, true ) );
			// @codingStandardsIgnoreEnd
		}

		if ( strpos( $headers[0], '200' ) || strpos( $headers[0], '301' ) || strpos( $headers[0], '302' ) ) {
			return true;
		} elseif ( strpos( $headers[0], '400' ) ) {

			// @codingStandardsIgnoreStart
			$response = wp_remote_get( $url );
			// @codingStandardsIgnoreEnd
			$response = json_decode( $response['body'] );

			// @codingStandardsIgnoreStart
			WP_CLI::error( 'Bad Request: ' . print_r( $response ) );
			// @codingStandardsIgnoreEnd
		} else {
			// @codingStandardsIgnoreStart
			WP_CLI::error( 'Invalide URL: ' . sanitize_text_field( $url ) );
			// @codingStandardsIgnoreEnd
		}
	} // End verify_url().

} // End WPCLI_Custom_Migrate_Command.

WP_CLI::add_command( 'migrate', 'WPCLI_Custom_Migrate_Command' );
