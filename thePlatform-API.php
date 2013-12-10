<?php

/**
 * Define MPX endpoints and associated parameters
 */

// XML File containing format definitions
define('TP_API_FORMATS_XML_URL', 'http://web.theplatform.com/descriptors/enums/format.xml');

// Identity Management Service URLs
define('TP_API_ADMIN_IDENTITY_BASE_URL', 'https://identity.auth.theplatform.com/idm/web/Authentication/');
define('TP_API_SIGNIN_URL', TP_API_ADMIN_IDENTITY_BASE_URL . 'signIn?schema=1.0&form=json&_duration=28800000&_idleTimeout=3600000');
define('TP_API_SIGNOUT_URL', TP_API_ADMIN_IDENTITY_BASE_URL . 'signOut?schema=1.0&form=json&_token=');

// Media Data Service URLs
define('TP_API_MEDIA_DATA_BASE_URL', 'http://data.media.theplatform.com/media/data/');
define('TP_API_MEDIA_ENDPOINT', TP_API_MEDIA_DATA_BASE_URL . 'Media?schema=1.5&form=json');
define('TP_API_MEDIA_FILE_ENDPOINT', TP_API_MEDIA_DATA_BASE_URL . 'MediaFile?schema=1.2&form=json');
define('TP_API_MEDIA_FIELD_ENDPOINT', TP_API_MEDIA_DATA_BASE_URL . 'Media/Field?schema=1.3&form=json');
define('TP_API_MEDIA_SERVER_ENDPOINT', TP_API_MEDIA_DATA_BASE_URL . 'Server?schema=1.0&form=json');
define('TP_API_MEDIA_ACCOUNT_SETTINGS_ENDPOINT', TP_API_MEDIA_DATA_BASE_URL . 'AccountSettings?schema=1.5.0&form=json');
define('TP_API_MEDIA_DELETE_ENDPOINT', TP_API_MEDIA_DATA_BASE_URL . 'Media?method=delete');
define('TP_API_MEDIA_RELEASE_ENDPOINT', TP_API_MEDIA_DATA_BASE_URL . 'Release?schema=1.5.0&form=json');
define('TP_API_MEDIA_CATEGORY_ENDPOINT', TP_API_MEDIA_DATA_BASE_URL . 'Category?schema=1.6.0&form=json');

// Player Data Service URLs
define('TP_API_PLAYER_BASE_URL', 'http://data.player.theplatform.com/player/data/');
define('TP_API_PLAYER_PLAYER_ENDPOINT', TP_API_PLAYER_BASE_URL . 'Player?schema=1.3.0&form=json');

// Access Data Service URLs
define('TP_API_ACCESS_BASE_URL', 'http://access.auth.theplatform.com/data/');
define('TP_API_ACCESS_ACCOUNT_ENDPOINT', TP_API_ACCESS_BASE_URL . 'Account?schema=1.3.0&form=json');

// Workflow Data Service URLs
define('TP_API_WORKFLOW_BASE_URL', 'http://data.workflow.theplatform.com/workflow/data/');
define('TP_API_WORKFLOW_PROFILE_RESULT_ENDPOINT', TP_API_WORKFLOW_BASE_URL . 'ProfileResult?schema=1.0&form=json');

// Publish Endpoint
define('TP_API_PUBLISH_BASE_URL', 'http://publish.theplatform.com/web/Publish/publish?schema=1.2&form=json');

// Publish Data Service URLs
define('TP_API_PUBLISH_DATA_BASE_URL', 'http://data.publish.theplatform.com/publish/data/');
define('TP_API_PUBLISH_PROFILE_ENDPOINT', TP_API_PUBLISH_DATA_BASE_URL . 'PublishProfile?schema=1.5.0&form=json');

// FMS URLs
define('TP_API_FMS_BASE_URL', 'http://fms.theplatform.com/web/FileManagement/');
define('TP_API_FMS_GET_UPLOAD_URLS_ENDPOINT', TP_API_FMS_BASE_URL . 'getUploadUrls?schema=1.4&form=json');

/**
 * Wrapper class around cURL HTTP methods
 */ 
class ThePlatform_API_HTTP {

