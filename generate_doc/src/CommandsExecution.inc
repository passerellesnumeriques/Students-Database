<?php 
class CommandsExecution {

	private $commands = array();
	
	public function add_command($cmd) {
		array_push($this->commands, $cmd);
	}
	
	public function launch_execution($unique_name) {
		global $generated_dir;
		echo " + Execute external commands (".$unique_name.")\n";
		mkdir($generated_dir."/tmp/batch_".$unique_name);
		$main_batch = "@echo off\r\n";
		$sub_batch_index = 0;
		foreach ($this->commands as $cmd) {
			$name = "batch".($sub_batch_index++);
			if ($sub_batch_index == 5) {
				$main_batch .= "ping -n 1 -w 2000 1.2.3.4 > NUL 2>&1\r\n";
			} else if ($sub_batch_index < 10) {
				$main_batch .= "ping -n 1 -w 500 1.2.3.4 > NUL 2>&1\r\n";
			} else if (($sub_batch_index%3) == 0) {
				$main_batch .= "ping -n 1 -w 1000 1.2.3.4 > NUL 2>&1\r\n";
			} else {
				$main_batch .= "ping -n 1 -w 100 1.2.3.4 > NUL 2>&1\r\n";
			}
			$main_batch .= "START /B CMD /C CALL ".$name.".bat\r\n";
			$f = fopen($generated_dir."/tmp/batch_".$unique_name."/".$name.".bat","w");
			if (is_array($cmd)) {
				foreach ($cmd as $c)
					fwrite($f, $c."\r\n");
			} else {
				fwrite($f, $cmd."\r\n");
			}
			fclose($f);
		}
		$f = fopen($generated_dir."/tmp/batch_".$unique_name."/main.bat","w");
		fwrite($f, $main_batch);
		fclose($f);
		system("CD ".$generated_dir."/tmp/batch_".$unique_name." && main.bat");
		echo "Cleaning temporary files\n";
		require_once("FSUtils.inc");
		FSUtils::remove_dir($generated_dir."/tmp/batch_".$unique_name);
	}	
}
?>