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
	$name2oidCacheFile = NAME2OID_CACHEFILE.".".str_replace("/", "", $ldap_func->getServer());
	$objectclassesCacheFile = OBJECTCLASSES_CACHEFILE.".".str_replace("/", "", $ldap_func->getServer());

	if (DEBUG) print ("UNLINKING!\n");
	if (file_exists($name2oidCacheFile)) unlink($name2oidCacheFile);
	if (file_exists($objectclassesCacheFile)) unlink($objectclassesCacheFile);

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
		if ($_SESSION["yala"] != TRUE)
			throw new Exception("", ERROR_SESSION_SUPPORT_PROBLEM);

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

	try {
		$ldap_func = login();
	}
	catch (Exception $ex) {
		$_SESSION["login_error"] = "Login error: ".getErrString($ex->getCode(), $ex->getMessage());
		unset($_SESSION["ldap_server"]); // BAD! should be some other way!
?>
<script type="text/javascript">
window.location = "<?=$_SERVER["REQUEST_URI"]?>";
</script>
<?php
	}

	if ($do) {
		switch ($do) {
			case "reloadschema":
				flushCache($ldap_func);
				break;

			default:
				throw new Exception($do, ERROR_BAD_OP);
		}
	}

	if ($submit) { # If it's a form which was submitted (modify/del/add...)
		if (is_array($_POST)) {
			# First format the posted strings
			$post_vars = formatInputArray($_POST);
		}

		switch ($submit) {
			case "Anonymous Login":
			case "Login":  break;
			default: throw new Exception($submit, ERROR_BAD_OP);
		}
	}

	require INCLUDE_PATH."/footer.inc";
}

##### MAIN #####
main();
