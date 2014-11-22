<?php
/* MediaAMP Video Manager Wordpress Plugin
  Copyright (C) 2013-2014 MediaAMP for Media Inc.

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
 * Handle WordPress Settings API
 */
class ThePlatform_Options {

	private $account_is_verified;
	private $region_is_verified;
	private $regions = array( 'us', 'eu' );
	/*
	 * WP Option key
	 */
	private $plugin_options_key = 'theplatform-settings';

	/*
	 * An array of tabs representing the admin settings interface.
	 */
	private $plugin_settings_tabs = array();
	private $tp_api;
	private $preferences;

	function __construct() {
		$tp_admin_cap = apply_filters( TP_ADMIN_CAP, TP_ADMIN_DEFAULT_CAP );
		if ( !current_user_can( $tp_admin_cap ) ) {
			wp_die( '<p>You do not have sufficient permissions to manage this plugin</p>' );
		}
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );

		$this->tp_api = new ThePlatform_API;

		$this->load_options();
		$this->enqueue_scripts();
		$this->register_account_options();
		$this->register_preferences_options();
		$this->register_upload_options();
		$this->register_metadata_options();
		


		//Render the page
		$this->plugin_options_page();
	}

	/**
	 * Enqueue our javascript file
	 */
	function enqueue_scripts() {
		wp_enqueue_script( 'jquery' );
		wp_enqueue_script( 'theplatform_js' );
		wp_enqueue_script( 'field_views' );
		wp_enqueue_script( 'jquery-ui-sortable' );
		wp_enqueue_style( 'dashicons' );
		wp_enqueue_style( 'field_views' );
	}

	/**
	 * Loads MediaAMP plugin options from
	 * the database into their respective arrays. Uses
	 * array_merge to merge with default values if they're
	 * missing.
	 */
	function load_options() {
		// Get existing options, or empty arrays if no options exist
		$this->account_options = get_option( TP_ACCOUNT_OPTIONS_KEY, array() );		
		$this->preferences_options = get_option( TP_PREFERENCES_OPTIONS_KEY, array() );
		$this->metadata_options = get_option( TP_METADATA_OPTIONS_KEY, array() );
		$this->upload_options = get_option( TP_UPLOAD_OPTIONS_KEY, array() );

		// Initialize option defaults	
		$this->account_options = array_merge( TP_ACCOUNT_OPTIONS_DEFAULTS(), $this->account_options );

		$this->preferences_options = array_merge( TP_PREFERENCES_OPTIONS_DEFAULTS(), $this->preferences_options );

		$this->metadata_options = array_merge( array(), $this->metadata_options );

		$this->upload_options = array_merge( array(), $this->upload_options );

		// Create options table entries in DB if none exist. Initialize with defaults
		update_option( TP_ACCOUNT_OPTIONS_KEY, $this->account_options );
		update_option( TP_PREFERENCES_OPTIONS_KEY, $this->preferences_options );
		update_option( TP_METADATA_OPTIONS_KEY, $this->metadata_options );
		update_option( TP_UPLOAD_OPTIONS_KEY, $this->upload_options );

		//Get preferences from the database for sanity checks
		$this->preferences = get_option( TP_PREFERENCES_OPTIONS_KEY );
		$this->account = get_option ( TP_ACCOUNT_OPTIONS_KEY );

		$this->account_is_verified = $this->tp_api->internal_verify_account_settings();

		if ( $this->account_is_verified ) {
			$this->region_is_verified = $this->tp_api->internal_verify_account_region();
		} else {
			$this->region_is_verified = FALSE;

			if ( $this->account_options['mpx_username'] != 'mpx/' ) {
				echo '<div id="message" class="error">';
				echo '<p><strong>Sign in to MediaAMP failed, please check your account settings.</strong></p>';
				echo '</div>';
			}
		}
	}

	/*
	 * Registers the account options via the Settings API,
	 * appends the setting to the tabs array of the object.
	 */
	function register_account_options() {
		$this->plugin_settings_tabs[TP_ACCOUNT_OPTIONS_KEY] = 'Account Settings';

		add_settings_section( 'section_mpx_account_options', 'MPX Account Options', array( $this, 'section_mpx_account_desc' ), TP_ACCOUNT_OPTIONS_KEY );
		add_settings_field( 'mpx_username_option',	'MPX Username',		array( $this, 'field_account_option' ), TP_ACCOUNT_OPTIONS_KEY, 'section_mpx_account_options', array( 'field' => 'mpx_username' ) );
		add_settings_field( 'mpx_password_option',	'MPX Password',		array( $this, 'field_account_option' ), TP_ACCOUNT_OPTIONS_KEY, 'section_mpx_account_options', array( 'field' => 'mpx_password' ) );
		add_settings_field( 'mpx_region_option',	'MPX Region',		array( $this, 'field_account_option' ), TP_ACCOUNT_OPTIONS_KEY, 'section_mpx_account_options', array( 'field' => 'mpx_region' ) );
		add_settings_field( 'mpx_accountid_option', 'MPX Account',		array( $this, 'field_account_option' ), TP_ACCOUNT_OPTIONS_KEY, 'section_mpx_account_options', array( 'field' => 'mpx_account_id' ) );
		add_settings_field( 'mpx_account_pid',		'MPX Account PID',	array( $this, 'field_account_option' ), TP_ACCOUNT_OPTIONS_KEY, 'section_mpx_account_options', array( 'field' => 'mpx_account_pid' ) );
		
	}

	/*
	 * Registers the preference options via the Settings API,
	 * appends the setting to the tabs array of the object.
	 */
	function register_preferences_options() {				
		if ( !$this->account_is_verified || !$this->region_is_verified ) {
			return;
		}

		if ( empty ( $this->account['mpx_account_id'] ) ) {
			return;
		}
		
		$this->plugin_settings_tabs[TP_PREFERENCES_OPTIONS_KEY] = 'Plugin Settings';

		add_settings_section( 'section_embed_options', 'Embedding Preferences', array( $this, 'section_embed_desc' ), TP_PREFERENCES_OPTIONS_KEY );
		add_settings_field( 'default_player_name',		'Default Player',			array( $this, 'field_preference_option' ), TP_PREFERENCES_OPTIONS_KEY, 'section_embed_options', array( 'field' => 'default_player_name' ) );
		add_settings_field( 'default_player_pid',		'Default Player PID',		array( $this, 'field_preference_option' ), TP_PREFERENCES_OPTIONS_KEY, 'section_embed_options', array( 'field' => 'default_player_pid' ) );
		add_settings_field( 'embed_tag_type_option',	'Embed Tag Type',			array( $this, 'field_preference_option' ), TP_PREFERENCES_OPTIONS_KEY, 'section_embed_options', array( 'field' => 'embed_tag_type' ) );
		add_settings_field( 'rss_embed_type_option',	'RSS Embed Type',			array( $this, 'field_preference_option' ), TP_PREFERENCES_OPTIONS_KEY, 'section_embed_options', array( 'field' => 'rss_embed_type' ) );
		add_settings_field( 'autoplay',					'Force Autoplay',			array( $this, 'field_preference_option' ), TP_PREFERENCES_OPTIONS_KEY, 'section_embed_options', array( 'field' => 'autoplay' ) );
		add_settings_field( 'default_width',			'Default Player Width',		array( $this, 'field_preference_option' ), TP_PREFERENCES_OPTIONS_KEY, 'section_embed_options', array( 'field' => 'default_width' ) );
		add_settings_field( 'default_height',			'Default Player Height',	array( $this, 'field_preference_option' ), TP_PREFERENCES_OPTIONS_KEY, 'section_embed_options', array( 'field' => 'default_height' ) );

		add_settings_section( 'section_preferences_options', 'General Preferences', array( $this, 'section_preferences_desc' ), TP_PREFERENCES_OPTIONS_KEY );
		add_settings_field( 'filter_by_user_id',	'Filter Users Own Videos',		array( $this, 'field_preference_option' ), TP_PREFERENCES_OPTIONS_KEY, 'section_preferences_options', array( 'field' => 'filter_by_user_id' ) );
		add_settings_field( 'user_id_customfield',	'User ID Custom Field',			array( $this, 'field_preference_option' ), TP_PREFERENCES_OPTIONS_KEY, 'section_preferences_options', array( 'field' => 'user_id_customfield' ) );
		add_settings_field( 'mpx_server_id',		'MPX Upload Server',			array( $this, 'field_preference_option' ), TP_PREFERENCES_OPTIONS_KEY, 'section_preferences_options', array( 'field' => 'mpx_server_id' ) );
		add_settings_field( 'default_publish_id',	'Default Publishing Profile',	array( $this, 'field_preference_option' ), TP_PREFERENCES_OPTIONS_KEY, 'section_preferences_options', array( 'field' => 'default_publish_id' ) );
	}

	/*
	 * Registers the metadata options and appends the
	 * key to the plugin settings tabs array.
	 */
	function register_metadata_options() {

		//Check for uninitialized options	
		if ( !$this->account_is_verified || !$this->region_is_verified ) {
			return;
		}

		$this->plugin_settings_tabs[TP_METADATA_OPTIONS_KEY] = 'Custom Metadata';
		$this->metadata_fields = $this->tp_api->get_metadata_fields();
		add_settings_section( 'section_metadata_options', 'Custom Metadata Settings', array( $this, 'section_metadata_desc' ), TP_METADATA_OPTIONS_KEY );

		foreach ( $this->metadata_fields as $field ) {
			if ( !array_key_exists( $field['id'], $this->metadata_options ) ) {
				$this->metadata_options[$field['id']] = 'hide';
			}

			if ( $field['fieldName'] === $this->preferences['user_id_customfield'] ) {
				continue;
			}

			update_option( TP_METADATA_OPTIONS_KEY, $this->metadata_options );

			$types = TP_CUSTOM_FIELDS_TYPES();
			add_settings_field( $field['id'], $field['title'], array( $this, 'field_metadata_option' ), TP_METADATA_OPTIONS_KEY, 'section_metadata_options', array( 'id' => $field['id'], 'title' => $field['title'], 'fieldName' => $field['fieldName'] ) );
		}
	}

	/*
	 * Registers the upload options and appends the
	 * key to the plugin settings tabs array.
	 */
	function register_upload_options() {

		if ( !$this->account_is_verified || !$this->region_is_verified ) {
			return;
		}

		$this->plugin_settings_tabs[TP_UPLOAD_OPTIONS_KEY] = 'Basic Metadata';

		$upload_fields = TP_UPLOAD_FIELDS();

		add_settings_section( 'section_upload_options', 'Basic Metadata Settings', array( $this, 'section_upload_desc' ), TP_UPLOAD_OPTIONS_KEY );

		foreach ( $upload_fields as $field ) {
			if ( !array_key_exists( $field, $this->upload_options ) ) {
				$this->upload_options[$field] = 'write';
			}

			update_option( TP_UPLOAD_OPTIONS_KEY, $this->upload_options );

			$field_title = (strstr( $field, '$' ) !== false) ? substr( strstr( $field, '$' ), 1 ) : $field;

			add_settings_field( $field, ucfirst( $field_title ), array( $this, 'field_upload_option' ), TP_UPLOAD_OPTIONS_KEY, 'section_upload_options', array( 'field' => $field ) );
		}
	}

	/**
	 * Provide a description to the MPX Account Settings Section	 
	 */
	function section_mpx_account_desc() {
		echo 'Set your MPX credentials and Account. If you do not have an account, please reach out to MediaAMP.';
	}

	/**
	 * Provide a description to the MPX Prefences Section	 
	 */
	function section_preferences_desc() {
		echo 'Configure general preferences below.';
	}

	/**
	 * Provide a description to the MPX Embed Settings Section	 
	 */
	function section_embed_desc() {
		echo 'Configure embedding defaults.';
	}

	/**
	 * Provide a description to the MPX Metadata Section	 
	 */
	function section_metadata_desc() {
		echo 'Drag and drop the custom metadata fields that you would like to be readable, writable, or omitted when uploading and editing media.';
	}

	/**
	 * Provide a description to the MPX Upload Fields Section	 
	 */
	function section_upload_desc() {
		echo 'Drag and drop the basic metadata fields that you would like to be readable, writable, or omitted when uploading and editing media.';
	}

	/**
	 * MPX Account Option field callbacks.
	 */
	function field_account_option ( $args ) {
		$opts = get_option( TP_ACCOUNT_OPTIONS_KEY, array() );
		$field = $args['field'];
		
		switch ( $field ) {
			case 'mpx_account_id':
				$html = '<select id="' . esc_attr( $field ) . '" name="theplatform_account_options[' . esc_attr( $field ) . ']">';

				if ( $this->account_is_verified ) {
					$subaccounts = $this->tp_api->get_subaccounts();
					foreach ( $subaccounts as $account ) {
						$html .= '<option value="' . esc_attr( $account['id'] ) . '|' . esc_attr( $account['pid'] ) . '"' . selected( $opts[$field], $account['id'], false ) . '>' . esc_html( $account['title'] ) . '</option>';
					}
				}
				$html .= '</select>';

				if ( $this->account['mpx_account_id'] === '' ) {
					$html .= '<span style="color:red; font-weight:bold"> Please pick the MPX account to manage with Wordpress</span>';
				}
				break;
			case 'mpx_region':
				$html = '<select id="' . esc_attr( $field ) . '" name="theplatform_account_options[' . esc_attr( $field ) . ']">';
				$regions = $this->regions;
				foreach ( $regions as $region ) {
					$html .= '<option value="' . esc_attr( $region ) . '|' . esc_attr( $region ) . '"' . selected( $opts[$field], $region, false ) . '>' . esc_html( strtoupper( $region ) ) . '</option>';
				}
				$html .= '</select>';

				if ( !$this->region_is_verified ) {
					$html .= '<span style="color:red; font-weight:bold"> Please select the correct region the MPX account is located at</span>';
				}
				break;
			case 'mpx_password':
				$html = '<input id="mpx_password" type="password" name="theplatform_account_options[' . esc_attr( $field ) . ']" value="' . $opts[$field] . '" autocomplete="off" />';
				$html .= '<span id="verify-account"><button id="verify-account-button" type="button" name="verify-account-button">Verify Account Settings</button><div id="verify-account-dashicon" class="dashicons"></div></span>';
				break;
			case 'mpx_username':
				$html = '<input id="mpx_username" type="text" name="theplatform_account_options[' . esc_attr( $field ) . ']" value="' . esc_attr( $opts[$field] ) . '" autocomplete="off" />';
				break;
			case 'mpx_account_pid':
				$html = '<input disabled style="background-color: lightgray" id="mpx_account_pid" type="text" name="theplatform_account_options[' . esc_attr( $field ) . ']" value="' . esc_attr( $opts[$field] ) . '" />';
				break;
		}
		
		echo $html;
	}
	
	/**
	 * MPX Preferences Option field callbacks.
	 */
	function field_preference_option( $args ) {
		$opts = get_option( TP_PREFERENCES_OPTIONS_KEY, array() );
		$field = $args['field'];

		switch ( $field ) {
			case 'mpx_server_id':
				$html = '<select id="' . esc_attr( $field ) . '" name="theplatform_preferences_options[' . esc_attr( $field ) . ']">';
				if ( $this->account['mpx_account_id'] !== '' ) {
					$servers = $this->tp_api->get_servers();
					$html .= '<option value="DEFAULT_SERVER"' . selected( $opts[$field], "DEFAULT_SERVER", false ) . '>Default Server</option>';
					foreach ( $servers as $server ) {
						$html .= '<option value="' . esc_attr( $server['id'] ) . '"' . selected( $opts[$field], $server['id'], false ) . '>' . esc_html( $server['title'] ) . '</option>';
					}
				}

				$html .= '</select>';
				break;
			case 'embed_tag_type':
				$html = '<select id="' . esc_attr( $field ) . '" name="theplatform_preferences_options[' . esc_attr( $field ) . ']">';
				$html .= '<option value="IFrame"' . selected( $opts[$field], 'iframe', false ) . '>IFrame</option>';
				$html .= '<option value="Script"' . selected( $opts[$field], 'script', false ) . '>Script</option>';
				$html .= '</select>';
				break;
			case 'rss_embed_type':
				$html = '<select id="' . esc_attr( $field ) . '" name="theplatform_preferences_options[' . esc_attr( $field ) . ']">';
				$html .= '<option value="article"' . selected( $opts[$field], 'article', false ) . '>Article</option>';
				$html .= '<option value="iframe"' . selected( $opts[$field], 'iframe', false ) . '>IFrame</option>';
				$html .= '<option value="script"' . selected( $opts[$field], 'script', false ) . '>Script</option>';
				$html .= '</select>';
				break;			
			case 'default_player_name':
				$html = '<select id="' . esc_attr( $field ) . '" name="theplatform_preferences_options[' . esc_attr( $field ) . ']">';
				if ( $this->account['mpx_account_id'] !== '' ) {
					$players = $this->tp_api->get_players();
					foreach ( $players as $player ) {
						$html .= '<option value="' . esc_attr( $player['id'] ) . '|' . esc_attr( $player['pid'] ) . '"' . selected( $opts[$field], $player['id'], false ) . '>' . esc_html( $player['title'] ) . '</option>';
					}
				}
				$html .= '</select>';
				break;
			case 'default_publish_id':
				$html = '<select id="' . esc_attr( $field ) . '" name="theplatform_preferences_options[' . esc_attr( $field ) . ']">';
				$html .= '<option value="tp_wp_none">Do not publish</option>';

				if ( $this->account['mpx_account_id'] !== '' ) {
					$profiles = $this->tp_api->get_publish_profiles();
					foreach ( $profiles as $profile ) {
						$html .= '<option value="' . esc_attr( $profile['title'] ) . '"' . selected( $opts[$field], $profile['title'], false ) . '>' . esc_html( $profile['title'] ) . '</option>';
					}
				}
				$html .= '</select>';
				break;
			case 'default_player_pid':
				$html = '<input disabled style="background-color: lightgray" id="default_player_pid" type="text" name="theplatform_preferences_options[' . esc_attr( $field ) . ']" value="' . esc_attr( $opts[$field] ) . '" />';
				break;
			case 'filter_by_user_id':
			case 'autoplay':
				$html = '<select id="' . esc_attr( $field ) . '"" name="theplatform_preferences_options[' . esc_attr( $field ) . ']"/>';
				$html .= '<option value="TRUE" ' . selected( $opts[$field], 'TRUE', false ) . '>True</option>';
				$html .= '<option value="FALSE" ' . selected( $opts[$field], 'FALSE', false ) . '>False</option>';
				$html .= '</select>';
				break;
			case 'user_id_customfield':
				$html = '<select id="' . esc_attr( $field ) . '"" name="theplatform_preferences_options[' . esc_attr( $field ) . ']"/>';
				$html .= '<option value="(None)" ' . selected( $opts[$field], '(None)', false ) . '>(None)</option>';
				foreach ( $this->metadata_fields as $metadata ) {
					$html .= '<option value="' . esc_attr( $metadata['fieldName'] ) . '" ' . selected( $opts[$field], $metadata['fieldName'], false ) . '>' . esc_html( $metadata['title'] ) . '</option>';
				}
				$html .= '</select>';
				break;
			default:
				$html = '<input type="text" id="' . esc_attr( $field ) . '" name="theplatform_preferences_options[' . esc_attr( $field ) . ']" value="' . esc_attr( $opts[$field] ) . '" />';
				break;
		}
		echo $html;
	}

	/**
	 * Metadata Option field callback.
	 */
	function field_metadata_option( $args ) {
		$field_id = $args['id'];

		$html = '<select id="' . esc_attr( $field_id ) . '" name="theplatform_metadata_options[' . esc_attr( $field_id ) . ']" class="sortableField">';
		$html .= '<option value="read"' . selected( $this->metadata_options[$field_id], 'read', false ) . '>Read</option>';
		$html .= '<option value="write"' . selected( $this->metadata_options[$field_id], 'write', false ) . '>Write</option>';
		$html .= '<option value="hide"' . selected( $this->metadata_options[$field_id], 'hide', false ) . '>Hide</option>';
		$html .= '</select>';

		echo $html;
	}

	/**
	 * Upload Option field callback.
	 */
	function field_upload_option( $args ) {
		$field = $args['field'];

		$html = '<select id="' . esc_attr( $field ) . '" name="theplatform_upload_options[' . esc_attr( $field ) . ']" class="sortableField">';
		$html .= '<option value="read"' . selected( $this->upload_options[$field], 'read', false ) . '>Read</option>';
		$html .= '<option value="write"' . selected( $this->upload_options[$field], 'write', false ) . '>Write</option>';
		$html .= '<option value="hide"' . selected( $this->upload_options[$field], 'hide', false ) . '>Hide</option>';
		$html .= '</select>';

		echo $html;
	}

	/**
	 * Called during admin_menu, adds an options
	 * page under Settings called My Settings, rendered
	 * using the plugin_options_page method.
	 */
	function add_admin_menus() {
		add_options_page( 'MediaAMP Plugin Settings', 'MediaAMP', 'manage_options', $this->plugin_options_key, array( $this, 'plugin_options_page' ) );
	}

	/**
	 * Plugin Options page rendering goes here, checks
	 * for active tab and replaces key with the related
	 * settings key. Uses the plugin_options_tabs method
	 * to render the tabs.
	 */
	function plugin_options_page() {
		$tab = isset( $_GET['tab'] ) ? $_GET['tab'] : TP_ACCOUNT_OPTIONS_KEY;
		
		?>
		<div class="wrap">
		<?php $this->plugin_options_tabs(); ?>
			<form method="POST" action="options.php" autocomplete="off">
			<?php settings_fields( $tab ); ?>
			<?php do_settings_sections( $tab ); ?>
				<?php submit_button(); ?>
			</form>
		</div>
		<?php
	}

	/**
	 * Renders our tabs in the plugin options page,
	 * walks through the object's tabs array and prints
	 * them one by one. Provides the heading for the
	 * plugin_options_page method.
	 */
	function plugin_options_tabs() {
		$current_tab = isset( $_GET['tab'] ) ? $_GET['tab'] : TP_ACCOUNT_OPTIONS_KEY;

		screen_icon( 'theplatform' );
		echo '<h2 class="nav-tab-wrapper">';
		foreach ( $this->plugin_settings_tabs as $tab_key => $tab_caption ) {
			$active = $current_tab == $tab_key ? 'nav-tab-active' : '';
			$url = '?page=' . $this->plugin_options_key . '&tab=' . $tab_key;
			echo '<a class="nav-tab ' . $active . '" href="' . esc_url( $url ) . '">' . $tab_caption . '</a>';
		}
		echo '</h2>';
	}
}

if ( !class_exists( 'ThePlatform_API' ) ) {
	require_once( dirname( __FILE__ ) . '/thePlatform-API.php' );
}

new ThePlatform_Options;
		