<?php 
class FSUtils {
	
	public static function mkdir_rec($dir) {
		if (is_dir($dir)) return;
		if (!is_dir(dirname($dir))) FSUtils::mkdir_rec(dirname($dir));
		mkdir($dir);
	}
	
	public static function copy_dir_flat($src, $dst) {
		$dir = opendir($src);
		while (($filename = readdir($dir)) <> null) {
			if ($filename == "." || $filename == "..") continue;
			if (is_dir($src."/".$filename)) continue;
			copy($src."/".$filename, $dst."/".$filename);
		}
		closedir($dir);
	}
		
	public static function remove_dir($path) {
		$dir = @opendir($path);
		if ($dir == null) return;
		while(false !== ( $file = readdir($dir)) ) {
			if (( $file != '.' ) && ( $file != '..' )) {
				if ( is_dir($path . '/' . $file) ) {
					FSUtils::remove_dir($path . '/' . $file);
				}
				else {
					unlink($path . '/' . $file);
				}
			}
		}
		closedir($dir);
		rmdir($path);
	}
	
	public static function copy_dir($src, $dst) {
		$dir = opendir($src);
		while (($filename = readdir($dir)) <> null) {
			if ($filename == "." || $filename == "..") continue;
			if (is_dir($src."/".$filename)) {
				mkdir($dst."/".$filename);
				FSUtils::copy_dir($src."/".$filename, $dst."/".$filename);
			} else
				copy($src."/".$filename, $dst."/".$filename);
		}
		closedir($dir);
	}
	
	public static function write_file($filename, $content) {
		global $generated_dir;
		$path = $generated_dir."/".$filename;
		$f = fopen($path, "w");
		fwrite($f, $content);
		fclose($f);
	}
}
?>