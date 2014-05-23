<?php

/*
  Plugin Name: thePlatform Video Manager
  Plugin URI: http://theplatform.com/
  Description: Manage video assets hosted in thePlatform MPX from within WordPress.
  Version: 1.3.0
  Author: thePlatform for Media, Inc.
  Author URI: http://theplatform.com/
  License: GPL2

  Copyright 2013-2014 thePlatform for Media, Inc.

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

if ( !defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Main class
 * @package default
 */
class ThePlatform_Plugin {

	var $plugin_base_dir;
	var $plugin_base_url;
	private static $instance;

	/**
	 * Initialize plugin
	 */
	public static function init() {
		if ( !isset( self::$instance ) ) {
			self::$instance = new ThePlatform_Plugin;
		}

		return self::$instance;
	}

	/**
	 * Constructor
	 */
	function __construct() {
		require_once(dirname( __FILE__ ) . '/thePlatform-URLs.php');
		require_once(dirname( __FILE__ ) . '/thePlatform-API.php');
		require_once(dirname( __FILE__ ) . '/thePlatform-helper.php');
		require_once(dirname( __FILE__ ) . '/thePlatform-proxy.php');

		new ThePlatform_Endpoints( 'theplatform_preferences_options' );
		$this->tp_api = new ThePlatform_API;

		$this->plugin_base_dir = plugin_dir_path( __FILE__ );
		$this->plugin_base_url = plugins_url( '/', __FILE__ );

		if ( is_admin() ) {
			add_action( 'admin_menu', array( $this, 'add_admin_page' ) );
			add_action( 'admin_init', array( $this, 'register_scripts' ) );
			add_action( 'wp_ajax_initialize_media_upload', array( $this->tp_api, 'initialize_media_upload' ) );
			add_action( 'wp_ajax_get_subaccounts', array( $this->tp_api, 'get_subaccounts' ) );
			add_action( 'wp_ajax_theplatform_media', array( $this, 'embed' ) );
			add_action( 'wp_ajax_theplatform_upload', array( $this, 'upload' ) );
			add_action( 'wp_ajax_theplatform_edit', array( $this, 'edit' ) );
			add_action( 'wp_ajax_get_categories', array( $this->tp_api, 'get_categories' ) );
			add_action( 'wp_ajax_get_videos', array( $this->tp_api, 'get_videos' ) );
			add_action( 'wp_ajax_set_thumbnail', array( $this, 'set_thumbnail_ajax' ) );
		}
		add_shortcode( 'theplatform', array( $this, 'shortcode' ) );
	}

	/**
	 * Registers javascripts and css
	 */
	function register_scripts() {
		wp_register_script( 'pdk', "//pdk.theplatform.com/pdk/tpPdk.js" );
		wp_register_script( 'holder', plugins_url( '/js/holder.js', __FILE__ ) );
		wp_register_script( 'bootstrap_js', plugins_url( '/js/bootstrap.min.js', __FILE__ ), array( 'jquery' ) );
		wp_register_script( 'theplatform_js', plugins_url( '/js/theplatform.js', __FILE__ ), array( 'jquery' ) );
		wp_register_script( 'infiniscroll_js', plugins_url( '/js/jquery.infinitescroll.min.js', __FILE__ ), array( 'jquery' ) );
		wp_register_script( 'mpxhelper_js', plugins_url( '/js/mpxHelper.js', __FILE__ ), array( 'jquery' ) );
		wp_register_script( 'theplatform_uploader_js', plugins_url( '/js/theplatform-uploader.js', __FILE__ ), array( 'jquery', 'theplatform_js' ) );
		wp_register_script( 'mediaview_js', plugins_url( '/js/mediaview.js', __FILE__ ), array( 'jquery', 'holder', 'mpxhelper_js', 'theplatform_js', 'pdk', 'infiniscroll_js', 'bootstrap_js' ) );
		wp_register_script( 'field_views', plugins_url( '/js/fieldViews.js', __FILE__ ), array( 'jquery' ) );

		wp_localize_script( 'theplatform_js', 'theplatform', array(
			'ajaxurl' => admin_url( 'admin-ajax.php' ),
			'plugin_base_url' => plugins_url( 'images/', __FILE__ ),
			'tp_nonce' => wp_create_nonce( 'theplatform-ajax-nonce' )
		) );

		wp_localize_script( 'mpxhelper_js', 'localscript', array(
			'ajaxurl' => admin_url( 'admin-ajax.php' ),
			'tp_nonce' => wp_create_nonce( 'theplatform-ajax-nonce' )
		) );

		wp_register_style( 'theplatform_css', plugins_url( '/css/thePlatform.css', __FILE__ ) );
		wp_register_style( 'bootstrap_tp_css', plugins_url( '/css/bootstrap_tp.min.css', __FILE__ ) );
		wp_register_style( 'field_views', plugins_url( '/css/fieldViews.css', __FILE__ ) );
	}

