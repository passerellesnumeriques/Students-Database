<?php 
class page_app_admin extends Page {
	
	public function getRequiredRights() { return array("manage_application"); }
	
	public function execute() {
		$sessions_path = ini_get("session.save_path");
		$sessions = array();
		$dir = opendir($sessions_path);
		while (($filename = readdir($dir)) <> null) {
			if (is_dir($filename)) continue;
			array_push($sessions, $filename);
		}
		closedir($dir);
?>
<table>
	<tr><th>Session ID</th><th>Creation</th><th>Last modification</th><th>User</th></tr>
<?php 
$method = ini_get("session.serialize_handler");
foreach ($sessions as $session) {
	echo "<tr>";
	echo "<td>".$session."</td>";
	$info = stat($sessions_path."/".$session);
	echo "<td>".date("Y-m-d h:i A", $info["ctime"])."</td>";
	echo "<td>".date("Y-m-d h:i A", $info["mtime"])."</td>";
	$data = self::decode_session(file_get_contents($sessions_path."/".$session));
	echo "<td>";
	if ($data <> null) {
		echo @$data["app"]->user_management->username;
	}
	echo "</td>";
	echo "</tr>";
}
?>
</table>		
	<?php 
	}

	private static function decode_session($session_string){
	    $current_session = session_encode();
	    foreach ($_SESSION as $key => $value){
	        unset($_SESSION[$key]);
	    }
	    session_decode($session_string);
	    $restored_session = $_SESSION;
	    foreach ($_SESSION as $key => $value){
	        unset($_SESSION[$key]);
	    }
	    session_decode($current_session);
	    return $restored_session;
	}
}
?>