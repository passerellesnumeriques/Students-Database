<!DOCTYPE html>
<html>
<head>
	<link rel='stylesheet' type='text/css' href='style.css'/>
</head>
<body>
<div class='navigation'>
<?php 
global $nav_id;
$nav_id = 0;
function generate_nav($node) {
	global $nav_id;
	$id = $nav_id++;
	echo "<div>";
	if (isset($node[2]) && $node[2] <> null && count($node[2]) > 0)
		echo "<img src='tree_collapse.png' style='vertical-align:bottom;cursor:pointer' onclick='nav_tree(this,$id)'>";
	else
		echo "<img src='list_circle.gif' style='vertical-align:bottom'>";
	if ($node[1] <> null)
		echo "<a href='".$node[1]."' target='content'>"; 
	echo $node[0];
	if ($node[1] <> null)
		echo "</a>";
	echo "</div>";
	if (isset($node[2]) && $node[2] <> null && count($node[2]) > 0) {
		echo "<div id='nav_$id' style='padding-left:15px;visibility:visible'>";
		foreach ($node[2] as $child)
			generate_nav($child);
		echo "</div>";
	}
}
global $nav;
foreach ($nav as $node)
	generate_nav($node);
?>
</div>
<script type='text/javascript'>
function nav_tree(img,id) {
	var div = document.all ? document.all['nav_'+id] : document.getElementById('nav_'+id);
	if (div.style.visibility == 'visible') {
		div.style.visibility = 'hidden';
		div.style.position = 'absolute';
		div.style.top = '-10000px';
		img.src = 'tree_expand.png';
	} else {
		div.style.visibility = 'visible';
		div.style.position = 'static';
		img.src = 'tree_collapse.png';
	}
}
</script>
</body>
</html>