	/**
	 * Perform cURL's GET method
	 *
	 * @param string $url URL to query
	 * @return string|WP_Error Response text or error
	 */
	static function get($url, $data = array()) {
		$url = esc_url_raw($url);
		$response = wp_remote_get($url, $data);		
		return $response;		
	}
	
	/**
	 * Perform cURL's PUT method
	 *
	 * @param string $url URL to query
	 * @param array $data Paylod to accompany the PUT request
	 * @return string|WP_Error Response text or error 
	 */
	static function put($url, $data = array()) {
		return ThePlatform_API_HTTP::post($url, $data, TRUE, 'PUT');		
	}
	
	/**
	 * Perform cURL's POST method
	 *
	 * @param string $url URL to query
	 * @param Array $data Paylod to accompany the PUT request
	 * @param boolean $isJSON Whether or not the request payload is JSON encoded
	 * @return string|WP_Error Response text or error
	 */
	static function post($url, $data, $isJSON = FALSE, $method='POST') {
		$url = esc_url_raw($url);
		$args = array(			
			'method' => $method,
			'body' => $data
			);

		if ($isJSON) {
			$args['headers'] = array('Content-Type' => 'application/json; charset=UTF-8');
		}		
		
		$response = wp_remote_post($url, $args);		

		return $response;	
	}
}

/**
 * A utility class
 */
class ThePlatform_API_Util {
	
	/**
	 * Generate a random GUID string
	 *
	 * @param int $length Length of GUID string to create
	 * @return string GUID
	 */
	static function random_guid($length) {
		$pool = 'abcdefghijklmnopqrstuvwxyz1234567890';
		$ret = '';

	    $pool_size = strlen($pool);

	    for ($i = 0; $i < $length; $i++) {
			$ret .= $pool[mt_rand(1, $pool_size)-1];
		}

	    return $ret;
	}
	
	/**
	 * Convert a MIME type to an MPX-compliant format identifier
	 *
	 * @param string $mime A MIME-type string
	 * @return string MPX-compliant format string
	 */
	static function get_format($mime) {
		
		$response = ThePlatform_API_HTTP::get(TP_API_FORMATS_XML_URL);

		$xmlString = "<?xml version='1.0'?>" . wp_remote_retrieve_body($response);		

		$formats = simplexml_load_string($xmlString);		
				
		foreach ($formats->format as $format) {			
			foreach ($format->mimeTypes->mimeType as $mimetype) {
				if ($mimetype == $mime)
					return $format;
			}
		}
		
		return 'Unknown';
	}
}

class ThePlatform_API {

	private $auth;
	private $token;
	
	// Plugin preferences option table key
	private $preferences_options_key = 'theplatform_preferences_options';

	/**
	 * Class constructor
	 */
	function __construct() {
		$this->preferences = get_option($this->preferences_options_key);	
		
		$this->endpoints['SignIn'] 					= TP_API_SIGNIN_URL;
		$this->endpoints['SignOut'] 				= TP_API_SIGNOUT_URL;
		$this->endpoints['Media'] 					= TP_API_MEDIA_ENDPOINT;
		$this->endpoints['MediaFile'] 				= TP_API_MEDIA_FILE_ENDPOINT;
		$this->endpoints['MediaField'] 				= TP_API_MEDIA_FIELD_ENDPOINT;
		$this->endpoints['MediaServer'] 			= TP_API_MEDIA_SERVER_ENDPOINT;
		$this->endpoints['MediaAccountSettings'] 	= TP_API_MEDIA_ACCOUNT_SETTINGS_ENDPOINT;
		$this->endpoints['MediaDelete']				= TP_API_MEDIA_DELETE_ENDPOINT;
		$this->endpoints['MediaRelease']			= TP_API_MEDIA_RELEASE_ENDPOINT;
		$this->endpoints['MediaCategory']			= TP_API_MEDIA_CATEGORY_ENDPOINT;
		$this->endpoints['Player'] 					= TP_API_PLAYER_PLAYER_ENDPOINT;
		$this->endpoints['AccessAccount'] 			= TP_API_ACCESS_ACCOUNT_ENDPOINT;
		$this->endpoints['PublishProfile']			= TP_API_PUBLISH_PROFILE_ENDPOINT;
		$this->endpoints['GetUploadURLs'] 			= TP_API_FMS_GET_UPLOAD_URLS_ENDPOINT;		

	}
	
