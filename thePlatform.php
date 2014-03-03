<?php
/*
Plugin Name: thePlatform Video Manager
Plugin URI: http://theplatform.com/
Description: Manage video assets hosted in thePlatform MPX from within WordPress.
Version: 1.2.0
Author: thePlatform for Media, Inc.
Author URI: http://theplatform.com/
License: GPL2

Copyright 2013 thePlatform for Media, Inc.

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License, version 2, as 
published by the Free Software Foundation.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

if ( ! defined( 'ABSPATH' ) ) exit;


/**
 * Placeholder for the plugin activation hook.
 * @return type
 */
function tp_activation_hook() {	}
register_activation_hook(__FILE__, 'tp_activation_hook' );
$preferences_options_key = 'theplatform_preferences_options';
$metadata_options_key = 'theplatform_metadata_options';
$upload_options_key = 'theplatform_upload_options';

/**
 * Main class
 * @package default
 */
class ThePlatform_Plugin {

	var $plugin_base_dir;
	var $plugin_base_url;

	/*
	 * WP Option key
	 */
	private $plugin_options_key = 'theplatform';

	/**
	 * Initialize plugin
	 */
	function &init() {
		static $instance = false;

		if ( !$instance ) {
			$instance = new ThePlatform_Plugin;
		}

		return $instance;
	}

	/**
	 * Constructor
	 */
	function __construct() {	
		require_once(dirname(__FILE__) . '/thePlatform-API.php' );
		require_once(dirname(__FILE__) . '/thePlatform-helper.php' );
		require_once( dirname( __FILE__ ) . '/thePlatform-proxy.php' );
			
		$this->tp_api = new ThePlatform_API;
				
		$this->plugin_base_dir = plugin_dir_path(__FILE__);
		$this->plugin_base_url = plugins_url('/', __FILE__);
		
		if (is_admin()) {						
			add_action('admin_menu', array(&$this, 'add_admin_page'));
			add_action('admin_init', array(&$this, 'register_scripts'));		
			add_action('media_buttons', array(&$this, 'theplatform_embed_button'), 100);	
			add_action('wp_ajax_initialize_media_upload', array($this->tp_api, 'initialize_media_upload'));
			add_action('wp_ajax_get_subaccounts', array($this->tp_api, 'get_subaccounts'));
			add_action('wp_ajax_theplatform_embed', array(&$this, 'embed')); 	
			add_action('wp_ajax_theplatform_upload', array(&$this, 'upload'));	
			add_action('wp_ajax_theplatform_edit', array(&$this, 'edit'));	
			add_action('wp_ajax_get_categories', array($this->tp_api, 'get_categories'));
			add_action('wp_ajax_get_videos', array($this->tp_api, 'get_videos'));	
		}	
		add_shortcode('theplatform', array(&$this, 'shortcode'));
	}
	
	/**
	 * Calls the Embed template in an IFrame and Dialog
	 * @return void
	 */
	function embed() {
		require_once( $this->plugin_dir . 'thePlatform-embed.php' );
		die();
	}

	/**
	 * Calls the Embed template in an IFrame and Dialog
	 * @return void
	 */
	function edit() {

		require_once($this->plugin_dir . 'thePlatform-uploader.php?media=' . $_GET['media'] );
		die();
	}

	/**
	 * Calls the Upload Window template in a popup
	 * @return void
	 */
	function upload() {
		require_once( $this->plugin_dir . 'thePlatform-upload-window.php' );
		die();
	}

