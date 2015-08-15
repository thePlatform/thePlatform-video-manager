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

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class ThePlatform_URLs
 * This class is responsible of retrieving the Service URLs from the account registry
 */
class ThePlatform_URLs {

	/**
	 * Define mpx endpoints and associated parameters
	 */
	function __construct() {
		// Set the base URLs based on the region
		$serviceUrls = get_option( TP_REGISTRY_OPTIONS_KEY );

		define( 'TP_API_ACCESS_MASTER_BASE_URL', 'http://access.auth.theplatform.com' );
		define( 'TP_API_ADMIN_IDENTITY_MASTER_BASE_URL', 'https://identity.auth.theplatform.com/idm' );

		// Registry URLs
		define( 'TP_API_RESOLVE_REGISTRY', TP_API_ACCESS_MASTER_BASE_URL . '/web/Registry/resolveDomain?schema=1.0&form=json' );

		if ( ! $serviceUrls ) {
			define( 'TP_API_SIGNIN_URL', TP_API_ADMIN_IDENTITY_MASTER_BASE_URL . '/web/Authentication/signIn?schema=1.0&form=json&wpVersion=' . TP_PLUGIN_VERSION );
			define( 'TP_API_ACCESS_ACCOUNT_ENDPOINT', TP_API_ACCESS_MASTER_BASE_URL . '/data/Account?schema=1.3.0&form=cjson' );
			define( 'TP_API_ACCESS_ACCOUNT_LOOKUP_ENDPOINT', TP_API_ACCESS_MASTER_BASE_URL . '/web/Lookup/getAccountInfoByIds?schema=1.0&form=json' );

			return;
		}

		define( 'TP_API_ADMIN_IDENTITY_BASE_URL', $serviceUrls['User Data Service'] );
		define( 'TP_API_MEDIA_DATA_BASE_URL', $serviceUrls['Media Data Service'] );
		define( 'TP_API_PLAYER_BASE_URL', $serviceUrls['Player Data Service'] );
		define( 'TP_API_ACCESS_BASE_URL', $serviceUrls['Access Data Service'] );
		define( 'TP_API_WORKFLOW_BASE_URL', $serviceUrls['Workflow Data Service'] );
		define( 'TP_API_PUBLISH_BASE_URL', $serviceUrls['Publish Service'] );
		define( 'TP_API_PUBLISH_DATA_BASE_URL', $serviceUrls['Publish Data Service'] );
		define( 'TP_API_FMS_BASE_URL', $serviceUrls['File Management Service'] );
		define( 'TP_API_PLAYER_EMBED_BASE_URL', $serviceUrls['Player Service'] );
		define( 'TP_API_TASK_BASE_URL', $serviceUrls['Task Service'] );
		define( 'TP_API_STATIC_WEB_BASE_URL', $serviceUrls['Static Web Files'] );


		// XML File containing format definitions
		define( 'TP_API_FORMATS_XML_URL', TP_API_STATIC_WEB_BASE_URL . '/descriptors/enums/format.xml' );

		// Identity Management Service URLs
		define( 'TP_API_SIGNIN_URL', TP_API_ADMIN_IDENTITY_BASE_URL . '/web/Authentication/signIn?schema=1.0&form=json&wpVersion=' . TP_PLUGIN_VERSION );

		// Media Data Service URLs
		define( 'TP_API_MEDIA_ENDPOINT', TP_API_MEDIA_DATA_BASE_URL . '/data/Media?schema=1.7.0&searchSchema=1.0&form=cjson' );
		define( 'TP_API_MEDIA_FILE_ENDPOINT', TP_API_MEDIA_DATA_BASE_URL . '/data/MediaFile?schema=1.7.0&form=cjson' );
		define( 'TP_API_MEDIA_FIELD_ENDPOINT', TP_API_MEDIA_DATA_BASE_URL . '/data/Media/Field?schema=1.7.0&form=cjson' );
		define( 'TP_API_MEDIA_SERVER_ENDPOINT', TP_API_MEDIA_DATA_BASE_URL . '/data/Server?schema=1.7.0&form=cjson' );
		define( 'TP_API_MEDIA_RELEASE_ENDPOINT', TP_API_MEDIA_DATA_BASE_URL . '/data/Release?schema=1.7.0&form=cjson' );
		define( 'TP_API_MEDIA_CATEGORY_ENDPOINT', TP_API_MEDIA_DATA_BASE_URL . '/data/Category?schema=1.7.0&form=cjson' );
		define( 'TP_API_MEDIA_ACCOUNTSETTINGS_ENDPOINT', TP_API_MEDIA_DATA_BASE_URL . '/data/AccountSettings?schema=1.7.0&form=cjson' );

		// Player Data Service URLs
		define( 'TP_API_PLAYER_PLAYER_ENDPOINT', TP_API_PLAYER_BASE_URL . '/data/Player?schema=1.3.0&form=cjson' );

		// Access Data Service URLs
		define( 'TP_API_ACCESS_ACCOUNT_ENDPOINT', TP_API_ACCESS_BASE_URL . '/data/Account?schema=1.3.0&form=cjson' );
		define( 'TP_API_ACCESS_ACCOUNT_LOOKUP_ENDPOINT', TP_API_ACCESS_BASE_URL . '/web/Lookup/getAccountInfoByIds?schema=1.0&form=json' );

		// Workflow Data Service URLs
		define( 'TP_API_WORKFLOW_PROFILE_RESULT_ENDPOINT', TP_API_WORKFLOW_BASE_URL . '/data/ProfileResult?schema=1.0&form=cjson' );

		// Publish Data Service URLs
		define( 'TP_API_PUBLISH_PROFILE_ENDPOINT', TP_API_PUBLISH_DATA_BASE_URL . '/data/PublishProfile?schema=1.8.0&form=json' );
		define( 'TP_API_PUBLISH_PUBLISH_ENDPOINT', TP_API_PUBLISH_BASE_URL . '/web/Publish/publish?schema=1.2&form=json' );
		define( 'TP_API_PUBLISH_REVOKE_ENDPOINT', TP_API_PUBLISH_BASE_URL . '/web/Publish/revoke?schema=1.2&form=json' );

		// Task Data Service URLs
		define( 'TP_API_TASK_TEMPLATE_ENDPOINT', TP_API_TASK_BASE_URL . '/data/TaskTemplate?schema=1.3.0&form=cjson' );

		// FMS URLs
		define( 'TP_API_FMS_GET_UPLOAD_URLS_ENDPOINT', TP_API_FMS_BASE_URL . '/web/FileManagement/getUploadUrls?schema=1.5&form=json' );
		define( 'TP_API_FMS_GENERATE_THUMBNAIL_ENDPOINT', TP_API_FMS_BASE_URL . '/web/FileManagement/generateNewFiles?schema=1.5&form=json' );
	}

}

new ThePlatform_URLs();
