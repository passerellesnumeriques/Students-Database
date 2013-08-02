<!DOCTYPE html>
<html>
<head>
	<link rel='stylesheet' type='text/css' href='style.css'/>
</head>
<body>
Navigation<br/>
<?php 
global $components;
foreach ($components as $c)
	echo "<a href='component/".$c."/index.html' target='content'>".$c."</a><br/>";
?>
</body>
</html>