	/**
	 * Add admin pages
	 */
	function add_admin_page() {
		$tp_admin_cap = apply_filters( 'tp_admin_cap', 'manage_options' );
		$tp_viewer_cap = apply_filters( 'tp_viewer_cap', 'edit_posts' );
		$tp_uploader_cap = apply_filters( 'tp_uploader_cap', 'upload_files' );
		$slug = 'theplatform';
		add_menu_page( 'thePlatform', 'thePlatform', $tp_viewer_cap, $slug, array( $this, 'media_page' ), 'dashicons-video-alt3', 11 );
		add_submenu_page( $slug, 'thePlatform Video Browser', 'Browse MPX Media', $tp_viewer_cap, $slug, array( $this, 'media_page' ) );
		add_submenu_page( $slug, 'thePlatform Video Uploader', 'Upload Media to MPX', $tp_uploader_cap, 'theplatform-uploader', array( $this, 'upload_page' ) );
		add_submenu_page( $slug, 'thePlatform Plugin Settings', 'Settings', $tp_admin_cap, 'theplatform-settings', array( $this, 'admin_page' ) );
	}

	/**
	 * Calls the plugin's options page template
	 * @return type
	 */
	function admin_page() {
		require_once(dirname( __FILE__ ) . '/thePlatform-options.php' );
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
		require_once( dirname( __FILE__ ) . '/thePlatform-upload.php' );
	}

	/**
	 * Calls the Embed template in an IFrame and Dialog
	 * @return void
	 */
	function embed() {
		require_once( $this->plugin_base_dir . 'thePlatform-media-browser.php' );
		die();
	}

	/**
	 * Calls the Embed template in an IFrame and Dialog
	 * @return void
	 */
	function edit() {
		$args = array( 'fields' => $_POST['params'], 'custom_fields' => $_POST['custom_params'] );
		$this->tp_api->update_media( $args );
		die();
	}

	/**
	 * Calls the Upload Window template in a popup
	 * @return void
	 */
	function upload() {
		require_once( $this->plugin_base_dir . 'thePlatform-upload-window.php' );
		die();
	}

	function set_thumbnail_ajax() {
		check_admin_referer( 'theplatform-ajax-nonce' );

		global $post_ID;

		if ( !isset( $_POST['id'] ) ) {
			die( "Post ID not found" );
		}

		$post_ID = intval( $_POST['id'] );

		if ( !$post_ID ) {
			die( "Illegal Post ID" );
		}

		$url = isset( $_POST['img'] ) ? $_POST['img'] : '';

		$url = esc_url_raw( $url );

		$thumbnail_id = $this->set_thumbnail( $url, $post_ID );

		if ( $thumbnail_id !== FALSE ) {
			set_post_thumbnail( $post_ID, $thumbnail_id );
			die( _wp_post_thumbnail_html( $thumbnail_id, $post_ID ) );
		}

		//TODO: Better error
		die( "Something went wrong" );
	}

	function set_thumbnail( $url, $post_id ) {
		$file = download_url( $url );

		preg_match( '/[^\?]+\.(jpg|JPG|jpe|JPE|jpeg|JPEG|gif|GIF|png|PNG)/', $url, $matches );
		$file_array['name'] = basename( $matches[0] );
		$file_array['tmp_name'] = $file;

		if ( is_wp_error( $file ) ) {
			unlink( $file_array['tmp_name'] );
			return false;
		}

		$thumbnail_id = media_handle_sideload( $file_array, $post_id );

		if ( is_wp_error( $thumbnail_id ) ) {
			unlink( $file_array['tmp_name'] );
			return false;
		}

		return $thumbnail_id;
	}

