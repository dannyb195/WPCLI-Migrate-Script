<?php
/**
 * WPCLI_Migration_Terms handles term mirgation and updates
 *
 * @package wpcli-migration-script
 */

/**
 * WPCLI_Migration_Terms handles term mirgation and updates
 *
 * @package wpcli-migration-script
 **/
class WPCLI_Migration_Terms {

	/**
	 * Post ID of current post being mirgated or updated
	 *
	 * @var integer
	 */
	private $post_id;

	/**
	 * Remote API URL endpoint for terms
	 *
	 * @var string
	 */
	private $terms_url;

	/**
	 * Terms as recieved by the $term_url property
	 *
	 * @var array
	 */
	private $terms;

	/**
	 * Debug options
	 *
	 * @var integer
	 */
	private $debug;

	/**
	 * [__construct description]
	 *
	 * @param integer $post_id   [description]
	 * @param string  $terms_url [description]
	 * @param string  $debug     [description]
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
		}

		// Checking if the term already exists here
		$this->terms_check( $this->terms );

		// We already know we have terms, it is safe to move forward
		$this->add_term_to_post( $this->post_id, $this->terms );
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

			if ( empty( $term_check ) ) {
				if ( true == $this->debug ) {
					WP_CLI::log( 'we should create this term' );
				}
				$this->term_create( $term );
			} else {
				if ( true == $this->debug ) {
					WP_CLI::log( 'term already exists' );
				}
				$this->add_term_to_post( $this->post_id, $term_check );
			}
		} // End foreach().

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
			$this->add_term_to_post( $this->post_id, $success_check );
		}

	}

	/**
	 * adding our term/s to the post
	 *
	 * @param integer $post_id Post ID to add terms to.
	 * @param array $terms     Array of terms to add to the post, if there is
	 *                         more than one term an array of objects is returned.
	 */
	private function add_term_to_post( $post_id, $term ) {

		if ( true == $this->debug ) {
			error_log( 'add term to post here: ' . print_r( $term, true ) );
			error_log( 'post ID from terms file: ' . $post_id );
		}

		/**
		 * We have more than one term on this post
		 * and for some reason the JSON API returns
		 * an array of objects in this situation
		 */
		if ( is_array( $term ) && array_key_exists( 0, $term ) ) {
			foreach ( $term as $single_term ) {
				$test = wp_set_object_terms( $post_id, $single_term->id, 'category', true );
			}
		} else {
			// Dealing with only one or no term.
			if ( isset( $term['term_id'] ) ) {
				$test = wp_set_post_terms( $post_id, array( $term['term_id'] ), 'category', true );
			}
		}
	}

} // END class
