<?php
	$htmloutput = new HTMLOutput;
	$htmloutput->modrdnHeader("Modify DN (modrdn)", $entry);
	$htmloutput->viewTitle($entry);
	$htmloutput->viewInnerHeader();
	
	$htmloutput->modrdnInnerRowDN($entry);
	$htmloutput->modrdnInnerRow("newrdn", $rdn, "A new Relative DN. Modify it in case you want to rename the first chunk.");
	$htmloutput->modrdnInnerRow("newsuperior", $superior, "A new parent. Modify it in case you want to move this entry.");
	$htmloutput->modrdnInnerRow("deleteoldrdn", 1, "Delete the older name? (if unsure choose yes)");

	$htmloutput->viewInnerFooter();
	$htmloutput->modrdnFooter();
?>

