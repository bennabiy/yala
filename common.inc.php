<?
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
	if (session_destroy()) {
?>
Logged out, click <a href="">here</a> to login again...
<?
	}
	else
		echo "Error logging out!"; // Shouldn't really happen..
} # }}}

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



?>
