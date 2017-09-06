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

	/**
	 * Check if there is a difference in term count on a post
	 * between the remote post and the local post.
	 * If there is a difference we remove all local post terms
	 * here to trigger them to be reassigned.
	 *
	 * @param  integer $post_id           Local post ID
	 * @param  array $local_post_terms  Array of term objects as returned by wp_get_post_terms
	 * @param  array $remote_post_terms Array of term IDs associated with remote post
	 * @return boolean                    True if there is a difference in post term count
	 */
	public static function term_diff_check( $post_id = null, $local_post_terms = null, $remote_post_terms = null ) {

		$term_check = false;

		if ( ! isset( $post_id, $local_post_terms, $remote_post_terms ) ) {
			/**
			 * WP_CLI::error fires return; to stop execution
			 */
			WP_CLI::error( 'A value for term_diff_check is not set, bailing here' );
		}

		/**
		 * Counting the array lengths to determine if there is a difference
		 */
		$local_post_terms_count = count( $local_post_terms );
		$remote_post_terms_count = count( $remote_post_terms );

		if ( $local_post_terms_count !== $remote_post_terms_count ) {
			$term_check = true; // There is a difference in term counts
		} else {
			/**
			 * We have the same term count but need to check if they are actually the same term
			 */

			/**
			 * @todo  still need to account for if the count is the same though the term is different
			 * probably using an additional JSON call to the remote site.
			 */
			foreach ($local_post_terms as $term) {

				WP_CLI::log( 'single term: ' . print_r($term, 1) );

				$local_term_meta_check = get_term_meta( $term->term_id, 'origin_id', true );

				WP_CLI::log( 'single term meta: ' . print_r($local_term_meta_check, 1) );


			}
		}

		return $term_check;

	} // End term_diff_check

} // END class
