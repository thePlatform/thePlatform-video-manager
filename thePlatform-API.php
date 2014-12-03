<?php

/* thePlatform Video Manager Wordpress Plugin
  Copyright (C) 2013-2014 thePlatform for Media Inc.

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
 * Simple wrapper class for the WordPress HTTP API methods
 */
class ThePlatform_API_HTTP {

	/**
	 * Make a HTTP GET request to the provided URL
	 * @param string $url URL to make the request to
	 * @param array $data optional Data to send with the request
	 * @param boolean $cache Try and make a cache request first
	 * @return wp_response Results of the GET request
	 */
	static function get( $url, $data = array(), $cache = false ) {
		// esc_url_raw eats []'s , so I'm forced to skip it for urls containing
		// those characters - at this time only the account list request
		if ( !strpos( $url, '[0]' ) ) {
			$url = esc_url_raw( $url );
		}			
		
		if ($cache && TRUE === WPCOM_IS_VIP_ENV) {
			return wpcom_vip_file_get_contents( $url );
		}
		else {
			return wp_remote_get( $url, $data );
		}
		
		return $response;
	}

	/**
	 * Make a HTTP PUT request to the provided URL
	 * @param string $url URL to make the request to
	 * @param array $data optional Data to send with the request
	 * @param boolean $isJSON optional|TRUE Whether our data is JSON encoded or not
	 * @return wp_response Results of the PUT request
	 */
	static function put( $url, $data = array(), $isJSON = TRUE ) {
		return ThePlatform_API_HTTP::post( $url, $data, $isJSON, 'PUT' );
	}

	/**
	 * Make a HTTP POST request to the provided URL
	 * @param string $url URL to make the request to
	 * @param array $data Data to send with the request, default is a blank array
	 * @param boolean $isJSON optional|TRUE Whether our data is JSON encoded or not
	 * @param string $method optional|POST Sets the header HTTP request method
	 * @return wp_response Results of the POST request
	 */
	static function post( $url, $data, $isJSON = TRUE, $method = 'POST' ) {
		$url = esc_url_raw( $url );
		$args = array(
			'method' => $method,
			'body' => $data,
			'timeout' => 10,
		);

		if ( $isJSON ) {
			$args['headers'] = array( 'Content-Type' => 'application/json; charset=UTF-8' );
		}

		$response = wp_remote_post( $url, $args );

		return $response;
	}
}

/**
 * Handle all calls to MPX API
 */
class ThePlatform_API {
		
	/**
	 * Class constructor
	 */
	function __construct() {
		$this->get_account();
		$this->get_preferences();
	}
	
	/**
	 * Gets the MPX account options from the database
	 */
	private function get_account() {
		if ( !isset ( $this->account ) ) {
			$this->account = get_option( TP_ACCOUNT_OPTIONS_KEY );
		}
	}
	
	/**
	 * Checks if the MPX account ID is set and returns it if available
	 * @param boolean $urlEncoded Return the MPX account id in a url encoded format, TRUE by default
	 * @return string|boolean MPX account ID or False if not found
	 */
	private function get_mpx_account_id($urlEncoded = TRUE) {
		$this->get_account();
		if ( empty( $this->account['mpx_account_id'] ) ) {
			return FALSE;
		}
		if (TRUE === $urlEncoded) {
			return urlencode( $this->account['mpx_account_id'] );
		}
		
		return $this->account['mpx_account_id'];
	}
	
	/**
	 * Gets the MPX preferences from the database
	 */
	private function get_preferences() {
		if ( !isset ( $this->preferences ) ) {
			$this->preferences = get_option( TP_PREFERENCES_OPTIONS_KEY );
		}
	}
	
	/**
	 * Construct a Basic Authorization header	 
	 * @return array 
	 */
	private function basicAuthHeader() {
		$this->get_account();

		$encoded = base64_encode( $this->account['mpx_username'] . ':' . $this->account['mpx_password'] );

		$args = array(
			'headers' => array(
				'Authorization' => 'Basic ' . $encoded
			)
		);

		return $args;
	}

	/**
	 * Convert a MIME type to an MPX-compliant format identifier
	 * @param string $mime A MIME-type string
	 * @return string MPX-compliant format string
	 */
	function get_format( $mime ) {
		$response = ThePlatform_API_HTTP::get( TP_API_FORMATS_XML_URL, null, true );
		
		if ( FALSE == WPCOM_IS_VIP_ENV ) {
			$response = wp_remote_retrieve_body( $response );
		} 
		
		$xmlString = "<?xml version='1.0'?>" . $response;

		$formats = simplexml_load_string( $xmlString );

		foreach ( $formats->format as $format ) {
			foreach ( $format->mimeTypes->mimeType as $mimetype ) {
				if ( $mimetype == $mime ) {
					return $format;
				}
			}
		}

		return 'Unknown';
	}