	/**
	 * Construct a Basic Authorization header
	 *
	 * @return array 
	 */
	function basicAuthHeader() {
		if (!$this->preferences)
			$this->preferences = get_option($this->preferences_options_key);
	
		$encoded = base64_encode( $this->preferences['mpx_username'] . ':' . $this->preferences['mpx_password'] );
	
		$args = array(
			'headers' => array(
				'Authorization' => 'Basic ' . $encoded
			)
		);
		
		return $args;
	}
	
	/**
	 * Query an MPX endpoint
	 *
	 * @param string $endpoint A string representing the endpoint to query, e.g. 'MediaFile', 'AccessAccount'
	 * @param string $method The HTTP method to use. Accepts 'post', 'put', and 'get'
	 * @param array $params The URL parameters to pass to the endpoint
	 * @param mixed $payload PUT or POST payload data
	 * @param boolean $isJson Whether or not the $payload parameter is already JSON encoded
	 * @return array|WP_Error A response array on success, or a WP_Error instance on failure 
	 */
	function query($endpoint, $method, $params, $payload = NULL, $isJson = false) {
	
		if (!$this->preferences)
			$this->preferences = get_option($this->preferences_options_key);

		if (isset($this->endpoints[$endpoint])) {
			$url = $this->endpoints[$endpoint];
			
			foreach ($params as $key => $value) {
				$url .= '&' . urlencode($key) . '=' . urlencode($value);
			}
			$url .= "&account=" . urlencode($this->preferences['mpx_account_id']);			


			switch ( $method ) {
				case 'get':
					$response = ThePlatform_API_HTTP::get($url);
					return $response;
					break;
				case 'post':
					$response = ThePlatform_API_HTTP::post($url, $isJson ? json_encode($payload, JSON_UNESCAPED_SLASHES) : $payload, $isJson);
					return $response;
					break;
				case 'put':
					$response = ThePlatform_API_HTTP::put($url, $payload);
					return $response;
					break;
				default:
					return new WP_Error('ThePlatform_API::query', 'Invalid HTTP method specified.');
					break;
			}
			
		} else {
			return new WP_Error('ThePlatform_API::query', 'Invalid Endpoint Specified.');
		}
	}
	
	/**
	 * Signs into MPX and retrieves an access token.
	 *
	 * @return An access token
	*/ 
	function mpx_signin() {
		$response = ThePlatform_API_HTTP::get(TP_API_SIGNIN_URL, $this->basicAuthHeader());		
		
		$payload = decode_json_from_server($response, TRUE);
		
		$this->token = $payload['signInResponse']['token'];
		
		return $this->token;
	}
	
	/**
	 * Deactivates an MPX access token.
	 *
	 * @param string $token The token to deactivate
	*/ 
	function mpx_signout($token) {
		$response = ThePlatform_API_HTTP::get(TP_API_SIGNOUT_URL . $token);		
		
		return $response;
	}
	
	/**
	 * Update a media asset in MPX
	 *
	 * @param string $mediaID The ID of the media asset to update
	 * @param array $payload JSON payload containing field-data pairs to update
	 * @return string A message indicating whether or not the update succeeded
	*/
	function update_media($payload) {
		if (!$this->preferences)
			$this->preferences = get_option($this->preferences_options_key);
	
		$params = array(			
			'token' => $this->mpx_signin(),			
		);
				
		$response = $this->query('Media', 'put', $params, $payload, true);
				
		
		$this->mpx_signout($params['token']);
		
		if( is_wp_error( $response ) ) {
		   $error_message = $response->get_error_message();
		   echo '<div id="message" class="error below-h2"><p>' . $error_message . '</p></div>';
		} else {
			$data = decode_json_from_server($response, TRUE);
			if ($data['isException'] == true) {
				$error_message = $data['title'] . ": " . $data['description'];
				echo '<div id="message" class="error below-h2"><p>' . $error_message . '</p></div>';
			} else {			
			   echo '<div id="message" class="updated below-h2 fade"><p>Video updated.</p></div>';
			}
		}
	}

