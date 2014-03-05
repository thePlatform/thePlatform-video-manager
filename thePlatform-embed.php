<?php
	/*
	 * Load scripts and styles 
	 */	
	wp_print_scripts('mediaview_js');
	wp_print_scripts('bootstrap_js');	
	wp_print_scripts('infiniscroll_js');
	wp_print_scripts('jquery-ui-dialog');
	wp_print_scripts('pdk_external_controller');
	wp_print_styles('dashicons');
	wp_print_styles('bootstrap_tp_css');
	wp_print_styles('theplatform_css');
	wp_print_styles('wp-jquery-ui-dialog');
?>
<!DOCTYPE html>
<html lang="en" class="no-js">
<head>

    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="tp:EnableExternalController" content="true" />
<?php 


if ( ! defined( 'ABSPATH' ) ) exit;
$tp_viewer_cap = apply_filters('tp_viewer_cap', 'edit_posts');
if (!current_user_can($tp_viewer_cap)) {
	wp_die('<p>'.__('You do not have sufficient permissions to browse MPX Media').'</p>');
}		

if (!class_exists( 'ThePlatform_API' )) {
	require_once( dirname(__FILE__) . '/thePlatform-API.php' );
}

$tp_api = new ThePlatform_API;  
$metadata = $tp_api->get_metadata_fields();

$metadata_options = get_option('theplatform_metadata_options');
$upload_options = get_option('theplatform_upload_options');
$fields = 'id,defaultThumbnailUrl,content,thumbnails';

foreach ($upload_options as $upload_field => $val) {
	if ($val !== 'allow') 
		continue;

	$field_title = (strstr($upload_field, '$') !== false) ? substr(strstr($upload_field, '$'), 1) : $upload_field;
	if (!empty($fields))
		$fields .= ',';
	$fields .= $field_title;
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

	if (is_null($metadata_info))
		continue;								
	
	$field_title = $metadata_info['fieldName'];

	if (empty($fields))
		$fields .= ':';
	else
		$fields .= ',:';

	$fields .= $field_title;
}


$preferences = get_option('theplatform_preferences_options');	

if (strcmp($preferences['mpx_account_id'], "") == 0) {			
			wp_die('MPX Account ID is not set, please configure the plugin before attempting to manage media');
}

//Embed only stuff
$players = $tp_api->get_players();
$IS_EMBED = strpos($_SERVER['QUERY_STRING'], '&embed=true') !== false ? true : false;

function writePlayers($players, $preferences) {		
	$html = '<p class="navbar-text sort-bar-text">Player:</p><form class="navbar-form navbar-left sort-bar-nav" role="sort"><select id="selectpick-player" class="form-control">';
	foreach ($players as $player) {																		
		$html .= '<option value="' . esc_attr($player['pid']) . '"' . selected($player['pid'], $preferences['default_player_pid'], false) . '>' . esc_html($player['title']) . '</option>';      								
	}
    $html .= '</select></form>';	
	echo $html;
}
	


?>

<script type="text/javascript">
	localStorage.clear();
	localStorage.token = "<?php echo $tp_api->mpx_signin();?>";
	localStorage.account = "<?php echo $preferences['mpx_account_id'];?>";
	localStorage.accountPid = "<?php echo $preferences['mpx_account_pid'];?>";
	localStorage.fields = "<?php echo $fields;?>";
	localStorage.isEmbed = "<?php echo $IS_EMBED;?>";
	localStorage.editUrl = "<?php echo plugins_url('theplatform-uploader.php', __FILE__);?>"
	window.parent.document.body.style.overflowY="hidden";


