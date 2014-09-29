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

add_action( 'wp_ajax_publishMedia', 'ThePlatform_Proxy::publishMedia' );
add_action( 'wp_ajax_startUpload', 'ThePlatform_Proxy::upload' );

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

    public static function upload() {               
        $action = $_POST['action'];
        // ThePlatform_Proxy::check_nonce_and_permissions( $action );

        $data = array();
        $method = strtolower( $_POST['method'] );
        $url = $_POST['url'];        
        $returnsValue = $_POST['returnsValue'];
        if ( FALSE === empty ( $_FILES ) ) {
            $file = $_FILES['file'];            
            $data = $file;         
        }
        

        switch ( $method ) {
            case 'put':
                $response = ThePlatform_API_HTTP::put( $url, $data );
                break;
            case 'get':
                $response = ThePlatform_API_HTTP::get( $url );
                break;
            case 'post':
                $response = ThePlatform_API_HTTP::post( $url );
                break;
            default:
                # code...
                break;
        }
        
        if ( $returnsValue ) {
            ThePlatform_Proxy::check_theplatform_proxy_response ( $response );
        }
        
        wp_send_json_success();
    }

	


}