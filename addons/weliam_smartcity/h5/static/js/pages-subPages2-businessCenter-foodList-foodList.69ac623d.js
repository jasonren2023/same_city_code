(window["webpackJsonp"]=window["webpackJsonp"]||[]).push([["pages-subPages2-businessCenter-foodList-foodList"],{"0486":function(t,i,e){"use strict";var o=e("4ea4");e("99af"),e("c975"),e("d81d"),e("a434"),Object.defineProperty(i,"__esModule",{value:!0}),i.default=void 0;var n=o(e("77ab")),s=o(e("cb39")),a=o(e("320e")),r=o(e("f30b")),l=o(e("a7cb")),c=o(e("a833")),u=o(e("e708")),d={components:{tuiRate:a.default,foodCategory:r.default,shoppingFixation:l.default,PopupView:c.default,tuiNumberbox:u.default,Loadlogo:s.default},onLoad:function(t){var i=this;-1===uni.getSystemInfoSync().system.indexOf("Android")?i.flag=!0:i.flag=!1,i.storeData.id=t.id,i.storeData.storeid=t.storeid,this.sid=this.storeData.id,uni.$on("onShows",(function(t){this.onShowss()}))},onShow:function(){this.getCommodityList(),this.oneShow?this.oneShow=!1:(this.getlist(!1,!0),this.topshow=!this.topshow,this.num++)},onHide:function(){},data:function(){return{oneShow:!0,loadlogo:!1,duration:500,interval:2e3,labelHeight:!1,ratio:0,storeData:{},storeFoodList:{storeinfo:{tag:[]}},num:0,sid:null,show:!1,shopingList:null,flag:!1,topshow:!1}},methods:{gogoodsins:function(){n.default.navigationTo({url:"pages/mainPages/store/index?sid="+this.storeData.storeid})},goindex:function(){var t=getCurrentPages();console.log(t),1==t.length?n.default.navigationTo({url:"pages/subPages2/businessCenter/businessCenter"}):uni.navigateBack({delta:1})},withdraw:function(){var t=this;n.default._post_form("&p=citydelivery&do=deteShopCart&sid=".concat(this.sid),{},(function(i){0==i.errno&&(uni.showToast({title:"移除成功",duration:2e3}),t.num++,t.shopingList=null,t.show=!1,t.getlist(!0,!0))}),!1,(function(){}))},empty:function(){var t=this;n.default.showError("确定移除该商店及所属商品？",(function(i){i.confirm&&i&&t.withdraw()}),!0)},change:function(t,i){var e=this,o=this;console.log(i),this.shopingList.cartinfo.goodslist.map((function(s,a){s.id==i.id&&(s.num=t.value,n.default._post_form("&p=citydelivery&do=addShopCart&goodid=".concat(i.goodid,"&specid=").concat(i.specid,"&addtype=").concat("plus"==t.type?1:0),{},(function(n){var s=n.data;s.changenum=t.value,s.changemoney=s.changemoney*t.value,s.id=i.id,0==i.num&&(o.shopingList.cartinfo.goodslist.splice(a,1),0==o.shopingList.cartinfo.goodslist.length&&e.getlist(!0,!0)),e.num++}),!1,(function(){})))}))},onShows:function(){this.getlist(!0,!0)},onShowis:function(t){console.log(t),this.shopingList=t,this.getlist(!0,!0)},getCommodityList:function(){var t=this,i=arguments.length>0&&void 0!==arguments[0]&&arguments[0],e=arguments.length>1&&void 0!==arguments[1]&&arguments[1],o=this;n.default._post_form("&p=citydelivery&do=cateList",{storeid:this.storeData.storeid},(function(n){t.setData({storeFoodList:n.data}),t.storeFoodList.num=t.num,t.storeFoodList.flag=e,i&&(t.show=!t.show),t.getlist(),uni.showLoading({title:"加载中"}),setTimeout((function(){uni.getSystemInfo({success:function(t){o.ratio=t.windowWidth/o.storeFoodList.imgstyle.width}})}))}),!1,(function(){t.loadlogo=!0}))},getlist:function(){var t=this,i=arguments.length>0&&void 0!==arguments[0]&&arguments[0],e=this;n.default._post_form("&p=citydelivery&do=goodsInfo",{storeid:this.storeData.storeid},(function(o){console.log(o),t.storeFoodList.cartinfo.catelist=o.data.catelist,t.storeFoodList.cartinfo.goodslist=o.data.goodslist,e.$nextTick((function(){uni.hideLoading()})),i&&(t.show=!t.show)}),!1,(function(){t.loadlogo=!0}))},labelClick:function(){this.labelHeight=!this.labelHeight},mobleck:function(t){uni.makePhoneCall({phoneNumber:t})}}};i.default=d},"0b81":function(t,i,e){"use strict";var o=e("22a8"),n=e.n(o);n.a},"10fa":function(t,i,e){"use strict";e.r(i);var o=e("0486"),n=e.n(o);for(var s in o)"default"!==s&&function(t){e.d(i,t,(function(){return o[t]}))}(s);i["default"]=n.a},"1c57":function(t,i,e){var o=e("24fb");i=o(!1),i.push([t.i,'@font-face{font-family:rateFont;src:url(data:application/font-woff;charset=utf-8;base64,d09GRgABAAAAAAT4AA0AAAAAB4wAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAABGRlRNAAAE3AAAABoAAAAciBprQUdERUYAAAS8AAAAHgAAAB4AKQALT1MvMgAAAaAAAABDAAAAVj1YSN1jbWFwAAAB+AAAAEIAAAFCAA/qlmdhc3AAAAS0AAAACAAAAAj//wADZ2x5ZgAAAkgAAADwAAABZLMTdXtoZWFkAAABMAAAADAAAAA2FZKISmhoZWEAAAFgAAAAHQAAACQHYgOFaG10eAAAAeQAAAARAAAAEgx6AHpsb2NhAAACPAAAAAwAAAAMAEYAsm1heHAAAAGAAAAAHgAAACABEQBPbmFtZQAAAzgAAAFJAAACiCnmEVVwb3N0AAAEhAAAAC0AAABHLO3vkXjaY2BkYGAA4t2/VF7G89t8ZeBmYQCBm9ZKMnC6ikGMuYXpP5DLwcAEEgUAHPQJOXjaY2BkYGBu+N/AEMPCAALMLQyMDKiABQBQwgLwAAAAeNpjYGRgYGBlcGZgYgABEMkFhAwM/8F8BgAPigFhAAB42mNgZGFgnMDAysDA1Ml0hoGBoR9CM75mMGLkAIoysDIzYAUBaa4pDA7PXj17zdzwv4EhhrmBoQEozAiSAwD/YA2wAHjaY2GAABYIrmKoAgACggEBAAAAeNpjYGBgZoBgGQZGBhCwAfIYwXwWBgUgzQKEQP6z1///A8lX//9LSkJVMjCyMcCYDIxMQIKJARUwMgx7AAA/9QiLAAAAAAAAAAAAAABGALJ42mNgZKhiEGNuYfrPoMnAwGimps+ox6jPqKbEz8jHCMLyjHJAmk1czMie0cxInlHMDChrZs6cJyaosI+NlzmU34I/lImPdb+CoHgXCyujIosYtzTfKlYBtlWyuqwKjKwsjNvFTdlkGDnZ1srKrmXjZJRhMxVvZxFgA+rgYI9iYoriV1TYzybAwsDABHeLBIMT0DUg29VBTjEHucvcjtGeUVyOUZ6JaFcybefnZ5HuFdEX6ZVm5uMvniemxuXmzqUmNs+FeOfHCeiKzfPi4vKaJ6YrUCDOIiM8YYKwDIu4OMRbrOtkZdex4vMWACzGM5B42n2QPU4DMRCFn/MHJBJCIKhdUQDa/JQpEyn0CKWjSDbekGjXXnmdSDkBLRUHoOUYHIAbINFyCl6WSZMia+3o85uZ57EBnOMbCv/fJe6EFY7xKFzBETLhKvUX4Rr5XbiOFj6FG9R/hJu4VQPhFi7UGx1U7YS7m9JtywpnGAhXcIon4Sr1lXCN/CpcxxU+hBvUv4SbGONXuIVrZakM4WEwQWCcQWOKDeMCMRwskjIG1qE59GYSzExPN3oRO5s4GyjvV2KXAx5oOeeAKe09t2a+Sif+YMuB1JhuHgVLtimNLiJ0KBtfLJzV3ahzsP2e7ba02L9rgTXH7FENbNT8Pdsz0khsDK+QkjXyMrekElOPaGus8btnKdbzXgiJTrzL9IjHmjR1OvduaeLA4ufyjBx9tLmSPfeoHD5jWQh5v91OxCCKXYY/k9hxGQAAAHjaY2BigAAuMMnIgA5YwaJMjEyMzPzJ+Tk5qcklmfl58WmZOTlcCD4Ak9QKlAAAAAAAAAH//wACAAEAAAAMAAAAFgAAAAIAAQADAAQAAQAEAAAAAgAAAAB42mNgYGBkAIKrS9Q5QPRNayUZGA0AM8UETgAA) format("woff");font-weight:400;font-style:normal}.tui-icon[data-v-ce9f243a]{font-family:rateFont!important;font-style:normal;-webkit-font-smoothing:antialiased;-moz-osx-font-smoothing:grayscale;display:block}.tui-relative[data-v-ce9f243a]{position:relative}.tui-icon-main[data-v-ce9f243a]{position:absolute;height:100%;left:0;top:0;overflow:hidden}.tui-icon-collection-fill[data-v-ce9f243a]:before{content:"\\e6ea"}.tui-icon-collection[data-v-ce9f243a]:before{content:"\\e6eb"}.tui-rate-box[data-v-ce9f243a]{display:-webkit-inline-flex;display:inline-flex;align-items:center;margin:0;padding:0}',""]),t.exports=i},"22a8":function(t,i,e){var o=e("b5dd");"string"===typeof o&&(o=[[t.i,o,""]]),o.locals&&(t.exports=o.locals);var n=e("4f06").default;n("637d0e5e",o,!0,{sourceMap:!1,shadowMode:!1})},"320e":function(t,i,e){"use strict";e.r(i);var o=e("cfb6"),n=e("abd6");for(var s in n)"default"!==s&&function(t){e.d(i,t,(function(){return n[t]}))}(s);e("86e4");var a,r=e("f0c5"),l=Object(r["a"])(n["default"],o["b"],o["c"],!1,null,"ce9f243a",null,!1,o["a"],a);i["default"]=l.exports},"44cc":function(t,i,e){"use strict";e("a9e3"),e("ac1f"),Object.defineProperty(i,"__esModule",{value:!0}),i.default=void 0;var o={name:"tuiRate",props:{quantity:{type:Number,default:5},current:{type:[Number,String],default:0},score:{type:[Number,String],default:1},disabled:{type:Boolean,default:!1},size:{type:Number,default:20},normal:{type:String,default:"#b2b2b2"},active:{type:String,default:"#e41f19"},hollow:{type:Boolean,default:!1}},data:function(){return{pageX:0,percent:0}},created:function(){this.percent=100*Number(this.score||0)},watch:{score:function(t,i){this.percent=100*Number(t||0)}},methods:{handleTap:function(t){if(!this.disabled){var i=t.currentTarget.dataset.index;this.$emit("change",{index:Number(i)+1})}},touchMove:function(t){if(!this.disabled&&t.changedTouches[0]){var i=t.changedTouches[0].pageX,e=i-this.pageX;if(!(e<=0)){var o=Math.ceil(e/this.size);o=o>this.count?this.count:o,this.$emit("change",{index:o})}}}},mounted:function(){var t=this,i=".tui-rate-box",e=uni.createSelectorQuery().in(this);e.select(i).boundingClientRect((function(i){t.pageX=i.left||0})).exec()},onReady:function(){var t=this,i=".tui-rate-box",e=uni.createSelectorQuery().in(this);e.select(i).boundingClientRect((function(i){t.pageX=i.left||0})).exec()}};i.default=o},"4cb7":function(t,i,e){var o=e("f5a6");"string"===typeof o&&(o=[[t.i,o,""]]),o.locals&&(t.exports=o.locals);var n=e("4f06").default;n("b786975a",o,!0,{sourceMap:!1,shadowMode:!1})},"74b1":function(t,i,e){"use strict";var o;e.d(i,"b",(function(){return n})),e.d(i,"c",(function(){return s})),e.d(i,"a",(function(){return o}));var n=function(){var t=this,i=t.$createElement,e=t._self._c||i;return e("v-uni-view",{staticClass:"shoppingFixation",staticStyle:{"z-index":"1000"}},[e("v-uni-view",{staticClass:"settleAccounts",staticStyle:{"z-index":"1000"}},[e("v-uni-view",{staticClass:"dis-flex",staticStyle:{flex:"4"}},[e("v-uni-view",{staticStyle:{flex:"1",position:"relative"}},[e("v-uni-view",{staticClass:"shopping iconfont icon-gouwuche",style:{backgroundColor:t.count>0&&0!==t.is_business?"#ff4444":"#666666"},on:{click:function(i){arguments[0]=i=t.$handleEvent(i),t.pops.apply(void 0,arguments)}}}),t.count>0&&0!==t.is_business?e("v-uni-view",{staticClass:"commodity"},[t._v(t._s(t.count))]):t._e()],1),e("v-uni-view",{staticStyle:{flex:"3",padding:"28upx 20upx","box-sizing":"border-box"}},[e("v-uni-text",{staticClass:"dis-il-block f-34 f-w",style:{color:t.count>0&&0!==t.is_business?"#ff4444":"#666666"}},[t._v("¥"+t._s(t.totalPrices.toFixed(2))),0!==t.deliveryprice&&t.count>0&&0!==t.is_business?e("v-uni-text",{staticClass:"dis-il-block"},[e("v-uni-text",{staticClass:"f-20",staticStyle:{"padding-left":"60upx",color:"#6f6f6f"}},[t._v(t._s(t.total.bzftext)+":")]),e("v-uni-text",{staticClass:"c-ff4444 f-20",staticStyle:{"padding-left":"10upx"}},[t._v("¥"+t._s(t.deliveryprice))])],1):t._e()],1)],1)],1),e("v-uni-view",{staticClass:"close",style:{backgroundColor:t.count>0&&0!==t.is_business?"#17D117":"#666666"},on:{click:function(i){arguments[0]=i=t.$handleEvent(i),t.goShopping.apply(void 0,arguments)}}},[t._v(t._s(0==t.is_business?"休息中":1!=t.languageStatus?"去结算":"결재하기"))])],1)],1)},s=[]},"86e4":function(t,i,e){"use strict";var o=e("aa47"),n=e.n(o);n.a},"95a2":function(t,i,e){"use strict";e.r(i);var o=e("ef86"),n=e("10fa");for(var s in n)"default"!==s&&function(t){e.d(i,t,(function(){return n[t]}))}(s);e("c242");var a,r=e("f0c5"),l=Object(r["a"])(n["default"],o["b"],o["c"],!1,null,"f2d2b03c",null,!1,o["a"],a);i["default"]=l.exports},a695:function(t,i,e){"use strict";var o=e("4ea4");e("d81d"),e("a9e3"),Object.defineProperty(i,"__esModule",{value:!0}),i.default=void 0;var n=o(e("77ab")),s={data:function(){return{totalPrices:0,count:0,total:[],numberAndPricea:null,show:!1,poststat:!1,deliveryprice:0,packing:""}},mounted:function(){var t=this;t.packing=1!=t.languageStatus?"包装费":"포장비용",this.getShopNum(),uni.$on("STOREPRICES",(function(i){this.poststat||t.getShopNum()}))},onUnload:function(){uni.$off("STOREPRICES")},computed:{},props:{num:{type:Number,default:null},sid:{default:null},is_business:{default:null}},watch:{numberAndPricea:{handler:function(t,i){console.log(t)},deep:!0},num:{handler:function(t,i){console.log(t),this.getShopNum()},deep:!0}},methods:{pops:function(){var t=this;n.default._post_form("&p=citydelivery&do=cartinfo",{},(function(i){t.totalPrices=0,t.count=0;var e=i.data.list;e.map((function(i){i.sid==t.sid&&(t.total=i,t.packing=i.bzftext,t.packing=1!=t.languageStatus?i.bzftext:"포장비용",t.totalPrices=i.cartinfo.totalmoney,t.deliveryprice=i.cartinfo.deliveryprice,t.count=i.cartinfo.totalnum,t.$emit("onShows",i))}))}),!1,(function(){}))},getShopNum:function(){var t=this;this.poststat=!0,n.default._post_form("&p=citydelivery&do=cartinfo",{},(function(i){t.totalPrices=0,t.count=0;var e=i.data.list;e.map((function(i){i.sid==t.sid&&(t.total=i,t.totalPrices=i.cartinfo.totalmoney,t.deliveryprice=i.cartinfo.deliveryprice,t.count=i.cartinfo.totalnum)})),uni.$emit("end","aa"),setTimeout((function(){t.poststat=!1}),500)}),!1,(function(){}))},goShopping:function(){0!=this.is_business&&(this.count?n.default.navigationTo({url:"pages/subPages2/businessCenter/foodOrder/foodOrder?id="+this.sid}):uni.showToast({title:"请选择要购买的商品",duration:2e3,icon:"none"}))}}};i.default=s},a7cb:function(t,i,e){"use strict";e.r(i);var o=e("74b1"),n=e("c61f");for(var s in n)"default"!==s&&function(t){e.d(i,t,(function(){return n[t]}))}(s);e("0b81");var a,r=e("f0c5"),l=Object(r["a"])(n["default"],o["b"],o["c"],!1,null,"4e8456bc",null,!1,o["a"],a);i["default"]=l.exports},aa47:function(t,i,e){var o=e("1c57");"string"===typeof o&&(o=[[t.i,o,""]]),o.locals&&(t.exports=o.locals);var n=e("4f06").default;n("5639964e",o,!0,{sourceMap:!1,shadowMode:!1})},abd6:function(t,i,e){"use strict";e.r(i);var o=e("44cc"),n=e.n(o);for(var s in o)"default"!==s&&function(t){e.d(i,t,(function(){return o[t]}))}(s);i["default"]=n.a},b5dd:function(t,i,e){var o=e("24fb");i=o(!1),i.push([t.i,'@charset "UTF-8";\r\n/**\r\n * 这里是uni-app内置的常用样式变量\r\n *\r\n * uni-app 官方扩展插件及插件市场（https://ext.dcloud.net.cn）上很多三方插件均使用了这些样式变量\r\n * 如果你是插件开发者，建议你使用scss预处理，并在插件代码中直接使用这些变量（无需 import 这个文件），方便用户通过搭积木的方式开发整体风格一致的App\r\n *\r\n */\r\n/**\r\n * 如果你是App开发者（插件使用者），你可以通过修改这些变量来定制自己的插件主题，实现自定义主题功能\r\n *\r\n * 如果你的项目同样使用了scss预处理，你也可以直接在你的 scss 代码中使用如下变量，同时无需 import 这个文件\r\n */\r\n/* 颜色变量 */\r\n/* 行为相关颜色 */\r\n/* 文字基本颜色 */\r\n/* 背景颜色 */\r\n/* 边框颜色 */\r\n/* 尺寸变量 */\r\n/* 文字尺寸 */\r\n/* 图片尺寸 */\r\n/* Border Radius */\r\n/* 水平间距 */\r\n/* 垂直间距 */\r\n/* 透明度 */\r\n/* 文章场景相关 */.close[data-v-4e8456bc]{flex:2;background-color:#17d117;color:#fff;text-align:center;line-height:%?100?%}.settleAccounts[data-v-4e8456bc]{height:%?100?%;width:100%;position:fixed;bottom:0;border-top:%?1?% solid #ececec;background-color:#fff;display:flex}.settleAccounts .shopping[data-v-4e8456bc]{display:inline-block;border-radius:%?15?%;color:#fff;font-size:%?44?%;font-weight:700;padding:%?15?% %?20?%;position:absolute;right:0;top:-20%}.settleAccounts .commodity[data-v-4e8456bc]{border-radius:%?60?%;background-color:#f44;color:#fff;border:%?1?% solid #fff;position:absolute;right:-15%;top:-37%;text-align:center;font-size:%?26?%;padding:%?5?% %?15?%}',""]),t.exports=i},c242:function(t,i,e){"use strict";var o=e("4cb7"),n=e.n(o);n.a},c61f:function(t,i,e){"use strict";e.r(i);var o=e("a695"),n=e.n(o);for(var s in o)"default"!==s&&function(t){e.d(i,t,(function(){return o[t]}))}(s);i["default"]=n.a},cfb6:function(t,i,e){"use strict";var o;e.d(i,"b",(function(){return n})),e.d(i,"c",(function(){return s})),e.d(i,"a",(function(){return o}));var n=function(){var t=this,i=t.$createElement,e=t._self._c||i;return e("v-uni-view",{staticClass:"tui-rate-class tui-rate-box",on:{touchmove:function(i){arguments[0]=i=t.$handleEvent(i),t.touchMove.apply(void 0,arguments)}}},[t._l(t.quantity,(function(i,o){return[e("v-uni-view",{key:o+"_0",staticClass:"tui-icon tui-relative",class:["tui-icon-collection"+(t.hollow&&(t.current<=o||t.disabled&&t.current<=o+1)?"":"-fill")],style:{fontSize:t.size+"px",color:t.current>o+1||!t.disabled&&t.current>o?t.active:t.normal},attrs:{"data-index":o},on:{click:function(i){arguments[0]=i=t.$handleEvent(i),t.handleTap.apply(void 0,arguments)}}},[t.disabled&&t.current==o+1?e("v-uni-view",{staticClass:"tui-icon tui-icon-main tui-icon-collection-fill",style:{fontSize:t.size+"px",color:t.active,width:t.percent+"%"}}):t._e()],1)]}))],2)},s=[]},ef86:function(t,i,e){"use strict";var o;e.d(i,"b",(function(){return n})),e.d(i,"c",(function(){return s})),e.d(i,"a",(function(){return o}));var n=function(){var t=this,i=t.$createElement,e=t._self._c||i;return e("v-uni-view",{staticClass:"foodList"},[t.loadlogo?t._e():e("loadlogo"),t.loadlogo?e("v-uni-view",[e("far-bottom"),e("v-uni-view",{staticClass:"flxedBox"},[e("v-uni-view",{staticClass:"relativeBox"},[t.storeFoodList.storeinfo.album?e("v-uni-view",{staticClass:"titleImgBox"},[e("v-uni-swiper",{staticClass:"swiper",style:{width:t.storeFoodList.imgstyle.width*t.ratio+"px",height:t.storeFoodList.imgstyle.height*t.ratio+"px"},attrs:{"indicator-dots":!0,autoplay:!0,interval:t.interval,duration:t.duration}},t._l(t.storeFoodList.storeinfo.album,(function(t,i){return e("v-uni-swiper-item",{key:i},[e("v-uni-image",{staticStyle:{width:"100%",height:"100%"},attrs:{src:t,mode:"aspectFill"}})],1)})),1),e("v-uni-view",{staticClass:"bankindex",on:{click:function(i){arguments[0]=i=t.$handleEvent(i),t.goindex.apply(void 0,arguments)}}},[e("v-uni-text",{staticClass:"iconfont icon-pullleft",staticStyle:{"font-size":"28upx","padding-right":"10upx"}}),t._v("返回")],1)],1):t._e(),e("v-uni-view",{staticClass:"introduce",style:{marginTop:t.storeFoodList.storeinfo.album?"0rpx":"50rpx"}},[e("v-uni-view",{staticClass:"dis-flex",on:{click:function(i){arguments[0]=i=t.$handleEvent(i),t.gogoodsins.apply(void 0,arguments)}}},[e("v-uni-view",{staticClass:"storeImgBox",staticStyle:{flex:"3",position:"relative"}},[0==t.storeFoodList.storeinfo.is_business?e("v-uni-view",{staticClass:"dis-il-block t-c f-30",staticStyle:{position:"relative",bottom:"0",left:"0",width:"170upx",color:"#FFFFFF","z-index":"99","background-color":"rgba(0,0,0,0.4)"}},[t._v("休息中")]):t._e(),e("v-uni-image",{staticStyle:{width:"170upx",height:"170upx","border-radius":"15upx"},attrs:{src:t.storeFoodList.storeinfo.logo,mode:"aspectFill"}})],1),e("v-uni-view",{staticStyle:{flex:"7"}},[e("v-uni-view",{staticClass:"storeName f-32 f-w"},[t._v(t._s(t.storeFoodList.storeinfo.storename))]),e("v-uni-view",[e("tui-rate",{attrs:{current:t.storeFoodList.storeinfo.score,disabled:!0,score:1,active:"#F5D145"}}),e("v-uni-view",{staticClass:"f-26 dis-il-block",staticStyle:{color:"#363636",height:"46upx","line-height":"44upx","vertical-align":"top"}},[t._v(t._s(t.storeFoodList.storeinfo.score)+"分")])],1),e("v-uni-view",{staticClass:"f-26",staticStyle:{color:"#363636","padding-top":"10upx"}},[e("v-uni-text",[t._v(t._s(t.storeFoodList.storeinfo.catename))])],1),e("v-uni-view",{staticClass:"f-26",staticStyle:{color:"#363636","padding-top":"10upx"}},[e("v-uni-text",[t._v(t._s(1!=t.languageStatus?"营业时间":"영업시간")+"：")]),e("v-uni-text",[t._v(t._s(t.storeFoodList.storeinfo.storehours))])],1),t.storeFoodList.storeinfo.mobile?e("v-uni-view",{staticClass:"telephone",on:{click:function(i){i.stopPropagation(),arguments[0]=i=t.$handleEvent(i),t.mobleck(t.storeFoodList.storeinfo.mobile)}}},[e("v-uni-image",{staticStyle:{width:"60upx",height:"60upx"},attrs:{src:t.imageRoot+"housePhone.png"}})],1):t._e()],1)],1),t.storeFoodList.storeinfo.tag[0]?e("v-uni-view",{staticClass:"tagbox"},[e("v-uni-view",{staticClass:"label"},[e("v-uni-view",{staticClass:"dis-flex tagitembox"},[e("v-uni-view",{staticClass:"labelBox",style:{height:(t.labelHeight,"auto")},on:{click:function(i){arguments[0]=i=t.$handleEvent(i),t.labelClick.apply(void 0,arguments)}}},t._l(t.storeFoodList.storeinfo.tag,(function(i,o){return e("v-uni-view",{key:o,staticClass:"dis-il-block labelItem"},[t._v(t._s(i))])})),1),e("v-uni-view",{class:t.labelHeight?"iconfont icon-fold":"iconfont icon-unfold",staticStyle:{flex:"1",color:"#999999","font-size":"40upx","line-height":"60upx"},on:{click:function(i){arguments[0]=i=t.$handleEvent(i),t.labelClick.apply(void 0,arguments)}}})],1)],1)],1):t._e()],1)],1)],1),e("v-uni-view",{class:t.flag?"abfoodlist":"Androidfoodlist"},[e("foodCategory",{attrs:{storeFoodList:t.storeFoodList,topshow:t.topshow,height:t.storeFoodList.imgstyle.height*t.ratio+"px",width:t.storeFoodList.imgstyle.width*t.ratio+"px"}})],1),e("shoppingFixation",{attrs:{num:t.num,sid:t.sid,is_business:t.storeFoodList.storeinfo.is_business},on:{onShows:function(i){arguments[0]=i=t.$handleEvent(i),t.onShowis.apply(void 0,arguments)}}}),e("popup-view",{staticStyle:{"margin-bottom":"100upx"},attrs:{show:t.show,type:"bottom",bottom:"7%"},on:{clickmask:function(i){arguments[0]=i=t.$handleEvent(i),t.onShows.apply(void 0,arguments)}}},[t.shopingList?e("v-uni-view",{staticStyle:{width:"93vw","border-radius":"15upx 15upx 0 0","background-color":"#f7f7f7",padding:"30upx",height:"50vh",overflow:"auto"}},[e("v-uni-view",{staticClass:"dis-flex f-24",staticStyle:{padding:"0 30upx 30upx"}},[e("v-uni-view",{staticClass:"flex-box "},[t._v("已选商品")]),t.shopingList.cartinfo.deliveryprice>0?e("v-uni-view",{staticClass:"dis-il-block",staticStyle:{"padding-left":"30upx"}},[t._v(t._s(t.shopingList.bzftext)+":"),e("v-uni-text",{staticClass:"c-ff4444",staticStyle:{"padding-left":"10upx"}},[t._v("¥"+t._s(t.shopingList.cartinfo.deliveryprice))])],1):t._e(),e("v-uni-view",{staticClass:"flex-box t-r",on:{click:function(i){arguments[0]=i=t.$handleEvent(i),t.empty.apply(void 0,arguments)}}},[e("v-uni-text",{staticClass:"iconfont icon-delete",staticStyle:{"font-size":"24upx"}}),e("v-uni-text",[t._v("清空")])],1)],1),t._l(t.shopingList.cartinfo.goodslist,(function(i,o){return t.shopingList.cartinfo.goodslist[o].num>0?e("v-uni-view",{key:o,staticClass:"b-f",staticStyle:{padding:"30upx"}},[e("v-uni-view",{staticClass:"dis-flex",staticStyle:{"justify-content":"space-between"}},[e("v-uni-view",{staticClass:"name dis-il-block f-28 f-w",staticStyle:{flex:"0.35","line-height":"40upx"}},[t._v(t._s(i.name))]),e("v-uni-view",{staticClass:"dis-flex",staticStyle:{flex:"0.8"}},[e("v-uni-view",{staticClass:"flex-box f-20 t-r",staticStyle:{"padding-right":"20upx",color:"#999999","line-height":"40upx"}}),e("v-uni-view",{staticClass:"flex-box f-32 f-w",staticStyle:{"padding-right":"20upx",color:"#FF4444","line-height":"40upx"}},[t._v("¥"+t._s(i.price))]),e("v-uni-view",{staticClass:"flex-box"},[e("tui-numberbox",{staticStyle:{float:"right"},attrs:{value:t.shopingList.cartinfo.goodslist[o].num,width:60,min:0},on:{change:function(e){arguments[0]=e=t.$handleEvent(e),t.change(e,i)}}})],1)],1)],1),e("v-uni-view",{staticClass:"f-20",staticStyle:{color:"#999999"}},[e("v-uni-text",[t._v(t._s(i.specname))])],1)],1):t._e()}))],2):t._e(),t.shopingList?t._e():e("v-uni-view",{staticStyle:{width:"93vw","border-radius":"15upx 15upx 0 0","background-color":"#f7f7f7",padding:"30upx","text-align":"center"}},[t._v("购物车空空如也~")])],1),e("popup-view",{attrs:{show:t.labelHeight,type:"bottom"},on:{clickmask:function(i){arguments[0]=i=t.$handleEvent(i),t.labelHeight=!1}}},[e("v-uni-view",{staticClass:"b-f",staticStyle:{width:"92vw","border-radius":"30upx 30upx 0upx 0upx",padding:"30upx 30upx 100upx"}},[e("v-uni-view",{staticClass:"f-30 f-w"},[t._v("商户标签")]),e("v-uni-view",{staticStyle:{padding:"30upx 0",height:"650upx",overflow:"auto"}},t._l(t.storeFoodList.storeinfo.tagslist,(function(i,o){return e("v-uni-view",{key:o,staticStyle:{"padding-bottom":"20upx"}},[e("v-uni-view",{staticStyle:{flex:"0.2"}},[e("v-uni-view",{staticClass:"t-c f-26 dis-il-block",staticStyle:{padding:"5upx 20upx",color:"#FF4444",border:"1upx solid pink","border-radius":"8upx"}},[t._v(t._s(i.title))])],1),e("v-uni-view",{staticStyle:{flex:"0.8"}},[e("v-uni-view",{staticClass:"t-c f-26 dis-il-block",staticStyle:{padding:"0upx 0upx",color:"#6f6f6f",border:"1upx solid #FFFFFF","line-height":"38upx",height:"38upx","border-radius":"5upx"}},[t._v(t._s(i.content))])],1)],1)})),1)],1)],1)],1):t._e()],1)},s=[]},f5a6:function(t,i,e){var o=e("24fb");i=o(!1),i.push([t.i,'@charset "UTF-8";\r\n/**\r\n * 这里是uni-app内置的常用样式变量\r\n *\r\n * uni-app 官方扩展插件及插件市场（https://ext.dcloud.net.cn）上很多三方插件均使用了这些样式变量\r\n * 如果你是插件开发者，建议你使用scss预处理，并在插件代码中直接使用这些变量（无需 import 这个文件），方便用户通过搭积木的方式开发整体风格一致的App\r\n *\r\n */\r\n/**\r\n * 如果你是App开发者（插件使用者），你可以通过修改这些变量来定制自己的插件主题，实现自定义主题功能\r\n *\r\n * 如果你的项目同样使用了scss预处理，你也可以直接在你的 scss 代码中使用如下变量，同时无需 import 这个文件\r\n */\r\n/* 颜色变量 */\r\n/* 行为相关颜色 */\r\n/* 文字基本颜色 */\r\n/* 背景颜色 */\r\n/* 边框颜色 */\r\n/* 尺寸变量 */\r\n/* 文字尺寸 */\r\n/* 图片尺寸 */\r\n/* Border Radius */\r\n/* 水平间距 */\r\n/* 垂直间距 */\r\n/* 透明度 */\r\n/* 文章场景相关 */.titleImgBox[data-v-f2d2b03c] .uni-swiper-dots-horizontal{bottom:%?50?%}.bankindex[data-v-f2d2b03c]{position:absolute;left:%?30?%;top:%?30?%;background-color:#fed55f;box-shadow:0 5px 15px hsla(0,0%,100%,.5);padding:%?10?% %?20?%;color:#fff;font-size:%?24?%;border-radius:%?60?%}.tagitembox[data-v-f2d2b03c]{border-radius:%?15?%;background-color:#fff;z-index:99;padding:0 %?10?%}.tagbox[data-v-f2d2b03c]{width:92%}.labelBox[data-v-f2d2b03c]{flex:10;overflow:hidden;background-color:#fff;box-sizing:border-box}.labelItem[data-v-f2d2b03c]{border:%?1?% solid #ececec;color:#666;border-radius:%?20?%;padding:%?5?% %?10?%;margin-right:%?20?%;font-size:%?22?%;margin-bottom:%?10?%}.foodList[data-v-f2d2b03c]{width:100%}.introduce[data-v-f2d2b03c]{width:92%;position:relative;top:%?-30?%;background-color:#fff;border-radius:%?40?% %?40?% 0 0;padding:%?30?% %?30?% 0}.flxedBox[data-v-f2d2b03c]{position:relative;width:100%;z-index:99;top:0}.storeName[data-v-f2d2b03c]{width:100%;overflow:hidden;\r\n  /*超出部分隐藏*/white-space:nowrap;\r\n  /*不换行*/text-overflow:ellipsis}.abfoodlist[data-v-f2d2b03c]{position:relative;width:100%;height:75vh;overflow:hidden}.Androidfoodlist[data-v-f2d2b03c]{position:relative;width:100%;height:80vh;overflow:hidden}.telephone[data-v-f2d2b03c]{position:absolute;right:%?20?%;top:%?50?%}',""]),t.exports=i}}]);