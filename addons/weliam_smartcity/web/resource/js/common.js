var common = {};
//获取总后台后台链接信息 一
common.sysUrl = function(routes, params = [],complete = false,siteroot=''){
    if(!routes){ console.log('请求地址不能为空');return false;}
    //获取基本路径信息
    var strs = [],url,path,c,a,m,temp,head;
    strs = routes.split("/");
    path = 'p=' + strs[0] + '&ac=' + strs[1] + '&do=' + strs[2];
    //获取基本模块参数信息
    c = common.getParams('c');
    a = common.getParams('a');
    m = common.getParams('m');
    temp = 'c='+c+'&a='+a+'&m='+m;
    //获取链接信息头部
    if(complete){
        if(siteroot){
            let http = siteroot;
            head = http+'web/index.php?';
        }else{
            let http = window.location.href.split("web");
            head = http[0]+'web/index.php?';
        }
    }else{
        head = './web/index.php?';
    }
    //拼接链接信息
    if(!c){
        if(siteroot){
            url = siteroot+'web/citysys.php'+'?'+path;
        }else{
            url = 'citysys.php'+'?'+path;
        }
    }else{
        url = head+temp+'&'+path;
    }
    if(params) {
        if(typeof(params) == 'object') {
            url += "&" + common.toQueryString(params)
        } else if(typeof(params) == 'string') {
            url += "&" + params
        }
    }
    return url;
};
//获取代理后台链接信息 一
common.webUrl = function(routes, params,complete = false,siteroot=''){
    if(!routes){ console.log('请求地址不能为空');return false;}
    var strs = [],url;
    strs = routes.split("/");
    if(complete){
        if(siteroot){
            let http = siteroot;
            url = http+'/web/cityagent.php?p=' + strs[0] + '&ac=' + strs[1] + '&do=' + strs[2];
        }else{
            let http = window.location.href.split("web");
            url = http[0]+'/web/cityagent.php?p=' + strs[0] + '&ac=' + strs[1] + '&do=' + strs[2];
        }
    }else{
        url = './web/cityagent.php?p=' + strs[0] + '&ac=' + strs[1] + '&do=' + strs[2];
    }
    if(params) {
        if(typeof(params) == 'object') {
            url += "&" + common.toQueryString(params)
        } else if(typeof(params) == 'string') {
            url += "&" + params
        }
    }
    return url;
};
//获取总后台后台链接信息 一
common.storeUrl = function(routes, params = [],complete = false){
    if(!routes){ console.log('请求地址不能为空');return false;}
    //获取基本路径信息
    var strs = [],url,path,c,a,m,temp,head;
    strs = routes.split("/");
    path = 'p=' + strs[0] + '&ac=' + strs[1] + '&do=' + strs[2];
    //获取基本模块参数信息
    // c = common.getParams('c');
    // a = common.getParams('a');
    // m = common.getParams('m');
    // temp = 'c='+c+'&a='+a+'&m='+m;
    //获取链接信息头部
    if(complete){
        let http = window.location.href.split("web");
        head = http[0]+'web/citystore.php?';
    }else{
        head = './web/citystore.php?';
    }
    //拼接链接信息
    url = head+path;
    if(params) {
        if(typeof(params) == 'object') {
            url += "&" + common.toQueryString(params)
        } else if(typeof(params) == 'string') {
            url += "&" + params
        }
    }
    return url;
};
//获取移动端链接信息 一
common.appUrl = function(routes, params,complete = false){
    var strs = [],url;
    strs = routes.split("/");
    if(complete){
        let http = window.location.href.split("web");
        url = http[0]+'app/index.php?i=' + window.sysinfo.uniacid + '&c=entry&m='+versm+'&p=' + strs[0] + '&ac=' + strs[1] + '&do=' + strs[2];
    }else{
        url = './index.php?i=' + window.sysinfo.uniacid + '&c=entry&m='+versm+'&p=' + strs[0] + '&ac=' + strs[1] + '&do=' + strs[2];
    }
    if(params) {
        if(typeof(params) == 'object') {
            url += "&" + common.toQueryString(params)
        } else if(typeof(params) == 'string') {
            url += "&" + params
        }
    }
    return url;
};
//获取链接信息 二
common.toQueryString  = function(obj) {
    var ret = [];
    for (var key in obj) {
        key = encodeURIComponent(key);
        var values = obj[key];
        if (values && values.constructor == Array) {
            var queryValues = [];
            for (var i = 0, len = values.length, value; i < len; i++) {
                value = values[i];
                queryValues.push(common.toQueryPair(key, value))
            }
            ret = concat(queryValues)
        } else {
            ret.push(common.toQueryPair(key, values))
        }
    }
    return ret.join('&')
};
//获取链接信息 三
common.toQueryPair  = function(key, value) {
    if (typeof value == 'undefined') {
        return key
    }
    return key + '=' + encodeURIComponent(value === null ? '' : String(value))
};
//复制链接信息
common.copyLink = function () {
    myrequire(['clipboard'], function (Clipboard) {
        $('.js-clip').each(function () {
            var text = $(this).data('text') || $(this).data('href') || $(this).data('url');
            const cb = new Clipboard(this, {
                text: () => text
            })
            cb.on('success', (e) => {
                console.log(e)
                tip.msgbox.suc('复制成功')
                e.clearSelection();
            })
            cb.on('error', (e) => {
                console.log(e)
                tip.msgbox.err('复制失败')
            })
        })
    })
};
common.getUrlParam = function(name) {
    var reg = new RegExp("(^|&)" + name + "=([^&]*)(&|$)"); //构造一个含有目标参数的正则表达式对象
    var r = window.location.search.substr(1).match(reg);  //匹配目标参数
    if (r != null) return unescape(r[2]); return null; //返回参数值
}
//获取url中的参数信息
common.getParams = function(name){
    var reg = new RegExp("(^|&)"+ name +"=([^&]*)(&|$)");
    var r = window.location.search.substr(1).match(reg);
    if(r!=null)return  unescape(r[2]); return null;
}
//POST请求
common.ajaxPost = function (path,params,returnType = false,urlType = true) {
    let result,url;
    //获取链接信息
    /*if(urlType == true){
        url = common.webUrl(path,'',true);
    }else if(urlType == 2){
        url = common.storeUrl(path,'',true);
    }else{
        url = common.sysUrl(path,'',true);
    }*/
    url = biz.url(path);
    $.ajax({
        type: "POST",
        url: url,
        data: params,
        dataType: "json",
        async: false,
        timeout: 180000 ,
        success: function (data) {
            if(returnType){
                result = data;
            }else{
                result = data['data'];
            }
        },
        error: function (errors) {
            console.log("请求失败");
        }
    });
    return result;
};





