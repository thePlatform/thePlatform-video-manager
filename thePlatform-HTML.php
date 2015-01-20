<?php
/* thePlatform Video Manager Wordpress Plugin
  Copyright (C) 2013-2014 thePlatform, LLC

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
 * Methods to generate some of our HTML markup
 */
class ThePlatform_HTML {

	private $tp_api;
	private $preferences;
	private $metadata;
	private $basic_metadata_options;
	private $custom_metadata_options;
	private $profiles;
	private $servers;
	private $players;

	function __construct() {
		$this->tp_api                  = new ThePlatform_API();
		$this->preferences             = get_option( TP_PREFERENCES_OPTIONS_KEY );
		$this->metadata                = $this->tp_api->get_custom_metadata_fields();
		$this->preferences             = get_option( TP_PREFERENCES_OPTIONS_KEY );
		$this->basic_metadata_options  = get_option( TP_BASIC_METADATA_OPTIONS_KEY );
		$this->custom_metadata_options = get_option( TP_CUSTOM_METADATA_OPTIONS_KEY );
		$this->profiles                = $this->tp_api->get_publish_profiles();
		$this->servers                 = $this->tp_api->get_servers();
		$this->players                 = $this->tp_api->get_players();
	}

	function player_dropdown() {
		$html = '<p class="navbar-text sort-bar-text">Player:</p><form class="navbar-form navbar-left sort-bar-nav" role="sort"><select id="selectpick-player" class="form-control">';
		foreach ( $this->players as $player ) {
			$html .= '<option value="' . esc_attr( $player['pid'] ) . '"' . selected( $player['pid'], $this->preferences['default_player_pid'], false ) . '>' . esc_html( $player['title'] ) . '</option>';
		}
		$html .= '</select></form>';
		echo $html;
	}

	function preview_player() {
		?>
		<div id="modal-player" class="marketplacePlayer">
			<img id="modal-player-placeholder" alt="Preview" data-src="holder.js/320x180/text:No Preview Available"
			     src=""><!-- holder.js/128x72/text:No Thumbnail" -->
			<div class="tpPlayer" id="player"
			     tp:allowFullScreen="true"
			     tp:skinUrl="//pdk.theplatform.com/current/pdk/skins/glass/glass.json"
			     tp:layout="&lt;controls&gt;
         &lt;region id=&quot;tpAdCountdownRegion&quot;&gt;
         &lt;row id=&quot;tpAdCountdownContainer&quot;&gt;
         &lt;control id=&quot;tpAdCountdown&quot;/&gt;
         &lt;/row&gt;
         &lt;/region&gt;
         &lt;region id=&quot;tpBottomFloatRegion&quot; alpha=&quot;85&quot;&gt;
         &lt;row height=&quot;10&quot;&gt;
         &lt;group percentWidth=&quot;100&quot; direction=&quot;horizontal&quot; verticalAlign=&quot;middle&quot;&gt;
         &lt;control id=&quot;tpScrubber&quot;/&gt;       
         &lt;/group&gt;
         &lt;/row&gt;
         &lt;row&gt;
         &lt;control id=&quot;tpPlay&quot;/&gt;               
         &lt;spacer/&gt;
         &lt;control id=&quot;tpCurrentTime&quot;/&gt;
         &lt;control id=&quot;tpTimeDivider&quot;/&gt;
         &lt;control id=&quot;tpTotalTime&quot;/&gt;
         &lt;spacer percentWidth=&quot;100&quot;/&gt;
         &lt;control id=&quot;tpVolumeSlider&quot;/&gt;
         &lt;control id=&quot;tpFullScreen&quot;/&gt;     
         &lt;/row&gt;
         &lt;/region&gt;
         &lt;/controls&gt;"
			     tp:showFullTime="true"
			     tp:controlBackgroundColor="0xbbbbbb"
			     tp:backgroundColor="0xbbbbbb"
			     tp:controlFrameColor="0x666666"
			     tp:frameColor="0x666666"
			     tp:textBackgroundColor="0xcccccc"
			     tp:controlHighlightColor="0x666666"
			     tp:controlHoverColor="0x444444"
			     tp:loadProgressColor="0x111111"
			     tp:controlSelectedColor="0x48821d"
			     tp:playProgressColor="0x48821d"
			     tp:scrubberFrameColor="0x48821d"
			     tp:controlColor="0x111111"
			     tp:textColor="0x111111"
			     tp:scrubberColor="0x111111"
			     tp:scrubTrackColor="0x111111"
			     tp:pageBackgroundColor="0xeeeeee"
			     tp:plugin1="type=content|url=//pdk.theplatform.com/current/pdk/swf/akamaiHD.swf|fallback=switch%3Dhttp|bufferProfile=livestable|priority=1|videoLayer=akamaihd"
			     tp:plugin2="type=content|url=//pdk.theplatform.com/current/pdk/js/plugins/akamaiHD.js|fallback=switch%3Dhttp|bufferProfile=livestable|priority=1|videoLayer=akamaihd">
				<noscript class="tpError">To view this site, you need to have JavaScript enabled in your browser, and
					either the Flash Plugin or an HTML5-Video enabled browser. Download <a
						href="http://get.adobe.com/flashplayer/" target="_black">the latest Flash player</a> and try
					again.
				</noscript>
			</div>
		</div> <?php
	}

