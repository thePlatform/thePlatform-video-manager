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
define('TP_API_MEDIA_ENDPOINT', TP_API_MEDIA_DATA_BASE_URL . 'Media?schema=1.5&form=cjson');
define('TP_API_MEDIA_FIELD_ENDPOINT', TP_API_MEDIA_DATA_BASE_URL . 'Media/Field?schema=1.3&form=cjson');
define('TP_API_MEDIA_SERVER_ENDPOINT', TP_API_MEDIA_DATA_BASE_URL . 'Server?schema=1.0&form=cjson');
define('TP_API_MEDIA_RELEASE_ENDPOINT', TP_API_MEDIA_DATA_BASE_URL . 'Release?schema=1.5.0&form=cjson');
define('TP_API_MEDIA_CATEGORY_ENDPOINT', TP_API_MEDIA_DATA_BASE_URL . 'Category?schema=1.6.0&form=cjson');

// Player Data Service URLs
define('TP_API_PLAYER_BASE_URL', 'http://data.player.theplatform.com/player/data/');
define('TP_API_PLAYER_PLAYER_ENDPOINT', TP_API_PLAYER_BASE_URL . 'Player?schema=1.3.0&form=cjson');

// Access Data Service URLs
define('TP_API_ACCESS_BASE_URL', 'http://access.auth.theplatform.com/data/');
define('TP_API_ACCESS_ACCOUNT_ENDPOINT', TP_API_ACCESS_BASE_URL . 'Account?schema=1.3.0&form=cjson');

// Workflow Data Service URLs
define('TP_API_WORKFLOW_BASE_URL', 'http://data.workflow.theplatform.com/workflow/data/');
define('TP_API_WORKFLOW_PROFILE_RESULT_ENDPOINT', TP_API_WORKFLOW_BASE_URL . 'ProfileResult?schema=1.0&form=cjson');

// Publish Endpoint
define('TP_API_PUBLISH_BASE_URL', 'http://publish.theplatform.com/web/Publish/publish?schema=1.2&form=json');

// Publish Data Service URLs
define('TP_API_PUBLISH_DATA_BASE_URL', 'http://data.publish.theplatform.com/publish/data/');
define('TP_API_PUBLISH_PROFILE_ENDPOINT', TP_API_PUBLISH_DATA_BASE_URL . 'PublishProfile?schema=1.5.0&form=json');

// FMS URLs
define('TP_API_FMS_BASE_URL', 'http://fms.theplatform.com/web/FileManagement/');
define('TP_API_FMS_GET_UPLOAD_URLS_ENDPOINT', TP_API_FMS_BASE_URL . 'getUploadUrls?schema=1.4&form=json');

/**
 * Wrapper class around Wordpress HTTP methods
 */ 
class ThePlatform_API_HTTP {
	/**
	 * HTTP GET wrapper
	 * @param string $url URL to make the request to
	 * @param array $data Data to send with the request, default is a blank array
	 * @return wp_response Results of the GET request
	 */
	static function get($url, $data = array()) {
		$url = esc_url_raw($url);
		$response = wp_remote_get($url, $data);		
		return $response;		
	}
	
	/**
	 * HTTP PUT wrapper
	 * @param string $url URL to make the request to
	 * @param array $data Data to send with the request, default is a blank array
	 * @return wp_response Results of the GET request
	 */
	static function put($url, $data = array()) {
		return ThePlatform_API_HTTP::post($url, $data, TRUE, 'PUT');		
	}
	
