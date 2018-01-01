<?php
/**
 * WPCLI_Migration_User handles creating and updating post authors
 *
 * Note class-wpcli-migration-post.php ~line 150
 *
 * @package wpcli-migration-script
 * @author Dan Beil
 */

/**
 * WPCLI_Migration_User handles creating and updating post authors
 *
 * Note class-wpcli-migration-post.php ~line 150
 *
 * @package wpcli-migration-script
 * @author Dan Beil
 */
class WPCLI_Migration_User {

	/**
	 * Our debugging property
	 *
	 * @var Boolean
	 */
	public $debug;

	/**
	 * Our construct
	 *
	 * @param boolean $debug True or false, testing is we are in debug mode.
	 */
	public function __construct( $debug ) {
		$this->debug = $debug;
	}

	/**
	 * Creating our user if they do no exist locally
	 *
	 * @param  object $import_post Remote post object.
	 * @return object              Local user object
	 */
	public function check_create_user( $import_post ) {

		/**
		 * Setting empty default
		 *
		 * @var string
		 */
		$new_user = '';

		$user = '';

		/**
		 * Author / User stuff here
		 */

		if ( ! is_object( $import_post->_links ) ) {
			return;
		}

		if ( property_exists( $import_post->_links, 'author' ) ) {
			// @codingStandardsIgnoreStart
			$author = wp_remote_get( $import_post->_links->author[0]->href );
			// @codingStandardsIgnoreEnd
		} else {
			$author = new WP_Error();
		}

		if ( ! is_wp_error( $author ) ) {
			$author = json_decode( $author['body'] );

			if ( property_exists( $author, 'name' ) && property_exists( $author, 'user_email' ) ) {
				$this->user = get_user_by( 'email', $author->user_email );
			} else {
				$author->name = null;
			}
		} else {
			$this->user = '';
		}

		if ( empty( $this->user ) ) {

			if ( true === $this->debug ) {
				WP_CLI::log( 'user ' . $author->name . ' does not exist we should create them' );
			}

			if ( ! isset( $author->name ) ) {
				$author->name = $author->slug;
			}

			if ( ! isset( $author->user_email ) ) {
				$author->user_email =  md5( $author->slug ) . '@12345.com';
			}

			if ( ! isset( $author->role ) ) {
				$author->role = 'author';
			}

			if ( property_exists( $author , 'name' ) ) {
				$new_user = wp_insert_user(
					array(
						'user_login' => $author->name,
						'user_name' => $author->name,
						'user_pass' => wp_generate_password( 12, false ),
						'user_email' => $author->user_email,
						'role' => $author->role,
					)
				);

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
		} // End if().

		return $new_user;

	} // End check_create_user()

	/**
	 * Getting our local user via user meta
	 *
	 * @param  integer $author    Local user ID.
	 * @param  boolean $debug     True or false, testing if we're in debug mode.
	 * @return object             WP_User object
	 */
	public static function local_user( $author = null, $debug = false ) {

		if ( null === $author ) {
			return;
		}

		$email = WPCLI_Migration_Helper::email_check( $author );

		$local_user = get_user_by( 'email', $email );

		if ( is_wp_error( $local_user ) || empty( $local_user ) ) {
			return;
		}

		$user = wp_update_user(
			array(
				'ID' => $local_user->ID,
				'display_name' => $local_user->data->display_name,
				'role' => $author->role,
			)
		);

		/**
		 * If the $new_user is an object the user already exists
		 */

		if ( true === $debug && ! empty( $user ) && ! is_wp_error( $user ) ) {
			// @codingStandardsIgnoreStart
			WP_CLI::log( 'User already exists with ID ' . print_r( $user, true ) . ' maybe updating' );
			// @codingStandardsIgnoreEnd
		}

		return get_user_by( 'ID', $user );

	} // End local_user().
}