	/**
	 * Registers javascripts and css
	 */
	function register_scripts() {		
		wp_register_script('theplatform_js', plugins_url('/js/theplatform.js', __FILE__), array('jquery'));
		wp_register_script('theplatform_uploader_js', plugins_url('/js/theplatform-uploader.js', __FILE__), array('jquery', 'theplatform_js'));		
		wp_register_script('mpxhelper_js', plugins_url('/js/mpxHelper.js', __FILE__), array('jquery'));
		wp_register_script('mediaview_js', plugins_url('/js/mediaView.js', __FILE__), array('jquery', 'holder', 'mpxhelper_js', 'theplatform_js'));
		wp_register_script('holder', plugins_url('/js/holder.js', __FILE__));
		wp_register_script('bootstrap_js', plugins_url('/js/bootstrap.min.js', __FILE__), array('jquery'));
		wp_register_script('pdk_external_controller', "http://pdk.theplatform.com/pdk/tpPdkController.js");
		wp_register_script('infiniscroll_js', plugins_url('/js/jquery.infinitescroll.min.js', __FILE__), array('jquery'));

		wp_localize_script('theplatform_js', 'theplatform', array(
			'ajax_url' => admin_url( 'admin-ajax.php' ),
			'plugin_base_url' => plugins_url('images/', __FILE__),
			'tp_nonce' => wp_create_nonce('theplatform-ajax-nonce')			
		));

		wp_localize_script('mpxhelper_js', 'localscript', array(
			'ajaxurl' => admin_url( 'admin-ajax.php' ),			
			'tp_nonce' => wp_create_nonce('theplatform-ajax-nonce')			
		));	

		wp_register_style('theplatform_css', plugins_url('/css/thePlatform.css', __FILE__ ));		
		wp_register_style('bootstrap_tp_css', plugins_url('/css/bootstrap_tp.min.css', __FILE__ ));
	}
	
	/**
	 * Calls the Media Manager template
	 * @return type
	 */
	function media_page() {
		require_once( dirname( __FILE__ ) . '/thePlatform-media.php' );
	}

	/**
	 * Calls the Media Manager template
	 * @return type
	 */
	function upload_page() {
		require_once( dirname( __FILE__ ) . '/thePlatform-uploader.php' );
	}

	/**
	 * Add admin page 
	 */
	function add_admin_page() {		
		$tp_admin_cap = apply_filters('tp_admin_cap', 'manage_options');
		$tp_viewer_cap = apply_filters('tp_viewer_cap', 'edit_posts');
		$tp_uploader_cap = apply_filters('tp_uploader_cap', 'upload_files');
		$slug = 'theplatform';		
		add_menu_page('thePlatform', 'thePlatform', $tp_viewer_cap, $slug, array( &$this, 'media_page' ), 'dashicons-video-alt3', 11);
		add_submenu_page($slug, 'thePlatform Video Browser', 'Browse MPX Media', $tp_viewer_cap, $slug, array( &$this, 'media_page' ));
		add_submenu_page($slug, 'thePlatform Video Uploader', 'Upload Media to MPX', $tp_uploader_cap, 'theplatform-uploader', array( &$this, 'upload_page' ));
		add_submenu_page($slug, 'thePlatform Plugin Settings', 'Settings', $tp_admin_cap, 'theplatform-settings', array( &$this, 'admin_page' ) );
	}

	/**
	 * Calls the plugin's options page template
	 * @return type
	 */
	function admin_page() {		
		require_once(dirname(__FILE__) . '/thePlatform-options.php' );	
	}
	
	/**
	 * Adds thePlatform media embed button to the media upload
	 */
	function theplatform_embed_button() {
		global $post_ID, $temp_ID;
		$iframe_post_id = (int) ( 0 == $post_ID ? $temp_ID : $post_ID );
		$title = 'Embed Video from thePlatform';
		$image_url = plugins_url('/images/embed_button.png', __FILE__);
 		$site_url = admin_url("admin-ajax.php?post_id=$iframe_post_id&action=theplatform_embed&embed=true"); 
		echo '<a href="#" class="button tp-embed" title="' . esc_attr($title) . '"><div id="tp-embed-dialog"></div><img src="' . esc_url($image_url) . '" alt="' . esc_attr($title) . '" width="20" height="20" />thePlatform</a>';

		echo '<script type="text/javascript">jQuery(".tp-embed").click(function() {jQuery("#tp-embed-dialog").html(\'<iframe src="' . $site_url . '" height="100%" width="100%">\').dialog({dialogClass: "wp-dialog", modal: true, resizable: true, minWidth: 1024, width: 1200, height: 1024}).css("overflow-y","hidden");});</script>';				
	}	
	
