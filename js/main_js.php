// vim:ft=javascript:foldmethod=marker:

/* Globals */
var jsutils = new jsutils();


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
		request.onreadystatechange = function() {
			if (request.readyState == 4 && request.status == 200) {

				if (request.responseText){

					callBackFunction(request.responseText);
				}
			}
		}
		request.send(null);
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
			refreshTree();
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

function getSelectedOptions(id) {
	var output = [];
	var el = document.getElementById(id);

	for (var i = 0; i < el.options.length; i++) {
		if (el.options[i].selected) {
			output.push(el.options[i].value);
		}
	}

	return output;
}

function callBackEnd(str, params) {
	var url = "back.php?do="+str;
	if (params != undefined)
		url += "&"+params;

	jsutils.ajax(url,
	function (html) {
		var mainDiv = jsutils.el("mainDiv");
		mainDiv.innerHTML = html;
		refreshTree();
	});
}

/* Refresh the tree by modifying only the deltas, don't re-generate */
function refreshTree() {
	// Temporarily does nothing, refreshing the dynamic tree is no easy :(

	return;
}

/* Duplicates the given object and places it right afterwards */
function dupObj(obj, cleanupInput) {
	var par = obj.parentNode;

	var newObj = obj.cloneNode(true);

	// Find the next sibling (of the same tagName), to insert before
	var nextObj = obj.nextSibling;
	while (nextObj != undefined &&
	       nextObj.nextSibling != undefined &&
	       nextObj.tagName != obj.tagName) {
		nextObj = nextObj.nextSibling;
	}

	if (cleanupInput) {
		// Clear the first input field in this object, if exists
		var NodeList = newObj.getElementsByTagName("input");
		if (NodeList.length > 0)
			NodeList[0].value = "";
	}

	par.insertBefore(newObj, nextObj);
}

function populateTree() {
	jsutils.el("treeDiv").style.visibility = 'hidden';
	jsutils.ajax("tree.php",
	function (html) {
		var treeDiv = jsutils.el("treeDiv");
		treeDiv.innerHTML = html;
		convertTrees();
		treeDiv.style.visibility = '';
	});

	return;
}

function afterLoad() {
	populateTree();
}

function viewEntry(dn) {
	currentEntry = dn;
	callBackEnd("view_entry", "dn="+dn);
}

function main() {
	var currentEntry;

	window.onload = function() { afterLoad(); }
}

/* ====== MAIN ====== */
main();
