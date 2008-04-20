<?php
# vim:set foldmethod=marker:
if (file_exists("config.inc.php")) require "config.inc.php";
	else die("Config file not found.. read INSTALL file for installation instructions");
require "general.inc.php";
require	"common.inc.php";
require "ldapfunc.inc.php";
require	"htmloutput.inc.php";

# {{{ flushCache() function just deletes the old cache files 
function flushCache($ldap_func) {

	# Set the filenames of cache files
	$name2oidCacheFile = NAME2OID_CACHEFILE.".".str_replace("/", "", $ldap_func->get_server());
	$objectclassesCacheFile = OBJECTCLASSES_CACHEFILE.".".str_replace("/", "", $ldap_func->get_server());

	if (DEBUG) print ("UNLINKING!\n");
	if (file_exists($name2oidCacheFile)) unlink($name2oidCacheFile);
	if (file_exists($objectclassesCacheFile)) unlink($objectclassesCacheFile);

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

function main() {
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

	require INCLUDE_PATH."/header.inc";

	# If anonymous login, act as if there is no binddn nor bindpw
	if ($submit == "Anonymous Login") {
		$_SESSION["ldap_binddn"] = ""; $_SESSION["ldap_bindpw"] = "";
	}
	$ldap_func = login();

	if ($do) {
		switch ($do) {
			case "reloadschema": flushCache($ldap_func); break;
			case "modrdn_form": modrdn_form($ldap_func, $_GET["entry"]); break;

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
			case "Anonymous Login":
			case "Login":  break;
			default: exitOnError(ERROR_BAD_OP, $submit);
		}
	}

	require INCLUDE_PATH."/footer.inc";
}

##### MAIN #####
main();
