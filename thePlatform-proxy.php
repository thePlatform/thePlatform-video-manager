<?php

if ( !class_exists( 'ThePlatform_API' ) )
	require_once( dirname(__FILE__) . '/thePlatform-API.php' );

if ( !isset( $tp_api ) )
	$tp_api = new ThePlatform_API;

if ( !isset( $preferences ) ) 
	$preferences = get_option('theplatform_preferences_options');

add_action('wp_ajax_startUpload', 'MPXProxy::startUpload');
add_action('wp_ajax_uploadStatus', 'MPXProxy::uploadStatus');
add_action('wp_ajax_publishMedia', 'MPXProxy::publishMedia');
add_action('wp_ajax_publishStatus', 'MPXProxy::publishStatus');
add_action('wp_ajax_cancelUpload', 'MPXProxy::cancelUpload');
add_action('wp_ajax_uploadFragment', 'MPXProxy::uploadFragment');
add_action('wp_ajax_establishSession', 'MPXProxy::establishSession');

/**
 * This class is responsible for uploading and publishing Media to MPX
 * @package default
 */
class MPXProxy {

	public static function check_nonce_and_permissions() {
		check_admin_referer('theplatform-ajax-nonce');
		$tp_publisher_cap = apply_filters('tp_publisher_cap', 'upload_files');
		if (!current_user_can($tp_publisher_cap)) {
      		wp_die('<p>'.__('You do not have sufficient permissions to modify MPX Media').'</p>');
      	}
	}
	/**
	 * Initiate a file upload
	 *
	 * @return mixed JSON response or instance of WP_Error
	 */
	public static function startUpload() {
		MPXProxy::check_nonce_and_permissions();		

		$ret = array();

		$url = $_POST['upload_base'] . '/web/Upload/startUpload';
		$url .= '?schema=1.1';
		$url .= '&token=' . $_POST['token'];
		$url .= '&account=' . urlencode($_POST['account_id']);
		$url .= '&_guid=' . $_POST['guid'];
		$url .= '&_mediaId=' . $_POST['media_id'];
		$url .= '&_filePath=' . urlencode($_POST['file_name']);
		$url .= '&_fileSize=' . $_POST['file_size'];
		$url .= '&_mediaFileInfo.format=' . $_POST['format'];
		$url .= '&_serverId=' . urlencode($_POST['server_id']);



		$response = ThePlatform_API_HTTP::put($url);

		if ( is_wp_error($response) ) {
			$ret['success'] = 'false';
			$ret['code'] = $response->get_error_message();
			echo json_encode($ret);
			die();
		}

		if ($response['data'] === false) {
			$ret['success'] = 'false';
			$ret['code'] = $response['status']['http_code'];
		} else {
			$ret['success'] = 'true';
		}

		echo json_encode($ret);

		die();
	}

	/**
	 * Retrieve the current status of a file upload
	 *
	 * @return mixed JSON response or instance of WP_Error
	 */
	public static function uploadStatus() {
		MPXProxy::check_nonce_and_permissions();
		
		$ret = array();

		$url = $_POST['upload_base'] . '/data/UploadStatus';
		$url .= '?schema=1.0';
		$url .= '&account=' . urlencode($_POST['account_id']);
		$url .= '&token=' . $_POST['token'];
		$url .= '&byGuid=' . $_POST['guid'];
		
		$response = ThePlatform_API_HTTP::get($url);

		if ( is_wp_error($response) ) {
			$ret['success'] = 'false';
			$ret['code'] = $response->get_error_message();
			echo json_encode($ret);
			die();
		}

		if ($response['data'] === false) {
			$ret['success'] = 'false';
			$ret['code'] = $response['status']['http_code'];
		} else {
			$ret['success'] = 'true';
			$ret['content'] = decode_json_from_server($response, TRUE);
		}

		echo json_encode($ret);
		die();
	}

