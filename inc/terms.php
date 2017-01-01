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

		if ( ! is_wp_error( $this->terms ) ) {
			$this->terms = json_decode( $this->terms['body'] );
		} else {
			WP_CLI::warning( 'Bad request for post terms' );
		}


		$this->debug = $debug;

		if ( true == $this->debug ) {
			error_log( 'post id: ' . $this->post_id );

			error_log( 'terms:' . print_r( $this->terms, true ) );
		}

	}

} // END class