(window["webpackJsonp"]=window["webpackJsonp"]||[]).push([["pages-subPages-shareMaterials-index"],{"0f10":function(e,t,a){"use strict";var i=a("a976"),n=a.n(i);n.a},"1ef5":function(e,t,a){"use strict";var i=a("4ea4");a("4160"),a("c975"),a("a434"),a("a9e3"),a("d3b7"),a("ac1f"),a("5319"),a("841c"),a("159b"),Object.defineProperty(t,"__esModule",{value:!0}),t.default=void 0;var n=i(a("77ab")),s=(i(a("8c82")),{data:function(){return{phoneHight:null,e:{},goodsExtensionInfo:{},posterList:{},imgArray:[],allImgLength:0,client:null,imgArrays:[],fomr:!0}},onLoad:function(e){var t=this;t.e=e,t.client=Number(n.default.getClientType()),t.init(e.id,e.type),console.info("client",t.client)},onShow:function(){},methods:{init:function(e,t){var a=this;uni.getSystemInfo({success:function(e){a.phoneHight=e.windowHeight+"px"}});var i=0;"3"==t?i=1:"4"==t?i=2:"6"==t?i=3:"7"==t&&(i=7),a.getGoodsExtensionInfo(e,i),a.getPoster(e,t)},previewImage:function(e){},qx:function(){var e=this;if(e.imgArray.length==e.allImgLength)e.setData({imgArray:[]});else if(e.imgArray.length!=e.allImgLength){e.setData({imgArray:[]});var t=[];t.push(e.posterList.url);for(var a=0;a<e.goodsExtensionInfo.extension_img.length;a++)t.push(e.goodsExtensionInfo.extension_img[a]);e.setData({imgArray:t})}},addImage:function(e,t,a){var i=this;if(3===e)if(-1==i.imgArray.indexOf(t))i.imgArray.push(t);else for(var n=0;n<i.imgArray.length;n++)i.imgArray[n]==t&&i.imgArray.splice(n,1);else uni.previewImage({current:i.imgArrays[a],urls:i.imgArrays})},getExtension:function(){var e=this;n.default.clipboard(e.goodsExtensionInfo.extension_text),uni.setClipboardData({data:e.goodsExtensionInfo.extension_text,success:function(){uni.showToast({icon:"none",title:"复制成功",duration:2e3})}})},saveImg:function(){var e=this;uni.showLoading({title:"保存中"}),e.queue(e.imgArray).then((function(e){uni.hideLoading(),n.default.showSuccess("保存成功")})).catch((function(e){wx.hideLoading(),n.default.showError(e),console.log(e)}))},queue:function(e){var t=this,a=Promise.resolve();return e.forEach((function(e,i){a=a.then((function(){return t.downloadUrl(e)}))})),a},downloadUrl:function(e){return-1==e.search("https")&&(e=e.replace("http","https")),new Promise((function(t,a){uni.downloadFile({url:e,success:function(e){var i=e.tempFilePath;uni.saveImageToPhotosAlbum({filePath:i,success:function(e){t(e)},fail:function(t){a(e)}})},fail:function(e){a(e)}})}))},getPoster:function(e,t){var a=this,i={id:e,type:t,bg_img:""};n.default._post_form("&do=Poster",i,(function(e){a.posterList=e.data,a.imgArrays.push(e.data.url)}))},getGoodsExtensionInfo:function(e,t){var a=this,i={id:e,type:t};n.default._post_form("&p=goods&do=getGoodsExtensionInfo",i,(function(e){a.goodsExtensionInfo=e.data;for(var t=0;t<e.data.extension_img.length;t++)a.imgArrays.push(e.data.extension_img[t]);a.allImgLength=1+e.data.extension_img.length}))}}});t.default=s},"2bb8":function(e,t,a){"use strict";a.r(t);var i=a("1ef5"),n=a.n(i);for(var s in i)"default"!==s&&function(e){a.d(t,e,(function(){return i[e]}))}(s);t["default"]=n.a},"85bb":function(e,t,a){"use strict";var i;a.d(t,"b",(function(){return n})),a.d(t,"c",(function(){return s})),a.d(t,"a",(function(){return i}));var n=function(){var e=this,t=e.$createElement,a=e._self._c||t;return a("v-uni-scroll-view",{staticClass:"shareMaterials",style:{height:e.phoneHight},attrs:{"scroll-y":!0,"scroll-x":!1}},[a("far-bottom"),a("v-uni-view",{staticClass:"shareMaterials_package"},[a("v-uni-view",{staticClass:"shareMaterials_title"},[a("v-uni-view",{staticClass:"shareMaterials_title_package"},[a("v-uni-view",{staticClass:"shareMaterials_level dis-flex"},[a("v-uni-view",{staticClass:"shareMaterials_level_left"},[a("v-uni-view",{staticClass:"line ver_mid"}),a("v-uni-text",{staticClass:"level_text ver_mid"},[e._v("推广文案")])],1),a("v-uni-view",{staticClass:"level_button",on:{click:function(t){arguments[0]=t=e.$handleEvent(t),e.getExtension.apply(void 0,arguments)}}},[a("v-uni-text",[e._v("复制文案")])],1)],1),a("v-uni-scroll-view",{staticClass:"shareMaterials_title_main",attrs:{"scroll-y":!0,"scroll-x":!1}},[a("v-uni-text",{staticClass:"shareMaterials_title_main_tetx"},[e._v(e._s(e.goodsExtensionInfo.extension_text))])],1)],1)],1),a("v-uni-view",{staticClass:"shareMaterials_mian"},[a("v-uni-view",{staticClass:"shareMaterials_mian_package"},[a("v-uni-view",{staticClass:"shareMaterials_level dis-flex"},[a("v-uni-view",{staticClass:"shareMaterials_level_left"},[a("v-uni-view",{staticClass:"line ver_mid"}),a("v-uni-text",{staticClass:"level_text ver_mid"},[e._v("选择分享图")])],1)],1),a("v-uni-view",{staticClass:"shareMaterials_mian_body dis-flex-star"},e._l(e.imgArrays,(function(t,i){return a("v-uni-view",{key:i,staticClass:"imgMb"},[a("v-uni-view",{staticClass:"imgMb_image",on:{click:function(a){arguments[0]=a=e.$handleEvent(a),e.addImage(e.client,t,i)}}},[a("v-uni-image",{staticClass:"imgMb_image",attrs:{src:t,mode:"aspectFill"}}),3===e.client?a("v-uni-view",{staticClass:"chackImg",class:-1!=e.imgArray.indexOf(t)?"have":"none"},[a("v-uni-text",{staticClass:"iconfont icon-check"})],1):e._e()],1)],1)})),1),3===e.client?a("v-uni-view",{staticClass:"shareMaterials_mian_foot dis-flex"},[a("v-uni-view",{staticClass:"shareMaterials_mian_foot_left",on:{click:function(t){arguments[0]=t=e.$handleEvent(t),e.qx.apply(void 0,arguments)}}},[a("v-uni-view",{staticClass:"qx ver_mid",class:e.imgArray.length==e.allImgLength?"have":"none"},[a("v-uni-text",{staticClass:"iconfont icon-check"})],1),a("v-uni-text",{staticClass:"qx_text ver_mid"},[e._v("全选")])],1),a("v-uni-view",{staticClass:"shareMaterials_mian_foot_button",on:{click:function(t){arguments[0]=t=e.$handleEvent(t),e.saveImg.apply(void 0,arguments)}}},[a("v-uni-text",[e._v("保存到相册")])],1)],1):e._e()],1)],1)],1)],1)},s=[]},a934:function(e,t,a){"use strict";a.r(t);var i=a("85bb"),n=a("2bb8");for(var s in n)"default"!==s&&function(e){a.d(t,e,(function(){return n[e]}))}(s);a("0f10");var r,o=a("f0c5"),l=Object(o["a"])(n["default"],i["b"],i["c"],!1,null,"54ee9bbc",null,!1,i["a"],r);t["default"]=l.exports},a976:function(e,t,a){var i=a("bfc4a");"string"===typeof i&&(i=[[e.i,i,""]]),i.locals&&(e.exports=i.locals);var n=a("4f06").default;n("1eb34321",i,!0,{sourceMap:!1,shadowMode:!1})},bfc4a:function(e,t,a){var i=a("24fb");t=i(!1),t.push([e.i,".none[data-v-54ee9bbc]{background:#fff}.none > uni-text[data-v-54ee9bbc]{color:#333}.have[data-v-54ee9bbc]{background:#f44}.have > uni-text[data-v-54ee9bbc]{color:#fff}.chackImg[data-v-54ee9bbc]{position:absolute;top:%?10?%;right:%?10?%;width:%?30?%;height:%?30?%;text-align:center;border-radius:50%}.chackImg > uni-text[data-v-54ee9bbc]{line-height:%?30?%;font-size:%?24?%}.qx_text[data-v-54ee9bbc]{margin-left:%?12?%;line-height:%?50?%;font-size:%?24?%;color:#333}.ver_mid[data-v-54ee9bbc]{vertical-align:middle}.qx[data-v-54ee9bbc]{display:inline-block;width:%?30?%;height:%?30?%;border-radius:50%;text-align:center}.qx > uni-text[data-v-54ee9bbc]{font-size:%?24?%;line-height:%?30?%}.shareMaterials_mian_foot_button[data-v-54ee9bbc]{width:%?170?%;height:%?50?%;background:#f44;border-radius:%?25?%;text-align:center}.shareMaterials_mian_foot_button > uni-text[data-v-54ee9bbc]{font-size:%?24?%;color:#fff;line-height:%?50?%}.shareMaterials_mian_foot[data-v-54ee9bbc]{margin-top:%?30?%}.shareMaterials_mian_body[data-v-54ee9bbc]{flex-wrap:wrap;margin-top:%?24?%}.imgMb_image[data-v-54ee9bbc]{position:relative;width:%?198?%;height:%?198?%;display:inline-block;margin:auto;background:grey}.imgMb[data-v-54ee9bbc]{margin-top:%?17?%;display:inline-block;width:33%}.shareMaterials_mian_package[data-v-54ee9bbc]{padding:%?40?% %?30?% %?30?% %?30?%}.shareMaterials_mian[data-v-54ee9bbc]{margin-top:%?20?%;width:%?690?%;background:#fff;border-radius:%?10?%}.shareMaterials_title_main[data-v-54ee9bbc]{margin-top:%?28?%;width:%?628?%;height:%?263?%}.shareMaterials_title_main_tetx[data-v-54ee9bbc]{font-size:%?24?%;color:#666}.level_button[data-v-54ee9bbc]{width:%?140?%;height:%?50?%;background:#f44;border-radius:%?25?%;text-align:center}.level_button > uni-text[data-v-54ee9bbc]{font-size:%?24?%;color:#fff;line-height:%?50?%}.dis-flex[data-v-54ee9bbc]{display:flex;justify-content:space-between}.dis-flex-star[data-v-54ee9bbc]{display:flex;justify-content:flex-start}.ver_mid[data-v-54ee9bbc]{vertical-align:middle}.level_text[data-v-54ee9bbc]{margin-left:%?15?%;font-size:%?28?%;color:#111}.line[data-v-54ee9bbc]{display:inline-block;width:%?6?%;height:%?26?%;background:#f44;border-radius:%?3?%}.shareMaterials_title_package[data-v-54ee9bbc]{padding:%?30?% %?30?% %?45?% %?30?%}.shareMaterials[data-v-54ee9bbc]{font-size:0;background:#f8f8f8}.shareMaterials_package[data-v-54ee9bbc]{padding:%?20?% %?30?% %?116?% %?30?%}.shareMaterials_title[data-v-54ee9bbc]{width:%?690?%;height:%?416?%;background:#fff;border-radius:%?10?%}",""]),e.exports=t}}]);