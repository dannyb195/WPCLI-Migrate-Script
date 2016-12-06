<?php
/**
 * Plugin Name: WPCLI Migrate Script
 * Plugin URI: https://github.com/dannyb195/WPCLI-Migrate-Script
 * Description: This plugin provides a generic WPCLI function to allow for importing content via a JSON file or JSON endpoint. Custom code will need to be written to account for your incoming data structure
 * Author: Dan Beil
 * Version: .0
 * Author URI: http://addactiondan.me
 */

/**
 *
 * WPCLI_Custom_Migrate_Command extends WP_CLI_Command allows for hitting a JSON file or endpoint for custom import code to be written
 *
 * Usage:
 * wp migrate --json_url=http://addactiondan.me/wp-json/wp/v2/posts?per_page=10
 * wp migrate --json_file=<path to local file>
 */

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
	 * Our construct
	 */
	public function __construct() {

		$this->user_args_values = array(
			'json_file',
			'json_url',
		);

		error_log( print_r( $this->user_args_values, true ) );

	}

	/**
	 * [__invoke description]
	 * @param  [type] $args      [description]
	 * @param  [type] $user_args [description]
	 * @return [type]            [description]
	 */
	public function __invoke( $args, $user_args ) {

		/**
		 * Acceptible user_args as define in the construct:
		 * --json_file=<file location>
		 * --json_url=<url>
		 */
		WP_CLI::success( 'user_args' . print_r( $user_args, true ) );

		/**
		 * Making sure the user input arguments
		 */
		if ( ! empty( $user_args ) ) {

			/**
			 * Making sure we have valid arguments
			 */
			foreach ( $user_args as $user_arg => $value ) {
				if ( ! in_array( $user_arg, $this->user_args_values ) ) {
					WP_CLI::error( 'Invalid argument -> ' . sanitize_text_field( $user_arg ) . "\nValid arguments are:\n" . print_r( $this->user_args_values, true ) );
				}

				/**
				 * Making sure we have only one JSON source
				 */
				if ( array_key_exists( 'json_file', $user_args ) && array_key_exists( 'json_url', $user_args ) ) {
					WP_CLI::error( 'Please enter only one JSON source' );
				}

				/**
				 * @todo  Make sure we have a valid file or URL to hit here
				 */

			}

			/**
			 * We know we have only one JSON source so starting the import process
			 */
			$this->import( $user_args );

		} else {
			WP_CLI::error( 'Either --json_file=<file> or --json_url=<url> must be defined' );
		}

		// WP_CLI::success( 'Hello World' );
	}

	/**
	 * [import description]
	 *
	 * @param  [type] $user_args [description]
	 * @return [type]            [description]
	 */
	public function import( $user_args ) {

		if ( array_key_exists( 'json_file', $user_args ) ) {
			/**
			 * We are dealing with a local JSON file
			 */

			error_log( 'we have a json file, ready to move forward and write custom code below' );

			/**
			 * Start where custom code would need to be written.
			 * working with standard WP JSON API data for now
			 */

			// Hitting post json file, assuming JSON file is in this plugin's main directory.
			$json = file_get_contents( __DIR__ . '/' . $user_args['json_file'] );

			// Turning json into array.
			$json = json_decode( $json );

			error_log( 'JSON ' . print_r( $json, true ) );

			/**
			 * Checking we have valid JSON
			 */
			if ( empty( $json ) ) {
				WP_CLI::error( 'Invalid JSON string' );
			}

			/**
			 * Do custom stuff to import data here
			 *
			 *
			 *
			 *
			 *
			 *
			 *
			 *
			 *
			 *
			 */

			error_log( 'local file: ' . print_r( $json, true ) );

			/**
			 * End where custom code would be written
			 */

		} elseif ( array_key_exists( 'json_url', $user_args ) ) {
			/**
			 * We are dealing with an external URL request which should return only JSON
			 */

			error_log( 'we have a json url, need to parse (probably)' );

			/**
			 * Making sure we have a valid URL to hit
			 * if this fails we stop here via WP_CLI::error
			 */
			$this->verify_url( $user_args['json_url'] );

			/**
			 * Start where custom code would need to be written.
			 * working with standard WP JSON API data for now
			 */
			// Hitting post json feed.
			$json = file_get_contents( esc_url( $user_args['json_url'] ) );

			// Turning json into array.
			$json = json_decode( $json );

			if ( false === $json ) {
				WP_CLI::error( 'Invalid JSON string' );
			}

			/**
			 * Do custom stuff to import data here
			 *
			 *
			 *
			 *
			 *
			 *
			 *
			 *
			 *
			 *
			 *
			 */

			error_log( print_r( $json, true ) );

			/**
			 * End where custom code would be written
			 */
		}
	}

	private function verify_url( $url ) {

		$headers = get_headers( $url );

		error_log( print_r( $headers, true ) );

		if ( strpos( $headers[0], '200') || strpos( $headers[0], '301') || strpos( $headers[0], '302') ) {
			return true;
		} else {
			WP_CLI::error( 'Invalide URL: ' . sanitize_text_field( $url ) );
		}
	}

}

WP_CLI::add_command( 'migrate', 'WPCLI_Custom_Migrate_Command' );
