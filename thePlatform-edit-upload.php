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

function theplatform_upload_clear_styles_and_scripts() {
    global $wp_styles; 
    foreach( $wp_styles->queue as $handle ) {   
        wp_dequeue_style( $handle );
    }    

    global $wp_scripts; 
    foreach( $wp_scripts->queue as $handle ) {          
        wp_dequeue_script( $handle );
    }   
        
    wp_enqueue_script( 'tp_edit_upload_js' );           
    wp_enqueue_style( 'tp_edit_upload_css' );    
}

$account = get_option( TP_ACCOUNT_OPTIONS_KEY );
if ( $account == false || empty( $account['mpx_account_id'] ) ) {
    wp_die( 'MPX Account ID is not set, please configure the plugin before attempting to manage media' );
}

$tp_html = new ThePlatform_HTML();

//TODO: Would just be better to not include the upload media link in the sidebar?
preg_match('/MSIE (.*?);/', $_SERVER['HTTP_USER_AGENT'], $matches);

if ( count( $matches ) > 1 ) {
    //Then we're using IE
    $version = $matches[1];
    if ( $version <= 9 ) {
        echo "<h2>Internet Explorer " . $version . ' is not supported</h2>';
        exit;
    }      
}

if ( !isset( $tp_api ) ) {
    $tp_api = new ThePlatform_API;
}
$preferences = get_option( TP_PREFERENCES_OPTIONS_KEY );
$metadata = $tp_api->get_custom_metadata_fields();
$basic_metadata_options = get_option( TP_BASIC_METADATA_OPTIONS_KEY );
$custom_metadata_options = get_option( TP_CUSTOM_METADATA_OPTIONS_KEY );

$tp_uploader_cap = apply_filters( TP_UPLOADER_CAP, TP_UPLOADER_DEFAULT_CAP );
$tp_revoke_cap = apply_filters( TP_REVOKE_CAP, TP_REVOKE_DEFAULT_CAP );

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

    add_action('wp_enqueue_scripts', 'theplatform_upload_clear_styles_and_scripts', 100912);

    theplatform_upload_clear_styles_and_scripts();

    if ( !current_user_can( $tp_uploader_cap ) ) {
        wp_die( '<p>You do not have sufficient permissions to upload MPX Media</p>' );
    }
    $media = array();

    ?>
    <!DOCTYPE html>
    <html <?php language_attributes(); ?>>
    <head><title>thePlatform Upload Form</title>
    <?php wp_head(); ?>
    </head>
    <body style="width: 95%; padding-left: 10px">
        <div ><h1>Upload Media to MPX</h1><div id="media-mpx-upload-form">
    <?php
} else { ?>
    <ul class="nav nav-tabs" role="tablist">
        <li class="active" id="edit"><a href="#edit_content" role="tab" data-toggle="tab">Edit Metadata</a></li>
        <?php 
        if ( current_user_can( $tp_uploader_cap ) ) { 
            echo '<li id="add_files"><a href="#add_files_content" role="tab" data-toggle="tab">Add New Files</a></li>';
            echo '<li id="publish"><a href="#publish_content" role="tab" data-toggle="tab">Publish</a></li>';
        } 
        if ( current_user_can( $tp_revoke_cap ) ) { 
            echo '<li id="revoke"><a href="#revoke_content" role="tab" data-toggle="tab">Revoke</a></li>';
        } ?>
    </ul>

    <div class="tab-content">
        <div class="tab-pane active" id="edit_content">
        <?php } ?>
        <form role="form">
    <?php
    wp_nonce_field( 'theplatform_upload_nonce' );
    
    $html = '';

    if ( strlen( $preferences['user_id_customfield'] ) && $preferences['user_id_customfield'] !== '(None)' ) {
        $userIdField = $tp_api->get_customfield_info( $preferences['user_id_customfield'] )['entries'];
        if (array_key_exists(0, $userIdField)) {
            $field_title = $userIdField[0]['fieldName'];
            $field_prefix = $userIdField[0]['namespacePrefix'];
            $field_namespace = $userIdField[0]['namespace'];
            $userID = strval( wp_get_current_user()->ID );
            echo '<input name="' . esc_attr( $field_title ) . '" id="theplatform_upload_' . esc_attr( $preferences['user_id_customfield'] ) . '" class="userid custom_field" type="hidden" value="' . esc_attr( $userID ) . '" data-type="String" data-name="' . esc_attr( strtolower( $field_title ) ) . '" data-prefix="' . esc_attr( strtolower( $field_prefix ) ) . '" data-namespace="' . esc_attr( strtolower( $field_namespace ) ) . '"/>';                      
        }
        
        
    }

    // Output rows of all our writable metadata
    $tp_html->metadata_fields();


    if ( !defined( 'TP_MEDIA_BROWSER' ) ) {
        $tp_html->profiles_and_servers("upload");    
    } else {
        ?>
        <div class="row" style="margin-top: 10px;">
            <div class="col-xs-3">
                <button id="theplatform_edit_button" class="form-control btn btn-primary" type="button" name="theplatform-edit-button">Submit</button>
            </div>          
        </div>
    <?php } ?>
</form>
<?php
if ( defined( 'TP_MEDIA_BROWSER' ) ) { ?>
    </div>

    <div class="tab-pane" id="add_files_content">
        <?php $tp_html->profiles_and_servers("add"); ?>
    </div>

    <div class="tab-pane" id="publish_content">
        <div class="row">
            <div class="col-xs-3">
                <?php
                if ( !isset($profiles) ) {
                    $profiles = $tp_api->get_publish_profiles();    
                }           
                $html = '<div class="form-group"><label class="control-label" for="edit_publishing_profile">Publishing Profile</label>';
                $html .= '<select id="edit_publishing_profile" name="edit_publishing_profile" class="form-control edit_profile">';              
                foreach ( $profiles as $entry ) {
                    $html .= '<option value="' . esc_attr( $entry['id'] ) . '"' . selected( $entry['title'], $preferences['default_publish_id'], false ) . '>' . esc_html( $entry['title'] ) . '</option>';
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
    <?php } ?>
</div>



</div>

<?php 
    if ( !defined( 'TP_MEDIA_BROWSER' ) ) {
        wp_footer();        
        echo '</body>';        
        echo '</html>';
    } 
?>