	/**
	 * HTTP POST wrapper
	 * @param string $url URL to make the request to
	 * @param array $data Data to send with the request, default is a blank array
	 * @param boolean $isJSON Whether our data is JSON encoded or not, default is FALSE
	 * @param string $method Sets the header HTTP request method, default is POST
	 * @return wp_response Results of the GET request
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
 * Wrapper for MPX's API calls
 * @package default
 */
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
	 * Convert a MIME type to an MPX-compliant format identifier
	 *
	 * @param string $mime A MIME-type string
	 * @return string MPX-compliant format string
	 */
	function get_format($mime) {
		
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
	
	/**
	 * Signs into MPX and retrieves an access token.
	 *
	 * @return string An access token
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
	 * @return WP_Error|array The response or WP_Error on failure.
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
	function update_media($args) {
		$token = $this->mpx_signin();
		$this->create_media_placeholder($args, $token);
		$this->mpx_signout($token);
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

		if (empty($fields))
			wp_die('No fields are set, unable to upload Media');

		$custom_field_ns = array();
		$custom_field_values = array();
		if (!empty($custom_fields)) {
			$fieldKeys = implode('|', array_keys($custom_fields));
			$customfield_info = $this->get_customfield_info($fieldKeys);
			foreach ($customfield_info['entries'] as $value) {
				if ($value['namespacePrefix'] !== '') {
					$custom_field_ns[$value['namespacePrefix']] = $value['namespace'];
					$custom_field_values[$value['namespacePrefix'] . '$' . $value['fieldName']] = $custom_fields[$value['fieldName']]; 	
				}
			}
		}		
		
		$payload = array_merge(array(
			'$xmlns' => array_merge(array(),$custom_field_ns)
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
		$url .= '&token=' .  urlencode($token);
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
		
		$format = $this->get_format($args['filetype']);

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

	/**
	 * Get the first Streaming Release form MPX based on a Media ID
	 * @param string $media_id the MPX Media ID
	 * @return string The Release PID
	 */
	function get_release_by_id($media_id) {
		$token = $this->mpx_signin();
		
		$url = TP_API_MEDIA_RELEASE_ENDPOINT . '&fields=pid';
		$url .= '&byMediaId=' . $media_id;
		$url .= '&token=' . $token;
		
		$response = ThePlatform_API_HTTP::get($url);	
	
		$payload = decode_json_from_server($response, TRUE);
		$releasePID = $payload['entries'][0]['plrelease$pid'];

		$this->mpx_signout($token);

		return $releasePID;
	}
	/**
	 * Query MPX for videos 
	 *
	 * @param string $query Query fields to append to the request URL, default empty
	 * @param string $sort Sort parameters to pass to the data service, default empty
	 * @param array $fields Optional set of fields to request from the data service, default empty
	 * @return array The Media data service response
	*/
	function get_videos() {			
		if (!$this->preferences)
			$this->preferences = get_option($this->preferences_options_key);

		$token = $this->mpx_signin();

		$fields = get_query_fields($this->get_metadata_fields());		
	
		$url = TP_API_MEDIA_ENDPOINT . '&count=true&fields=' . $fields . '&token=' . $token . '&range=' . $_POST['range'];

		if ($_POST['isEmbed'] === "1") {
			$url .= '&byAvailabilityState=available&byApproved=true&count=true&byContent=byReleases=byDelivery%253Dstreaming';
		}

		if (!empty($_POST['myContent']) && $_POST['myContent'] === 'true') {
			$url .= '&byCustomValue=' . urlencode('{' . $this->preferences['user_id_customfield'] . '}{' . wp_get_current_user()->ID . '}');
		}	

		if (!empty($this->preferences['mpx_account_id'])) {
			$url .= '&account=' . urlencode($this->preferences['mpx_account_id']);
		}
		else {
			wp_die('<p>'.__('MPX Account is not set, unable to retrieve videos.').'</p>');			
		}		
		
		if (!empty($_POST['query'])) {
			$url .= '&' . $_POST['query'];
		}
		

		$response = ThePlatform_API_HTTP::get($url, array("timeout" => 120));		
		$this->mpx_signout($token);

		echo(wp_remote_retrieve_body($response));		
		die();						
	}
	
	/**
	 * Query MPX for a specific video 
	 *
	 * @param string $id The Media ID associated with the asset we are requesting 
	 * @return array The Media data service response
	*/
	function get_video_by_id($id) {
		$token = $this->mpx_signin();
		$fields = get_query_fields($this->get_metadata_fields());

		$url = TP_API_MEDIA_ENDPOINT . '&fields=' . $fields . ' &token=' . $token . '&byId=' . $id;
				
		$response = ThePlatform_API_HTTP::get($url);
		
		$data = decode_json_from_server($response, TRUE);

		$this->mpx_signout($token);
		
		return $data['entries'][0];
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
				
		$this->mpx_signout($token);
		
		return $data['entries'];
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

		$this->mpx_signout($token);
		
		return $data['entries'];
	}

		/**
	 * Query MPX for account categories
	 *
	 * @param array $query Query fields to append to the request URL
	 * @param array $sort Sort parameters to pass to the data service
	 * @param array $fields Optional set of fields to request from the data service
	 * @return array The Media data service response
	*/
	function get_categories($returnResponse = false) {
		$fields = array('title', 'fullTitle');

		if (!$this->preferences)
			$this->preferences = get_option($this->preferences_options_key);
			
		$token = $this->mpx_signin();
		
		$url = TP_API_MEDIA_CATEGORY_ENDPOINT . '&fields=title,fullTitle&sort=title,order&token=' . $token;
		
		if (!empty($this->preferences['mpx_account_id'])) {
			$url .= '&account=' . urlencode($this->preferences['mpx_account_id']);
		}
		
		$response = ThePlatform_API_HTTP::get($url);

		$this->mpx_signout($token);

		if (!$returnResponse) {
			echo(wp_remote_retrieve_body($response));
			die();			
		}

		$data = decode_json_from_server($response, TRUE);
		return $data['entries'];				
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
		
		$url = TP_API_ACCESS_ACCOUNT_ENDPOINT . '&fields=' . $fields . '&token=' . $token . '&sort=title&range=1-1000';
		
		$response = ThePlatform_API_HTTP::get($url);

		$data = decode_json_from_server($response,TRUE);

		$this->mpx_signout($token);
		
		return $data['entries'];
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

		$this->mpx_signout($token);
			
		return $data['entries'];
	}
};