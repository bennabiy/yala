<?php
require_once 'PHPUnit/Framework.php';
require '../config.inc.php';
require '../general.inc.php';
require '../ldapfunc.inc.php';

define("HOST", "");
define("PORT", 389);
define("BIND_DN", "");
define("BIND_PWD", "");
define("BASE_DN", "");
define("TEST_RDN", "cn=yalatest");
define("TEST_RDN2", "cn=yalatest2");

define("DEBUG", 0);

// TODO:
// setUp: should delete old existing yalatest, yalatest2 entries
// Make dependencies (don't run delete if create failed....)

class LdapFuncTest extends PHPUnit_Framework_TestCase
{
	private $ldap_func;

	# Connect to LDAP
	public function test1Connect() {
		$ldap_func = new LDAPFunc(HOST, PORT, false);
	}

	public function testBindAnonymous() {
		$ldap_func = new LDAPFunc(HOST, PORT, false);

		$this->assertEquals($ldap_func->getBound(), 0);

		$ldap_func->bind("", "");

		$this->assertEquals($ldap_func->getBound(), 1);
	}

	public function testBindAsUser() {
		$ldap_func = new LDAPFunc(HOST, PORT, false);

		$this->assertEquals($ldap_func->getBound(), 0);

		$ldap_func->bind(BIND_DN, BIND_PWD);

		$this->assertEquals($ldap_func->getBound(), 1);
	}

	public function testSchema_objectclasses() {
		$ldap_func = new LDAPFunc(HOST, PORT, false);
		$ldap_func->bind("", "");

		$name2oid = array(); $objectclasses = array();

		$ldap_func->getSchemaHash($name2oid, $objectclasses);

		# Test sample objectclasses according to rfc2256
		$this->assertEquals($name2oid["top"], "2.5.6.0");
		$this->assertEquals($objectclasses["2.5.6.2"]["must"][0], "c");
	}

	public function testSchema_attributes() {
		$ldap_func = new LDAPFunc(HOST, PORT, false);
		$ldap_func->bind("", "");

		$name2oid = array(); $attributetypes = array();

		$ldap_func->getSchemaHash_attributeTypes($name2oid, $attributetypes);

		# Test sample attributetypes according to rfc2256
		$this->assertEquals($name2oid["l"], "2.5.4.7");
		# A trick to make sure this attribute type exists at all
		$this->assertEquals(count($attributetypes["2.5.4.7"]), 2);
	}

	public function testCreateEntry() {
		$ldap_func = new LDAPFunc(HOST, PORT, false);
		$ldap_func->bind(BIND_DN, BIND_PWD);

		$info["cn"] = "yalatest";
		$info["sn"] = "Jones";
		$info["objectclass"] = "person";

		$result = ldap_add($ldap_func->getConn(), TEST_RDN.",".BASE_DN, $info);

		$this->assertTrue($result);
	}

	public function testReadEntry() {
#		ldap_read();
	}

	public function testRenameEntry() {
		$ldap_func = new LDAPFunc(HOST, PORT, false);
		$ldap_func->bind(BIND_DN, BIND_PWD);

		# Rename
		$result = ldap_rename($ldap_func->getConn(), TEST_RDN.",".BASE_DN, TEST_RDN2, "", 1);
		$this->assertTrue($result);

		# Bring it back
		$result = ldap_rename($ldap_func->getConn(), TEST_RDN2.",".BASE_DN, TEST_RDN, "", 1);
		$this->assertTrue($result);

	}

	public function testDeleteEntry() {
		$ldap_func = new LDAPFunc(HOST, PORT, false);
		$ldap_func->bind(BIND_DN, BIND_PWD);

		$result = ldap_delete($ldap_func->getConn(), TEST_RDN.",".BASE_DN);
		$this->assertTrue($result);
	}
}
?>
