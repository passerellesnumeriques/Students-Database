<?php 
$f = fopen("/etc/passwd","r");
if ($f == null) {
	echo "<get_users result='fail'></get_users>";
	die();
}
echo "<get_users result='ok'>";
$users = array();
while (!feof($f)) {
	$line = fgets($f,4096);
	
	$i = strpos($line,":");
	if ($i == false) continue;
	$username = substr($line,0,$i);
	if (substr($username, strlen($username-1)) == "\$") continue; // computer name
	$line = substr($line,$i+1);

	$i = strpos($line,":");
	if ($i == false) continue;
	$line = substr($line,$i+1);
	
	$i = strpos($line,":");
	if ($i == false) continue;
	$user_id = intval(substr($line,0,$i));
	if ($user_id < 1000) continue; // system user
	$line = substr($line,$i+1);
	
	$i = strpos($line,":");
	if ($i == false) continue;
	$main_group_id = intval(substr($line,0,$i));
	if ($main_group_id <> 100) continue; // not among users group
	$line = substr($line,$i+1);
	
	$i = strpos($line,":");
	if ($i == false) continue;
	$user_fullname = substr($line,0,$i);
	$line = substr($line,$i+1);
	
	array_push($users, array("username"=>$username, "id"=>$user_id, "fullname"=>$user_fullname, "groups"=>array()));
}
fclose($f);
$f = fopen("/etc/group","r");
if ($f <> null) {
	while (!feof($f)) {
		$line = fgets($f,4096);
		
		$i = strpos($line,":");
		if ($i == false) continue;
		$name = substr($line,0,$i);
		$line = substr($line,$i+1);
		
		$i = strpos($line,":");
		if ($i == false) continue;
		$line = substr($line,$i+1);
		
		$i = strpos($line,":");
		if ($i == false) continue;
		$id = substr($line,0,$i);
		$line = substr($line,$i+1);

		$users_list = explode(",", $line);
		
		foreach ($users as &$user) {
			$found = false;
			foreach ($users_list as $username)
				if ($username == $user["username"]) { $found = true; break; }
			if ($found)
				array_push($user["groups"], array("name"=>$name, "id"=>$id));
		}
	}
	fclose($f);
}

function toXML($str) {
	return str_replace("\\", "\\\\", str_replace("\"", "&quot;", $str));
}

foreach ($users as &$user) {
	echo "<user";
	echo " username=\"".toXML($user["username"])."\"";
	echo " userid=\"".toXML($user["id"])."\"";
	echo " fullname=\"".toXML($user["fullname"])."\"";
	echo ">";
	foreach ($user["groups"] as $group) {
		echo "<group";
		echo " name=\"".toXML($group["name"])."\"";
		echo " id=\"".toXML($group["id"])."\"";
		echo "></group>";
	}
	echo "</user>";
}
echo "</get_users>";
?>