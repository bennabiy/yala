<?php
# vim: foldmethod=marker
#
# HTMLOutput is a class which is supposed to contain all the ugly HTML code..
# The idea is to make all the other code clean, and call functions from here to
# do the ugly html output.
#

class HTMLOutput {

	var $bgcolor;

	function HTMLOutput() {
	}

	function resultsHeader($dn) {
?><!-- resultsHeader -->
<TABLE CLASS="results-outer" WIDTH="98%">
	<TR CLASS="dnbgcolor"><TD CLASS="view-dnattr" ALIGN="center"><?php echo $dn; ?></TD></TR>
<?php
	}

	function resultsInnerHeader() {
?>	<!-- resultsInnerHeader -->
	<TR><TD>
	<TABLE CLASS="results-inner" WIDTH="100%">
<?php
	}


	function resultsInnerRow($attr, $value, $status) {
		if (!$attr) $attr = "&nbsp;";
		if (!$value) $value = "&nbsp;";
?>		<TR><TD CLASS="results-inner" WIDTH="40%"><?php echo $attr; ?></TD><TD CLASS="results-inner" WIDTH="40%"><?php echo $value; ?></TD><TD CLASS=<?php
		switch ($status) {
			case 1: echo "\"success\">OK"; break;
			case -1: echo "\"success\">&nbsp;"; break;
			default: echo "\"failed\">FAILED";
		}
?></TD></TR>
<?php
	}

	function resultsInnerFooter() {
?>	</TABLE>
	</TD></TR>
	<!-- resultsInnerFooter -->
<?php
	}
	
	function resultsTitle($text) {
?>	<TR CLASS="bgcolor1"><TD><B><?php echo $text; ?></B></TD></TR>
<?php
	}

	function resultsFooter() {
?></TABLE>
<!-- resultsFooter -->
<?php
	}





	function viewHeader($dn = "") {
?><!-- viewHeader -->
<FORM NAME="form" METHOD="post" ACTION="<?php echo MAINFILE; ?>" <?php if (ENABLE_JAVASCRIPT) echo " onSubmit=\"javascript:return confirm('Are you sure you want to commit a \''+this.chosen_action.value+'\' operation on this entry?');\""; ?> >
<?php if ($dn) echo "<INPUT TYPE=\"hidden\" NAME=\"entry\" VALUE=\"".$dn."\">\n"; ?>
<INPUT TYPE="hidden" NAME="chosen_action" VALUE="none">

<TABLE CLASS="view-outer" WIDTH="98%">
	<TR CLASS="dnbgcolor"><TD CLASS="view-dnattr" ALIGN="center"><?php echo $dn; ?></TD></TR>
<?php
	}

	function modrdnHeader($dn = "") {
?><!-- modrdnHeader -->
<TABLE CLASS="view-outer" WIDTH="98%">
	<TR CLASS="dnbgcolor"><TD CLASS="view-dnattr" ALIGN="center"><?php echo $dn; ?></TD></TR>
<?php
	}


	function viewInnerHeader() {
?>	<!--viewInnerHeader -->
	<TR><TD>
	<TABLE CLASS="view-inner" WIDTH="100%">
<?php
	}

	function viewInnerRowDN($dn) {
		echo "
		<TR CLASS=\"bgcolor1\"><TD CLASS=\"view-dnattr\">";
echo "<ACRONYM TITLE=\"Distinguished Name\">dn</ACRONYM>&nbsp;<FONT";
echo " SIZE=\"-2\">[&nbsp;<A HREF=\"".MAINFILE."?do=modrdn_form&amp;entry=";
echo urlencode($dn)."\">Modify DN</A>&nbsp;]&nbsp;</FONT></TD>";
echo "<TD><INPUT TYPE=\"text\" NAME=\"dn\" VALUE=\"".formatOutputStr($dn);
echo "\" SIZE=\"".INPUT_TEXT_SIZE."\"></TD></TR>\n";

	}

	function modrdnInnerRowDN($dn) {
		echo "
		<TR CLASS=\"bgcolor1\"><TD CLASS=\"view-dnattr\">";
echo "<ACRONYM title=\"The DN before the modifictation\">dn</ACRONYM></TD>";
echo "<TD>".formatOutputStr($dn)."</TD></TR>\n";

	}


	function modrdnInnerRow($attr, $value, $acronym) {

		# Very stupid color changing
		if (isset($this->bgcolor) && $this->bgcolor == "bgcolor2") 
			$this->bgcolor = "bgcolor1";
		else
			$this->bgcolor = "bgcolor2";

		$str = 
"		<TR CLASS=\"".$this->bgcolor."\"><TD CLASS=\"attr\">";
		if ($acronym) $str .= "<ACRONYM title=\"".$acronym."\">";
		$str .= $attr;
		if ($acronym) $str .= "</ACRONYM>";
		$str .= "</TD><TD CLASS=\"value\"><INPUT TYPE=\"text\" NAME=\"";
		$str .= $attr."\" VALUE=\"".$value."\" SIZE=\"".INPUT_TEXT_SIZE."\">";
		$str .= "</TD></TR>\n";

		echo $str;
	}

