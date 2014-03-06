jQuery(document).ready(function () {
    //Parse params and basic setup.
    var queryParams = mpxHelper.getParameters();
    tpHelper.selectedCategory = '';
    tpHelper.feedEndRange = 0;
    tpHelper.queryString = ''
    $pdk.bind("player");    
    jQuery('#load-overlay').hide();
    mpxHelper.getCategoryList(buildCategoryAccordion);

    /**
     * Set up the infinite scrolling media list
     */
    jQuery('#media-list').infiniteScroll({
        threshold: 100,
        onEnd: function () {
            //No more results
        },
        onBottom: function (callback) {
            jQuery('#load-overlay').show(); // show loading before we call getVideos
            var theRange = parseInt(tpHelper.feedEndRange);
            theRange = (theRange + 1) + '-' + (theRange + 20);
            mpxHelper.getVideos(theRange, function (resp) {
                if (resp['isException']) {
                    jQuery('#load-overlay').hide();
                    //what do we do on error?
                }

                tpHelper.feedResultCount = resp['totalResults'];
                tpHelper.feedStartRange = resp['startIndex'];
                tpHelper.feedEndRange = 0;
                if (resp['entryCount'] > 0) tpHelper.feedEndRange = resp['startIndex'] + resp['entryCount'] - 1;

                var entries = resp['entries'];
                for (var i = 0; i < entries.length; i++)
                    addMediaObject(entries[i]);

                jQuery('#load-overlay').hide();
                Holder.run();
                callback(parseInt(tpHelper.feedEndRange) < parseInt(tpHelper.feedResultCount)); //True if there are still more results.
            });
        }
    });

    //This is for setting a section "scrollable" so it will scroll without scrolling everything else.
    jQuery('.scrollable').on('DOMMouseScroll mousewheel', function (ev) {
        var $this = jQuery(this),
            scrollTop = this.scrollTop,
            scrollHeight = this.scrollHeight,
            height = $this.height(),
            delta = (ev.type == 'DOMMouseScroll' ? ev.originalEvent.detail * -40 : ev.originalEvent.wheelDelta),
            up = delta > 0;

        var prevent = function () {
                ev.stopPropagation();
                ev.preventDefault();
                ev.returnValue = false;
                return false;
            };

        if (!up && -delta > scrollHeight - height - scrollTop) {
            // Scrolling down, but this will take us past the bottom.
            $this.scrollTop(scrollHeight);
            return prevent();
        } else if (up && delta > scrollTop) {
            // Scrolling up, but this will take us past the top.
            $this.scrollTop(0);
            return prevent();
        }
    });

    /**
     * Search form event handlers
     */
    jQuery('#btn-feed-preview').click(refreshView);

    jQuery('input:checkbox', '#my-content').click(refreshView);

    jQuery('#selectpick-sort').on('change', refreshView);

    jQuery('#input-search').keyup(function (event) {
        if (event.keyCode == 13) refreshView();
    });

    /**
     * Look and feel event handlers
     */    
    jQuery(document).on('click', '.media', function () {
        updateContentPane(jQuery(this).data('media'));    
        jQuery('.media').css('background-color', '');
        jQuery(this).css('background-color', '#D8E8FF');
        jQuery(this).data('bgc', '#D8E8FF');
        tpHelper.currentRelease = jQuery(this).data('release');    
        $pdk.controller.resetPlayer();
        if (tpHelper.currentRelease !== "undefined") {
            jQuery('#modal-player-placeholder').hide();        
            $pdk.controller.loadReleaseURL("http://link.theplatform.com/s/" + tpHelper.accountPid + "/" + tpHelper.currentRelease,true);
        }
        else {
            jQuery('#modal-player-placeholder').show()        
        }
    });

    //Update background color when hovering over media
    jQuery(document).on('mouseenter', '.media', function () {
        $this = jQuery(this);
        $this.data('bgc', $this.css('background-color'));
        $this.css('background-color', '#f5f5f5');
    });

    //Update background color when hovering off media
    jQuery(document).on('mouseleave', '.media', function () {
        $this = jQuery(this);
        var oldbgc = $this.data('bgc');

        if (oldbgc) $this.css('background-color', oldbgc);
        else $this.css('background-color', '');

    });

    /**
     * Set the page layout 
     */
    var container = window.parent.document.getElementById('tp-container')
    if (container)
        container.style.height = window.parent.innerHeight;

    jQuery('#info-affix').affix({
        offset: {
            top: 0
        }
    });

    jQuery('#filter-affix').affix({
        offset: {
            top: 0
        }
    });

});

