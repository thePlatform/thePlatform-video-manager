<?php 

/**
 * Validate the allow/omit dropdown options
 * @param array $input Passed by Wordpress, an Array of upload/metadata options
 * @return array A clean copy of the array, invalid values will be returned as "omit"
 */
function dropdown_options_validate($input) {	
	foreach ($input as $key => $value) {	
		if ($value != "allow" && $value != "omit") {
			$input[$key] = "omit";
		}			
	}
	return $input;
}

/**
 * Validate MPX Settings for invalid input
 * @param array $input Passed by Wordpress, an Array of MPX options
 * @return array A cleaned up copy of the array, invalid values will be cleared.
 */
function connection_options_validate($input) {	
	if ( ! is_array( $input ) ) 
	{
		return array(
			'mpx_account_id' => '',
			'mpx_username' => 'mpx/',				
			'mpx_password' => '',
			'videos_per_page' => 16,
			'default_sort' => 'id',
			'video_type' => 'embed',				
			'mpx_account_pid' => '',
			'default_player_name' => '',
			'default_player_pid' => '',
			'mpx_server_id' => '',
			'default_publish_id' => '',
			'user_id_customfield' => '',
			'filter_by_user_id' => 'FALSE'
			);;
	}

	if (strpos($input['mpx_account_id'], '|') !== FALSE) {
		$ids = explode('|', $input['mpx_account_id']);
		$input['mpx_account_id'] = $ids[0];
		$input['mpx_account_pid'] = $ids[1];
	}

	if (strpos($input['default_player_name'], '|') !== FALSE) {
		$ids = explode('|', $input['default_player_name']);
		$input['default_player_name'] = $ids[0];
		$input['default_player_pid'] = $ids[1];
	}

	foreach ($input as $key => $value) {
		if ($key == 'videos_per_page') {
			$input[$key] = intval($value);
		}
		else {
			$input[$key] = strval($value);
		}
	}
	return $input;
}	

/**
 *	AJAX callback for account verification button
 */
function verify_account_settings() {
	//User capability check
	check_admin_referer('theplatform-ajax-nonce'); 	
	$hash = $_POST['auth_hash'];

	$response = ThePlatform_API_HTTP::get(TP_API_SIGNIN_URL, array('headers' => array('Authorization' => 'Basic ' . $hash)));

	$payload = decode_json_from_server($response, TRUE);

	if (!array_key_exists('isException', $payload)) {						
		$account_is_verified = TRUE;		
		echo "success";
	} else {						
		$account_is_verified = FALSE;
		echo "failed";
	}				

	die();		
}

/**
 *	Catch JSON decode errors
 */
function decode_json_from_server($input, $assoc, $die_on_error = TRUE) {

	$response = json_decode(wp_remote_retrieve_body($input), $assoc);		

	if (!$die_on_error)
		return $response;

	if (is_null($response) && wp_remote_retrieve_response_code($input) != "200") {						
		wp_die('<p>'.__('There was an error getting data from MPX, if the error persists please contact thePlatform.').'</p>');
	}

	if (is_null($response) && wp_remote_retrieve_response_code($input) == "200") {	
		return $response;
	}

	if ( is_wp_error($response) ) {
		wp_die('<p>'.__('There was an error getting data from MPX, if the error persists please contact thePlatform. ' . $response->get_error_message()).'</p>');

	}

	if (array_key_exists('isException', $response)) {
		wp_die('<p>'.__('There was an error getting data from MPX, if the error persists please contact thePlatform. ' . $response['description']).'</p>');			
	}

	return $response;
}
?>
