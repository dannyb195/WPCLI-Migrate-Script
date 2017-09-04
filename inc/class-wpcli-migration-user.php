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
		 * Setting empty default
		 * @var string
		 */
		$new_user = '';

		/**
		 * Author / User stuff here
		 *
		 */

		if ( property_exists( $import_post->_links, 'author' ) ) {
			// @codingStandardsIgnoreStart
			$author = wp_remote_get( $import_post->_links->author[0]->href );
			// @codingStandardsIgnoreEnd
		} else {
			$author = new WP_Error();
		}

		if ( ! is_wp_error( $author ) ) {
			$author = json_decode( $author['body'] );

			if ( property_exists( $author, 'name' ) ) {
				$user = get_user_by( 'email', $author->user_email );

			} else {
				$author->name = null;
			}
		} else {
			$user = false;
		}

		if ( false === $user ) {

			if ( true === $this->debug ) {
				WP_CLI::log( 'user ' . $author->name . ' does not exist we should create them' );
			}

			if ( property_exists( $author , 'name' ) ) {
				$new_user = wp_insert_user( array(
					'user_login' => $author->name,
					'user_name' => $author->name,
					'user_pass' => wp_generate_password( 12, false ),
					'user_email' => $author->user_email,
					'role' => $author->role,
				) );

				/**
				 * Adding user meta which is later used to determine if a post author has changed.
				 */

				if ( ! is_wp_error( $new_user ) ) {
					// @codingStandardsIgnoreStart
					add_user_meta( intval( $new_user ), 'origin_id', $author->id );
					// @codingStandardsIgnoreEnd
				}
			} // End if $author->name

			if ( true === $this->debug ) {
				// @codingStandardsIgnoreStart
				WP_CLI::log( print_r( $new_user, true ) . 'created' );
				// @codingStandardsIgnoreEnd
			}
		} else {


		} // End if().

		return $new_user;

	} // End check_create_user()

	/**
	 * Getting our local user via user meta
	 * @param  integer $author_id Local user ID
	 * @return object             WP_User object
	 */
	public static function local_user( $author = null ) {

		if ( null === $author ) {
			return;
		}

		$local_user = get_user_by( 'email', $author->user_email );

		if ( is_wp_error( $local_user ) || empty ( $local_user ) ) {
			return;
		}

		$user = wp_update_user( array(
			'ID' => $local_user->ID,
			'display_name' => $local_user->data->display_name,
			'role' => $author->role,
		) );

		/**
		 * If the $new_user is an object the user already exists
		 */
		if ( ! empty( $user ) && ! is_wp_error( $user ) && true === $this->debug ) {
			// @codingStandardsIgnoreStart
			WP_CLI::log( 'User already exists with ID ' . print_r( $user, true ) . ' maybe updating' );
			// @codingStandardsIgnoreEnd
		} else {
			return;
		}

		return get_user_by( 'ID', $user );

	} // End local_user().
}
