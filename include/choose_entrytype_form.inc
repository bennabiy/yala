<script type="text/javascript">

function check_form(form) {

	checked = false;

	for (i = 0; i < form.entry_type.length; i++) {
		if (form.entry_type[i].checked == true) checked = true;
	}

	if (!checked) {
		alert("You didn't choose an entry type!");
		return false;
	}

	return true;
}

</script>
<?php
	reset($entry_types);

	$count = 0;

	while ( list($entry_type, $val) = each($entry_types) ) {
		$count++;
		if (file_exists(IMAGES_PATH."/icons/".$entry_type.".png"))
			$image = "<img src=\"".IMAGES_URLPATH."/icons/".$entry_type.".png\" alt=\"".$entry_type."\">";
		else
			$image = "<img src=\"".IMAGES_URLPATH."/icons/default.png\" alt=\"".$entry_type."\">";
?>
<input type="radio" name="entry_type" onclick="g_chosenEntryType='<?=$entry_type?>'">&nbsp;<?=$image?>&nbsp;<?=$entry_type?><br>
<?php
	}

?>
<div class="center"><button onclick="callBackEnd('create_step2', 'entry_type='+g_chosenEntryType+'&parent=<?=urlencode($parent)?>&objectclasses=')">Next</button></div>

<!-- CUSTOM entry type -->
<hr><input type="radio" name="entry_type" id="custom" value="custom">&nbsp;<img src="<?=IMAGES_URLPATH?>/icons/default.png" alt="Custom">&nbsp;custom<BR>
<div class="center">
	<select id="custom_objectclasses" size="8" onchange="document.getElementById('custom').checked=true;" multiple>
<?php
	$names = array_keys($name2oid);
	asort($names);
	foreach ($names as $name) {
		echo "<option value=\"".$name."\">".$name."<br>";
	}
?>
	</select>
</div>
<div class="center"><button onclick="callBackEnd('create_step2', 'entry_type=custom&parent=<?=urlencode($parent)?>&objectclasses='+getSelectedOptions('custom_objectclasses').join(','))">Next</button></div>