	/**
	 * Authenticate using MPX Identity service
	 * @return string API Authentication Token
	 */
	function mpx_signin() {
		$response = ThePlatform_API_HTTP::get( TP_API_SIGNIN_URL, $this->basicAuthHeader() );

		$payload = theplatform_decode_json_from_server( $response, TRUE );

		$token = $payload['signInResponse']['token'];

		return $token;
	}

	/**
	 * Deactivates an MPX access token.
	 * @param string $token The token to deactivate
	 * @return WP_Error|array The response or WP_Error on failure.
	 */
	function mpx_signout( $token ) {
		$response = ThePlatform_API_HTTP::get( TP_API_SIGNOUT_URL . $token );
		return $response;
	}

	/**
	 * Update a Media Object's Metadata
	 * @param string $mediaID The ID of the media asset to update
	 * @param array $payload JSON payload containing field-data pairs to update
	 * @return string A message indicating whether or not the update succeeded
	 */
	function update_media( $args ) {
		$token = $this->mpx_signin();
		$this->create_media_placeholder( $args, $token );
		$this->mpx_signout( $token );
	}

	/**
	 * Creates a placeholder Media object in MPX.
	 *
	 * @param array $args URL arguments to pass to the Media data service
	 * @param string $token The token for this upload session
	 * @return string JSON response from the Media data service
	 */
	function create_media_placeholder( $args, $token ) {
		$fields = json_decode( stripslashes( $args['fields'] ), TRUE );
		$custom_fields = json_decode( stripslashes( $args['custom_fields'] ), TRUE );

		if ( empty( $fields ) ) {
			wp_die( 'No fields are set, unable to upload Media' );
		}

		$custom_field_ns = array();
		$custom_field_values = array();
		if ( !empty( $custom_fields ) ) {
			$fieldKeys = implode( '|', array_keys( $custom_fields ) );
			$customfield_info = $this->get_customfield_info( $fieldKeys );
			foreach ( $customfield_info['entries'] as $value ) {
				if ( $value['namespacePrefix'] !== '' ) {
					$custom_field_ns[$value['namespacePrefix']] = $value['namespace'];
					$custom_field_values[$value['namespacePrefix'] . '$' . $value['fieldName']] = $custom_fields[$value['fieldName']];
				}
			}
		}

		$payload = array_merge( array(
			'$xmlns' => array_merge( array(), $custom_field_ns )
				), array_merge( $fields, $custom_field_values )
		);

		$url = TP_API_MEDIA_ENDPOINT;
		$url .= '&account=' . $this->get_mpx_account_id();
		$url .= '&token=' . $token;
			
		$response = ThePlatform_API_HTTP::post( $url, json_encode( $payload, JSON_UNESCAPED_SLASHES ), true );
		$data = theplatform_decode_json_from_server( $response, TRUE );
		return $data;
	}

	/**
	 * Gets custom fields namespaces and prefixes
	 *
	 * @param string $fields A pipe separated list of mediafields
	 * @param string $token The token for this upload session
	 * @return string Default server returned from the Media Account Settings data service
	 */
	function get_customfield_info( $fields ) {
		$token = $this->mpx_signin();
		$url = TP_API_MEDIA_FIELD_ENDPOINT;
		$url .= '&fields=namespace,namespacePrefix,fieldName';
		$url .= '&byFieldName=' . $fields;
		$url .= '&token=' . $token;

		$response = ThePlatform_API_HTTP::get( $url );

		$this->mpx_signout( $token );

		return theplatform_decode_json_from_server( $response, TRUE );
	}

	/**
	 * Get the upload server URLs configured for the current user.
	 *
	 * @param string $server_id The current user's default server identifier
	 * @param string $token The token for this upload session
	 * @return string A valid upload server URL
	 */
	function get_upload_urls( $server_id, $token ) {
		$url = TP_API_FMS_GET_UPLOAD_URLS_ENDPOINT;
		$url .= '&token=' . urlencode( $token );
		$url .= '&account=' . $this->get_mpx_account_id();
		$url .= '&_serverId=' . urlencode( $server_id );

		$response = ThePlatform_API_HTTP::get( $url );

		$data = theplatform_decode_json_from_server( $response, TRUE );

		return $data['getUploadUrlsResponse'][0];
	}

