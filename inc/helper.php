<?php
/**
 * undocumented class
 *
 * @package default
 * @author
 **/
class WPCLI_Migration_Helper {

	public static function initiate_terms( $migration_check, $terms_href, $debug ) {

		require_once( __DIR__ . '/../inc/class-wpcli-migration-terms.php' );

		if ( true == $debug ) {
			error_log( 'terms_href: ' . $terms_href );
		}

		new WPCLI_Migration_Terms( $migration_check, $terms_href, $debug );

	}

	/**
	 * Getting our local user via user meta
	 * @param  integer $author_id Local user ID
	 * @return object             WP_User object
	 */
	public static function local_user( $author_id = null ) {

		if ( null === $author_id ) {
			return;
		}

		return get_users( array(
			'meta_key' => 'origin_id',
			'meta_value' => intval( $author_id ),
		) );

	} // End local_user().

} // END class
