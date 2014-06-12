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


/**
 * Validate the allow/omit dropdown options
 * @param array $input Passed by Wordpress, an Array of upload/metadata options
 * @return array A clean copy of the array, invalid values will be returned as "omit"
 */
function theplatform_dropdown_options_validate( $input ) {
	foreach ( $input as $key => $value ) {
		if ( !in_array( $value, array( 'read', 'write', 'hide' ) ) ) {
			$input[$key] = "hide";
		}
	}
	return $input;
}

/**
 * Validate MPX Account Settings for invalid input
 * @param array $input Passed by Wordpress, an Array of MPX options
 * @return array A cleaned up copy of the array, invalid values will be cleared.
 */
function theplatform_account_options_validate ( $input ) {	
	$tp_api = new ThePlatform_API;
	$defaults = array(
		'mpx_account_id' => '',
		'mpx_username' => 'mpx/',
		'mpx_password' => '',
		'mpx_account_pid' => '',
		'mpx_region' => 'us'
	);		
	
	if ( !is_array( $input ) || $input['mpx_username'] === 'mpx/' ) {
		return $defaults;
	}
			
	$account_is_verified = $tp_api->internal_verify_account_settings();
	if ( $account_is_verified ) {
		$region_is_verified = $tp_api->internal_verify_account_region();
		
		if ( strpos( $input['mpx_account_id'], '|' ) !== FALSE ) {
			$ids = explode( '|', $input['mpx_account_id'] );			
			$input['mpx_account_id'] = $ids[0];
			$input['mpx_account_pid'] = $ids[1];
		}

		if ( strpos( $input['mpx_region'], '|' ) !== FALSE ) {
			$ids = explode( '|', $input['mpx_region'] );
			$input['mpx_region'] = $ids[0];
		}

	}
	
	foreach ($input as $key => $value) {
		$input[$key] = sanitize_text_field($value);
	}
	
	// If username, account id, or region have changed, reset settings to default	
	$old_preferences = get_option( 'theplatform_account_options' );			
	if ( $old_preferences ) {		
		$updates = false;
		// If the username changes, reset all preferences except user/pass
		if ( theplatform_setting_changed( $old_preferences['mpx_username'], $input['mpx_username'] ) ) {
			$input['mpx_region'] = $defaults['mpx_region'];
			$input['mpx_account_pid'] = $defaults['mpx_account_pid'];
			$input['mpx_account_id'] = $defaults['mpx_account_id'];
			$updates = true;
		}

		// If the region changed, reset all preferences, but keep the new account settings
		if ( theplatform_setting_changed( $old_preferences['mpx_region'], $input['mpx_region'] ) ) {		
			$updates = true;
		}

		// If the account changed, reset all preferences, but keep the new account settings
		if ( theplatform_setting_changed( $old_preferences['mpx_account_id'], $input['mpx_account_id'] ) ) {
			$updates = true;
		}
		// Clear old options
		if ( $updates ) {			
			delete_option( 'theplatform_preferences_options' );
			delete_option( 'theplatform_metadata_options' );
			delete_option( 'theplatform_upload_options' );
		}
	}
		
	return $input;
}

function theplatform_setting_changed( $oldValue, $newValue ) {
	if ( !isset( $oldValue ) && !isset( $newValue ) ){
		return FALSE;
	}
	
	if ( empty( $oldValue ) && empty( $newValue ) ){
		return FALSE;
	}
	
	if ( $oldValue !== $newValue ) {
		return TRUE;
	}
	
	return FALSE;
}
/**
 * Validate MPX Settings for invalid input
 * @param array $input Passed by Wordpress, an Array of MPX options
 * @return array A cleaned up copy of the array, invalid values will be cleared.
 */
