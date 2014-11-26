<?php
class page_logout extends Page {
	public function getRequiredRights() { return array(); }
	public function mayUpdateSession() { return true; }
	public function execute() {
		PNApplication::$instance->user_management->logout();
?>
<script type='text/javascript'>
if (window.top.pnapplication) {
	window.top.pnapplication.logged_in = false;
	window.top.pnapplication.onlogout.fire();
}
if (window.top.pn_loading_start) {
	window.top.pn_loading_start();
	window.top.set_loading_message('Logging out...');
	window.location.href = "<?php echo "/dynamic/application/page/enter?".(isset($_GET["from"]) ? "&from=".$_GET["from"] : "").(isset($_GET["testing"]) ? "&testing=".$_GET["testing"] : "");?>";
} else {
	window.location.href = "/?<?php (isset($_GET["from"]) ? "&from=".$_GET["from"] : "").(isset($_GET["testing"]) ? "&testing=".$_GET["testing"] : "")?>";
}
</script>
<?php 
	}
} 
?>