	/**
	 * Creates a placeholder Media object in MPX.
	 *
	 * @param array $args URL arguments to pass to the Media data service
	 * @param string $token The token for this upload session
	 * @return string JSON response from the Media data service
	*/ 
	function create_media_placeholder($args, $token) {
		if (!$this->preferences)
			$this->preferences = get_option($this->preferences_options_key);
	
		$filename = $args['filename'];
		$filesize = $args['filesize'];
		$filetype = $args['filetype'];

		$fields = json_decode(stripslashes($args['fields']), TRUE);		
		$custom_fields = json_decode(stripslashes($args['custom_fields']), TRUE);		

		if (is_null($fields))
			wp_die('MPX error');

		$custom_field_ns = array();
		$custom_field_values = array();
		if (!is_null($custom_fields)) {
			$fieldKeys = implode('|', array_keys($custom_fields));
			$customfield_info = $this->get_customfield_info($fieldKeys);
			foreach ($customfield_info['entries'] as $value) {
				if ($value['plfield$namespacePrefix'] !== '') {
					$custom_field_ns[$value['plfield$namespacePrefix']] = $value['plfield$namespace'];
					$custom_field_values[$value['plfield$namespacePrefix'] . '$' . $value['plfield$fieldName']] = $custom_fields[$value['plfield$fieldName']]; 	
				}
			}
		}		
		
		$payload = array_merge(array(
			'$xmlns' => array_merge(array(
				"dcterms" => "http://purl.org/dc/terms/",
				"media" => "http://search.yahoo.com/mrss/",
				"pl" => "http://xml.theplatform.com/data/object",
				"pla" => "http://xml.theplatform.com/data/object/admin",
				"plmedia" => "http://xml.theplatform.com/media/data/Media",
				"plfile" => "http://xml.theplatform.com/media/data/MediaFile",
				"plrelease" => "http://xml.theplatform.com/media/data/Release",
				"plcategory" => "http://xml.theplatform.com/media/data/Category"
				),
				$custom_field_ns)
			), 
			array_merge($fields, $custom_field_values)
		);
					
		$url = TP_API_MEDIA_ENDPOINT;
		$url .= '&account=' .  urlencode($this->preferences['mpx_account_id']);
		$url .= '&token=' . $token;
		
		$response = ThePlatform_API_HTTP::post($url, json_encode($payload, JSON_UNESCAPED_SLASHES), true);		
		
		$data = decode_json_from_server($response, TRUE);			
			
		return $data;
	}
	

	/**
	 * Gets custom fields namespaces and prefixes
	 *
	 * @param string $fields A pipe separated list of mediafields
	 * @param string $token The token for this upload session
	 * @return string Default server returned from the Media Account Settings data service
	*/ 
	function get_customfield_info($fields) {
		
		$token = $this->mpx_signin();
		$url =  TP_API_MEDIA_FIELD_ENDPOINT;
		$url .= '&fields=namespace,namespacePrefix,fieldName';
		$url .= '&byFieldName=' . $fields;
		$url .= '&token=' . $token;
		
		$response = ThePlatform_API_HTTP::get($url);

		$this->mpx_signout($token);
		
		return decode_json_from_server($response, TRUE);
	}
	
	/**
	 * Get the upload server URLs configured for the current user.
	 *
	 * @param string $server_id The current user's default server identifier
	 * @param string $token The token for this upload session
	 * @return string A valid upload server URL
	*/ 
	function get_upload_urls($server_id, $token) {
		if (!$this->preferences)
			$this->preferences = get_option($this->preferences_options_key);
	
		$url =  TP_API_FMS_GET_UPLOAD_URLS_ENDPOINT;
		$url .= '&token=' . $token;
		$url .= '&account=' . urlencode($this->preferences['mpx_account_id']);
		$url .= '&_serverId=' . urlencode($server_id);		

		$response = ThePlatform_API_HTTP::get($url);
				
		$data = decode_json_from_server($response, TRUE);	
		
		return $data['getUploadUrlsResponse'][0];
	}
	
