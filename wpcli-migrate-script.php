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
	 */
	public $user_args_values;

	public function __construct() {

		$this->user_args_values = array(
			'json_file',
			'json_url',
		);

		error_log( print_r( $this->user_args_values, true ) );

	}


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

	public function import( $user_args ) {

	}

}

WP_CLI::add_command( 'migrate', 'WPCLI_Custom_Migrate_Command' );
