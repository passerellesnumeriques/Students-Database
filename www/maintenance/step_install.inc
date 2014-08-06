<?php 
if (isset($_GET["copy"])) {
	
	
	echo "New version installed.<br/>";
	echo "<a href='?step=destroy_sessions'>Ok, go back to main page of maintenance mode</a>";
} else {
	echo "Copying files...";
	echo "<script type='text/javascript'>location.href='?step=install&copy=true';</script>";
}
?>