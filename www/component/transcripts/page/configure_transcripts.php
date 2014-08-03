<?php 
class page_configure_transcripts extends Page {
	
	public function getRequiredRights() { return array("edit_transcripts_design"); } // TODO
	
	public function execute() {
		$app_conf = SQLQuery::create()->bypassSecurity()
#DEV
			->noWarning() // TODO
#END
			->select("ApplicationConfig")->where("`name` LIKE 'transcripts_%'")->execute();
		$config = array();
		foreach ($app_conf as $ac) $config[substr($ac["name"],12)] = $ac["value"];
		
		?>
<div style='width:100%;height:100%;display:flex;flex-direction:column'>
	<div class='page_title' style='flex:none'>
		Design transcripts
	</div>
	<div style='flex:1 1 auto;display:flex;flex-direction:row'>
		<div style='flex:none;overflow:auto;background-color:white;box-shadow:2px 2px 2px 0px #808080;margin-right:5px;'>
			<div class='page_section_title'>
				Information to include
			</div>
			TODO
			<div class='page_section_title'>
				General configuration
			</div>
			<table>
				<tr>
					<td>Location</td>
					<td><input type='text' size=10 value="<?php echo htmlentities(@$config["location"]);?>" onchange="saveAppConfig('location',this.value);"/></td>
				</tr>
				<tr>
					<td>Signatory Name</td>
					<td><input type='text' size=10 value="<?php echo htmlentities(@$config["signatory_name"]);?>" onchange="saveAppConfig('signatory_name',this.value);"/></td>
				</tr>
				<tr>
					<td>Signatory Title</td>
					<td><input type='text' size=10 value="<?php echo htmlentities(@$config["signatory_title"]);?>" onchange="saveAppConfig('signatory_title',this.value);"/></td>
				</tr>
			</table>
			<div class='page_section_title'>
				Display settings
			</div>
			TODO
		</div>
		<div style='flex:1 1 auto;overflow:auto;text-align:center'>
			<div id='design' class='transcripts' style='text-align:left;background-color:white;border-radius:5px;display:inline-block;box-shadow: 2px 2px 2px 0px #808080;width:590px;height:770px;margin-bottom:5px;'>
				<?php require_once("design.inc"); generate_transcript();?>
			</div>
		</div>
	</div>
</div>
<script type='text/javascript'>
function saveAppConfig(name, value) {
	var locker = lock_screen(null, "Saving");
	service.json("transcripts","save_transcripts_app_config",{name:name,value:value},function(res) {
		unlock_screen(locker);
		refreshDesign();
	});
}
function refreshDesign() {
	var locker = lock_screen(null, "Reloading transcript");
	service.customOutput("transcripts","generate_transcript",{},function(html) {
		unlock_screen(locker);
		document.getElementById('design').innerHTML = html;
	});
}
</script>
		<?php 
	}
	
}
?>