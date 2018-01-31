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
	 * [$skip_images description]
	 *
	 * @var [type]
	 */
	private $skip_images;

	/**
	 * Class Construct
	 *
	 * @param string $json      JSON string of incoming data.
	 * @param array  $user_args  array of $user_args as provided by WP CLI.
	 */
	public function __construct( $json = '', $user_args = '', $post_type = '' ) {

		$this->json = $json;
		$this->debug = isset( $user_args['migrate_debug'] ) && true === $user_args['migrate_debug'] ? true : false;
		$this->skip_images = isset( $user_args['skip_images'] ) && true === $user_args['skip_images'] ? true : false;
		$this->post_import( $json, $user_args, $post_type );
	}

	/**
	 * This method accounts for the general post import process.
	 * It also checks for in-content images as well as a featured image and
	 * triggers the image migrate class
	 *
	 * @param  string $json As provided by the WP JSON API /posts endpoint.
	 */
	private function post_import( $json, $user_args, $post_type ) {

		$count = count( $json );

		if ( ! isset( $user_args['all'] ) || false === $user_args['all'] ) {
			WP_CLI::log( 'importing ' . $count . ' posts' );
		} else {
			WP_CLI::log( 'importing ' . $count . ' ' . $post_type );
		}

		/**
		 * Https://wp-cli.org/docs/internal-api/wp-cli-utils-make-progress-bar/
		 */
		$progress = \WP_CLI\Utils\make_progress_bar( 'Migrating Posts', $count );

		$i = 0;

		while ( $i < $count ) {

			foreach ( $json as $import_post ) {

				if ( true === $this->debug ) {
					WP_CLI::warning( 'find me ' . print_r( json_decode( $import_post ), 1 ) );
				}

				$i++;

				if ( true === $this->debug ) {
					// @codingStandardsIgnoreStart
					WP_CLI::log( 'post: ' . print_r( $import_post, true ) );
					// @codingStandardsIgnoreEnd
				}

				/**
				 * Debug info for feataured image
				 */
				if ( true === $this->debug ) {
					if ( isset( $import_post->_links->{'wp:featuredmedia'} ) ) {
						// @codingStandardsIgnoreStart
						WP_CLI::log( WP_CLI::colorize( '%Gpost featured media%n: ' . print_r( $import_post->_links->{'wp:featuredmedia'}, true ) ) );
						// @codingStandardsIgnoreEnd
					} else {
						WP_CLI::log( WP_CLI::colorize( '%RNo featured image%n' ) );
					}

					// @codingStandardsIgnoreStart
					WP_CLI::log( print_r( $import_post, true ) );
					// @codingStandardsIgnoreEnd

				}

				/**
				 * Checking if we have a feautured image set on from the remote endpoint
				 */
				if ( isset( $import_post->_links->{'wp:featuredmedia'}[0] ) && isset( $import_post->_links->{'wp:featuredmedia'}[0]->href ) ) {

					require_once( __DIR__ . '/../inc/class-wpcli-migration-attachment.php' ); // Loading our class that handles migrating media / attachments.

					if ( true === $this->debug ) {
						WP_CLI::log( $import_post->_links->{'wp:featuredmedia'}[0]->href );
					}

					$upload_featured_image = new WPCLI_Migration_Attachment();
					$featured_image_id = $upload_featured_image->upload_featured_image( $import_post->_links->{'wp:featuredmedia'}[0]->href );

					if ( true === $this->debug ) {
						WP_CLI::log( 'featured_image_id: ' . $featured_image_id );
					}
				}

				/**
				 * Checking if our post already exists
				 */
				$status_check = get_posts(
					array(
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
					)
				);

				/**
				 * If our post does not exist we will create it here
				 */
				if ( ! isset( $status_check[0] ) || empty( $status_check[0] ) ) {

					/**
					 * Importing a User / Author
					 */
					$new_user = new WPCLI_Migration_User( $this->debug );
					$new_user = $new_user->check_create_user( $import_post );

					if ( 1 !== intval( $this->skip_images ) ) {

						/**
						 * Checking for in-content images
						 *
						 * @var Rendered post_content
						 */
						preg_match_all( '#(https?://[-a-zA-Z./0-9_]+(jpg|gif|png|jpeg))#', $import_post->content->rendered, $matches );

						if ( true === $this->debug && empty( $matches ) ) {
							WP_CLI::log( 'no images found in content' );
						}

						/**
						 * We have in-content images, migrating them here
						 */
						if ( ! empty( $matches[1] ) ) {
							require_once( __DIR__ . '/../inc/class-wpcli-migration-attachment.php' ); // Loading our class that handles migrating media / attachments.

							if ( true === $this->debug ) {
								// @codingStandardsIgnoreStart
								WP_CLI::log( 'These image URLs need to be updated. ' . print_r( $matches[1], true ) );
								// @codingStandardsIgnoreEnd
							}

							/**
							 * Sending the array of in-content images to our Attachment Mirgation class
							 *
							 * $post_content is returned to allow for updating of asset URLs and is used below as seen in
							 * wp_insert_post
							 */
							$post_content = new WPCLI_Migration_Attachment( $matches[1], $import_post->content->rendered, $this->debug );

							/**
							 * Debugging info which should include local URLs for in-content assets
							 */
							if ( true === $this->debug ) {
								// @codingStandardsIgnoreStart
								WP_CLI::log( 'new content: ' . print_r( $post_content, true ) );
								// @codingStandardsIgnoreEnd
							}
						}
					} // End if().

					/**
					 * Initial import is happening here
					 *
					 * Setting or updating our post content
					 */
					if ( isset( $post_content->post_content ) ) {
						$content = $post_content->post_content;
					} else {
						$content = $import_post->content->rendered;
					}

					$migration_check = wp_insert_post(
						array(
							'post_author' => $new_user,
							'post_date' => $import_post->date,
							'post_date_gmt' => $import_post->date_gmt,
							'post_content' => $content ?: '',
							'post_title' => ! empty( $import_post->title->rendered ) ? $import_post->title->rendered : 'no title',
							'post_excerpt' => ! empty( $import_post->excerpt->rendered ) ? $import_post->excerpt->rendered : '',
							'post_type' => $import_post->type,
							'post_name' => '',
							'post_modified' => $import_post->modified,
							'post_modified_gmt' => '',
							'post_status' => 'publish',
							'comment_status' => 'closed',
							'ping_status' => 'open',
							'post_category' => array(),
							'meta_input' => array(
								'content_origin' => $import_post->_links->self[0]->href,
								'content_origin_id' => $import_post->id,
								'_thumbnail_id' => ! empty( $featured_image_id ) ? intval( $featured_image_id ) : '',
							),
						)
					);

					if ( true === $this->debug ) {
						if ( false !== $migration_check ) {
							WP_CLI::log( 'Migrated Post ID: ' . $migration_check );
						} else {
							WP_CLI::error( 'Failed migration of post', false ); // Setting false here not to kill the migration loop.
						}
					}

					/**
					 * Checking and assigning Terms
					 */
					if ( isset( $import_post->_links->{'wp:term'}[0] ) && isset( $import_post->_links->{'wp:term'}[0]->href ) && ! empty( $migration_check ) ) {
						WPCLI_Migration_Helper::initiate_terms( $migration_check, $import_post->_links->{'wp:term'}[0]->href, $this->debug );
					}
				} else {

					/**
					 * If nothing has changed for a post we should skip all this
					 *
					 * @todo make sure none of this fires if a post has not changed
					 */

					/**
					 * Post Updating happens here
					 */
					if ( true === $this->debug ) {
						WP_CLI::log( "updating post, need to check on author \n\n" );
						WP_CLI::log( 'Post ' . $status_check[0] . ' already exists, updating' );
					}

					$local_post = get_post( $status_check[0] );

					/**
					 * Setting out local post information
					 *
					 * @var array
					 */
					$local_post_check = array();
					$local_post_check['post_content'] = $local_post->post_content;
					$local_post_check['post_title'] = $local_post->post_title;
					if ( 0 === intval( $local_post->post_author ) ) {
						$local_post->post_author = 1;
					}
					$local_post_check['post_author'] = intval( $local_post->post_author );

					/**
					 * Setting our remote post information
					 */
					$remote_post = array();

					// @codingStandardsIgnoreStart
					/**
					 * @todo need to write an additional method to check if a user already exists
					 */
					$author = wp_remote_get( $import_post->_links->author[0]->href );
					// @codingStandardsIgnoreEnd
					$author = json_decode( $author['body'] );

					/**
					 * Getting our local user via user meta
					 */
					$local_user = WPCLI_Migration_User::local_user( $author, $this->debug );

					if ( empty( $local_user && ! is_wp_error( $local_user ) ) ) {

						WP_CLI::warning( 'no local user with origin id: ' . $author->id . ' we will create them' );

						$email = WPCLI_Migration_Helper::email_check( $author );

						$local_user = wp_insert_user(
							array(
								'user_login' => $author->name,
								'user_name' => $author->name,
								'user_pass' => wp_generate_password( 12, false ),
								'user_email' => $email,
							)
						);

						/**
						 * We should use update_user_attribute() here rather using add_user_meta
						 * than though it is only available on VIP.
						 */
						if ( is_wp_error( $local_user ) ) {
							continue;
						}
						// @codingStandardsIgnoreStart
						add_user_meta( intval( $local_user ), 'origin_id', $author->id );
						// @codingStandardsIgnoreEnd

						/**
						 * Getting our local user via user meta
						 */
						$local_user = WPCLI_Migration_User::local_user( $author->id );

					} // End if empty local_user

					/**
					 * Leaving this all in place for future work on comparing post changes
					 */
					// @codingStandardsIgnoreStart
					// $remote_post['ID'] = $status_check[0]; // Faking that the remote post has the same ID as the local post
					// $remote_post['post_date'] = $post->date;
					// $remote_post['post_date_gmt'] = $post->date_gmt;
					$remote_post['post_content'] = $import_post->content->rendered;
					$remote_post['post_title'] = $import_post->title->rendered;
					// $remote_post['post_excerpt'] = $post->excerpt->rendered;
					// $remote_post['post_type'] = $post->type;
					// $remote_post['post_name'] = '';
					// $remote_post['post_modified'] = $post->modified;
					$remote_post['post_author'] = $local_user->ID;
					// $remote_post['post_status'] = 'publish';
					// $remote_post['comment_status'] = $post->comment_status;
					// $remote_post['ping_status'] = $post->ping_status;
					// $remote_post['post_name'] = $post->slug;
					// $remote_post['post_modified_gmt'] = $post->date_gmt;
					// error_log( 'remote post: ' . print_r( $post, true ) );
					// @codingStandardsIgnoreEnd

					/**
					 * Checking the difference between our local and remote post content
					 */
					$diff = array_diff( (array) $local_post_check, $remote_post );

					/**
					 * Array of term objects associated with local post
					 */
					$local_post_terms = wp_get_post_terms( $status_check[0], 'category' );

					/**
					 * Array of term ids associated with the remote post
					 */
					$remote_post_terms = array();
					if ( ! empty( $import_post->categories )  ) {
						$remote_post_terms = $import_post->categories;
					}

					/**
					 * Checking if the remote post has a different term count than our local post
					 *
					 * If so, clearing out local post terms and re adding them
					 *
					 * $status_check[0] is our local post ID
					 */
					$term_diff = WPCLI_Migration_Helper::term_diff_check( $status_check[0], $local_post_terms, $remote_post_terms );

					/**
					 * If we dont have a diff nothing has changed and we can skip the current object
					 */
					if ( 0 === count( $diff ) && false === $term_diff ) {
						if ( true === $this->debug ) {
							WP_CLI::log( 'Nothing has changed with remote post: ' . $remote_post['post_title'] );
						}
						continue;
					} else {
						/**
						 * The remote post has changed, we will update the local one here
						 */
						preg_match_all( '#(https?://[-a-zA-Z./0-9_]+(jpg|gif|png|jpeg))#', $import_post->content->rendered, $matches );

						if ( ! empty( $matches[1] ) ) {
							require_once( __DIR__ . '/../inc/class-wpcli-migration-attachment.php' ); // Loading our class that handles migrating media / attachments.
							$post_content = new WPCLI_Migration_Attachment( $matches[1], $import_post->content->rendered, $this->debug );
						}

						/**
						 * Updating posts if they already exist
						 *
						 * @todo  need some type of check if the post has actually changed here
						 */

						if ( isset( $post_content->post_content ) ) {
							$content = $post_content->post_content;
						} else {
							$content = $import_post->content->rendered;
						}

						if ( empty( $content ) ) {
							WP_CLI::warning( 'no content 2' );
						}

						$migration_check = wp_insert_post(
							array(
								'ID' => $status_check[0], // This is the existing post ID.
								'post_author' => $local_user->ID,
								'post_date' => $import_post->date,
								'post_date_gmt' => $import_post->date_gmt,
								'post_content' => $content,
								'post_title' => $import_post->title->rendered,
								'post_excerpt' => $import_post->excerpt->rendered,
								'post_type' => $import_post->type,
								'post_name' => '',
								'post_modified' => $import_post->modified,
								'post_status' => 'publish',
								'meta_input' => array(
									'content_origin' => $import_post->_links->self[0]->href,
									'content_origin_id' => $import_post->id,
								),
							)
						);

						if ( false !== $migration_check ) {
							WP_CLI::log( 'migration check result for: ' . $status_check[0] . ' ' . $migration_check );
							WP_CLI::log( 'Post ' . WP_CLI::colorize( '%G' . $import_post->title->rendered . '%n' ) . ' with ID ' . $status_check[0] . ' has been updated' );
						}
					}// End if().

					/**
					 * Debug info for Terms
					 */
					if ( true === $this->debug ) {
						if ( isset( $import_post->_links->{'wp:term'}[0] ) ) {
							// @codingStandardsIgnoreStart
							WP_CLI::log( WP_CLI::colorize( '%GTerms%n: ' . print_r( $import_post->_links->{'wp:term'}[0], true ) ) );
							// @codingStandardsIgnoreEnd
						} else {
							WP_CLI::log( WP_CLI::colorize( '%RNo Terms found%n' ) );
						}
					}

					/**
					 * Checking and assigning Terms
					 */
					if ( isset( $import_post->_links->{'wp:term'}[0] ) && isset( $import_post->_links->{'wp:term'}[0]->href ) && ! empty( $migration_check ) ) {
						WPCLI_Migration_Helper::initiate_terms( $migration_check, $import_post->_links->{'wp:term'}[0]->href, $this->debug );
					}
				} // End if().

				$progress->tick();
			} // End foreach().
		} // End while().

		$progress->finish();
	}

} // END class