	/**
	 * Initialize a media upload session.
	 *
	 * @param array $args URL arguments to pass to the Media data service
	 * @return array An array of parameters for the fragmented uploader service
	*/ 
	function initialize_media_upload() {		
		check_admin_referer('theplatform-ajax-nonce');
		$tp_publisher_cap = apply_filters('tp_publisher_cap', 'upload_files'); 
		if (!current_user_can($tp_publisher_cap)) {
			wp_die('<p>'.__('You do not have sufficient permissions to upload files').'</p>');
		}

		if (!$this->preferences)
			$this->preferences = get_option($this->preferences_options_key);

		$args = array(
				'filesize' => $_POST[filesize],
				'filetype' => $_POST[filetype],
				'filename' => $_POST[filename],
				'fields' => $_POST[fields],
				'profile' => $_POST[profile],
				'custom_fields' => $_POST[custom_fields]
			);		

		$token = $this->mpx_signin();
			
		$response = $this->create_media_placeholder($args, $token);

		$media_guid = $response['guid'];
		$media_id = $response['id'];
		
		$format = ThePlatform_API_Util::get_format($args['filetype']);
			
		$upload_server_id = $this->preferences['mpx_server_id'];
	
		$upload_server_base_url = $this->get_upload_urls($upload_server_id, $token); 

		if ( is_wp_error( $upload_server_base_url ) ) {
			return $upload_server_base_url;
		}

		$params = array(
				'token' => $token,
				'media_id' => $media_id,
				'guid' => $media_guid,
				'account_id' => $this->preferences['mpx_account_id'],
				'server_id' => $upload_server_id,
				'upload_base' => $upload_server_base_url,
				'format' => (string)$format->title,
				'contentType' => (string)$format->defaultContentType,
				'success' => 'true'
			);
				
		echo json_encode($params);
		die();
	}
	
	
	
	function get_release_by_id($media_id) {

		$token = $this->mpx_signin();

		$url = TP_API_MEDIA_RELEASE_ENDPOINT . '&fields=pid';
		$url .= '&byMediaId=' . $media_id;
		$url .= '&token=' . $token;
		$response = ThePlatform_API_HTTP::get($url);	
		
		if ( is_wp_error( $response ) ) {
			return $response;
		}

					
		$payload = decode_json_from_server($response, TRUE);
		$releasePID = $payload['entries'][0]['plrelease$pid'];

		$this->mpx_signout($token);

		return $releasePID;
	}
	/**
	 * Query MPX for videos 
	 *
	 * @param string $query Query fields to append to the request URL
	 * @param string $sort Sort parameters to pass to the data service
	 * @param array $fields Optional set of fields to request from the data service
	 * @return array The Media data service response
	*/
	function get_videos($query = '', $sort = '', $startPage = '', $fields = array()) {			
		$default_fields = array('id', 'categories', 'guid', 'author', 'added', 'keywords', 'content', 'description', 'title', 'ownerId', 'defaultThumbnailUrl', ':');
		
		$fields = array_merge($default_fields, $fields);
		
		$fields = implode(',', $fields);

		if (!$this->preferences)
			$this->preferences = get_option($this->preferences_options_key);

		$token = $this->mpx_signin();
		
		if ($startPage === '' || $startPage === '1')
			$range = '1-' . $this->preferences['videos_per_page'];
		else {
			$startRange = (intval($startPage)-1)*intval($this->preferences['videos_per_page']);
			$endRange = $startRange+intval($this->preferences['videos_per_page']);
			$range = ++$startRange. '-' . $endRange;
		}
		$url = TP_API_MEDIA_ENDPOINT . '&count=true&fields=' . $fields . '&token=' . $token . '&range=' . $range;
		
		
		if (!empty($this->preferences['mpx_account_id'])) {
			$url .= '&account=' . urlencode($this->preferences['mpx_account_id']);
		}
		else {
			wp_die('<p>'.__('MPX Account is not set, unable to retrieve videos.').'</p>');			
		}		
		
		if (!empty($query)) {
			$url .= '&' . $query;
		}
		
		if (!empty($sort)) {
			$url .= '&sort=' . $sort;
		}		

		$response = ThePlatform_API_HTTP::get($url);
		
		$ret = decode_json_from_server($response, TRUE);
		// var_dump($ret['entries']);
		$this->mpx_signout($token);				

		return $ret;
	}
	
