<?php
# vim:set foldmethod=marker:

if (file_exists("config.inc.php")) require "config.inc.php";
	else die("Config file not found.. read INSTALL file for installation instructions");
require "general.inc.php";
require	"common.inc.php";
require "ldapfunc.inc.php";
require	"htmloutput.inc.php";

# {{{ search_form() displays a search form
function search_form($ldap_basedn) {
	include INCLUDE_PATH."/search_form.inc";
} # }}}

function sanity() {
	if (!isset($_SESSION["ldap_basedn"])) {
		exit;
	}
}

# {{{ create_step1() displays a form for choosing a new entry type
function create_step1($entry_types, $ldap_func, $parent) {
	$ldap_func = login();

	$ldap_func->getSchemaHash($name2oid, $schema_objectclasses);

	require INCLUDE_PATH."/choose_entrytype_form.inc";
} # }}}

# {{{ modrdn_form() displays the rename dn form
function modrdn_form($entry) {
	$entry = formatInputStr($entry);
	if (eregi("^([^,]+),(.*)$", $entry, $regs)) {
		$rdn		= $regs[1];
		$superior	= $regs[2];
	}
	include INCLUDE_PATH."/modrdn_form.inc";
} # }}}

# {{{ modrdn() - Modify the RDN and/or the Parent ( = rename )
function modrdn($json) {
	$htmloutput	= new HTMLOutput;
	$ldap_func	= login();

	$json		= formatInputStr($json);
	$data		= json_decode($json, true);

	$dn		= $data["dn"][0];
	$newrdn		= $data["newrdn"][0];
	$deleteoldrdn	= $data["deleteoldrdn"][0];
	$newsuperior	= $data["newsuperior"][0];

	$result = ldap_rename($ldap_func->getConn(), $dn, $newrdn, $newsuperior, $deleteoldrdn);
	$htmloutput->resultsHeader($dn);
	$htmloutput->resultsTitle("Modify DN..");
	$htmloutput->resultsInnerHeader();
	$htmloutput->resultsInnerRow("dn", formatOutputStr($dn), -1);
	$htmloutput->resultsInnerRow("newrdn", formatOutputStr($newrdn), -1);
	$htmloutput->resultsInnerRow("deleteoldrdn", formatOutputStr($deleteoldrdn), -1);
	$htmloutput->resultsInnerRow("newsuperior", formatOutputStr($newsuperior), -1);
	$htmloutput->resultsInnerRow(NULL, NULL, $result);
	$htmloutput->resultsInnerFooter();
	$htmloutput->resultsFooter();
	if (!$result)
		throw new Exception(ldap_error($ldap_func->getConn()), ERROR_LDAP_OP_FAILED);
} # }}}

# {{{ search() search and return the results as an array
function search() {
	$ldap_func = login();

	$binddn = $_REQUEST["basedn"];
	$filter = $_REQUEST["filter"];
	$scope  = $_REQUEST["scope"];

	if (!$binddn || !$filter || !$scope)
		throw new Exception("", ERROR_FEW_ARGUMENTS);

	$info = $ldap_func->search($binddn, $filter, $scope);

	echo "<h2>".$info["count"]." result(s)</h2>\n";
	if (!$info["count"]) return;

	for ($i = 0; $i < $info["count"]; $i++) {
		$dn = $info[$i]["dn"];
?>
<a href="" onclick="viewEntry('<?=urlencode($dn)?>'); return false"><?=$dn?></a><br>
<?
	}
} # }}}

