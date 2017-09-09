<?php
/**
 * Place holder file for now
 */

/**
 * undocumented class
 *
 * @package default
 * @author
 **/
class WPCLI_Migration_Menus {

	/**
	 * [$menus_json_endpoint description]
	 *
	 * @var [type]
	 */
	private $menus_json_endpoint;

	/**
	 * [__construct description]
	 *
	 * @param [type] $user_args [description]
	 */
	public function __construct( $user_args ) {
		if ( isset( $user_args['json_url'] ) && ! empty( $user_args['json_url'] ) ) {
			$this->menus_json_endpoint = $user_args['json_url'];
			$this->update_menus_endpoint( $this->menus_json_endpoint );
		} else {
			WP_CLI::error( 'WordPress menus can only be migrated using a WP REST API endpoint' );
		}
	} // End __construct

	private function update_menus_endpoint( $url ) {

		/**
		 * Changing our URL to hit the nav_menu endpoint.
		 * This requires installing https://github.com/dannyb195/WPCLI-Migrate-Script-Source-Site
		 * on the source site to open nav_menu objects to the JSON API
		 */
		preg_match( '#(?:http.*/v2/)(.*)?(?)#', $url, $matches );
		$url = str_replace( $matches['1'], 'nav_menu', $matches[0] );

		/**
		 * Making sure we have a valid url
		 * @var string
		 */
		$headers = get_headers( $url );
		if ( strpos( $headers[0], '200' ) > -1 ) {
			WP_CLI::success( 'We have a valid menus JSON endpoint' );
			$menus = wp_remote_get( esc_url( $url ) );
			$menus = json_decode( $menus['body'] );
		} else {
			WP_CLI::error( 'Something went wrong, please ensure you have https://github.com/dannyb195/WPCLI-Migrate-Script-Source-Site installed on the remote / source site' );
		}

		/**
		 * Creating our menus
		 */
		$this->create_menus( $menus );

	} // End $update_menus_endpoint

	private function create_menus( $menus ) {

		/**
		 * Looping through our menus to create them
		 */
		if ( ! empty( $menus ) ) {
			foreach ( $menus as $menu ) {
				$menu_term = wp_insert_term(
					$menu->name, 'nav_menu', array(
						// 'parent' => '', Placeholder for now
						'slug' => $menu->slug,
					)
				);

				if ( ! is_wp_error( $menu_term ) ) {
					WP_CLI::success( 'Created menu ' . $menu->name );
				}

				/**
				 *
				 */
				$this->add_menu_items( $menu->menu_items, $menu_term );

			}
		} // End if().

	} // End create_menus

	private function add_menu_items( $menu_items, $menu_term ) {

		/**
		 * If the menu already exists $menu_term will be a WP_Error object
		 */
		if ( is_wp_error( $menu_term ) ) {
			$menu_id = $menu_term->error_data['term_exists'];
		} else {
			$menu_id = $menu_term['term_id'];
		}

		foreach ( $menu_items as $menu_item ) {

			if ( ! empty( $menu_item->title ) ) {

				/**
				 * PHP Notice:  Trying to get property of non-object in /srv/www/vanilla-php/wp-includes/nav-menu.php on line 427
				 * Notice: Trying to get property of non-object in /srv/www/vanilla-php/wp-includes/nav-menu.php on line 427
				 * PHP Notice:  Trying to get property of non-object in /srv/www/vanilla-php/wp-includes/nav-menu.php on line 428
				 * Notice: Trying to get property of non-object in /srv/www/vanilla-php/wp-includes/nav-menu.php on line 428
				 *
				 * These notices seem to be thrown because the menu items do not actually exist on the recieving site, or have different IDs
				 * Most likely it is line 426 in the above file. I need to figure out how to associate the things that the menu items actually
				 * need to link to
				 *
				 * @todo  Fix this ^
				 */

				/**
				 * @link http://wordpress.stackexchange.com/questions/44736/programmatically-add-a-navigation-menu-and-menu-items
				 *
				 * Coding standard ignore is in place just for the time being
				 */
				// @codingStandardsIgnoreStart
				$item = wp_update_nav_menu_item(
					$menu_id, 0, array(
						// 'post_title' =>$menu_item->post_title,
						// 'post_type' => 'nav_menu_item',
						'menu-item-object-id' => 0,
						'menu-item-object' => $menu_item->object,
						'menu-item-position' => $menu_item->menu_order,
						'menu-item-type' => $menu_item->type,
						'menu-item-title' => $menu_item->title,
						'menu-item-parent-id' => 0,
						// 'menu-item-url' => '',
						// 'menu-item-description' => '',
						// 'menu-item-attr-title' => '',
						// 'menu-item-target' => '',
						// 'menu-item-classes' => '',
						// 'menu-item-xfn' => '',
						// 'menu-item-status' => '',
					)
				);
				// @codingStandardsIgnoreEnd
			}// End if().

			if ( ! is_wp_error( $item ) ) {
				WP_CLI::success( 'Create menu item with ID of: ' . $item );
			} else {
				WP_CLI::warning( 'Menu creation failed' );
			}
		}// End foreach().

	} // End add_menu_items


} // END class
