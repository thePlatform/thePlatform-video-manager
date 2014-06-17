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

	public static function check_nonce_and_permissions() {
		check_admin_referer( 'theplatform-ajax-nonce' );
		$tp_uploader_cap = apply_filters( TP_UPLOADER_CAP, TP_UPLOADER_DEFAULT_CAP );
		if ( !current_user_can( $tp_uploader_cap ) ) {
			wp_die( 'You do not have sufficient permissions to modify MPX Media' );
		}
	}

	/**
	 * Initiate a file upload
	 * @return mixed JSON response or instance of WP_Error
	 */
	public static function startUpload() {
		ThePlatform_Proxy::check_nonce_and_permissions();

		$ret = array();

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

		$response = ThePlatform_API_HTTP::put( $url );

		if ( is_wp_error( $response ) ) {
			$ret['success'] = 'false';
			$ret['code'] = $response->get_error_message();
			wp_send_json( $ret );
		}

		if ( isset( $response['data'] ) && $response['data'] === false ) {
			$ret['success'] = 'false';
			$ret['code'] = $response['status']['http_code'];
		} else {
			$ret['success'] = 'true';
		}

		wp_send_json( $ret );
	}

	/**
	 * Retrieve the current status of a file upload
	 * @return mixed JSON response or instance of WP_Error
	 */
	public static function uploadStatus() {
		ThePlatform_Proxy::check_nonce_and_permissions();

		$ret = array();

		$url = $_POST['upload_base'] . '/data/UploadStatus';
		$url .= '?schema=1.0';
		$url .= '&account=' . urlencode( $_POST['account_id'] );
		$url .= '&token=' . $_POST['token'];
		$url .= '&byGuid=' . $_POST['guid'];

		$response = ThePlatform_API_HTTP::get( $url );

		if ( is_wp_error( $response ) ) {
			$ret['success'] = 'false';
			$ret['code'] = $response->get_error_message();
			wp_send_json( $ret );
		}

		if ( isset( $response['data'] ) && $response['data'] === false ) {
			$ret['success'] = 'false';
			$ret['code'] = $response['status']['http_code'];
		} else {
			$ret['success'] = 'true';
			$ret['content'] = theplatform_decode_json_from_server( $response, TRUE );
		}

		wp_send_json( $ret );
	}

	/**
	 * Publish an uploaded media asset using the 'Wordpress' profile
	 * @return mixed JSON response or instance of WP_Error
	 */
	public static function publishMedia() {
		ThePlatform_Proxy::check_nonce_and_permissions();

		$ret = array();
		if ( !isset( $account ) ) {
			$account = get_option( TP_ACCOUNT_OPTIONS_KEY );
		}

		$url = TP_API_PUBLISH_PROFILE_ENDPOINT;
		if ( $_POST['profile'] == 'wp_tp_none' ) {
			die();
		} else {
			$url .= '&byTitle=' . urlencode( $_POST['profile'] );
		}
		$url .= '&token=' . $_POST['token'];
		$url .= '&account=' . $account['mpx_account_id'];

		$response = ThePlatform_API_HTTP::get( $url );

		if ( is_wp_error( $response ) ) {
			$ret['success'] = 'false';
			$ret['code'] = $response->get_error_message();
			wp_send_json( $ret );
		}

		if ( $response['data'] === false ) {
			$ret['success'] = 'false';
			$ret['code'] = $response['status']['http_code'];
		} else {
			$content = theplatform_decode_json_from_server( $response, TRUE );

			if ( $content['entryCount'] == 0 ) {
				$ret['success'] = 'false';
				$ret['code'] = 'No Publishing Profile Found.';
				wp_send_json( $ret );
			}

			$profileId = $content['entries'][0]['id'];
			$mediaId = $_POST['media_id'];

			$url = TP_API_PUBLISH_BASE_URL;
			$url .= '&token=' . $_POST['token'];
			$url .= '&account=' . urlencode( $_POST['account_id'] );
			$url .= '&_mediaId=' . urlencode( $mediaId );
			$url .= '&_profileId=' . urlencode( $profileId );

			$response = ThePlatform_API_HTTP::get( $url, array( "timeout" => 120 ) );

			if ( is_wp_error( $response ) ) {
				$ret['success'] = 'false';
				$ret['code'] = $response->get_error_message();
				wp_send_json( $ret );
			}

			if ( isset( $response['data'] ) && $response['data'] === false ) {
				$ret['success'] = 'false';
				$ret['code'] = 'Unable to publish media.';
				wp_send_json( $ret );
			}

			$content = theplatform_decode_json_from_server( $response, TRUE );

			$ret['content'] = $content['publishResponse']['profileResultId'];
			$ret['success'] = 'true';
		}

		wp_send_json( $ret );
	}

	/**
	 * Cancel a file upload process
	 * @return mixed JSON response or instance of WP_Error
	 */
	public static function cancelUpload() {
		ThePlatform_Proxy::check_nonce_and_permissions();

		$ret = array();

		$url = $_POST['upload_base'] . '/web/Upload/cancelUpload?schema=1.1';
		$url .= '&token=' . $_POST['token'];
		$url .= '&account=' . urlencode( $_POST['account_id'] );
		$url .= '&_guid=' . $_POST['guid'];

		$response = ThePlatform_API_HTTP::put( $url );

		if ( is_wp_error( $response ) ) {
			$ret['success'] = 'false';
			$ret['code'] = $response->get_error_message();
			wp_send_json( $ret );
		}

		if ( $response['data'] == false ) {
			$ret['success'] = 'false';
			$ret['code'] = 'Unable to cancel upload.';
			wp_send_json( $ret );
		} else {
			$url = TP_API_MEDIA_DELETE_ENDPOINT;
			$url .= '&byGuid=' . $_POST['guid'];
			$url .= '&token=' . $_POST['token'];
			$url .= '&account=' . urlencode( $_POST['account_id'] );

			sleep( 30 );

			$response = ThePlatform_API_HTTP::get( $url );

			if ( is_wp_error( $response ) ) {
				$ret['success'] = 'false';
				$ret['code'] = $response->get_error_message();
				wp_send_json( $ret );
			}

			$content = theplatform_decode_json_from_server( $response, TRUE );
			$ret['success'] = 'true';
		}

		wp_send_json( $ret );
	}

	/**
	 * Retrieve the current publishing status of a newly uploaded media asset
	 * @return mixed JSON response or instance of WP_Error
	 */
	public static function establishSession() {
		ThePlatform_Proxy::check_nonce_and_permissions();

		$ret = array();

		$url = $_POST['url'];

		$response = ThePlatform_API_HTTP::get( $url );

		die( "OK" ); //doesn't matter what we return here
	}
}