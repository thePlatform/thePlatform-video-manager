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

if ( !defined( 'ABSPATH' ) ) {
	exit;
}


if ( !defined( 'TP_MEDIA_BROWSER' ) ) {
	wp_enqueue_style( 'bootstrap_tp_css' );
	wp_enqueue_script( 'theplatform_js' );

	$tp_uploader_cap = apply_filters( 'tp_uploader_cap', 'upload_files' );

	if ( !current_user_can( $tp_uploader_cap ) ) {
		wp_die( '<p>' . __( 'You do not have sufficient permissions to upload MPX Media' ) . '</p>' );
	}

	$tp_api = new ThePlatform_API;
	$media = array();

	$metadata = $tp_api->get_metadata_fields();
	$preferences = get_option( 'theplatform_preferences_options' );
	$upload_options = get_option( 'theplatform_upload_options' );
	$metadata_options = get_option( 'theplatform_metadata_options' );

	echo '<h1> Upload Media to MPX </h1><div id="media-mpx-upload-form" class="tp">';
}
?>

<form role="form">
	<?php
	wp_nonce_field( 'theplatform_upload_nonce' );

	$upload_options = get_option( 'theplatform_upload_options' );
	$html = '';

	if ( $preferences['user_id_customfield'] !== '(None)' ) {
		echo '<input type="hidden" name="' . esc_attr( $preferences['user_id_customfield'] ) . '" class="custom_field" value="' . wp_get_current_user()->ID . '" />';
	}

	$col = 0;
	$catHtml = '';
	foreach ( $upload_options as $upload_field => $val ) {
		$field_title = (strstr( $upload_field, '$' ) !== false) ? substr( strstr( $upload_field, '$' ), 1 ) : $upload_field;

		if ( $val == 'write' ) {
			if ( $upload_field == 'categories' ) {
				$categories = $tp_api->get_categories( true );
				$catHtml .= '<div class="row">';
				$catHtml .= '<div class="col-xs-3">';
				$catHtml .= '<label class="control-label" for="theplatform_upload_' . esc_attr( $upload_field ) . '">' . esc_html( ucfirst( $field_title ) ) . '</label>';
				$catHtml .= '<select class="category_field form-control" multiple id="theplatform_upload_' . esc_attr( $upload_field ) . '" name="' . esc_attr( $upload_field ) . '">';
				foreach ( $categories as $category ) {
					$catHtml .= '<option value="' . esc_attr( $category['fullTitle'] ) . '">' . esc_html( $category['fullTitle'] ) . '</option>';
				}
				$catHtml .= '</select>';
				$catHtml .= '</div>';
				$catHtml .= '</div>';
			} else {
				if ( $col === 0 ) {
					echo '<div class="row">';
				}
				$default_value = isset($media[$upload_field]) ? esc_attr( $media[$upload_field] ) : '';
				$html = '';
				$html .= '<div class="col-xs-3">';
				$html .= '<label class="control-label" for="theplatform_upload_' . esc_attr( $upload_field ) . '">' . esc_html( ucfirst( $field_title ) ) . '</label>';
				$html .= '<input name="' . esc_attr( $upload_field ) . '" id="theplatform_upload_' . esc_attr( $upload_field ) . '" class="form-control upload_field" type="text" value="' . $default_value . '"/>'; //upload_field
				$html .= '</div>';
				echo $html;
				if ( $col === 2 ) {
					echo '</div>';
					$col = 0;
				} else {
					$col++;
				}
			}
		}
	}

	$metadata_options = get_option( 'theplatform_metadata_options' );

	$html = '';

	foreach ( $metadata_options as $custom_field => $val ) {
		$metadata_info = NULL;
		foreach ( $metadata as $entry ) {
			if ( array_search( $custom_field, $entry ) ) {
				$metadata_info = $entry;
				break;
			}
		}

		if ( is_null( $metadata_info ) ) {
			continue;
		}

		$field_title = $metadata_info['fieldName'];
		$field_prefix = $metadata_info['namespacePrefix'];
		$field_namespace = $metadata_info['namespace'];
		$field_type = $metadata_info['dataType'];
		$field_structure = $metadata_info['dataStructure'];

		if ( $field_title === $preferences['user_id_customfield'] ) {
			continue;
		}

		if ( $val == 'write' ) {
			$field_name = $field_prefix . '$' . $field_title;
			$field_value = isset($media[$field_prefix . '$' . $field_title]) ? $media[$field_prefix . '$' . $field_title] : '';

			$html = '';
			if ( $col === 0 ) {
				echo '<div class="row">';
			}
			$html .= '<div class="col-xs-3">';
			$html .= '<label class="control-label" for="theplatform_upload_' . esc_attr( $field_name ) . '">' . esc_html( ucfirst( $field_title ) ) . '</label>';
			$html .= '<input name="' . esc_attr( $field_title ) . '" id="theplatform_upload_' . esc_attr( $field_name ) . '" class="form-control custom_field" type="text" value="' . esc_attr( $field_value ) . '" data-type="' . esc_attr( $field_type ) . '" data-structure="' . esc_attr( $field_structure ) . '" data-name="' . esc_attr( strtolower( $field_title ) ) . '" data-prefix="' . esc_attr( strtolower( $field_prefix ) ) . '" data-namespace="' . esc_attr( strtolower( $field_namespace ) ) . '"/>';
			$html .= '</div>';
			echo $html;

			if ( $col === 2 ) {
				echo '</div>';
				$col = 0;
			} else {
				$col++;
			}
		}
	}
	if ( $col !== 0 ) {
		echo '</div>';
		$col = 0;
	}

	if ( !empty( $catHtml ) ) {
		echo $catHtml;
	}

	if ( !defined( 'TP_MEDIA_BROWSER' ) ) {
		?>

		<div class="row">
			<div class="col-xs-3">
				<?php
				$profiles = $tp_api->get_publish_profiles();
				$html = '<label class="control-label" for="publishing_profile">Publishing Profile</label>';
				$html .= '<select name="profile" id="publishing_profile" name="publishing_profile" class="form-control upload_profile">';
				$html .= '<option value="tp_wp_none">Do not publish</option>';
				foreach ( $profiles as $entry ) {
					$html .= '<option value="' . esc_attr( $entry['title'] ) . '"' . selected( $entry['title'], $preferences['default_publish_id'], false ) . '>' . esc_html( $entry['title'] ) . '</option>';
				}
				$html .= '</select>';
				echo $html;
				?>
			</div>
		</div>
		<div class="row">
			<div class="col-xs-3">
				<label class="control-label" for="theplatform_upload_file">File</label><input type="file" accept="audio/*|video/*|image/*" id="theplatform_upload_file" />
			</div>
		</div>
		<div class="row">
			<div class="col-xs-3">
				<button id="theplatform_upload_button" class="form-control btn btn-primary" type="button" name="theplatform-upload-button">Upload Media</button>
			</div>
		</div>
	<?php } else {
		?>
		<div class="row" style="margin-top: 10px;">
			<div class="col-xs-3">
				<button id="theplatform_edit_button" class="form-control btn btn-primary" type="button" name="theplatform-edit-button">Submit</button>
			</div>
		</div>
	<?php } ?>
</form>
</div>

