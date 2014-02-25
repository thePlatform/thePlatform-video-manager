jQuery(document).ready(function () {

    var container = window.parent.document.getElementById('tp-container')
    if (container)
        container.style.height = window.parent.innerHeight;

    $pdk.bind("player");
    $pdk.controller.addEventListener('OnPlayerLoaded', function() {
        //Select the first one on the page.
        setTimeout(function() {
            if (jQuery('#media-list').children().length < 2) 
                jQuery('.media', '#media-list').click();
        }, 500)
        
    })
    jQuery('#load-overlay').hide();
    //Parse params and basic setup.
    var queryParams = mpxHelper.getParameters();
    localStorage.baseMediaUrl = ''
    localStorage.provider = queryParams.provider || '';
    localStorage.selectedCategory = '';
    localStorage.feedEndRange = 0;
    localStorage.queryString = ''

    mpxHelper.getCategoryList(localStorage.baseMediaUrl, buildCategoryAccordion);

    jQuery('#list-categories').on('mouseover', function () {
        jQuery('body')[0].style.overflowY = 'none';
    });
    jQuery('#list-categories').on('mouseout', function () {
        jQuery('body')[0].style.overflowY = 'auto';
    });

    //Set search button to refresh when clicked.
    jQuery('#btn-feed-preview').click(refreshView);

    //Turn on infinite scrolling.
    jQuery('#media-list').infiniteScroll({
        threshold: 100,
        onEnd: function () {
            //No more results
        },
        onBottom: function (callback) {
            jQuery('#load-overlay').show(); // show loading before we call getFeed
            var theRange = parseInt(localStorage.feedEndRange);
            theRange = (theRange + 1) + '-' + (theRange + 20);
            mpxHelper.getFeed(theRange, function (resp) {
                if (resp['isException']) {
                    jQuery('#load-overlay').hide();
                    //what do we do on error?
                }

                localStorage.feedResultCount = resp['totalResults'];
                localStorage.feedStartRange = resp['startIndex'];
                localStorage.feedEndRange = 0;
                if (resp['entryCount'] > 0) localStorage.feedEndRange = resp['startIndex'] + resp['entryCount'] - 1;

                var entries = resp['entries'];
                for (var i = 0; i < entries.length; i++)
                    addMediaObject(entries[i]);

                jQuery('#load-overlay').hide();
                Holder.run();
                callback(parseInt(localStorage.feedEndRange) < parseInt(localStorage.feedResultCount)); //True if there are still more results.
            });
        }
    });

    jQuery('#text-feedurl').tooltip({
        title: mpxHelper.copyMessage(navigator.platform),
        trigger: 'mouseup',
        placement: 'bottom',
        delay: {
            hide: 200
        }
    });

    jQuery('#text-feedurl').blur(function () {
        jQuery(this).val(decodeURIComponent(jQuery(this).data('feed')));
    });

    jQuery('#text-feedurl').mouseup(function () {
        jQuery(this).select();
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


    jQuery('input:checkbox', '#my-content').click(refreshView);

    jQuery('#selectpick-sort').on('change', refreshView);

    jQuery('#input-search').keyup(function (event) {
        if (event.keyCode == 13) refreshView();
    });

    //Set side sections to appropriately be affixed via bootstrap
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

//Hide popovers when mouse off.
jQuery(document).on('blur', '.media > .popover', function () {
    var media = jQuery(this).parent();
    jQuery('.btn', media).popover('toggle');
});



jQuery(document).on('mouseup', '.embedIframe', function () {
    jQuery(this).select();
});

//Select guids by checking their box.
jQuery(document).on('change', '.media > [type="checkbox"]', function () {
    var feedUrl = mpxHelper.buildFeedQuery(localStorage.baseMediaUrl, {
        category: localStorage.selectedCategory,
        selectedGuids: getSelectedGuids()
    });

});

//Set color and release into player when clicking on media.
jQuery(document).on('click', '.media', function () {
    updateContentPane(jQuery(this).data('media'));
    jQuery('.media').css('background-color', '');
    jQuery(this).css('background-color', '#D8E8FF');
    jQuery(this).data('bgc', '#D8E8FF');
    localStorage.currentRelease = jQuery(this).data('release');
    $pdk.controller.resetPlayer();
    $pdk.controller.loadReleaseURL("http://link.theplatform.com/s/lkKgNC/" + localStorage.currentRelease,true);
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

//Generic refresh the view function


function refreshView() {
    var $mediaList = jQuery('#media-list');
    //TODO: If sorting clear search?
    var queryObject = {
        search: jQuery('#input-search').val(),
        category: localStorage.selectedCategory,
        sort: getSort(),
        desc: jQuery('#sort-desc').data('sort'),
        myContent: jQuery('#my-content-cb').prop('checked'),
        selectedGuids: getSelectedGuids()
    };

    localStorage.queryParams = queryObject
    var newFeed = mpxHelper.buildFeedQuery(localStorage.baseMediaUrl, queryObject);

    delete queryObject.selectedGuids;
    localStorage.queryString = mpxHelper.buildFeedQuery(localStorage.baseMediaUrl, queryObject);



    displayMessage('');

    localStorage.feedEndRange = 0;
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

    jQuery('.cat-list-selector', '#list-categories').click(function () {
        localStorage.selectedCategory = jQuery(this).text();
        if (localStorage.selectedCategory == "All Videos") localStorage.selectedCategory = '';
        jQuery('.cat-list-selector', '#list-categories').each(function (idx, item) {
            var $item = jQuery(item);

            if ((localStorage.selectedCategory == $item.text()) || (localStorage.selectedCategory == '' && $item.text() == 'All Videos')) $item.css('background-color', '#D8E8FF');
            else jQuery(item).css('background-color', '');
        });
        jQuery('#input-search').val(''); //Clear the searching when we choose a category        

        refreshView();
    });
}

function getSelectedGuids() {
    var mediaList = jQuery('#media-list').children();
    var guids = '';
    for (var i = 0; i < mediaList.length; i++)
    if (jQuery('input', mediaList[i]).is(':checked')) {
        if (guids.length > 0) guids += '|';

        guids += jQuery(mediaList[i]).data('guid');
    }
    localStorage.selectedGuids = guids;
    return guids;
}

function buildFeedPreview(data) {
    var entries = data['entries'];
    jQuery('#load-overlay').hide(); // Hide the loading overlay
    //Reset the media list.
    jQuery('#media-list').empty();

    //Add each media to the list.
    for (var idx in entries)
    addMediaObject(entries[idx]);
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
        newMedia += '<button class="btn btn-xs media-embed pull-right" data-toggle="tooltip" data-placement="bottom" title="Embed this Media"><></button>';
    newMedia += '<div class="media-body">' + '<div id="head"><strong class="media-heading"></strong></div>' + '<div id="source"></div>' + '<div id="desc"></div>' + '</div>' + '</div>';

    newMedia = jQuery(newMedia);

    jQuery('#head > strong', newMedia).text(media.title);    
    if (media.description) {
        if (media.description.length > 300)
            media.description = media.description.substring(0,297) + '...'
        jQuery('#desc', newMedia).text(media.description);
    }

    //TBD: Should there be fallback?
    //There seems to be a max depth when storing data on media, so this injects the thumb release to a parent for easily find and display later.
    media['defaultThumbRelease'] = mpxHelper.getDefaultThumbRelease(media.thumbnails);

    newMedia.data('guid', media.guid);
    newMedia.data('media', media);
    var previewUrl = mpxHelper.extractVideoUrlfromFeed(media);
    if (previewUrl.length == 0 && localStorage.isEmbed == "1")
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

    jQuery('#media-list').append(newMedia);

    

}

//Hide all but the exception Id


function hideAllMediaPopover(excId) {
    var allMedia = jQuery('.media');
    for (var i = 0; i < allMedia.length; i++) {
        if (jQuery(allMedia[i]).attr('id') == excId) continue;

        jQuery('.btn', allMedia[i]).popover('hide');
    }
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
    })  
}

function displayMessage(msg) {
    jQuery('#msg').text(msg);
}

function setFieldsFromQuery(query) {
    var displayMap = mpxHelper.parseParameters(query);

    if (displayMap.sort) setSort(displayMap.sort.split('|').shift());

    if (displayMap.q) //Search
    jQuery('#input-search').val(displayMap.q);
    else jQuery('#input-search').val('');

    jQuery('input:checkbox', '#my-content').prop('checked', !! (displayMap.byCustomValue));
}