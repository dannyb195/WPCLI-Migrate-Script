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
	private $debug;

	public function __construct( $json, $user_args ) {

		$this->json = $json;

		$this->debug = isset ( $user_args['migrate_debug'] ) && 'true' == $user_args['migrate_debug'] ? true : false;

		// error_log( print_r( $user_args, true ) );

		// error_log( 'migrate debug ' . $this->debug );

		// error_log( 'invoked' );


		$this->post_import( $json );
	}

	private function post_import( $json ) {

		$count = count( $json );

		error_log( 'importing ' . $count . ' posts' );

		/**
		 * https://wp-cli.org/docs/internal-api/wp-cli-utils-make-progress-bar/
		 */
		$progress = \WP_CLI\Utils\make_progress_bar( 'Migrating Posts', $count );

		$i = 0;
		while ( $i < $count ) {

			foreach ( $json as $post ) {

				$i++;

				// error_log( print_r( $post, true ) );

				/**
				 * Checking if our post already exists
				 */
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

				/**
				 * If our post does not exist we will create it here
				 */
				if ( ! isset( $status_check[0] ) || empty( $status_check[0] ) ) {

					/**
					 * Author / User stuff here
					 */


					/**
					 * Initial import is happening here
					 */
					$migration_check = wp_insert_post( array(
						'post_author' => $post->author, // @todo still need to deal with authors
						'post_date' => $post->date,
						'post_date_gmt' => $post->date_gmt,
						'post_content' => $post->content->rendered,
						'post_title' => $post->title->rendered,
						'post_excerpt' => $post->excerpt->rendered,
						'post_type' => $post->type,
						'post_name' => '',
						'post_modified' => $post->modified,
						'post_modified_gmt' => '',
						'post_status' => 'publish',
						'comment_status' => 'closed',
						'ping_status' => 'open',
						'meta_input' => array(
							'content_origin' => $post->_links->self[0]->href,
						),
					) );

					if ( true == $this->debug ) {
						if ( false !== $migration_check ) {
							WP_CLI::log( 'Migrated Post ID: ' . $migration_check );
						} else {
							WP_CLI::error( 'Failed migration of post', false ); // Setting false here not to kill the migration loop
						}
					}

				} else {
					/**
					 * Post Updating happens here
					 */
					if ( true == $this->debug ) {
						error_log( 'Post ' . $status_check[0] . ' already exists, updating' );
					}

					$local_post = get_post( $status_check[0] );

					$local_post_check = array();
					$local_post_check['post_content'] = $local_post->post_content;
					$local_post_check['post_title'] = $local_post->post_title;

					// error_log( 'local post: ' . print_r( $local_post_check, true ) );

					$remote_post = array();

					// $remote_post['ID'] = $status_check[0]; // Faking that the remote post has the same ID as the local post
					// $remote_post['post_date'] = $post->date;
					// $remote_post['post_date_gmt'] = $post->date_gmt;
					$remote_post['post_content'] = $post->content->rendered;
					$remote_post['post_title'] = $post->title->rendered;
					// $remote_post['post_excerpt'] = $post->excerpt->rendered;
					// $remote_post['post_type'] = $post->type;
					// $remote_post['post_name'] = '';
					// $remote_post['post_modified'] = $post->modified;
					// $remote_post['post_author'] = $post->author;
					// $remote_post['post_status'] = 'publish';
					// $remote_post['comment_status'] = $post->comment_status;
					// $remote_post['ping_status'] = $post->ping_status;
					// $remote_post['post_name'] = $post->slug;
					// $remote_post['post_modified_gmt'] = $post->date_gmt;

					// error_log( 'remote post: ' . print_r( $post, true ) );

					$diff = array_diff( (array) $local_post_check, $remote_post );




					if ( ! empty( $diff ) ) {

						error_log( 'Diff: ' . print_r( $diff, true ) );

						/**
						 * Updating posts if they already exist
						 *
						 * @todo  need some type of check if the post has actually changed here
						 */
						$migration_check = wp_insert_post( array(
							'ID' => $status_check[0], // This is the existing post ID
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
							WP_CLI::log( 'Post ' . $post->title->rendered . ' with ID ' . $status_check[0] . ' has been updated' );
						}

					}


				}



				$progress->tick();

			}





		}

		$progress->finish();
	}

} // END class