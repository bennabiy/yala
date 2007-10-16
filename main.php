<?php
if (file_exists("config.inc.php")) require "config.inc.php";
	else die("Config file not found.. read INSTALL file for installation instructions");
require "general.inc.php";
require "ldapfunc.inc.php";
require	"htmloutput.inc.php";

$javascript = "";

# {{{ login() returns binddn and bindpw if true, otherwise
# shows the login form 
function login() {
	# First make the sanity checks
	$_SESSION["yala"] = TRUE;
	sanity_checks();

	# If $ldap_server && $ldap_port are set
	if (array_key_exists("ldap_server", $_SESSION) && array_key_exists("ldap_port", $_SESSION) && $_SESSION["ldap_server"] && $_SESSION["ldap_port"]) {
		# First connect..
		$ldap_func = new LDAPFunc($_SESSION["ldap_server"], $_SESSION["ldap_port"], $_SESSION["ldap_tls"]) or exitOnError(ERROR_LDAP_CANT_CONNECT, $_SESSION["ldap_server"].":".$_SESSION["ldap_port"]);

		# Let's try to login, if successful skip the next stuff
		$bind = $ldap_func->bind($_SESSION["ldap_binddn"], $_SESSION["ldap_bindpw"]);
		if ($bind) {
			return $ldap_func;
		}
			echo "Bind problem: ".ldap_error($ldap_func->ldap_conn)."<BR>";
	}

	# Get settings either from session or from defaults
	if (isset($_SESSION["ldap_server"]))
		$ldap_server	= $_SESSION["ldap_server"];
	else
		if (defined("LDAP_SERVER"))
			$ldap_server	= LDAP_SERVER;
		else
			$ldap_server 	= NULL;

	if (isset($_SESSION["ldap_port"]))
		$ldap_port	= $_SESSION["ldap_port"];
	else
		if (defined("LDAP_PORT"))
			$ldap_port	= LDAP_PORT;
		else
			$ldap_port	= NULL;

	if (isset($_SESSION["ldap_binddn"]))
		$ldap_binddn	= $_SESSION["ldap_binddn"];
	else
		if (defined("LDAP_BINDDN"))
			$ldap_binddn	= LDAP_BINDDN;
		else
			$ldap_binddn	= NULL;

	if (isset($_SESSION["ldap_basedn"]))
		$ldap_basedn	= $_SESSION["ldap_basedn"];
	else
		if (defined("LDAP_BASEDN"))
			$ldap_basedn	= LDAP_BASEDN;
		else
			$ldap_basedn	= NULL;

	if (isset($_SESSION["ldap_tls"]))
		$ldap_tls	= $_SESSION["ldap_tls"];
	else
		if (defined("LDAP_TLS"))
			$ldap_tls	= LDAP_TLS;
		else
			$ldap_tls	= NULL;

	require INCLUDE_PATH."/login_form.inc";
} # }}}

# {{{ logout() - deletes the cookies (Session info..)
function logout() {
	global $javascript;
	if (session_destroy()) 
		echo "Logged out, click <A HREF=\"".MAINFILE."\" TARGET=\"right\">here</A> to login again...<BR>";
	else
		echo "Error logging out!<BR>";
} # }}}

# {{{ flushCache() function just deletes the old cache files 
function flushCache($ldap_func) {

	# Set the filenames of cache files
	$name2oidCacheFile = NAME2OID_CACHEFILE.".".str_replace("/", "", $ldap_func->get_server());
	$objectclassesCacheFile = OBJECTCLASSES_CACHEFILE.".".str_replace("/", "", $ldap_func->get_server());

	if (DEBUG) print ("UNLINKING!\n");
	if (file_exists($name2oidCacheFile)) unlink($name2oidCacheFile);
	if (file_exists($objectclassesCacheFile)) unlink($objectclassesCacheFile);

} # }}}

