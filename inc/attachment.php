<?php
/**
 * Class for handling moving attachment assets
 *
 * @package wpcli-migration-script
 */

/**
 * Class for handling moving attachment assets
 *
 * @package default
 * @author
 **/
class WPCLI_Migration_Attachment {

	/**
	 * Placeholder for media URLs
	 *
	 * @var array
	 */
	private $media;



	/**
	 * Placeholder for post content
	 *
	 * @var string
	 */
	public $post_content;

	/**
	 * Placeholder propert for debug parameter
	 *
	 * @var string
	 */
	private $debug;

	/**
	 * Our construct
	 *
	 * @param array   $media array of media URLs
	 * @param string  $post_content string value of post_content
	 * @param boolean $debug true|false based on the wpcli request argument of --migrate_debug=<true|false>
	 */
	public function __construct( $media = '', $post_content = '', $debug = '' ) {
		$this->media = $media;
		$this->post_content = $post_content;
		$this->debug = $debug;

		/**
		 *
		 */
		$this->upload( $this->media, $this->post_content );
	}


	/**
	 * Checking if a local file with the same name as a remote file has already be imported
	 *
	 * @param  string $media_file URL of remote image to check against
	 * @param  string $sub_dir    Current sub directory of local WordPress media uploads
	 * @return integer            Media Post ID if found, else false
	 */
	private function media_file_check( $media_file, $sub_dir = '' ) {

		/**
		 * Checking that we actually have a valid asset to retrieve
		 */
		$file_check = get_headers( $media_file );

		if ( false === strpos( $file_check[0], '404' ) ) {

			if ( empty( $sub_dir ) ) {
				// http://wordpress.stackexchange.com/questions/50123/image-upload-from-url
				$uploaddir = wp_upload_dir();
				$sub_dir = preg_replace( '#(^/)#' , '', $uploaddir['subdir'] );
			}

			$media_check = get_posts( array(
				'suppress_filters' => false,
				'post_type' => 'attachment',
				'posts_per_page' => 1,
				'fields' => 'ids',
				'meta_query' => array(
					array(
						'key' => '_wp_attached_file',
						'value' => $sub_dir . '/' . basename( $media_file ),
					),
				),
			) );

			if ( true == $this->debug && ! empty( $media_check[0] ) ) {

				WP_CLI::log( 'media file already exists with id: ' . print_r( $media_check, true ) );

			}
		} else {

			WP_CLI::warning( 'Missing image file: ' . $media_file );
			$media_check = false;

		}

		return $media_check;

	}

	public function upload( $media ) {

		// http://wordpress.stackexchange.com/questions/50123/image-upload-from-url
		$uploaddir = wp_upload_dir();

		if ( true == $this->debug ) {
			WP_CLI::log( 'upload dir: ' . print_r( $uploaddir, true ) );
		}

		$sub_dir = preg_replace( '#(^/)#' , '', $uploaddir['subdir'] );

		if ( true == $this->debug ) {
			WP_CLI::log( 'sub_dir: ' . $sub_dir );
		}

		/**
		 * Making sure we have in-content images else bailing here
		 * this situation arises when uploaded featured images
		 */
		if ( empty( $media ) ) {
			return;
		}

		foreach ( $media as $media_file ) {

			if ( true == $this->debug ) {
				WP_CLI::log( 'Attachment meta value to check if it already exists: ' . $sub_dir . '/' . basename( $media_file ) );
			}

			$media_file_check = $this->media_file_check( $media_file, $sub_dir );

			/**
			 * Actually importing our images if they do not current exist
			 */
			if ( empty( $media_file_check ) && false !== $media_file_check ) {

				/**
				 * If our media file does not exist we create / import it here
				 */

				if ( true == $this->debug ) {
					WP_CLI::log( 'uploading file: ' . basename( $media_file ) );
				}

				$uploadfile = $uploaddir['path'] . '/' . basename( $media_file );

				$contents = file_get_contents( $media_file );
				$savefile = fopen( $uploadfile, 'w' );
				fwrite( $savefile, $contents );
				fclose( $savefile );

				$wp_filetype = wp_check_filetype( basename( $media_file ), null );

				$attachment = array(
					'post_mime_type' => $wp_filetype['type'],
					'post_title' => basename( $media_file ),
					'post_content' => '',
					'post_status' => 'inherit',
				);

				$attach_id = wp_insert_attachment( $attachment, $uploadfile );

				$imagenew = get_post( $attach_id );
				$fullsizepath = get_attached_file( $imagenew->ID );
				$attach_data = wp_generate_attachment_metadata( $attach_id, $fullsizepath );
				wp_update_attachment_metadata( $attach_id, $attach_data );

				$img_url = wp_get_attachment_url( $imagenew->ID );

				if ( true == $this->debug ) {

					WP_CLI::log( 'source URL: ' . $media_file );

					WP_CLI::log( 'We have a new image: ' . print_r( $imagenew, true ) );

					// $img_url = wp_get_attachment_url( $imagenew->ID );
					WP_CLI::log( 'image full url: ' . print_r( $img_url, true ) );

					// WP_CLI::log( 'post content: ' . print_r( $this->post_content, true ) );
				}

				if ( ! empty( $this->post_content ) ) {

					// error_log( 'media file: ' . print_r( $media_file, true ) );
					// error_log( 'img url: ' . print_r( $img_url, true ) );
					$this->post_content = preg_replace( '#' . $media_file . '#', $img_url, $this->post_content );

				} else {
					continue;
				}
			} // End if we are uploading the image.

			// return $post_content;
		} // End foreach media_file

		return $this->post_content;

	} // End method upload


	public function upload_featured_image( $img_json_url ) {

		if ( true == $this->debug ) {

			error_log( 'upload featuered image is firing for: ' . $img_json_url );
		}

		$img_url = wp_remote_get( $img_json_url );
		$img_url = json_decode( $img_url['body'] );
		$img_url = $img_url->source_url;

		// error_log( 'img json: ' . print_r( $img_url->source_url, true ) );
		$media_file_check = $this->media_file_check( $img_url );

		if ( empty( $media_file_check ) && false !== $media_file_check ) {
			// http://wordpress.stackexchange.com/questions/50123/image-upload-from-url
			$uploaddir = wp_upload_dir();

			$uploadfile = $uploaddir['path'] . '/' . basename( $img_url );

			$contents = file_get_contents( $img_url );
			$savefile = fopen( $uploadfile, 'w' );
			fwrite( $savefile, $contents );
			fclose( $savefile );

			$wp_filetype = wp_check_filetype( basename( $img_url ), null );

			$attachment = array(
				'post_mime_type' => $wp_filetype['type'],
				'post_title' => basename( $img_url ),
				'post_content' => '',
				'post_status' => 'inherit',
			);

			$attach_id = wp_insert_attachment( $attachment, $uploadfile );

			$imagenew = get_post( $attach_id );
			$fullsizepath = get_attached_file( $imagenew->ID );
			$attach_data = wp_generate_attachment_metadata( $attach_id, $fullsizepath );
			wp_update_attachment_metadata( $attach_id, $attach_data );

			return $attach_id;
		}

	}

} // END class
