<?php

function dropdown_options_validate($input) {	
	foreach ($input as $key => $value) {	
		if ($value != "allow" && $value != "omit") {
			$input[$key] = "allow";
		}			
	}
	return $input;
}
function connection_options_validate($input) {	
	if ( ! is_array( $input ) ) 
	{
		return array(
			'mpx_account_id' => '',
			'mpx_username' => 'mpx/',				
			'mpx_password' => '',
			'mpx_namespace' => '',
			'videos_per_page' => 16,
			'default_sort' => 'id',
			'video_type' => 'embed',				
			'mpx_account_pid' => '',
			'default_player_name' => '',
			'default_player_pid' => '',
			'mpx_server_id' => '',
			'default_publish_id' => ''
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

if ( ! class_exists( 'ThePlatform_API' ) )
	require_once( dirname(__FILE__) . '/thePlatform-API.php' );

class ThePlatform_Options {
	
	private $preferences_options_key = 'theplatform_preferences_options';
	private $metadata_options_key = 'theplatform_metadata_options';
	private $upload_options_key = 'theplatform_upload_options';

	private $account_is_verified;
	
	/*
	 * WP Option key
	 */
	private $plugin_options_key = 'theplatform';
	
	/*
	 * An array of tabs representing the admin settings interface.
	 */
	private $plugin_settings_tabs = array();

	private $tp_api;
	private $preferences;	

	
	/*
	 * Fired during plugins_loaded
	 */
	function __construct() {
		
		add_action('admin_init', array( &$this, 'load_options' ), 1 );
		add_action('admin_init', array( &$this, 'register_preferences_options' ), 2 );
		add_action('admin_init', array( &$this, 'register_plugin_settings' ) );		
		add_action('admin_init', array( &$this, 'register_metadata_options' ) );
		add_action('admin_init', array( &$this, 'register_upload_options' ) );
		add_action('admin_menu', array( &$this, 'add_admin_menus' ) );
		add_action('admin_enqueue_scripts', array(&$this, 'enqueue_scripts'));
		add_action('wp_ajax_verify_account', array(&$this, 'verify_account_settings'));

		$this->tp_api = new ThePlatform_API;

		
	}
	
	/**
	 * Enqueue our javascript file
	 */
	function enqueue_scripts() {
		wp_enqueue_script('jquery');  
		wp_enqueue_script('theplatform_js');
		wp_enqueue_style('theplatform_css');
	}
	
	function internal_verify_account_settings()
	{
		if (!current_user_can('manage_options')) {
			wp_die('<p>'.__('You do not have sufficient permissions to manage this plugin').'</p>');
		}

		$hash = base64_encode($this->preferences['mpx_username'] . ':' . $this->preferences['mpx_password']);

		$response = ThePlatform_API_HTTP::get(TP_API_SIGNIN_URL, array('headers' => array('Authorization' => 'Basic ' . $hash)));

		$payload = decode_json_from_server($response, TRUE, FALSE);

		if (is_null($response)) {
			return FALSE;
		}

		if (!array_key_exists('isException', $payload)) {						
			return TRUE;					
		} else {						
			return FALSE;			
		}		
	}
	/**
	 *	AJAX callback for account verification button
	 */
	function verify_account_settings() {
		//User capability check
		check_admin_referer('plugin-name-action_tpnonce'); 
		if (!current_user_can('upload_files')) {
			wp_die('<p>'.__('You do not have sufficient permissions to edit this plugin').'</p>');
		}

		$hash = $_POST['auth_hash'];

		$response = ThePlatform_API_HTTP::get(TP_API_SIGNIN_URL, array('headers' => array('Authorization' => 'Basic ' . $hash)));
	
		$payload = decode_json_from_server($response, TRUE);

		if (!array_key_exists('isException', $payload)) {			
			$this->register_preferences_options();	
			$account_is_verified = TRUE;		
			echo "success";
		} else {						
			$account_is_verified = FALSE;
			echo "failed";
		}		
	
		die();		
	}
	
	/**
	 * Loads thePlatform plugin options from
	 * the database into their respective arrays. Uses
	 * array_merge to merge with default values if they're
	 * missing.
	 */
	function load_options() {
				
		// Get existing options, or empty arrays if no options exist
		$this->preferences_options = get_option($this->preferences_options_key, array());
		$this->metadata_options = get_option($this->metadata_options_key, array());
		$this->upload_options = get_option($this->upload_options_key, array());
		
		// Initialize option defaults		
		$this->preferences_options = array_merge(array(
			'mpx_account_id' => '',
			'mpx_username' => 'mpx/',
			'mpx_password' => '',
			'mpx_namespace' => '',
			'videos_per_page' => 16,
			'default_sort' => 'id',
			'video_type' => 'embed',			
			'mpx_account_pid' => '',
			'default_player_name' => '',
			'default_player_pid' => '',
			'mpx_server_id' => '',
			'default_publish_id' => ''
		), $this->preferences_options);
			
		$this->metadata_options = array_merge(array(), $this->metadata_options);
				
		$this->upload_options = array_merge(array(), $this->upload_options);
				
		// Create options table entries in DB if none exist. Initialize with defaults
		update_option($this->preferences_options_key, $this->preferences_options);
		update_option($this->metadata_options_key, $this->metadata_options);
		update_option($this->upload_options_key, $this->upload_options);

		//Get preferences from the database for sanity checks
		$this->preferences = get_option('theplatform_preferences_options');

		$this->account_is_verified = $this->internal_verify_account_settings();
	}
	
	function register_plugin_settings() {
		register_setting( $this->preferences_options_key, $this->preferences_options_key, 'connection_options_validate'); 
		register_setting( $this->metadata_options_key, $this->metadata_options_key, 'dropdown_options_validate'); 
		register_setting( $this->upload_options_key, $this->upload_options_key, 'dropdown_options_validate'); 
	}


	
	/*
	 * Registers the preference options via the Settings API,
	 * appends the setting to the tabs array of the object.
	 */
	function register_preferences_options() {
		$this->plugin_settings_tabs[$this->preferences_options_key] = 'Preferences';			 	

		add_settings_section( 'section_mpx_account_options', 'MPX Account Options', array( &$this, 'section_mpx_account_desc' ), $this->preferences_options_key );
		add_settings_field( 'mpx_username_option', 'MPX Username', array( &$this, 'field_preference_option' ), $this->preferences_options_key, 'section_mpx_account_options', array('field' => 'mpx_username') );
		add_settings_field( 'mpx_password_option', 'MPX Password', array( &$this, 'field_preference_option' ), $this->preferences_options_key, 'section_mpx_account_options', array('field' => 'mpx_password') );								
						
		if (!$this->account_is_verified)
			return;

		add_settings_field( 'mpx_accountid_option', 'MPX Account', array( &$this, 'field_preference_option' ), $this->preferences_options_key, 'section_mpx_account_options', array('field' => 'mpx_account_id') );
		add_settings_field( 'mpx_account_pid', 'MPX Account PID', array( &$this, 'field_preference_option' ), $this->preferences_options_key, 'section_mpx_account_options', array('field' => 'mpx_account_pid') ); 		

		add_settings_field( 'mpx_namespace_option', 'MPX Namespace', array( &$this, 'field_preference_option' ), $this->preferences_options_key, 'section_mpx_account_options', array('field' => 'mpx_namespace') );
	
		add_settings_section( 'section_preferences_options', 'General Preferences', array( &$this, 'section_preferences_desc' ), $this->preferences_options_key );		
		add_settings_field( 'default_player_name', 'Default Player', array( &$this, 'field_preference_option' ), $this->preferences_options_key, 'section_preferences_options', array('field' => 'default_player_name') );
		add_settings_field( 'default_player_pid', 'Default Player PID', array( &$this, 'field_preference_option' ), $this->preferences_options_key, 'section_preferences_options', array('field' => 'default_player_pid') );
		add_settings_field( 'videos_per_page_option', 'Number of Videos', array( &$this, 'field_preference_option' ), $this->preferences_options_key, 'section_preferences_options', array('field' => 'videos_per_page') );
		add_settings_field( 'default_sort_order_option', 'Default Sort Order', array( &$this, 'field_preference_option' ), $this->preferences_options_key, 'section_preferences_options', array('field' => 'default_sort') );
 		add_settings_field( 'video_type_option', 'Default Video Type', array( &$this, 'field_preference_option' ), $this->preferences_options_key, 'section_preferences_options', array('field' => 'video_type') );
 		add_settings_field( 'mpx_server_id', 'Default Upload Server', array( &$this, 'field_preference_option' ), $this->preferences_options_key, 'section_preferences_options', array('field' => 'mpx_server_id') );
 		add_settings_field( 'default_publish_id', 'Default Publishing Profile', array( &$this, 'field_preference_option' ), $this->preferences_options_key, 'section_preferences_options', array('field' => 'default_publish_id') ); 		
 		
	}

	/*
	 * Registers the metadata options and appends the
	 * key to the plugin settings tabs array.
	 */
	function register_metadata_options() {

		//Check for uninitialized options	
		if (!$this->account_is_verified)
				return;
			

		$this->plugin_settings_tabs[$this->metadata_options_key] = 'Metadata';			
		
		$this->metadata_fields = $this->tp_api->get_metadata_fields();
		
		add_settings_section( 'section_metadata_options', 'Metadata Settings', array( &$this, 'section_metadata_desc' ), $this->metadata_options_key );
		
		foreach ($this->metadata_fields as $field) {
			if (!array_key_exists($field['id'], $this->metadata_options)) {
				$this->metadata_options[$field['id']] = 'allow';
			}
			
			update_option($this->metadata_options_key, $this->metadata_options);
		
			add_settings_field( $field['id'], $field['title'], array( &$this, 'field_metadata_option' ), $this->metadata_options_key, 'section_metadata_options', array('id' => $field['id'], 'title' => $field['title']));
		}
	}
		
	/*
	 * Registers the upload options and appends the
	 * key to the plugin settings tabs array.
	 */
	function register_upload_options() {

		if (!$this->account_is_verified)
			return;

		$this->plugin_settings_tabs[$this->upload_options_key] = 'Upload Fields';
		
		$upload_fields = array(
			'title',			
			'description',			
			'media$categories',
			'author',
			'media$keywords',
			'link',
			'guid'
		);
		
		add_settings_section( 'section_upload_options', 'Upload Field Settings', array( &$this, 'section_upload_desc' ), $this->upload_options_key );

		foreach ($upload_fields as $field) {
			if (!array_key_exists($field, $this->upload_options)) {
				$this->upload_options[$field] = 'allow';
			}
			
			update_option($this->upload_options_key, $this->upload_options);
		
			$field_title = (strstr($field, '$') !== false) ? substr(strstr($field, '$'), 1) : $field;
		
			add_settings_field( $field, ucfirst($field_title), array( &$this, 'field_upload_option' ), $this->upload_options_key, 'section_upload_options', array('field' => $field));
		}
	}
	
	/*
	 * The following methods provide descriptions
	 * for their respective sections, used as callbacks
	 * with add_settings_section
	 */
	function section_mpx_account_desc() { 
		echo 'Set your MPX credentials here. These should have been provided to you when creating your account with thePlatform.'; 
	}
	
	function section_preferences_desc() { 
		echo 'Configure general plugin preferences below.'; 
	}
	
	function section_metadata_desc() { 
		echo 'Select the metadata fields that you would like to be allowed or omitted when querying MPX.'; 
	}
	
	function section_upload_desc() { 
		echo 'Select the fields that you would like to be allowed or omitted when uploading media to MPX.'; 
	}
	/*
	 * MPX Account Option field callbacks.
	 */
	function field_preference_option($args) {
		$opts = get_option($this->preferences_options_key, array());
		$field = $args['field'];

		if ($field == 'mpx_server_id') {		
		
			$servers = $this->tp_api->get_servers();
		
 			$html = '<select id="' . esc_attr($field) . '" name="theplatform_preferences_options[' . esc_attr($field) . ']">';
 			
 			foreach ($servers as $server) {
 				$html .= '<option value="' . esc_attr($server['id']) . '"' . selected( $opts[$field], $server['id'], false) . '>' . esc_html($server['title']) . '</option>';
 			}
 			
 			$html .= '</select>';
 		} else if ($field == 'mpx_account_id') {
 			
			$subaccounts = $this->tp_api->get_subaccounts();
		
 			$html = '<select id="' . esc_attr($field) . '" name="theplatform_preferences_options[' . esc_attr($field) . ']">';
 			
 			foreach ($subaccounts as $account) {
 				$html .= '<option value="' . esc_attr($account['id']) . '|' . esc_attr($account['placcount$pid']) . '"' . selected( $opts[$field], $account['id'], false) . '>' . esc_html($account['title']) . '</option>';
 			}
 			
 			$html .= '</select>';
 		} else if ($field == 'video_type') {
 			$html = '<select id="' . esc_attr($field) . '" name="theplatform_preferences_options[' . esc_attr($field) . ']">';  
        		$html .= '<option value="embed"' . selected( $opts[$field], 'embed', false) . '>Embed</option>';  
        		$html .= '<option value="full"' . selected( $opts[$field], 'full', false) . '>Full Player</option>';  
        	$html .= '</select>';
 		} else if ($field == 'default_sort') {
 			$html = '<select id="' . esc_attr($field) . '" name="theplatform_preferences_options[' . esc_attr($field) . ']">';  
        		$html .= '<option value="title"' . selected( $opts[$field], 'title', false) . '>Title - Ascending</option>';  
        		$html .= '<option value="title|desc"' . selected( $opts[$field], 'title|desc', false) . '>Title - Descending</option>';  
        		$html .= '<option value="author"' . selected( $opts[$field], 'author', false) . '>Author - Ascending</option>';
        		$html .= '<option value="author|desc"' . selected( $opts[$field], 'author|desc', false) . '>Author - Descending</option>'; 
        		$html .= '<option value="added"' . selected( $opts[$field], 'added', false) . '>Date Added - Ascending</option>'; 
        		$html .= '<option value="added|desc"' . selected( $opts[$field], 'added|desc', false) . '>Date Added - Descending</option>';   
    		$html .= '</select>';
 		} else if ($field == 'mpx_password') {
 			$html = '<input id="mpx_password" type="password" name="theplatform_preferences_options[' . esc_attr($field) . ']" value="' . esc_attr( $opts[$field] ) . '" />';
 			$html .= '<span id="verify-account"><button id="verify-account-button" type="button" name="verify-account-button">Verify Account Settings</button></span>';
 		} else if ($field == 'mpx_username') {
 			$html = '<input id="mpx_username" type="text" name="theplatform_preferences_options[' . esc_attr($field) . ']" value="' . esc_attr( $opts[$field] ) . '" />';
 		} else if ($field == 'mpx_account_pid') {
 			$html = '<input disabled style="background-color: lightgray" id="mpx_account_pid" type="text" name="theplatform_preferences_options[' . esc_attr($field) . ']" value="' . esc_attr( $opts[$field] ) . '" />';
 		} else if ($field == "default_player_name") {
 			$players = $this->tp_api->get_players();
		
 			$html = '<select id="' . esc_attr($field) . '" name="theplatform_preferences_options[' . esc_attr($field) . ']">';
 			
 			foreach ($players as $player) {
 				$html .= '<option value="' . esc_attr($player['id']) . '|' . esc_attr($player['plplayer$pid']) . '"' . selected( $opts[$field], $player['id'], false) . '>' . esc_html($player['title']) . '</option>';
 			}
 			
 			$html .= '</select>'; 			
 		}
 		else if ($field == "default_publish_id") {
 			$profiles = $this->tp_api->get_publish_profiles();
		
 			$html = '<select id="' . esc_attr($field) . '" name="theplatform_preferences_options[' . esc_attr($field) . ']">';
 			$html .= '<option value="tp_wp_none">Do not publish</option>';
 			foreach ($profiles as $profile) {
 				$html .= '<option value="' . esc_attr($profile['title']) . '"' . selected( $opts[$field], $profile['title'], false) . '>' . esc_html($profile['title']) . '</option>';
 			}
 			
 			$html .= '</select>'; 			
 		}
 		else if ($field == 'default_player_pid') {
 			$html = '<input disabled style="background-color: lightgray" id="default_player_pid" type="text" name="theplatform_preferences_options[' . esc_attr($field) . ']" value="' . esc_attr( $opts[$field] ) . '" />';
 		}
 		else {
 			$html = '<input type="text" name="theplatform_preferences_options[' . esc_attr($field) . ']" value="' . esc_attr( $opts[$field] ) . '" />';
 		}
 		
 		echo $html;
	}
	
	
	/*
	 * Metadata Option field callback.
	 */
	function field_metadata_option($args) {
		$field_id = $args['id'];
		$field_title = $args['title'];			

		$html = '<select id="' . esc_attr($field_id) . '" name="theplatform_metadata_options[' . esc_attr($field_id) . ']">';  
			$html .= '<option value="allow"' . selected( $this->metadata_options[$field_id], 'allow', false) . '>Allow</option>';    
			$html .= '<option value="omit"' . selected( $this->metadata_options[$field_id], 'omit', false) . '>Omit</option>';  
		$html .= '</select>';
	
    	echo $html; 
	}

	/*
	 * Upload Option field callback.
	 */
	function field_upload_option($args) {
		$field = $args['field'];

		$html = '<select id="' . esc_attr($field) . '" name="theplatform_upload_options[' . esc_attr($field) . ']">';  
			$html .= '<option value="allow"' . selected( $this->upload_options[$field], 'allow', false) . '>Allow</option>';    
			$html .= '<option value="omit"' . selected( $this->upload_options[$field], 'omit', false) . '>Omit</option>';  
		$html .= '</select>';
	
    	echo $html; 
	}
	
	/*
	 * Called during admin_menu, adds an options
	 * page under Settings called My Settings, rendered
	 * using the plugin_options_page method.
	 */
	function add_admin_menus() {
		add_options_page( 'thePlatform Plugin Settings', 'thePlatform', 'manage_options', $this->plugin_options_key, array( &$this, 'plugin_options_page' ) );
	}

	
	/*
	 * Plugin Options page rendering goes here, checks
	 * for active tab and replaces key with the related
	 * settings key. Uses the plugin_options_tabs method
	 * to render the tabs.
	 */
	function plugin_options_page() {
		$tab = isset( $_GET['tab'] ) ? $_GET['tab'] : $this->preferences_options_key;

		?>
		<div class="wrap">
			<?php $this->plugin_options_tabs(); ?>
			<form method="post" action="options.php">
				<?php wp_nonce_field( 'plugin-name-action_tpnonce' ); ?>
				<?php settings_fields( $tab ); ?>
				<?php do_settings_sections( $tab ); ?>
				<?php submit_button(); ?>
			</form>
		</div>
		<?php
	}
	
	/*
	 * Renders our tabs in the plugin options page,
	 * walks through the object's tabs array and prints
	 * them one by one. Provides the heading for the
	 * plugin_options_page method.
	 */
	function plugin_options_tabs() {
		$current_tab = isset( $_GET['tab'] ) ? $_GET['tab'] : $this->preferences_options_key;

		screen_icon('theplatform');
		echo '<h2 class="nav-tab-wrapper">';
		foreach ( $this->plugin_settings_tabs as $tab_key => $tab_caption ) {
			$active = $current_tab == $tab_key ? 'nav-tab-active' : '';
			echo '<a class="nav-tab ' . $active . '" href="?page=' . $this->plugin_options_key . '&tab=' . $tab_key . '">' . $tab_caption . '</a>';	
		}
		echo '</h2>';
	}
};

// Initialize the plugin
new ThePlatform_Options;