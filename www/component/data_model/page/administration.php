<?php 
class page_administration extends Page {
	
	public function getRequiredRights() { return array(); } // TODO add right
	
	public function execute() {
		$this->requireJavascript("section.js");
		$this->onload("sectionFromHTML('section_lost_entities');");
		$this->onload("sectionFromHTML('section_invalid_keys');");
?>
<div id='section_lost_entities' title='Lost data' collapsable='true' style='margin:10px'>
	<div id='lost_entities'>
		<button onclick="lostEntities();" class='action'>Search for lost entities</button>
	</div>
</div>
<div id='section_invalid_keys' title='Invalid keys' collapsable='true' style='margin:10px'>
	<div id='invalid_keys'>
		<button onclick="invalidKeys();" class='action'>Search for invalid keys</button>
	</div>
</div>
<script type='text/javascript'>
function lostEntities() {
	var container = document.getElementById('lost_entities');
	container.innerHTML = "<img src='"+theme.icons_16.loading+"'/> This may take a while because we need to analyze deeply the database... Please be patient...";
	service.json("data_model","find_lost_entities",{},function(list) {
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
}
function invalidKeys() {
	var container = document.getElementById('invalid_keys');
	container.innerHTML = "<img src='"+theme.icons_16.loading+"'/>";
	service.json("data_model","find_invalid_keys",{},function(list) {
		if (!list || list.length == 0) {
			container.innerHTML = "<img src='"+theme.icons_16.ok+"' style='vertical-align:bottom'/> All keys are valid.";
		} else {
			container.innerHTML = "<img src='"+theme.icons_16.error+"' style='vertical-align:bottom'/> "+list.length+" table(s) contain invalid keys:";
			for (var i = 0; i < list.length; ++i) {
				var content = document.createElement("DIV");
				for (var j = 0; j < list[i].columns.length; ++j) {
					var div = document.createElement("DIV");
					var s = "";
					for (var k = 0; k < list[i].columns[j].keys.length; ++k) {
						if (s.length > 0) s += ", ";
						s += list[i].columns[j].keys[k];
					}
					div.appendChild(document.createTextNode("Column "+list[i].columns[j].name+": "+s));
					content.appendChild(div);
				}
				var sec = new section(null, list[i].table, content, true);
				sec.element.style.margin = "5px";
				container.appendChild(sec.element);
			}
		}
	});
}
</script>
<?php 
	}
	
}
?>