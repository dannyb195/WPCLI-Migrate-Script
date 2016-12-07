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




		$this->post_import( $json );
	}

	private function post_import( $json ) {

		$count = count( $json );

		error_log( 'importing ' . $count . ' posts' );

		/**
		 * https://wp-cli.org/docs/internal-api/wp-cli-utils-make-progress-bar/
		 */
		$progress = \WP_CLI\Utils\make_progress_bar( 'Migrating Posts', $count );

		for ( $i = 0; $i < $count; $i++ ) {

			foreach ( $json as $post ) {

				// error_log( print_r( $post, true ) );

				$status_check = get_posts( array(
					'suppress_filters' => false,
					'post_type' => $post->type,
					'fields' => 'ids',
					'posts_per_page' => 1,
					'meta_query' => array(
						array(
							'key' => 'content_origin',
							'value' => $post->_links->self[0]->href,
						),
					),
				) );

				// error_log( print_r( $status_check, true ) );

				if ( ! isset( $status_check[0] ) || empty( $status_check[0] ) ) {
					$migration_check = wp_insert_post( array(
						'post_author' => '', // @todo still need to deal with authors
						'post_date' => $post->date,
						'post_date_gmt' => $post->date_gmt,
						'post_content' => $post->content->rendered,
						'post_title' => $post->title->rendered,
						'post_excerpt' => $post->excerpt->rendered,
						'post_type' => $post->type,
						'post_name' => '',
						'post_modified' => $post->modified,
						'post_status' => 'publish',
						'meta_input' => array(
							'content_origin' => $post->_links->self[0]->href,
						),

					) );

					if ( false !== $migration_check ) {
						WP_CLI::log( 'Migrated Post ID: ' . $migration_check );
					} else {
						WP_CLI::error( 'Failed migration of post', false ); // Setting false here not to kill the migration loop
					}


				} else {
					// error_log( 'Post ' . $status_check[0] . ' already exists' );
				}





			}

			$progress->tick();

		}

		$progress->finish();
	}

} // END class