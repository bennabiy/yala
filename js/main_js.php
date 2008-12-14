// vim:ft=javascript:foldmethod=marker:

/* Globals */
var g_jsutils = new jsutils();
var g_treeExpandedItems = {};
var g_iterate = 0;

/* Constants */
var MIN_WIN_HEIGHT = 300;

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
	g_jsutils.ajaxPost(url, "do="+action+"&data="+JSON.stringify(data),
		function (html) {
			g_jsutils.el("entryDiv").innerHTML = html;
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

	var basedn = g_jsutils.urlencode(g_jsutils.el("search_basedn").value);
	var filter = g_jsutils.urlencode(g_jsutils.el("search_filter").value);
	var scope = g_jsutils.urlencode(g_jsutils.el("search_scope").value);

	url += "&basedn="+basedn+"&filter="+filter+"&scope="+scope;

	g_jsutils.ajax(url, 
	function (html) {
		var div = g_jsutils.el("entryDiv");
		div.innerHTML = html;
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

function callBackEnd(str, params, refresh) {
	var url = "back.php?do="+str;
	if (params != undefined)
		url += "&"+params;

	g_jsutils.ajax(url,
	function (html) {
		var div = g_jsutils.el("entryDiv");
		div.innerHTML = html;
		if (refresh)
			refreshTree();
	});
}

function expandBranches() {
	var dn;
	var el;

	for (var dn in g_treeExpandedItems) {
		el = document.getElementById(g_iterate+dn);
		if (el != undefined) {
			el.className = nodeOpenClass;
		}
	}
}

function refreshTree() {
	g_iterate++;
	populateTree(g_iterate);

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

function populateTree(iterate) {
	g_jsutils.el("treeDiv").style.visibility = 'hidden';

	g_jsutils.ajax("tree.php?iterate="+iterate,
	function (html) {
		var treeDiv = g_jsutils.el("treeDiv");
		treeDiv.innerHTML = html;
		convertTrees();
		expandBranches();
		treeDiv.style.visibility = '';
	});

	return;
}

function onResize() {
	var windowHeight;

	if (document.documentElement != undefined)
		windowHeight = document.documentElement.clientHeight;

	if (windowHeight == undefined || windowHeight < MIN_WIN_HEIGHT)
		windowHeight = MIN_WIN_HEIGHT;

	var divsHeight = windowHeight - 65;
	divsHeight += "px";

	g_jsutils.el("entryDiv").style.height = divsHeight;
	g_jsutils.el("treeDiv").style.height = divsHeight;
}

function onLoad() {
	onResize()
	populateTree(0);
}

/* Set them on or off... */
function actionButtons(on, exists) {
	btn_mod = document.getElementById("button_modify");
	btn_del = document.getElementById("button_delete");
	btn_new = document.getElementById("button_new");
	if (on) {
		btn_mod.disabled = btn_del.disabled = false;
		if (exists) 
			btn_new.disabled = false;
		else
			btn_new.disabled = true;
	}
	else {
		btn_mod.disabled = btn_del.disabled = btn_new.disabled = true;
	}
}

function viewEntry(dn) {
	currentEntry = dn;
	actionButtons(true, true)
	callBackEnd("view_entry", "dn="+dn, false);
}

function main() {
	var currentEntry;

	window.onload = function() { onLoad(); }
	window.onresize = function() { onResize(); }
}

/* ====== MAIN ====== */
main();
