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

		if ( true === $debug ) {
			WP_CLI::log( 'terms_href: ' . $terms_href );
		}

		new WPCLI_Migration_Terms( $migration_check, $terms_href, $debug );

	}

} // END class