/**
 * Refresh the infinite scrolling media list based on the selected category and search options
 * @return {void} 
 */
function refreshView() {
    var $mediaList = jQuery('#media-list');
    //TODO: If sorting clear search?
    var queryObject = {
        search: jQuery('#input-search').val(),
        category: tpHelper.selectedCategory,
        sort: getSort(),
        desc: jQuery('#sort-desc').data('sort'),
        myContent: jQuery('#my-content-cb').prop('checked')        
    };

    tpHelper.queryParams = queryObject
    var newFeed = mpxHelper.buildMediaQuery(queryObject);

    delete queryObject.selectedGuids;
    tpHelper.queryString = mpxHelper.buildMediaQuery(queryObject);

    displayMessage('');

    tpHelper.feedEndRange = 0;
    $mediaList.empty();
    $mediaList.infiniteScroll('reset');
}

function getSort() {
    var sortMethod = jQuery('option:selected', '#selectpick-sort').val();

    switch (sortMethod) {
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

function setSort(val) {
    var $sortBox = jQuery('#selectpick-sort');
    switch (val.toLowerCase()) {
    case "updated":
        $sortBox.val('Updated');
        break;
    case "title":
        $sortBox.val('Title');
        break;
    default:
        $sortBox.val('Added');
    }
}

function getSearch() {
    return jQuery('#input-search').val();
}

function buildCategoryAccordion(resp) {
    var entries = resp['entries'];
    for (var idx in entries) {
        var entryTitle = entries[idx]['title'];
        jQuery('#list-categories').append('<a href="#" class="list-group-item cat-list-selector">' + entryTitle + '</a>');
    }

    jQuery('#list-categories').on('mouseover', function () {
        jQuery('body')[0].style.overflowY = 'none';
    });
    jQuery('#list-categories').on('mouseout', function () {
        jQuery('body')[0].style.overflowY = 'auto';
    });

    jQuery('.cat-list-selector', '#list-categories').click(function () {
        tpHelper.selectedCategory = jQuery(this).text();
        if (tpHelper.selectedCategory == "All Videos") tpHelper.selectedCategory = '';
        jQuery('.cat-list-selector', '#list-categories').each(function (idx, item) {
            var $item = jQuery(item);

            if ((tpHelper.selectedCategory == $item.text()) || (tpHelper.selectedCategory == '' && $item.text() == 'All Videos')) $item.css('background-color', '#D8E8FF');
            else jQuery(item).css('background-color', '');
        });
        jQuery('#input-search').val(''); //Clear the searching when we choose a category        

        refreshView();
    });
}

function addMediaObject(media) {
    //Prevent adding the same media twice.
    // This cannot be filtered out earlier because it only really occurs when
    // Something just gets added.
    if (document.getElementById(media.guid) != null) //Can't use jquery because of poor guid format convention.
    return;
    
    var placeHolder = "";
    if (media.defaultThumbnailUrl === "")
        placeHolder = "holder.js/128x72/text:No Thumbnail";

    var newMedia = '<div class="media" id="' + media.guid + '"><img class="media-object pull-left thumb-img" data-src="' + placeHolder + '" alt="128x72" src="' + media.defaultThumbnailUrl + '">'
    if (location.search.indexOf('&embed=true') != -1)
        newMedia += '<button class="btn btn-xs media-embed pull-right" data-toggle="tooltip" data-placement="bottom" title="Embed this Media"><div class="dashicons dashicons-migrate"></div></button>';
    if (jQuery('#tp-edit-dialog').length !== 0)
        newMedia += '<button class="btn btn-xs media-edit pull-right" data-toggle="tooltip" data-placement="bottom" title="Edit this Media"><div class="dashicons dashicons-edit"></div></button>';
    newMedia += '<div class="media-body">' + '<div id="head"><strong class="media-heading"></strong></div>' + '<div id="source"></div>' + '<div id="desc"></div>' + '</div>' + '</div>';

    newMedia = jQuery(newMedia);

    jQuery('#head > strong', newMedia).text(media.title);    
    if (media.description) {
        if (media.description.length > 300)
            media.description = media.description.substring(0,297) + '...'
        jQuery('#desc', newMedia).text(media.description);
    }    
    
    newMedia.data('guid', media.guid);
    newMedia.data('media', media);
    newMedia.data('id', media.id)
    var previewUrl = mpxHelper.extractVideoUrlfromMedia(media);
    if (previewUrl.length == 0 && tpHelper.isEmbed == "1")
        return; 
    
    newMedia.data('release', previewUrl.pop()) 
    jQuery('.media-embed', newMedia).hover(function() {
        jQuery(this).tooltip();
    }, function() {
        jQuery(this).attr('title', 'Embed this Media').tooltip('fixTitle');
    });

    jQuery('.media-embed', newMedia).click(function() {

        var player = jQuery('#selectpick-player').val();
        jQuery(this).attr('title', 'Media Embedded').tooltip('fixTitle').tooltip('show');

        if (newMedia != '') {
            var shortcode = '[theplatform media="' + newMedia.data('release') + '" player="' + player + '"]';
        
            var win = window.dialogArguments || opener || parent || top;
            var isVisual = (typeof win.tinyMCE != "undefined") && win.tinyMCE.activeEditor && !win.tinyMCE.activeEditor.isHidden(); 
            if (isVisual) {
                win.tinyMCE.activeEditor.execCommand('mceInsertContent', false, shortcode);
            } else {
                var currentContent = jQuery('#content', window.parent.document).val();
                if ( typeof currentContent == 'undefined' )
                    currentContent = '';        
                jQuery( '#content', window.parent.document ).val( currentContent + shortcode );
            }
            self.parent.tb_remove();
        }
        return false;

    });

    jQuery('.media-edit', newMedia).hover(function() {
        jQuery(this).tooltip();
    }, function() {
        jQuery(this).attr('title', 'Edit this Media').tooltip('fixTitle');
    });

    jQuery('.media-edit', newMedia).click(function() {
        jQuery(newMedia).click();
        tpHelper.mediaId = newMedia.data('id');
    
        if (newMedia != '') {
            jQuery("#tp-edit-dialog").dialog({
                    dialogClass: "wp-dialog", 
                    modal: true, 
                    resizable: true, 
                    minWidth: 800, 
                    width: 1024,
                    position: ['center',20]                   
                }).css("overflow","hidden");    
        }

        return false;
    });

    jQuery('#media-list').append(newMedia);
}

function updateContentPane(mediaItem) {
    var i, catArray, catList;

    var $fields = jQuery('.panel-body span')

    $fields.each(function(index, value) {
        var name = jQuery(value).data('name');
        var fullName = name
        var prefix = jQuery(value).data('prefix');
        if (prefix !== undefined)
            fullName = prefix + '$' + name;
        var value = mediaItem[fullName]
        if (name === 'categories') {
            var catArray = mediaItem.categories || [];
            var catList = '';
            for (i = 0; i < catArray.length; i++) {
                if (catList.length > 0) catList += ', ';
                catList += catArray[i].name;
            }
            value = catList
        }              
        jQuery('#media-' + name).text(value || '')
        jQuery('#theplatform_upload_' + fullName.replace('$', "\\$")).val(value || '')
    })  
}

function displayMessage(msg) {
    jQuery('#msg').text(msg);
}

