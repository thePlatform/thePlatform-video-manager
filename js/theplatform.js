(function(jQuery){
	jQuery.extend({
	
		/**
		 @function base64Encode Performs Base 64 Encoding on a string
		 @param {String} data - string to encode
		 @return {Number} encoded string
		*/
		base64Encode: function(data) {
			var b64 = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/=";
			var o1, o2, o3, h1, h2, h3, h4, bits, i = 0,
			  ac = 0,
			  enc = "",
			  tmp_arr = [];

			if (!data) {
			  return data;
			}

			do { 
			  o1 = data.charCodeAt(i++);
			  o2 = data.charCodeAt(i++);
			  o3 = data.charCodeAt(i++);

			  bits = o1 << 16 | o2 << 8 | o3;

			  h1 = bits >> 18 & 0x3f;
			  h2 = bits >> 12 & 0x3f;
			  h3 = bits >> 6 & 0x3f;
			  h4 = bits & 0x3f;

			  tmp_arr[ac++] = b64.charAt(h1) + b64.charAt(h2) + b64.charAt(h3) + b64.charAt(h4);
			} while (i < data.length);

			enc = tmp_arr.join('');

			var r = data.length % 3;

			return (r ? enc.slice(0, r - 3) : enc) + '==='.slice(r || 3);
		}
	});
})(jQuery);


/**
 @function message_nag Display an informative message to the user
 @param {String} msg - The message to display
 @param {Boolean} fade - Whether or not to fade the message div after some delay
*/
var message_nag = function(msg, fade, isError) {
	fade = typeof fade !== 'undefined' ? fade : false;
	var messageType="updated";
	if (isError)
		messageType="error";

	if (jQuery('#message_nag').length == 0) {
		jQuery('.wrap > h2').parent().prev().after('<div id="message_nag" class="' + messageType + '"><p id="message_nag_text">' +  msg + '</p></div>').fadeIn(1000);
	} else {
		jQuery('#message_nag').removeClass();
		jQuery('#message_nag').addClass(messageType);
		jQuery('#message_nag').fadeIn(500);
		jQuery('#message_nag_text').animate({'opacity': 0}, 500, function () {
			jQuery(this).text(msg);
		}).animate({'opacity': 1}, 500);
	}
	
	if (fade == true) {
		jQuery('#message_nag').delay(6000).fadeOut(10000);
	}
}

/**
 @function error_nag Display an error message to the user
 @param {String} msg - The message to display
 @param {Boolean} fade - Whether or not to fade the message div after some delay
*/
var error_nag = function(msg, fade) {
	message_nag(msg, fade, true);
}

/**
 * Validate Media data is valid before submitting upload/edit
 * @param  {Object} event click event
 * @return {boolean}       Did validation pass or not
 */
