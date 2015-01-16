<?php 
class service_admin_users extends Service {
	
	public function getRequiredRights() { return array("admin_google"); }
	
	public function documentation() { echo "Users part of the admin page"; }
	public function inputDocumentation() {}
	public function outputDocumentation() {}
	
	public function getOutputFormat($input) {
		return "text/html";
	}
	
	public function execute(&$component, $input) {
		require_once("component/google/lib_api/PNGoogleDirectory.inc");
		$dir = new PNGoogleDirectory();
		$root = $dir->getHierarchy();
		$this->generateSubOrganizations($root, 0);
	}
	private $id_counter = 0;
	private function generateSubOrganizations($node, $indent) {
		$ts = time();
		foreach ($node["sub_organizations"] as $so) {
			echo "<div style='margin-left:".$indent."px;'>";
			echo "<b>".$so["org"]->name."</b>";
			echo " ";
			$id = "org_".($this->id_counter++);
			echo "<a class='black_link' href='#' onclick=\"";
			echo "require('popup_window.js',function() { var d = document.createElement('DIV'); d.innerHTML = document.getElementById('$id').innerHTML; var p = new popup_window('Users',null,d);p.show();});";
			echo "return false;\">";
			echo count($so["users"])." users";
			echo "</a>";
			$this->generateSubOrganizations($so, $indent+20);
			echo "</div>";
			echo "<div style='display:none' id='$id'>";
			$images = array();
			foreach ($so["users"] as $u) {
			/* @var $u Google_Service_Directory_User */
			echo "<div style='border-bottom:1px solid #808080;margin-bottom: 1px;white-space:nowrap;'>";
			echo toHTML($u->getName()->getFullName());
			echo " (".$u->getPrimaryEmail().")";
			if ($u->getThumbnailPhotoUrl() <> null) {
			$iid = "img_".$ts."_".($this->id_counter++);
			$images[$iid] = $u->getThumbnailPhotoUrl();
			echo " <img id='$iid' style='vertical-align:middle;' no_wait='true'/>";
			}
			echo "</div>";
			}
			echo "</div>";
			echo "<script type='text/javascript'>";
			echo "setTimeout(function(){var i;";
			foreach ($images as $iid=>$url) echo "i=document.getElementById('$iid');i.style.maxHeight='50px';i.src='$url';";
			echo "},2000);";
			echo "</script>";
		}
	}
	
}
?>