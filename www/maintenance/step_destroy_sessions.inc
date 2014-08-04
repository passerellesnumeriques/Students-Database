<?php 
$sessions_path = ini_get("session.save_path");
$i = strrpos($sessions_path, ";");
if ($i !== false) $sessions_path = substr($sessions_path, $i+1);
$sessions_path = realpath($sessions_path);
$dir = opendir($sessions_path);
$remaining = 0;
while (($filename = readdir($dir)) <> null) {
	if (is_dir($sessions_path."/".$filename)) continue;
	$content = @file_get_contents($sessions_path."/".$filename);
	if (strpos($content,"PNApplication") !== false) {
		@unlink($sessions_path."/".$filename);
		if (file_exists($sessions_path."/".$filename)) $remaining++;
	}
}
closedir($dir);
if ($remaining > 0) {
	echo $remaining;
?>
 session(s) still open. Please wait...
<script type='text/javascript'>
setTimeout(function() { location.reload(); },1000);
</script>
<?php 	
} else {
?>
All openend sessions have been removed.<br/>
<button onclick="location.href='/maintenance?step=back_to_normal';">Leave maintenance mode and put back the software in normal mode</button>
<?php 	
}
