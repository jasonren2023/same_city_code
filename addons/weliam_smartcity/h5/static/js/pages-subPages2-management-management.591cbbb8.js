(window["webpackJsonp"]=window["webpackJsonp"]||[]).push([["pages-subPages2-management-management"],{"002c":function(t,a,e){"use strict";e.r(a);var i=e("05a7"),n=e.n(i);for(var o in i)"default"!==o&&function(t){e.d(a,t,(function(){return i[t]}))}(o);a["default"]=n.a},"0251":function(t,a,e){"use strict";var i;e.d(a,"b",(function(){return n})),e.d(a,"c",(function(){return o})),e.d(a,"a",(function(){return i}));var n=function(){var t=this,a=t.$createElement,e=t._self._c||a;return e("v-uni-view",{staticClass:"container-image"},[e("v-uni-view",{staticClass:"iamges-box"},[e("v-uni-image",{attrs:{src:t.propsImagesSrc?t.propsImagesSrc:t.imageSrc,mode:"widthFix"}}),"Data"===t.propsdiyTitleType?[e("v-uni-view",{staticClass:"title f-24 col-9"},[t._v(t._s(t.propsdiyTitle?t.propsdiyTitle:1!=t.languageStatus?"暂无数据，快去逛逛吧~":"쇼핑하러 가기"))])]:t._e(),"packet"===t.propsdiyTitleType?[e("v-uni-view",{staticClass:"title f-24 col-9 m-btm20"},[t._v("您还没有红包，去红包广场领取吧！")]),e("v-uni-view",{staticClass:"navPacket f-24",on:{click:function(a){arguments[0]=a=t.$handleEvent(a),t.navgateTo()}}},[t._v("立即去领取")])]:t._e()],2)],1)},o=[]},"05a7":function(t,a,e){"use strict";Object.defineProperty(a,"__esModule",{value:!0}),a.default=void 0;var i={data:function(){return{}},mounted:function(){},computed:{loadImage:function(){var t=this,a=t.$store.state.appInfo.loading;return a||""}}};a.default=i},"0b44":function(t,a,e){var i=e("14b2");"string"===typeof i&&(i=[[t.i,i,""]]),i.locals&&(t.exports=i.locals);var n=e("4f06").default;n("4a2e5f60",i,!0,{sourceMap:!1,shadowMode:!1})},"0fdb":function(t,a,e){"use strict";var i;e.d(a,"b",(function(){return n})),e.d(a,"c",(function(){return o})),e.d(a,"a",(function(){return i}));var n=function(){var t=this,a=t.$createElement,e=t._self._c||a;return e("v-uni-view",{staticClass:"loadMore-box",style:{backgroundColor:t.bgc}},[t.isMore?t._e():[e("v-uni-view",{staticClass:"more-status dis-flex flex-y-center flex-x-center"},[e("v-uni-view",{staticClass:"loadingImg m-right10",style:{"background-image":"url("+t.loadingSrc+")"}}),e("v-uni-view",{staticClass:"f-28 col-3"},[t._v("正在加载")])],1)],t.isMore?[e("v-uni-view",{staticClass:"not-more-status dis-flex flex-y-center flex-x-center"},[e("v-uni-view",{staticClass:"cut-off cut-off-left"}),e("v-uni-view",{staticClass:"not-moreTitle col-9 f-28 m-left-right-20",staticStyle:{flex:"0.35","text-align":"center"}},[t._v(t._s(1!=t.languageStatus?"暂无数据":"기록이 없습니다"))]),e("v-uni-view",{staticClass:"cut-off cut-off-right"})],1)]:t._e()],2)},o=[]},"14b2":function(t,a,e){var i=e("24fb");a=i(!1),a.push([t.i,".loadlogo-container[data-v-23fdce49]{width:100%;height:100%;background-color:#fff;position:fixed;z-index:999}.loadlogo[data-v-23fdce49]{width:80px;height:80px;\n\t/* margin: -60px 0 0 -60px; */position:fixed;top:50%;left:50%;-webkit-transform:translate(-50%,-50%);transform:translate(-50%,-50%);overflow:hidden}.loadlogo .image[data-v-23fdce49]{width:100%;height:100%;overflow:hidden}",""]),t.exports=a},"1dfb":function(t,a,e){"use strict";var i=e("4ea4");e("4160"),e("d81d"),e("a434"),e("159b"),Object.defineProperty(a,"__esModule",{value:!0}),a.default=void 0;var n=i(e("2909")),o=i(e("77ab")),r=i(e("cb39")),s=i(e("39c8")),d=i(e("8127")),c=i(e("a833")),l={data:function(){for(var t=new Date,a=[],e=t.getFullYear(),i=[],n=t.getMonth()+1,o=[],r=t.getDate(),s=1990;s<=t.getFullYear();s++)a.push(s);for(var d=1;d<=12;d++)i.push(d);for(var c=1;c<=31;c++)o.push(c);return{hidegoods:1,isMore:!0,indexs:null,typeList:["抢购","团购","拼团","","优惠卷","","砍价商品"],total:null,goodsList:[],typeShow:"bottom",keyword:"",popShow:!1,scdpText:"",orderlist:[],orderInfo:{},storeid:null,pagetotal:null,type:"",status:2,sort:"",time:"",page:1,array:[],catelist:[],index:0,loadlogo:!1,shopShow:"",filterDialog:!0,title:"picker-view",years:a,year:e,months:i,month:n,days:o,day:r,value:[9999,n-1,r-1],visible:!0,indicatorStyle:"height: ".concat(Math.round(uni.getSystemInfoSync().screenWidth/7.5),"px;"),show:!1,itemid:""}},components:{Loadlogo:r.default,nonemores:s.default,loadmore:d.default,PopupView:c.default},computed:{},onLoad:function(t){var a=this;uni.getSystemInfo({success:function(t){a.phoneHight=t.windowHeight,a.scrollHeight=a.phoneHight-125+"px"}})},onShow:function(t){var a=this;uni.$on("datagoods",(function(t){a.goodsList.forEach((function(e,i){t.id==e.id&&(console.log(t,a.goodsList),a.$set(e,"name",t.name),a.$set(e,"thumb",t.thumb),t.status!==e.status&&a.goodsList.splice(i,1))}))})),uni.getStorage({key:"checkStoreid",success:function(t){a.storeid=t.data,a.getStoreGoods("",!1,1)}}),console.log(a.goodsList)},mounted:function(){},methods:{bindPickerChange:function(t){var a=this;this.goodsList=[],console.log(t),this.index=t.target.value,0==this.index?this.getStoreGoods():this.catelist.map((function(t){a.array[a.index]===t.name&&(a.itemid=t.id,a.getStoreGoods(t.id))}))},addGoods:function(){o.default.navigationTo({url:"pages/subPages2/management/addGoods/addGoods"})},lookclass:function(){o.default.navigationTo({url:"pages/subPages2/management/foodclassify/foodclassify"})},onShows:function(){this.show=!this.show},goBj:function(t){o.default.navigationTo({url:"pages/subPages2/management/addGoods/addGoods?id="+t.id})},goAddGoods:function(){this.show=!this.show},init:function(){},goPost:function(t){var a=null;a="1"==t.type?3:"2"==t.type?4:"3"==t.type?6:"5"==t.type?5:7,console.info("type",t.type),o.default.navigationTo({url:"pages/mainPages/poster/poster?type="+a+"&id="+t.id})},changeGoodsStatus:function(t,a){var e=this,i={storeid:e.storeid,goodsid:t.id,status:a};o.default._post_form("&p=citydelivery&do=changeGoodStatus",i,(function(t){e.doSearch(),uni.showToast({icon:"none",title:"操作成功",duration:2e3})}))},popT:function(t){var a=this;a.popShow=!0,a.indexs=t},load:function(){var t=this;t.total==t.page?t.isMore=!0:(t.page++,console.log("下拉了"),t.getStoreGoods("",!0))},doSearch:function(){var t=this;t.isMore=!1,t.page=1,t.indexs=null;var a={cateid:t.itemid||"",storeid:t.storeid,page:t.page,initialization:1,status:t.status,name:t.keyword};o.default._post_form("&p=citydelivery&do=deliveGoodsList",a,(function(a){console.log(a),t.goodsList=a.data.list,t.total=a.data.total,t.isMore=!0}))},getStoreGoods:function(t,a,e){var i=this;if(!(e>i.page)){i.isMore=!1;var r={cateid:t||"",storeid:i.storeid,page:e||i.page,initialization:1,status:i.status,name:i.keyword};o.default._post_form("&p=citydelivery&do=deliveGoodsList",r,(function(t){var o;(i.array=["全部"],a)?(o=i.goodsList).push.apply(o,(0,n.default)(t.data.list)):i.goodsList=t.data.list;i.total=t.data.total,i.catelist=t.data.catelist,i.catelist.map((function(t){i.array.push(t.name)})),i.isMore=!0,e&&i.getStoreGoods("",!0,e+1)}))}},changeStatus:function(t){var a=this;a.status=t,a.doSearch()}}};a.default=l},2153:function(t,a,e){var i=e("24fb");a=i(!1),a.push([t.i,'@charset "UTF-8";\r\n/**\r\n * 这里是uni-app内置的常用样式变量\r\n *\r\n * uni-app 官方扩展插件及插件市场（https://ext.dcloud.net.cn）上很多三方插件均使用了这些样式变量\r\n * 如果你是插件开发者，建议你使用scss预处理，并在插件代码中直接使用这些变量（无需 import 这个文件），方便用户通过搭积木的方式开发整体风格一致的App\r\n *\r\n */\r\n/**\r\n * 如果你是App开发者（插件使用者），你可以通过修改这些变量来定制自己的插件主题，实现自定义主题功能\r\n *\r\n * 如果你的项目同样使用了scss预处理，你也可以直接在你的 scss 代码中使用如下变量，同时无需 import 这个文件\r\n */\r\n/* 颜色变量 */\r\n/* 行为相关颜色 */\r\n/* 文字基本颜色 */\r\n/* 背景颜色 */\r\n/* 边框颜色 */\r\n/* 尺寸变量 */\r\n/* 文字尺寸 */\r\n/* 图片尺寸 */\r\n/* Border Radius */\r\n/* 水平间距 */\r\n/* 垂直间距 */\r\n/* 透明度 */\r\n/* 文章场景相关 */.loadMore-box[data-v-106757a8]{background-color:#fff}.more-status .loadingImg[data-v-106757a8]{width:%?38?%;height:%?38?%;background-size:%?38?% %?38?%;background-repeat:no-repeat;-webkit-animation:loading-data-v-106757a8 2s linear 2s infinite;animation:loading-data-v-106757a8 2s linear 2s infinite}@-webkit-keyframes loading-data-v-106757a8{from{-webkit-transform:rotate(0deg);transform:rotate(0deg)}to{-webkit-transform:rotate(1turn);transform:rotate(1turn)}}@keyframes loading-data-v-106757a8{from{-webkit-transform:rotate(0deg);transform:rotate(0deg)}to{-webkit-transform:rotate(1turn);transform:rotate(1turn)}}.not-more-status .cut-off[data-v-106757a8]{flex:0.3;height:%?2?%;background-color:#eee}',""]),t.exports=a},2909:function(t,a,e){"use strict";Object.defineProperty(a,"__esModule",{value:!0}),a.default=d;var i=s(e("6005")),n=s(e("db90")),o=s(e("06c5")),r=s(e("3427"));function s(t){return t&&t.__esModule?t:{default:t}}function d(t){return(0,i.default)(t)||(0,n.default)(t)||(0,o.default)(t)||(0,r.default)()}},3427:function(t,a,e){"use strict";function i(){throw new TypeError("Invalid attempt to spread non-iterable instance.\nIn order to be iterable, non-array objects must have a [Symbol.iterator]() method.")}Object.defineProperty(a,"__esModule",{value:!0}),a.default=i},"39c8":function(t,a,e){"use strict";e.r(a);var i=e("0251"),n=e("3e8b");for(var o in n)"default"!==o&&function(t){e.d(a,t,(function(){return n[t]}))}(o);e("ab89");var r,s=e("f0c5"),d=Object(s["a"])(n["default"],i["b"],i["c"],!1,null,"293e65cc",null,!1,i["a"],r);a["default"]=d.exports},"3ac98":function(t,a,e){var i=e("24fb");a=i(!1),a.push([t.i,".popup[data-v-60a53a4d]{width:100vw}.dataPicker1[data-v-60a53a4d]{width:%?70?%;height:%?82?%;display:inline-block}.addOrderButton[data-v-60a53a4d]{width:%?80?%;height:%?80?%;position:fixed;border-radius:%?60?%;bottom:%?80?%;left:%?330?%;background:#38f;text-align:center}.addOrderButton > uni-text[data-v-60a53a4d]{line-height:%?76?%;font-size:%?40?%;color:#fff}.tips[data-v-60a53a4d]{height:%?180?%;text-align:center}.tips > span[data-v-60a53a4d]{line-height:%?80?%;font-size:%?28?%;color:#000}.popView[data-v-60a53a4d]{position:relative;bottom:%?220?%;background:rgba(0,0,0,.5);height:%?220?%;width:100%;display:flex;justify-content:space-around}.popView > uni-view[data-v-60a53a4d]{margin-top:%?60?%;display:inline-block;vertical-align:middle;text-align:center}.popView > uni-view > uni-view[data-v-60a53a4d]{overflow:hidden;width:%?66?%;height:%?66?%;border-radius:50%}.popView > uni-view > uni-view > uni-image[data-v-60a53a4d]{width:%?66?%;height:%?66?%}.popView > uni-view > span[data-v-60a53a4d]{display:inline-block;margin-top:%?10?%;font-size:%?24?%;color:#ddd}.mbRightFoot[data-v-60a53a4d]{display:flex;justify-content:space-between}.mbRightFoot > span[data-v-60a53a4d]{vertical-align:bottom;font-size:%?36?%;color:#f44}.mbRightFoot > span > span[data-v-60a53a4d]{font-size:%?24?%}.mbRightFoot > uni-view[data-v-60a53a4d]{width:%?52?%;height:%?52?%;border:%?2?% solid #ccc;border-radius:50%;display:flex;justify-content:space-around}.mbRightFoot > uni-view > uni-view[data-v-60a53a4d]{margin-top:%?25?%;line-height:%?52?%;width:%?7?%;height:%?7?%;background:#666;border-radius:50%}.mbRightBody[data-v-60a53a4d]{display:flex;justify-content:space-between;margin-top:%?10?%}.mbRightBody > uni-view > span[data-v-60a53a4d]{font-size:%?24?%;color:#999}.mbRightBody > uni-view > span[data-v-60a53a4d]:nth-child(2){margin-left:%?10?%}.mbRightBody > uni-view > uni-text[data-v-60a53a4d]{font-size:%?24?%;color:#f44}.mbRight[data-v-60a53a4d]{height:%?159?%;margin-left:%?20?%;width:%?450?%}.mbRight > uni-view[data-v-60a53a4d]{height:%?159?%;display:flex;justify-content:space-between;flex-direction:column}.mbRightTitle > uni-view[data-v-60a53a4d]{display:inline-block;vertical-align:middle;width:%?52?%;height:%?30?%;background:#f44;border-radius:%?4?%;text-align:center}.mbRightTitle > uni-view > span[data-v-60a53a4d]{line-height:%?30?%;display:inline-block;font-size:%?20?%;color:#fff}.mbRightTitle > span[data-v-60a53a4d]{margin-left:%?10?%;vertical-align:middle;font-size:%?26?%;font-weight:700;color:#333}.mbLeft[data-v-60a53a4d]{width:%?159?%;height:%?159?%;border-radius:%?10?%}.mbLeft > uni-image[data-v-60a53a4d]{width:%?159?%;height:%?159?%}.orderMbPackage[data-v-60a53a4d]{padding:%?30?%}.orderMbPackage > uni-view[data-v-60a53a4d]{display:inline-block;vertical-align:middle}.orderMb[data-v-60a53a4d]{height:%?220?%;overflow:hidden;margin:%?20?% auto 0 auto;width:%?690?%;background:#fff;border-radius:%?10?%}.full[data-v-60a53a4d]{height:%?220?%;background:#f6f6f6}.scrollList[data-v-60a53a4d]{background:#f6f6f6}.tips[data-v-60a53a4d]{height:%?180?%;text-align:center}.tips > span[data-v-60a53a4d]{line-height:%?80?%;font-size:%?28?%;color:#000}uni-page-body[data-v-60a53a4d]{font-size:0;background-color:#f6f6f6}.color-33[data-v-60a53a4d]{color:#333}.color-red[data-v-60a53a4d]{color:#f44}.container .orderList-header[data-v-60a53a4d]{z-index:2;position:fixed;top:%?0?%;left:0;right:0}.container .topNav[data-v-60a53a4d]{background:#fff;display:flex;justify-content:space-between}.container .topNav .line[data-v-60a53a4d]{position:absolute;opacity:0;bottom:0;display:inline-block;width:%?48?%;height:%?7?%;left:50%;-webkit-transform:translateX(-50%);transform:translateX(-50%);background:#38f;border-radius:4px 4px 0 0}.container .topNav > uni-view[data-v-60a53a4d]{text-align:center;width:20%;display:inline-block}.container .topNav > uni-view > uni-view[data-v-60a53a4d]{height:%?81?%;margin:auto;border-bottom:%?4?% solid transparent;position:relative}.container .topNav > uni-view > uni-view > span[data-v-60a53a4d]{line-height:%?85?%;font-size:%?24?%;color:#333}.container .check .line[data-v-60a53a4d]{opacity:1}.container .check > span[data-v-60a53a4d]{font-size:%?28?%!important;font-weight:500!important;color:#38f!important}.container .search-main .search-box .search-input[data-v-60a53a4d]{position:relative;padding:0 %?60?% 0 %?80?%;margin-right:%?40?%;background:#f6f6f6;height:%?76?%;border-radius:%?38?%;flex:0.8}.container .search-main .search-box .search-input uni-input[data-v-60a53a4d]{width:100%;height:100%}.container .search-main .search-box .search-input .icon.icon-sousuo[data-v-60a53a4d]{position:absolute;top:50%;-webkit-transform:translateY(-50%);transform:translateY(-50%);left:%?30?%}.container .search-main .search-box .pullDown[data-v-60a53a4d]{flex:0.2}.container .search-main .search-box .search-select[data-v-60a53a4d]{width:%?100?%;white-space:nowrap;color:#333}.container .search-main .search-box .search-select .icon[data-v-60a53a4d]{margin-left:%?10?%;color:#999}.container .permission-name[data-v-60a53a4d]{font-size:%?28?%;color:#999}.container .pages-header[data-v-60a53a4d]{width:100%;height:%?400?%;background-repeat:no-repeat;background-size:100% %?400?%}.container .orderList[data-v-60a53a4d]{background-color:#fff}.container .orderList .orderList-search[data-v-60a53a4d]{padding:%?30?%;background-color:#fff}.container .orderList .orderList-body[data-v-60a53a4d]{padding:%?30?%;margin-top:%?206?%;background-color:#f6f6f6}.container .orderList .orderList-body .orderList-list-item[data-v-60a53a4d]{padding:%?30?%;border-radius:%?10?%;background:#fff}.container .orderList .orderList-body .orderList-list-item .user-msg[data-v-60a53a4d]{padding:%?25?% %?30?%;background:#f8f8f8;border-radius:%?8?%;color:#999}.container .orderList .orderList-body .orderList-list-item .user-msg .blue[data-v-60a53a4d]{color:#2196f3}.container .orderList .orderList-body .orderList-list-item .user-msg .img[data-v-60a53a4d]{margin:0 %?15?% 0 %?43?%;height:%?34?%;width:%?34?%}.container .orderList .orderList-body .orderList-list-item .orderList-list-item-header[data-v-60a53a4d]{padding-bottom:%?28?%}.container .orderList .orderList-body .orderList-list-item .orderList-list-item-header .color-status[data-v-60a53a4d]{color:#38f}.container .orderList .orderList-body .orderList-list-item .orderList-list-item-header .tag[data-v-60a53a4d]{padding:%?4?%;border:1px solid #f44;border-radius:%?4?%;text-align:center;font-size:%?20?%;margin-right:%?20?%;color:#f44}.container .orderList .orderList-body .orderList-list-item .orderList-list-item-content[data-v-60a53a4d]{padding:%?30?% 0;border-bottom:1px solid #eee}.container .orderList .orderList-body .orderList-list-item .orderList-list-item-content .orderList-img[data-v-60a53a4d]{width:%?130?%;height:%?130?%;border-radius:%?4?%;margin-right:%?30?%;flex-shrink:0}.container .orderList .orderList-body .orderList-list-item .orderList-list-item-content .goods-name[data-v-60a53a4d]{color:#000;line-height:%?36?%}.container .orderList .orderList-body .orderList-list-item .orderList-list-item-content .goods-sku[data-v-60a53a4d]{color:#999}.container .orderList .orderList-body .orderList-list-item .orderList-list-item-footer .footer-item[data-v-60a53a4d]{padding-top:%?30?%}.container .orderList .orderList-body .orderList-list-item .orderList-list-item-footer .footer-item .btn[data-v-60a53a4d]{width:%?109?%;height:%?50?%;line-height:%?50?%;text-align:center;background:#38f;color:#fff;font-size:%?24?%;border-radius:%?25?%}body.?%PAGE?%[data-v-60a53a4d]{background-color:#f6f6f6}",""]),t.exports=a},"3e8b":function(t,a,e){"use strict";e.r(a);var i=e("f433"),n=e.n(i);for(var o in i)"default"!==o&&function(t){e.d(a,t,(function(){return i[t]}))}(o);a["default"]=n.a},"3ec4":function(t,a,e){var i=e("2153");"string"===typeof i&&(i=[[t.i,i,""]]),i.locals&&(t.exports=i.locals);var n=e("4f06").default;n("329cdaa7",i,!0,{sourceMap:!1,shadowMode:!1})},"4ba9":function(t,a,e){"use strict";e.r(a);var i=e("1dfb"),n=e.n(i);for(var o in i)"default"!==o&&function(t){e.d(a,t,(function(){return i[t]}))}(o);a["default"]=n.a},6005:function(t,a,e){"use strict";Object.defineProperty(a,"__esModule",{value:!0}),a.default=o;var i=n(e("6b75"));function n(t){return t&&t.__esModule?t:{default:t}}function o(t){if(Array.isArray(t))return(0,i.default)(t)}},"6f92":function(t,a,e){"use strict";var i;e.d(a,"b",(function(){return n})),e.d(a,"c",(function(){return o})),e.d(a,"a",(function(){return i}));var n=function(){var t=this,a=t.$createElement,e=t._self._c||a;return e("v-uni-view",[e("v-uni-view",{staticClass:"container"},[e("far-bottom"),e("v-uni-view",{staticClass:"orderList"},[e("v-uni-view",{staticClass:"dis-flex flex-dir-column orderList-header"},[e("v-uni-view",{staticClass:"orderList-search"},[e("v-uni-view",{staticClass:"search-main"},[e("v-uni-view",{staticClass:"search-box dis-flex flex-y-center"},[e("v-uni-view",{staticClass:"search-input"},[e("v-uni-view",{staticClass:"i icon iconfont icon-sousuo"}),e("v-uni-input",{staticClass:"f-24",attrs:{type:"text",value:"",placeholder:"请输入关键字进行搜索","placeholder-style":"color:#999999;margin-left:10upx;"},on:{blur:function(a){arguments[0]=a=t.$handleEvent(a),t.doSearch.apply(void 0,arguments)}},model:{value:t.keyword,callback:function(a){t.keyword=a},expression:"keyword"}})],1),e("v-uni-view",{staticClass:"pullDown"},[e("v-uni-picker",{attrs:{value:t.index,range:t.array},on:{change:function(a){arguments[0]=a=t.$handleEvent(a),t.bindPickerChange.apply(void 0,arguments)}}},[e("v-uni-view",{staticClass:"uni-input t-r f-28",staticStyle:{color:"#999999"}},[t._v(t._s(t.array[t.index])),e("v-uni-text",{staticClass:"iconfont icon-unfold",staticStyle:{"font-size":"28upx"}})],1)],1)],1)],1)],1)],1),e("v-uni-view",{staticClass:"topNav"},[e("v-uni-view",{on:{click:function(a){arguments[0]=a=t.$handleEvent(a),t.changeStatus(2)}}},[e("v-uni-view",{class:2==t.status?"check":""},[e("span",[t._v("出售中")]),e("v-uni-view",{staticClass:"line"})],1)],1),e("v-uni-view",{on:{click:function(a){arguments[0]=a=t.$handleEvent(a),t.changeStatus(4)}}},[e("v-uni-view",{class:4==t.status?"check":""},[e("span",[t._v("已下架")]),e("v-uni-view",{staticClass:"line"})],1)],1),e("v-uni-view",{on:{click:function(a){arguments[0]=a=t.$handleEvent(a),t.changeStatus(5)}}},[e("v-uni-view",{class:5==t.status?"check":""},[e("span",[t._v("审核中")]),e("v-uni-view",{staticClass:"line"})],1)],1),e("v-uni-view",{on:{click:function(a){arguments[0]=a=t.$handleEvent(a),t.changeStatus(8)}}},[e("v-uni-view",{class:8==t.status?"check":""},[e("span",[t._v("回收站")]),e("v-uni-view",{staticClass:"line"})],1)],1)],1)],1),e("v-uni-view",{staticClass:"full"}),e("v-uni-scroll-view",{staticClass:"scrollList",style:{height:t.scrollHeight},attrs:{"scroll-y":!0,"lower-threshold":0},on:{scrolltolower:function(a){arguments[0]=a=t.$handleEvent(a),t.load.apply(void 0,arguments)}}},[0==t.total?e("nonemores"):t._e(),t._l(t.goodsList,(function(a,i){return 0!==t.total?e("v-uni-view",{staticClass:"orderMb"},[e("v-uni-view",{staticClass:"orderMbPackage"},[e("v-uni-view",{staticClass:"mbLeft"},[e("v-uni-image",{attrs:{src:a.thumb}})],1),e("v-uni-view",{staticClass:"mbRight"},[e("v-uni-view",[e("v-uni-view",{staticClass:"mbRightTitle"},[e("span",{staticStyle:{display:"block",width:"440upx",overflow:"hidden","white-space":"nowrap","text-overflow":"ellipsis"}},[t._v(t._s(a.name))])]),e("v-uni-view",{staticClass:"mbRightBody"},[e("v-uni-view",[e("span",[t._v("销量:"+t._s(a.salenum||"0"))])]),e("v-uni-view",[e("v-uni-text",[t._v(t._s("4"==a.status?"已下架":"2"==a.status?"出售中":"5"==a.status?"审核中":"8"==a.status?"回收站":""))])],1)],1),e("v-uni-view",{staticClass:"mbRightFoot"},[e("span",[e("span")]),e("v-uni-view",{on:{click:function(a){arguments[0]=a=t.$handleEvent(a),t.popT(i)}}},[e("v-uni-view"),e("v-uni-view"),e("v-uni-view")],1)],1)],1)],1)],1),t.indexs==i?e("v-uni-view",{staticClass:"popView",on:{click:function(a){arguments[0]=a=t.$handleEvent(a),t.indexs=null}}},["4"==a.status?e("v-uni-view",{on:{click:function(e){arguments[0]=e=t.$handleEvent(e),t.changeGoodsStatus(a,2)}}},[e("v-uni-view",[e("v-uni-image",{attrs:{src:t.imgfixUrls+"merchant/sj.png"}})],1),e("span",[t._v("上架")])],1):t._e(),"2"==a.status?e("v-uni-view",{on:{click:function(e){arguments[0]=e=t.$handleEvent(e),t.changeGoodsStatus(a,4)}}},[e("v-uni-view",[e("v-uni-image",{attrs:{src:t.imgfixUrls+"merchant/xj.png"}})],1),e("span",[t._v("下架")])],1):t._e(),"8"!==a.status?e("v-uni-view",{on:{click:function(e){arguments[0]=e=t.$handleEvent(e),t.changeGoodsStatus(a,8)}}},[e("v-uni-view",[e("v-uni-image",{attrs:{src:t.imgfixUrls+"merchant/sc.png"}})],1),e("span",[t._v("删除")])],1):t._e(),e("v-uni-view",{on:{click:function(e){arguments[0]=e=t.$handleEvent(e),t.goBj(a)}}},[e("v-uni-view",[e("v-uni-image",{attrs:{src:t.imgfixUrls+"merchant/bianj.png"}})],1),e("span",[t._v("编辑")])],1)],1):t._e()],1):t._e()})),0!==t.total?e("v-uni-view",{staticClass:"tips"},[e("loadmore",{attrs:{isMore:t.isMore}})],1):t._e()],2)],1)],1),e("v-uni-view",{staticClass:"addOrderButton",on:{click:function(a){arguments[0]=a=t.$handleEvent(a),t.goAddGoods.apply(void 0,arguments)}}},[e("v-uni-text",{staticClass:"iconfont icon-add f-w"})],1),e("popup-view",{attrs:{show:t.show,type:"bottom",bottom:"0%"},on:{clickmask:function(a){arguments[0]=a=t.$handleEvent(a),t.onShows.apply(void 0,arguments)}}},[e("v-uni-view",{staticClass:"b-f popup"},[e("v-uni-view",{staticClass:"dis-flex",staticStyle:{padding:"50upx 0"}},[e("v-uni-view",{staticClass:"flex-box t-c",on:{click:function(a){arguments[0]=a=t.$handleEvent(a),t.addGoods.apply(void 0,arguments)}}},[e("v-uni-view",{staticClass:"iconfont icon-goodsfill",staticStyle:{width:"100upx",height:"100upx","border-radius":"60upx","background-color":"#FBC420",margin:"0 auto 30upx","line-height":"100upx","font-size":"40upx",color:"#FFFFFF"}}),e("v-uni-view",{staticStyle:{"font-size":"28upx"}},[t._v("添加商品")])],1),e("v-uni-view",{staticClass:"flex-box t-c",on:{click:function(a){arguments[0]=a=t.$handleEvent(a),t.lookclass.apply(void 0,arguments)}}},[e("v-uni-view",{staticClass:"iconfont icon-jingdianwanfa1",staticStyle:{width:"100upx",height:"100upx","border-radius":"60upx","background-color":"#4693FF",margin:"0 auto 30upx","line-height":"100upx","font-size":"40upx",color:"#FFFFFF"}}),e("v-uni-view",{staticStyle:{"font-size":"28upx"}},[t._v("查看分类")])],1)],1),e("v-uni-view",{staticClass:"t-c",staticStyle:{"font-size":"28upx",padding:"20upx 0","border-top":"1px solid #f8f8f8"},on:{click:function(a){arguments[0]=a=t.$handleEvent(a),t.onShows.apply(void 0,arguments)}}},[t._v("关闭")])],1)],1)],1)},o=[]},7827:function(t,a,e){"use strict";var i=e("3ec4"),n=e.n(i);n.a},8127:function(t,a,e){"use strict";e.r(a);var i=e("0fdb"),n=e("b849");for(var o in n)"default"!==o&&function(t){e.d(a,t,(function(){return n[t]}))}(o);e("7827");var r,s=e("f0c5"),d=Object(s["a"])(n["default"],i["b"],i["c"],!1,null,"106757a8",null,!1,i["a"],r);a["default"]=d.exports},"864b":function(t,a,e){"use strict";var i;e.d(a,"b",(function(){return n})),e.d(a,"c",(function(){return o})),e.d(a,"a",(function(){return i}));var n=function(){var t=this,a=t.$createElement,e=t._self._c||a;return e("v-uni-view",[e("v-uni-view",{staticClass:"loadlogo-container"},[e("v-uni-view",{staticClass:"loadlogo"},[e("v-uni-image",{staticClass:"image",attrs:{src:t.loadImage||t.imgfixUrls+"loadlogo.svg",mode:"aspectFill"}})],1)],1)],1)},o=[]},"97c7":function(t,a,e){var i=e("24fb");a=i(!1),a.push([t.i,".container-image[data-v-293e65cc]{position:relative;display:block;width:100%;height:0;padding-bottom:100%;overflow:hidden}.iamges-box[data-v-293e65cc]{position:absolute;display:flex;top:0;bottom:0;left:0;right:0;flex-direction:column;justify-content:center;align-items:center}.iamges-box uni-image[data-v-293e65cc]{width:%?580?%;height:%?270?%;display:block;background:transparent no-repeat;background-size:cover}.navPacket[data-v-293e65cc]{color:#17d117}",""]),t.exports=a},"9e53":function(t,a,e){"use strict";var i=e("edb7"),n=e.n(i);n.a},ab89:function(t,a,e){"use strict";var i=e("d91d"),n=e.n(i);n.a},b849:function(t,a,e){"use strict";e.r(a);var i=e("c3b8"),n=e.n(i);for(var o in i)"default"!==o&&function(t){e.d(a,t,(function(){return i[t]}))}(o);a["default"]=n.a},c3b8:function(t,a,e){"use strict";Object.defineProperty(a,"__esModule",{value:!0}),a.default=void 0;var i={data:function(){return{}},computed:{loadingSrc:function(){return this.imageRoot+"loadmore.svg"}},props:{isMore:{type:Boolean,default:function(){return!1}},bgc:{type:String,default:"#f8f8f8"}}};a.default=i},c6b2:function(t,a,e){"use strict";var i=e("0b44"),n=e.n(i);n.a},cb39:function(t,a,e){"use strict";e.r(a);var i=e("864b"),n=e("002c");for(var o in n)"default"!==o&&function(t){e.d(a,t,(function(){return n[t]}))}(o);e("c6b2");var r,s=e("f0c5"),d=Object(s["a"])(n["default"],i["b"],i["c"],!1,null,"23fdce49",null,!1,i["a"],r);a["default"]=d.exports},d91d:function(t,a,e){var i=e("97c7");"string"===typeof i&&(i=[[t.i,i,""]]),i.locals&&(t.exports=i.locals);var n=e("4f06").default;n("7af83296",i,!0,{sourceMap:!1,shadowMode:!1})},db90:function(t,a,e){"use strict";function i(t){if("undefined"!==typeof Symbol&&Symbol.iterator in Object(t))return Array.from(t)}e("a4d3"),e("e01a"),e("d28b"),e("a630"),e("d3b7"),e("3ca3"),e("ddb0"),Object.defineProperty(a,"__esModule",{value:!0}),a.default=i},edb7:function(t,a,e){var i=e("3ac98");"string"===typeof i&&(i=[[t.i,i,""]]),i.locals&&(t.exports=i.locals);var n=e("4f06").default;n("39a5abb4",i,!0,{sourceMap:!1,shadowMode:!1})},f433:function(t,a,e){"use strict";var i=e("4ea4");Object.defineProperty(a,"__esModule",{value:!0}),a.default=void 0;var n=i(e("77ab")),o={data:function(){return{}},methods:{navgateTo:function(){n.default.navigationTo({url:"pages/subPages/redpacket/redsquare"})}},props:{diyImagesSrc:{type:String,default:function(){return""}},diyTitle:{type:String,default:function(){return""}},diyTitleType:{type:String,default:function(){return"Data"}}},computed:{imageSrc:function(){return this.imageRoot+"noneMores.png"},propsImagesSrc:function(){return this.diyImagesSrc},propsdiyTitle:function(){return this.diyTitle},propsdiyTitleType:function(){return this.diyTitleType}}};a.default=o},f442:function(t,a,e){"use strict";e.r(a);var i=e("6f92"),n=e("4ba9");for(var o in n)"default"!==o&&function(t){e.d(a,t,(function(){return n[t]}))}(o);e("9e53");var r,s=e("f0c5"),d=Object(s["a"])(n["default"],i["b"],i["c"],!1,null,"60a53a4d",null,!1,i["a"],r);a["default"]=d.exports}}]);