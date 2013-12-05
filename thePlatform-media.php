<?php 

if (!class_exists( 'ThePlatform_API' )) {
	require_once( dirname(__FILE__) . '/thePlatform-API.php' );
}

if (!current_user_can('upload_files')) {
		wp_die('<p>'.__('You do not have sufficient permissions to modify MPX Media').'</p>');
}

$preferences = get_option('theplatform_preferences_options');	

if (strcmp($preferences['mpx_account_id'], "") == 0) {			
			wp_die('MPX Account ID is not set, please configure the plugin before attempting to manage media');
}
	
/*
 * Load scripts and styles 
 */
wp_enqueue_script('jquery');
wp_enqueue_script('theplatform_js');
wp_enqueue_script('nprogress_js');
wp_enqueue_script('set-post-thumbnail');
wp_enqueue_script('jquery-ui-progressbar');
wp_enqueue_script('thickbox');

wp_enqueue_style('theplatform_css');
wp_enqueue_style('nprogress_css');
wp_enqueue_style('global');
wp_enqueue_style('media');
wp_enqueue_style('wp-admin');
wp_enqueue_style('colors');
wp_enqueue_style('jquery-ui-progressbar');

?>

<div class="wrap">
	<?php screen_icon('theplatform'); ?>	
	<h2><div style="clear:both;"></div> </h2>
	<?php
	
	

	$tp_api = new ThePlatform_API;
	$metadata = $tp_api->get_metadata_fields();
	
	if ( is_wp_error( $metadata ) )
		echo '<div id="message" class="error below-h2"><p>' . esc_html($metadata->get_error_message()) . '</p></div>';
	
	//Update media handler
	if ( isset( $_POST['submit'] ) && $_POST['submit'] == 'Save Changes' ) {

		check_admin_referer('plugin-name-action_tpnonce'); 
		if (!current_user_can('upload_files')) {	 		
			wp_die('<p>'.__('You do not have sufficient permissions to edit Media.').'</p>');
		}
		// Update media item in detail view		

		unset($_GET['edit']);

		$payload = array(
			'$xmlns' => array(
				"dcterms" => "http://purl.org/dc/terms/",
				"media" => "http://search.yahoo.com/mrss/",
				"pl" => "http://xml.theplatform.com/data/object",
				"pla" => "http://xml.theplatform.com/data/object/admin",
				"plmedia" => "http://xml.theplatform.com/media/data/Media",
				"plfile" => "http://xml.theplatform.com/media/data/MediaFile",
				"plrelease" => "http://xml.theplatform.com/media/data/Release"				
			),
			'id' => $_POST['edit_id']
		);
		
		if (isset($_POST['media$categories'])) {
			$_POST['media$categories'] = array(array('media$name' => sanitize_text_field($_POST['media$categories'])));
		}				
		
		$upload_options = get_option('theplatform_upload_options');
		
		foreach ($upload_options as $upload_field => $val) {
			if ($val == 'allow')
				$payload[$upload_field] = sanitize_text_field($_POST[$upload_field]);
		}
		
		$payloadJSON = json_encode($payload, JSON_UNESCAPED_SLASHES);
	
 		$tp_api->update_media($payloadJSON);
		
		$response = $tp_api->get_videos();
		if ( is_wp_error( $response ) )
			echo '<div id="message" class="error below-h2"><p>' . esc_html($response->get_error_message()) . '</p></div>';
	} 
	
	if ( !empty( $_GET['edit'] ) ) {
		// Detail view + Media editor
		$response = $tp_api->get_video_by_id(sanitize_text_field($_GET['edit']));
		
		if ( is_wp_error( $response ) )
			echo '<div id="message" class="error below-h2"><p>' . esc_html($response->get_error_message()) . '</p></div>';
	} else {
		// Search Results
	
		if (isset($_GET['s'])) {
			$key_word = isset( $_GET['s'] ) ? sanitize_text_field( $_GET['s'] ) : '';
			$field = isset( $_GET['theplatformsearchfield'] ) ? sanitize_text_field( $_GET['theplatformsearchfield'] ) : '';
			$sort = isset( $_GET['theplatformsortfield'] ) ? sanitize_text_field( $_GET['theplatformsortfield'] ) : '';

					
			if ($field == 'byTitle') {	
				$response = $tp_api->get_videos($field . '=' . urlencode($key_word), $sort);
			} else if ($field == 'q') {
				$response = $tp_api->get_videos($field . '=' . urlencode($key_word));
			} else if ($field == 'All' && $sort != '') {
				$response = $tp_api->get_videos('', $sort);
			} else {
			 	$query = '{' . $field . '}{' . $key_word . '}';
				$response = $tp_api->get_videos('byCustomValue' . '=' . urlencode($query), $sort);
			}
			
			if ( is_wp_error( $response ) )
				echo '<div id="message" class="error below-h2"><p>' . esc_html($response->get_error_message()) . '</p></div>';		
		} else {
			// Library View	

			$response = $tp_api->get_videos();

			if ( is_wp_error( $response ) )
				echo '<div id="message" class="error below-h2"><p>' . esc_html($response->get_error_message()) . '</p></div>';			
		}

	} ?>


	<?php if ( !is_wp_error( $response ) ) {
		$videos = decode_json_from_server($response, TRUE);
		$videos = stripslashes_deep( $videos );
		if (empty( $videos['entries']) )
			echo 'No media present.';

		if ( !empty( $_GET['edit'] ) ) : 
			$video = $videos['entries'][0];			
			$embed_id = NULL;
				if (is_array($video['media$content'])) {			
					foreach ($video['media$content'] as $value) {
						foreach ($value['plfile$releases'] as $key => $value) {
							if ($value['plrelease$delivery'] == "streaming") {
								$embed_id = $value['plrelease$pid'];							
							}		
						break;						
						}					
					}	
				}	
			?>
			<form id="theplatform-edit-media" method="post" action="<?php echo menu_page_url( 'theplatform-media', false ); ?>">
				<?php wp_nonce_field( 'plugin-name-action_tpnonce' ); ?>
				<input type="hidden" name="edit_id" value="<?php echo esc_attr( $video['id'] ); ?>" />
				<span class="theplatform-media-edit">
					<div class="theplatform-media-thumbnail">
						<div class="photo">
							<?php
							if (is_null($embed_id)) 
								echo '<img src="' . esc_url($video['plmedia$defaultThumbnailUrl']) . '">';
							else {
								$url = 'http://player.theplatform.com/p/' . $preferences['mpx_account_pid'] . '/' .  $preferences['default_player_pid'] . '/embed/select/' .  $embed_id . '?form=html';
								echo '<iframe src="' . esc_url($url) . '" width="256" height="176" frameBorder="0" seamless="seamless" allowFullScreen></iframe>';
							}
							?>
						</div>
					</div>
					<div style="float: left; margin: 4px 4px 4px 40px; padding-left: 40px; border-left: 1px solid #DFDFDF;">
						<table class="form-table">
						<tbody>
							<?php
								$upload_options = get_option('theplatform_upload_options');
								$html = '';

								foreach ($upload_options as $upload_field => $val) {
									$field_title = (strstr($upload_field, '$') !== false) ? substr(strstr($upload_field, '$'), 1) : $upload_field;
							
									if ($val == 'allow') {
										if ($upload_field == 'media$categories') {
											$params = array(
												'token' => $tp_api->mpx_signin(),
												'fields' => 'title,fullTitle',
												'account' => $preferences['mpx_account_id']
											);
									
											$response = $tp_api->query('MediaCategory', 'get', $params);
										
											$tp_api->mpx_signout($params['token']);
										
											if (!is_wp_error($response)) {
												$categories = decode_json_from_server($response, TRUE);
										
												$html = '<tr valign="top"><th scope="row">Category</th><td><select class="upload_field" id="theplatform_upload_' . esc_attr($field) . '" name="' . esc_attr($upload_field) . '">';
			
												foreach ($categories['entries'] as $category) {
													$selected = $category['plcategory$fullTitle'] == $video[$upload_field][0]['media$name'] ? ' selected="selected"' : '';
													$html .= '<option value="' . esc_attr($category['plcategory$fullTitle']) . '" ' . esc_attr($selected) . '>' . esc_html($category['title']) . '</option>';
												}
			
												$html .= '</select></td></tr>';
												echo $html;
											}
										} else {											
											$field_value = $video[$upload_field];																						
											$html = '<tr valign="top"><th scope="row">' . esc_html(ucfirst($field_title)) . '</th><td><input name="' . esc_attr($upload_field) . '" id="theplatform_upload_' . esc_attr($upload_field) . '" class="edit_field" type="text" value="' . esc_attr($field_value) . '"/></td></tr>';
											echo $html;
										}
									}
								}							
							?>
						</tbody>
						</table>
					</div>
				</span>
				<div style="clear:both;"></div>
				 <p class="submit">
					<button id="theplatform_edit_submit_button" class="button button-primary" type="submit" name="submit" value="Save Changes">Save Changes</button>
					<button id="theplatform_cancel_edit_button" class="button" type="button" name="theplatform-cancel-edit-button">Cancel</button>
				</p>
					
			</form>
			
		<?php else : ?>
			<div id="media-mpx-upload-form" style="display: none;">
					<input type="hidden" name="page" value="theplatform-media" />
     				<table class="form-table">
     					<?php
     						$upload_options = get_option('theplatform_upload_options');
     						$html = '';
     						
     						foreach ($upload_options as $upload_field => $val) {
     							$field_title = (strstr($upload_field, '$') !== false) ? substr(strstr($upload_field, '$'), 1) : $upload_field;
     						
     							if ($val == 'allow') {
     								if ($upload_field == 'media$categories') {
     									$params = array(
											'token' => $tp_api->mpx_signin(),
											'fields' => 'title,fullTitle',
											'account' => $preferences['mpx_account_id']
										);
     								
     									$response = $tp_api->query('MediaCategory', 'get', $params);

     									$tp_api->mpx_signout($params['token']);
     									
     									if (!is_wp_error($response)) {
     										$categories = decode_json_from_server($response, TRUE);
     									
											$html = '<tr valign="top"><th scope="row">Category</th><td><select class="upload_field" id="theplatform_upload_' . esc_attr($field) . '" name="' . esc_attr($upload_field) . '">';
			
											foreach ($categories['entries'] as $category) {
												$html .= '<option value="' . esc_attr($category['plcategory$fullTitle']) . '">' . esc_html($category['title']) . '</option>';
											}
			
											$html .= '</select></td></tr>';
											echo $html;
										}
     								} else {
     									$html = '<tr valign="top"><th scope="row">' . esc_html(ucfirst($field_title)) . '</th><td><input name="' . esc_attr($upload_field) . '" id="theplatform_upload_' . esc_attr($upload_field) . '" class="upload_field" type="text" /></td></tr>';
     									echo $html;
     								}
     							}
     						}
     						
     					?>
     					<tr valign="top"><th scope="row">Publishing Profile</th>
     						<td>
     							<?php     								
     									$profiles = $tp_api->get_publish_profiles();     								
     									$html = '<select name="profile" id="publishing_profile" name="publishing_profile" class="upload_profile">';  											
     									$html .= '<option value="tp_wp_none">Do not publish</option>'; 
											foreach($profiles as $entry) {
												if ($entry['title'] == $preferences['default_publish_id'])													
													$html .= '<option value="' . esc_attr($entry['title']) . '" selected="selected">' . esc_html($entry['title']) . '</option>'; 
												else
													$html .= '<option value="' . esc_attr($entry['title']) . '">' . esc_html($entry['title']) . '</option>'; 
											}
										$html .= '</select>';
										echo $html;
     								
     								
     							?>
     						</td>
     					</tr>
     					<tr valign="top"><th scope="row">File</th><td><input type="file" accept="video/*" id="theplatform_upload_file" /></td></tr>
     				</table>
     				<p class="submit">
     					<button id="theplatform_upload_button" class="button button-primary" type="button" name="theplatform-upload-button">Upload Video</button>
     					<button id="theplatform_cancel_upload_button" class="button" type="button" name="theplatform-cancel-upload-button">Cancel</button>
     				</p>
     			</div>
		
			<div id="theplatform-library-view">
		
			<div id="search-bar-outer">
				<div id="search-bar-inner" class="nav-sprite">
					<div>
						<button id="media-mpx-upload-button" type="button">Upload Media</button>
		
						<label id="search-label"> Search </label>
						<form class="search-form" id="theplatform-search" name="library-search" method="get" action="<?php echo menu_page_url( 'theplatform-media', false ); ?>">
							<?php
								//nonce check 								
								wp_nonce_field('plugin-name-action_tpnonce');
							?>
          					<input type="hidden" name="page" value="theplatform-media" />
							<span class="nav-sprite" id="search-by" style="width: auto;">
							  <span id="search-by-content" style="width: auto; overflow: visible;">
								Title
							  </span>
							  <span class="search-down-arrow nav-sprite"></span>
							  <select title="Search by" class="search-select" id="search-dropdown" name="theplatformsearchfield" data-nav-selected="0" style="top: 0px;">
									<?php
										echo '<option value="byTitle" selected="selected">Title</option>';  
										echo '<option value="byDescription">Description</option>';
										echo '<option value="byCategories">Categories</option>';  
										foreach ($metadata as $field) {
											if ($field['plfield$dataType'] == 'String') {
												echo '<option value="' . esc_attr($field['plfield$fieldName']) . '">' . esc_html($field['title']) . '</option>';  
											}
										}
									?>
								<option value="q">q</option>
							  </select>
							</span>
							
							<span class="nav-sprite" id="sort-by" style="width: auto;">
							  <span id="sort-by-content" style="width: auto; overflow: visible;">
								Sort by..
							  </span>
							  <span class="sort-down-arrow nav-sprite"></span>
							  <select title="Sort by" class="sort-select" id="sort-dropdown" name="theplatformsortfield" data-nav-selected="0" style="top: 0px;">
							  	<option value="title" selected="selected">Title: Ascending</option>
								<option value="title|desc" selected="selected">Title: Descending</option>
								<option value="added" selected="selected">Date Added: Ascending</option>
								<option value="added|desc" selected="selected">Date Added: Descending</option>
								<option value="author" selected="selected">Author: Ascending</option>
								<option value="author|desc" selected="selected">Author: Descending</option>
							  </select>
							</span>

							<div class="searchfield-outer nav-sprite">
							  <div class="searchfield-inner nav-sprite">
								<div class="searchfield-width" style="padding-left: 44px;">
								  <div id="search-input-container">
									<input type="text" autocomplete="off" name="s" value="" title="Search For" id="search-input" style="padding-right: 1px;">
								  </div>
								</div>
							  </div>
							</div>


							<div class="search-submit-button nav-sprite">
							  <input type="submit" title="Go" class="search-submit-input" value="Go">
							</div>
						
						
							<?php if (isset($_GET['s'])) { ?>
								<button id="media-mpx-show-all-button" type="button">Show All</button>
					  		<?php } ?>
					  </form>

						  
					  </div>
							
				</div>	
				
			</div>

		<?php	
			$output = '<div id="theplatform-media-library-view" class="wrap">';

			foreach ( $videos['entries'] as $video ) {
				$edit_url = add_query_arg( 'edit', substr($video['id'], strrpos($video['id'], '/')+1), menu_page_url( 'theplatform-media', false ) );
				$output .= '
				<div id="theplatform-media-' . esc_attr( substr($video['id'], strrpos($video['id'], '/')+1) ) . '" class="theplatform-media">
					<input type="hidden" name="guid" id="guid" value="' . esc_attr($video['guid']) . '"/>
					<input type="hidden" name="added" id="added" value="' . esc_attr($video['added']) . '"/>
					<input type="hidden" name="keywords" id="keywords" value="' . esc_attr($video['keywords']) . '"/>
					<input type="hidden" name="author" id="author" value="' . esc_attr($video['author']) . '"/>
					<input type="hidden" name="title" id="title" value="' . esc_attr($video['title']) . '"/>
					<input type="hidden" name="description" id="description" value="' . esc_attr($video['description']) . '"/>
					
					<div class="photo">
						<a href="' . esc_url( $edit_url ) . '" title="' . esc_attr( $video['id'] ) .'" class="use-shortcode"><img src="' . esc_url($video['plmedia$defaultThumbnailUrl']) . '"></a>
					</div>
					<div class="item-title"><a href="' . esc_url( $edit_url ) . '" title="' . esc_attr( $video['id']) .'" class="use-shortcode">' . esc_html( $video['title'] ) .'</a></div>
				
				</div>';
			}
			$output.='</div><div style="clear:both;"></div></div>';			
			echo $output;
		endif; 
	} ?>
</div> 