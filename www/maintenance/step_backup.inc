<?php 
if (isset($_GET["backup"])) {
	set_error_handler(function($severity, $message, $filename, $lineno) {
		if (error_reporting() == 0) return true;
		PNApplication::error("PHP Error: ".$message." in ".$filename.":".$lineno);
		return true;
	});
	
	require_once("component/application/Backup.inc");
	$time = Backup::createBackup();
	
	if (PNApplication::hasErrors())
		PNApplication::printErrors();
	else {
		include("header.inc");
		echo "<div style='flex:none;background-color:white;padding:10px'>";
		echo "Backup created.";
		echo "<form name='install' method='POST' action='?step=".$_GET["ondone"]."'>";
		if (isset($_POST["path"])) {
			echo "<input type='hidden' name='version' value='".$_POST["version"]."'/>";
			echo "<input type='hidden' name='path' value='".$_POST["path"]."'/>";
		}
		echo "<input type='hidden' name='backup_time' value='$time'/>";
		echo "<input type='submit' value='Continue'/>";
		echo "</form>";
		echo "</div>";
		include("footer.inc");
	}
} else {
	include("header.inc");
	echo "<div style='flex:none;background-color:white;padding:10px'>";
	echo "Creating backup...";
	echo "<form name='install' method='POST' action='?step=backup&backup=true&ondone=".$_GET["ondone"]."'>";
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