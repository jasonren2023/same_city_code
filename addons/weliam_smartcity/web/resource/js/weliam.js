var version = new Date().getTime();
var versm = "weliam_smartcity";
var myconfig =  {
	path: '../addons/'+versm+'/web/resource/',
	alias:{
		'jquery': 'js/jquery-1.11.1.min',
		'jquery.ui': 'components/jquery/jquery-ui-1.10.3.min',
		'jquery.form': 'components/jquery/jquery.form',
		'jquery.validate': 'components/jquery/jquery.validate.min',
		'jquery.confirm': 'components/confirm/jquery-confirm',
		'jquery.contextMenu' : 'js/contextMenu/jquery.contextMenu',
		'clipboard': 'components/clipboard/clipboard.min',
		'select2' : 'js/select2.min',
		'switchery' : 'components/switchery/switchery',
		'layui' : 'components/layui/layui',
		'layer' : 'components/layer/layer',
		'scrollLoading' : 'components/scrollLoading/jquery.scrollLoading.min',
		'g2' : 'components/g2/g2.min',
		'data-set' : 'components/g2/data-set.min',
		'goods_selector': 'js/goods_selector',
	},
	map:{
		'js':'.js?v='+version,
		'css':'.css?v='+version
	},
	css: {
		'jquery.confirm': 'components/confirm/jquery-confirm',
		'jquery.contextMenu' : 'js/contextMenu/jquery.contextMenu',
		'select2' : 'css/select2.min',
		'switchery' : 'components/switchery/switchery',
		'layui' : 'components/layui/css/layui',
		'layer' : 'components/layer/skin/layer',
	},preload:['jquery']
}

function ReadFile(data) {
	versm = data;
}
var xhr = new XMLHttpRequest();
xhr.onload = function () {
	ReadFile(xhr.responseText);
};
try {
	xhr.open("get", "../../../../wlversion.txt", true);
	xhr.send();
}
catch (ex) {
	console.log("catch")
	ReadFile(ex.message);
}
var myrequire = function(arr, callback) {
	var newarr = [ ];
	myconfig.path = '../addons/'+versm+'/web/resource/';
	$.each(arr, function(){
		var js = this;
		if( myconfig.css[js]){
			var css = myconfig.css[js].split(',');
			$.each(css,function(){
				newarr.push( "css!" +  myconfig.path + this + myconfig.map['css']);
			});
		}
		var jsitem = this;
		if( myconfig.alias[js]){
			jsitem = myconfig.alias[js];
		}
		newarr.push(  myconfig.path + jsitem + myconfig.map['js']);
	});
	require(newarr,callback);
}