	/**
	 * Shortcode Callback
	 * @param array $atts Shortcode attributes
	 */
	function shortcode( $atts ) {
		if ( ! class_exists( 'ThePlatform_API' ) )
			require_once( dirname(__FILE__) . '/thePlatform-API.php' );
	
		extract(shortcode_atts(array(
			'width' => '',
			'height' => '',
			'media' => '',
			'player' => '',
			'mute' => '',
			'autoplay' => '',
			'loop' => '',
			'form' => '',
			'params' => ''
			), $atts
		));

		if ( empty($width) )
			$width = $GLOBALS['content_width'];
		if ( empty($width) )
			$width = 500;

		$width = (int) $width;

		if ( empty($height) )
			$height = $GLOBALS['content_height'];
		if ( empty($height) ) {
			$height = floor($width*9/16);
		}
		
		if ( empty($mute) ) {
			$mute = "false";
		}
		
		if ( empty($autoplay) ) {
			$autoplay = "false";
		}
		
		if ( empty($loop) ) {
			$loop = "false";
		}

		if ( empty($form) ) {
			$form = "iframe";
		}

		if ( empty( $media ) )
			return '<!--Syntax Error: Required Media parameter missing. -->';

		if ( empty( $player ) )
			return '<!--Syntax Error: Required Player parameter missing. -->';


		if ( !is_feed() ) {
			$preferences = get_option('theplatform_preferences_options');
			$accountPID = $preferences['mpx_account_pid'];
			$output = $this->get_embed_shortcode($accountPID, $media, $player, $width, $height, $loop, $autoplay, $mute, $form, $params);
			$output = apply_filters('tp_embed_code', $output);							
		} else {
			$output = '[Sorry. This video cannot be displayed in this feed. <a href="'.get_permalink().'">View your video here.]</a>';
		}
		
		return $output;
	}

	/**
	 * Called by the plugin shortcode callback function to construct a media embed iframe.
	 * 
	 * @param string $account_id Account of the user embedding the media asset
	 * @param string $media_id Identifier of the media object to embed
	 * @param string $player_id Identifier of the player to display the embedded media asset in
	 * @param string $player_width The width of the embedded player
	 * @param string $player_height The height of the embedded player
	 * @param boolean $loop Whether or not to loop the embedded media automatically
	 * @param boolean $auto_play Whether or not to autoplay the embedded media asset on page load
	 * @param boolean $mute Whether or not to mute the audio channel of the embedded media asset
	 * @return string An iframe tag sourced from the selected media embed URL
	*/ 
	function get_embed_shortcode($accountPID, $releasePID, $playerPID, $player_width, $player_height, $loop = false, $autoplay = false, $mute = false, $form = "iframe", $params) {

		if (!$this->preferences)
			$this->preferences = get_option($this->preferences_options_key);

		
		$type = $this->preferences['video_type'];		

		if (empty($type))
			$type = 'embed';
		
		
		$url = 'http://player.theplatform.com/p/' . urlencode($accountPID) . '/' . urlencode($playerPID);

		$url = apply_filters('tp_base_embed_url', $url);
		
		if ($type == 'embed') {
			$url .= '/embed';
		}
		$url .= '/select/' . urlencode($releasePID);
		
		$url .= '?width=' . (int)$player_width . '&height=' . (int)$player_height;
		
		if ( $loop != "false" ) {
			$url .= "&loop=true";
		}
		
		if ( $autoplay != "false" ) {
			$url .= "&autoPlay=true";
		}
		
		if ( $mute != "false" ) {
			$url .= "&mute=true";
		}

		if ($params !== '')
			$url .= '&' . $params;
		
		$url = apply_filters('tp_full_embed_url', $url);

		if ($form == "script") {		
			return '<div style="width:' . (int)$player_width . 'px; height:' . (int)$player_height . 'px"><script type="text/javascript" src="' . esc_url($url . "&form=javascript") . '"></script></div>';
		}
		else { //Assume iframe			
			return '<iframe src="' . esc_url($url) . '" height=' . (int)$player_height . ' width=' . (int)$player_width . ' frameBorder="0" seamless="seamless" allowFullScreen></iframe>';
		}	
	}
}

// Instantiate thePlatform plugin on WordPress init
add_action('init', array( 'ThePlatform_Plugin', 'init' ) );
add_action('wp_ajax_verify_account', 'verify_account_settings');
add_action('admin_init', 'register_plugin_settings' );

/**
 * Registers initial plugin settings during initalization
 * @return type
 */
function register_plugin_settings() {
	$preferences_options_key = 'theplatform_preferences_options';
	$metadata_options_key = 'theplatform_metadata_options';
	$upload_options_key = 'theplatform_upload_options';
	register_setting( $preferences_options_key, $preferences_options_key, 'connection_options_validate'); 
	register_setting( $metadata_options_key, $metadata_options_key, 'dropdown_options_validate'); 
	register_setting( $upload_options_key, $upload_options_key, 'dropdown_options_validate'); 
}

