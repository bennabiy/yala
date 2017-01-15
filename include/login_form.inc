<script type="text/javascript">
// hide the tree, for it's not needed now
document.getElementById("mainDiv").style.display = 'none';
document.getElementById("sideBarDiv").style.display = 'none';
</script>
<div class="center">
<img src="images/yala-logo-small.jpg" alt="YALA logo" width="385" height="179">
<br>
v<?= YALA_VERSION; ?>
</div>
<form method="post">
<table style="border: thin solid #DDE5FF; margin: 0 auto;">
	<tr><td><b><acronym title="The LDAP server to connect to. either IP address or name">Server</acronym>:</b></td><td><input type="text" size="45" name="ldap_server" value="<?= $ldap_server; ?>"></td></tr>
	<tr><td><b><acronym title="The port which the LDAP daemon listens to. usually 389 or 636">Port</acronym>:</b></td><td><input type="text" size="45" name="ldap_port" value="<?= $ldap_port; ?>"></td></tr>
	<tr><td><b><acronym title="The root of the tree we'll be looking under">Base DN</acronym>:</b></td><td><input type="text" size="45" name="ldap_basedn" value="<?= $ldap_basedn; ?>"></td></tr>
	<tr><td><b><acronym title="Distinguished Name to bind (=login) as">Bind DN</acronym>:</b></td><td><input type="text" size="45" name="ldap_binddn" value="<?= $ldap_binddn; ?>"></td></tr>
	<tr><td><b><acronym title="Password (credentials) of the above DN">Bind Password</acronym>:</b></td><td><input type="password" size="45" name="ldap_bindpw"></td></tr>
	<tr><td><b><acronym title="Use Transport Layer Security encryption for this connection">TLS</acronym>:</b></td><td><input type="checkbox" name="ldap_tls"<?php if ($ldap_tls) echo " checked"; ?>>Use <acronym title="Transport Layer Security">TLS</acronym> for this connection</td></tr>
</table>
<div class="center">
<input type="submit" class="submit" name="submit" value="Login">&nbsp;&nbsp;
<input type="submit" class="submit" name="submit" value="Anonymous Login">
</div>
</form>
