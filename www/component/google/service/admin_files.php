<?php 
class service_admin_files extends Service {
	
	public function getRequiredRights() { return array("admin_google"); }
	
	public function documentation() { echo "Files part of the admin page"; }
	public function inputDocumentation() {}
	public function outputDocumentation() {}
	
	public function getOutputFormat($input) {
		return "text/html";
	}
	
	public function execute(&$component, $input) {
		require_once 'component/google/lib_api/PNGoogleDrive.inc';
		$gdrive = new PNGoogleDrive();
		$list = $gdrive->getFiles();
?>
<table>
	<tr>
		<th>Title</th>
		<th>Description</th>
		<th>Size</th>
		<th>Original name</th>
	</tr>
<?php 
		foreach ($list as $file) {
			/* @var $file Google_Service_Drive_DriveFile */
			echo "<tr>";
			echo "<td>".toHTML($file->getTitle())."</td>";
			echo "<td>".toHTML($file->getDescription())."</td>";
			echo "<td>".toHTML($file->getFileSize())."</td>";
			echo "<td>".toHTML($file->getOriginalFilename())."</td>";
			echo "</tr>";
		}
		echo "</table>";
	}
	
}
?>