# {{{ getCachedSchema() - wraps $ldap_func->getSchema() function to add 
# caching. We use the neat serialize() function php has..
#
# Returns the values into $name2oid & $objectclasses
function getCachedSchema($ldap_func, &$name2oid, &$objectclasses) {

	# Set the filenames of cache files
	$name2oidCacheFile = NAME2OID_CACHEFILE.".".str_replace("/", "", $ldap_func->get_server());
	$objectclassesCacheFile = OBJECTCLASSES_CACHEFILE.".".str_replace("/", "", $ldap_func->get_server());
	$cacheIsFine = TRUE;

	/* Can we get it from cache?
	1. If cache files exist *AND*
	2. If CACHE_EXPIRES seconds haven't passed yet since the file's mtime
	*/

	if (!file_exists($name2oidCacheFile) ||
	    !file_exists($objectclassesCacheFile))
		$cacheIsFine = FALSE; # Cache files don't exist, cache is NOT fine
	else {
		if ((filemtime($name2oidCacheFile)+CACHE_EXPIRES<time()) ||
		(filemtime($objectclassesCacheFile)+CACHE_EXPIRES<time())) {
			# Cache expired, then cache is NOT fine
			$cacheIsFine = FALSE; 
		}
	}

	
	if ($cacheIsFine) {
		if ($f = @fopen($name2oidCacheFile, "r")) {
			$str = fread($f, filesize($name2oidCacheFile)) or exitOnError(ERROR_CACHE_CANT_READ, $name2oidCacheFile);
			$name2oid = unserialize($str);
			fclose($f);
		}
		else
			exitOnError(ERROR_CACHE_CANT_READ, $name2oidCacheFile);

		if ($f = @fopen($objectclassesCacheFile, "r")) {
			$str = fread($f, filesize($objectclassesCacheFile)) or exitOnError(ERROR_CACHE_CANT_READ, $objectclassesCacheFile);
			$objectclasses = unserialize($str);
			fclose($f);
		}
		else
			exitOnError(ERROR_CACHE_CANT_READ, $objectclassesCacheFile);
	}
	else { # Cache is no good
		/* Read the schema from LDAP */
		$ldap_func->getSchemaHash($name2oid, $objectclasses);

		/* Save it in cache */
		umask(077); # We don't want the schema world readable..
		if ($f = @fopen($name2oidCacheFile, "w")) {
			fwrite($f, serialize($name2oid)) or exitOnError(ERROR_CACHE_CANT_WRITE, $name2oidCacheFile);
			fclose($f);
		}
		else
			exitOnError(ERROR_CACHE_CANT_WRITE, $name2oidCacheFile);

		if ($f = @fopen($objectclassesCacheFile, "w")) {
			fwrite($f, serialize($objectclasses)) or exitOnError(ERROR_CACHE_CANT_WRITE, $objectclassesCacheFile);
			fclose($f);
		}
		else
			exitOnError(ERROR_CACHE_CANT_WRITE, $objectclassesCacheFile);
	}
}
# }}}

# {{{ removeDupAttributes() takes a MayMust array, and removes duplicate items
#      - If same attribute appears in both May & Must, leave it in Must
#      - Duplicates by OID, not only by name
function removeDupAttributes($mayMustArray, $attributes2oid) {
	$oids = array();
	$returnArray = array();
	$returnArray["may"] = array();
	$returnArray["must"] = array();

	foreach ( array("must", "may") as $mayOrMust) {
		foreach ($mayMustArray[$mayOrMust] as $attr) {
			$oid = $attributes2oid[strtolower($attr)];

			// If we've never yet seen $oid, it's not a dup, yey :)
			if (!in_array($oid, $oids)) {
				array_push($oids, $oid);
				array_push($returnArray[$mayOrMust], $attr);
			}
			// If it's a dup, do nothing..
		}
	}

	return $returnArray;
}
# }}}