	function content_pane( $IS_EMBED ) {
		?>
		<div id="panel-contentpane" class="panel panel-default">
			<div class="panel-heading">
				<h3 class="panel-title">Metadata</h3>
			</div>
			<div class="panel-body">
				<?php
				foreach ( $this->basic_metadata_options as $basic_field => $val ) {
					if ( $val == 'hide' ) {
						continue;
					}

					$field_title   = ( strstr( $basic_field, '$' ) !== false ) ? substr( strstr( $basic_field, '$' ), 1 ) : $basic_field;
					$display_title = mb_convert_case( $field_title, MB_CASE_TITLE );

					//Custom names
					if ( $field_title === 'guid' ) {
						$display_title = 'Reference ID';
					}
					if ( $field_title === 'link' ) {
						$display_title = 'Related Link';
					}
					$html = '<div class="row">';
					$html .= '<strong>' . esc_html( $display_title ) . ': </strong>';
					$html .= '<span class="field" id="media-' . esc_attr( strtolower( $field_title ) ) . '" data-name="' . esc_attr( strtolower( $field_title ) ) . '"></span></div>';
					echo $html;
				}

				foreach ( $this->custom_metadata_options as $custom_field => $val ) {
					if ( $val == 'hide' ) {
						continue;
					}

					$metadata_info = null;
					foreach ( $this->metadata as $entry ) {
						if ( array_search( $custom_field, $entry ) ) {
							$metadata_info = $entry;
							break;
						}
					}

					if ( is_null( $metadata_info ) ) {
						continue;
					}

					$field_title     = $metadata_info['fieldName'];
					$field_prefix    = $metadata_info['namespacePrefix'];
					$field_namespace = $metadata_info['namespace'];
					$field_type      = $metadata_info['dataType'];
					$field_structure = $metadata_info['dataStructure'];

					$html = '<div class="row">';
					$html .= '<strong>' . esc_html( mb_convert_case( $field_title, MB_CASE_TITLE ) ) . ': </strong>';
					$html .= '<span class="field" id="media-' . esc_attr( $field_title ) . '" data-type="' . esc_attr( $field_type ) . '" data-structure="' . esc_attr( $field_structure ) . '" data-name="' . esc_attr( $field_title ) . '" data-prefix="' . esc_attr( $field_prefix ) . '" data-namespace="' . esc_attr( $field_namespace ) . '"></span></div>';
					echo $html;
				}
				?>
			</div>
			<div id="btn-container">
				<?php if ( $IS_EMBED ) { ?>
					<div class="btn-group">
						<input type="button" id="btn-embed" class="btn btn-default btn-xs" value="Embed">
						<input type="button" id="btn-embed-close" class="btn btn-default btn-xs" value="Embed & Close">
						<input type="button" id="btn-set-image" class="btn btn-default btn-xs"
						       value="Set Featured Image">
					</div>
				<?php
				} else {
					?>
					<input type="button" id="btn-edit" class="btn btn-default btn-xs" value="Edit Media">
				<?php } ?>
			</div>
		</div> <?php
	}

