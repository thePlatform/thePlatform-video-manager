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

TheplatformUploader = ( function() {		

	/**
	 @function startUpload Inform FMS via the API proxy that we are starting an upload
	 passed to the proxy	 
	 */
	TheplatformUploader.prototype.startUpload = function() {
		var me = this;
		
		var requestUrl = me.uploadUrl + '/web/Upload/startUpload';		
		requestUrl += '?schema=1.1';
		requestUrl += '&token=' + me.token;
		requestUrl += '&account=' + encodeURIComponent( me.account );
		requestUrl += '&_guid=' + me._guid;
		requestUrl += '&_mediaId=' + encodeURIComponent( me._mediaId );
		requestUrl += '&_filePath=' + me._filePath;
		requestUrl += '&_fileSize=' + me._fileSize;
		requestUrl += '&_mediaFileInfo.format=' + me.fileFormat;
		requestUrl += '&_serverId=' + encodeURIComponent ( me._serverId );		
		
		me.message( "Starting Upload of " + me._filePath + 'to ' + me.uploadUrl, true);
		
		var data = {
			url: requestUrl,
			method: 'put',
			returnsValue: false,
			action: 'startUpload'			
		}
		jQuery.ajax( {
			url: theplatform_uploader_local.ajaxurl,			
			type: "POST",	
			data: data,		
			xhrFields: {
				withCredentials: true
			},
			success: function( response ) {			
				me.message( "Waiting for READY status from " + me.uploadUrl + "." );
				me.waitForReady();				
			},
			error: function( result ) {
				me.error( "Call to startUpload failed. Please try again later." );
			}
		} );
	};

	/**
	 @function waitForReady Wait for FMS to become ready for the upload	 	 
	 */
	TheplatformUploader.prototype.waitForReady = function() {
		var me = this;

		var requestUrl = me.uploadUrl + '/data/UploadStatus';
		requestUrl += '?schema=1.0';
		requestUrl += '&token=' + me.token;
		requestUrl += '&account=' + encodeURIComponent( me.account );
		requestUrl += '&byGuid=' + me._guid;

		var data = {
			url: requestUrl,
			method: 'get',
			returnsValue: true,
			action: 'startUpload'			
		}

		jQuery.ajax( {
			url: theplatform_uploader_local.ajaxurl,		
			type: "POST",	
			data: data,					
			xhrFields: {
				withCredentials: true
			},						
			success: function( response ) {
				var data = response.data;
				if ( data.entries.length !== 0 ) {
					var state = data.entries[0].state;

					if ( state === "Ready" ) {

						var frags = me.fragFile( me.file );

						me.frags_uploaded = 0;
						me.num_fragments = frags.length;
						me.progressIncrements = 1/frags.length;

						me.message( "Beginning upload of " + frags.length + " fragments. Please do not close this window." );

						me.uploadFragments( frags, 0 );

					} else {
						setTimeout(function() { me.waitForReady() }, 1000 );
					}
				} else {
					setTimeout(function() { me.waitForReady() }, 1000 );
				}			
			},
			error: function( response ) {
				me.error( "An error occurred while waiting for upload server READY status: " + response );
			}
		} );
	};

	/**
	 @function uploadFragments Uploads file fragments to FMS
	 
	 @param {Array} fragments - Array of file fragments
	 @param {Integer} index - Index of current fragment to upload
	 */
	TheplatformUploader.prototype.uploadFragments = function( fragments, index ) {
		var me = this;
		var fragSize = 1024 * 1024 * 5;

		if ( this.failed ) {
			return;
		}

		if ( me.frags_uploaded == 0 ) {					
			me.message( 'Uploading File...', true )
			NProgress.set(0.00001)
		} 

		var requestUrl = me.uploadUrl + '/web/Upload/uploadFragment';
		requestUrl += '?schema=1.1';
		requestUrl += '&token=' + me.token;
		requestUrl += '&account=' + encodeURIComponent( me.account );
		requestUrl += '&_guid=' + me._guid;
		requestUrl += '&_offset=' + ( index * fragSize );
		requestUrl += '&_size=' + fragments[index].size;		

		var data = new FormData();
		data.append('file', fragments[index]);
		data.append('url', requestUrl);
		data.append('action', 'startUpload');
		data.append('returnsValue', false);
		data.append('method', 'put');

		// var data = {
		// 	url: requestUrl,
		// 	method: 'put',
		// 	returnsValue: false,
		// 	action: 'startUpload',
		// 	file: fragments[index]		
		// }

		jQuery.ajax( {
			url: theplatform_uploader_local.ajaxurl,			
			data: data,			
			type: "POST",
			processData: false,
  			contentType: false,
			xhrFields: {
				withCredentials: true
			},
			success: function( response ) {
				me.frags_uploaded++;
				
				if ( me.num_fragments == me.frags_uploaded ) {
					me.message( "Uploaded last fragment. Finishing up" );
					NProgress.inc(me.progressIncrements);
					me.finish();
				} else {					
					NProgress.inc(me.progressIncrements);
					me.message( "Finished uploading fragment " + me.frags_uploaded + " of " + me.num_fragments );
					index++;
					me.attempts = 0;
					me.uploadFragments( fragments, index );
				}
			},
			error: function( response, type, msg ) {
				me.attempts++;
				if ( index == 0 ) {
					me.message( "Unable to start upload, server is not ready." );
					me.startUpload();
					return;
				}
				var actualIndex = parseInt( index ) + 1;
				me.error( "Unable to upload fragment " + actualIndex + " of " + me.num_fragments + ". Retrying count is " + me.attempts + " of 5. Retrying in 5 seconds.." );

				if ( me.attempts < 5 ) {
					setTimeout( function() {
						me.uploadFragments( fragments, index );
					}, 1000 );
				} else {
					me.error( "Uploading fragment " + actualIndex + " of " + me.num_fragments + " failed on the client side. Cancelling... Retry upload later." );

					window.setTimeout( function() {
						me.cancel();
					}, 6000 );

				}
			}
		} );
	};

	/**
	 @function finish Notify MPX that the upload has finished	 
	 */
	TheplatformUploader.prototype.finish = function() {
		var me = this;

		if ( this.finishedUploadingFragments ) {
			return;
		}

		this.finishedUploadingFragments = true;

		var requestUrl = me.uploadUrl + '/web/Upload/finishUpload';
		requestUrl += '?schema=1.1';
		requestUrl += '&token=' + me.token;
		requestUrl += '&account=' + encodeURIComponent( me.account );
		requestUrl += '&_guid=' + me._guid;

		var data = "finished";

		var data = {
			url: requestUrl,
			method: 'post',
			returnsValue: false,
			action: 'startUpload',
			data: 'finished'	
		}

		jQuery.ajax( {
			url: theplatform_uploader_local.ajaxurl,
			data: data,			
			type: "POST",
			xhrFields: {
				withCredentials: true
			},
			success: function( response ) {
				setTimeout( function() { me.waitForComplete(); }, 5000 )
			},
			error: function( response ) {

			}
		} );
	};

	/**
	 @function waitForComplete Poll FMS via the API proxy until upload status is 'Complete'
	 passed to the proxy
	 */
	TheplatformUploader.prototype.waitForComplete = function() {
		var me = this;
		
		var requestUrl = me.uploadUrl + '/data/UploadStatus';
		requestUrl += '?schema=1.0';
		requestUrl += '&token=' + me.token;
		requestUrl += '&account=' + encodeURIComponent( me.account );
		requestUrl += '&byGuid=' + me._guid;

		var data = {
			url: requestUrl,
			method: 'get',
			returnsValue: true,
			action: 'startUpload',			
		}

		jQuery.ajax( {
			url: theplatform_uploader_local.ajaxurl,			
			type: "GET",			
			data: data,
			xhrFields: {
				withCredentials: true
			},
			success: function( response ) {
				var data = response.data;
				if ( data.entries.length != 0 ) {
					var state = data.entries[0].state;

					if ( state === "Complete" ) {
						var fileID = data.entries[0].fileId;

						me.file_id = fileID;

						if ( me.profile != "tp_wp_none" ) {
							me.message( "Waiting for MPX to publish media." );
							me.publishMedia();
						}
						else {
							me.message( "Upload completed, you can now safely close this window." );
							window.setTimeout( 'window.close()', 5000 );
						}
					} else if ( state === "Error" ) {
						me.error( data.entries[0].exception );
					} else {
						me.message( state );
						setTimeout(function() { me.waitForComplete(); }, 5000)
					}
				} else {
					setTimeout(function() { me.waitForComplete(); }, 5000)
				}
			},
			error: function( response ) {
				me.error( "An error occurred while waiting for upload server COMPLETE status: " + response );
			}
		} );
	};

	/**
	 @function publishMedia Publishes the uploaded media via the API proxy
	 passed to the proxy
	 */
	TheplatformUploader.prototype.publishMedia = function() {
		var me = this;
		var params =  { 
			mediaId: me._mediaId,
			account: me.account,
			profile: me.publishProfile,
			action: 'publishMedia',
			_wpnonce: theplatform_uploader_local.tp_nonce['publishMedia'],
			token: me.token
		};
		
		if ( this.publishing ) {
			return;
		}

		this.publishing = true;

		me.message( "Publishing media..." );

		jQuery.ajax( {
			url: theplatform_uploader_local.ajaxurl,
			data: params,
			type: "POST",
			success: function( response ) {
				if ( response.success ) {
					me.message( "Media is being published. It may take several minutes until the media is available. This window will now close.", true );
					window.setTimeout( 'window.close()', 10000 );
				} else {
					me.message( "Publish for the uploaded Media was requested but timed out, this is normal but your Media may or may not have published.", true );
					window.setTimeout( 'window.close()', 10000 );
				}
			}
		} );
	};

	/**
	 @function cancel Notify the API proxy to cancel the upload process
	 passed to the proxy
	 */
	TheplatformUploader.prototype.cancel = function() {
		var me = this;
		
		var requestUrl = me.uploadUrl + '/web/Upload/cancelUpload';
		requestUrl += '?schema=1.1';
		requestUrl += '&token=' + me.token;
		requestUrl += '&account=' + encodeURIComponent( me.account );
		requestUrl += '&_guid=' + me._guid;
		this.failed = true;

		jQuery.ajax( {
			url: requestUrl,		
			type: "PUT",
			xhrFields: {
				// withCredentials: true
			},
			complete: function() {

			}
		} );
	};

	/**
	 @function fragFile Slices a file into fragments
	 @param {File} file - file to slice
	 @return {Array} array of file fragments
	 */
	TheplatformUploader.prototype.fragFile = function( file ) {
		var fragSize = 1024 * 1024 * 5;
		var i, j, k;
		var ret = [ ];				

		if ( !( this.file.slice || this.file.mozSlice ) ) {
			return this.file;
		}

		for ( i = j = 0, k = Math.ceil( this.file.size / fragSize ); 0 <= k ? j < k : j > k; i = 0 <= k ? ++j : --j ) {
			if ( this.file.slice ) {
				ret.push( this.file.slice( i * fragSize, ( i + 1 ) * fragSize ) );
			} else if ( file.mozSlice ) {
				ret.push( this.file.mozSlice( i * fragSize, ( i + 1 ) * fragSize ) );
			}
		}

		return ret;
	};

	/**
	 @function Attempt to parse JSON, alert to user if it failed
	 @param {string} jsonString - JSON String	 
	 */
	TheplatformUploader.prototype.parseJSON = function( jsonString ) {
		try {
			return jQuery.parseJSON( jsonString );
		}
		catch ( ex ) {
			me.error( jsonString );
		}
	};

	TheplatformUploader.prototype.createUUID = function() {
    // http://www.ietf.org/rfc/rfc4122.txt
	    var s = [];
	    var hexDigits = "0123456789abcdef";
	    for (var i = 0; i < 36; i++) {
	        s[i] = hexDigits.substr(Math.floor(Math.random() * 0x10), 1);
	    }
	    s[14] = "4";  // bits 12-15 of the time_hi_and_version field to 0010
	    s[19] = hexDigits.substr((s[19] & 0x3) | 0x8, 1);  // bits 6-7 of the clock_seq_hi_and_reserved to 01
	    s[8] = s[13] = s[18] = s[23] = "-";

	    var uuid = s.join("");
	    return uuid;
	};

	/**
	 @function message Display an informative message to the user
	 @param {String} msg - The message to display
	 @param {Boolean} fade - Whether or not to fade the message div after some delay
	 */
	TheplatformUploader.prototype.message = function( msg, userFacing, isError ) {
		console.log(msg);	
		
		if ( !userFacing ) return;
		jQuery( '.lead' ).removeClass('error');

		if ( isError == true) {
			jQuery( '.lead' ).addClass('error');
		}

		jQuery( '.lead' ).animate( { 'opacity': 0 }, 500, function() {
			jQuery( this ).html( msg );
		} ).animate( { 'opacity': 1 }, 500 );
	};

	/**
	 @function error Display an error message to the user
	 @param {String} msg - The message to display
	 @param {Boolean} fade - Whether or not to fade the message div after some delay
	 */
	TheplatformUploader.prototype.error = function( msg ) {
		this.message( msg, true, true );
	};

	/**
	 @function constructor Inform the API proxy to create placeholder media assets in MPX and begin uploading	 
	 */
	function TheplatformUploader( file, fields, custom_fields, profile, server ) {
		var me = this;
		this.file = file;
			
		var splashHtml = '<div class="splash card">' +
		    '<div role="spinner">' +
		        '<div class="spinner-icon"></div>' +
		    '</div>' +		    
		    '<p class="lead" style="text-align:center">Preparing for upload...</p>' +
		    '<div class="progress">' +
		        '<div class="mybar" role="bar">' +
		    '</div>' +
		    '</div>' +
		'</div>';

		NProgress.configure({
		    template: splashHtml,
		    trickle: false,		    
		    minimum: 0
		});

		NProgress.start();

		this.failed = false;
		this.finishedUploadingFragments = false;
		this.publishing = false;
		this.attempts = 0;
		this._fileSize = file.size;
		this.filetype = file.type;
		this._filePath = file.name;	
		this.publishProfile = profile;

		var data = {
			_wpnonce: theplatform_uploader_local.tp_nonce['initialize_media_upload'],
			action: 'initialize_media_upload',
			filesize: file.size,
			filetype: file.type,
			filename: file.name,
			server_id: server,
			fields: fields,
			custom_fields: custom_fields			
		};
		
		me.message( "Creating Placeholder media" );				

		jQuery.post( theplatform_uploader_local.ajaxurl, data, function( response ) {			
			if ( response.success ) {
				var data = response.data;
				
				me.uploadUrl = data.uploadUrl
				me.token = data.token;
				me.account = data.account;
				me._mediaId = data.mediaId;				
				me._guid = me.createUUID();
				me._serverId = data.serverId;
				me.format = data.format;
				me.contentType = data.contentType;
				
				me.message( "Placeholder media created with id: " + me._mediaId );								
				me.startUpload();
			} else {
				me.error( "Unable to upload media asset at this time. Please try again later." + response.data );
			}
		});
	};

	return TheplatformUploader;
} )();