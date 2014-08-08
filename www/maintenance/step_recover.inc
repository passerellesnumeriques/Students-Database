<?php 
if (isset($_GET["recover"])) {
	require_once("component/application/Backup.inc");
	Backup::recoverBackup($_GET["time"]);
	
	if (PNApplication::hasErrors())
		PNApplication::printErrors();
	else {
		include("header.inc");
		echo "<div style='flex:none;background-color:white;padding:10px'>";
		echo "Data recovered.";
		echo "<form name='install' method='POST' action='?step=".$_GET["ondone"]."'>";
		if (isset($_POST["path"])) {
			echo "<input type='hidden' name='version' value='".$_POST["version"]."'/>";
			echo "<input type='hidden' name='path' value='".$_POST["path"]."'/>";
		}
		echo "<input type='submit' value='Continue'/>";
		echo "</form>";
		echo "</div>";
		include("footer.inc");
	}
} else {
	include("header.inc");
	echo "<div style='flex:none;background-color:white;padding:10px'>";
	echo "Recovering data...";
	echo "<form name='install' method='POST' action='?step=recover&recover=true&time=".$_GET["time"]."&ondone=".$_GET["ondone"]."'>";
	if (isset($_POST["path"])) {
		echo "<input type='hidden' name='version' value='".$_POST["version"]."'/>";
		echo "<input type='hidden' name='path' value='".$_POST["path"]."'/>";
	}
	echo "</form>";
	echo "<script type='text/javascript'>document.forms['install'].submit();</script>";
	echo "</div>";
	include("footer.inc");
}
?>