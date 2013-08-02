<!DOCTYPE html>
<html>
<head>
	<link rel='stylesheet' type='text/css' href='style.css'/>
</head>
<body>
<a href='home.html' target="content">Home</a>
<a href='#' onclick="return false">Components</a>
<div class='context_menu' id='menu_components'>
<?php 
global $components;
foreach ($components as $c) {
	echo $c."<br/>";
}
?>
</div>
</body>
</html>