	/**
	 * Publish an uploaded media asset using the 'Wordpress' profile
	 *
	 * @return mixed JSON response or instance of WP_Error
	 */
	public static function publishMedia() {
		MPXProxy::check_nonce_and_permissions();

		$ret = array();

		$url = TP_API_PUBLISH_PROFILE_ENDPOINT;
		if ($_POST['profile'] == 'wp_tp_none')
			die();
		else
			$url .= '&byTitle=' . urlencode($_POST['profile']);
		$url .= '&token=' . $_POST['token'];							

		$response = ThePlatform_API_HTTP::get($url);		

		if ( is_wp_error($response) ) {
			$ret['success'] = 'false';
			$ret['code'] = $response->get_error_message();
			echo json_encode($ret);
			die();
		}
	
		if ($response['data'] === false) {
			$ret['success'] = 'false';
			$ret['code'] = $response['status']['http_code'];
		} else {
			
			
			$content = decode_json_from_server($response, TRUE);
		
			if ($content['entryCount'] == 0) {
				$ret['success'] = 'false';
				$ret['code'] = 'No Publishing Profile Found.';
				echo json_encode($ret);
				die();
			}
		
			$profileId = $content['entries'][0]['id'];
			$mediaId = $_POST['media_id'];
		
			$url = TP_API_PUBLISH_BASE_URL;
			$url .= '&token=' . $_POST['token'];
			$url .= '&account=' . urlencode($_POST['account_id']); 
			$url .= '&_mediaId=' . urlencode($mediaId);
			$url .= '&_profileId=' . urlencode($profileId); 

			$response = ThePlatform_API_HTTP::get($url);
							
			if ( is_wp_error($response) ) {
				$ret['success'] = 'false';
				$ret['code'] = $response->get_error_message();
				echo json_encode($ret);
				die();
			}
		
			if ($response['data'] === false) {
				$ret['success'] = 'false';
				$ret['code'] = 'Unable to publish media.';
				echo json_encode($ret);
				die();
			}
		
			$content = decode_json_from_server($response, TRUE);
			
			$ret['content'] = $content['publishResponse']['profileResultId'];
			$ret['success'] = 'true';
		}

		echo json_encode($ret);
		die();
	}

	/**
	 * Retrieve the current publishing status of a newly uploaded media asset
	 *
	 * @return mixed JSON response or instance of WP_Error
	 */
	public static function publishStatus() {
		MPXProxy::check_nonce_and_permissions();

		$ret = array();

		$url = TP_API_WORKFLOW_PROFILE_RESULT_ENDPOINT;
		$url .= '&byProfileId=' . urlencode($_POST['profile_result_id']);
		$url .= '&token=' . $_POST['token'];
	
		$response = ThePlatform_API_HTTP::get($url);
		
		if ( is_wp_error($response) ) {
			$ret['success'] = 'false';
			$ret['code'] = $response->get_error_message();
			echo json_encode($ret);
			die();
		}
		
		if ($response['data'] === false) {
			$ret['success'] = 'false';
			$ret['code'] = 'Unable to publish media.';
			echo json_encode($ret);
			die();
		} else {
			$content = decode_json_from_server($response, TRUE);
		
			if (!isset($content['plprofileresult$status'])) {
				$ret['status'] = 'Unknown';
			} else {
				$ret['status'] = $content['plprofileresult$status'];
			}
			$ret['success'] = 'true';
		}
	
		echo json_encode($ret); 
		die();
	}

	/**
	 * Cancel a file upload process
	 *
	 * @return mixed JSON response or instance of WP_Error
	 */
	public static function cancelUpload() {
		MPXProxy::check_nonce_and_permissions();
			
		$ret = array();
	
		$url = $_POST['upload_base'] . '/web/Upload/cancelUpload?schema=1.1';
		$url .= '&token=' . $_POST['token'];
		$url .= '&account=' . urlencode($_POST['account_id']);
		$url .= '&_guid=' . $_POST['guid'];
	
		$response = ThePlatform_API_HTTP::put($url);

		if ( is_wp_error($response) ) {
			$ret['success'] = 'false';
			$ret['code'] = $response->get_error_message();
			echo json_encode($ret);
			die();
		}
	
		if ($response['data'] == false) {
			$ret['success'] = 'false';
			$ret['code'] = 'Unable to cancel upload.';
			echo json_encode($ret);
			die();
		} else {
			$url = TP_API_MEDIA_DELETE_ENDPOINT;
			$url .= '&byGuid=' . $_POST['guid'];
			$url .= '&token=' . $_POST['token'];
			$url .= '&account=' . urlencode($_POST['account_id']);
		
			sleep(30);
		
			$response = ThePlatform_API_HTTP::get($url);
	
			if ( is_wp_error($response) ) {
				$ret['success'] = 'false';
				$ret['code'] = $response->get_error_message();
				echo json_encode($ret);
				die();
			}
	
			$content = decode_json_from_server($response, TRUE);
			$ret['success'] = 'true';
		}
	
		echo json_encode($ret); 
		die();
	}

	/**
	 * Retrieve the current publishing status of a newly uploaded media asset
	 *
	 * @return mixed JSON response or instance of WP_Error
	 */
	public static function establishSession() {
		MPXProxy::check_nonce_and_permissions();

		$ret = array();

		$url = $_POST['url'];
	
		$response = ThePlatform_API_HTTP::get($url);
		
		var_dump($response);	
		die();
	}

	/**
	 * Retrieve the current publishing status of a newly uploaded media asset
	 *
	 * @return mixed JSON response or instance of WP_Error
	 */
	public static function uploadFragment() {
		echo "hi";
		die();
		MPXProxy::check_nonce_and_permissions();

		$ret = array();

		$url = $_POST['url'];
		
		$data = array(
			'data' => $_POST['fragment'],			
			);

	echo "hi";			
		$response = ThePlatform_API_HTTP::put($url, $data);
		
		echo "hi";			
		die();
	}
}