var validate_media = function(event) {

	//TODO: Change CSS to Bootstrap classes
	//TODO: Validate that file has been selected for upload but not edit
	var validation_error = false;
	
	jQuery('.edit_field').each(function() {
	   if (jQuery(this).val().match(/<(\w+)((?:\s+\w+(?:\s*=\s*(?:(?:"[^"]*")|(?:'[^']*')|[^>\s]+))?)*)\s*(\/?)>/)) {
		  jQuery(this).css({border: 'solid 1px #FF0000'}); 
		  validation_error = true;
	   }
	});		

	jQuery('.edit_custom_field').each(function() {
	   if (jQuery(this).val().match(/<(\w+)((?:\s+\w+(?:\s*=\s*(?:(?:"[^"]*")|(?:'[^']*')|[^>\s]+))?)*)\s*(\/?)>/)) {
		  jQuery(this).css({border: 'solid 1px #FF0000'}); 
		  validation_error = true;
	   }
	});

	if (jQuery('#theplatform_upload_title').val() === "") {
		validation_error = true;
		jQuery('#theplatform_upload_title').addClass('has-error');
	}

	
	return validation_error;
}

var parseMediaParams = function() {
	var params = {};
	jQuery('.upload_field').each(function(i){
		if (jQuery(this).val().length != 0)
			params[jQuery(this).attr('name')] = jQuery(this).val();
	});

	var categories = []
	var categoryArray = jQuery('.category_field').val();
	for (i in categoryArray) {
		var name = categoryArray[i];
		if (name != '(None)') {
			var cat = {};
			cat['name'] = name;
			categories.push(cat);
		}
	}
		
	params['categories'] = categories;

	return params;
}

var parseCustomParams = function() {
	var custom_params = {};
	jQuery('.custom_field').each(function(i){
		if (jQuery(this).val().length != 0) 
			custom_params[jQuery(this).attr('name')] = jQuery(this).val();
		});
	return custom_params;
}

jQuery(document).ready(function() {

	// Hide PID option fields in the Settings page
	if (document.title.indexOf('thePlatform Plugin Settings') != -1) {		
		jQuery('#mpx_account_pid').parent().parent().hide();
		jQuery('#default_player_pid').parent().parent().hide();

		if (jQuery('#mpx_account_id option:selected').length != 0) {
			
			jQuery('#mpx_account_pid').val(jQuery('#mpx_account_id option:selected').val().split('|')[1]);
		}
		else 
			jQuery('#mpx_account_id').parent().parent().hide();

		if (jQuery('#default_player_name option:selected').length != 0) {			
			jQuery('#default_player_pid').val(jQuery('#default_player_name option:selected').val().split('|')[1]);	
		}
		else
			jQuery('#default_player_name').parent().parent().hide();

		if (jQuery('#mpx_server_id option:selected').length == 0) {			
			jQuery('#mpx_server_id').parent().parent().hide();
		}
	}	
	
	//Set up the PID for the MPX account on change in the Settings page	
	jQuery('#mpx_account_id').change(function(e) {
			jQuery('#mpx_account_pid').val(jQuery('#mpx_account_id option:selected').val().split('|')[1]);
	})

	//Set up the PID for the Player on change in the Settings page
	jQuery('#default_player_name').change(function(e) {
			jQuery('#default_player_pid').val(jQuery('#default_player_name option:selected').val().split('|')[1]);
	})

	// Validate account information in plugin settings fields by logging in to MPX
	jQuery("#verify-account-button").click(function($) {
		var usr = jQuery("#mpx_username").val();
		var pwd = jQuery("#mpx_password").val();
		var images = theplatform.plugin_base_url;
	
		var hash = jQuery.base64Encode(usr + ":" + pwd);
	
		var data = {
			action: 'verify_account',
			_wpnonce: theplatform.tp_nonce,
			auth_hash: hash
		};

		jQuery.post(theplatform.ajaxurl, data, function(response) {
			if (jQuery("#verification_image").length > 0) {
				jQuery("#verification_image").remove();
			}
			
			if (response.indexOf('success') != -1 ) {
				jQuery('#verify-account-dashicon').removeClass('dashicons-no').addClass('dashicons-yes');
			} else {
				jQuery('#verify-account-dashicon').removeClass('dashicons-yes').addClass('dashicons-no');
			}
		});	
	});

	//Edit Media Validation	
	jQuery("#theplatform_edit_button").click(function(event) {
		var validation_error = validate_media(event);;
		var params = parseMediaParams();
		var custom_params = parseCustomParams();	
		params.id = tpHelper.mediaId;

		var data = {
			action: 'theplatform_edit',
			params: JSON.stringify(params),
			custom_params: JSON.stringify(custom_params)
		}

		jQuery('#tp-edit-dialog').dialog('close');
		jQuery.post(localscript.ajaxurl, data, function(resp) {
			refreshView();
		});
	});

	// Upload media button handler
	jQuery("#theplatform_upload_button").click(function(event) {
		var file = document.getElementById('theplatform_upload_file').files[0];

		var validation_error = validate_media(event);

		if (validation_error || file === undefined)
			return false;

		var params = parseMediaParams();
		var custom_params = parseCustomParams();						
		
		var profile = jQuery('.upload_profile');
		
		var upload_window = window.open(theplatform.ajaxurl + '?action=theplatform_upload', '_blank', 'menubar=no,location=no,resizable=yes,scrollbars=no,status=no,width=700,height=150')

		upload_window.uploaderData = { 
			file: file,
			params: JSON.stringify(params), 
			custom_params: JSON.stringify(custom_params),
			profile: profile.val()
		}

		upload_window.parentLocation = window.location;
		
	});
});