	function profiles_and_servers( $upload_or_add ) {
		?>
		<div class="row">
			<div class="col-xs-3">
				<?php
				$html = '<div class="form-group"><label class="control-label" for="publishing_profile">Publishing Profile</label>';
				$html .= '<select id="publishing_profile" name="publishing_profile" class="form-control upload_profile">';
				$html .= '<option value="tp_wp_none"' . selected( $this->preferences['default_publish_id'], 'wp_tp_none', false ) . '>Do not publish</option>';
				foreach ( $this->profiles as $entry ) {
					$html .= '<option value="' . esc_attr( $entry['id'] ) . '"' . selected( $entry['title'], $this->preferences['default_publish_id'], false ) . '>' . esc_html( $entry['title'] ) . '</option>';
				}
				$html .= '</select></div>';
				echo $html;
				?>
			</div>
			<div class="col-xs-3">
				<?php
				$html = '<div class="form-group"><label class="control-label" for="theplatform_server">Server</label>';
				$html .= '<select id="theplatform_server" name="theplatform_server" class="form-control server_id">';
				$html .= '<option value="DEFAULT_SERVER"' . selected( $this->preferences['mpx_server_id'], "DEFAULT_SERVER", false ) . '>Default Server</option>';
				foreach ( $this->servers as $entry ) {
					$html .= '<option value="' . esc_attr( $entry['id'] ) . '"' . selected( $entry['id'], $this->preferences['mpx_server_id'], false ) . '>' . esc_html( $entry['title'] ) . '</option>';
				}
				$html .= '</select></div>';
				echo $html;
				?>
			</div>
		</div>
		<div class="row">
			<div class="col-md-8">
				<div class="form-group" id="file-form-group">
					<label class="control-label" for="theplatform_upload_label">File</label>

					<div class="input-group">
                    <span class="input-group-btn">
                        <span class="btn btn-default btn-file">
                            Browse&hellip; <input type="file" id="theplatform_upload_file" multiple>
                        </span>
                    </span>
						<input type="text" class="form-control" style="cursor: text; text-indent: 10px;"
						       id="theplatform_upload_label" readonly value="No file chosen">
					</div>
				</div>
			</div>
		</div>
		<div class="row">
			<div class="col-xs-3">
				<div class="form-group">
					<?php
					if ( $upload_or_add == "add" ) {
						echo '<button id="theplatform_add_file_button" class="form-control btn btn-primary" type="button" name="theplatform-add-file-button">Upload Files</button>';
					} else {
						echo '<button id="theplatform_upload_button" class="form-control btn btn-primary" type="button" name="theplatform-upload-button">Upload Media</button>';
					}
					?>
				</div>
			</div>
		</div> <?php
	}

