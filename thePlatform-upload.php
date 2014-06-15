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

if ( !isset( $tp_api ) ) {
	$tp_api = new ThePlatform_API;
}

$metadata = $tp_api->get_metadata_fields();
$preferences = get_option( TP_PREFERENCES_OPTIONS_KEY );
$account = get_option( TP_ACCOUNT_OPTIONS_KEY );
$upload_options = get_option( TP_UPLOAD_OPTIONS_KEY );
$metadata_options = get_option( TP_METADATA_OPTIONS_KEY );

$dataTypeDesc = array(
	'Integer' => 'Integer',
	'Decimal' => 'Decimal',
	'String' => 'String',
	'DateTime' => 'MM/DD/YYYY HH:MM:SS',
	'Date' => 'YYYY-MM-DD',
	'Time' => '24 hr time (20:00)',
	'Link' => 'title: Link Title, href: http://www.wordpress.com',
	'Duration' => 'HH:MM:SS',
	'Boolean' => 'true, false, or empty',
	'URI' => 'http://www.wordpress.com',
);

$structureDesc = array(
	'Map' => 'Map (field1: value1, field2: value2)',
	'List' => 'List (value1, value2)',
);

if ( !defined( 'TP_MEDIA_BROWSER' ) ) {
	wp_enqueue_style( 'bootstrap_tp_css' );
	wp_enqueue_script( 'theplatform_js' );

	$tp_uploader_cap = apply_filters( 'tp_uploader_cap', 'upload_files' );

	if ( !current_user_can( $tp_uploader_cap ) ) {
		wp_die( '<p>You do not have sufficient permissions to upload MPX Media</p>' );
	}
	$media = array();

	echo '<h1> Upload Media to MPX </h1><div id="media-mpx-upload-form" class="tp">';
}
?>

