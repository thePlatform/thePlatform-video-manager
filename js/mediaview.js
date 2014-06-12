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

jQuery( document ).ready( function() {
	//Parse params and basic setup.
	var queryParams = mpxHelper.getParameters();
	tpHelper.selectedCategory = '';
	tpHelper.feedEndRange = 0;
	tpHelper.queryString = '';
	$pdk.initialize();
	jQuery( '#load-overlay' ).hide();
	mpxHelper.getCategoryList( buildCategoryAccordion );

	jQuery( '#btn-embed' ).click( function() {

		var player = jQuery( '#selectpick-player' ).val();

		var shortcode = '[theplatform media="' + tpHelper.currentRelease + '" player="' + player + '"]';

		var win = window.dialogArguments || opener || parent || top;
		var editor = win.tinyMCE.activeEditor;
		var isVisual = ( typeof win.tinyMCE != "undefined" ) && editor && !editor.isHidden();
		if ( isVisual ) {
			editor.execCommand( 'mceInsertContent', false, shortcode );
		}
		else {
			var currentContent = jQuery( '#content', window.parent.document ).val();
			if ( typeof currentContent == 'undefined' )
				currentContent = '';
			jQuery( '#content', window.parent.document ).val( currentContent + shortcode );
		}
	} )

	jQuery( '#btn-embed-close' ).click( function() {
		jQuery( '#btn-embed' ).click();
		var win = opener || parent
		if ( win.tinyMCE.majorVersion > 3 )
			win.tinyMCE.activeEditor.windowManager.close();
		else
			win.jQuery( '#tp-embed-dialog' ).dialog( 'close' );
	} )

	jQuery( '#btn-set-image' ).click( function() {
		var post_id = window.parent.jQuery( '#post_ID' ).val();
		if ( !tpHelper.selectedThumb || !post_id )
			return;

		var data = {
			action: 'set_thumbnail',
			img: tpHelper.selectedThumb,
			id: post_id,
			_wpnonce: localscript.tp_nonce
		};

		jQuery.post( ajaxurl, data, function( response ) {
			if ( response.indexOf( 'set-post-thumbnail' ) != -1 )
				window.parent.jQuery( '#postimagediv .inside' ).html( response );
		} );
	} );

	// if (location.search.indexOf('&embed=true') != -1)
	//     newMedia += '<button class="btn btn-xs media-embed pull-right" data-toggle="tooltip" data-placement="bottom" title="Embed this Media"><div class="dashicons dashicons-migrate"></div></button>';
	// if (jQuery('#tp-edit-dialog').length !== 0)
	//     newMedia += '<button class="btn btn-xs media-edit pull-right" data-toggle="tooltip" data-placement="bottom" title="Edit this Media"><div class="dashicons dashicons-edit"></div></button>';
	jQuery( '#btn-edit' ).click( function() {

		jQuery( "#tp-edit-dialog" ).dialog( {
			dialogClass: "wp-dialog",
			modal: true,
			resizable: true,
			minWidth: 800,
			width: 1024,
			position: [ 'center', 20 ]
		} ).css( "overflow", "hidden" );


		return false;
	} );

	/**
	 * Set up the infinite scrolling media list
	 */
	jQuery( '#media-list' ).infiniteScroll( {
		threshold: 100,
		onEnd: function() {
			//No more results
		},
		onBottom: function( callback ) {
			jQuery( '#load-overlay' ).show(); // show loading before we call getVideos
			var theRange = parseInt( tpHelper.feedEndRange );
			theRange = ( theRange + 1 ) + '-' + ( theRange + 20 );
			mpxHelper.getVideos( theRange, function( resp ) {
				if ( resp['isException'] ) {
					jQuery( '#load-overlay' ).hide();
					//what do we do on error?
				}

				tpHelper.feedResultCount = resp['totalResults'];
				tpHelper.feedStartRange = resp['startIndex'];
				tpHelper.feedEndRange = 0;
				if ( resp['entryCount'] > 0 )
					tpHelper.feedEndRange = resp['startIndex'] + resp['entryCount'] - 1;
				else
					notifyUser( 'info', 'No Results' );

				var entries = resp['entries'];
				for ( var i = 0; i < entries.length; i++ )
					addMediaObject( entries[i] );

				jQuery( '#load-overlay' ).hide();
				Holder.run();
				callback( parseInt( tpHelper.feedEndRange ) < parseInt( tpHelper.feedResultCount ) ); //True if there are still more results.
			} );
		}
	} );

	//This is for setting a section "scrollable" so it will scroll without scrolling everything else.
	jQuery( '.scrollable' ).on( 'DOMMouseScroll mousewheel', function( ev ) {
		var $this = jQuery( this ),
				scrollTop = this.scrollTop,
				scrollHeight = this.scrollHeight,
				height = $this.height(),
				delta = ( ev.type == 'DOMMouseScroll' ? ev.originalEvent.detail * -40 : ev.originalEvent.wheelDelta ),
				up = delta > 0;

		var prevent = function() {
			ev.stopPropagation();
			ev.preventDefault();
			ev.returnValue = false;
			return false;
		};

		if ( !up && -delta > scrollHeight - height - scrollTop ) {
			// Scrolling down, but this will take us past the bottom.
			$this.scrollTop( scrollHeight );
			return prevent();
		} else if ( up && delta > scrollTop ) {
			// Scrolling up, but this will take us past the top.
			$this.scrollTop( 0 );
			return prevent();
		}
	} );

	/**
	 * Search form event handlers
	 */
	jQuery( '#btn-feed-preview' ).click( refreshView );

	jQuery( 'input:checkbox', '#my-content' ).click( refreshView );

	jQuery( '#selectpick-sort' ).on( 'change', refreshView );

	jQuery( '#input-search' ).keyup( function( event ) {
		if ( event.keyCode == 13 )
			refreshView();
	} );

	/**
	 * Look and feel event handlers
	 */
	jQuery( document ).on( 'click', '.media', function() {
		updateContentPane( jQuery( this ).data( 'media' ) );
		jQuery( '.media' ).css( 'background-color', '' );
		jQuery( this ).css( 'background-color', '#D8E8FF' );
		jQuery( this ).data( 'bgc', '#D8E8FF' );
		tpHelper.currentRelease = jQuery( this ).data( 'release' );
		tpHelper.mediaId = jQuery( this ).data( 'id' );
		tpHelper.selectedThumb = jQuery( this ).data( 'media' )['defaultThumbnailUrl'];
		$pdk.controller.resetPlayer();
		if ( tpHelper.currentRelease !== undefined ) {
			jQuery( '#modal-player-placeholder' ).hide();
			jQuery( '.tpPlayer' ).css( 'visibility', 'visible' );
			$pdk.controller.loadReleaseURL( "//link.theplatform.com/s/" + tpHelper.accountPid + "/" + tpHelper.currentRelease, true );
		}
		else {
			jQuery( '.tpPlayer' ).css( 'visibility', 'hidden' );
			jQuery( '#modal-player-placeholder' ).show()
		}
	} );

	//Update background color when hovering over media
	jQuery( document ).on( 'mouseenter', '.media', function() {
		$this = jQuery( this );
		$this.data( 'bgc', $this.css( 'background-color' ) );
		$this.css( 'background-color', '#f5f5f5' );
	} );

	//Update background color when hovering off media
	jQuery( document ).on( 'mouseleave', '.media', function() {
		$this = jQuery( this );
		var oldbgc = $this.data( 'bgc' );

		if ( oldbgc )
			$this.css( 'background-color', oldbgc );
		else
			$this.css( 'background-color', '' );

	} );

	/**
	 * Set the page layout 
	 */
	var container = window.parent.document.getElementById( 'tp-container' )
	if ( container )
		container.style.height = window.parent.innerHeight;

	jQuery( '#info-affix' ).affix( {
		offset: {
			top: 0
		}
	} );

	jQuery( '#filter-affix' ).affix( {
		offset: {
			top: 0
		}
	} );

} );

