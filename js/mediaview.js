jQuery(document).ready(function(){
        //$pdk.bind("player");
        jQuery('#load-overlay').hide();
        var queryParams = getParameters();
        localStorage.baseFeedUrl = 'http://feed.theplatform.com/f/van-dev/van-dev-player';
        localStorage.token = <?php echo '"' . $tp_api->mpx_signin() . '"'; ?>;
        localStorage.provider = queryParams.provider;
        localStorage.account = <?php echo '"' . $preferences['mpx_account_id'] . '"'; ?>;
        localStorage.feedEndRange = 0;

        getCategoryList(localStorage.baseFeedUrl,buildCategoryAccordion);

        //Initial feed call
        setFeedUrl(localStorage.baseFeedUrl,localStorage.baseFeedUrl.appendParams({ sort: getSort() }));

        if (localStorage.token != 'undefined' && localStorage.account != 'undefined')
            getBookmarks(displayBookmarks);
        else jQuery('#btn-bookmark').prop('disabled',true);

        jQuery('#list-categories').on('mouseover',function(){
            jQuery('body')[0].style.overflowY = 'none';
        });
        jQuery('#list-categories').on('mouseout',function(){
            jQuery('body')[0].style.overflowY = 'auto';
        });

        jQuery('#btn-feed-preview').click(function(){
            refreshView();
        });

        //Turn on infinite scrolling.
        jQuery('#media-list').infiniteScroll({
            threshold: 100,
            onEnd: function() {
                //No more results
            },
            onBottom: function(callback) {
                jQuery('#load-overlay').show(); // show loading before we call getFeed
                var feed = jQuery('#text-feedurl').data('feed');
                var theRange = parseInt(localStorage.feedEndRange);
                theRange = theRange +'-'+(theRange+20);
                getFeed(feed.appendParams({ range: theRange  }),function(resp){
                    if (resp['isException']){
                        jQuery('#load-overlay').hide();
                        //what do we do on error?
                    }

                    localStorage.feedResultCount = resp['totalResults'];
                    localStorage.feedStartRange = resp['startIndex'];
                    localStorage.feedEndRange = 0;
                    if (resp['entryCount'] > 0)
                        localStorage.feedEndRange = resp['startIndex'] + resp['entryCount'] - 1;

                    var entries = resp['entries'];
                    for (var i = 0; i < entries.length; i++ )
                        addMediaObject(entries[i]);

                    jQuery('#load-overlay').hide();
                    callback(parseInt(localStorage.feedEndRange) < parseInt(localStorage.feedResultCount)); //True if there are still more results.
                });
            }
        });

        jQuery('#text-feedurl').tooltip(
                {
                    title:copyMessage(navigator.platform),
                    trigger: 'mouseup',
                    placement: 'bottom',
                    delay: { hide: 200}
                });

        jQuery('#text-feedurl').blur(function(){
            jQuery(this).val(jQuery(this).data('feed'));
        });

        jQuery('#text-feedurl').mouseup(function(){
            jQuery(this).select();
        });

        jQuery('.scrollable').on('DOMMouseScroll mousewheel', function(ev) {
            var $this = jQuery(this),
                    scrollTop = this.scrollTop,
                    scrollHeight = this.scrollHeight,
                    height = $this.height(),
                    delta = (ev.type == 'DOMMouseScroll' ?
                            ev.originalEvent.detail * -40 :
                            ev.originalEvent.wheelDelta),
                    up = delta > 0;

            var prevent = function() {
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

        jQuery('#feed-affix').affix({
            offset: {
                top: 0
            }
        });

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

        jQuery('#btn-feed-reset').click(function(){
            getBookmarkFeed(localStorage.baseFeedUrl);
        });

        jQuery('#btn-add-bm').popover({
            placement: 'bottom',
            html: true,
            content: '<div style="width: 210px;"><input id="bm-new-title" type="text" maxlength="15" placeholder="Bookmark Title" style="margin-right:5px;"><button id="btn-save-bm" class="btn btn-sm btn-primary">Save</button></div>'
        });

        jQuery('input:checkbox','#my-content').click(function(){
            refreshView();
        });

        jQuery('#selectpick-sort').on('change',function(){
            refreshView();
        });

        jQuery('#input-search').keyup(function(event){
           if (event.keyCode == 13)
            refreshView();
        });

    });

    //Need this event to handle saving bookmarks because the button doesn't exist until the popup is created.
    jQuery(document).on('click','#btn-save-bm',function(){
        var bookmarkFeed = jQuery('#text-feedurl').data('feed');
        var bmTitle = jQuery('#bm-new-title').val();

        if (bookmarkFeed && bmTitle)
            saveBookmark(bmTitle,bookmarkFeed,function(){
                getBookmarks(displayBookmarks);
                jQuery('#bm-new-title').val('');
                jQuery('#btn-add-bm').popover('hide');
            });
    });


    jQuery(document).on('change','.media > [type="checkbox"]',function(){
        var feedUrl = buildFeedQuery(localStorage.baseFeedUrl,{
            category: localStorage.selectedCategory,
            selectedGuids: getSelectedGuids()
        });
        setFeedUrl(feedUrl,'');
    });

    jQuery(document).on('click','.media',function(){
        updateContentPane(jQuery(this).data('media'));
        localStorage.currentRelease = jQuery(this).data('release');
        //$pdk.controller.loadReleaseURL(localStorage.currentRelease);
        //$pdk.controller.setReleaseURL('');
    });

    jQuery(document).on('mouseover','.media',function(){
        $this = jQuery(this);
        $this.attr('style','background-color: #D8E8FF;');
    });

    jQuery(document).on('mouseleave','.media',function(){
        $this = jQuery(this);
        $this.attr('style','');
    });

    function refreshView(){
        var $mediaList = jQuery('#media-list');
        //TODO: If sorting clear search?
        var queryObject = {
            search: jQuery('#input-search').val(),
            category: localStorage.selectedCategory,
            sort: getSort(),
            desc: jQuery('#sort-desc').data('sort'),
            myContent: jQuery('input:checkbox','#my-content').prop('checked'),
            selectedGuids: getSelectedGuids()
        };

        var newFeed = buildFeedQuery(localStorage.baseFeedUrl,queryObject);

        delete queryObject.selectedGuids;
        var dataFeed = buildFeedQuery(localStorage.baseFeedUrl,queryObject);

        setFeedUrl(newFeed,dataFeed);

        displayMessage('');

        localStorage.feedEndRange = 0;
        $mediaList.empty();
        $mediaList.infiniteScroll('reset');
    }

    //TODO: Should make bookmark field name a variable.
    function displayBookmarks(bmResp){
        var $bmPanelList = jQuery('#bm-list-panel');
        var bmEntry = bmResp['entries'][0]; //TODO: This is dirty.
        localStorage.bookmarkNmsp = JSON.stringify({ '$xmlns' : bmResp['$xmlns']});
        localStorage.bookmarkId = bmEntry.id;


        var bmObject;
        for (var key in bmEntry)
            if (key.indexOf('vanDashboardBookmarks') > -1){
                bmObject = bmEntry[key];
                var storageObject = {};
                    storageObject[key] = bmObject;
                localStorage.bookMarks = JSON.stringify(storageObject);
                break;
            }

        $bmPanelList.empty();
        var bmTable = jQuery('<table id="bmtable"></table>');
        for (key in bmObject)
            bmTable.append('<tr><td><a class="bm-launch" href="#" bm-data="'+bmObject[key]+'" >'+key+'</a></td><td><button value="'+key+'" class="btn btn-danger btn-xs bm-delete"><span class="glyphicon glyphicon-remove"></span></button></td></tr>');
        $bmPanelList.append(bmTable);

        jQuery('.bm-delete', bmTable).click(function(){
            var $this = jQuery(this);
            deleteBookmark($this.val(),function(){
                getBookmarks(displayBookmarks);
            });
        });

        jQuery('.bm-launch',bmTable).click(function(){
            var $this = jQuery(this);
            getBookmarkFeed($this.attr('bm-data'));
        });
    }

    function setFeedUrl(url,dataurl){
        var $feedUrl = jQuery('#text-feedurl');
        $feedUrl.val(url);
        if (dataurl && dataurl.length > 0)
            $feedUrl.data('feed',dataurl);
        else
            $feedUrl.data('feed',url);
    }

    function getBookmarkFeed(feed){
        var baseUrl = feed.split('?').shift();
        var query = feed.split('?').pop();
        var $mediaList = jQuery('#media-list');
        localStorage.baseFeedUrl = baseUrl;

        displayMessage('');

        setFieldsFromQuery(query);
        setFeedUrl(feed);

        localStorage.feedEndRange = 0;
        $mediaList.empty();
        $mediaList.infiniteScroll('reset');
    }

    function getSort(){
    	var sortMethod = jQuery('option:selected','#selectpick-sort').val();
    	    	
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

    function setSort(val){
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

    function getSearch(){
        return jQuery('#input-search').val();
    }

    function buildCategoryAccordion(resp){
        jQuery('.cat-list-selector','#list-categories').click(function(){
            localStorage.selectedCategory = jQuery(this).text();
            jQuery.each(jQuery('.cat-list-selector.active'),function(index, value) {
            	jQuery(value).removeClass('active');
            })
            jQuery(this).addClass('active');
            if (localStorage.selectedCategory == "All Videos")
                localStorage.selectedCategory = '';
            jQuery('#input-search').val(''); //Clear the searching when we choose a category
            refreshView();
        });
    }

    function getSelectedGuids(){
        var mediaList = jQuery('#media-list').children();
        var guids = '';
        for (var i =0; i < mediaList.length; i++)
            if ( jQuery('input',mediaList[i]).is(':checked') ){
                if (guids.length > 0) guids += '|';

                guids += jQuery(mediaList[i]).data('guid');
            }
        localStorage.selectedGuids = guids;
        return guids;
    }

    function buildFeedPreview(data){
        var entries = data['entries'];
        jQuery('#load-overlay').hide(); // Hide the loading overlay
        //Reset the media list.
        jQuery('#media-list').empty();

        //Add each media to the list.
        for (var idx in entries)
            addMediaObject(entries[idx]);
    }

    function addMediaObject(media){
       var newMedia = '<div class="media"><input type="checkbox" class="pull-left media-cb">'+
                '<img class="media-object pull-left thumb-img" data-src="holder.js/128x72" alt="128x72" src="'+media.defaultThumbnailUrl+'">'+
                '<button class="btn btn-xs media-embed pull-right"><></button>'+
                '<div class="media-body">'+
                '<strong class="media-heading">'+media.title+'</strong>'+
                '<br/>'+(media['cnn-video$source'] || media['cnn-video$videoSource'])+
                '<br/>'+media.description +
                //'<br/><small>added: '+new Date(media.added).toLocaleString() + '</small>'+
                '</div>'+
                '</div>';

        newMedia = jQuery(newMedia);
        newMedia.data('guid',media.guid);
        newMedia.data('media',media);
        var previewUrl = extractVideoUrlfromFeed(media);
        newMedia.data('release',previewUrl[0].appendParams({mbr: true}));
        //newMedia.data('release',media['content'][0]['releases'][0]['url']);

        if (localStorage.selectedGuids && $.inArray(media.guid, localStorage.selectedGuids.split('|')) > -1)
            jQuery('input:checkbox',newMedia).prop('checked',true);

        //TODO: update this with actual embed?
        jQuery('.media-embed',newMedia).popover({
            html: true,
            title: 'Embed Tag',
            content: 'Something To copy',
            placement: 'left'
         });

        jQuery('#media-list').append(newMedia);

    }

    function updateContentPane(mediaItem){
        var i, catArray, catList;
        jQuery('#media-title').text(mediaItem.title);
        jQuery('#media-description').text(mediaItem.description);

        if (mediaItem.categories){
            catArray = mediaItem.categories;
            catList = '';
            for (i = 0; i < catArray.length; i++){
                if (catList.length > 0) catList += ', ';
                catList += catArray[i].name;
            }

            jQuery('#media-categories').text(catList);
        }

        //TODO: Figure out how to store namespacing?
        if(mediaItem['cnn-video$additionalCategories']){
            catArray = mediaItem['cnn-video$additionalCategories'];
            catList = '';
            for (i = 0; i < catArray.length; i++){
                if (catList.length > 0) catList += ', ';
                catList += catArray[i];
            }
            jQuery('#media-addl-categories').text(catList);
        }

        if (mediaItem['cnn-video$source'] || mediaItem['cnn-video$videoSource'])
            jQuery('#media-provider').text(mediaItem['cnn-video$source'] || mediaItem['cnn-video$videoSource']);

        if (mediaItem['cnn-video$embargoes'])
        jQuery('#media-embargoes').text(mediaItem['cnn-video$embargoes']);

        if (mediaItem.keywords)
            jQuery('#media-keywords').text(mediaItem.keywords.split(',').join(', '));

        if (mediaItem.added > 0)
            jQuery('#media-added').text(new Date(mediaItem.added).toLocaleString());

        if(mediaItem.updated > 0)
            jQuery('#media-updated').text(new Date(mediaItem.updated).toLocaleString());
            
        if(mediaItem.expirationDate > 0)
            jQuery('#media-expiration').text(new Date(mediaItem.expirationDate).toLocaleString());

    }

    function displayMessage(msg){
        jQuery('#msg').text(msg);
    }

    function setFieldsFromQuery(query){
        var displayMap = parseParameters(query);

        if (displayMap.sort)
            setSort(displayMap.sort.split('|').shift());

        if (displayMap.q) //Search
            jQuery('#input-search').val(displayMap.q);
        else
            jQuery('#input-search').val('');

        jQuery('input:checkbox','#my-content').prop('checked',!!(displayMap.byProvider));
    }