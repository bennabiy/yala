<?php
# vim:foldmethod=marker

class LdapTree {
	var $treeArray = array();
	var $iconsHash = array();
	var $ldap_conn;

	function __construct($ldap_func, $basedn) {
		global $entry_types;
		$this->ldap_conn = $ldap_func->ldap_conn;

		$this->treeArray = $this->mkArray($basedn);

		/* Make the icons hash */
		if (isset($entry_types) && is_array($entry_types))
			$this->iconsHash = $this->createIconsHash($entry_types);
	}

	/** {{{ getList
	 * @param basedn basedn
	 * @return an ldap-array with list of entries directly under $basedn
	*/
	function getList($basedn) {
		$ldapResults = ldap_list($this->ldap_conn, $basedn, "(objectClass=*)", array("dn", "objectclass"));

		if (!$ldapResults) {
			exitOnError(ERROR_CANT_READ_TREE, $base_dn);
		}

		$info = ldap_get_entries($this->ldap_conn, $ldapResults);
		if (!$info["count"])
			return array("count" => 0);
		
		return $info;
	} /* }}} */

	/** {{{ mkArray
	 *
	*/
	function mkArray($basedn, $ancestor = true) {
		$ar = array();

		$info = $this->getList($basedn);

		/* If it's a leaf: break the recursion */
		if ($info["count"] == 0) {
			return array();
		}

		// A node: add all the children recursively..
		for ($i = 0; $i < $info["count"]; $i++) {
			if (isset($info[$i]["dn"])) {
				$dn = $info[$i]["dn"];
				$objcls = $info[$i]["objectclass"];

				$node = $this->mkArray($dn, false);
				$node["count"] = count($node);
				$node["dn"] = $dn;

				unset($objcls["count"]);
				$node["entryType"] = getEntryType($objcls);

				array_push($ar, $node);
			}
		}

		if ($ancestor) { // Only in the ancestor call
			$ar["count"] = count($ar);
			$ar["dn"] = $basedn;
		}

		return $ar;
	} /* }}} */

	/** {{{ createIconsHash
	  * Prepare a hash of icons and sizes, i.e.:
	  * $icons["user"]["filename"] = "user.png";
	  * $icons["user"]["size"] = "width=22 height=20"
	  * $icons["open_user"]["filename"] = "open_user.png";
	  * $icons["open_user"]["size"] = "width=22 height=20"
	  * @param entry_types The hash from the configuration..
	  * @return the hash..
	 */
	function createIconsHash($entry_types) {
		$icons = array();

		foreach (array_keys($entry_types) as $key) {
			$closedFilename	= IMAGES_PATH."/icons/%TYPE%".$key.".png";
			foreach (array("", "open_") as $type) {
				$filename = str_replace("%TYPE%", $type, $closedFilename);
				$basename = basename($filename);
				if (file_exists($filename)) {
					$icons[$type.$key]["filename"] = $basename;
					$size = getimagesize($filename);
					if ($size)
						$icons[$type.$key]["size"] = $size[3];
				}
			}
		}

		return $icons;
	} /* }}} */

	function getTreeArray() { return $this->treeArray; }
}


?>
