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

jQuery(document).ready(function() {

	if (document.title.indexOf('thePlatform Plugin Settings') != -1) {
		// Hide PID option fields
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
	
	jQuery('#upload_add_category').click(function(e) {
		var categories = jQuery(this).prev().clone()
		var name = categories.attr('name');
		if (name.indexOf('-') != -1) {
			name = name.split('-')[0] + '-' + (parseInt(name.split('-')[1])+1)
		}
			
		jQuery(this).before(categories.attr('name',name));
	});

	// Fade in the upload form and fade out the media library view
	jQuery('#media-mpx-upload-button').click(function($) {
		jQuery('#theplatform-library-view').fadeOut(500, function() {
			jQuery('#media-mpx-upload-form').fadeIn(500);
		});
	});

	//Set up the PID for users	
	jQuery('#mpx_account_id').change(function(e) {
			jQuery('#mpx_account_pid').val(jQuery('#mpx_account_id option:selected').val().split('|')[1]);
	})

	//and players
	jQuery('#default_player_name').change(function(e) {
			jQuery('#default_player_pid').val(jQuery('#default_player_name option:selected').val().split('|')[1]);
	})

	// Validate account information in plugin settings fields
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

		jQuery.post(theplatform.ajax_url, data, function(response) {
			if (jQuery("#verification_image").length > 0) {
				jQuery("#verification_image").remove();
			}
			
			if (response.indexOf('success') != -1 ) {
				jQuery('#verify-account').append('<img id="verification_image" src="' + images + 'checkmark.png" />');											
			} else {
				jQuery('#verify-account').append('<img id="verification_image" src="' + images + 'xmark.png" />');				
			}
		});	
	});

	jQuery("#theplatform-edit-media").submit(function(event) {
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
	
		if (validation_error) {
			event.preventDefault();
			return false;
		} else {
			return true;
		}
		
	});

	// Upload a file to MPX
	jQuery("#theplatform_upload_button").click(function(event) {
		var file = document.getElementById('theplatform_upload_file').files[0];
		
		var validation_error = false;
		var params = {};
		var custom_params = {}
	
		jQuery('.upload_field').each(function() {
		   if (jQuery(this).val().match(/<(\w+)((?:\s+\w+(?:\s*=\s*(?:(?:"[^"]*")|(?:'[^']*')|[^>\s]+))?)*)\s*(\/?)>/)) {
			  jQuery(this).css({border: 'solid 1px #FF0000'}); 
			  validation_error = true;
		   }
		});

		if (jQuery('#theplatform_upload_title').val() === "") {
			validation_error = true;
			jQuery('#theplatform_upload_title').addClass('has-error');
		}

		jQuery('.custom_field').each(function() {
		   if (jQuery(this).val().match(/<(\w+)((?:\s+\w+(?:\s*=\s*(?:(?:"[^"]*")|(?:'[^']*')|[^>\s]+))?)*)\s*(\/?)>/)) {
			  jQuery(this).css({border: 'solid 1px #FF0000'}); 
			  validation_error = true;
		   }
		});
	
		if (validation_error) {
			event.preventDefault();
			return false;
		}
		
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

		jQuery('.custom_field').each(function(i){
			if (jQuery(this).val().length != 0) 
				custom_params[jQuery(this).attr('name')] = jQuery(this).val();
		});
		
		var profile = jQuery('.upload_profile');
		
		var upload_window = window.open(theplatform.ajax_url + '?action=theplatform_upload', '_blank', 'menubar=no,location=no,resizable=yes,scrollbars=no,status=no,width=700,height=150')

		upload_window.uploaderData = { 
			file: file,
			params: JSON.stringify(params), 
			custom_params: JSON.stringify(custom_params),
			profile: profile.val()
		}

		upload_window.parentLocation = window.location;
		
	});

	// Reload media viewer with no search queries
	jQuery("#media-mpx-show-all-button, #theplatform_cancel_edit_button").click(function(event) {
		var url = document.location;
		
		document.location = url.origin + url.pathname + "?page=theplatform-media";
	});

	// Cancel upload.. fade out upload form and fade in media library view
	jQuery("#theplatform_cancel_upload_button").click(function(event) {
	
		jQuery('#media-mpx-upload-form').fadeOut(750, function() {
			jQuery('#theplatform-library-view').fadeIn(750);
			message_nag("Cancelling upload..", true);
		});
	});
	
	// Handle search dropdown text
	jQuery('#search-dropdown').change(function() {
		jQuery('#search-by-content').text(jQuery(this).find(":selected").text());
	});
	
	// Handle sort dropdown text
	jQuery('#sort-dropdown').change(function() {
		jQuery('#sort-by-content').text(jQuery(this).find(":selected").text());
	});	

	jQuery('#search-by-content').text(jQuery('.search-select').find(":selected").text());
	jQuery('#sort-by-content').text(jQuery('.sort-select').find(":selected").text());
});