</script>
</head>
<body>
<div class="tp">
	<nav class="navbar navbar-default navbar-fixed-top" role="navigation">
        <div class="row">
        <div class="navbar-header" style="margin-left: 15px">
            <a class="navbar-brand" href="#"><img height="25px" width="25px" src="<?php echo plugins_url('/images/embed_button.png', __FILE__);?>"> thePlatform</a>
        </div>            
            <form class="navbar-form navbar-left" role="search" onsubmit="return false;"><!--TODO: Add seach functionality on Enter -->
                <div class="form-group">
                    <input id="input-search" type="text" class="form-control" placeholder="Keywords">
                </div>
                <button id="btn-feed-preview" type="button" class="btn btn-default">Search</button>
            </form>
            <p class="navbar-text sort-bar-text">Sort:</p>
            <form class="navbar-form navbar-left sort-bar-nav" role="sort">
                <select id="selectpick-sort" class="form-control">
                    <option>Added</option>
                    <option>Title</option>
                    <option>Updated</option>
                </select>
            </form>

            <div id="my-content" class="navbar-left">
                <p class="navbar-text sort-bar-text">
                <!-- Render My Content checkbox -->
    				<?php if ($preferences['user_id_customfield'] !== '') { ?>
						<input type="checkbox" id="my-content-cb"
						<?php 
									checked($preferences['filter_by_user_id'] === 'TRUE'); 									
						?>
								/>
						<label for="my-content-cb" style="font-weight: normal">My Content</label>													
                	<?php } ?>
                <!-- End My Content Checkbox -->	
                </p>
            </div>
            <?php if ($IS_EMBED) writePlayers($players, $preferences); ?>    
            <img id="load-overlay" src="<?php echo plugins_url('/images/loading.gif', __FILE__)?>" class="loadimg navbar-right">
        </div>   

        
    </nav>

    <div class="fs-main">
        <div id="filter-container">
            <div id="filter-affix" class="scrollable affix-top">
                    <div id="list-categories" class="list-group">
                        <a class="list-group-item active">
                            Categories
                        </a>
                        <a href="#" class="list-group-item cat-list-selector">All Videos</a>
                    </div>                    
            </div>
        </div>

        <div id="content-container">
            <div id="media-list"></div>
        </div>
        <div id="info-container">
            <div id="info-affix" class="scrollable affix-top">
                <div id="info-player-container">
                        <div id="modal-player" class="marketplacePlayer">
                        	<img id="modal-player-placeholder" data-src="holder.js/320x180/text:No Preview Available" src="" style="position: absolute"><!-- holder.js/128x72/text:No Thumbnail" -->
                            <iframe id="player" width="320px" height="180px" frameBorder="0" seamless="seamless" src="http://player.theplatform.com/p/<?php echo $preferences['mpx_account_pid'] . '/' .  $preferences['default_player_pid'];?>/embed?autoPlay=false"
                                    webkitallowfullscreen mozallowfullscreen msallowfullscreen allowfullscreen></iframe>
                        </div>
                    <br>
                    <div id="panel-contentpane" class="panel panel-default">
                        <div class="panel-heading">
                            <h3 class="panel-title">Metadata</h3>
                        </div>
                        <div class="panel-body">
                        <?php                         	
								foreach ($upload_options as $upload_field => $val) {
									if ($val !== 'allow') 
										continue;

										$field_title = (strstr($upload_field, '$') !== false) ? substr(strstr($upload_field, '$'), 1) : $upload_field;
										$display_title = mb_convert_case($field_title, MB_CASE_TITLE);
										
										//Custom names
										if ($field_title === 'guid') $display_title = 'Reference ID';
										if ($field_title === 'link') $display_title = 'Related Link';
										$html = '<div class="row">';																			
										$html .= '<strong>' . esc_html($display_title) . ': </strong>';
										$html .= '<span id="media-' . esc_attr(strtolower($field_title)) . '"' . '" data-name="' . esc_attr(strtolower($field_title)) . '"></span></div>';
										echo $html;
								}	

								
								$metadata_options = get_option('theplatform_metadata_options');							
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
									
									$field_title = $metadata_info['fieldName'];
									$field_prefix = $metadata_info['namespacePrefix'];
									$field_namespace = $metadata_info['namespace'];
									$field_type = $metadata_info['dataType'];
									$field_structure = $metadata_info['dataStructure'];
									
									if ($field_title === $preferences['user_id_customfield'])
										continue;

									$html = '<div class="row">';																			
									$html .= '<strong>' . esc_html(mb_convert_case($field_title, MB_CASE_TITLE)) . ': </strong>';
									$html .= '<span id="media-' . esc_attr(strtolower($field_title)) . '" data-type="' . esc_attr($field_type) . '" data-structure="' . esc_attr($field_structure) . '" data-name="' . esc_attr(strtolower($field_title)) . '" data-prefix="' . esc_attr(strtolower($field_prefix)) . '" data-namespace="' . esc_attr(strtolower($field_namespace)) . '"></span></div>';
									echo $html;
								}				
                        ?>                      
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php if (!$IS_EMBED) { ?>
<div id="tp-edit-dialog" class="tp" style="display: none;">
<h1> Edit Media</h1>
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
					$html .= 	'<input name="' . esc_attr($upload_field) . '" id="theplatform_upload_' . esc_attr($upload_field) . '" class="form-control upload_field" type="text" value="' . esc_attr($media[$upload_field]) . '"/>'; //upload_field
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
				$html .= 	'<label class="control-label" for="' . esc_attr('theplatform_upload_' . esc_attr($field_prefix . '$' . $field_title)) . '">' . esc_html(ucfirst($field_title)) . '</label>';
				$html .= 	'<input name="' . esc_attr($field_title) . '" id="' . esc_attr('theplatform_upload_' . $field_prefix . '$' . $field_title) . '" class="form-control custom_field" type="text" value="' . esc_attr($media[$field_prefix . '$' . $field_title]) . '"/>'; 
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
				<button id="theplatform_edit_button" class="form-control btn btn-primary" type="button" name="theplatform-edit-button">Submit</button>		
			</div>
		</div>
	</form>	
</div>
<?php } ?>
</body>

</html>