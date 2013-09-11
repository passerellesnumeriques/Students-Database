<?php 
class page_home extends Page {

	public function get_required_rights() { return array(); }
	
	public function execute() {
		$exclude_components = array("development","test");
		
		$this->add_javascript("/static/widgets/collapsable_section/collapsable_section.js");

		echo "Components<br/>";
		$components = PNApplication::sort_components_by_dependencies();
		foreach ($components as $c) {
			if (in_array($c->name, $exclude_components)) continue;
			echo "<div id='section_component_".$c->name."' class='collapsable_section' style='margin:3px'>";
			echo "<div class='collapsable_section_header'>".$c->name."</div>";
			echo "<div class='collapsable_section_content' style='padding:3px'><img id='loading_".$c->name."' src='".theme::$icons_16["loading"]."'/></div>";
			echo "</div>";
			$this->onload("new collapsable_section('section_component_".$c->name."');");
		}

?>
General reports<br/>
phpmd<br/>
<div id='phpmd' style='width:100%;height:350px;overflow:auto'>
<img src='<?php echo theme::$icons_16["loading"];?>'/>
</div>

<script type='text/javascript'>
function load_tests(component, ondone) {
	service.json("test","get_tests",{component:component},function(tests){
		var loading = document.getElementById('loading_'+component);
		if (!loading) { alert('No loading for component '+component); ondone(); return; }
		var content = loading.parentNode;
		content.innerHTML = "";
		if (!tests) { ondone(); return; }
		tests.func_div = document.createElement("DIV");
		tests.services_div = document.createElement("DIV");
		content.appendChild(tests.func_div);
		content.appendChild(tests.services_div);
		if (tests.functions == null) {
			tests.func_div.innerHTML = "No function provided.";
		} else {
			tests.func_div.innerHTML = "Functionalities";
			tests.func_content = document.createElement("DIV");
			tests.func_content.style.marginLeft = "10px";
			tests.func_div.appendChild(tests.func_content);
			if (tests.functions.not_covered.length > 0) {
				var div = document.createElement("DIV");
				div.innerHTML = "<img src='"+theme.icons_16.error+"' style='vertical-align:bottom'/> Not covered: ";
				for (var i = 0; i < tests.functions.not_covered.length; ++i) {
					if (i>0) div.innerHTML += ", ";
					div.innerHTML += tests.functions.not_covered[i];
				}
				tests.func_content.appendChild(div);
			}
		}
		if (tests.services == null) {
			tests.services_div.innerHTML = "No service provided.";
		} else {
			tests.services_div.innerHTML = "Services";
			tests.services_content = document.createElement("DIV");
			tests.services_content.style.marginLeft = "10px";
			tests.services_div.appendChild(tests.services_content);
			if (tests.services.not_covered.length > 0) {
				var div = document.createElement("DIV");
				div.innerHTML = "<img src='"+theme.icons_16.error+"' style='vertical-align:bottom'/> Not covered: ";
				for (var i = 0; i < tests.services.not_covered.length; ++i) {
					if (i>0) div.innerHTML += ", ";
					div.innerHTML += tests.services.not_covered[i];
				}
				tests.services_content.appendChild(div);
			}
		}
		process_functions_tests(component, tests, ondone);
	});
}
function process_functions_tests(component, tests, ondone) {
	if (tests.functions == null) { process_services_tests(component, tests, ondone); return; }
	if (tests.functions.scenarios.length == 0) {
		var div = document.createElement("DIV");
		div.innerHTML = "No test defined.";
		tests.func_content.appendChild(div);
		process_services_tests(component, tests, ondone);
		return;
	}
	for (var i = 0; i < tests.functions.scenarios.length; ++i) {
		tests.functions.scenarios[i].div = document.createElement("DIV");
		tests.functions.scenarios[i].div.innerHTML = tests.functions.scenarios[i].name;
		tests.functions.scenarios[i].icon = document.createElement("IMG");
		tests.functions.scenarios[i].icon.src = theme.icons_16.wait;
		tests.functions.scenarios[i].icon.style.verticalAlign = "bottom";
		tests.functions.scenarios[i].div.appendChild(tests.functions.scenarios[i].icon);
		tests.functions.scenarios[i].div.padding = "3px";
		for (var j = 0; j < tests.functions.scenarios[i].steps.length; ++j) {
			var step = {name:tests.functions.scenarios[i].steps[j]};
			tests.functions.scenarios[i].steps[j] = step;
			step.div = document.createElement("DIV");
			step.div.innerHTML = step.name;
			step.div.style.marginLeft = "10px";
			tests.functions.scenarios[i].div.appendChild(step.div);			
		}
		tests.func_content.appendChild(tests.functions.scenarios[i].div);
	}
	var pos = 0;
	var next_scenario = function() {
		if (pos == tests.functions.scenarios.length) { process_services_tests(component, tests, ondone); return; }
		tests.functions.scenarios[pos].icon.src = theme.icons_16.loading;
		service.json("test","execute_functionalities_scenario",{component:component,scenario:tests.functions.scenarios[pos].path},function(res_scenario){
			var success = true;
			if (!res_scenario)
				success = false;
			else {
				for (var i = 0; i < res_scenario.length; ++i) {
					if (res_scenario[i] == "OK")
						tests.functions.scenarios[pos].steps[i].div.innerHTML += " <img src='"+theme.icons_16.ok+"' style='vertical-align:bottom'/>";
					else {
						success = false;
						if (res_scenario[i] != null)
							tests.functions.scenarios[pos].steps[i].div.innerHTML += " <img src='"+theme.icons_16.error+"' style='vertical-align:bottom'/> "+res_scenario[i];
					} 
				}
			}
			if (!success) {
				tests.functions.scenarios[pos].icon.src = theme.icons_16.error;
				// TODO details
				while (++pos < tests.functions.scenarios.length)
					tests.functions.scenarios[pos].icon.src = theme.icons_16.error;
				process_services_tests(component, tests, ondone);
				return;
			} else {
				tests.functions.scenarios[pos].icon.src = theme.icons_16.ok;
				pos++;
				next_scenario();
			}				
		});		
	};
	next_scenario();
}
function process_services_tests(component, tests, ondone) {
	if (tests.services == null) { ondone(); return; }
	if (tests.services.scenarios.length == 0) {
		var div = document.createElement("DIV");
		div.innerHTML = "No test defined.";
		tests.services_content.appendChild(div);
		ondone();
		return;
	}
	for (var i = 0; i < tests.services.scenarios.length; ++i) {
		tests.services.scenarios[i].div = document.createElement("DIV");
		tests.services.scenarios[i].div.innerHTML = tests.services.scenarios[i].name;
		tests.services.scenarios[i].icon = document.createElement("IMG");
		tests.services.scenarios[i].icon.src = theme.icons_16.wait;
		tests.services.scenarios[i].icon.style.verticalAlign = "bottom";
		tests.services.scenarios[i].div.appendChild(tests.services.scenarios[i].icon);
		tests.services.scenarios[i].div.padding = "3px";
		for (var j = 0; j < tests.services.scenarios[i].steps.length; ++j) {
			var step = {name:tests.services.scenarios[i].steps[j]};
			tests.services.scenarios[i].steps[j] = step;
			step.div = document.createElement("DIV");
			step.div.innerHTML = step.name;
			step.div.style.marginLeft = "10px";
			tests.services.scenarios[i].div.appendChild(step.div);			
		}
		tests.services_content.appendChild(tests.services.scenarios[i].div);
	}
	var pos = 0;
	var next_scenario = function() {
		if (pos == tests.services.scenarios.length) { ondone(); return; }
		tests.services.scenarios[pos].icon.src = theme.icons_16.loading;
		service.json("test","execute_services_scenario",{component:component,scenario:tests.services.scenarios[pos].path},function(res_scenario){
			var success = true;
			if (!res_scenario)
				success = false;
			else {
				for (var i = 0; i < res_scenario.length; ++i) {
					if (res_scenario[i] == "OK")
						tests.services.scenarios[pos].steps[i].div.innerHTML += " <img src='"+theme.icons_16.ok+"' style='vertical-align:bottom'/>";
					else {
						success = false;
						if (res_scenario[i] != null)
							tests.services.scenarios[pos].steps[i].div.innerHTML += " <img src='"+theme.icons_16.error+"' style='vertical-align:bottom'/> "+res_scenario[i];
					} 
				}
			}
			if (!success) {
				tests.services.scenarios[pos].icon.src = theme.icons_16.error;
				// TODO details
				while (++pos < tests.services.scenarios.length)
					tests.services.scenarios[pos].icon.src = theme.icons_16.error;
				ondone();
				return;
			} else {
				tests.services.scenarios[pos].icon.src = theme.icons_16.ok;
				pos++;
				next_scenario();
			}				
		});		
	};
	next_scenario();
}

var current_component = -1;
var phpmd_done = false;
function test_next_component() {
	current_component++;
	if (current_component >= components.length) {
		if (!phpmd_done) {
			phpmd_done = true;
			ajax.post("/dynamic/test/service/phpmd","",function(error){
				window.top.status_manager.add_status(new window.top.StatusMessageError(null,error,10000));
			},function(xhr) {
				var xml = xhr.responseXML.childNodes[0];

				// remove unwanted rules
				for (var i = 0; i < xml.childNodes.length; ++i) {
					var node = xml.childNodes[i];
					if (node.nodeType != 1) { xml.removeChild(node); i--; continue; }
					if (node.nodeName != "file") { xml.removeChild(node); i--; continue; }
					for (var j = 0; j < node.childNodes.length; ++j) {
						var viol = node.childNodes[j];
						if (viol.nodeType != 1) { node.removeChild(viol); j--; continue; }
						if (viol.nodeName != "violation") { node.removeChild(viol); j--; continue; }
						if (viol.getAttribute("rule") == "StaticAccess") {
							node.removeChild(viol); j--;
						}
					}
					if (node.childNodes.length == 0) { xml.removeChild(node); i--; continue; }
				}
				
				var div = document.getElementById('phpmd');
				div.innerHTML = "";
				var table = document.createElement("TABLE"); div.appendChild(table);
				var tr = document.createElement("TR"); table.appendChild(tr);
				var td;
				tr.appendChild(td = document.createElement("TH")); td.innerHTML = "Line";
				tr.appendChild(td = document.createElement("TH")); td.innerHTML = "Rule Set";
				tr.appendChild(td = document.createElement("TH")); td.innerHTML = "Rule";
				tr.appendChild(td = document.createElement("TH")); td.innerHTML = "Message";
				for (var i = 0; i < xml.childNodes.length; ++i) {
					var node = xml.childNodes[i];
					table.appendChild(tr = document.createElement("TR"));
					tr.appendChild(td = document.createElement("TH"));
					td.colSpan = 4;
					td.innerHTML = node.getAttribute("name");
					for (var j = 0; j < node.childNodes.length; ++j) {
						var viol = node.childNodes[j];
						table.appendChild(tr = document.createElement("TR"));
						tr.appendChild(td = document.createElement("TD")); td.innerHTML = viol.getAttribute("beginline");
						tr.appendChild(td = document.createElement("TD")); td.innerHTML = viol.getAttribute("ruleset");
						tr.appendChild(td = document.createElement("TD")); td.innerHTML = viol.getAttribute("rule");
						var text = "";
						for (var k = 0; k < viol.childNodes.length; ++k)
							if (viol.childNodes[k].nodeType == 3) { text = viol.childNodes[k].nodeValue; break; }
						tr.appendChild(td = document.createElement("TD")); td.innerHTML = text;
					}
				}
				test_next_component();
			});
		}
		return;
	}
	load_tests(components[current_component],function(){
		test_next_component();
	});
}
var components = [
<?php 
$first = true;
foreach (PNApplication::$instance->components as $name=>$c) {
	if (in_array($name, $exclude_components)) continue;
	if ($first) $first = false; else echo ",";
	echo "\"".$name."\"";
}
?>
];
test_next_component();
</script>
<?php
	}
	
}
?>