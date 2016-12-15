<?php
/**
 * WPCLI_Migration_Post: Handles the basic WordPress to WordPress import process
 *
 * @package wpcli-migration-script
 */

/**
 * WPCLI_Migration_Post: Handles the basic WordPress to WordPress import process
 *
 * @package wpcli-migration-script
 * @author Dan Beil
 **/
class WPCLI_Migration_Post {

	/**
	 * Placeholder property for incoming
	 *
	 * @var string
	 */
	private $json;

	/**
	 * Placeholder propert for debug parameter
	 *
	 * @var string
	 */
	private $debug;

	/**
	 * Class Construct
	 *
	 * @param string $json      JSON string of incoming data.
	 * @param array  $user_args  array of $user_args as provided by WP CLI.
	 */
	public function __construct( $json, $user_args ) {
		$this->json = $json;
		$this->debug = isset( $user_args['migrate_debug'] ) && 'true' === $user_args['migrate_debug'] ? true : false;
		$this->post_import( $json );
	}

	/**
	 * [post_import description]
	 *
	 * @param  [type] $json [description]
	 * @return [type]       [description]
	 */
	private function post_import( $json ) {

		$count = count( $json );

		WP_CLI::log( 'importing ' . $count . ' posts' );

		/**
		 * https://wp-cli.org/docs/internal-api/wp-cli-utils-make-progress-bar/
		 */
		$progress = \WP_CLI\Utils\make_progress_bar( 'Migrating Posts', $count );

		$i = 0;

		while ( $i < $count ) {

			foreach ( $json as $import_post ) {

				$i++;

				// error_log( print_r( $post, true ) );
				/**
				 * Checking if our post already exists
				 */
				$status_check = get_posts( array(
					'suppress_filters' => false,
					'post_type' => $import_post->type,
					'fields' => 'ids',
					'posts_per_page' => 1,
					'meta_query' => array(
						array(
							'key' => 'content_origin',
							'value' => $import_post->_links->self[0]->href,
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
					$author = wp_remote_get( $import_post->_links->author[0]->href );
					$author = json_decode( $author['body'] );

					$user = get_user_by( 'login', $author->name );

					if ( false === $user ) {

						if ( true == $this->debug ) {
							WP_CLI::log( 'user ' . $author->name . ' does not exist we should create them' );
						}

						$new_user = wp_insert_user( array(
							'user_login' => $author->name,
							'user_name' => $author->name,
							'user_pass' => wp_generate_password( 12, false ),
						) );

						/**
						 *
						 */
						if ( is_object( $new_user ) ) {
							WP_CLI::log( 'User already exists: ' . print_r( $new_user, true ) );
							continue;
						}

						/**
						 *
						 */
						if ( true == $this->debug ) {
							WP_CLI::log( print_r( $new_user, true ) . 'created' );
						}


					} else {
						$new_user = $user->data->ID;

						wp_update_user( array(
							$new_user,
							'display_name' => $user->name,
						) );
					}
					// error_log( print_r( $author, true ) );

					// error_log( print_r( $post, true ) );

					preg_match_all( '#(?:<img.*src=")(https?.*.jpg|jpeg|png|gif)(?:")#', $import_post->content->rendered, $matches );

					if ( true == $this->debug && empty( $matches ) ) {
						WP_CLI::log( 'no images found in content' );
					}

					if ( ! empty( $matches[1] ) ) {
						require_once( __DIR__ . '/../inc/attachment.php' ); // Loading our class that handles migrating media / attachments
						new WPCLI_Migration_Attachment( $matches[1], $this->debug );
					}

					/**
					 * Initial import is happening here
					 */
					$migration_check = wp_insert_post( array(
						'post_author' => $new_user, // @todo still need to deal with authors
						'post_date' => $import_post->date,
						'post_date_gmt' => $import_post->date_gmt,
						'post_content' => $import_post->content->rendered,
						'post_title' => ! empty( $import_post->title->rendered ) ? $import_post->title->rendered : 'no title',
						'post_excerpt' => $import_post->excerpt->rendered,
						'post_type' => $import_post->type,
						'post_name' => '',
						'post_modified' => $import_post->modified,
						'post_modified_gmt' => '',
						'post_status' => 'publish',
						'comment_status' => 'closed',
						'ping_status' => 'open',
						'meta_input' => array(
							'content_origin' => $import_post->_links->self[0]->href,
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

					/**
					 * Setting out local post information
					 * @var array
					 */
					$local_post_check = array();
					$local_post_check['post_content'] = $local_post->post_content;
					$local_post_check['post_title'] = $local_post->post_title;

					/**
					 * Setting our remote post information
					 */
					// error_log( 'local post: ' . print_r( $local_post_check, true ) );
					$remote_post = array();

					// $remote_post['ID'] = $status_check[0]; // Faking that the remote post has the same ID as the local post
					// $remote_post['post_date'] = $post->date;
					// $remote_post['post_date_gmt'] = $post->date_gmt;
					$remote_post['post_content'] = $import_post->content->rendered;
					$remote_post['post_title'] = $import_post->title->rendered;
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


					/**
					 * Checking the difference between our local and remote post content
					 */
					$diff = array_diff( (array) $local_post_check, $remote_post );

					/**
					 * The remote post has changed, we will update it here
					 */
					if ( ! empty( $diff ) ) {

						error_log( 'Diff: ' . print_r( $diff, true ) );

						preg_match_all( '#(?:<img .* src=")(https?.*.jpg|jpeg|png|gif)(?:")#', $import_post->content->rendered, $matches );
						if ( ! empty( $matches[1] ) ) {
							require_once( __DIR__ . '/../inc/attachment.php' ); // Loading our class that handles migrating media / attachments
							new WPCLI_Migration_Attachment( $matches[1], $this->debug );
						}

						/**
						 * Updating posts if they already exist
						 *
						 * @todo  need some type of check if the post has actually changed here
						 */
						$migration_check = wp_insert_post( array(
							'ID' => $status_check[0], // This is the existing post ID
							'post_author' => '', // @todo still need to deal with authors
							'post_date' => $import_post->date,
							'post_date_gmt' => $import_post->date_gmt,
							'post_content' => $import_post->content->rendered,
							'post_title' => $import_post->title->rendered,
							'post_excerpt' => $import_post->excerpt->rendered,
							'post_type' => $import_post->type,
							'post_name' => '',
							'post_modified' => $import_post->modified,
							'post_status' => 'publish',
							'meta_input' => array(
								'content_origin' => $import_post->_links->self[0]->href,
							),
						) );

						if ( false !== $migration_check ) {
							WP_CLI::log( 'Post ' . $import_post->title->rendered . ' with ID ' . $status_check[0] . ' has been updated' );
						}
					}
				}

				$progress->tick();

			}
		}

		$progress->finish();
	}

} // END class
