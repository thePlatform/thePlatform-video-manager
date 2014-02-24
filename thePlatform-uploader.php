<?php 
	wp_enqueue_script('theplatform_js');	
	wp_enqueue_style('bootstrap_tp_css');
	$tp_api = new ThePlatform_API;
	$metadata = $tp_api->get_metadata_fields();
	$preferences = get_option('theplatform_preferences_options');	
	$upload_options = get_option('theplatform_upload_options');
	$metadata_options = get_option('theplatform_metadata_options');		

?>
<!-- <div id="message_nag"><p id="message_nag_text"></p></div> -->
<h1> Upload Media to MPX </h1>
<div id="media-mpx-upload-form" class="tp">
<form role="form">
	<?php
		wp_nonce_field('theplatform_upload_nonce');

		$upload_options = get_option('theplatform_upload_options');
		$html = '';
		
		if ($preferences['user_id_customfield'] !== '') 
			echo '<input type="hidden" name="' . esc_attr($preferences['user_id_customfield']) . '" class="custom_field" value="' . wp_get_current_user()->ID . '" />';

		$col=0;
		$catHtml = '';
		foreach ($upload_options as $upload_field => $val) {
			$field_title = (strstr($upload_field, '$') !== false) ? substr(strstr($upload_field, '$'), 1) : $upload_field;
			
			if ($val == 'allow') {	
				if ($upload_field == 'categories') {
					$params = array(
						'token' => $tp_api->mpx_signin(),
						'fields' => 'title,fullTitle',
						'account' => $preferences['mpx_account_id']
					);
				
					$response = $tp_api->query('MediaCategory', 'get', $params);

					$tp_api->mpx_signout($params['token']);
					
					if (!is_wp_error($response)) {
						$categories = decode_json_from_server($response, TRUE);
						$catHtml .= '<div class="row">';
						$catHtml .= '<div class="col-xs-3">';
						$catHtml .= 	'<label class="control-label" for="theplatform_upload_' . esc_attr($upload_field) . '">' . esc_html(ucfirst($field_title)) . '</label>';
						$catHtml .= 	'<select class="category_field form-control" multiple id="theplatform_upload_' . esc_attr($upload_field) . '" name="' . esc_attr($upload_field) . '">';						
						foreach ($categories['entries'] as $category) {
							$catHtml .= '<option value="' . esc_attr($category['fullTitle']) . '">' . esc_html($category['fullTitle']) . '</option>';						
						}			
						$catHtml .= 	'</select>';
						$catHtml .= '</div>';
						$catHtml .= '</div>';
					} 
				}
				else {
					if ($col === 0) {
						echo '<div class="row">';
					}		
					$html = '';
					$html .= '<div class="col-xs-3">';
					$html .= 	'<label class="control-label" for="theplatform_upload_' . esc_attr($upload_field) . '">' . esc_html(ucfirst($field_title)) . '</label>';
					$html .= 	'<input name="' . esc_attr($upload_field) . '" id="theplatform_upload_' . esc_attr($upload_field) . '" class="form-control upload_field" type="text"/>'; //upload_field
					$html .= '</div>';							
					echo $html;
					if ($col === 2) {
					echo '</div>';
					$col = 0;					
					}
					else
						$col++;
				}				
			}

		}
		// if ($col !== 0) {
		// 	echo '</div>';	
		// 	$col = 0;
		// }							

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
	
			$field_title = $metadata_info['fieldName'];
			$field_prefix = $metadata_info['namespacePrefix'];

			if ($field_title === $preferences['user_id_customfield'])
				continue; 

			if ($val == 'allow') {										
				$field_value = $video[$field_prefix . '$' . $field_title];	
				$html = '';
				if ($col === 0) {
					echo '<div class="row">';
				}	
				$html .= '<div class="col-xs-3">';
				$html .= 	'<label class="control-label" for="theplatform_upload_' . esc_attr('theplatform_upload_' . esc_attr($field_prefix . '$' . $field_title)) . '">' . esc_html(ucfirst($field_title)) . '</label>';
				$html .= 	'<input name="' . esc_attr($field_title) . '" id="theplatform_upload_' . esc_attr($field_prefix . '$' . $field_title) . '" class="form-control custom_field" type="text"/>'; 
				$html .= '</div>';																											
				echo $html;	

				if ($col === 2) {
					echo '</div>';
					$col = 0;					
				}
				else
					$col++;									
			}		
		}
		if ($col !== 0) {
			echo '</div>';	
			$col = 0;
		}

		if (!empty($catHtml))
			echo $catHtml;
						
		
	?>
	<div class="row">
		<div class="col-xs-3">			
			<?php     								
					$profiles = $tp_api->get_publish_profiles();     								
					$html  = '<label class="control-label" for="publishing_profile">Publishing Profile</label>';
					$html .= 	'<select name="profile" id="publishing_profile" name="publishing_profile" class="form-control upload_profile">';  											
					$html .= 		'<option value="tp_wp_none">Do not publish</option>'; 
					foreach($profiles as $entry) {																		
						$html .= 	'<option value="' . esc_attr($entry['title']) . '"' . selected($entry['title'], $preferences['default_publish_id'], false) . '>' . esc_html($entry['title']) . '</option>'; 												
					}
				$html .=		 '</select>';
				echo $html;

			?>
		</div>
	</div>
	<div class="row">
		<label class="control-label" for="theplatform_upload_file">File</label><input type="file" accept="video/*" id="theplatform_upload_file" />
	</div>	
	<div class="row">
		<button id="theplatform_upload_button" class="form-control btn btn-primary" type="button" name="theplatform-upload-button">Upload Video</button>		
	</div>
	</form>	
	<!-- <p class="submit">
		
	</p> -->
</div>