	function metadata_fields() {
		$categoryHtml = '';
		$write_fields = array();
		// We need a count of the write enabled fields in order to display rows appropriately.
		foreach ( $this->basic_metadata_options as $basic_field => $val ) {
			if ( $val == 'write' ) {
				$write_fields[] = $basic_field;
			}
		}

		$len = count( $write_fields ) - 1;
		$i   = 0;
		foreach ( $write_fields as $basic_field ) {
			$field_title = ( strstr( $basic_field, '$' ) !== false ) ? substr( strstr( $basic_field, '$' ), 1 ) : $basic_field;
			if ( $basic_field == 'categories' ) {
				$categories = $this->tp_api->get_categories( true );
				// Always Put categories on it's own row
				$categoryHtml .= '<div class="row">';
				$categoryHtml .= '<div class="col-xs-5">';
				$categoryHtml .= '<div class="form-group">';
				$categoryHtml .= '<label class="control-label" for="theplatform_upload_' . esc_attr( $basic_field ) . '">' . esc_html( ucfirst( $field_title ) ) . '</label>';
				$categoryHtml .= '<select class="category_field form-control" multiple id="theplatform_upload_' . esc_attr( $basic_field ) . '" name="' . esc_attr( $basic_field ) . '">';
				foreach ( $categories as $category ) {
					$categoryHtml .= '<option value="' . esc_attr( $category['fullTitle'] ) . '">' . esc_html( $category['fullTitle'] ) . '</option>';
				}
				$categoryHtml .= '</select>';
				$categoryHtml .= '</div>';
				$categoryHtml .= '</div>';
				$categoryHtml .= '</div>';
			} else {
				$html = '';
				if ( $i % 2 == 0 ) {
					$html .= '<div class="row">';
				}
				$html .= '<div class="col-xs-5">';
				$html .= '<div class="form-group">';
				$html .= '<label class="control-label" for="theplatform_upload_' . esc_attr( $basic_field ) . '">' . esc_html( ucfirst( $field_title ) ) . '</label>';
				$html .= '<input name="' . esc_attr( $basic_field ) . '" id="theplatform_upload_' . esc_attr( $basic_field ) . '" class="form-control upload_field" type="text" placeholder="' . esc_attr( ucfirst( $field_title ) ) . '"';
				if ( $basic_field == 'title' ) {
					$html .= ' autofocus ';	// Autofocus on title
				} 				
				$html .= '/>';
				$html .= '</div>';
				$html .= '</div>';
				$i ++;
				if ( $i % 2 == 0 ) {
					$html .= '</div>';
				}
				echo $html;
			}
		}
		if ( $i % 2 != 0 ) {
			echo '</div>';
		}

		$html         = '';
		$write_fields = array();

		foreach ( $this->custom_metadata_options as $custom_field => $val ) {
			if ( $val == 'write' ) {
				$write_fields[] = $custom_field;
			}
		}

		$i   = 0;
		$len = count( $write_fields ) - 1;
		foreach ( $write_fields as $custom_field ) {
			$metadata_info = null;
			foreach ( $this->metadata as $entry ) {
				if ( array_search( $custom_field, $entry ) ) {
					$metadata_info = $entry;
					break;
				}
			}

			if ( is_null( $metadata_info ) ) {
				continue;
			}

			$field_title     = $metadata_info['fieldName'];
			$field_prefix    = $metadata_info['namespacePrefix'];
			$field_namespace = $metadata_info['namespace'];
			$field_type      = $metadata_info['dataType'];
			$field_structure = $metadata_info['dataStructure'];
			$allowed_values  = $metadata_info['allowedValues'];

			if ( $field_title === $this->preferences['user_id_customfield'] ) {
				continue;
			}

			$field_name = $field_prefix . '$' . $field_title;

			$html = '';
			if ( $i % 2 == 0 ) {
				$html .= '<div class="row">';
			}
			$html .= '<div class="col-xs-5">';
			$html .= '<label class="control-label" for="theplatform_upload_' . esc_attr( $field_name ) . '">' . esc_html( ucfirst( $field_title ) ) . '</label>';

			$html .= '<input name="' . esc_attr( $field_title ) . '" id="theplatform_upload_' . esc_attr( $field_name ) . '" class="form-control custom_field" type="text" data-type="' . esc_attr( $field_type ) . '" data-structure="' . esc_attr( $field_structure ) . '" data-name="' . esc_attr( strtolower( $field_title ) ) . '" data-prefix="' . esc_attr( strtolower( $field_prefix ) ) . '" data-namespace="' . esc_attr( strtolower( $field_namespace ) ) . '"/>';
			if ( isset( TP_DATA_STRUCTURE_DESCRIPTIONS()[ $field_structure ] ) ) {
				$html .= '<div class="structureDesc"><strong>Structure</strong> ' . esc_html( TP_DATA_STRUCTURE_DESCRIPTIONS()[ $field_structure ] ) . '</div>';
			}
			if ( isset( TP_DATA_TYPE_DESCRIPTIONS()[ $field_type ] ) ) {
				$html .= '<div class="dataTypeDesc"><strong>Format:</strong> ' . esc_html( TP_DATA_TYPE_DESCRIPTIONS()[ $field_type ] ) . '</div>';
			}
			$html .= '<br />';
			$html .= '</div>';
			if ( $i % 2 !== 0 || $i == $len ) {
				$html .= '</div>';
			}
			echo $html;
			$i ++;
		}

		if ( ! empty( $categoryHtml ) ) {
			echo $categoryHtml;
		}
	}