# {{{ viewEntry() displays the given entry's attributes and values,
# according to the schema of its objectclasses
# If no entry is given we display an empty entry of the specified object claases
#
# For each attribute we'll show $empties values in addition
function viewEntry($ldap_func, $entry, $empties = 0, $dn = "", $objectclasses = array()) {
	global $attr_desc; # FIXME Global is ugly

	# Arg check
	if (!$ldap_func || !$entry && !count($objectclasses)) exitOnError(ERROR_FEW_ARGUMENTS);

	$data = array();
	$attributes["must"] = array();
	$attributes["may"] = array();

	# Get the schema contents
	getCachedSchema($ldap_func, $name2oid, $schema_objectclasses);
	// TODO should be cached
	$ldap_func->getSchemaHash_attributeTypes($attributes2oid, $schema_attributetypes);

	if ($entry) { # Existing entry; read it.
		$sr = ldap_read($ldap_func->ldap_conn, formatInputStr($entry), "(objectclass=*)") or exitOnError(ERROR_LDAP_CANT_SEARCH);
		$data = ldap_get_entries($ldap_func->ldap_conn, $sr) or exitOnError(ERROR_LDAP_CANT_SEARCH);

	}
	else { # Empty entry; create a fake entry with objectclasses coming from user input ($objectclasses)
		$data[0]["objectclass"] = array();
		foreach ($objectclasses as $objectclass) {
			array_push($data[0]["objectclass"], $objectclass);
		}
		$data[0]["objectclass"]["count"] = count($objectclasses);
	}


	# Get the attrs of each objectclass and merge them (an entry might have several objectclasses)
	for ($i = 0; $i < $data[0]["objectclass"]["count"]; $i++) {
		$objectclass = $data[0]["objectclass"][$i];

		# Recursively get the may/must attributes of $objectclass and it's
		# superiors.
		$maymust = $ldap_func->getMayMust($objectclass, $schema_objectclasses, $name2oid);

		$attributes["must"] = array_merge($attributes["must"], $maymust["must"]);
		$attributes["may"] = array_merge($attributes["may"], $maymust["may"]);
	}

	# Remove dups + sort
/*	$attributes["must"] = array_unique($attributes["must"]);
	$attributes["may"]  = array_unique($attributes["may"]);*/
	$attributes = removeDupAttributes($attributes, $attributes2oid);
	asort($attributes["must"]);
	asort($attributes["may"]);


	# ENTRY TITLE COMES HERE
	$htmloutput = new HTMLOutput();
	if ($entry)
		$htmloutput->viewHeader($entry);
	else
		$htmloutput->viewHeader();
	
	# Allow adding/removing empty fields
	# TODO We don't allow this on new-entry-mode because I'm lazy.
	if ($entry) {
		$str = "One <A HREF=\"".MAINFILE."?do=view_entry&amp;entry=".urlencode($entry)."&amp;empties=".($empties+1)."\">more</A> / <A HREF=\"".MAINFILE."?do=view_entry&amp;entry=".urlencode($entry)."&amp;empties=".($empties-1)."\">less</A> empty value field (for each attribute)";
		$htmloutput->viewTitle($str);
	}


	# Show the dn before anything else
	# existsing-entry: get the dn from $data
	# try $dn, else: empty
	if (array_key_exists(0, $data) && array_key_exists("dn", $data[0]))
		$dn = $data[0]["dn"];
	if (!isset($dn)) $dn = "";

	$htmloutput->viewInnerHeader();
	$htmloutput->viewInnerRowDN($dn);

	# Print the must+may attributes and their values
	foreach (array("must", "may") as $attr_type) {
		foreach ($attributes[$attr_type] as $attr) {

			if (!array_key_exists(strtolower($attr), $attributes2oid)) 
				continue; // shouldn't happen

			$oid = $attributes2oid[strtolower($attr)];
			if (!array_key_exists($oid, $schema_attributetypes))
				continue; //shouldn't happen

			# See if there's a description to this specific attribute
			$acronym_body = "";
			
			/* Add attr description from the schema if exists */
			if (array_key_exists("desc", $schema_attributetypes[$oid])) {
				$acronym_body = $schema_attributetypes[$oid]["desc"];
			}

			/* Internaly set description? it'll override.. */
			if (isset($attr_desc) && is_array($attr_desc))
				if (array_key_exists(strtolower($attr), $attr_desc))
					$acronym_body = $attr_desc[strtolower($attr)];

			if (array_key_exists(strtolower($attr), $data[0]))
				$val  = $data[0][strtolower($attr)];
			else {
				$val = NULL; // No value found..

				// Maybe the value hides in a twin-attribute?	
				// (i.e. userid instead of uid / vice versa)
				$synonyms = $ldap_func->getSynonymAttrs($attributes2oid, strtolower($attr));
				foreach ($synonyms as $synonym) {
					if ($data[0][$synonym]) { // FOUND!
						$attr = $synonym;
						$val = $data[0]["uid"];
					}
				}
			}

			# Show all the existing values (if none, at least one empty!) 
			# + $empties empty_values in addition
			for ($j = 0; ($j < ( max($val["count"], 1) + $empties)); $j++) {
				
				if ($j + 1 > $val["count"]) {
					$value = ""; // No more values here
				}
				else {
					$value = formatOutputStr($val[$j]);
				}
				$htmloutput->viewInnerRow($attr, $value, $attr_type == "must", $acronym_body);
			}
		}
	}
	$htmloutput->viewInnerFooter();
	$htmloutput->viewFooter($entry);
}
# }}}

