<?php
/**
 * Place holder file for now
 */

error_log( 'post.php loading' );

/**
 * undocumented class
 *
 * @package wpcli-migration-script
 * @author Dan Beil
 **/
class WPCLI_Migration_Post {

	private $json;

	public function __construct( $json ) {

		$this->json = $json;


		error_log( print_r( $json, true ) );

		$this->post_import( $json );
	}

	private function post_import( $json ) {

		$count = count( $json );

		error_log( 'importing ' . $count . ' posts' );

		foreach ( $json as $post ) {

		}
	}

} // END class