<?php
class page_export_storage extends Page {
	
	public function getRequiredRights() {
		return array();
	}
	
	protected function execute() {
		$domain = PNApplication::$instance->local_domain;
		$db = SQLQuery::getDataBaseAccessWithoutSecurity();
		if (isset($_POST["dir"])) {
			$dir = $_POST["dir"];
			$target = realpath(dirname($_SERVER["SCRIPT_FILENAME"])."/component/development/data/storage");
			if (file_exists($target."/".$dir)) {
				echo "The directory ".$dir." already exists.";
				return;
			}
			$target .= "/".$dir;
			if (!mkdir($target)) {
				echo "Unable to create directory ".$target;
				return;
			}
			$lines = "";
			foreach ($_POST as $name=>$value) {
				if (substr($name,0,1) <> "f") continue;
				if ($value <> "on") continue;
				$id = intval(substr($name,1));
				$dir1 = $id%100;
				$dir2 = ($id/100)%100;
				$dir3 = ($id/10000)%100;
				$filename = intval($id/1000000);
				$path = realpath(dirname($_SERVER["SCRIPT_FILENAME"]))."/data/$domain/storage/".$dir1."/".$dir2."/".$dir3."/".$filename;
				if (!file_exists($path)) {
					echo "Error for ID ".$id.": file not found (".$path.").<br/>";
					continue;
				}
				if (!copy($path, $target."/".$id)) {
					echo "An error occured while copying file ".$id."<br/>";
					continue;
				}
				$res = $db->execute("SELECT * FROM `storage` WHERE `id`=".$id);
				$row = $db->nextRow($res);
				$lines .= "INSERT INTO `storage` (`id`,`mime`,`type`,`revision`) VALUES (".$id.",'".$db->escapeString($row["mime"])."','".$db->escapeString($row["type"])."',1);\r\n";
			}
			$f = fopen($target."/insert.sql","w");
			fwrite($f, $lines);
			fclose($f);
			echo "Export done.";
			return;
		}
		$res = $db->execute("SELECT * FROM `storage` WHERE `expire` IS NULL");
		echo "<form method='POST'>";
		echo "Please select the elements to export:<br/>";
		echo "<table style='border: 1px solid black' border='1' rules='all'>";
		echo "<tr><th></th><th>ID</th><th>File Type</th><th>Type</th></tr>";
		while (($row = $db->nextRow($res)) <> null) {
			echo "<tr>";
			echo "<td><input type='checkbox' name='f".$row["id"]."'/></td>";
			echo "<td>".$row["id"]."</td>";
			echo "<td>".$row["mime"]."</td>";
			echo "<td>".$row["type"]."</td>";
			echo "</tr>";
		}
		echo "</table>";
		echo "Directory to export (under development/data/storage): ";
		echo "<input type='text' name='dir'/><br/>";
		echo "<input type='submit' value='Export'/>";
		echo "</form>";
	}

}
?>