/**
 * vue公共方法
 */
var commonVue = new Vue({
    el: '',
    data: {
        demo:'调用成功',
    },
    methods: {
        //ajax请求
        requestAjax(path,params,returnType = false,urlType = true,is_file=false,siteroot=''){
            let result,url;
            //获取链接信息
            if(urlType){
                url = common.webUrl(path,'',true,siteroot);
            }else{
                url = common.sysUrl(path,'',true,siteroot);
            }
            //自由获取连接 公共调用
            if(urlType == 'public'){
                let c = common.getParams('c');
                let sys = window.location.href.includes('citysys');
                if(c || sys){
                    url = common.sysUrl(path,'',true,siteroot);
                }else{
                    url = common.webUrl(path,'',true,siteroot);
                }
            }
            console.log(url);
            //请求发送信息
            if(is_file){
                $.ajax({
                    type: "POST",
                    url: url,
                    data: params,
                    dataType: "json",
                    async: false,
                    processData : false, // 使数据不做处理
                    contentType : false, // 不要设置Content-Type请求头
                    success: function (data) {
                        if(returnType){
                            result = data;
                        }else{
                            result = data['data'];
                        }
                    },
                    error: function (errors) {
                        console.log("请求失败");
                    }
                });
            }else{
                $.ajax({
                    type: "POST",
                    url: url,
                    data: params,
                    dataType: "json",
                    async: false,
                    success: function (data) {
                        if(returnType){
                            result = data;
                        }else{
                            result = data['data'];
                        }
                    },
                    error: function (errors) {
                        console.log("请求失败");
                    }
                });
            }
            return result;
        },
        //获取拥有的模块信息
        getModular(){
            let res = commonVue.requestAjax('goods/Goods/getModular',{page:this.page});
            return res;
        },
    },
    watch: {},
});