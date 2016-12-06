<?php
/**
 * Plugin Name: WPCLI Migrate Script
 * Plugin URI:
 * Description:
 * Author: Dan Beil
 * Version: .0
 * Author URI: http://addactiondan.me
 */

/**
 *
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
		 * Acceptible user_args:
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

			error_log( 'we have a json file, ready to move forward and write custom code here' );

		} elseif ( array_key_exists( 'json_url', $user_args ) ) {

			error_log( 'we have a json url, need to parse (probably)' );

			/**
			 * Start where custom code would need to be written.
			 * working with standard WP JSON API data for now
			 */

			// Hitting post json feed.
			$json = file_get_contents( esc_url( $user_args['json_url'] ) );
			// Turning json into array.
			$json = json_decode( $json );

			error_log( print_r( $json, true ) );

			/**
			 * End where custom code would be written
			 */

		}
	}

}

WP_CLI::add_command( 'migrate', 'WPCLI_Custom_Migrate_Command' );