/**
 * Refresh the infinite scrolling media list based on the selected category and search options
 * @return {void} 
 */
function refreshView() {
	notifyUser( 'clear' ); //clear alert box.
	var $mediaList = jQuery( '#media-list' );
	//TODO: If sorting clear search?
	var queryObject = {
		search: jQuery( '#input-search' ).val(),
		category: tpHelper.selectedCategory,
		sort: getSort(),
		desc: jQuery( '#sort-desc' ).data( 'sort' ),
		myContent: jQuery( '#my-content-cb' ).prop( 'checked' )
	};

	tpHelper.queryParams = queryObject
	var newFeed = mpxHelper.buildMediaQuery( queryObject );

	delete queryObject.selectedGuids;
	tpHelper.queryString = mpxHelper.buildMediaQuery( queryObject );

	displayMessage( '' );

	tpHelper.feedEndRange = 0;
	$mediaList.empty();
	$mediaList.infiniteScroll( 'reset' );
}

function getSort() {
	var sortMethod = jQuery( 'option:selected', '#selectpick-sort' ).val();

	switch ( sortMethod ) {
		case "Added":
			sortMethod = "added|desc";
			break;
		case "Updated":
			sortMethod = "updated|desc";
			break;
		case "Title":
			sortMethod = "title";
			break;
	}

	return sortMethod || "added";
}

