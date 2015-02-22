<?php

/* thePlatform Video Manager Wordpress Plugin
  Copyright (C) 2013-2015 thePlatform, LLC

  This program is free software; you can redistribute it and/or modify
  it under the terms of the GNU General Public License as published by
  the Free Software Foundation; either version 2 of the License, or
  (at your option) any later version.

  This program is distributed in the hope that it will be useful,
  but WITHOUT ANY WARRANTY; without even the implied warranty of
  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  GNU General Public License for more details.

  You should have received a copy of the GNU General Public License along
  with this program; if not, write to the Free Software Foundation, Inc.,
  51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA. */



/**
 * This class is responsible for proxying API calls from the UI to PHP
 */
class ThePlatform_Proxy {

	private $tp_api;
	function __construct() {
		add_action( 'wp_ajax_publish_media', array( $this, 'publish_media' ) );
		add_action( 'wp_ajax_revoke_media', array( $this, 'revoke_media' ) );

		add_action( 'wp_ajax_get_categories', array( $this, 'get_categories' ) );
		add_action( 'wp_ajax_get_videos', array( $this, 'get_videos' ) );		
		add_action( 'wp_ajax_get_video_by_id', array( $this, 'get_video_by_id' ) );
		add_action( 'wp_ajax_get_profile_results', array( $this, 'get_profile_results' ) );
		add_action( 'wp_ajax_generate_thumbnail', array( $this, 'generate_thumbnail' ) );
		add_action( 'wp_ajax_initialize_media_upload', array( $this, 'initialize_media_upload' ) );
		
	}

	private function get_api() {
		if ( !isset($this->tp_api)) {
			require_once( dirname( __FILE__ ) . '/thePlatform-API.php' );

			$this->tp_api = new ThePlatform_API();
		}

		return $this->tp_api;
	}

	private function check_nonce_and_permissions( $action = "" ) {
		if ( empty( $action ) ) {
			check_admin_referer( 'theplatform-ajax-nonce' );
		} else {
			// die($action);
			check_admin_referer( 'theplatform-ajax-nonce-' . $action );
		}

		$tp_uploader_cap = apply_filters( TP_UPLOADER_CAP, TP_UPLOADER_DEFAULT_CAP );
		if ( ! current_user_can( $tp_uploader_cap ) ) {
			wp_die( 'You do not have sufficient permissions to modify MPX Media' );
		}
	}

	private function check_theplatform_proxy_response( $response, $returnsValue = false ) {

		// Check if we got an error back and return it
		if ( is_wp_error( $response ) ) {
			wp_send_json_error( $response->get_error_message() );
		}

		if ( isset( $response['data'] ) && $response['data'] === false ) {
			wp_send_json_error( $response['status']['http_code'] );
		}

		$responseBody = wp_remote_retrieve_body( $response );

		// This AJAX call should not return a value, in this case we send a json error with the body to the UI
		if ( ! $returnsValue && ! empty( $responseBody ) ) {
			wp_send_json_error( theplatform_decode_json_from_server( $response, false ) );
		}

		$parsedResponse = theplatform_decode_json_from_server( $response, false );

		wp_send_json_success( $parsedResponse );
	}

	private function proxy_http_request( $data = array() ) {
		$method = strtolower( $_POST['method'] );
		$url    = $_POST['url'];

		switch ( $method ) {
			case 'put':
				$response = ThePlatform_API_HTTP::put( $url, $data );
				break;
			case 'get':
				$response = ThePlatform_API_HTTP::get( $url );
				break;
			case 'post':
				$response = ThePlatform_API_HTTP::post( $url, $data );
				break;
			default:
				$response = array();
				break;
		}

		return $response;
	}

	/**
	 * Publish an uploaded media asset using the 'Wordpress' profile
	 * @return mixed JSON response or instance of WP_Error
	 */
	public function publish_media() {
		$this->check_nonce_and_permissions( $_POST['action'] );

		if ( $_POST['profile'] == 'wp_tp_none' ) {
			wp_send_json_success( "No Publishing Profile Selected" );
		}

		if ( ! isset( $_POST['token]'] ) ) {
			$tp_api = $this->get_api();
			$token  = $tp_api->mpx_signin();
		} else {
			$token = $_POST['token]'];
		}

		$profileId = $_POST['profile'];
		$mediaId   = $_POST['mediaId'];

		$publishUrl = TP_API_PUBLISH_BASE_URL;
		$publishUrl .= '&token=' . $token;
		$publishUrl .= '&account=' . urlencode( $_POST['account'] );
		$publishUrl .= '&_mediaId=' . urlencode( $mediaId );
		$publishUrl .= '&_profileId=' . urlencode( $profileId );

		$response = ThePlatform_API_HTTP::get( esc_url_raw( $publishUrl ), array( "timeout" => 120 ) );

		$this->check_theplatform_proxy_response( $response, true );
	}

	/**
	 * Publish an uploaded media asset using the 'Wordpress' profile
	 * @return mixed JSON response or instance of WP_Error
	 */
	public function revoke_media() {
		$this->check_nonce_and_permissions( $_POST['action'] );

		if ( ! isset( $_POST['token]'] ) ) {
			$tp_api = $this->get_api();
			$token  = $tp_api->mpx_signin();
		} else {
			$token = $_POST['token]'];
		}

		$profileId = $_POST['profile'];
		$mediaId   = $_POST['mediaId'];

		$publishUrl = TP_API_REVOKE_BASE_URL;
		$publishUrl .= '&token=' . $token;
		$publishUrl .= '&account=' . urlencode( $_POST['account'] );
		$publishUrl .= '&_mediaId=' . urlencode( $mediaId );
		$publishUrl .= '&_profileId=' . urlencode( $profileId );

		$response = ThePlatform_API_HTTP::get( esc_url_raw( $publishUrl ), array( "timeout" => 120 ) );

		$this->check_theplatform_proxy_response( $response, true );
	}

	public function get_categories() {
		$this->check_nonce_and_permissions( $_POST['action'] );
		$response = $this->get_api()->get_categories();

		wp_send_json( $response );
	}

	public function get_videos() {
		$this->check_nonce_and_permissions( $_POST['action'] );
		$this->get_api()->get_videos_ajax();		
	}

	public function get_video_by_id() {
		$this->check_nonce_and_permissions( $_POST['action'] );
		$this->get_api()->get_video_by_id_ajax();
	}

	public function get_profile_results() {
		$this->check_nonce_and_permissions( $_POST['action'] );
		$this->get_api()->get_profile_results_ajax();
	}

	public function generate_thumbnail() {
		$this->check_nonce_and_permissions( $_POST['action'] );
		$this->get_api()->generate_thumbnail_ajax();
	}

	public function initialize_media_upload() {
		$this->check_nonce_and_permissions( $_POST['action'] );
		$this->get_api()->initialize_media_upload_ajax();
	}
}

new ThePlatform_Proxy();