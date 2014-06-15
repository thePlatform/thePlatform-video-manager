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

define( 'TP_PLUGIN_VERSION', '1.2.0' );
define( 'TP_PREFERENCES_OPTIONS_KEY', 'theplatform_preferences_options' );
define( 'TP_ACCOUNT_OPTIONS_KEY', 'theplatform_account_options' );
define( 'TP_METADATA_OPTIONS_KEY', 'theplatform_metadata_options' );
define( 'TP_UPLOAD_OPTIONS_KEY', 'theplatform_upload_options' );


function TP_ACCOUNT_OPTIONS_DEFAULTS() {
	return array(
					'mpx_account_id' => '',
					'mpx_username' => 'mpx/',
					'mpx_password' => '',
					'mpx_account_pid' => '',
					'mpx_region' => 'us'
				);
}

function TP_PREFERENCES_OPTIONS_DEFAULTS() {
	return array(
					'plugin_version' => TP_PLUGIN_VERSION,
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
}

function TP_UPLOAD_FIELDS() {
	return array(
					'title',
					'description',
					'categories',
					'author',
					'keywords',
					'link',
					'guid'
				);
}

function TP_CUSTOM_FIELDS_TYPES() {
	return array( 
					'String', 
					'Time',
					'Date', 
					'DateTime', 
					'Integer', 
					'Decimal', 
					'Duration', 
					'Boolean', 
					'URI', 
					'Link' 
				);
}

function TP_PLUGIN_VERSION() {
	return array_combine( array( 'major', 'minor', 'patch' ), explode( '.', TP_PLUGIN_VERSION ) );	
}

function TP_REGIONS() {
	return array( 'us', 'eu' );
}
