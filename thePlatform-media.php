<?php 

if (!class_exists( 'ThePlatform_API' )) {
	require_once( dirname(__FILE__) . '/thePlatform-API.php' );
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
wp_enqueue_script('set-post-thumbnail');
wp_enqueue_script('thickbox');

wp_enqueue_style('theplatform_css');
wp_enqueue_style('global');
wp_enqueue_style('media');
wp_enqueue_style('wp-admin');
wp_enqueue_style('colors');
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
		// Update media item in detail view		

		check_admin_referer('theplatform-ajax-nonce'); 		

		//Check if a user can edit MPX Media
		$tp_editor_cap = apply_filters('tp_editor_cap', 'upload_files');
		if (!current_user_can($tp_editor_cap)) {
				wp_die('<p>'.__('You do not have sufficient permissions to modify MPX Media').'</p>');
		}

		unset($_GET['edit']);
		
		$fields = array('id' => $_POST['edit_id']);
		$namespaces = array('$xmlns' => array(
				"dcterms" => "http://purl.org/dc/terms/",
				"media" => "http://search.yahoo.com/mrss/",
				"pl" => "http://xml.theplatform.com/data/object",
				"pla" => "http://xml.theplatform.com/data/object/admin",
				"plmedia" => "http://xml.theplatform.com/media/data/Media",
				"plfile" => "http://xml.theplatform.com/media/data/MediaFile",
				"plrelease" => "http://xml.theplatform.com/media/data/Release"				
			)
		);

		$payload = array();			
					
		$upload_options = get_option('theplatform_upload_options');
		$metadata_options = get_option('theplatform_metadata_options');		
								
		$html = '';

		if (!empty($preferences['user_id_customfield'])) {
			$user_id_customfield = $tp_api->get_customfield_info($preferences['user_id_customfield']);
			$metadata_info = $user_id_customfield['entries'][0];			
			$fieldName = $metadata_info['plfield$namespacePrefix'] . '$' . $metadata_info['plfield$fieldName'];			
			$fields[$fieldName] = $_POST[$preferences['user_id_customfield']];				
			$namespaces['$xmlns'] = array_merge($namespaces['$xmlns'], array($metadata_info['plfield$namespacePrefix'] => $metadata_info['plfield$namespace']));
		}		

		foreach ($metadata_options as $custom_field => $val) {
			if ($val !== 'allow')
				continue;

			$metadata_info = NULL;
			foreach ($metadata as $entry) {
				if (array_search($custom_field, $entry)) {
					$metadata_info = $entry;
					break;
				}
			}	

			$field_title = $metadata_info['plfield$fieldName'];
			if ($field_title === $preferences['user_id_customfield'])
				continue;

			if (is_null($metadata_info))
				continue;								
			
			$fieldName = $metadata_info['plfield$namespacePrefix'] . '$' . $metadata_info['plfield$fieldName'];
			$namespaces['$xmlns'][$metadata_info['plfield$namespacePrefix']] = $metadata_info['plfield$namespace'];
			$fields[$fieldName] = $_POST[$fieldName];			
		}	

		foreach ($upload_options as $upload_field => $val) {
			if ($val == 'allow') {
				if ($upload_field == 'media$categories') {
					$i=0;
					$categories = array();
					while (isset($_POST['media$categories-' . $i])) {
						if ($_POST['media$categories-' . $i] !== '(None)')
							array_push($categories, array('media$name' => $_POST['media$categories-' . $i]));
						$i++;
					}
					$fields['media$categories'] = $categories;
				}
				else 
					$fields[$upload_field] = sanitize_text_field($_POST[$upload_field]);	
							
			}
		}
							
		$payload = array_merge($payload, $namespaces);
		$payload = array_merge($payload, $fields);				
		$payloadJSON = json_encode($payload, JSON_UNESCAPED_SLASHES);

 		$tp_api->update_media($payloadJSON);
		
		$response = $tp_api->get_videos();
		if ( is_wp_error( $response ) )
			echo '<div id="message" class="error below-h2"><p>' . esc_html($response->get_error_message()) . '</p></div>';
	} 
	
	if ( !empty( $_GET['edit'] ) ) {
		// Detail view + Media editor
		$video = $tp_api->get_video_by_id(sanitize_text_field($_GET['edit']));
		
		if ( is_wp_error( $response ) )
			echo '<div id="message" class="error below-h2"><p>' . esc_html($response->get_error_message()) . '</p></div>';
	} else {
		// Search Results
		$page = isset( $_GET['tppage'] ) ? sanitize_text_field( $_GET['tppage'] ) : '1';

		if (isset($_GET['s'])) {
			
			$key_word = isset( $_GET['s'] ) ? sanitize_text_field( $_GET['s'] ) : '';						
			$field = isset( $_GET['theplatformsearchfield'] ) ? sanitize_text_field( $_GET['theplatformsearchfield'] ) : '';			
			$sort = isset( $_GET['theplatformsortfield'] ) ? sanitize_text_field( $_GET['theplatformsortfield'] ) : '';

			$query = array();
			if ($key_word !== '')
				array_push($query, $field. '=' . $key_word);
						
			if (isset($_GET['filter-by-userid']) && $preferences['user_id_customfield'] !== '')
			 	array_push($query, 'byCustomValue=' . urlencode('{' . $preferences['user_id_customfield'] . '}{' . wp_get_current_user()->ID . '}'));
			
			if ($field !== 'q')
				$videos = $tp_api->get_videos(implode('&', $query), $sort, $page);
			else
				$videos = $tp_api->get_videos(implode('&', $query), '', $page);
			
		} else {
			// Library View				
			if ($preferences['filter_by_user_id'] === 'TRUE' && $preferences['user_id_customfield'] !== '')
			 		$videos = $tp_api->get_videos('byCustomValue=' . urlencode('{' . $preferences['user_id_customfield'] . '}{' . wp_get_current_user()->ID . '}'), '', $page);
			else
				$videos = $tp_api->get_videos('','',$page);			
		}
		$count = $videos['totalResults'];
		$pages = ceil(intval($count)/intval($preferences['videos_per_page']));		
		$videos = $videos['entries'];	

	} ?>


	<?php 
	if ( !is_wp_error( $response ) ) {		

		if ( !empty( $_GET['edit'] ) ) : 
			
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
				<?php 
					wp_nonce_field( 'theplatform-ajax-nonce' ); 
					if ($preferences['user_id_customfield'] !== '') 
						echo '<input type="hidden" name="' . esc_attr($preferences['user_id_customfield']) . '" class="custom_field" value="' . wp_get_current_user()->ID . '" />';
				?>
				<input type="hidden" name="edit_id" value="<?php echo esc_attr( $video['id'] ); ?>" />

				<span class="theplatform-media-edit">
					<div class="theplatform-media-thumbnail">
						<div class="photo">
							<?php
							if (is_null($embed_id)) 
								echo '<img src="' . esc_url($video['plmedia$defaultThumbnailUrl']) . '">';
							else {
								$url = 'http://player.theplatform.com/p/' . $preferences['mpx_account_pid'] . '/' .  $preferences['default_player_pid'] . '/embed/select/' .  $embed_id . '?form=html';
								echo '<iframe src="' . esc_url($url) . '" width="491" height="272" frameBorder="0" seamless="seamless" allowFullScreen></iframe>';
							}
							?>
						</div>
					</div>
					<div style="float: left; margin: 4px 4px 4px 40px; padding-left: 40px; border-left: 1px solid #DFDFDF;">
						<table class="form-table">
						<tbody>
							<?php
								$metadata_options = get_option('theplatform_metadata_options');

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
												$i = 0;
												$html = '<tr valign="top"><th scope="row">Category</th><td>';
												
												foreach ($video[$upload_field] as $mediaCategory) {																										
													$html .= '<select class="category_field" id="theplatform_upload_' . esc_attr($upload_field) . '" name="' . esc_attr($upload_field) . '-' . $i++ . '">';
													$html .= '<option value="(None)">No category</option>';
													foreach ($categories['entries'] as $category) {
														$selected = $category['plcategory$fullTitle'] == $mediaCategory['media$name'] ? ' selected="selected"' : '';
														$html .= '<option value="' . esc_attr($category['plcategory$fullTitle']) . '" ' . esc_attr($selected) . '>' . esc_html($category['plcategory$fullTitle']) . '</option>';
													}
													$html .= '</select>';													
												}
												$html .= '<input type="button" class="button" id="upload_add_category" value="+"/></td></tr>';
																								
												echo $html;
											}
										} else {											
											$field_value = $video[$upload_field];																						
											$html = '<tr valign="top"><th scope="row">' . esc_html(ucfirst($field_title)) . '</th><td><input name="' . esc_attr($upload_field) . '" id="theplatform_upload_' . esc_attr($upload_field) . '" class="edit_field" type="text" value="' . esc_attr($field_value) . '"/></td></tr>';
											echo $html;
										}
									}
								}	

								$metadata_options = get_option('theplatform_metadata_options');
								
								$html = '';
						
								foreach ($metadata_options as $custom_field => $val) {
									if ($val !== 'allow')
										continue;

									$metadata_info = NULL;
									foreach ($metadata as $entry) {
										if (array_search($custom_field, $entry)) {
											$metadata_info = $entry;
											break;
										}
									}	

									if (is_null($metadata_info))
										continue;								

									$field_title = $metadata_info['plfield$fieldName'];
									$field_prefix = $metadata_info['plfield$namespacePrefix'];
									$field_namespace = $metadata_info['plfield$namespace'];
									
									if ($field_title === $preferences['user_id_customfield'])
										continue;

									$field_value="";
									if (array_key_exists($field_prefix . '$' . $field_title, $video))
										$field_value = $video[$field_prefix . '$' . $field_title];
									$html = '<tr valign="top"><th scope="row">' . esc_html(ucfirst($field_title)) . '</th><td><input name="' . esc_attr($field_prefix . '$' . $field_title) . '" id="theplatform_upload_' . esc_attr($field_prefix . '$' . $field_title) . '" class="edit_custom_field" type="text" value="' . esc_attr($field_value) . '"/></td></tr>';
									echo $html;										

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
     						
     						if ($preferences['user_id_customfield'] !== '') 
     							echo '<input type="hidden" name="' . esc_attr($preferences['user_id_customfield']) . '" class="custom_field" value="' . wp_get_current_user()->ID . '" />';

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
     									
											$html = '<tr valign="top"><th scope="row">Category</th><td><select class="category_field" id="theplatform_upload_' . esc_attr($upload_field) . '" name="' . esc_attr($upload_field) . '">';
											$html .= '<option value="(None)">No category</option>';
											foreach ($categories['entries'] as $category) {
												$html .= '<option value="' . esc_attr($category['plcategory$fullTitle']) . '">' . esc_html($category['plcategory$fullTitle']) . '</option>';
											}
			
											$html .= '</select><input type="button" class="button" id="upload_add_category" value="+"/></td></tr>';

											echo $html;
										}
     								} else {
     									$html = '<tr valign="top"><th scope="row">' . esc_html(ucfirst($field_title)) . '</th><td><input name="' . esc_attr($upload_field) . '" id="theplatform_upload_' . esc_attr($upload_field) . '" class="upload_field" type="text" /></td></tr>';
     									echo $html;
     								}
     							}
     						}

     						$metadata_options = get_option('theplatform_metadata_options');
								
								$html = '';
						
								foreach ($metadata_options as $custom_field => $val) {
									$metadata_info = NULL;
									foreach ($metadata as $entry) {
										if (array_search($custom_field, $entry)) {
											$metadata_info = $entry;
											break;
										}
									}	

									if (is_null($metadata_info))
										continue;								
							
									$field_title = $metadata_info['plfield$fieldName'];
									$field_prefix = $metadata_info['plfield$namespacePrefix'];
									if ($val == 'allow') {										
											$field_value = $video[$field_prefix . '$' . $field_title];																						
											$html = '<tr valign="top"><th scope="row">' . esc_html(ucfirst($field_title)) . '</th><td><input name="' . esc_attr($field_title) . '" id="theplatform_upload_' . esc_attr($field_prefix . '$' . $field_title) . '" class="custom_field" type="text" value="' . esc_attr($field_value) . '"/></td></tr>';
											echo $html;										
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
						<form class="search-form" id="theplatform-search" name="library-search" method="get" action="#">
							<?php wp_nonce_field('theplatform-ajax-nonce'); ?>
          					<input type="hidden" name="page" value="theplatform-media" />
							<span class="nav-sprite" id="search-by" style="width: auto;">
							  <span id="search-by-content" style="width: auto; overflow: visible;">
								Title Prefix
							  </span>
							  <span class="search-down-arrow nav-sprite"></span>
							  <select title="Search by" class="search-select" id="search-dropdown" name="theplatformsearchfield" data-nav-selected="0" style="top: 0px;">
								<option value="byTitlePrefix" <?php echo $_GET['theplatformsearchfield'] == 'byTitlePrefix' ? 'selected="selected"' : '' ?>>Title Prefix</option>
								<option value="byTitle" <?php echo $_GET['theplatformsearchfield'] == 'byTitle' ? 'selected="selected"' : '' ?>>Full Title</option>								
								<option value="byCategories" <?php echo $_GET['theplatformsearchfield'] == 'byCategories' ? 'selected="selected"' : '' ?>>Categories</option>
								<option value="q" <?php echo $_GET['theplatformsearchfield'] == 'q' ? 'selected="selected"' : '' ?>>q</option>
							  </select>
							</span>
							
							<span class="nav-sprite" id="sort-by" style="width: auto;">
							  <span id="sort-by-content" style="width: auto; overflow: visible;">
								Sort by..
							  </span>
							  <span class="sort-down-arrow nav-sprite"></span>
							  <select title="Sort by" class="sort-select" id="sort-dropdown" name="theplatformsortfield" data-nav-selected="0" style="top: 0px;">
							  	<option value="title" <?php echo $_GET['theplatformsortfield'] == 'title' ? 'selected="selected"' : '' ?>>Title: Ascending</option>
								<option value="title|desc" <?php echo $_GET['theplatformsortfield'] == 'title|desc' ? 'selected="selected"' : '' ?>>Title: Descending</option>
								<option value="added" <?php echo $_GET['theplatformsortfield'] == 'added' ? 'selected="selected"' : '' ?>>Date Added: Ascending</option>
								<option value="added|desc" <?php echo $_GET['theplatformsortfield'] == 'added|desc' ? 'selected="selected"' : '' ?>>Date Added: Descending</option>
								<option value="author" <?php echo $_GET['theplatformsortfield'] == 'author' ? 'selected="selected"' : '' ?>>Author: Ascending</option>
								<option value="author|desc" <?php echo $_GET['theplatformsortfield'] == 'author|desc' ? 'selected="selected"' : '' ?>>Author: Descending</option>
							  </select>
							</span>

							<?php 
								if ($preferences['user_id_customfield'] !== '') { ?>
									<span id="filter-by">
										<input name="filter-by-userid" id="filter-cb" type="checkbox" <?php 
										if (!isset($_GET['s']) && $preferences['filter_by_user_id'] === 'TRUE') { 
											echo 'checked="checked"'; 
										} 
										if (isset($_GET['filter-by-userid'])) {
											echo 'checked="checked"';
										} ?>
										/>
                                		<label id="filter-label" for="filter-cb">My Media</label>
								</span>
							<?php } ?>							

							<div class="searchfield-outer nav-sprite">
							  <div class="searchfield-inner nav-sprite">
								<div class="searchfield-width" style="padding-left: 44px;">
								  <div id="search-input-container">
									<input type="text" autocomplete="off" name="s" value="<?php echo $_GET['s'] ?>" title="Search For" id="search-input" style="padding-right: 1px;">
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

			foreach ( $videos as $video ) {

				$thumbnail_url = $video['plmedia$defaultThumbnailUrl'];
				if ($thumbnail_url === '')					
					$thumbnail_url = plugins_url('/images/notavailable.gif', __FILE__);
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
						<a href="' . esc_url( $edit_url ) . '" title="' . esc_attr( $video['id'] ) .'" class="use-shortcode"><img src="' . esc_url($thumbnail_url) . '"></a>
					</div>
					<div class="item-title"><a href="' . esc_url( $edit_url ) . '" title="' . esc_attr( $video['id']) .'" class="use-shortcode">' . esc_html( $video['title'] ) .'</a></div>
				
				</div>';
			}
			$output.='</div><div style="clear:both;"></div>';	

			//Pagination
			$output .= '<ul id="pagination">';

			if (!isset($_GET['tppage']) || $_GET['tppage'] === '1')
				$output .= '<li class="previous-off">«Previous</li>';
			else
				$output .= '<li><a href="' . esc_url(add_query_arg(array('tppage'=> intval($_GET['tppage'])-1))) . '">«Previous</a></li><li>';

			for ($i=1; $i <= $pages; $i++) { 
				if ($i == $page)
					$output .= '<li class="active">' . $page . '</li>';
				else
					$output .= '<li><a href="' . esc_url(add_query_arg(array('tppage'=> $i))) . '">' . $i . '</a></li>';
			}

			if ($_GET['tppage'] != $pages)
				$output .= '<li><a href="' . esc_url(add_query_arg(array('tppage'=> isset($_GET['tppage']) ? intval($_GET['tppage'])+1 : 2))) . '">Next »</a></li>';
			else
				$output .= '<li class="next-off">Next »</li><li>';
				
			$output .= '</ul></div>';
			
			echo $output;
		endif; 
	} ?>
</div>