<?php
/**
 * Place holder file for now
 */

/**
 * undocumented class
 *
 * @package default
 * @author
 **/
class WPCLI_Migration_Menus {

	/**
	 * [$menus_json_endpoint description]
	 * @var [type]
	 */
	private $menus_json_endpoint;

	/**
	 * [__construct description]
	 * @param [type] $user_args [description]
	 */
	public function __construct( $user_args ) {

		// echo "user args in menus\n<pre>";
		// print_r($user_args);
		// echo "</pre>\n\n";

		/**
		 *
		 */
		if ( isset( $user_args['json_url'] ) && ! empty( $user_args['json_url'] ) ) {
			$this->menus_json_endpoint = $user_args['json_url'];

			$this->update_menus_endpoint( $this->menus_json_endpoint );

		} else {
			WP_CLI::error( 'WordPress menus can only be migrated using a WP REST API endpoint' );
		}

	} // End __construct

	private function update_menus_endpoint( $url ) {
		// echo "url\n<pre>";
		// print_r($url);
		// echo "</pre>\n\n";

		preg_match( '#(?:http.*/v2/)(.*)?(?)#', $url, $matches );

		// echo "url\n<pre>";
		// print_r($matches);
		// echo "</pre>\n\n";

		$url = str_replace( $matches['1'], 'nav_menu', $matches[0] );

		// str_replace(search, replace, subject)

		echo "url\n<pre>";
		print_r($url);
		echo "</pre>\n\n";

		$headers = get_headers( $url );

		echo "headers\n<pre>";
		print_r($headers);
		echo "</pre>\n\n";

		if ( strpos( $headers[0], '200' ) > -1 ) {

			WP_CLI::success( 'We have a valid menus JSON endpoint' );

			$menus = wp_remote_get( esc_url( $url ) );
			$menus = $menus['body'];

			echo "menus\n<pre>";
			print_r($menus);
			echo "</pre>\n\n";
		} else {

			WP_CLI::error( 'Something went wrong, please ensure you have https://github.com/dannyb195/WPCLI-Migrate-Script-Source-Site installed on the remote / source site' );
		}




		die();
	}
} // END class