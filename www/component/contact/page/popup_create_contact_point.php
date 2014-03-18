<?php 
class page_popup_create_contact_point extends Page {
	
	public function get_required_rights() { return array(); }
	
	public function execute() {
if ($_GET["org"] == "-1") {
	?>
	<script type='text/javascript'>
	postData("/dynamic/people/page/popup_create_people?types=contact_<?php echo $_GET["creator"];?>&donotcreate=<?php echo urlencode($_GET["donotcreate"]);?>", {});
	</script>
	<?php 
} else {
	$creator = SQLQuery::create()->select("Organization")->whereValue("Organization", "id", $_GET["org"])->field("creator")->executeSingleValue();
	?>
	<script type='text/javascript'>
	postData("/dynamic/people/page/popup_create_people?types=contact_<?php echo $creator;?>&ondone=<?php echo urlencode($_GET["ondone"]);?>", {
		fixed_columns: [
		  {table:"ContactPoint",column:"organization",value:<?php echo json_encode($_GET["org"]);?>}
		]
	});
	</script>
	<?php 
}
	}
	
}
?>