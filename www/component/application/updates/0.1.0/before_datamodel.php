<?php 
global $previous_version_path;
$f = fopen($previous_version_path."/conf/channel","w");
fwrite($f, "stable");
fclose($f);
?>