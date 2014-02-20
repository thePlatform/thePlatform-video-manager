var ajaxurl = localscript.ajaxurl;

var mpxHelper = {
    feedFields:{
        fields: 'guid,title,description,categories,provider,:id,:additionalCategories,keywords,:source,:videoSource,:embargoes,pubDate,added,updated,defaultThumbnailUrl,content,expirationDate,thumbnails',
        fileFields: 'releases,isDefault,contentType,url,format',
        releaseFields: 'url',
        range: '-50'
    },
    IDM_DS:'https://identity.auth.theplatform.com/idm',
    MDS_DS:'http://data.media.theplatform.com/media',

    getFeed: function(range, callback){

        var data = {
            _wpnonce: theplatform.tp_nonce,
            action: 'get_videos',
            range: range,
            query: localStorage.queryString,
            fields: localStorage.fields,
            myContent: jQuery('#my-content-cb').prop('checked')
        };
    
        jQuery.post(ajaxurl, data, function(resp){
            resp = JSON.parse(resp);
            if (resp.isException)
                displayMessage(resp.description);
            else{
               callback(resp);
            }
        });
    },    

    buildFeedQuery: function (feed,data){

        var queryParams = '';
        if (data.category)
            queryParams = queryParams.appendParams({byCategories: data.category});

        if (data.search){
            queryParams = queryParams.appendParams({q: encodeURIComponent(data.search)});
            data.sort = ''; //Workaround because solr hates sorts.
        }

        if (data.sort){
            var sortValue = data.sort + (data.desc ? '|desc' : '');
            queryParams = queryParams.appendParams({sort: sortValue});
        }
    
        if (data.selectedGuids)
            queryParams = queryParams.appendParams({byGuid: data.selectedGuids});

        if (queryParams.length > 1)
            return feed + queryParams;

        return feed;
    },

    getCategoryList: function (feed,callback){        
        var data = {
            _wpnonce: theplatform.tp_nonce,
            action: 'get_categories',
            sort: 'order',
            fields: 'title'                        
        };
    
        jQuery.post(ajaxurl, data,            
            function(resp){
                callback(JSON.parse(resp));
            });
    },

    //Retrieve parameters from the original request.
    getParameters: function (str) {
        var searchString ='';
        if (str && str.length > 0){
            if (str.indexOf('?') < 0 )
                return {};
            else
                searchString = str.substring(str.indexOf('?') + 1);
        }else
            searchString = window.location.search.substring(1);

        var params = searchString.split("&")
        ,   hash = {};

        if (searchString == "") return {};
        for (var i = 0; i < params.length; i++) {
            var val = params[i].split("=");
            hash[decodeURIComponent(val[0])] = decodeURIComponent(val[1]);
        }
        return hash;
    },

    parseParameters: function (str){
        var params = str.split("&")
        ,   hash = {};

        if (str == "") return {};
        for (var i = 0; i < params.length; i++) {
            var val = params[i].split("=");
            hash[decodeURIComponent(val[0])] = decodeURIComponent(val[1]);
        }
        return hash;
    },

    //Get a list of release URls
    extractVideoUrlfromFeed: function (media){
        var res = [];

        if (media.entries)
            media = media['entries'].shift(); //We always only grab the first media in the list THIS SHOULD BE THE ONLY MEDIA.

        if (media && media.content)
            media = media.content;
        else
            return res;

        for (var contentIdx  in media){
            var content = media[contentIdx];
            if (content.contentType == "video" && content.format == "MPEG4" && content.releases) {
                for (var releaseIndex in content.releases) {
                    if (content.releases[releaseIndex].delivery == "streaming")
                        res.push(content.releases[releaseIndex].pid);    
                }
                
            }
        }

        return res;
    },

    copyMessage: function (str){
        if (str.indexOf('Mac') > -1)
            return "Press \u2318-C to copy";

        if (str.indexOf('Win') > -1)
            return "Press CTRL-C to copy";

        return "User your Copy shortcut now.";
    },

    //Parse an array into a put-able get
    parseArray: function (name,ary){
        var ret = {};

        //Handle empty array.
        if (ary.length < 1)
            ret['_'+name+'[]']='';

        for (var i in ary){
            if (ret.length > 0)	ret += '&';

            if (typeof(ary[i]) === "object")
                jQuery.extend(ret ,mpxHelper.parseMap(name+'['+i+']', ary[i]));
            else
                ret['_'+name+'['+i+']'] =  ary[i];
        }
        return ret;
    },

    //Parse a hashmap into a put-able get
    parseMap: function (name, map){
        var ret = {};

        //Handle empty object
        if (jQuery.isEmptyObject(map))
            ret['_'+name+'{}']='';

        for (key in map){
            if (ret.length > 0) ret += '&';

            if (jQuery.isArray(map[key]))
                jQuery.extend(ret, mpxHelper.parseArray(name+'{'+key+'}', map[key]));
            else
                ret['_'+name+'{'+key+'}'] =  map[key];
        }
        return ret;
    },
    //Get the release url from the default thumbnails
    getDefaultThumbRelease: function(thumbnails){
        for (var i=0; i< thumbnails.length; i++){
            var thumb = thumbnails[i];
            if (thumb.isDefault && thumb.releases.length)
                return thumb.releases.shift().url;
        }
        return '';
    }
};

//Make my life easier by prototyping this into the string.
String.prototype.appendParams = function (params){
    var updatedString = this;
    for (var key in params){
        if (updatedString.indexOf(key+'=') > -1)
            continue;

        // if (updatedString.indexOf('?') > -1)
            updatedString += '&'+key+'='+params[key];
        // else
        //     updatedString += '?'+key+'='+params[key];
    }
    return updatedString;
};