	/**
	 * Query MPX for a specific video 
	 *
	 * @param string $id The Media ID associated with the asset we are requesting 
	 * @return array The Media data service response
	*/
	function get_video_by_id($id) {
		$token = $this->mpx_signin();
		
		$url = TP_API_MEDIA_ENDPOINT . '&fields=:,id,title,guid,description,author,categories,copyright,credits,keywords,provider,defaultThumbnailUrl,content&token=' . $token . '&byId=' . $id;
				
		$response = ThePlatform_API_HTTP::get($url);
		
		$data = decode_json_from_server($response, TRUE);

		$this->mpx_signout($token);
		
		return $data['entries'][0];
	}

	/**
	 * Query MPX for categories
	 *
	 * @param string $id The Media ID associated with the asset we are requesting 
	 * @return array The Media data service response
	*/
	function get_categories() {
		$token = $this->mpx_signin();
		
		if (!$this->preferences)
			$this->preferences = get_option($this->preferences_options_key);

		$url = TP_API_MEDIA_CATEGORY_ENDPOINT . '&fields=id,title&token=' . $token . '&account=' . $this->preferences['mpx_account_id'];
								
		$response = ThePlatform_API_HTTP::get($url);
		
		$data = decode_json_from_server($response, TRUE);

		$this->mpx_signout($token);
		
		return $data['entries'];
	}
	
	/**
	 * Query MPX for players 
	 *
	 * @param array $query Query fields to append to the request URL
	 * @param array $sort Sort parameters to pass to the data service
	 * @param array $fields Optional set of fields to request from the data service
	 * @return array The Player data service response
	*/
	function get_players($fields = array(), $query = array(), $sort = array()) {
		$default_fields = array('id', 'title', 'plplayer$pid');
		
		$fields = array_merge($default_fields, $fields);
		
		$fields = implode(',', $fields);
		
		if (!$this->preferences)
			$this->preferences = get_option($this->preferences_options_key);
		
		$token = $this->mpx_signin();
		
		$url = TP_API_PLAYER_PLAYER_ENDPOINT . '&sort=title&fields=' . $fields . '&token=' . $token;
		
		if (!empty($this->preferences['mpx_account_id'])) {
			$url .= '&account=' . urlencode($this->preferences['mpx_account_id']);
		}
		
		$response = ThePlatform_API_HTTP::get($url);

		$data = decode_json_from_server($response, TRUE);;
		$ret = $data['entries'];

		$this->mpx_signout($token);
				
		return $ret;
	}

	/**
	 * Query MPX for custom metadata fields 
	 *
	 * @param array $query Query fields to append to the request URL
	 * @param array $sort Sort parameters to pass to the data service
	 * @param array $fields Optional set of fields to request from the data service
	 * @return array The Media Field data service response
	*/
	function get_metadata_fields($fields = array(), $query = array(), $sort = array()) {
		$default_fields = array('id', 'title', 'description', 'added', 'allowedValues', 'dataStructure', 'dataType', 'fieldName', 'defaultValue', 'namespace', 'namespacePrefix');
		
		$fields = array_merge($default_fields, $fields);
		$fields = implode(',', $fields);
		
		if (!$this->preferences)
			$this->preferences = get_option($this->preferences_options_key);
		
		$token = $this->mpx_signin();
		
		$url = TP_API_MEDIA_FIELD_ENDPOINT . '&fields=' . $fields . '&token=' . $token;
		
		if (!empty($this->preferences['mpx_namespace'])) {
			$url .= '&byNamespace=' . $this->preferences['mpx_namespace']; 
		}
		
		if (!empty($this->preferences['mpx_account_id'])) {
			$url .= '&account=' . urlencode($this->preferences['mpx_account_id']);
		}
		
		$response = ThePlatform_API_HTTP::get($url);
				
		$data = decode_json_from_server($response, TRUE);
		
		$ret = $data['entries'];
		
		
		$this->mpx_signout($token);
		
		return $ret;
	}
	
