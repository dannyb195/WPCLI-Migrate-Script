<?php
/**
 * undocumented class
 *
 * @package default
 * @author
 **/


class WPCLI_Migration_Terms {

	private $post_id;

	private $terms_url;

	private $terms;

	private $debug;

	/**
	 * [__construct description]
	 * @param string $post      [description]
	 * @param string $terms_url [description]
	 * @param string $debug     [description]
	 */
	public function __construct( $post_id = 0, $terms_url = '', $debug = '' ) {

		$this->post_id = $post_id; // this is currently the remote post ID

		$this->terms = wp_remote_get( $terms_url );

		$this->debug = $debug;

		if ( ! is_wp_error( $this->terms ) ) {
			$this->terms = json_decode( $this->terms['body'] );
		} else {
			WP_CLI::warning( 'Bad request for post terms' );
		}

		if ( true == $this->debug ) {
			error_log( 'post id: ' . $this->post_id );

			error_log( 'terms:' . print_r( $this->terms, true ) );
		}

		// Checking if the term already exists here
		$this->terms_create( $this->terms );

		// We already know we have terms, it is safe to move forward
		$this->add_term_to_post( $this->post_id, $this->terms );

	}

	private function terms_create( $terms ) {

	}

	private function add_term_to_post( $post_id, $terms ) {

		// add term to post here
	}

} // END class