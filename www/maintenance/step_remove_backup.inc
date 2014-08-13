<?php 
if (isset($_GET["doit"])) {
	$version = $_GET["version"];
	$time = $_GET["time"];
	require_once("component/application/Backup.inc");
	Backup::removeDirectory("data/backups/$version/$time");
	echo "<script type='text/javascript'>location.href = '?step=destroy_sessions';</script>";
} else {
	include("header.inc");
	echo "<div style='flex:none;background-color:white;padding:10px'>";
	echo "Removing backup...";
	echo "<script type='text/javascript'>location.href = location.href+'&doit=true';</script>";
	echo "</div>";
	include("footer.inc");
}
?>