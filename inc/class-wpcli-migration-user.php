<?php
/**
 * Place holder file for now
 *
 * Note class-wpcli-migration-post.php ~line 150
 */

class WPCLI_Migration_User {

	/**
	 * Our debugging property
	 * @var Boolean
	 */
	public $debug;

	public function __construct( $debug ) {
		$this->debug = $debug;
	}

	public function check_create_user( $import_post ) {

		/**
		 * Start move this
		 * Author / User stuff here
		 *
		 * @todo  move this to inc/author.php because I should have put it there in the first place
		 */

		if ( property_exists( $import_post->_links, 'author' ) ) {
			WP_CLI::log( '1' );
			// @codingStandardsIgnoreStart
			$author = wp_remote_get( $import_post->_links->author[0]->href );
			// @codingStandardsIgnoreEnd
		} else {
			WP_CLI::log( '2' );
			$author = new WP_Error();
		}

		if ( ! is_wp_error( $author ) ) {
			WP_CLI::log( '3' );
			$author = json_decode( $author['body'] );
			if ( property_exists( $author, 'name' ) ) {
				WP_CLI::log( '4' );
				$user = get_user_by( 'login', $author->name );
			} else {
				WP_CLI::log( '5' );
				$author->name = null;
			}
		} else {
			WP_CLI::log( '6' );
			$user = false;
		}

		if ( false === $user ) {
			WP_CLI::log( '7' );

			if ( true === $this->debug ) {
				WP_CLI::log( 'user ' . $author->name . ' does not exist we should create them' );
			}

echo "author\n<pre>";
print_r($author);
echo "</pre>\n\n";

			if ( property_exists( $author , 'name' ) ) {
				$new_user = wp_insert_user( array(
					'user_login' => $author->name,
					'user_name' => $author->name,
					'user_pass' => wp_generate_password( 12, false ),
					'role' => $author->role,
				) );

				/**
				 * Adding user meta which is later used to determine if a post author has changed.
				 */
				// @codingStandardsIgnoreStart
				add_user_meta( intval( $new_user ), 'origin_id', $author->id );
				// @codingStandardsIgnoreEnd
			} else {
				// continue;
			}

			/**
			 * If the $new_user is an object the user already exists
			 */
			if ( is_object( $new_user ) ) {
				// @codingStandardsIgnoreStart
				WP_CLI::log( 'User already exists: ' . print_r( $new_user, true ) );
				// @codingStandardsIgnoreEnd
				// continue;
			}

			if ( true === $this->debug ) {
				// @codingStandardsIgnoreStart
				WP_CLI::log( print_r( $new_user, true ) . 'created' );
				// @codingStandardsIgnoreEnd
			}
		} else {

			WP_CLI::log( 'User already exists' );

			$new_user = $user->data->ID;

echo "new_user\n<pre>";
print_r($new_user);
echo "</pre>\n\n";

			wp_update_user( array(
				$new_user,
				'display_name' => $user->name,
			) );
		}// End if().

		return $new_user;

		/**
		 * End move this
		 */

	} // End check_create_user()
}
