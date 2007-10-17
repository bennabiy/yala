<?php

#
# This files contains general functions and definitions...
#

#
# DEFINITIONS
#

# General
define("YALA_VERSION", "0.35b");

# ERRORS
define("ERROR_BAD_OP"			,1);
define("ERROR_FEW_ARGUMENTS"		,2);
define("ERROR_LDAP_CANT_CONNECT"	,3);
define("ERROR_LDAP_CANT_SEARCH"		,4);
define("ERROR_CACHE_CANT_READ"		,5);
define("ERROR_CACHE_CANT_WRITE"		,6);
define("ERROR_TLS_BUT_V3"		,7);
define("ERROR_TLS_CANT_CONNECT"		,8);
define("ERROR_CANT_READ_TREE"		,9);
define("ERROR_LDAP_OP_FAILED"		,10);
define("ERROR_SCHEMA_PROBLEM"		,11);
define("ERROR_LDAP_NOT_SUPPORTED"	,12);
define("ERROR_TLS_NOT_SUPPORTED"	,13);
define("ERROR_SESSION_SUPPORT_PROBLEM"	,14);


#
# FUNCTIONS
#

function exitOnError($error_num, $additional_str = "") {

	$str = "";

	switch ($error_num) {
		case ERROR_BAD_OP: $str = "Unknown operation was chosen"; 
			if ($additional_str) $str .= " - ".$additional_str;
			break;
		case ERROR_FEW_ARGUMENTS: $str = "Weird, function didn't get enough arguments!"; 
			break;
		case ERROR_LDAP_CANT_CONNECT: $str = "Cannot connect to ldap server (".$additional_str.")";
			break;
		case ERROR_LDAP_CANT_SEARCH: $str = "Search problem..";
			break;
		case ERROR_CACHE_CANT_READ: $str = "Can't open cache file (".$additional_str.") for reading!";
			break;
		case ERROR_CACHE_CANT_WRITE: $str = "Can't open cache file (".$additional_str.") for writing!";
			break;
		case ERROR_TLS_BUT_V3: $str = "TLS Connects require LDAP v3. Fix the 'LDAP_VERSION' setting in conf.inc.php";
			break;
		case ERROR_TLS_CANT_CONNECT: $str = "Couldn't establish TLS connection: ".$additional_str."<BR>Hint 1: Is the certificate made for <I>".$_SESSION["ldap_server"]."</I> (Common-Name field)?<BR>Hint 2: If you don't want TLS support, comment out LDAP_TLS in config.inc.php";
			if (function_exists("logout")) logout();
			break;
		case ERROR_CANT_READ_TREE: $str = "Cannot read the tree. Maybe the Base DN (".$additional_str.") is wrong?";
			break;
		case ERROR_LDAP_OP_FAILED: $str = "The LDAP operation failed: ".$additional_str;
			break;
		case ERROR_SCHEMA_PROBLEM: $str = "Something is weird about the current schema: ".$additional_str;
			break;
		case ERROR_LDAP_NOT_SUPPORTED: $str = "LDAP is not supported by
this PHP installation:<BR><OL><LI>make sure you have php-ldap package
installed</LI><LI>check out your php.ini configuration</LI></OL>";
			break;
		case ERROR_TLS_NOT_SUPPORTED: $str = "LDAP over TLS is not
supported by this php installation";
			break;
		case ERROR_SESSION_SUPPORT_PROBLEM: $str = "Error using PHP sessions.
check your PHP session configuration (i.e. /etc/php),
and make sure there's enough free space on the disk";
			break;
		default: $str = "Unknown Error!";
	}

?>
<CENTER>
<TABLE CLASS="error">
<TR><TD><IMG SRC="images/error.png"></TD><TD>
<?
	echo "ERROR #".$error_num.": ".$str;
?>
</TD></TR>
</TABLE>
</CENTER>
<?
	include INCLUDE_PATH."/footer.inc";
	exit;
}

/* {{{ sanity_checks()
	Makes some sanity checks at beginning to make sure we're ok (read: sane)
*/
function sanity_checks() {
	if (!function_exists("ldap_connect")) {
		exitOnError(ERROR_LDAP_NOT_SUPPORTED);
	}
} /* }}} */

/* {{{ array_val_to_lower()
	Helper function that does something like array_change_key_case but
	for the values.
	Contributed by Sven Carstens
*/
function array_val_to_lower($an_array) {
	if (is_array($an_array)) {
		foreach ($an_array as $key => $value)
			$new_array[$key] = strtolower($value);

		return $new_array;
	}
	else
		return $an_array;
} /* }}} */
 

/* {{{ getEntryType() returns the entry first entry type in $entry_types hash 
	matches the objectclass list
*/
function getEntryType($objectclasses) {
	global $entry_types;

	if (isset($entry_types) && is_array($entry_types)) {
		reset($entry_types);
		while(list($attr, $value) = each($entry_types)) {
			$a = array_diff(array_val_to_lower($value), array_val_to_lower($objectclasses));
			if (!count($a))
				return $attr; # Yup, we're of entry_type $attr
		}
	}

	return "default";

} /* }}} */


/* {{{ formatOutputStr() formats a string taken from the directory
       to be displayed well on the webpage. i.e. changes " to \"
       (returns the new string)
*/
function formatOutputStr($str) {
	$newvalue = htmlspecialchars($str);

	if ($newvalue != $str) {
#		echo "DEBUG: ".$str." -> ".$newvalue."<BR>\n";
		$str = $newvalue;
	}

	return $str;
	
} /* }}} */


/* {{{ formatInputStr() formats a string taken from the user
       to be stored well on the directory. i.e. stripslashes()
*/
function formatInputStr($str) {
	# If php is configured to auto-add slashes, remove'em
	if (get_magic_quotes_gpc())
		return stripslashes($str);
	else
		return $str;
} /* }}} */


/* {{{ formatInputArray() uses formatInputStr() to format a whole
	array of strings came from _POST
	It returns the formatted new string 
*/
function formatInputArray($a) {
	if (!is_array($a)) return array();

	reset($a);
	while ( list($attr, $values) = each($a) ) {
		
		# Skip if not an array: means it's not an value of the
		# directory
		if (!is_array($values)) continue;

		for ($i = 0; $i < count($values); $i++) {
			# Strip the slashes
			$newvalue = formatInputStr($values[$i]);

			if ($newvalue != $a[$attr][$i]) {
				$a[$attr][$i] = $newvalue;
			}
		}
	}
	return $a;
} /* }}} */


