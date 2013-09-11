<?php
class page_set_picture extends Page {
	public function get_required_rights() { return array(); }
	public function execute() {
		global $people_id;
		$people_id = $_GET["people"];
		if ($people_id <> PNApplication::$instance->user_people->user_people_id) {
			if (!PNApplication::$instance->user_management->has_right("see_other_people_details")) {
				PNApplication::error("Access Denied");
				return;
			}
		}
		require_once("component/data_import/page/pictures.inc");
		pictures_import("people_set_picture_".$people_id,600,600,100,100,'finalize_picture_import');
		
	}
} 
function finalize_picture_import($ids) {
	global $people_id;
	$storage_id = $ids[0];
	$people = SQLQuery::create()->select("People")->where("id",$people_id)->execute_single_row();
	if ($people <> null) {
		$version = isset($people["picture_version"]) && $people["picture_version"] <> null ? intval($people["picture_version"])+1 : 1;
		SQLQuery::create()->update("People", array("picture"=>$storage_id,"picture_version"=>$version), array("id"=>$people_id));
		if (!PNApplication::has_errors()) {
			if (isset($people["picture"]) && $people["picture"] <> null && $people["picture"] <> 0)
				PNApplication::$instance->storage->remove_data($people["picture"]);
			PNApplication::$instance->storage->set_expire($storage_id, null);
			?>
			<img src='<?php echo theme::$icons_16["saving"];?>'/>
			<script type='text/javascript'>
			window.parent.location.reload();
			</script>
			<?php 
		}
	}
}
?>