<?php 
class page_administration extends Page {
	
	public function getRequiredRights() { return array(); } // TODO add right
	
	public function execute() {
		$this->requireJavascript("section.js");
		$this->onload("sectionFromHTML('section_status');");
		$this->onload("sectionFromHTML('section_lost_entities');");
		$this->onload("sectionFromHTML('section_invalid_keys');");
?>
<div id='section_status' title='Database Status' collapsable='true' style='margin:10px'>
	<div style='padding:5px;background-color:white;'>
		<?php
		$status = array(); 
		global $db_config;
		foreach (PNApplication::$instance->getDomains() as $domain=>$conf) {
			$res = SQLQuery::getDataBaseAccessWithoutSecurity()->execute("SHOW TABLE STATUS IN `".$db_config["prefix"].$domain."`");
			$rows = array();
			while (($row = SQLQuery::getDataBaseAccessWithoutSecurity()->nextRow($res)) <> null)
				array_push($rows, $row);
			$status[$domain] = $rows;
		}
		echo "<div class='page_section_title2'>General Status</div>";
		foreach ($status as $domain=>$rows) {
			$size = 0;
			foreach ($rows as $row) $size += intval($row["Data_length"])+intval($row["Index_length"]);
			echo "Database of $domain: ".$this->size($size)."<br/>";
		}
		echo "<div class='page_section_title2'>Status per table</div>";
		foreach ($status as $domain=>$rows) {
			echo "<div id='section_domain_tables_$domain' css='soft' title='Domain $domain' collapsable='true' collapsed='true'>";
			echo "<table>";
			echo "<tr><th>Table</th><th>Rows</th><th>Data size</th><th>Indexes size</th><th>Auto increment</th></tr>";
			foreach ($rows as $row) {
				echo "<tr>";
				echo "<td>".toHTML($row["Name"])."</td>";
				echo "<td align='right'>".$row["Rows"]."</td>";
				echo "<td align='right'>".$this->size($row["Data_length"])."</td>";
				echo "<td align='right'>".$this->size($row["Index_length"])."</td>";
				$ai = $row["Auto_increment"];
				if ($ai == null) echo "<td></td>";
				else {
					require_once("component/data_model/Model.inc");
					$table = null;
					foreach (DataModel::get()->internalGetTables() as $t) {
						if ($t->getModel() instanceof SubDataModel) {
							$n = strtolower($t->getName());
							$n2 = strtolower($row["Name"]);
							if (substr($n2,0,strlen($n)+1) == $n."_") { $table = $t; break; }
						} else {
							if (strtolower($t->getName()) == strtolower($row["Name"])) { $table = $t; break; }
						}
					}
					if ($table == null) $color = "black";
					else {
						$pk = $table->getPrimaryKey();
						$bits = $pk->size;
						$r = $ai;
						while ($bits > 3 && $r > 0) {
							$r = floor($r/2);
							$bits--;
						}
						if ($r == 0) $color = "green";
						else if (floor($r/2) == 0) $color = "orange";
						else $color = "red";
					}
					echo "<td style='color:$color' align='right'>$ai</td>";
				}
				echo "</tr>";
			}
			echo "</table>";
			echo "</div>";
			$this->onload("sectionFromHTML('section_domain_tables_$domain');");
		}
		?>
	</div>
</div>
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

	function size($size) {
		if ($size >= 1024*1024*1024) return number_format($size/(1024*1024*1024),2)." GB";
		if ($size >= 1024*1024) return number_format($size/(1024*1024),2)." MB";
		if ($size >= 1024) return number_format($size/(1024),2)." KB";
		return $size." B";
	}
}
?>