# {{{ modifyEntry() gets the modifications and decides what to do...
function modifyEntry($ldap_func, $post_vars) {
	$htmloutput = new HTMLOutput();
	$modified = 0; # Becomes 1 if anything happens to the entry
	$add_hash = array();
	$del_hash = array();
	$replace_hash = array();

	if (!$ldap_func || !is_array($post_vars)) exitOnError(ERROR_FEW_ARGUMENTS);
	$dn = formatInputStr($post_vars["entry"]);

	$sr = ldap_read($ldap_func->ldap_conn, $dn, "(objectClass=*)") or exitOnError(ERROR_LDAP_CANT_SEARCH);
	$entry = ldap_first_entry($ldap_func->ldap_conn, $sr) or exitOnError(ERROR_LDAP_CANT_SEARCH);
	$attributes = ldap_get_attributes($ldap_func->ldap_conn, $entry);


	#
	# Create add_hash
	# 

	# Pass on each posted attribute
	while ( list($attr, $posted_values) = each($post_vars) ) {
		# We care about this var only if it's an array
		if (!is_array($posted_values)) continue;

		# If $attr wasn't found in ldap (means that it has no 
		# value in ldap yet) - add all values to the 'add_hash'
		if (!isset($attributes[$attr])) {
			foreach ($posted_values as $posted_value) {
				# Skip empty values
				if ($posted_value == "") continue;
				if (!array_key_exists($attr, $add_hash))
					$add_hash[$attr] = array();
				array_push($add_hash[$attr], $posted_value);
			}
			continue;
		}

		$ldap_values = ldap_get_values($ldap_func->ldap_conn, $entry, $attr);

		foreach ($posted_values as $posted_value) {
			# Skip empty values
			if ($posted_value == "") continue;
			if (!in_array($posted_value, $ldap_values)) {
				if (!array_key_exists($attr, $add_hash))
					$add_hash[$attr] = array();
				array_push($add_hash[$attr], $posted_value);
			}
		}
	}


	#
	# Create del_hash
	#

	# Now pass on each attribute from ldap and see if it has a real
	# value in $posted_vars
	for ($i = 0; $i < $attributes["count"]; $i++) {
		$attr = $attributes[$i];
		$ldap_values = ldap_get_values($ldap_func->ldap_conn, $entry, $attr);

		for ($j = 0; $j < $ldap_values["count"]; $j++) {
			if (!in_array($ldap_values[$j], $post_vars[$attr]))  {
				if (!array_key_exists($attr, $del_hash))
					$del_hash[$attr] = array();
				array_push($del_hash[$attr], $ldap_values[$j]);
			}
		}
	}

	#
	# Create replace_hash
	#
	
	# Now we have two hashes, add_hash and del_hash. If we both del a 
	# value of attribute 'x' and add another value - we'd rather REPLACE
	# this value instead of deleting and adding (as long as attribute 
	# 'x' has only a single value!).. (MUST values cannot be deleted, 
	# that is)
	if (is_array($del_hash))
	while ( list($attr, $values) = each($del_hash) ) {

		# If it has more than one value in the directory, we cannot
		# replace it.
		if ($attributes[$attr]["count"] > 1) continue;

		# does the same attribute from del_hash exist in add_hash too?
		if (array_key_exists($attr, $add_hash) && count($add_hash[$attr])) {
			$add = array_shift($add_hash[$attr]);
			$del = array_shift($del_hash[$attr]);

			if (!array_key_exists($attr, $replace_hash))
				$replace_hash[$attr] = array();
			array_push($replace_hash[$attr], $add);
		}
	}

	$operations = array("del", "add", "replace");

	# Now commit the changes, first del, then add, then replace
	$htmloutput->resultsHeader($dn);
	$failed = 0; # Becomes 1 if anything failed
	foreach ($operations as $op) {
		if ($failed) break;

		$varname = $op."_hash"; #either del/add/replace_hash
		if (!count(${$varname})) continue; # If empty, skip
		$modified = 1;
		$htmloutput->resultsTitle($op." attribute..");
		$htmloutput->resultsInnerHeader();

		reset(${$varname});
		while ((list($attr, $values) = each(${$varname})) and !$failed) {
			# FIXME make it commit each operation only once
			foreach($values as $value) {
				$entry = array();

				$entry[$attr] = $value;

				switch($op) {
					case "del": $result = @ldap_mod_del($ldap_func->ldap_conn, $dn, $entry); break;
					case "add": $result = @ldap_mod_add($ldap_func->ldap_conn, $dn, $entry); break;
					case "replace": $result = @ldap_mod_replace($ldap_func->ldap_conn, $dn, $entry); break;
				}
				$htmloutput->resultsInnerRow($attr,
formatOutputStr($value), $result);
				if (!$result) {
					$failed = 1;
					break;
				}
			}
		}
		$htmloutput->resultsInnerFooter();
	}
	if (!$modified) echo
"	<TR><TD ALIGN=\"center\">Nothing was modified!</TD</TR>\n";
	$htmloutput->resultsFooter();
	if ($failed)
		exitOnError(ERROR_LDAP_OP_FAILED, ldap_error($ldap_func->ldap_conn));
} # }}}

