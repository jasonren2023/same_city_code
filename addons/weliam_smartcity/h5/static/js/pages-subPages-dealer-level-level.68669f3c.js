(window["webpackJsonp"]=window["webpackJsonp"]||[]).push([["pages-subPages-dealer-level-level"],{"002c":function(t,e,i){"use strict";i.r(e);var a=i("05a7"),n=i.n(a);for(var s in a)"default"!==s&&function(t){i.d(e,t,(function(){return a[t]}))}(s);e["default"]=n.a},"05a7":function(t,e,i){"use strict";Object.defineProperty(e,"__esModule",{value:!0}),e.default=void 0;var a={data:function(){return{}},mounted:function(){},computed:{loadImage:function(){var t=this,e=t.$store.state.appInfo.loading;return e||""}}};e.default=a},"083f":function(t,e,i){"use strict";i.r(e);var a=i("5b90"),n=i.n(a);for(var s in a)"default"!==s&&function(t){i.d(e,t,(function(){return a[t]}))}(s);e["default"]=n.a},"0b44":function(t,e,i){var a=i("14b2");"string"===typeof a&&(a=[[t.i,a,""]]),a.locals&&(t.exports=a.locals);var n=i("4f06").default;n("4a2e5f60",a,!0,{sourceMap:!1,shadowMode:!1})},"14b2":function(t,e,i){var a=i("24fb");e=a(!1),e.push([t.i,".loadlogo-container[data-v-23fdce49]{width:100%;height:100%;background-color:#fff;position:fixed;z-index:999}.loadlogo[data-v-23fdce49]{width:80px;height:80px;\n\t/* margin: -60px 0 0 -60px; */position:fixed;top:50%;left:50%;-webkit-transform:translate(-50%,-50%);transform:translate(-50%,-50%);overflow:hidden}.loadlogo .image[data-v-23fdce49]{width:100%;height:100%;overflow:hidden}",""]),t.exports=e},2909:function(t,e,i){"use strict";Object.defineProperty(e,"__esModule",{value:!0}),e.default=r;var a=o(i("6005")),n=o(i("db90")),s=o(i("06c5")),l=o(i("3427"));function o(t){return t&&t.__esModule?t:{default:t}}function r(t){return(0,a.default)(t)||(0,n.default)(t)||(0,s.default)(t)||(0,l.default)()}},3427:function(t,e,i){"use strict";function a(){throw new TypeError("Invalid attempt to spread non-iterable instance.\nIn order to be iterable, non-array objects must have a [Symbol.iterator]() method.")}Object.defineProperty(e,"__esModule",{value:!0}),e.default=a},"4f29":function(t,e,i){var a=i("7eec");"string"===typeof a&&(a=[[t.i,a,""]]),a.locals&&(t.exports=a.locals);var n=i("4f06").default;n("540ec2d2",a,!0,{sourceMap:!1,shadowMode:!1})},"51a5":function(t,e,i){"use strict";var a;i.d(e,"b",(function(){return n})),i.d(e,"c",(function(){return s})),i.d(e,"a",(function(){return a}));var n=function(){var t=this,e=t.$createElement,i=t._self._c||e;return i("v-uni-view",[t.loadlogo?t._e():i("loadlogo"),t.loadlogo?i("v-uni-view",[i("far-bottom"),i("v-uni-view",{staticClass:"current-level"},[i("v-uni-view",{staticClass:"user-level dis-flex flex-x-between flex-y-center"},[i("v-uni-view",{staticClass:"user-info dis-flex flex-y-center"},[i("v-uni-image",{staticClass:"user-avatar",attrs:{src:t.userinfo.avatar}}),i("v-uni-view",{staticClass:"user-name f-26 col-f"},[t._v(t._s(t.userinfo.nickname))])],1),i("v-uni-view",{staticClass:"level-info dis-flex flex-y-center"},[i("v-uni-image",{staticClass:"level-icon",attrs:{src:t.imgfixUrls+"level.png"}}),i("v-uni-view",{staticClass:"level f-26 col-level"},[t._v(t._s(t.userlevel.name))])],1)],1),i("v-uni-view",{staticClass:"level-list",style:{transform:"translateX("+(50+50*t.current)+"%)"}},t._l(t.levelList,(function(e,a){return i("v-uni-view",{key:a,staticClass:"level-item",on:{click:function(i){arguments[0]=i=t.$handleEvent(i),t.levelChange(a,e.id)}}},[i("v-uni-view",{staticClass:"dis-flex flex-dir-column flex-x-center flex-y-center"},[i("v-uni-view",{staticClass:"level-dot"}),t.self_leave===e.id?i("v-uni-image",{staticClass:"level-vip",attrs:{src:t.imgfixUrls+"vip.png"}}):t._e(),i("v-uni-view",{staticClass:"level-text",class:[t.self_leave===e.id?"f-30 f-w":"f-26"]},[t._v(t._s(e.name))])],1),a!==t.levelList.length-1?i("v-uni-view",{staticClass:"item-line"}):t._e()],1)})),1)],1),i("v-uni-view",{staticClass:"upgrade-info b-f"},[i("part-loading",{ref:"partLoading"}),t.levelDetail?i("v-uni-view",{staticClass:"up-item"},[i("v-uni-view",{staticClass:"up-title"},[t._v(t._s(t.TextSubstitution.yjtext)+"比例")]),i("v-uni-view",{staticClass:"up-content"},[i("v-uni-view",{staticClass:"commission-item dis-flex flex-y-center flex-x-between"},[i("v-uni-view",{staticClass:"commission-left dis-flex flex-y-center"},[i("v-uni-image",{staticClass:"commission-icon",attrs:{src:t.imgfixUrls+"yongjin.png"}}),i("v-uni-view",{staticClass:"commission-text f-26"},[t._v("一级"+t._s(t.TextSubstitution.yjtext))])],1),i("v-uni-view",{staticClass:"commission-right f-30 f-w"},[t._v(t._s(t.levelDetail.onecommission)+"%")])],1),"2"==t.levelDetail.hierarchy?i("v-uni-view",{staticClass:"commission-item dis-flex flex-y-center flex-x-between"},[i("v-uni-view",{staticClass:"commission-left dis-flex flex-y-center"},[i("v-uni-image",{staticClass:"commission-icon",attrs:{src:t.imgfixUrls+"yongjin.png"}}),i("v-uni-view",{staticClass:"commission-text f-26"},[t._v("二级"+t._s(t.TextSubstitution.yjtext))])],1),i("v-uni-view",{staticClass:"commission-right f-30 f-w"},[t._v(t._s(t.levelDetail.twocommission)+"%")])],1):t._e()],1)],1):t._e(),t.levelDetail.flag||t.levelDetail.flag1||t.levelDetail.flag2||t.levelDetail.flag3||t.levelDetail.flag4||t.levelDetail.flag5?i("v-uni-view",{staticClass:"up-item"},[i("v-uni-view",{staticClass:"up-title dis-flex flex-x-between flex-y-end"},[i("v-uni-view",[t._v("升级条件")]),i("v-uni-view",{staticClass:"col-9 f-24"},[t._v("满足以下条件")])],1),i("v-uni-view",{staticClass:"up-content"},[1==t.levelDetail.flag?i("v-uni-view",{staticClass:"condition-item border-line border-bottom dis-flex flex-x-between flex-y-center"},[i("v-uni-view",{staticClass:"condition-detail"},[i("v-uni-view",{staticClass:"f-28 col-3 m-btm10"},[t._v(t._s("已结算总"+t.TextSubstitution.yjtext)),i("v-uni-text",{staticClass:"upstand-text"},[t._v(t._s(t.levelDetail.upstandard)+"元")])],1),i("v-uni-view",{staticClass:"f-24 col-9"},[t._v("当前还差"+t._s(t.levelDetail.distance)+"元")])],1),i("v-uni-view",{staticClass:"condition-status",class:{completed:0==t.levelDetail.distance}},[t._v(t._s(0==t.levelDetail.distance?"已完成":"未完成"))])],1):t._e(),1==t.levelDetail.flag1?i("v-uni-view",{staticClass:"condition-item border-line border-bottom dis-flex flex-x-between flex-y-center"},[i("v-uni-view",{staticClass:"condition-detail"},[i("v-uni-view",{staticClass:"f-28 col-3 m-btm10"},[t._v("下级总人数"),i("v-uni-text",{staticClass:"upstand-text"},[t._v(t._s(t.levelDetail.upstandard1)+"人")])],1),i("v-uni-view",{staticClass:"f-24 col-9"},[t._v("当前还差"+t._s(t.levelDetail.distance1)+"人")])],1),i("v-uni-view",{staticClass:"condition-status",class:{completed:0==t.levelDetail.distance1}},[t._v(t._s(0==t.levelDetail.distance1?"已完成":"未完成"))])],1):t._e(),1==t.levelDetail.flag2?i("v-uni-view",{staticClass:"condition-item border-line border-bottom dis-flex flex-x-between flex-y-center"},[i("v-uni-view",{staticClass:"condition-detail"},[i("v-uni-view",{staticClass:"f-28 col-3 m-btm10"},[t._v("一级下级人数"),i("v-uni-text",{staticClass:"upstand-text"},[t._v(t._s(t.levelDetail.upstandard2)+"人")])],1),i("v-uni-view",{staticClass:"f-24 col-9"},[t._v("当前还差"+t._s(t.levelDetail.distance2)+"人")])],1),i("v-uni-view",{staticClass:"condition-status",class:{completed:0==t.levelDetail.distance2}},[t._v(t._s(0==t.levelDetail.distance2?"已完成":"未完成"))])],1):t._e(),1==t.levelDetail.flag3?i("v-uni-view",{staticClass:"condition-item border-line border-bottom dis-flex flex-x-between flex-y-center"},[i("v-uni-view",{staticClass:"condition-detail"},[i("v-uni-view",{staticClass:"f-28 col-3 m-btm10"},[t._v(t._s("下级"+t.TextSubstitution.fxstext+"总人数")),i("v-uni-text",{staticClass:"upstand-text"},[t._v(t._s(t.levelDetail.upstandard3)+"人")])],1),i("v-uni-view",{staticClass:"f-24 col-9"},[t._v("当前还差"+t._s(t.levelDetail.distance3)+"人")])],1),i("v-uni-view",{staticClass:"condition-status",class:{completed:0==t.levelDetail.distance3}},[t._v(t._s(0==t.levelDetail.distance3?"已完成":"未完成"))])],1):t._e(),1==t.levelDetail.flag4?i("v-uni-view",{staticClass:"condition-item border-line border-bottom dis-flex flex-x-between flex-y-center"},[i("v-uni-view",{staticClass:"condition-detail"},[i("v-uni-view",{staticClass:"f-28 col-3 m-btm10"},[t._v(t._s("一级"+t.TextSubstitution.fxstext+"人数")),i("v-uni-text",{staticClass:"upstand-text"},[t._v(t._s(t.levelDetail.upstandard4)+"人")])],1),i("v-uni-view",{staticClass:"f-24 col-9"},[t._v("当前还差"+t._s(t.levelDetail.distance4)+"人")])],1),i("v-uni-view",{staticClass:"condition-status",class:{completed:0==t.levelDetail.distance4}},[t._v(t._s(0==t.levelDetail.distance4?"已完成":"未完成"))])],1):t._e(),1==t.levelDetail.flag5?i("v-uni-view",{staticClass:"condition-item border-line border-bottom dis-flex flex-x-between flex-y-center"},[i("v-uni-view",{staticClass:"condition-detail"},[i("v-uni-view",{staticClass:"f-28 col-3 m-btm10"},[t._v("自购订单金额"),i("v-uni-text",{staticClass:"upstand-text"},[t._v(t._s(t.levelDetail.upstandard5)+"元")])],1),i("v-uni-view",{staticClass:"f-24 col-9"},[t._v("当前还差"+t._s(t.levelDetail.distance5)+"元")])],1),i("v-uni-view",{staticClass:"condition-status",class:{completed:0==t.levelDetail.distance5}},[t._v(t._s(0==t.levelDetail.distance5?"已完成":"未完成"))])],1):t._e()],1)],1):t._e()],1)],1):t._e()],1)},s=[]},"5b90":function(t,e,i){"use strict";Object.defineProperty(e,"__esModule",{value:!0}),e.default=void 0;var a={data:function(){return{isShow:!0}},components:{},computed:{},onLoad:function(t){},methods:{showChange:function(){this.isShow=!this.isShow}},props:{backgroundType:{type:String,default:function(){return""}}}};e.default=a},6005:function(t,e,i){"use strict";Object.defineProperty(e,"__esModule",{value:!0}),e.default=s;var a=n(i("6b75"));function n(t){return t&&t.__esModule?t:{default:t}}function s(t){if(Array.isArray(t))return(0,a.default)(t)}},"679f":function(t,e,i){"use strict";var a;i.d(e,"b",(function(){return n})),i.d(e,"c",(function(){return s})),i.d(e,"a",(function(){return a}));var n=function(){var t=this,e=t.$createElement,i=t._self._c||e;return t.isShow?i("v-uni-view",{staticClass:"part-loading",style:"goods"===t.backgroundType?"":"background-color:#FFFFFF;"},[i("v-uni-view",{staticClass:"data-load"})],1):t._e()},s=[]},"6bd4":function(t,e,i){"use strict";i.r(e);var a=i("51a5"),n=i("98a1");for(var s in n)"default"!==s&&function(t){i.d(e,t,(function(){return n[t]}))}(s);i("f156");var l,o=i("f0c5"),r=Object(o["a"])(n["default"],a["b"],a["c"],!1,null,"fb742f2a",null,!1,a["a"],l);e["default"]=r.exports},7681:function(t,e,i){var a=i("24fb");e=a(!1),e.push([t.i,'.col-level[data-v-fb742f2a]{color:#ba721b}.current-level[data-v-fb742f2a]{overflow-x:hidden;height:%?370?%;background:linear-gradient(-29deg,#f44,#ff5b5b)}.upgrade-info[data-v-fb742f2a]{position:relative;min-height:calc(100vh - %?370?% + %?60?%);\nmin-height:calc(100vh - %?370?% + %?60?% - 44px);\nborder-radius:%?30?% %?30?% 0 0;margin-top:%?-60?%;padding:%?40?% %?30?% 0;box-sizing:border-box}.upgrade-info[data-v-fb742f2a]::after{content:" ";position:absolute;top:-20px;left:50%;-webkit-transform:translateX(-50%);transform:translateX(-50%);border-width:10px;border-style:solid;border-color:transparent transparent #fff transparent}.upgrade-info[data-v-fb742f2a] .part-loading{top:%?40?%}.user-level[data-v-fb742f2a]{padding-top:%?40?%}.user-info[data-v-fb742f2a]{margin-left:%?30?%}.user-info .user-avatar[data-v-fb742f2a]{width:%?54?%;height:%?54?%;display:block;margin-right:%?10?%;border-radius:50%}.level-info[data-v-fb742f2a]{height:%?54?%;background:#f6c115;border-radius:50px 0 0 50px;padding:0 %?24?%}.level-info .level-icon[data-v-fb742f2a]{width:%?26?%;height:%?26?%;display:block;vertical-align:bottom;margin-right:%?10?%}.level-list[data-v-fb742f2a]{margin:%?65?% %?100?% 0;white-space:nowrap;transition:-webkit-transform .4s;transition:transform .4s;transition:transform .4s,-webkit-transform .4s}.level-item[data-v-fb742f2a]{position:relative;display:inline-block;width:50%;-webkit-transform:translateX(-50%);transform:translateX(-50%)}.level-dot[data-v-fb742f2a]{width:%?20?%;height:%?20?%;border-radius:50%;background:#fff589;margin-right:0 auto;border:%?8?% solid #f75;margin-bottom:%?12?%;position:relative;z-index:2}.level-vip[data-v-fb742f2a]{width:%?59?%;height:%?65?%;display:block;margin-top:%?-60?%;position:relative;z-index:3}.level-text[data-v-fb742f2a]{color:#ffc0c0;margin-top:%?10?%}.item-line[data-v-fb742f2a]{position:absolute;top:%?12?%;left:0;right:0;height:%?10?%;-webkit-transform:translateX(50%);transform:translateX(50%);background:#f75}.up-title[data-v-fb742f2a]{color:#333;font-size:%?30?%;font-weight:700;margin-bottom:%?40?%}.up-title.dis-flex[data-v-fb742f2a]{margin-bottom:0}.up-content[data-v-fb742f2a]{padding-bottom:%?40?%}.up-title .col-9[data-v-fb742f2a]{font-weight:400}.commission-item[data-v-fb742f2a]{height:%?80?%;background:rgba(255,166,166,.2);padding:0 %?25?%;border-radius:%?20?%;margin-bottom:%?20?%}.commission-left .commission-icon[data-v-fb742f2a]{width:%?36?%;height:%?36?%;display:block;margin-right:%?20?%}.commission-text[data-v-fb742f2a]{color:#f44}.commission-right[data-v-fb742f2a]{color:#eb0000}.condition-item[data-v-fb742f2a]{min-height:%?160?%}.upstand-text[data-v-fb742f2a]{color:#ff5252}.condition-status[data-v-fb742f2a]{width:%?123?%;height:%?58?%;line-height:%?58?%;background:#f6f6f6;border-radius:%?29?%;color:#999;font-size:%?26?%;text-align:center}.condition-status.completed[data-v-fb742f2a]{color:#ba721b;background:#fff4bb}.goods-item[data-v-fb742f2a]{padding:%?12?% 0}.goods-item .goods-thumb[data-v-fb742f2a]{width:%?110?%;height:%?110?%;border-radius:%?8?%;display:block;margin-right:%?12?%}',""]),t.exports=e},"7eec":function(t,e,i){var a=i("24fb");e=a(!1),e.push([t.i,".part-loading[data-v-10df3a3f]{position:absolute;top:0;left:0;right:0;bottom:0;z-index:500}.data-load[data-v-10df3a3f]{position:absolute;top:50%;left:50%;margin:%?-30?% 0 0 %?-30?%;width:%?60?%;height:%?60?%;display:inline-block;padding:0;border-radius:100%;border:%?4?% solid;border-top-color:#ffd940;border-bottom-color:rgba(0,0,0,.2);border-left-color:#ffd940;border-right-color:rgba(0,0,0,.2);-webkit-animation:loader-data-v-10df3a3f 1s ease-in-out infinite;animation:loader-data-v-10df3a3f 1s ease-in-out infinite}@-webkit-keyframes loader-data-v-10df3a3f{from{-webkit-transform:rotate(0deg);transform:rotate(0deg)}to{-webkit-transform:rotate(1turn);transform:rotate(1turn)}}@keyframes loader-data-v-10df3a3f{from{-webkit-transform:rotate(0deg);transform:rotate(0deg)}to{-webkit-transform:rotate(1turn);transform:rotate(1turn)}}",""]),t.exports=e},8292:function(t,e,i){"use strict";var a=i("4ea4");i("99af"),i("c740"),i("a434"),Object.defineProperty(e,"__esModule",{value:!0}),e.default=void 0;var n=a(i("2909")),s=a(i("77ab")),l=a(i("efa8")),o=a(i("cb39")),r={data:function(){return{userinfo:{},current:-1,self_leave:"1",levelList:[],levelDetail:{},userlevel:{},loadlogo:!1,TextSubstitution:null}},components:{PartLoading:l.default,Loadlogo:o.default},computed:{},onLoad:function(t){uni.setNavigationBarColor({frontColor:"#ffffff",backgroundColor:"#FF5454"}),this.getLevelDetail(),this.TextSubstitution=uni.getStorageSync("TextSubstitution")},methods:{getLevelDetail:function(){var t=this;s.default._post_form("&p=distribution&do=disLvInfo",{},(function(e){var i,a=e.data.lv_list,s=a.findIndex((function(t){return t.id==e.data.dislevel})),l={avatar:e.data.avatar,nickname:e.data.nickname},o=e.data,r=null;(i=t.levelList).splice.apply(i,[0,a.length].concat((0,n.default)(a))),s=s>0?s:0,r=a[s],t.setData({self_leave:r.id,current:0-s,userlevel:r,userinfo:l,levelDetail:o,loadlogo:!0}),t.$nextTick((function(){t.$refs.partLoading.showChange()}))}))},getLeaveData:function(t){var e=this;s.default._post_form("&p=distribution&do=disLvInfo",{id:t},(function(t){var i=t.data;e.levelDetail=i,e.$nextTick((function(){e.$refs.partLoading.showChange()}))}))},levelChange:function(t){var e=arguments.length>1&&void 0!==arguments[1]?arguments[1]:0;this.$refs.partLoading.showChange(),this.current=0-t,this.getLeaveData(e)}}};e.default=r},"864b":function(t,e,i){"use strict";var a;i.d(e,"b",(function(){return n})),i.d(e,"c",(function(){return s})),i.d(e,"a",(function(){return a}));var n=function(){var t=this,e=t.$createElement,i=t._self._c||e;return i("v-uni-view",[i("v-uni-view",{staticClass:"loadlogo-container"},[i("v-uni-view",{staticClass:"loadlogo"},[i("v-uni-image",{staticClass:"image",attrs:{src:t.loadImage||t.imgfixUrls+"loadlogo.svg",mode:"aspectFill"}})],1)],1)],1)},s=[]},"98a1":function(t,e,i){"use strict";i.r(e);var a=i("8292"),n=i.n(a);for(var s in a)"default"!==s&&function(t){i.d(e,t,(function(){return a[t]}))}(s);e["default"]=n.a},aaa4:function(t,e,i){var a=i("7681");"string"===typeof a&&(a=[[t.i,a,""]]),a.locals&&(t.exports=a.locals);var n=i("4f06").default;n("0c06c5c9",a,!0,{sourceMap:!1,shadowMode:!1})},ac0e:function(t,e,i){"use strict";var a=i("4f29"),n=i.n(a);n.a},c6b2:function(t,e,i){"use strict";var a=i("0b44"),n=i.n(a);n.a},cb39:function(t,e,i){"use strict";i.r(e);var a=i("864b"),n=i("002c");for(var s in n)"default"!==s&&function(t){i.d(e,t,(function(){return n[t]}))}(s);i("c6b2");var l,o=i("f0c5"),r=Object(o["a"])(n["default"],a["b"],a["c"],!1,null,"23fdce49",null,!1,a["a"],l);e["default"]=r.exports},db90:function(t,e,i){"use strict";function a(t){if("undefined"!==typeof Symbol&&Symbol.iterator in Object(t))return Array.from(t)}i("a4d3"),i("e01a"),i("d28b"),i("a630"),i("d3b7"),i("3ca3"),i("ddb0"),Object.defineProperty(e,"__esModule",{value:!0}),e.default=a},efa8:function(t,e,i){"use strict";i.r(e);var a=i("679f"),n=i("083f");for(var s in n)"default"!==s&&function(t){i.d(e,t,(function(){return n[t]}))}(s);i("ac0e");var l,o=i("f0c5"),r=Object(o["a"])(n["default"],a["b"],a["c"],!1,null,"10df3a3f",null,!1,a["a"],l);e["default"]=r.exports},f156:function(t,e,i){"use strict";var a=i("aaa4"),n=i.n(a);n.a}}]);