	/**
	 * Initialize a media upload session.
	 *
	 * @param array $args URL arguments to pass to the Media data service
	 * @return array An array of parameters for the fragmented uploader service
	 */
	function initialize_media_upload() {
		check_admin_referer( 'theplatform-ajax-nonce-initialize_media_upload' );

		$args = array(
			'filesize' => $_POST['filesize'],
			'filetype' => $_POST['filetype'],
			'filename' => $_POST['filename'],
			'fields' => $_POST['fields'],
			'profile' => $_POST['profile'],
			'custom_fields' => $_POST['custom_fields'],
			'server_id' => $_POST['server_id']
		);

		$token = $this->mpx_signin();
			
		if ( $args['filetype'] === "audio/mp3" ) {
			$args['filetype'] = "audio/mpeg";
		}
		
		$format = $this->get_format( $args['filetype'] );
		
		if ( $format === "unknown" ) {
			$formatTitle = $format;
		} else {
			$formatTitle = (string) $format->title;
		}

		$upload_server_id = $args['server_id'];

		if ( $upload_server_id === 'DEFAULT_SERVER' ) {
			$upload_server_id = $this->get_default_upload_server( $formatTitle );
		}				
		
		if ( FALSE === $upload_server_id ) {
			wp_send_json_error( "Unable to determine MPX Server ID, please check your account configuration" );
		}
		
		$upload_server_base_url = $this->get_upload_urls( $upload_server_id, $token );

		if ( is_wp_error( $upload_server_base_url ) ) {
			wp_send_json_error( $upload_server_base_url );
		}
		
		$media = $this->create_media_placeholder( $args, $token );
		$params = array(
			'token' => $token,
			'media_id' => $media['id'],
			'guid' => $media['guid'],
			'account_id' => $this->get_mpx_account_id(FALSE),
			'server_id' => $upload_server_id,
			'upload_base' => $upload_server_base_url,
			'format' => $formatTitle,
			'contentType' => (string) $format->defaultContentType			
		);
		
		wp_send_json_success( $params );		
	}
	
	/**
	 * Returns a default server for the specific format
	 * @param string $formatTitle MPX Format title
	 * @return string|boolean MPX Server ID or FALSE
	 */
	function get_default_upload_server($formatTitle) {		
		$accountSettings = $this->get_account_settings();
		$defaultServers = $accountSettings['entries'][0]['defaultServers'];
		$defaultServerURN = "urn:theplatform:format:default";
		
		if ( array_key_exists( $formatTitle, $defaultServers ) ) {
			return $defaultServers[$formatTitle];
		} else if ( array_key_exists( $defaultServerURN, $defaultServers ) ) {
			return $defaultServers[$defaultServerURN];
		} else {
			$servers = $this->get_servers( array( 'formats' ), '&byFormats=' . $formatTitle );
			if ( array_key_exists( 0, $servers ) ) {
				return $servers[0]["id"];
			} else {
				return FALSE;
			}
		}
	}

	/**
	 * Get the first Streaming Release form MPX based on a Media ID
	 * @param string $media_id the MPX Media ID
	 * @return string The Release PID
	 */
	function get_release_by_id( $media_id ) {
		$token = $this->mpx_signin();

		$url = TP_API_MEDIA_RELEASE_ENDPOINT . '&fields=pid';
		$url .= '&byMediaId=' . $media_id;
		$url .= '&token=' . $token;

		$response = ThePlatform_API_HTTP::get( $url );

		$payload = theplatform_decode_json_from_server( $response, TRUE );
		$releasePID = $payload['entries'][0]['plrelease$pid'];

		$this->mpx_signout( $token );

		return $releasePID;
	}

	/**
	 * Query MPX for videos 
	 * @return array The Media data service response
	 */
	function get_videos() {	
		check_admin_referer( 'theplatform-ajax-nonce-get_videos' );
		
		$token = $this->mpx_signin();

		$fields = theplatform_get_query_fields( $this->get_metadata_fields() );

		$url = TP_API_MEDIA_ENDPOINT . '&fields=guid,' . $fields . '&token=' . $token . '&range=' . $_POST['range'];

		if ( $_POST['isEmbed'] === "1" ) {
			$url .= '&byAvailabilityState=available&byApproved=true&byContent=byReleases=byDelivery%253Dstreaming';
		}

		if ( !empty( $_POST['myContent'] ) && $_POST['myContent'] === 'true' ) {
			$url .= '&byCustomValue=' . urlencode( '{' . $this->preferences['user_id_customfield'] . '}{' . wp_get_current_user()->ID . '}' );
		}

		if ( $this->get_mpx_account_id() ) {
			$url .= '&account=' . $this->get_mpx_account_id();
		} else {
			wp_die( '<p>MPX Account is not set, unable to retrieve videos.</p>' );
		}

		if ( !empty( $_POST['query'] ) ) {
			$url .= '&' . $_POST['query'];
		}

		$response = ThePlatform_API_HTTP::get( $url, array( "timeout" => 120 ) );
		$this->mpx_signout( $token );
		
		wp_send_json( wp_remote_retrieve_body( $response ) );
	}