# {{{ deleteEntry() deletes the given entry from the directory
function deleteEntry($ldap_func, $dn) {
	$htmloutput = new HTMLOutput();

	
	$dn = formatInputStr($dn);

	$result = @ldap_delete($ldap_func->ldap_conn, $dn);
	$htmloutput->resultsHeader($dn);
	$htmloutput->resultsTitle("Deleting entry..");
	$htmloutput->resultsInnerHeader();
	$htmloutput->resultsInnerRow(NULL, NULL, $result);

	$htmloutput->resultsInnerFooter();
	$htmloutput->resultsFooter();
	if (!$result)
		exitOnError(ERROR_LDAP_OP_FAILED, ldap_error($ldap_func->ldap_conn));
}
# }}}

# {{{ newEntry() creates a new LDAP entry according to the given parameters
function newEntry($ldap_func, $post_vars) {
	$htmloutput = new HTMLOutput();

	$dn = formatInputStr($post_vars["dn"]);
	$htmloutput->resultsHeader($dn);
	$htmloutput->resultsTitle("Adding entry..");
	$htmloutput->resultsInnerHeader();

	# Let's construct $entry, which will contain the future attrs/vals
	$entry = array();
	while (list($attr, $values) = each($post_vars)) {

		# Skip if the value is not an array- it means that it's not
		# a variable ment to be an attribute
		if (!is_array($values)) continue;

		foreach ($values as $value) {
			# Skip if value is empty
			if (!$value) continue;

			if (!array_key_exists($attr, $entry))
				$entry[$attr] = array();

			array_push($entry[$attr], $value);
			$htmloutput->resultsInnerRow($attr,
formatOutputStr($value), -1);
		}
	}
	$result = @ldap_add($ldap_func->ldap_conn, $dn, $entry);
	$htmloutput->resultsInnerRow(NULL, NULL, $result);
	$htmloutput->resultsInnerFooter();
	$htmloutput->resultsFooter();
	if (!$result) 
		exitOnError(ERROR_LDAP_OP_FAILED, ldap_error($ldap_func->ldap_conn));

} # }}}

