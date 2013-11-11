<?php
class page_logout extends Page {
	public function get_required_rights() { return array(); }
	public function execute() {
		PNApplication::$instance->user_management->logout();
?>
<script type='text/javascript'>
if (window.top.pn_loading_start) {
	window.top.pn_loading_start();
	window.top.set_loading_message('Loading authentication page...');
	window.location.href = "<?php echo "/dynamic/application/page/enter".(isset($_GET["from"]) ? "?from=".$_GET["from"] : "");?>";
} else {
	window.location.href = "/";
}
</script>
<?php 
	}
} 
?>