<?php 
class page_popup_create_student extends Page {
	
	public function get_required_rights() { return array(); }
	
	public function execute() {
?>
<script type='text/javascript'>
postData("/dynamic/people/page/popup_create_people?types=student<?php if (isset($_GET["ondone"])) echo "&ondone=".urlencode($_GET["ondone"]);?>", {
	prefilled_data: [<?php if (isset($_GET["batch"])) {?>{table:"Student",data:"Batch",value:<?php echo $_GET["batch"];?>}<?php }?>]
});
</script>
<?php 
	}
	
}
?>