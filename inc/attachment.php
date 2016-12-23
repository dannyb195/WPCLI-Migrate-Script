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




	public $post_content;

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
	public function __construct( $media, $post_content, $debug ) {
		$this->media = $media;
		$this->post_content = $post_content;
		$this->debug = $debug;

		if ( true == $this->debug && ! empty( $media ) ) {
			WP_CLI::log( print_r( $this->media, true ) );
		}

		/**
		 *
		 */
		$this->upload( $this->media, $this->post_content );
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


		foreach ( $media as $media_file ) {

			if ( true == $this->debug ) {
				WP_CLI::log( 'Attachment meta value to check: ' . $sub_dir . '/' . basename( $media_file ) );
			}


			/**
			 * Checking if our media file already exists
			 * @var [type]
			 */
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
				/**
				 * If our media file does not exist we create / import it here
				 */

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

				$img_url = wp_get_attachment_url( $imagenew->ID );

				if ( true == $this->debug ) {

					WP_CLI::log( 'source URL: ' . $media_file );

					WP_CLI::log( 'We have a new image: ' . print_r( $imagenew, true ) );

					// $img_url = wp_get_attachment_url( $imagenew->ID );

					WP_CLI::log( 'image full url: ' . print_r( $img_url, true ) );

					// WP_CLI::log( 'post content: ' . print_r( $this->post_content, true ) );

				}

				if ( ! empty( $this->post_content ) ) {

					error_log( 'media file: ' . print_r( $media_file, true ) );

					error_log( 'img url: ' . print_r( $img_url, true ) );



					$this->post_content = preg_replace( '#' . $media_file . '#', $img_url, $this->post_content );

					// $this->post_content = 'working';


					// WP_CLI::log( 'new content: ' . print_r( $this->post_content ) );

				} else {
					continue;
				}






			} // End else.

			// return $post_content;

		} // End foreach media_file



		return $this->post_content;

	} // End method upload




} // END class