# {{{ search_form() displays a search form
function search_form($ldap_basedn) {
	include INCLUDE_PATH."/search_form.inc";
} # }}}

# {{{ search() search and return the results as an array
function search($ldap_func, $post_vars) {

	$binddn = $post_vars["basedn"];
	$filter = $post_vars["filter"];
	$scope  = $post_vars["scope"];

	if (!$binddn || !$filter || !$scope)
		exitOnError(ERROR_FEW_ARGUMENTS);

	$info = $ldap_func->search($binddn, $filter, $scope);

	echo "<H2><CENTER>".$info["count"]." result(s)</CENTER></H2><BR>\n";
	if (!$info["count"]) return;

	for ($i = 0; $i < $info["count"]; $i++) {
		$dn = $info[$i]["dn"];
		echo "<A HREF=\"".MAINFILE."?do=view_entry&amp;entry=".urlencode($dn)."\">".$dn."</A><BR>\n";
	}
} # }}}

# {{{ choose_entrytype() displays a form for choosing a new entry type
function choose_entrytype($entry_types, $ldap_func, $parent) {

	$ldap_func->getSchemaHash($name2oid, $schema_objectclasses);

	require INCLUDE_PATH."/choose_entrytype_form.inc";
} # }}}

# {{{ new_form() displays a form for adding a new entry
function new_form($ldap_func, $entry_types, $post_vars) {
	$entry_type = $post_vars["entry_type"] or exitOnError(ERROR_FEW_ARGUMENTS);

	$objectclasses_list = array();

	# If we have the parent DN, put ",<parent DN>" as the dn
	if ($post_vars["dn"]) $dn = ",".$post_vars["dn"];
	else
		$dn = "";

	# If custom, list of objectclasses is given as an argument
	if ($entry_type == "custom") {
		if (!count($post_vars["custom_objectclasses"])) exitOnError(ERROR_FEW_ARGUMENTS);
		$objectclasses_list = $post_vars["custom_objectclasses"];
	}
	else # If a specific entry type was chosen, list is in $entry_types
		$objectclasses_list = $entry_types[$entry_type];

	viewEntry($ldap_func, "", 0, $dn, $objectclasses_list);

	
} # }}}

# {{{ modrdn_form() displays a form for modifying the dn
function modrdn_form($ldap_func, $entry) {
	$entry = formatInputStr($entry);
	if (eregi("^([^,]+),(.*)$", $entry, $regs)) {
		$rdn		= $regs[1];
		$superior	= $regs[2];
	}
	include INCLUDE_PATH."/modrdn_form.inc";

} # }}}

# {{{ modrdn() - Modify the RDN and/or the Parent ( = rename )
function modrdn($ldap_func, $post_vars) {
	$htmloutput = new HTMLOutput;
	$entry		= formatInputStr($post_vars["entry"]);
	$newrdn		= formatInputStr($post_vars["newrdn"]);
	$deleteoldrdn	= formatInputStr($post_vars["deleteoldrdn"]);
	$newsuperior	= formatInputStr($post_vars["newsuperior"]);

	$result = ldap_rename($ldap_func->ldap_conn, $entry, $newrdn, $newsuperior, $deleteoldrdn);
	$htmloutput->resultsHeader($entry);
	$htmloutput->resultsTitle("Modify DN..");
	$htmloutput->resultsInnerHeader();
	$htmloutput->resultsInnerRow("dn", formatOutputStr($entry), -1);
	$htmloutput->resultsInnerRow("newrdn", formatOutputStr($newrdn), -1);
	$htmloutput->resultsInnerRow("deleteoldrdn",
formatOutputStr($deleteoldrdn), -1);
	$htmloutput->resultsInnerRow("newsuperior",
formatOutputStr($newsuperior), -1);
	$htmloutput->resultsInnerRow(NULL, NULL, $result);
	$htmloutput->resultsInnerFooter();
	$htmloutput->resultsFooter();
	if (!$result)
		exitOnError(ERROR_LDAP_OP_FAILED, ldap_error($ldap_func->ldap_conn));
} # }}}