function theplatform_preferences_options_validate( $input ) {	
	$tp_api = new ThePlatform_API;
	$defaults = array(
		'embed_tag_type' => 'embed',
		'default_player_name' => '',
		'default_player_pid' => '',
		'mpx_server_id' => 'DEFAULT_SERVER',
		'default_publish_id' => 'tp_wp_none',
		'user_id_customfield' => '(None)',
		'filter_by_user_id' => 'FALSE',
		'autoplay' => 'TRUE',
		'rss_embed_type' => 'article',
		'default_width' => $GLOBALS['content_width'],
		'default_height' => ($GLOBALS['content_width'] / 16) * 9
	);

	
	$account_is_verified = $tp_api->internal_verify_account_settings();	
	if ( $account_is_verified ) {
		$region_is_verified = $tp_api->internal_verify_account_region();		
		
		if ( isset( $input['default_player_name'] ) && strpos( $input['default_player_name'], '|' ) !== FALSE ) {
			$ids = explode( '|', $input['default_player_name'] );
			$input['default_player_name'] = $ids[0];
			$input['default_player_pid'] = $ids[1];
		}
		
		// If the account is selected, but no player has been set, use the first
		// returned as the default.
		if (  !isset( $input['default_player_name'] ) || empty( $input['default_player_name'] ) )  {			
			if ( $region_is_verified ) {				
				$players = $tp_api->get_players();
				$player = $players[0];				
				$input['default_player_name'] = $player['title'];
				$input['default_player_pid'] = $player['pid'];
			} else {
				$input['default_player_name'] = '';
				$input['default_player_pid'] = '';
			}
		}
		
		// If the account is selected, but no upload server has been set, use the first
		// returned as the default.
		if ( !isset( $input['mpx_server_id'] ) || empty ( $input['mpx_server_id'] ) ) {			
				$input['mpx_server_id'] = 'DEFAULT_SERVER';			
		}
		
		foreach ( $input as $key => $value ) {
			if ( $key == 'videos_per_page' || $key === 'default_width' || $key === 'default_height' ) {
				$input[$key] = intval( $value );
			} else {
				$input[$key] = sanitize_text_field( $value );
			}
		}
	}	

	return $input;
}

/**
 * 	AJAX callback for account verification button
 */
function theplatform_verify_account_settings() {
	//User capability check
	check_admin_referer( 'theplatform-ajax-nonce' );
	$hash = $_POST['auth_hash'];

	$response = ThePlatform_API_HTTP::get( TP_API_SIGNIN_URL, array( 'headers' => array( 'Authorization' => 'Basic ' . $hash ) ) );

	$payload = theplatform_decode_json_from_server( $response, TRUE );

	if ( !array_key_exists( 'isException', $payload ) ) {
		$account_is_verified = TRUE;
		echo "success";
	} else {
		$account_is_verified = FALSE;
		echo "failed";
	}

	die();
}

/**
 * 	Catch JSON decode errors
 */
function theplatform_decode_json_from_server( $input, $assoc, $die_on_error = TRUE ) {

	$response = json_decode( wp_remote_retrieve_body( $input ), $assoc );

	if ( FALSE === $die_on_error ) {
		return $response;
	}

	if ( is_null( $response ) && wp_remote_retrieve_response_code( $input ) != "200" ) {
		wp_die( '<p>' . __( 'There was an error getting data from MPX, if the error persists please contact thePlatform.' ) . '</p>' );
	}

	if ( is_null( $response ) && wp_remote_retrieve_response_code( $input ) == "200" ) {
		return $response;
	}

	if ( is_wp_error( $response ) ) {
		wp_die( '<p>' . __( 'There was an error getting data from MPX, if the error persists please contact thePlatform. ' . esc_html( $response->get_error_message() ) ) . '</p>' );
	}

	if ( array_key_exists( 'isException', $response ) ) {
		wp_die( '<p>' . __( 'There was an error getting data from MPX, if the error persists please contact thePlatform. ' . esc_html( $response['description'] ) ) . '</p>' );
	}

	return $response;
}

function theplatform_get_query_fields( $metadata ) {
	$metadata_options = get_option( 'theplatform_metadata_options' );
	$upload_options = get_option( 'theplatform_upload_options' );

	$fields = 'id,defaultThumbnailUrl,content';

	foreach ( $upload_options as $upload_field => $val ) {
		if ( $val == 'hide' ) {
			continue;
		}

		$field_title = (strstr( $upload_field, '$' ) !== false) ? substr( strstr( $upload_field, '$' ), 1 ) : $upload_field;
		if ( !empty( $fields ) ) {
			$fields .= ',';
		}
		$fields .= $field_title;
	}

	foreach ( $metadata_options as $custom_field => $val ) {
		if ( $val == 'hide' ) {
			continue;
		}

		$metadata_info = NULL;
		foreach ( $metadata as $entry ) {
			if ( array_search( $custom_field, $entry ) ) {
				$metadata_info = $entry;
				break;
			}
		}

		if ( is_null( $metadata_info ) ) {
			continue;
		}

		$field_title = $metadata_info['fieldName'];

		if ( empty( $fields ) ) {
			$fields .= ':';
		} else {
			$fields .= ',:';
		}

		$fields .= $field_title;
	}

	return $fields;
}
