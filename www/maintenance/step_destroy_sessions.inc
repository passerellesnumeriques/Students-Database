<?php 
$sessions_path = realpath(ini_get("session.save_path"));
$dir = opendir($sessions_path);
$remaining = 0;
while (($filename = readdir($dir)) <> null) {
	if (is_dir($sessions_path."/".$filename)) continue;
	@unlink($sessions_path."/".$filename);
	if (file_exists($sessions_path."/".$filename)) $remaining++;
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
