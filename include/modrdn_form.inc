<FORM NAME="form" METHOD="post" ACTION="<?php echo MAINFILE; ?>">
<INPUT TYPE="hidden" NAME="entry" VALUE="<?php echo $entry; ?>">
<?php
	$htmloutput = new HTMLOutput;
	$htmloutput->modrdnHeader("Modify DN (modrdn)");
	$htmloutput->viewTitle($entry);
	$htmloutput->viewInnerHeader();
	
	$htmloutput->modrdnInnerRowDN($entry);
	$htmloutput->modrdnInnerRow("newrdn", $rdn, "If you want to rename, type another Relative DN");
	$htmloutput->modrdnInnerRow("deleteoldrdn", 1, "Delete the older name? (if unsure choose yes)");
	$htmloutput->modrdnInnerRow("newsuperior", $superior, "A new parent IF you want to move this entry");

	$htmloutput->viewInnerFooter();
	$htmloutput->modrdnFooter();
?>