	/**
	 * Query MPX for a specific video 
	 *
	 * @param string $id The Media ID associated with the asset we are requesting 
	 * @return array The Media data service response
	 */
	function get_video_by_id( $id ) {
		$token = $this->mpx_signin();
		$fields = theplatform_get_query_fields( $this->get_metadata_fields() );

		$url = TP_API_MEDIA_ENDPOINT . '&fields=' . $fields . ' &token=' . $token . '&byId=' . $id;

		$response = ThePlatform_API_HTTP::get( $url );

		$data = theplatform_decode_json_from_server( $response, TRUE );

		$this->mpx_signout( $token );

		return $data['entries'][0];
	}

	/**
	 * Query MPX for players 
	 *
	 * @param array $fields Optional set of fields to request from the data service
	 * @return array The Player data service response
	 */
	function get_players( $fields = array() ) {
		$default_fields = array( 'id', 'title', 'plplayer$pid' );

		$fields = implode( ',', 
				array_merge( $default_fields, $fields )
		);

		$token = $this->mpx_signin();

		$url = TP_API_PLAYER_PLAYER_ENDPOINT . '&sort=title&fields=' . $fields . '&token=' . $token;

		if ( $this->get_mpx_account_id() ) {
			$url .= '&account=' . $this->get_mpx_account_id();
		}

		$response = ThePlatform_API_HTTP::get( $url );

		$data = theplatform_decode_json_from_server( $response, TRUE );

		$ret = $data['entries'];

		$this->mpx_signout( $token );

		return $ret;
	}

	/**
	 * Query MPX for custom metadata fields 
	 *
	 * @param array $fields Optional set of fields to request from the data service
	 * @return array The Media Field data service response
	 */
	function get_metadata_fields( $fields = array() ) {
		$default_fields = array( 'id', 'title', 'description', 'added', 'allowedValues', 'dataStructure', 'dataType', 'fieldName', 'defaultValue', 'namespace', 'namespacePrefix' );
		
		$fields = implode( ',', 
				array_merge( $default_fields, $fields )
		);

		$this->get_preferences();

		$token = $this->mpx_signin();

		$url = TP_API_MEDIA_FIELD_ENDPOINT . '&fields=' . $fields . '&token=' . $token;

		if ( !empty( $this->preferences['mpx_namespace'] ) ) {
			$url .= '&byNamespace=' . $this->preferences['mpx_namespace'];
		}

		if ( $this->get_mpx_account_id() ) {
			$url .= '&account=' . $this->get_mpx_account_id();
		}

		$response = ThePlatform_API_HTTP::get( $url );

		$data = theplatform_decode_json_from_server( $response, TRUE );

		$this->mpx_signout( $token );

		return $data['entries'];
	}

	/**
	 * Query MPX for available servers
	 *
	 * @param array $fields Optional set of fields to request from the data service
	 * @param String $query Query fields to append to the request URL
	 * @return array The Media data service response
	 */
	function get_servers( $fields = array(), $query = "" ) {
		$default_fields = array( 'id', 'title', 'description', 'added' );

		$fields = implode( ',', 
				array_merge( $default_fields, $fields )
		);

		$token = $this->mpx_signin();

		$url = TP_API_MEDIA_SERVER_ENDPOINT . '&fields=' . $fields . '&token=' . $token;

		if ( $this->get_mpx_account_id() ) {
			$url .= '&account=' . $this->get_mpx_account_id();
		}

		if ( !empty( $query ) ) {
			$url .= $query;
		}

		$response = ThePlatform_API_HTTP::get( $url );
		$data = theplatform_decode_json_from_server( $response, TRUE );

		$this->mpx_signout( $token );

		return $data['entries'];
	}

