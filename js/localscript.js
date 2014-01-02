var feedFields = {
    fields: 'guid,title,description,categories,provider,:additionalCategories,keywords,:source,:videoSource,:embargoes,added,updated,defaultThumbnailUrl,content,expirationDate',
    fileFields: 'contentType,url',
    range: '-50'
};
var IDM_DS = 'https://identity.auth.theplatform.com/idm'
,   MDS_DS = 'http://data.media.theplatform.com/media';

function getVideos(query,range,callback){
    var data = {
        action: 'getVideos',
        _wpnonce: theplatform.tp_nonce,    
        token: localStorage.token,
        account: localStorage.account,
        range: range,
        query: query    
    };
    jQuery.post(ajaxurl, data, function(resp){
        if (resp.isException)
            displayMessage(resp.description);
        else{
           callback(JSON.parse(resp));
        }
    });
}

function getBookmarks(callback){
   // var requestUrl = MDS_DS + '/data/AccountSettings';

   //  jQuery.ajaxSetup({cache:true});
   //  jQuery.getJSON(requestUrl.appendParams({callback:'?'}),
   //      {
   //          token: localStorage.token,
   //          account: localStorage.account,
   //          fields: 'id,:',
   //          range: '-1',
   //          form: 'cjson',
   //          schema: '1.6.0',
   //          sort: 'added|desc'
   //      },function(resp){
   //          if (resp.isException)
   //              displayMessage(resp.description);
   //          else
   //              callback(resp);
   //      });

}

function saveBookmark(title,feed,callback){
    // var jsonBookmarks = JSON.parse(localStorage.bookMarks);
    // var jsonBMParent;
    // for (var key in jsonBookmarks){
    //     //if there is more than one we have issues.
    //     jsonBMParent = key;
    //     break;
    // }

    // jsonBookmarks[jsonBMParent][title] = feed;
    // localStorage.bookMarks = JSON.stringify(jsonBookmarks);

    // var updateData = JSON.parse(localStorage.bookmarkNmsp);
    // updateData['id'] = localStorage.bookmarkId;
    // jQuery.extend(updateData, JSON.parse(localStorage.bookMarks));

    // setBookmarks(updateData,callback);

}

function deleteBookmark(title,callback){
    // var jsonBookmarks = JSON.parse(localStorage.bookMarks);
    // var jsonBMParent;
    // for (var key in jsonBookmarks){
    //     //if there is more than one we have issues.
    //     jsonBMParent = key;
    //     break;
    // }

    // delete jsonBookmarks[jsonBMParent][title];

    // if (!jsonBookmarks[jsonBMParent]) //Allow us to still put an update to delete the last bookmark
    //     jsonBookmarks[jsonBMParent]  = '';

    // localStorage.bookMarks = JSON.stringify(jsonBookmarks);

    // var updateData = JSON.parse(localStorage.bookmarkNmsp);
    // updateData['id'] = localStorage.bookmarkId;
    // jQuery.extend(updateData, JSON.parse(localStorage.bookMarks));

    // setBookmarks(updateData,callback);
}

function setBookmarks(data,callback){
    // var requestUrl = MDS_DS + "/data/AccountSettings/"
    // ,   opts = {};


    // //Here we take in a media object (json) in data, and parse into a put-able get request.
    // for (var i in data){
    //     if (typeof(data[i]) == "undefined" || data[i] == "" || data[i] == null )
    //         continue;

    //     if (jQuery.isArray(data[i]))
    //         jQuery.extend(opts, parseArray(i,data[i]));
    //     else if (typeof (data[i]) === "object")
    //         jQuery.extend(opts, parseMap(i,data[i]));
    //     else
    //         opts['_'+i] = data[i];
    // }

    // //Add params for the get request
    // opts['form'] = 'cjson';
    // opts['token'] = localStorage.token;
    // opts['schema'] = '1.6.0';
    // opts['account'] = localStorage.account;
    // opts['method'] = 'put';

    // jQuery.ajaxSetup({cache:true});
    // jQuery.getJSON(requestUrl.appendParams({callback: '?'}),opts,function(resp){
    //     if (resp.isException)
    //         displayMessage(resp.description);
    //     else
    //         callback();
    //         //displayMessage('Bookmark Added!');
    // });

}

