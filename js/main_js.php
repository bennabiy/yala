// vim:ft=javascript:foldmethod=marker:
/* {{{ jsutils class */
function jsutils() {

	this.el		= el;
	this.ajax	= ajax;
	this.ajaxPost	= ajaxPost;
	this.urlencode	= urlencode;
	this.urldecode	= urldecode;

	function ajax(url, callBackFunction){

		var request = window.XMLHttpRequest ? new XMLHttpRequest() : new ActiveXObject("MSXML2.XMLHTTP.3.0");

		request.open("GET", url, true);
		request.setRequestHeader("Content-Type", "application/x-www-form-urlencoded"); 

		request.onreadystatechange = function(){

			if (request.readyState == 4 && request.status == 200) {

				if (request.responseText){

					callBackFunction(request.responseText);
				}
			}
		}
		request.send();
	}

	// Just like ajax() function but for POST
	function ajaxPost(url, data, callBackFunction){

		var request = window.XMLHttpRequest ? new XMLHttpRequest() : new ActiveXObject("MSXML2.XMLHTTP.3.0");

		request.open("POST", url, true);
		request.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
		request.setRequestHeader("Content-length", data.length);
		request.setRequestHeader("Connection", "close");

		request.onreadystatechange = function(){

			if (request.readyState == 4 && request.status == 200) {

				if (request.responseText){

					callBackFunction(request.responseText);
				}
			}
		}
		request.send(data);
	}

	function el(id) {
		return document.getElementById(id);
	}

	function urlencode(str) {
		str = escape(str);
		str = str.replace('+', '%2B');
		str = str.replace('%20', '+');
		str = str.replace('*', '%2A');
		str = str.replace('/', '%2F');
		str = str.replace('@', '%40');
		return str;
	}

	function urldecode(str) {
		str = str.replace('+', ' ');
		str = unescape(str);
		return str;
	}
} /* }}} */

function processEntryForm(action) {
	var formElements = document.forms.yalaForm.elements;
	var data = {};

	for (var i = 0; i < formElements.length; i++) {
		var el = formElements[i];

		if (el.value != "") {
			if (data[el.name] == undefined)
				data[el.name] = Array();
			
			data[el.name].push(el.value);
		}
	}
	
	var url = "back.php";
	jsutils.ajaxPost(url, "do="+action+"&data="+JSON.stringify(data),
		function (html) {
			jsutils.el("mainDiv").innerHTML = html;
		});
}

function deleteEntry() {
	if (confirm("Are you sure you want to delete this entry?")) {
		callBackEnd("delete_entry", "dn="+currentEntry);
	}
}

function doSearch() {
	var url = "back.php?do=search";

	var basedn = jsutils.urlencode(jsutils.el("search_basedn").value);
	var filter = jsutils.urlencode(jsutils.el("search_filter").value);
	var scope = jsutils.urlencode(jsutils.el("search_scope").value);

	url += "&basedn="+basedn+"&filter="+filter+"&scope="+scope;

	jsutils.ajax(url, 
	function (html) {
		var mainDiv = jsutils.el("mainDiv");
		mainDiv.innerHTML = html;
	});
}

function callBackEnd(str, params) {
	var url = "back.php?do="+str;
	if (params != undefined)
		url += "&"+params;

	jsutils.ajax(url,
	function (html) {
		var mainDiv = jsutils.el("mainDiv");
		mainDiv.innerHTML = html;
	});
}

function populateTree() {
	jsutils.ajax("tree.php",
	function (html) {
		var treeDiv = jsutils.el("treeDiv");
		treeDiv.innerHTML = html;
		convertTrees();
	});

	return;
}

function onload() {
	populateTree();
}

function viewEntry(dn) {
	currentEntry = dn;
	callBackEnd("view_entry", "dn="+dn);
}

/* ====== MAIN ====== */
var currentEntry;
var jsutils = new jsutils();

document.onload = onload;
