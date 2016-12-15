<?php
/**
 * Place holder file for now
 */

error_log( 'attachments class is firing' );

/**
 * undocumented class
 *
 * @package default
 * @author
 **/
class WPCLI_Migration_Attachment {

	/**
	 * Placeholder for media URLs
	 * @var array
	 */
	private $media;

	/**
	 * Placeholder propert for debug parameter
	 *
	 * @var string
	 */
	private $debug;

	/**
	 * [__construct description]
	 * @param array $media array of media URLs
	 * @param boolean $debug true|false based on the wpcli request argument of --migrate_debug=<true|false>
	 */
	public function __construct( $media, $debug ) {
		$this->media = $media;
		$this->debug = $debug;

		if ( true == $this->debug && ! empty( $media ) ) {
			WP_CLI::log( print_r( $this->media, true ) );
		}

		/**
		 *
		 */
		$this->upload( $this->media );
	}

	private function upload( $media ) {

		// http://wordpress.stackexchange.com/questions/50123/image-upload-from-url
		$uploaddir = wp_upload_dir();

		if ( true == $this->debug ) {
			WP_CLI::log( 'upload dir: ' . print_r( $uploaddir, true ) );
		}




		$sub_dir = preg_replace( '#(^/)#' , '', $uploaddir['subdir'] );

		if ( true == $this->debug ) {
			WP_CLI::log( 'sub_dir: ' . $sub_dir );
		}

		if ( empty( $media_file_check[0] ) ) {

			foreach ( $media as $media_file ) {

				if ( true == $this->debug ) {
					WP_CLI::log( 'Attachment meta value to check: ' . $sub_dir . '/' . basename( $media_file ) );
				}


				$media_file_check = get_posts( array(
					'suppress_filters' => false,
					'post_type' => 'attachment',
					'posts_per_page' => 1,
					'fields' => 'ids',
					'meta_query' => array(
						array(
							'key' => '_wp_attached_file',
							'value' => $sub_dir . '/' . basename( $media_file ),
						)
					),
				) );

				if ( true == $this->debug && ! empty( $media_file_check[0] ) ) {

					WP_CLI::log( 'media file already exists with id: ' . print_r( $media_file_check, true ) );

				} else {

					if ( true == $this->debug ) {
						WP_CLI::log( 'uploading file: ' . basename( $media_file ) );
					}

					$uploadfile = $uploaddir['path'] . '/' . basename( $media_file );

					$contents= file_get_contents( $media_file );
					$savefile = fopen($uploadfile, 'w');
					fwrite($savefile, $contents);
					fclose($savefile);


					$wp_filetype = wp_check_filetype(basename( $media_file ), null );

					$attachment = array(
					    'post_mime_type' => $wp_filetype['type'],
					    'post_title' => basename( $media_file ),
					    'post_content' => '',
					    'post_status' => 'inherit'
					);

					$attach_id = wp_insert_attachment( $attachment, $uploadfile );

					$imagenew = get_post( $attach_id );
					$fullsizepath = get_attached_file( $imagenew->ID );
					$attach_data = wp_generate_attachment_metadata( $attach_id, $fullsizepath );
					wp_update_attachment_metadata( $attach_id, $attach_data );
				} // End else.

			}

		}




	}
} // END class