function buildVideoQuery(data){

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

    if (data.myContent &&  (localStorage.provider != 'undefined')) // There should be a better way to validate.
        queryParams = queryParams.appendParams({byProvider: encodeURIComponent(localStorage.provider)});

    if (data.selectedGuids)
        queryParams = queryParams.appendParams({byGuid: data.selectedGuids});

    return queryParams;
}

function getCategoryList(callback){
    var data = {
        action: 'getCategories',
        _wpnonce: theplatform.tp_nonce,    
        token: localStorage.token,
        account: localStorage.account        
    };

    jQuery.post(ajaxurl, data,
    function(resp){
        callback(JSON.parse(resp));
    });
}

//Retrieve parameters from the original request.
function getParameters() {
    var searchString = window.location.search.substring(1)
    ,   params = searchString.split("&")
    ,   hash = {};

    if (searchString == "") return {};
    for (var i = 0; i < params.length; i++) {
        var val = params[i].split("=");
        hash[decodeURIComponent(val[0])] = decodeURIComponent(val[1]);
    }
    return hash;
}

function parseParameters(str){
    var params = str.split("&")
    ,   hash = {};

    if (str == "") return {};
    for (var i = 0; i < params.length; i++) {
        var val = params[i].split("=");
        hash[decodeURIComponent(val[0])] = decodeURIComponent(val[1]);
    }
    return hash;
}

//Get a list of release URls
function extractVideoUrlfromFeed(media){
    var res = [];

    if (media.entries)
        media = media['entries'].shift(); //We always only grab the first media in the list THIS SHOULD BE THE ONLY MEDIA.

    if (media && media.content)
        media = media.content;
    else
        return res;

    for (var contentIdx  in media){
        var content = media[contentIdx];
        if (content.contentType == "video" && content.url)
            res.push(content.url);
    }

    return res;
}

function copyMessage(str){
    if (str.indexOf('Mac') > -1)
        return "Press \u2318-C to copy";

    if (str.indexOf('Win') > -1)
        return "Press CTRL-C to copy";

    return "User your Copy shortcut now.";
}

//Make my life easier by prototyping this into the string.
String.prototype.appendParams = function (params){
    var updatedString = this;
    for (var key in params){
        if (updatedString.indexOf(key+'=') > -1)
            continue;

        // if (updatedString.indexOf('?') > -1)
            updatedString += '&'+key+'='+params[key];
        // else
            // updatedString += '?'+key+'='+params[key];
    }
    return updatedString;
};

String.prototype.escape = function() {
    var tagsToReplace = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;'
    };
    return this.replace(/[&<>]/g, function(tag) {
        return tagsToReplace[tag] || tag;
    });
};

//Parse an array into a put-able get
function parseArray(name,ary){
    var ret = {};

    //Handle empty array.
    if (ary.length < 1)
        ret['_'+name+'[]']='';

    for (var i in ary){
        if (ret.length > 0)	ret += '&';

        if (typeof(ary[i]) === "object")
            jQuery.extend(ret ,parseMap(name+'['+i+']', ary[i]));
        else
            ret['_'+name+'['+i+']'] =  ary[i];
    }
    return ret;
}

//Parse a hashmap into a put-able get
function parseMap(name, map){
    var ret = {};

    //Handle empty object
    if (jQuery.isEmptyObject(map))
        ret['_'+name+'{}']='';

    for (key in map){
        if (ret.length > 0) ret += '&';

        if (jQuery.isArray(map[key]))
            jQuery.extend(ret, parseArray(name+'{'+key+'}', map[key]));
        else
            ret['_'+name+'{'+key+'}'] =  map[key];
    }
    return ret;
}