# {{{ viewEntry() displays the given entry's attributes and values,
# according to the schema of its objectclasses
# If no entry is given we display an empty entry of the specified object claases
#
function viewEntry($entry, $dn = "", $objectclasses = array()) {
	global $attr_desc;

	$ldap_func = login();

	# Arg check
	if ( (!isset($ldap_func)) || (!$entry && !count($objectclasses)) )
		throw new Exception("", ERROR_FEW_ARGUMENTS);

	$data = array();
	$attributes["must"] = array();
	$attributes["may"] = array();

	# Get the schema contents
	getCachedSchema($ldap_func, $name2oid, $schema_objectclasses);
	// TODO should be cached
	$ldap_func->getSchemaHash_attributeTypes($attributes2oid, $schema_attributetypes);

	if ($entry) { # Existing entry; read it.
		$sr = ldap_read($ldap_func->getConn(), formatInputStr($entry), "(objectclass=*)");
		if (!$sr)
			throw new Exception("", ERROR_LDAP_CANT_SEARCH);

		$data = ldap_get_entries($ldap_func->getConn(), $sr);
		if (!$data)
			throw new Exception("", ERROR_LDAP_CANT_SEARCH);

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
	$htmloutput->viewHeader();
	
	if ($entry) {
		$htmloutput->viewTitle($entry);
	}

	# Show the dn before anything else
	# existsing-entry: get the dn from $data
	# try $dn, else: empty
	if ($dn == null) {
		if (count($data) && isset($data[0]["dn"]))
			$dn = $data[0]["dn"];
		else
			$dn = "";
	}

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
					if (array_key_exists($synonym, $data[0])) { // FOUND!
						$attr = $synonym;
						$val = $data[0]["uid"];
					}
				}
			}

			# Show all the existing values (if none, at least one empty!) 
			for ($j = 0; ($j < ( max($val["count"], 1))); $j++) {
				
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

# {{{ create_step2() displays a form for adding a new entry
function create_step2($entry_type, $parent = null, $objectclasses_list = array()) {
	global $entry_types;

	# If we have the parent DN, put ",<parent DN>" as the dn
	if ($parent) $dn = ",".$parent;
	else
		$dn = "";

	# If a specific entry type was chosen, 
	# get the objectclasses list is in $entry_types
	if ($entry_type != "custom")
		$objectclasses_list = $entry_types[$entry_type];

	viewEntry("", $dn, $objectclasses_list);
} # }}}

# {{{ create_step3() processes the submitted form, creating a new entry
function create_step3($json) {
	$ldap_func = login();
	$htmloutput = new HTMLOutput();

	$json = formatInputStr($json);
	$data = json_decode($json, true);

	$dn = $data["dn"][0];
	unset($data["dn"]); # We don't need it now when it's in $dn

	$htmloutput->resultsHeader($dn);
	$htmloutput->resultsTitle("Adding entry..");
	$htmloutput->resultsInnerHeader();

	# Let's construct $entry, which will contain the future attrs/vals
	$entry = array();
	while (list($attr, $values) = each($data)) {

		# Skip if the value is not an array- it means that it's not
		# a variable ment to be an attribute
		if (!is_array($values))
			continue;

		foreach ($values as $value) {
			# Skip if value is empty
			if (!$value)
				continue;

			if (!isset($entry[$attr]))
				$entry[$attr] = array();

			array_push($entry[$attr], $value);
			$htmloutput->resultsInnerRow($attr, formatOutputStr($value), -1);
		}
	}
	$result = @ldap_add($ldap_func->getConn(), $dn, $entry);
	$htmloutput->resultsInnerRow(NULL, NULL, $result);
	$htmloutput->resultsInnerFooter();
	$htmloutput->resultsFooter();
	if (!$result) 
		throw new Exception(ldap_error($ldap_func->getConn()), ERROR_LDAP_OP_FAILED);

} # }}}

# {{{ deleteEntry() deletes the given entry from the directory
function deleteEntry($dn) {
	$htmloutput = new HTMLOutput();
	$ldap_func = login();
	
	$dn = formatInputStr($dn);

	$result = @ldap_delete($ldap_func->getConn(), $dn);
	$htmloutput->resultsHeader($dn);
	$htmloutput->resultsTitle("Deleting entry..");
	$htmloutput->resultsInnerHeader();
	$htmloutput->resultsInnerRow(NULL, NULL, $result);

	$htmloutput->resultsInnerFooter();
	$htmloutput->resultsFooter();
	if (!$result)
		throw new Exception(ldap_error($ldap_func->getConn()), ERROR_LDAP_OP_FAILED);
}
# }}}

# {{{ modifyEntry() gets the modifications and decides what to do...
function modifyEntry($json) {
	$htmloutput = new HTMLOutput();
	$ldap_func = login();

/*	$json = str_replace('\"', '"', $json);
	$json = str_replace("\'", "'", $json);*/
	$json = formatInputStr($json);
	$data = json_decode($json, true);

	$dn = $data["dn"][0];
	unset($data["dn"]); # We don't need it now when it's in $dn

	$modified = 0; # Becomes 1 if anything happens to the entry
	$add_hash = array();
	$del_hash = array();
	$replace_hash = array();

	if (!$ldap_func || !is_array($data))
		throw new Exception("", ERROR_FEW_ARGUMENTS);

	$sr = ldap_read($ldap_func->getConn(), $dn, "(objectClass=*)");
	if (!$sr)
		throw new Exception("", ERROR_LDAP_CANT_SEARCH);

	$entry = ldap_first_entry($ldap_func->getConn(), $sr);
	if (!$entry)
		throw new Exception("", ERROR_LDAP_CANT_SEARCH);

	$attributes = ldap_get_attributes($ldap_func->getConn(), $entry);


	#
	# Create add_hash
	# 

	# Pass on each posted attribute
	while ( list($attr, $posted_values) = each($data) ) {
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

		$ldap_values = ldap_get_values($ldap_func->getConn(), $entry, $attr);

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
	# value in the posted data; if posted data misses a value, we want to
	# remove it from LDAP..
	for ($i = 0; $i < $attributes["count"]; $i++) {
		$attr = $attributes[$i];
		$ldap_values = ldap_get_values($ldap_func->getConn(), $entry, $attr);

		for ($j = 0; $j < $ldap_values["count"]; $j++) {

			if ( !isset($data[$attr]) ||
			   !in_array($ldap_values[$j], $data[$attr]) ) {
				# Value $ldap_values[$j] wasn't found in posted data, remove then!

				if (!isset($del_hash[$attr]))
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
					case "del": $result = @ldap_mod_del($ldap_func->getConn(), $dn, $entry);
					break;
					case "add": $result = @ldap_mod_add($ldap_func->getConn(), $dn, $entry);
					break;
					case "replace": $result = @ldap_mod_replace($ldap_func->getConn(), $dn, $entry);
					break;
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
		throw new Exception(ldap_error($ldap_func->getConn()), ERROR_LDAP_OP_FAILED);
} # }}}

# {{{ main()
function main() {
	global $entry_types;

	session_start();

	sanity();

	if (isset($_REQUEST["do"])) {
		try {
			switch ($_REQUEST["do"]) {
				case "search_form":
					search_form($_SESSION["ldap_basedn"]);
				break;
				case "logout":
					logout();
				break;
				case "create_step1":
					if (isset($_REQUEST["parent"]))
						$parent = $_REQUEST["parent"];
					else
						$parent = "";
				create_step1($entry_types, $ldap_func, $parent);
				break;
				case "create_step2":
					create_step2($_REQUEST["entry_type"], $_REQUEST["parent"], split(",", $_REQUEST["objectclasses"]));
				break;
				case "create_step3":
					create_step3($_REQUEST["data"]);
				break;
				case "search":
					search();
				break;
				case "view_entry": 
					viewEntry($_REQUEST["dn"]);
				break;
				case "delete_entry":
					deleteEntry($_REQUEST["dn"]);
				break;
				case "modify_entry":
					modifyEntry($_REQUEST["data"]);
				break;

				case "modrdn_form":
					modrdn_form($_REQUEST["dn"]);
				break;

				case "modrdn":
					modrdn($_REQUEST["data"]);
				break;

			}
		}
		catch (Exception $ex) {
			$htmloutput = new HTMLOutput();
			$htmloutput->errorDialog($ex);
		}
	}
}
# }}}


##### MAIN #####

main();
?>