	/**
	 * Query MPX for metadata fields used during media upload 
	 *
	 * @param array $query Query fields to append to the request URL
	 * @param array $sort Sort parameters to pass to the data service
	 * @param array $fields Optional set of fields to request from the data service
	 * @return array The Media data service response
	*/
	function get_upload_fields($fields = array(), $query = array(), $sort = array()) {
		$default_fields = array('id', 'title', 'description', 'added', 'allowedValues', 'dataStructure', 'dataType', 'fieldName', 'defaultValue');
		
		$fields = array_merge($default_fields, $fields);
		$fields = implode(',', $fields);
		
		if (!$this->preferences)
			$this->preferences = get_option($this->preferences_options_key);
		
		$token = $this->mpx_signin();
		
		$url = TP_API_MEDIA_FIELD_ENDPOINT . '&fields=' . $fields . '&token=' . $token;
		
		if (!empty($this->preferences['mpx_account_id'])) {
			$url .= '&account=' . urlencode($this->preferences['mpx_account_id']);
		}
		
		$response = ThePlatform_API_HTTP::get($url);
		
		if( is_wp_error( $response )) {
		   $ret = $response;
		} else {
			$data = decode_json_from_server($response, TRUE);
			
			if ( !empty( $data['isException'] ) ) {
				$ret = new WP_Error('ThePlatform_API::get_upload_fields', $data['title']);
			} else {
				$ret = $data['entries'];
			}
		}
		
		$this->mpx_signout($token);
		
		return $ret;
	}
	
	/**
	 * Query MPX for available servers
	 *
	 * @param array $query Query fields to append to the request URL
	 * @param array $sort Sort parameters to pass to the data service
	 * @param array $fields Optional set of fields to request from the data service
	 * @return array The Media data service response
	*/
	function get_servers($fields = array(), $query = array(), $sort = array()) {
		$default_fields = array('id', 'title', 'description', 'added');
		
		$fields = array_merge($default_fields, $fields);
		$fields = implode(',', $fields);
		
		if (!$this->preferences)
			$this->preferences = get_option($this->preferences_options_key);
			
		$token = $this->mpx_signin();
		
		$url = TP_API_MEDIA_SERVER_ENDPOINT . '&fields=' . $fields . '&token=' . $token;
		
		if (!empty($this->preferences['mpx_account_id'])) {
			$url .= '&account=' . urlencode($this->preferences['mpx_account_id']);
		}
		
		$response = ThePlatform_API_HTTP::get($url);
		$data = decode_json_from_server($response, TRUE);
		$ret = $data['entries'];
		
		$this->mpx_signout($token);
		
		return $ret;
	}
	
	/**
	 * Query MPX for subaccounts associated with the configured account
	 *
	 * @param array $query Query fields to append to the request URL
	 * @param array $sort Sort parameters to pass to the data service
	 * @param array $fields Optional set of fields to request from the data service
	 * @return array The Media data service response
	*/
	function get_subaccounts($fields = array(), $query = array(), $sort = array()) {
		$default_fields = array('id', 'title', 'description', 'placcount$pid');
		
		$fields = array_merge($default_fields, $fields);
		$fields = implode(',', $fields);
		
		$token = $this->mpx_signin();
		
		$url = TP_API_ACCESS_ACCOUNT_ENDPOINT . '&fields=' . $fields . '&token=' . $token . '&sort=title';
		
		$response = ThePlatform_API_HTTP::get($url);

		$data = decode_json_from_server($response,TRUE);
	
		$ret = $data['entries'];

		$this->mpx_signout($token);
		
		return $ret;
	}	

	/**
	 * Query MPX for Publishing Profiles associated with the configured account
	 *
	 * @param array $query Query fields to append to the request URL
	 * @param array $sort Sort parameters to pass to the data service
	 * @param array $fields Optional set of fields to request from the data service
	 * @return array The Media data service response
	*/
	function get_publish_profiles($fields = array(), $query = array(), $sort = array()) {
		$default_fields = array('id', 'title');
		
		$fields = array_merge($default_fields, $fields);
		$fields = implode(',', $fields);
		
		$token = $this->mpx_signin();
		
		$url = TP_API_PUBLISH_PROFILE_ENDPOINT . '&fields=' . $fields . '&token=' . $token . '&sort=title';
		
		if (!empty($this->preferences['mpx_account_id'])) {
			$url .= '&account=' . urlencode($this->preferences['mpx_account_id']);
		}

		$response = ThePlatform_API_HTTP::get($url);
	
		$data = decode_json_from_server($response, TRUE);

		$ret = $data['entries'];
	
		$this->mpx_signout($token);
			
		return $ret;
	}
};