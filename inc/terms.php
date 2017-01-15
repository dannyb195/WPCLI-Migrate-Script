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
	 *
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

			// error_log( 'terms:' . print_r( $this->terms, true ) );
		}

		// Checking if the term already exists here
		$this->terms_check( $this->terms );

		// We already know we have terms, it is safe to move forward
		// $this->add_term_to_post( $this->post_id, $this->terms );
	}

	/**
	 * [terms_check description]
	 *
	 * @param  [type] $terms [description]
	 * @return [type]        [description]
	 */
	private function terms_check( $terms ) {

		foreach ( $terms as $term ) {

			$term_check = term_exists( $term->slug, $term->taxonomy );

			// error_log( 'term check: ' . print_r( $term_check, true ) );
			if ( empty( $term_check ) ) {
				if ( true == $this->debug ) {
					WP_CLI::log( 'we should create this term' );
				}

				$this->term_create( $term );

			} else {
				if ( true == $this->debug ) {
					WP_CLI::log( 'term already exists' );
				}

				$this->add_term_to_post( $this->post_id, $term );

			}
		} // End foreach

	} // End terms_check

	/**
	 * [term_create description]
	 *
	 * @param  [type] $term [description]
	 * @return [type]       [description]
	 */
	private function term_create( $term ) {

		/**
		 * Debug info for single terms
		 */
		if ( true == $this->debug ) {
			error_log( 'term_create: ' . print_r( $term, true ) );
		}

		$success_check = wp_insert_term( $term->name, $term->taxonomy );

		if ( is_array( $success_check ) ) {

			$this->add_term_to_post( $this->post_id, $term );
		}

	}

	/**
	 * [add_term_to_post description]
	 *
	 * @param [type] $post_id [description]
	 * @param [type] $terms   [description]
	 */
	private function add_term_to_post( $post_id, $term ) {

		// add term to post here
		if ( true == $this->debug ) {
			error_log( 'add term to post here: ' . print_r( $term, true ) );
			error_log( 'post ID from terms file: ' . $post_id );
		}

		$test = wp_set_object_terms( $post_id, $term->id, $term->taxonomy, true );

		// error_log( 'find me ' . print_r( $test, true ) );
	}

} // END class