	/**
	 * Returns the account setting objects, this is actually used to test our connection
	 * @return array AccountSettings response
	 */
	function get_account_settings() {
		$token = $this->mpx_signin();

		$url = TP_API_MEDIA_ACCOUNTSETTINGS_ENDPOINT . '&token=' . $token;

		if ( $this->get_mpx_account_id() ) {
			$url .= '&account=' . $this->get_mpx_account_id();
		}

		$response = ThePlatform_API_HTTP::get( $url );
		$data = theplatform_decode_json_from_server( $response, TRUE, FALSE );

		$this->mpx_signout( $token );

		return $data;
	}

	/**
	 * Query MPX for account categories
	 *
	 * @param array $query Query fields to append to the request URL
	 * @param array $sort Sort parameters to pass to the data service
	 * @param array $fields Optional set of fields to request from the data service
	 * @return array The Media data service response
	 */
	function get_categories( $returnResponse = false ) {
		// Check nonce if we got here through an AJAX call
		if ( !$returnResponse ) {
			check_admin_referer( 'theplatform-ajax-nonce-get_categories' );
		}
		$token = $this->mpx_signin();

		$url = TP_API_MEDIA_CATEGORY_ENDPOINT . '&fields=title,fullTitle&sort=title,order&token=' . $token;

		if ( $this->get_mpx_account_id() ) {
			$url .= '&account=' . $this->get_mpx_account_id();
		}

		$response = ThePlatform_API_HTTP::get( $url );

		$this->mpx_signout( $token );

		if ( !$returnResponse ) {
			wp_send_json( wp_remote_retrieve_body( $response ) );
		}

		$data = theplatform_decode_json_from_server( $response, TRUE );
		return $data['entries'];
	}

	/**
	 * Query MPX for subaccounts associated with the configured account
	 *
	 * @param array $fields Optional set of fields to request from the data service
	 * @return array The Media data service response
	 */
	function get_subaccounts( $fields = array() ) {
		$default_fields = array( 'id', 'title', 'description', 'placcount$pid' );

		$fields = implode( ',', 
				array_merge( $default_fields, $fields )
		);

		$token = $this->mpx_signin();

		$url = TP_API_ACCESS_AUTH_ENDPOINT . '&_operations[0].service=Media%20Data%20Service&_operations[0].method=GET&_operations[0].endpoint=Media&token=' . $token . '&sort=title&range=1-1000';

		$response = ThePlatform_API_HTTP::get( $url );

		$data = theplatform_decode_json_from_server( $response, TRUE );

		$this->mpx_signout( $token );

		return $data['authorizeResponse']['accounts'];
	}

	/**
	 * Query MPX for Publishing Profiles associated with the configured account
	 *
	 * @param array $fields Optional set of fields to request from the data service
	 * @return array The Media data service response
	 */
	function get_publish_profiles( $fields = array() ) {
		$default_fields = array( 'id', 'title' );

		$fields = implode( ',', 
				array_merge( $default_fields, $fields )
		);

		$token = $this->mpx_signin();

		$url = TP_API_PUBLISH_PROFILE_ENDPOINT . '&fields=' . $fields . '&token=' . $token . '&sort=title';

		if ( $this->get_mpx_account_id() ) {
			$url .= '&account=' . $this->get_mpx_account_id();
		}

		$response = ThePlatform_API_HTTP::get( $url );

		$data = theplatform_decode_json_from_server( $response, TRUE );

		$this->mpx_signout( $token );

		return $data['entries'];
	}

	/**
	 * Used to verify the account server settings on the server side
	 * @return type
	 */
	function internal_verify_account_settings() {
		$this->get_account();

		$username = trim( $this->account['mpx_username'] );
		$password = trim( $this->account['mpx_password'] );

		if ( $username === "mpx/" || $username === "" || $password === "" ) {
			return FALSE;
		}

		$hash = base64_encode( $username . ':' . $password );

		$response = ThePlatform_API_HTTP::get( TP_API_SIGNIN_URL, array( 'headers' => array( 'Authorization' => 'Basic ' . $hash ) ) );

		$payload = theplatform_decode_json_from_server( $response, TRUE, FALSE );

		if ( is_null( $response ) ) {
			return FALSE;
		}

		if ( !array_key_exists( 'isException', $payload ) ) {
			return TRUE;
		} else {
			return FALSE;
		}
	}

	/**
	 * Verify that the account you've selected is within the region you've selected
	 * @return bool account is within the same region
	 */
	function internal_verify_account_region() {
		if ( !$this->get_mpx_account_id() ) {
			return FALSE;
		}

		$response = $this->get_account_settings();

		if ( is_null( $response ) && !is_array( $response ) ) {
			return FALSE;
		}

		if ( !array_key_exists( 'isException', $response ) ) {
			return TRUE;
		} else {
			return FALSE;
		}
	}
}