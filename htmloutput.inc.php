<?php
# vim: foldmethod=marker
#
# HTMLOutput is a class which is supposed to contain all the ugly HTML code..
# The idea is to make all the other code clean, and call functions from here to
# do the ugly html output.
#

class HTMLOutput {

	private $bgcolor;

	# ========================================

	public function __construct() {
	}

	public function resultsHeader($dn) {
?><!-- resultsHeader -->
<table class="results-outer" width="100%">
	<tr class="dnbgcolor"><td class="view-dnattr" align="center"><?php echo $dn; ?></td></tr>
<?php
	}

	public function resultsInnerHeader() {
?>	<!-- resultsInnerHeader -->
	<tr><td>
	<table class="results-inner" widtH="100%">
<?php
	}

	public function resultsInnerRow($attr, $value, $status) {
		if (!$attr) $attr = "&nbsp;";
		if (!$value) $value = "&nbsp;";
?>		<tr><td class="results-inner" width="40%"><?php echo $attr; ?></td><td class="results-inner" width="40%"><?php echo $value; ?></td><td class=<?php
		switch ($status) {
			case 1: echo "\"success\">OK"; break;
			case -1: echo "\"success\">&nbsp;"; break;
			default: echo "\"failed\">FAILED";
		}
?></td></tr>
<?php
	}

	public function resultsInnerFooter() {
?>	</table>
	</td></tr>
	<!-- resultsInnerFooter -->
<?php
	}

	public function resultsTitle($text) {
?>	<tr class="bgcolor1"><td><b><?php echo $text; ?></b></td></tr>
<?php
	}

	public function resultsFooter() {
?></table>
<!-- resultsFooter -->
<?php
	}

	public function viewHeader() {
?><!-- viewHeader -->
<form name="yalaForm" id="yalaForm">
<table class="view-outer" width="100%">
<?php
	}

	public function modrdnHeader($title, $dn) {
?><!-- modrdnHeader -->
<form name="yalaForm" id="yalaForm">
<input type="hidden" name="dn" value="<?= $dn; ?>">
<table class="view-outer" width="100%">
	<tr class="dnbgcolor"><td class="view-dnattr" align="center"><?= $title; ?></td></tr>
<?php
	}


	public function viewInnerHeader() {
?>	<!--viewInnerHeader -->
	<TR><TD>
	<TABLE CLASS="view-inner" WIDTH="100%">
<?php
	}

	public function viewInnerRowDN($dn) {
?>
<tr class="bgcolor1">
	<td class="view-dnattr"><acronym title="Distinguished Name">dn</acronym></td>
	<td><input type="text" name="dn" value="<?=formatOutputStr($dn); ?>" size="<?=INPUT_TEXT_SIZE?>">&nbsp;<span class="smaller">[&nbsp;<a tabIndex=998 href="" onclick="callBackEnd('modrdn_form', 'dn=<?=urlencode($dn)?>'); return false">Modify</a>&nbsp;]</span>&nbsp;</td>
</tr>
<?php

	}

	public function modrdnInnerRowDN($dn) {
?>
<tr class="bgcolor1">
	<td class="view-dnattr"><acronym title="The DN before the modifictation">dn</acronym></td>
	<td><?=formatOutputStr($dn)?></td>
</tr>
<?php
	}


	public function modrdnInnerRow($attr, $value, $acronym) {

		# Very stupid color changing
		if (isset($this->bgcolor) && $this->bgcolor == "bgcolor2")
			$this->bgcolor = "bgcolor1";
		else
			$this->bgcolor = "bgcolor2";

		$acronym_start = "";
		$acronym_end = "";
		if ($acronym) {
			$acronym_start = "<acronym title=\"".$acronym."\">";
			$acronym_end = "</acronym>";
		}
?>
<tr class="<?= $this->bgcolor ?>">
	<td class="attr"><?=$acronym_start?><?=$attr?><?=$acronym_end?></td>
	<td class="value"><input type="text" name="<?=$attr?>" value="<?=$value?>" size="<?=INPUT_TEXT_SIZE?>"></td>
</tr>

<?php
	}

	public function viewInnerRow($attr, $value, $bold, $acronym) {

		# Very stupid color changing
		if (isset($this->bgcolor) && $this->bgcolor == "bgcolor2")
			$this->bgcolor = "bgcolor1";
		else
			$this->bgcolor = "bgcolor2";

		$str =
"		<tr class=\"".$this->bgcolor."\"><td class=\"attr\">";
		if ($bold) $str .= "<b>";
		if ($acronym) $str .= "<acronym title=\"".$acronym."\">";
		$str .= $attr;
		if ($acronym) $str .= "</acronym>";
		if ($bold) $str .= "</b>";
		$str .= "</td><td class=\"value\"><input type=\"text\" name=\"";
		$str .= $attr."\" value=\"".$value."\" size=\"".INPUT_TEXT_SIZE."\">";
		$str .= "<sup>[<a tabindex=999 href='' onclick='dupObj(this.parentNode.parentNode.parentNode, true); return false;'><acronym title='Add one more field'>+</acronym></a>]</sup></td></tr>\n";

		echo $str;
	}

	public function viewInnerFooter() {
?>
	<!-- viewInnerFooter -->
	</table>
	</td></tr>
<?php
	}

	public function viewTitle($text) {
?><tr class="bgcolor1"><td><b><?= $text; ?></b></td></tr><?php
	}

	public function modrdnFooter() {
?>
<!-- modrdnFooter -->
</table>
</form>
<div class="actionBar">
<button onclick="processEntryForm('modrdn')">Modrdn</button>
</div>

<div class="center smaller">Place the mouse over an unknown term in order to get help</center>

<?php
	}

	public function viewFooter($dn = "") {
?>
<!-- viewFooter -->
</table>
</form>
<?php
	}

	public function viewTreeElement($data) {
		global $tree;

		$dn = $data["dn"];
		$rdn = mkRdn($dn);
		$entryType = $data["entryType"];
		$iconsHash = $tree->getIconsHash();
		if (isset($iconsHash[$entryType])) {
			$iconFile = $iconsHash[$entryType]["filename"];
			$iconSize = $iconsHash[$entryType]["size"];
		}
		else {
			$iconFile = DEFAULT_ICON;
			$iconSize = "";
		}
		$iconStr = '<img src="'.IMAGES_URLPATH.'/icons/'.$iconFile.'" '.$iconSize.' border="0" alt="" class="TreeItemIcon"/>';

		?>
		<li id="<?=$_REQUEST["iterate"].urlencode($dn)?>"><?=$iconStr?><a href="" onclick="viewEntry('<?=urlencode($dn)?>'); return false;"><?=$rdn?></a><span>&nbsp;&nbsp;<sup>[<a href="" onclick="callBackEnd('create_step1', 'parent=<?=urlencode($dn)?>'); return false"><acronym title="Create a new entry under this entry">n</acronym></a>]</sup></span>
		<?php
	}

	public function viewTree($treeArray) {
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

	public function errorDialog($ex) {
?>
<!-- ERROR DIALOG START -->
<div>
<div class="center">
	<img src="images/error.png">
</div>
<div class="center"><?="Error #".$ex->getCode().": ".getErrString($ex->getCode(), $ex->getMessage());?></div>
</div>
<!-- ERROR DIALOG END -->
<?php
	}
}
