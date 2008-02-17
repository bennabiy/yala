<script type="text/javascript">

function make_filter(str) {
	document.search_form.filter.value = '(&(objectclass=*)(cn='+str+'))';
}
	

</script>

<h1>Search</h1>
<table>
<tr bgcolor="#ffffff"><td>Filter:</td><td><input type="text" size="45" id="search_filter" value="(&(objectclass=*)(cn=replaceme))"></td></tr>
<tr><td>Base DN:</td><td><input type="text" size="45" id="search_basedn" value="<?= $ldap_basedn; ?>"></td></tr>
<tr><td>Scope:</td><td>
<select id="search_scope">
	<option value="sub">Subtree</option>
	<option value="one">One Level</option>
	<option value="base">Base</option>
</select>
</table>
<div class="center"><button onclick="doSearch()">Search</button></div>