function getSearch() {
	return jQuery( '#input-search' ).val();
}

function buildCategoryAccordion( resp ) {
	var entries = resp['entries'];
	for ( var idx in entries ) {
		var entryTitle = entries[idx]['title'];
		jQuery( '#list-categories' ).append( '<a href="#" class="list-group-item cat-list-selector">' + entryTitle + '</a>' );
	}

	//Add an empty row for scrolling
	jQuery( '#list-categories' ).append( '<div style="height: 50px;"></div>' );

	jQuery( '#list-categories' ).on( 'mouseover', function() {
		jQuery( 'body' )[0].style.overflowY = 'none';
	} );
	jQuery( '#list-categories' ).on( 'mouseout', function() {
		jQuery( 'body' )[0].style.overflowY = 'auto';
	} );

	jQuery( '.cat-list-selector', '#list-categories' ).click( function() {
		tpHelper.selectedCategory = jQuery( this ).text();
		if ( tpHelper.selectedCategory == "All Videos" )
			tpHelper.selectedCategory = '';
		jQuery( '.cat-list-selector', '#list-categories' ).each( function( idx, item ) {
			var $item = jQuery( item );

			if ( ( tpHelper.selectedCategory == $item.text() ) || ( tpHelper.selectedCategory == '' && $item.text() == 'All Videos' ) )
				$item.css( 'background-color', '#D8E8FF' );
			else
				jQuery( item ).css( 'background-color', '' );
		} );
		jQuery( '#input-search' ).val( '' ); //Clear the searching when we choose a category        

		refreshView();
	} );
}

function notifyUser( type, msg ) {
	var $msgPanel = jQuery( '#message-panel' );
	$msgPanel.attr( 'class', '' );
	if ( type === 'clear' ) {
		$msgPanel.attr( 'class', '' );
		msg = '';
	} else {
		$msgPanel.addClass( 'alert alert-' + type );
		$msgPanel.alert();
	}
	$msgPanel.text( msg );
}