	function viewInnerRow($attr, $value, $bold, $acronym) {

		# Very stupid color changing
		if (isset($this->bgcolor) && $this->bgcolor == "bgcolor2") 
			$this->bgcolor = "bgcolor1";
		else
			$this->bgcolor = "bgcolor2";

		$str = 
"		<TR CLASS=\"".$this->bgcolor."\"><TD CLASS=\"attr\">";
		if ($bold) $str .= "<B>";
		if ($acronym) $str .= "<ACRONYM title=\"".$acronym."\">";
		$str .= $attr;
		if ($acronym) $str .= "</ACRONYM>";
		if ($bold) $str .= "</B>";
		$str .= "</TD><TD CLASS=\"value\"><INPUT TYPE=\"text\" NAME=\"";
		$str .= $attr."[]\" VALUE=\"".$value."\" SIZE=\"".INPUT_TEXT_SIZE."\">";
		$str .= "</TD></TR>\n";

		echo $str;
	}

	function viewActionBar($entryExists) {
?><!-- ACTION BAR BEGIN -->
<BR><TABLE CLASS="actionbar">
	<TR CLASS="actionbar">
	<?php
	### MODIFY ###
	if ($entryExists) {
		echo "
	<TD><INPUT TYPE=\"submit\" CLASS=\"submit\" NAME=\"submit\"";
		echo "VALUE=\"Modify\"";
		if (ENABLE_JAVASCRIPT) echo 
" onClick=\"javascript:this.form.chosen_action.value='Modify';\"";
		echo "></TD>";
	}

	### NEW ###

	echo "
	<TD><INPUT TYPE=\"submit\" CLASS=\"submit\" NAME=\"submit\"";
echo "VALUE=\"New\"";
	if (ENABLE_JAVASCRIPT) echo
" onClick=\"javascript:this.form.chosen_action.value='New';\"";
	echo "></TD>\n";

	### DELETE ###

	if ($entryExists) {
		echo "
	<TD><INPUT TYPE=\"submit\" CLASS=\"submit\" NAME=\"submit\"";
		echo "VALUE=\"Delete\"";
		if (ENABLE_JAVASCRIPT) echo 
" onClick=\"javascript:this.form.chosen_action.value='Delete';\"";
		echo "></TD>";
	}
?>
	</TR>
</TABLE>
<!-- ACTION BAR END -->
<?php
	}

	function viewInnerFooter() {
?>
	<!-- viewInnerFooter -->
	</TABLE>
	</TD></TR>
<?php
	}
	
	function viewTitle($text) {
?>	<TR CLASS="bgcolor1"><TD><B><?php echo $text; ?></B></TD></TR>
<?php
	}

	function modrdnFooter() {
?>
<!-- modrdnFooter -->
</TABLE>
<CENTER><INPUT TYPE="submit" NAME="submit" VALUE="Modrdn"></CENTER>
</FORM>
<CENTER><FONT SIZE="-1">TIP: Put the mouse over an unknown term in order to get help</FONT></CENTER><?php
	}

	function viewFooter($dn = "") {
?>
<!-- viewFooter -->
</TABLE>
<?php $this->viewActionBar($dn); ?>
</FORM>
<?php
	}

	function viewTreeElement($data) {
		global $tree;

		$dn = $data["dn"];
		$rdn = mkRdn($dn);
		$entryType = $data["entryType"];
		if (isset($tree->iconsHash[$entryType])) {
			$iconFile = $tree->iconsHash[$entryType]["filename"];
			$iconSize = $tree->iconsHash[$entryType]["size"];
		}
		else {
			$iconFile = DEFAULT_ICON;
			$iconSize = "";
		}
		$iconStr = '<img src="'.IMAGES_URLPATH.'/icons/'.$iconFile.'" '.$iconSize.' border="0" alt="" class="TreeItemIcon"/>';

		?>
		<li><?=$iconStr?><a href="<?=MAINFILE?>?do=view_entry&amp;entry=<?=urlencode($dn)?>" target="right"><?=$rdn?></a><span>&nbsp;&nbsp;<sup>[<a href="<?=MAINFILE?>?do=choose_entrytype&amp;parent=<?=urlencode($dn)?>" target="right"><acronym title="Create a new entry under this entry">n</acronym></a>]</sup></span>
		<?
	}

	function viewTree($treeArray) {
		for ($i = 0; $i < $treeArray["count"]; $i++) {

			$this->viewTreeElement($treeArray[$i]);
			if ($treeArray[$i]["count"] == 0) {
				/* LEAF: close it.. */
				print "</li>";
			}
			else {
				print "<ul>";
				/* NODE: Recurse */
				$this->viewTree($treeArray[$i]);
				print "</ul>";
			}
		}
	}
}