<form role="form">
	<?php
	wp_nonce_field( 'theplatform_upload_nonce' );
	
	$html = '';

	if ( strlen( $preferences['user_id_customfield'] ) && $preferences['user_id_customfield'] !== '(None)' ) {
		echo '<input type="hidden" name="' . esc_attr( $preferences['user_id_customfield'] ) . '" class="custom_field" value="' . esc_attr( wp_get_current_user()->ID ) . '" />';
	}

	$catHtml = '';
	$write_fields = array();
	// We need a count of the write enabled fields in order to display rows appropriately.
	foreach ( $upload_options as $upload_field => $val ) {
		if ( $val == 'write' ) {
			$write_fields[] = $upload_field;
		}
	}

	$len = count( $write_fields ) - 1;
	$i = 0;
	foreach ( $write_fields as $upload_field ) {
		$field_title = (strstr( $upload_field, '$' ) !== FALSE) ? substr( strstr( $upload_field, '$' ), 1 ) : $upload_field;
		if ( $upload_field == 'categories' ) {
			$categories = $tp_api->get_categories( TRUE );
			$catHtml .= '<div class="row">';
			$catHtml .= '<div class="col-xs-5">';
			$catHtml .= '<label class="control-label" for="theplatform_upload_' . esc_attr( $upload_field ) . '">' . esc_html( ucfirst( $field_title ) ) . '</label>';
			$catHtml .= '<select class="category_field form-control" multiple id="theplatform_upload_' . esc_attr( $upload_field ) . '" name="' . esc_attr( $upload_field ) . '">';
			foreach ( $categories as $category ) {
				$catHtml .= '<option value="' . esc_attr( $category['fullTitle'] ) . '">' . esc_html( $category['fullTitle'] ) . '</option>';
			}
			$catHtml .= '</select>';
			$catHtml .= '</div>';
			$catHtml .= '</div>';
		} else {
			$default_value = isset( $media[$upload_field] ) ? esc_attr( $media[$upload_field] ) : '';
			$html = '';
			if ( $i % 2 == 0 ) {
				$html .= '<div class="row">';
			}
			$html .= '<div class="col-xs-5">';
			$html .= '<label class="control-label" for="theplatform_upload_' . esc_attr( $upload_field ) . '">' . esc_html( ucfirst( $field_title ) ) . '</label>';
			$html .= '<input name="' . esc_attr( $upload_field ) . '" id="theplatform_upload_' . esc_attr( $upload_field ) . '" class="form-control upload_field" type="text" value="' . esc_attr( $default_value ) . '"/>'; //upload_field
			$html .= '</div>';
			if ( $i % 2 !== 0 ) {
				$html .= '</div>';
			}
			echo $html;
			$i++;
		}
	}
	
	$html = '';
	$write_fields = array();

	foreach ( $metadata_options as $custom_field => $val ) {
		if ( $val == 'write' ) {
			$write_fields[] = $custom_field;
		}
	}

	$i = 0;
	$len = count( $write_fields ) - 1;
	foreach ( $write_fields as $custom_field ) {
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
		$allowed_values = $metadata_info['allowedValues'];

		if ( $field_title === $preferences['user_id_customfield'] ) {
			continue;
		}

		$field_name = $field_prefix . '$' . $field_title;
		$field_value = isset( $media[$field_prefix . '$' . $field_title] ) ? $media[$field_prefix . '$' . $field_title] : '';

		$html = '';
		if ( $i % 2 == 0 ) {
			$html .= '<div class="row">';
		}
		$html .= '<div class="col-xs-5">';
		$html .= '<label class="control-label" for="theplatform_upload_' . esc_attr( $field_name ) . '">' . esc_html( ucfirst( $field_title ) ) . '</label>';

		$html .= '<input name="' . esc_attr( $field_title ) . '" id="theplatform_upload_' . esc_attr( $field_name ) . '" class="form-control custom_field" type="text" value="' . esc_attr( $field_value ) . '" data-type="' . esc_attr( $field_type ) . '" data-structure="' . esc_attr( $field_structure ) . '" data-name="' . esc_attr( strtolower( $field_title ) ) . '" data-prefix="' . esc_attr( strtolower( $field_prefix ) ) . '" data-namespace="' . esc_attr( strtolower( $field_namespace ) ) . '"/>';
		if ( isset( $structureDesc[$field_structure] ) ) {
			$html .= '<div class="structureDesc"><strong>Structure</strong> ' . esc_html( $structureDesc[$field_structure] ) . '</div>';
		}
		if ( isset( $dataTypeDesc[$field_type] ) ) {
			$html .= '<div class="dataTypeDesc"><strong>Format:</strong> ' . esc_html( $dataTypeDesc[$field_type] ) . '</div>';
		}
		$html .= '<br />';
		$html .= '</div>';
		if ( $i % 2 !== 0 || $i == $len ) {
			$html .= '</div>';
		}
		echo $html;
		$i++;
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
				$html .= '<select id="publishing_profile" name="publishing_profile" class="form-control upload_profile">';
				$html .= '<option value="tp_wp_none"' . selected( $preferences['default_publish_id'], 'wp_tp_none', false ) . '>Do not publish</option>';
				foreach ( $profiles as $entry ) {
					$html .= '<option value="' . esc_attr( $entry['title'] ) . '"' . selected( $entry['title'], $preferences['default_publish_id'], false ) . '>' . esc_html( $entry['title'] ) . '</option>';
				}
				$html .= '</select>';
				echo $html;
				?>
			</div>
			<div class="col-xs-3">
				<?php
				$servers = $tp_api->get_servers();
				$html = '<label class="control-label" for="theplatform_server">Server</label>';
				$html .= '<select id="theplatform_server" name="theplatform_server" class="form-control server_id">';
				$html .= '<option value="DEFAULT_SERVER"' . selected( $preferences['mpx_server_id'], "DEFAULT_SERVER", false ) . '>Default Server</option>';
				foreach ( $servers as $entry ) {
					$html .= '<option value="' . esc_attr( $entry['id'] ) . '"' . selected( $entry['id'], $preferences['mpx_server_id'], false ) . '>' . esc_html( $entry['title'] ) . '</option>';
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