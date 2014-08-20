<?php 
class page_publish extends Page {
	
	public function getRequiredRights() { return array("edit_transcripts_design"); }
	
	public function execute() {
		$period = $_GET["period"];
		$spe = isset($_GET["specialization"]) ? $_GET["specialization"] : null;
		$existing = SQLQuery::create()->select("PublishedTranscript")->whereValue("PublishedTranscript","period",$period)->whereValue("PublishedTranscript","specialization",$spe)->execute();
?>
<div style='background-color:white;display:flex;flex-direction:column;'>
	<div class='info_box'>
		<img src='<?php echo theme::$icons_16["info"];?>'/>
		Once published, the students will be able to see their own transcript.<br/>
		All grades and comments will be fixed. In other words, any modification of grades,<br/>
		or transcripts' design won't affect	a published transcript.<br/>
		This allow you to take a <i>picture</i> of the actual situation, either in the<br/>
		middle of the academic period or at the end, and keep it as an official record.
	</div>
	<div style='padding:10px'>
		Publish actual situation:<br/>
		<form name='publish' onsubmit='return false'>
		<input type='radio' name='action' value='new' checked='checked'/> as new transcripts with name <input type='text' name='name'/><br/>
		<?php
		foreach ($existing as $t)
			echo "<input type='radio' name='action' value='".$t["id"]."'/> replace ".htmlentities($t["name"])."<br/>";
		?>
		</form>
	</div>
</div>
<script type='text/javascript'>
var popup = window.parent.get_popup_window_from_frame(window);
popup.addOkCancelButtons(function() {
	require("form.js",function() {
		var data = {
			period: <?php echo $period;?>,
			specialization: <?php echo json_encode($spe);?>
		};
		var form = document.forms['publish'];
		var val = get_radio_value(form,'action');
		if (val == 'new') {
			data.name = form.elements['name'].value.trim();
			if (data.name.length == 0) { alert("Please specify a name for the new publication"); return; }
		} else
			data.id = val;
		popup.freeze("Publishing transcripts...");
		service.json("transcripts","publish",data,function(res){
			if (res) popup.close();
			else popup.unfreeze();
		});
	});
});
</script>
<?php 
	}
	
}
?>