function addMediaObject( media ) {
	//Prevent adding the same media twice.
	// This cannot be filtered out earlier because it only really occurs when
	// Something just gets added.
	if ( document.getElementById( media.guid ) != null ) //Can't use jquery because of poor guid format convention.
		return;

	var placeHolder = "";
	if ( media.defaultThumbnailUrl === "" )
		placeHolder = "holder.js/128x72/text:No Thumbnail";

	var newMedia = '<div class="media" id="' + media.guid + '"><img class="media-object pull-left thumb-img" data-src="' + placeHolder + '" alt="128x72" src="' + media.defaultThumbnailUrl + '">'
	newMedia += '<div class="media-body">' + '<div id="head"><strong class="media-heading"></strong></div>' + '<div id="source"></div>' + '<div id="desc"></div>' + '</div>' + '</div>';

	newMedia = jQuery( newMedia );

	jQuery( '#head > strong', newMedia ).text( media.title );
	if ( media.description ) {
		if ( media.description.length > 300 )
			media.description = media.description.substring( 0, 297 ) + '...'
		jQuery( '#desc', newMedia ).text( media.description );
	}

	newMedia.data( 'guid', media.guid );
	newMedia.data( 'media', media );
	newMedia.data( 'id', media.id )
	var previewUrl = mpxHelper.extractVideoUrlfromMedia( media );
	if ( previewUrl.length == 0 && tpHelper.isEmbed == "1" )
		return;

	newMedia.data( 'release', previewUrl.pop() )

	jQuery( '#media-list' ).append( newMedia );

	//Select the first one on the page.
	if ( jQuery( '#media-list' ).children().length < 2 )
		jQuery( '.media', '#media-list' ).click();
}

function updateContentPane( mediaItem ) {
	var i, catArray, catList;

	var $fields = jQuery( '.panel-body span' )

	$fields.each( function( index, value ) {
		var name = jQuery( value ).data( 'name' );
		var fullName = name
		var prefix = jQuery( value ).data( 'prefix' );
		var dataType = jQuery( value ).data( 'type' );
		var dataStructure = jQuery( value ).data( 'structure' );
		if ( prefix !== undefined )
			fullName = prefix + '$' + name;
		var value = mediaItem[fullName];
		if ( name === 'categories' ) {
			var catArray = mediaItem.categories || [ ];
			var catList = '';
			for ( i = 0; i < catArray.length; i++ ) {
				if ( catList.length > 0 )
					catList += ', ';
				catList += catArray[i].name;
			}
			value = catList
		}
		else if ( typeof ( value ) != 'undefined' && ( value.length || objSize( value ) > 0 ) ) {
			if ( dataStructure == 'List' || dataStructure == 'Map' ) {
				var valString = '';
				// Lists
				if ( dataStructure == 'List' ) {
					for ( var i = 0; i < value.length; i++ ) {
						valString += formatValue( value[i], dataType ) + ', ';
					}
				}
				// Maps
				else {
					for ( var propName in value ) {
						if ( value.hasOwnProperty( propName ) )
							valString += propName + ': ' + formatValue( value[propName], dataType ) + ', ';
					}
				}
				// Remove the last comma
				if ( valString.length )
					value = valString.substr( 0, valString.length - 2 );
				else
					value = '';
			}
			else {
				value = formatValue( value, dataType );
			}
		}

		jQuery( '#media-' + name ).html( value || '' )
		jQuery( '#theplatform_upload_' + fullName.replace( '$', "\\$" ) ).val( value || '' )
	} )
}

function formatValue( value, dataType ) {
	switch ( dataType ) {
		case 'DateTime':
			value = new Date( value );
			break;
		case 'Duration':
			value = secondsToDuration( value );
			break;
		case 'Link':
			value = '<a href="' + value.href + '" target="_blank">' + value.title + '</a>';
			break;
	}
	return value;
}

function displayMessage( msg ) {
	jQuery( '#msg' ).text( msg );
}

function isArray( o ) {
	return Object.prototype.toString.call( o ) === '[object Array]';
}

function secondsToDuration( secs ) {
	var t = new Date( 1970, 0, 1 );
	t.setSeconds( secs );
	var s = t.toTimeString().substr( 0, 8 );
	if ( secs > 86399 )
		s = Math.floor( ( t - Date.parse( "1/1/70" ) ) / 3600000 ) + s.substr( 2 );
	return s;
}

var objSize = function( obj ) {
	var size = 0, key;
	for ( key in obj ) {
		if ( obj.hasOwnProperty( key ) )
			size++;
	}
	return size;
};