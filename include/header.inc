<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<html>
<head>
<script type="text/javascript" src="js/json2.js"></script>
<script type="text/javascript" src="js/mktree.js"></script>
<script type="text/javascript" src="js/main_js.php"></script>
<meta http-equiv="content-type" content="text/html; charset=utf-8">
<link rel="stylesheet" type="text/css" href="css/stylesheet.css">
<link rel="stylesheet" type="text/css" href="css/mktree.css">
<title>YALA</title>
</head>
<body>
<div id="sideBarDiv" class="sideBarDiv">
	<div class="toolBar">
		[<a href="" onclick="callBackEnd('create_step1'); return false;">New</a>]&nbsp;
		[<a href="" onclick="callBackEnd('search_form'); return false;">Search</a>]&nbsp;
		[<a href="" onclick="refreshTree(); return false;">Refresh</a>]&nbsp;
		[<a href="" onclick="document.getElementById('treeDiv').style.display='none'; callBackEnd('logout'); return false;">Log Out</a>]&nbsp;
	</div>
	<div id="treeDiv" class="treeDiv">
		&nbsp;
	</div>
</div>
<div id="mainDiv" class="mainDiv">
	<!-- ACTION BAR BEGIN -->
	<div class="actionBar" id="actionBar">
		<button id="button_modify" onclick="processEntryForm('modify_entry')" disabled>Modify</button>
		<button id="button_new" onclick="processEntryForm('create_step3')" disabled>New</button>
		<button id="button_delete" onclick="deleteEntry()" disabled>Delete</button>
	</div>
	<!-- ACTION BAR END -->
	<div class="entryDiv" id="entryDiv">
		&nbsp;
	</div>
</div>