###############################################
# Here we BEGIN


session_start();

if (array_key_exists("submit", $_POST))
	$submit		= $_POST["submit"];
else
	$submit = NULL;

if (array_key_exists("do", $_GET))
	$do		= $_GET["do"];
else
	$do = NULL;

if (isset($_POST["ldap_server"])) {

	# Before we use the php session feature, make sure it works :)
	if ($_SESSION["yala"] != TRUE) exitOnError(ERROR_SESSION_SUPPORT_PROBLEM);

	# If we're just after the login form:
	$_SESSION["ldap_server"] = $_POST["ldap_server"];
	$_SESSION["ldap_port"] = $_POST["ldap_port"];
	$_SESSION["ldap_basedn"] = $_POST["ldap_basedn"];
	$_SESSION["ldap_binddn"] = $_POST["ldap_binddn"];
	$_SESSION["ldap_bindpw"] = formatInputStr($_POST["ldap_bindpw"]);
	if (array_key_exists("ldap_tls", $_POST))
		$_SESSION["ldap_tls"]	= TRUE;
	else
		$_SESSION["ldap_tls"]	= FALSE;
}


if ($submit == "Login" || $submit == "Anonymous Login" || $submit == "Delete" || $submit == "New" || $submit == "Modify" || $submit == "Modrdn" || $do == "logout") {
	$javascript .= "top.left.location.reload();\n"; # Refresh after login
}

require INCLUDE_PATH."/header.inc";

# If anonymous login, act as if there is no binddn nor bindpw
if ($submit == "Anonymous Login") {
	$_SESSION["ldap_binddn"] = ""; $_SESSION["ldap_bindpw"] = "";
}
$ldap_func = login();

# Sanity checks on parameters
if (array_key_exists("empties", $_GET)) {
	$empties = $_GET["empties"]; 
	if ($empties < 0) $empties = 0; elseif ($empties > 5) $empties = 5;
}
else
	$empties = NULL;


if (DEBUG) echo $empties;
if ($do) {
	switch ($do) {
		case "reloadschema": flushCache($ldap_func); break;
		case "logout": logout(); break;
		case "search_form": search_form($_SESSION["ldap_basedn"]); break;
		case "modrdn_form": modrdn_form($ldap_func, $_GET["entry"]); break;
		case "view_entry": viewEntry($ldap_func, $_GET["entry"], $empties); break;
		case "choose_entrytype":
			if (array_key_exists("parent", $_GET))
				$parent = $_GET["parent"];
			else
				$parent = "";
			choose_entrytype($entry_types, $ldap_func, $parent);

			break;

		default: exitOnError(ERROR_BAD_OP, $do);
	}
}

if ($submit) { # If it's a form which was submitted (modify/del/add...)
	if (is_array($_POST)) {
		# First format the posted strings
		$post_vars = formatInputArray($_POST);
	}

	switch ($submit) {
		case "Modrdn": modrdn($ldap_func, $post_vars); break;
		case "Modify": modifyEntry($ldap_func, $post_vars); break;
		case "Delete": deleteEntry($ldap_func, $post_vars["entry"]); break;
		case "New": newEntry($ldap_func, $post_vars); break;
		case "Search": search($ldap_func, $post_vars); break;
		case "Create": new_form($ldap_func, $entry_types, $post_vars); break;
		case "Anonymous Login":
		case "Login": echo "<H2>Welcome! You're logged in.<BR>In case you don't see the tree on the left frame, <A HREF=\"javascript:top.location.reload();\">refresh</A> manually...</H2>"; break;
		default: exitOnError(ERROR_BAD_OP, $submit);
	}
}

require INCLUDE_PATH."/footer.inc";


