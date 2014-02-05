<?php 
class page_administration extends Page {
	
	public function get_required_rights() { return array(); } // TODO add right
	
	public function execute() {
		$this->require_javascript("section.js");
		$this->onload("section_from_html('section_lost_entities');");
?>
<div id='section_lost_entities' title='Lost data' collapsable='true' style='margin:10px'>
	<div id='lost_entities'>
		<img src='<?php echo theme::$icons_16["loading"];?>'/>
	</div>
</div>
<script type='text/javascript'>
service.json("data_model","find_lost_entities",{},function(list) {
	var container = document.getElementById('lost_entities');
	if (!list || list.length == 0) {
		container.innerHTML = "<img src='"+theme.icons_16.ok+"' style='vertical-align:bottom'/> No lost data.";
	} else {
		container.innerHTML = "<img src='"+theme.icons_16.error+"' style='vertical-align:bottom'/> "+list.length+" table(s) contain lost data:";
		for (var i = 0; i < list.length; ++i) {
			var content = document.createElement("TABLE");
			content.className = 'all_borders';
			var tr, td;
			content.appendChild(tr = document.createElement("TR"));
			for (var name in list[i].rows[0]) {
				tr.appendChild(td = document.createElement("TH"));
				td.appendChild(document.createTextNode(name));
			}
			for (var j = 0; j < list[i].rows.length; ++j) {
				content.appendChild(tr = document.createElement("TR"));
				for (var name in list[i].rows[j]) {
					tr.appendChild(td = document.createElement("TD"));
					td.appendChild(document.createTextNode(list[i].rows[j][name]));
				}
			}
			var sec = new section(null, list[i].table, content, true);
			sec.element.style.margin = "5px";
			container.appendChild(sec.element);
		}
	}
});
</script>
<?php 
	}
	
}
?>