	function user_id_field() {
		$user_id_customfield = $this->preferences['user_id_customfield'];
		if ( strlen( $user_id_customfield ) && $user_id_customfield !== '(None)' ) {
			$userIdField = $this->tp_api->get_customfield_info( $user_id_customfield )['entries'];
			if ( array_key_exists( 0, $userIdField ) ) {
				$field_title     = $userIdField[0]['fieldName'];
				$field_prefix    = $userIdField[0]['namespacePrefix'];
				$field_namespace = $userIdField[0]['namespace'];
				$userID          = strval( wp_get_current_user()->ID );
				echo '<input name="' . esc_attr( $field_title ) . '" id="theplatform_upload_' . esc_attr( $user_id_customfield ) . '" class="userid custom_field" type="hidden" value="' . esc_attr( $userID ) . '" data-type="String" data-name="' . esc_attr( strtolower( $field_title ) ) . '" data-prefix="' . esc_attr( strtolower( $field_prefix ) ) . '" data-namespace="' . esc_attr( strtolower( $field_namespace ) ) . '"/>';
			}
		}
	}

	function edit_tabs_header() {
		?>
		<ul class="nav nav-tabs" role="tablist">
			<li class="active" id="edit"><a href="#edit_content" role="tab" data-toggle="tab">Edit Metadata</a></li>
			<?php
			$tp_uploader_cap = apply_filters( TP_UPLOADER_CAP, TP_UPLOADER_DEFAULT_CAP );
			if ( current_user_can( $tp_uploader_cap ) ) {
				echo '<li id="add_files"><a href="#add_files_content" role="tab" data-toggle="tab">Add New Files</a></li>';
				echo '<li id="publish"><a href="#publish_content" role="tab" data-toggle="tab">Publish</a></li>';
			}
			$tp_revoke_cap = apply_filters( TP_REVOKE_CAP, TP_REVOKE_DEFAULT_CAP );
			if ( current_user_can( $tp_revoke_cap ) ) {
				echo '<li id="revoke"><a href="#revoke_content" role="tab" data-toggle="tab">Revoke</a></li>';
			} ?>
		</ul>

		<div class="tab-content">
		<div class="tab-pane active" id="edit_content"> <?php
	}

	function edit_tabs_content() {
		?>
        </div> <!-- /#edit_content -->

    <div class="tab-pane" id="add_files_content">
        <?php $this->profiles_and_servers( "add" ); ?>
    </div>

    <div class="tab-pane" id="publish_content">
        <div class="row">
            <div class="col-xs-3">
                <?php
		$html = '<div class="form-group"><label class="control-label" for="edit_publishing_profile">Publishing Profile</label>';
		$html .= '<select id="edit_publishing_profile" name="edit_publishing_profile" class="form-control edit_profile">';
		foreach ( $this->profiles as $entry ) {
			$html .= '<option value="' . esc_attr( $entry['id'] ) . '"' . selected( $entry['title'], $this->preferences['default_publish_id'], false ) . '>' . esc_html( $entry['title'] ) . '</option>';
		}
		$html .= '</select></div>';
		echo $html;
		?>
            </div>          
        </div>
        <div class="row" style="margin-top: 10px;">
            <div class="col-xs-3">
                <button id="theplatform_publish_button" class="form-control btn btn-primary" type="button" name="theplatform-publish-button">Publish</button>
            </div>                      
        </div>
    </div>
     <div class="tab-pane" id="revoke_content">
        <div class="row">           
            <div class="col-xs-3">
                <div class="form-group">
                    <label class="control-label" for="publish_status">Currently Published Profiles</label>
                    <select id="publish_status" name="publish_status" class="form-control revoke_profile">
                    </select>
                </div>
            </div>
        </div>
        <div class="row" style="margin-top: 10px;">         
            <div class="col-xs-3">
                <button id="theplatform_revoke_button" class="form-control btn btn-primary" type="button" name="theplatform-revoke-button">Revoke</button>
            </div>          
        </div>
    </div>
  <?php
	}
}