	/**
	 * Shortcode Callback
	 * @param array $atts Shortcode attributes
	 */
	function shortcode( $atts ) {
		if ( !class_exists( 'ThePlatform_API' ) ) {
			require_once( dirname( __FILE__ ) . '/thePlatform-API.php' );
		}

		if ( !isset( $this->preferences ) ) {
			$this->preferences = get_option( 'theplatform_preferences_options' );
		}

		list( $width, $height, $media, $player, $mute, $autoplay, $loop, $tag, $params ) = array_values( shortcode_atts( array(
			'width' => '',
			'height' => '',
			'media' => '',
			'player' => '',
			'mute' => '',
			'autoplay' => '',
			'loop' => '',
			'tag' => '',
			'params' => '' ), $atts
				)
		);

		if ( empty( $width ) ) {
			$width = $this->preferences['default_width'];
		}
		if ( strval( $width ) === '0' ) {
			$width = 500;
		}

		$width = (int) $width;

		if ( empty( $height ) ) {
			$height = $this->preferences['default_height'];
		}
		if ( strval( $height ) === '0' ) {
			$height = floor( $width * 9 / 16 );
		}

		if ( empty( $mute ) ) {
			$mute = "false";
		}

		if ( empty( $autoplay ) ) {
			$autoplay = $this->preferences['autoplay'];
		}
		if ( empty( $autoplay ) ) {
			$autoplay = 'false';
		}

		if ( empty( $loop ) ) {
			$loop = "false";
		}

		if ( empty( $tag ) ) {
			$tag = $this->preferences['embed_tag_type'];
		}
		if ( empty( $tag ) ) {
			$tag = "iframe";
		}

		if ( empty( $media ) ) {
			return '<!--Syntax Error: Required Media parameter missing. -->';
		}

		if ( empty( $player ) ) {
			return '<!--Syntax Error: Required Player parameter missing. -->';
		}


		if ( !is_feed() ) {
			$accountPID = $this->preferences['mpx_account_pid'];
			$output = $this->get_embed_shortcode( $accountPID, $media, $player, $width, $height, $autoplay, $tag, $loop, $mute, $params );
			$output = apply_filters( 'tp_embed_code', $output );
		} else {
			switch ( $this->preferences['rss_embed_type'] ) {
				case 'article':
					$output = '[Sorry. This video cannot be displayed in this feed. <a href="' . get_permalink() . '">View your video here.]</a>';
					break;
				case 'iframe':
					$output = $this->get_embed_shortcode( $accountPID, $media, $player, $width, $height, $autoplay, 'iframe', $loop, $mute, $params );
					break;
				case 'script':
					$output = $this->get_embed_shortcode( $accountPID, $media, $player, $width, $height, $autoplay, 'script', $loop, $mute, $params );
					break;
				default:
					$output = '[Sorry. This video cannot be displayed in this feed. <a href="' . get_permalink() . '">View your video here.]</a>';
					break;
			}
			$output = apply_filters( 'tp_rss_embed_code', $output );
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
	function get_embed_shortcode( $accountPID, $releasePID, $playerPID, $player_width, $player_height, $autoplay, $tag, $loop = false, $mute = false, $params ) {

		$url = TP_API_PLAYER_EMBED_BASE_URL . urlencode( $accountPID ) . '/' . urlencode( $playerPID );
		$url .= '/embed/select/' . urlencode( $releasePID );

		$url = apply_filters( 'tp_base_embed_url', $url );

		$url .= '?width=' . (int) $player_width . '&height=' . (int) $player_height;

		if ( $loop !== "false" ) {
			$url .= "&loop=true";
		}

		if ( $autoplay !== "false" ) {
			$url .= "&autoPlay=true";
		}

		if ( $mute !== "false" ) {
			$url .= "&mute=true";
		}

		if ( $params !== '' ) {
			$url .= '&' . $params;
		}

		$url = apply_filters( 'tp_full_embed_url', $url );

		if ( $tag == "script" ) {
			return '<div style="width:' . esc_attr( $player_width ) . 'px; height:' . esc_attr( $player_height ) . 'px"><script type="text/javascript" src="' . esc_url_raw( $url . "&form=javascript" ) . '"></script></div>';
		} else { //Assume iframe			
			return '<iframe src="' . esc_url_raw( $url ) . '" height=' . esc_attr( $player_height ) . ' width=' . esc_attr( $player_width ) . ' frameBorder="0" seamless="seamless" allowFullScreen></iframe>';
		}
	}

}

// Instantiate thePlatform plugin on WordPress init
add_action( 'init', array( 'ThePlatform_Plugin', 'init' ) );
add_action( 'wp_ajax_verify_account', 'verify_account_settings' );
add_action( 'admin_init', 'register_plugin_settings' );
add_action( 'init', 'theplatform_buttonhooks' );

function theplatform_buttonhooks() {
	$tp_embedder_cap = apply_filters( 'tp_embedder_cap', 'edit_posts' );
	if ( current_user_can( $tp_embedder_cap ) ) {
		add_filter( "mce_external_plugins", "theplatform_register_tinymce_javascript" );
		add_filter( 'mce_buttons', 'theplatform_register_buttons' );
	}
}

function theplatform_register_buttons( $buttons ) {
	array_push( $buttons, "|", "theplatform" );
	return $buttons;
}

// Load the TinyMCE plugin : editor_plugin.js (wp2.5)
function theplatform_register_tinymce_javascript( $plugin_array ) {
	$plugin_array['theplatform'] = plugins_url( '/js/theplatform.tinymce.plugin.js', __file__ );
	return $plugin_array;
}

/**
 * Registers initial plugin settings during initalization
 * @return type
 */
function register_plugin_settings() {
	$preferences_options_key = 'theplatform_preferences_options';
	$metadata_options_key = 'theplatform_metadata_options';
	$upload_options_key = 'theplatform_upload_options';
	register_setting( $preferences_options_key, $preferences_options_key, 'connection_options_validate' );
	register_setting( $metadata_options_key, $metadata_options_key, 'dropdown_options_validate' );
	register_setting( $upload_options_key, $upload_options_key, 'dropdown_options_validate' );
}
