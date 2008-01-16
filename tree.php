<?php
# vim:foldmethod=marker

if (file_exists("config.inc.php")) require "config.inc.php";
	else die("Config file not found.. read INSTALL file for installation instructions");

require "ldapfunc.inc.php";
require "general.inc.php";
require "tree.inc.php";
require "htmloutput.inc.php";

function connect_to_ldap() {
	if (!array_key_exists("ldap_server", $_SESSION)) die("Not connected!");
	$ldap_func = new LDAPFunc($_SESSION["ldap_server"], $_SESSION["ldap_port"], $_SESSION["ldap_tls"]) or die("Cannot Connect!");
	$ldap_func->bind($_SESSION["ldap_binddn"], $_SESSION["ldap_bindpw"]) or die("Can't bind");

	return $ldap_func;
}

function main() {
	global $tree;
	session_start();

	$ldap_func = connect_to_ldap();
	$tree = new LdapTree($ldap_func, $_SESSION["ldap_basedn"]);
	$htmloutput = new HTMLOutput();

	require INCLUDE_PATH."/tree_header.inc";

	require INCLUDE_PATH."/toolbar.inc";

?><ul class="mktree"><?
	$htmloutput->viewTree($tree->getTreeArray());
?></ul><?

	require INCLUDE_PATH."/footer.inc";
}

main();

?>
