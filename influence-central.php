<?php
/**
 * Plugin Name: Influence Central
 * Plugin URI: http://influencecentral.ca
 * Description: For members of the Influence Central community
 * Version: 1.0
 * Author: Influence Central, Inc.
 * Author URI: http://influencecentral.ca
 * License: GPLv2 or later
 */

defined('ABSPATH') or die("Don't call this script directly!");

define('WP_IC_COMET_VERSION', '1.0');

/*  Copyright 2015 Influence Central, Inc.

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
*/

/**
 * WPICComet is the class that handles ALL of the plugin functionality.
 * It helps us avoid name collisions
 * http://codex.wordpress.org/Writing_a_Plugin#Avoiding_Function_Name_Collisions
 */
class WPICComet {

	/**
	 * @var WPICComet - Static property to hold our singleton instance
	 */
	static $instance = false;

	static $page_slug = 'wp-ic-comet';

	/**
	 * This is our constructor, which is private to force the use of get_instance()
	 * @return void
	 */
	private function __construct() {
		add_action( 'admin_init',               array( $this, 'admin_init' ) );
		add_action( 'admin_menu',               array( $this, 'admin_menu' ) );
		add_action( 'get_footer',               array( $this, 'insert_code' ) );
		add_filter( 'plugin_action_links',      array( $this, 'add_plugin_page_links' ), 10, 2 );
	}

 	/**
	 * Function to instantiate our class and make it a singleton
	 */
	public static function get_instance() {
		if ( !self::$instance )
			self::$instance = new self;
		return self::$instance;
	}
	
	
	/**
	 * This adds the settings link to the entry on the Plugins listing page
	 */
	public function add_plugin_page_links( $links, $file ){
		if ( plugin_basename( __FILE__ ) == $file ) {
			$link = '<a href="' . admin_url( 'options-general.php?page=' . self::$page_slug ) . '">Settings</a>';
			array_unshift( $links, $link );
		}
		return $links;
	}

	/**
	 * This adds the options page for this plugin to the Options page
	 */
	public function admin_menu() {
		add_options_page('Influence Central', 'Influence Central', 'manage_options', self::$page_slug, array( $this, 'settings_view' ) );
	}

	/**
	 * Register our settings
	 */
	public function admin_init() {

		register_setting( 'ic_comet', 'ic_comet', array( $this, 'sanitize_general_options' ) );

		add_settings_section( 'ic_comet_general', false, '__return_false', 'ic_comet' );
		add_settings_field( 'code', 'Influence Central ID:', array( $this, 'field_code' ), 'ic_comet', 'ic_comet_general' );
	}

	/**
	 * Where the user adds their IC code
	 */
	public function field_code() {
		echo '<input name="ic_comet[code]" id="ic_comet-code" type="text" value="' . esc_attr( $this->_get_options( 'code' ) ) . '" />';
		echo '<p class="description">Paste your Influence Central ID into the field.</p>';
	}

	/**
	 * Sanitize all of the options associated with the plugin
	 */
	public function sanitize_general_options( $in ) {

		$out = array();

		// The actual tracking ID
		if ( preg_match( '#[A-Za-z0-9]{6}#', $in['code'], $matches ) )
			$out['code'] = $matches[0];
		else
			$out['code'] = '';

		return $out;
	}

	/**
	 * This is used to display the options page for this plugin
	 */
	public function settings_view() {
?>
		<div class="wrap">
			<img src="<?php echo plugins_url( 'ic_logo.png', __FILE__ );?>"/>
			<p>Thank you for installing the Influence Central member plugin! All you need to do now is enter your ID into the field below. Easy peasy!</p>
			<p>If you don't yet have a member ID, simply <a href="http://influence-central.com" target="_blank">visit our site</a> to join our community.</p>
			<form action="options.php" method="post" id="wp_ic_comet">
				<?php
					settings_fields( 'ic_comet' );
					do_settings_sections( 'ic_comet' );
					submit_button('Save');
				?>
			</form>
		</div>
<?php
	}

	/**
	 * Maybe output or return, depending on the context
	 */
	private function _output_or_return( $val, $maybe ) {
		if ( $maybe )
			echo $val . "\r\n";
		else
			return $val;
	}

	/**
	 * This injects the IC code into the footer of the page.
	 *
	 * @param bool[optional] $output - defaults to true, false returns but does NOT echo the code
	 */
	public function insert_code( $output = true ) {
		//If $output is not a boolean false, set it to true (default)
		$output = ($output !== false);

		$tracking_id = $this->_get_options( 'code' );
		if ( empty( $tracking_id ) )
			return $this->_output_or_return( '<!-- Your Influence Central plugin is missing the ID. Visit your WordPress Settings to correct it. -->', $output );

		// Build the code snippet
		$snippet = "<!-- Start Influence Central Tag --><noscript><iframe height=\"0\" src=\"//www.googletagmanager.com/ns.html?id=GTM-" . $tracking_id . "\" style=\"display:none;visibility:hidden\" width=\"0\"></iframe></noscript><script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src='//www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);})(window,document,'script','dataLayer','GTM-" . $tracking_id . "');</script><!-- End Influence Central Tag -->";

		return $this->_output_or_return( $snippet, $output );
	}

	/**
	 * Used to get one or all of our plugin options
	 *
	 * @param string[optional] $option - Name of options you want.  Do not use if you want ALL options
	 * @return array of options, or option value
	 */
	private function _get_options( $option = null, $default = false ) {

		$o = get_option('ic_comet');

		if (isset($option)) {

			if (isset($o[$option])) {
				if ( 'code' == $option ) {
					if ( preg_match( '#[A-Za-z0-9]{6}#', $o[$option], $matches ) )
						return $matches[0];
					else
						return '';
				} else
					return $o[$option];
			} else {
				return $default;
			}
		} else {
			return $o;
		}
	}

}

global $wp_ic_comet;
$wp_ic_comet = WPICComet::get_instance();