<?php

/* thePlatform Video Manager Wordpress Plugin
  Copyright (C) 2013-2014  thePlatform for Media Inc.

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

if ( !class_exists( 'ThePlatform_API' ) ) {
	require_once( dirname( __FILE__ ) . '/thePlatform-API.php' );
}

if ( !isset( $tp_api ) ) {
	$tp_api = new ThePlatform_API;
}

if ( !isset( $account ) ) {
	$account = get_option( TP_ACCOUNT_OPTIONS_KEY );
}

add_action( 'wp_ajax_startUpload', 'ThePlatform_Proxy::startUpload' );
add_action( 'wp_ajax_uploadStatus', 'ThePlatform_Proxy::uploadStatus' );
add_action( 'wp_ajax_publishMedia', 'ThePlatform_Proxy::publishMedia' );
add_action( 'wp_ajax_cancelUpload', 'ThePlatform_Proxy::cancelUpload' );
add_action( 'wp_ajax_uploadFragment', 'ThePlatform_Proxy::uploadFragment' );
add_action( 'wp_ajax_establishSession', 'ThePlatform_Proxy::establishSession' );

/**
 * This class is responsible for uploading and publishing Media to MPX
 */
class ThePlatform_Proxy {

	public static function check_nonce_and_permissions( $action = "") {
		if ( empty( $action ) ) {
			check_admin_referer( 'theplatform-ajax-nonce' );
		}
		else {			
			check_admin_referer( 'theplatform-ajax-nonce-' . $action);
		}
		
		$tp_uploader_cap = apply_filters( TP_UPLOADER_CAP, TP_UPLOADER_DEFAULT_CAP );
		if ( !current_user_can( $tp_uploader_cap ) ) {
			wp_die( 'You do not have sufficient permissions to modify MPX Media' );
		}
	}
	
	public static function check_theplatform_proxy_response( $response ) {
		
		if ( is_wp_error( $response ) ) {
			wp_send_json_error( $response->get_error_message() );
		}
	
		if ( isset( $response['data'] ) && $response['data'] === false ) {
			wp_send_json_error( $response['status']['http_code'] );
		}
		
		wp_send_json_success( theplatform_decode_json_from_server( $response, TRUE, FALSE ) );				
	}

	/**
	 * Initiate a file upload
	 * @return mixed JSON response or instance of WP_Error
	 */
	public static function startUpload() {
		ThePlatform_Proxy::check_nonce_and_permissions( $_POST['action'] );

		$url = $_POST['upload_base'] . '/web/Upload/startUpload';
		$url .= '?schema=1.1';
		$url .= '&token=' . $_POST['token'];
		$url .= '&account=' . urlencode( $_POST['account_id'] );
		$url .= '&_guid=' . $_POST['guid'];
		$url .= '&_mediaId=' . $_POST['media_id'];
		$url .= '&_filePath=' . urlencode( $_POST['file_name'] );
		$url .= '&_fileSize=' . $_POST['file_size'];
		$url .= '&_mediaFileInfo.format=' . $_POST['format'];
		$url .= '&_serverId=' . urlencode( $_POST['server_id'] );

		$response = ThePlatform_API_HTTP::put( esc_url_raw( $url ) );

		ThePlatform_Proxy::check_theplatform_proxy_response( $response );
	}

	/**
	 * Retrieve the current status of a file upload
	 * @return mixed JSON response or instance of WP_Error
	 */
	public static function uploadStatus() {
		ThePlatform_Proxy::check_nonce_and_permissions( $_POST['action'] );

		$url = $_POST['upload_base'] . '/data/UploadStatus';
		$url .= '?schema=1.0';
		$url .= '&account=' . urlencode( $_POST['account_id'] );
		$url .= '&token=' . $_POST['token'];
		$url .= '&byGuid=' . $_POST['guid'];
		
		$response = ThePlatform_API_HTTP::get( esc_url_raw( $url ) );

		ThePlatform_Proxy::check_theplatform_proxy_response( $response );
	}

	/**
	 * Publish an uploaded media asset using the 'Wordpress' profile
	 * @return mixed JSON response or instance of WP_Error
	 */
	public static function publishMedia() {
		ThePlatform_Proxy::check_nonce_and_permissions( $_POST['action'] );

		if ( $_POST['profile'] == 'wp_tp_none' ) {
			wp_send_json_success();
		} 
		
		$profileUrl = TP_API_PUBLISH_PROFILE_ENDPOINT;
		$profileUrl .= '&byTitle=' . urlencode( $_POST['profile'] );		
		$profileUrl .= '&token=' . $_POST['token'];
		$profileUrl .= '&account=' . urlencode( $_POST['account_id'] );

		$profileResponse = ThePlatform_API_HTTP::get( esc_url_raw( $profileUrl ) );

		$content = theplatform_decode_json_from_server( $profileResponse, TRUE );

		if ( $content['entryCount'] == 0 ) {
			wp_send_json_error( "No Publishing Profile Found" );
		}

		$profileId = $content['entries'][0]['id'];
		$mediaId = $_POST['media_id'];

		$publishUrl = TP_API_PUBLISH_BASE_URL;
		$publishUrl .= '&token=' . $_POST['token'];
		$publishUrl .= '&account=' . urlencode( $_POST['account_id'] );
		$publishUrl .= '&_mediaId=' . urlencode( $mediaId );
		$publishUrl .= '&_profileId=' . urlencode( $profileId );

		$response = ThePlatform_API_HTTP::get( esc_url_raw ( $publishUrl ), array( "timeout" => 120 ) );

		ThePlatform_Proxy::check_theplatform_proxy_response( $response );
	}

	/**
	 * Cancel a file upload process
	 * @return mixed JSON response or instance of WP_Error
	 */
	public static function cancelUpload() {
		ThePlatform_Proxy::check_nonce_and_permissions( $_POST['action'] );
		
		//Send a cancel request to the upload endpoint
		$uploadUrl = $_POST['upload_base'] . '/web/Upload/cancelUpload?schema=1.1';
		$uploadUrl .= '&token=' . $_POST['token'];
		$uploadUrl .= '&account=' . urlencode( $_POST['account_id'] );
		$uploadUrl .= '&_guid=' . $_POST['guid'];
		$uploadResponse = ThePlatform_API_HTTP::put( esc_url_raw( $uploadUrl ) );

		theplatform_decode_json_from_server( $uploadResponse, TRUE );
		
		//Send a delete media request to FMS
		$deleteUrl = TP_API_MEDIA_DELETE_ENDPOINT;
		$deleteUrl .= '&byGuid=' . $_POST['guid'];
		$deleteUrl .= '&token=' . $_POST['token'];
		$deleteUrl .= '&account=' . urlencode( $_POST['account_id'] );
		$deleteResponse = ThePlatform_API_HTTP::get( esc_url_raw( $deleteUrl ) );

		ThePlatform_Proxy::check_theplatform_proxy_response( $deleteResponse );
	}
}