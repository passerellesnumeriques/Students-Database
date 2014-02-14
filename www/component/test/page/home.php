<?php 
class page_home extends Page {

	public function get_required_rights() { return array(); }
	
	public function execute() {
		$exclude_components = array("development","test");
		
		$this->add_javascript("/static/widgets/header_bar.js");
		$this->add_javascript("/static/widgets/vertical_layout.js");
		$this->add_javascript("/static/widgets/horizontal_layout.js");
		$this->add_javascript("/static/widgets/vertical_align.js");
		$this->add_javascript("/static/development/debug_status.js");
		$this->add_javascript("/static/test/browser_control.js");
		
		$components = PNApplication::sort_components_by_dependencies();
		
		echo "<div id='page' style='height:100%;width:100%'>";
			echo "<div id='top_status' layout='fixed' icon='/static/test/test_32.png' title='Automatic Tests'></div>";
			echo "<div style='overflow:auto' layout='fill' id='components'>";
			echo "</div>";
		echo "</div>";
		
?>
<!-- General reports<br/>
phpmd<br/>
<div id='phpmd' style='width:100%;height:350px;overflow:auto'>
<img src='<?php echo theme::$icons_16["loading"];?>'/>
</div>
 -->

<script type='text/javascript'>
var components = [
<?php 
$first = true;
foreach (PNApplication::$instance->components as $name=>$c) {
	if (in_array($name, $exclude_components)) continue;
	if (substr($name,0,4) == "lib_") continue;
	if ($first) $first = false; else echo ",";
	echo "{name:".json_encode($name);
	echo "}";
}
?>
];

function top_status_widget() {
	container = document.getElementById('top_status');
	var t=this;
	t.widget = new header_bar(container);

	t.refresh = document.createElement("IMG");
	t.refresh.className = 'button';
	t.refresh.style.verticalAlign = "bottom";
	t.refresh.src = theme.icons_16.refresh;
	t.refresh.onclick = function() { location.reload(); };
	t.widget.menu_container.appendChild(t.refresh);

	var span_debug = document.createElement("SPAN");
	span_debug.className = 'button';
	t.widget.menu_container.appendChild(span_debug);
	new debug_status(span_debug);

	t.play_all = document.createElement("DIV");
	t.play_all.className = 'button disabled';
	t.play_all.innerHTML = "<img src='/static/test/play.png' style='vertical-align:bottom'/> Launch all tests";
	t.widget.menu_container.appendChild(t.play_all);

	t.widget.menu_container.appendChild(t.span_nb_scenarios = document.createElement("SPAN"));
	t.widget.menu_container.appendChild(t.span_scenarios_waiting = document.createElement("SPAN"));
	t.widget.menu_container.appendChild(t.span_scenarios_succeed = document.createElement("SPAN"));
	t.widget.menu_container.appendChild(t.span_scenarios_failed = document.createElement("SPAN"));
	
	t.waiting_components = 0;
	
	fireLayoutEventFor(container);

	t.getTotalScenarios = function() {
		var nb = 0;
		for (var i = 0; i < components.length; ++i)
			if (components[i].widget) nb += components[i].widget.getTotalScenarios();
		return nb;
	};
	t.getNbScenariosSucceed = function() {
		var nb = 0;
		for (var i = 0; i < components.length; ++i)
			if (components[i].widget) nb += components[i].widget.getNbScenariosSucceed();
		return nb;
	};
	t.getNbScenariosFailed = function() {
		var nb = 0;
		for (var i = 0; i < components.length; ++i)
			if (components[i].widget) nb += components[i].widget.getNbScenariosFailed();
		return nb;
	};
	t.getNbScenariosWaiting = function() {
		var nb = 0;
		for (var i = 0; i < components.length; ++i)
			if (components[i].widget) nb += components[i].widget.getNbScenariosWaiting();
		return nb;
	};
	
	t.update_status = function() {
		var nb = t.getTotalScenarios();
		t.span_nb_scenarios.innerHTML = nb+" scenario"+(nb>1?"s":"")+(nb>0?":":"");
		nb = t.getNbScenariosWaiting();
		if (nb == 0)
			t.span_scenarios_waiting.innerHTML = "";
		else
			t.span_scenarios_waiting.innerHTML = "<img src='"+theme.icons_16.wait+"' style='vertical-align:middle;padding-left:3px'/> "+nb+" not run";
		if (nb == 0 || t.waiting_components > 0) {
			t.play_all.className = 'button disabled';
			t.play_all.onclick = null;
		} else {
			t.play_all.className = 'button';
			t.play_all.onclick = function() { t.launch_all(); };
		}
		nb = t.getNbScenariosSucceed();
			if (nb == 0)
				t.span_scenarios_succeed.innerHTML = "";
			else
				t.span_scenarios_succeed.innerHTML = "<img src='"+theme.icons_16.ok+"' style='vertical-align:middle;padding-left:3px'/> "+nb+" succeed";
		nb = t.getNbScenariosFailed();
		if (nb == 0)
			t.span_scenarios_failed.innerHTML = "";
		else
			t.span_scenarios_failed.innerHTML = "<img src='"+theme.icons_16.error+"' style='vertical-align:middle;padding-left:3px'/> "+nb+" failed";
	};
	t.update_status();

	t.launch_all = function(ondone) {
		for (var i = 0; i < components.length; ++i) {
			components[i].widget.disable_refresh();
			components[i].widget.disable_launch_all();
		}
		var next = function(pos) {
			if (pos == components.length) {
				if (ondone) ondone();
				return;
			}
			components[pos].widget.launch_all(function(){
				components[pos].widget.enable_refresh();
				next(pos+1);
			});
		};
		next(0);
	};

	t.component_loading = function(component) {
		t.waiting_components++;
		t.play_all.className = 'button disabled';
		t.play_all.onclick = null;
		t.update_status();
	};
	t.component_loaded = function(component) {
		--t.waiting_components;
		t.update_status();
	};

}
var top_status = new top_status_widget();
new vertical_layout('page');

function load_tests(component, ondone) {
	var loading = document.createElement("IMG");
	loading.src = theme.icons_16.loading;
	var content = component.widget.collapsable.content;
	content.innerHTML = "";
	content.appendChild(loading);
	service.json("test","get_tests",{component:component.name},function(tests){
		component.tests = tests;
		content.innerHTML = "";
		if (!tests) {
			content.innerHTML = "Error while calling the service"; 
			if (ondone) ondone();
			return; 
		}

		// first check what is provided
		if (tests.functions == null) {
			var div = document.createElement("DIV");
			div.innerHTML  = "No function provided";
			content.appendChild(div);
		}
		if (tests.services == null) {
			var div = document.createElement("DIV");
			div.innerHTML  = "No service provided";
			content.appendChild(div);
		}
		if (tests.ui == null) {
			var div = document.createElement("DIV");
			div.innerHTML  = "No UI test provided";
			content.appendChild(div);
		}
		
		if (tests.functions != null) {
			var table = build_tests_table("Functionalities", component, tests.functions, play_function_test);
			content.appendChild(table);
			for (var i  = 0; i < tests.functions.scenarios.length; ++i)
				tests.functions.scenarios[i].status = 0;
		}
		if (tests.services != null) {
			var table = build_tests_table("Services", component, tests.services, play_service_test);
			content.appendChild(table);
			for (var i  = 0; i < tests.services.scenarios.length; ++i)
				tests.services.scenarios[i].status = 0;
		}
		if (tests.ui != null) {
			var table = build_tests_table("UI", component, tests.ui, play_ui_test);
			content.appendChild(table);
			for (var i  = 0; i < tests.services.scenarios.length; ++i)
				tests.ui.scenarios[i].status = 0;
		}
		if (ondone) ondone();
	});
}

function build_tests_table(title, component, list, play_function) {
	var table = document.createElement("TABLE");
	table.style.borderCollapse = "collapse";
	table.style.borderSpacing = "0px";
	table.style.border = "1px solid black";
	table.style.marginTop = "3px";
	table.style.width = "100%";
	table.style.overflowX = "auto";
	table.style.display = "block";
	var tr, td;
	table.appendChild(tr = document.createElement("TR"));
	tr.appendChild(td = document.createElement("TD"));
	td.innerHTML = title;
	td.colSpan = 3;
	td.style.fontWeight = "bold";
	td.style.backgroundColor = "#C0C0C0";
	td.style.borderBottom = "1px solid black";
	var columns = list.scenarios.length > 0 && list.scenarios[0].steps ? 3 : 2;
	if (list.not_covered && list.not_covered.length > 0) {
		table.appendChild(tr = document.createElement("TR"));
		tr.appendChild(td = document.createElement("TD"));
		td.innerHTML = "<img src='"+theme.icons_16.error+"' style='vertical-align:bottom'/> Not covered: ";
		td.colSpan = columns;
		td.style.borderBottom = "1px solid black";
		for (var i = 0; i < list.not_covered.length; ++i) {
			if (i>0) td.innerHTML += ", ";
			td.innerHTML += list.not_covered[i];
		}
	}
	if (list.scenarios.length == 0) {
		table.appendChild(tr = document.createElement("TR"));
		tr.appendChild(td = document.createElement("TD"));
		td.innerHTML = "<img src='"+theme.icons_16.warning+"' style='vertical-align:bottom'/> No test defined";
		td.colSpan = columns;
		td.style.borderBottom = "1px solid black";
	} else {
		table.appendChild(tr = document.createElement("TR"));
		tr.appendChild(td = document.createElement("TD"));
		td.innerHTML = "Scenario";
		td.style.fontWeight = "bold";
		td.style.backgroundColor = "#D0D0E0";
		td.style.border = "1px solid black";
		if (columns == 3) {
			tr.appendChild(td = document.createElement("TD"));
			td.innerHTML = "Steps";
			td.style.fontWeight = "bold";
			td.style.backgroundColor = "#D0D0E0";
			td.style.border = "1px solid black";
		}
		tr.appendChild(td = document.createElement("TD"));
		td.innerHTML = "Result";
		td.style.fontWeight = "bold";
		td.style.backgroundColor = "#D0D0E0";
		td.style.border = "1px solid black";
		for (var i = 0; i < list.scenarios.length; ++i) {
			if (columns == 3)
				for (var j = 0; j < list.scenarios[i].steps.length; ++j)
					list.scenarios[i].steps[j] = {name:list.scenarios[i].steps[j]};
			table.appendChild(tr = document.createElement("TR"));

			tr.appendChild(td = document.createElement("TD"));
			td.style.border = "1px solid black";
			if (columns == 3)
				td.rowSpan = list.scenarios[i].steps.length+1;
			td.style.verticalAlign = "top";
			td.style.whiteSpace = "nowrap";
			td.innerHTML = ""+(i+1)+"- "+list.scenarios[i].name;
			list.scenarios[i].button = document.createElement("IMG");
			list.scenarios[i].button.className = 'button';
			list.scenarios[i].button.src = '/static/test/play.png';
			list.scenarios[i].button.style.verticalAlign = "bottom";
			list.scenarios[i].button.component = component;
			list.scenarios[i].button.scenario = i;
			list.scenarios[i].button.onclick = function() {
				component.widget.disable_refresh();
				component.widget.disable_launch_all();
				play_function(this.component, this.scenario, function() {
					component.widget.enable_refresh();
				});
			};
			td.appendChild(list.scenarios[i].button);

			if (columns == 3) {
				tr.appendChild(td = document.createElement("TD"));
				td.style.border = "1px solid black";
				td.style.whiteSpace = "nowrap";
				td.innerHTML = ""+(i+1)+".0- Initialize database";
				list.scenarios[i].init_step = {};
				list.scenarios[i].init_step.container = td;

				tr.appendChild(td = document.createElement("TD"));
				td.style.border = "1px solid black";
				td.style.whiteSpace = "nowrap";
				list.scenarios[i].init_step.result_container = td;

				for (var j = 0; j < list.scenarios[i].steps.length; ++j) {
					table.appendChild(tr = document.createElement("TR"));
					tr.appendChild(td = document.createElement("TD"));
					td.style.border = "1px solid black";
					td.style.whiteSpace = "nowrap";
					td.innerHTML = ""+(i+1)+"."+(j+1)+"- "+list.scenarios[i].steps[j].name;
					list.scenarios[i].steps[j].container = td;
					tr.appendChild(td = document.createElement("TD"));
					td.style.whiteSpace = "nowrap";
					td.style.border = "1px solid black";
					list.scenarios[i].steps[j].result_container = td;
				}
			} else {
				tr.appendChild(td = document.createElement("TD"));
				td.style.border = "1px solid black";
				td.style.whiteSpace = "nowrap";
				list.scenarios[i].result_container = td;
			}
		}
	}
	return table;
}

function component_widget(component) {
	var t=this;
	component.widget = this;

	t.collapsable = new collapsable_section();
	t.collapsable.toggle();
	t.collapsable.element.style.display = "block";
	t.collapsable.element.style.marginBottom = "2px";
	t.collapsable.content.style.padding = "5px";
	t.collapsable.content.style.overflowX = "auto";
	document.getElementById('components').appendChild(t.collapsable.element);
	t.collapsable_header = document.createElement("DIV");
	t.collapsable.header.appendChild(t.collapsable_header);
	t.title = document.createElement("DIV");
	t.title.style.backgroundColor = "rgba(255,255,255,0.2)";
	t.title.style.borderRight = "1px solid #FFFFFF";
	setBorderRadius(t.title,0,0,0,0,0,0,5,5);
	t.title.style.padding = "2px 5px 2px 5px";
	t.title.style.fontSize = "12pt";
	t.title.style.fontWeight = "bold";
	t.title.innerHTML = component.name;
	t.collapsable_header.appendChild(t.title);
	t.header = document.createElement("DIV");
	t.collapsable_header.appendChild(t.header);
	t.fake_for_collapse = document.createElement("DIV");
	t.collapsable_header.appendChild(t.fake_for_collapse);
	t.title.setAttribute("layout","fixed");
	t.header.setAttribute("layout","fill");
	t.fake_for_collapse.setAttribute("layout","15");
	t.fake_for_collapse.style.position = "absolute";
	t.fake_for_collapse.style.top = "-10000px";
	new horizontal_layout(t.collapsable_header);
	new vertical_align(t.header, "middle");

	t.load = document.createElement("IMG");
	t.load.style.verticalAlign = "bottom";
	t.load.src = theme.icons_16.loading;
	t.load.className = "button disabled";
	t.header.appendChild(t.load);

	t.play_all = document.createElement("DIV");
	t.play_all.innerHTML = "<img src='/static/test/play.png' style='vertical-align:bottom'/> Launch all tests";
	t.play_all.className = "button disabled";
	t.header.appendChild(t.play_all);

	t.header.appendChild(t.span_nb_scenarios = document.createElement("SPAN"));
	t.header.appendChild(t.span_scenarios_waiting = document.createElement("SPAN"));
	t.header.appendChild(t.span_scenarios_succeed = document.createElement("SPAN"));
	t.header.appendChild(t.span_scenarios_failed = document.createElement("SPAN"));

	t.getTotalScenarios = function() {
		if (component.tests == null) return 0;
		var total = 0;
		if (component.tests.functions != null)
			total += component.tests.functions.scenarios.length;
		if (component.tests.services != null)
			total += component.tests.services.scenarios.length;
		if (component.tests.ui != null)
			total += component.tests.ui.scenarios.length;
		return total;
	};
	t.getNbScenariosSucceed = function() {
		if (component.tests == null) return 0;
		var total = 0;
		if (component.tests.functions != null)
			for (var i = 0; i < component.tests.functions.scenarios.length; ++i)
				if (component.tests.functions.scenarios[i].status == 1)
					total++;
		if (component.tests.services != null)
			for (var i = 0; i < component.tests.services.scenarios.length; ++i)
				if (component.tests.services.scenarios[i].status == 1)
					total++;
		if (component.tests.ui != null)
			for (var i = 0; i < component.tests.ui.scenarios.length; ++i)
				if (component.tests.ui.scenarios[i].status == 1)
					total++;
		return total;
	};
	t.getNbScenariosFailed = function() {
		if (component.tests == null) return 0;
		var total = 0;
		if (component.tests.functions != null)
			for (var i = 0; i < component.tests.functions.scenarios.length; ++i)
				if (component.tests.functions.scenarios[i].status == -1)
					total++;
		if (component.tests.services != null)
			for (var i = 0; i < component.tests.services.scenarios.length; ++i)
				if (component.tests.services.scenarios[i].status == -1)
					total++;
		if (component.tests.ui != null)
			for (var i = 0; i < component.tests.ui.scenarios.length; ++i)
				if (component.tests.ui.scenarios[i].status == -1)
					total++;
		return total;
	};
	t.getNbScenariosWaiting = function() {
		if (component.tests == null) return 0;
		var total = 0;
		if (component.tests.functions != null)
			for (var i = 0; i < component.tests.functions.scenarios.length; ++i)
				if (component.tests.functions.scenarios[i].status == 0)
					total++;
		if (component.tests.services != null)
			for (var i = 0; i < component.tests.services.scenarios.length; ++i)
				if (component.tests.services.scenarios[i].status == 0)
					total++;
		if (component.tests.ui != null)
			for (var i = 0; i < component.tests.ui.scenarios.length; ++i)
				if (component.tests.ui.scenarios[i].status == 0)
					total++;
		return total;
	};

	t.disable_refresh = function() {
		t.load.src = theme.icons_16.loading;
		t.load.className = "button disabled";
		t.load.onclick = null;
	};
	t.enable_refresh = function() {
		t.load.src = theme.icons_16.refresh;
		t.load.className = "button";
		t.load.onclick = function(e) { t.reload(); stopEventPropagation(e); return false; };
	};
	t.disable_launch_all = function() {
		t.play_all.className = "button disabled";
		t.play_all.onclick = null;
	};
	t.launch_all = function(ondone) {
		if (component.tests && component.tests.functions)
			for (var i = 0; i < component.tests.functions.scenarios.length; ++i)
				if (component.tests.functions.scenarios[i].status == 0)
					component.tests.functions.scenarios[i].status = -2;
		if (component.tests && component.tests.services)
			for (var i = 0; i < component.tests.services.scenarios.length; ++i)
				if (component.tests.services.scenarios[i].status == 0)
					component.tests.services.scenarios[i].status = -2;
		if (component.tests && component.tests.ui)
			for (var i = 0; i < component.tests.ui.scenarios.length; ++i)
				if (component.tests.ui.scenarios[i].status == 0)
					component.tests.ui.scenarios[i].status = -2;
		t.disable_refresh();
		t.disable_launch_all();
		t.update_status();
		
		var next_ui = function(pos) {
			if (component.tests == null || component.tests.ui == null || pos == component.tests.ui.scenarios.length) {
				t.enable_refresh();
				t.update_status();
				if (ondone) ondone();
				return;
			}
			if (component.tests.ui.scenarios[pos].status != -2) {
				next_ui(pos+1);
				return;
			}
			play_ui_test(component, pos, function() {
				next_ui(pos+1);
			});
		};
		var next_service = function(pos) {
			if (component.tests == null || component.tests.services == null || pos == component.tests.services.scenarios.length) {
				next_ui(0);
				return;
			}
			if (component.tests.services.scenarios[pos].status != -2) {
				next_service(pos+1);
				return;
			}
			play_service_test(component, pos, function() {
				next_service(pos+1);
			});
		};
		var next_function = function(pos) {
			if (component.tests == null || component.tests.functions == null || pos == component.tests.functions.scenarios.length) {
				next_service(0);
				return;
			}
			if (component.tests.functions.scenarios[pos].status != -2) {
				next_function(pos+1);
				return;
			}
			play_function_test(component, pos, function() {
				next_function(pos+1);
			});
		};
		next_function(0);
	};
	
	t.update_status = function() {
		var nb = t.getTotalScenarios();
		t.span_nb_scenarios.innerHTML = nb+" scenario"+(nb>1?"s":"")+(nb>0?":":"");
		nb = t.getNbScenariosWaiting();
		if (nb == 0)
			t.span_scenarios_waiting.innerHTML = "";
		else
			t.span_scenarios_waiting.innerHTML = "<img src='"+theme.icons_16.wait+"' style='vertical-align:middle;padding-left:3px'/> "+nb+" not run";
		if (nb == 0)
			t.disable_launch_all();
		else {
			t.play_all.className = "button";
			t.play_all.onclick = function(e) { t.launch_all(); stopEventPropagation(e); return false; };
		}
		nb = t.getNbScenariosSucceed();
			if (nb == 0)
				t.span_scenarios_succeed.innerHTML = "";
			else
				t.span_scenarios_succeed.innerHTML = "<img src='"+theme.icons_16.ok+"' style='vertical-align:middle;padding-left:3px'/> "+nb+" succeed";
		nb = t.getNbScenariosFailed();
		if (nb == 0)
			t.span_scenarios_failed.innerHTML = "";
		else
			t.span_scenarios_failed.innerHTML = "<img src='"+theme.icons_16.error+"' style='vertical-align:middle;padding-left:3px'/> "+nb+" failed";
		top_status.update_status();
	};
	t.update_status();
	
	t.reload = function(onready) {
		top_status.component_loading(component);

		component.tests = null;
		t.update_status();

		t.disable_refresh();
		t.disable_launch_all();
		
		load_tests(component, function() {
			t.enable_refresh();

			t.update_status();
			
			top_status.component_loaded(component);
			if (onready) onready();
		});
	};
}

function load_all(ondone) {
	for (var i = 0; i < components.length; ++i)
		new component_widget(components[i]);
	var nb = components.length;
	for (var i = 0; i < components.length; ++i)
		components[i].widget.reload(function(){ if (--nb == 0) ondone(); });
}
load_all(function() {
});

function play_function_test(component, scenario_index, ondone) {
	var scenario = component.tests.functions.scenarios[scenario_index]; 
	if (!scenario.button) return;
	scenario.status = -2;
	component.widget.update_status();

	if (!scenario.icon) {
		scenario.icon = document.createElement("IMG");
		scenario.button.parentNode.insertBefore(scenario.icon, scenario.button);
	}
	scenario.button.parentNode.removeChild(scenario.button);
	scenario.button = null;
	scenario.icon.src = theme.icons_16.loading;
	scenario.icon.style.verticalAlign = "bottom";
	var next_step = function(step_pos, data) {
		var step = step_pos == -1 ? scenario.init_step : scenario.steps[step_pos];
		step.icon = document.createElement("IMG");
		step.icon.src = theme.icons_16.loading;
		step.icon.style.verticalAlign = "bottom";
		step.result_container.appendChild(step.icon);
		service.json("test","execute_functionalities_scenario?testing=true",{component:component.name,scenario:scenario.path,step:step_pos,data:data},function(res_step){
			var success;
			if (!res_step) {
				success = false;
				step.result_container.innerHTML = " <img src='"+theme.icons_16.error+"' style='vertical-align:bottom'/> An error occured during the call";
			} else if (res_step.error == null) {
				success = true;
				step.result_container.innerHTML = " <img src='"+theme.icons_16.ok+"' style='vertical-align:bottom'/>";
			} else {
				success = false;
				step.result_container.innerHTML = " <img src='"+theme.icons_16.error+"' style='vertical-align:bottom'/> "+res_step.error;
			}
			if (!success) {
				scenario.icon.src = theme.icons_16.error;
				scenario.button = document.createElement("IMG");
				scenario.button.className = 'button';
				scenario.button.src = '/static/test/replay.png';
				scenario.button.style.verticalAlign = "bottom";
				scenario.button.component = component;
				scenario.button.scenario = scenario_index;
				scenario.button.onclick = function() {
					scenario.init_step.result_container.innerHTML = "";
					for (var i = 0; i < scenario.steps.length; ++i)
						scenario.steps[i].result_container.innerHTML = "";
					play_function_test(this.component, this.scenario);
				};
				scenario.icon.parentNode.appendChild(scenario.button);
				// TODO details
				scenario.status = -1;
				component.widget.update_status();
				if (ondone) ondone(false);				
			} else {
				scenario.icon.src = theme.icons_16.ok;
				if (step_pos == scenario.steps.length-1) {
					scenario.status = 1;
					component.widget.update_status();
					if (ondone) ondone(true);				
				} else {
					next_step(step_pos+1, res_step.data);
				}
			}
		});	
	};
	next_step(-1, {});
}

function play_service_test(component, scenario_index, ondone) {
	var scenario = component.tests.services.scenarios[scenario_index]; 
	if (!scenario.button) return;

	scenario.status = -2;
	component.widget.update_status();

	if (!scenario.icon) {
		scenario.icon = document.createElement("IMG");
		scenario.button.parentNode.insertBefore(scenario.icon, scenario.button);
	}
	scenario.button.parentNode.removeChild(scenario.button);
	scenario.button = null;
	scenario.icon.src = theme.icons_16.loading;
	scenario.icon.style.verticalAlign = "bottom";

	var update_step_status = function(scenario, step, res_step, keep_running) {
		var success;
		if (!res_step) {
			success = false;
			step.result_container.innerHTML = " <img src='"+theme.icons_16.error+"' style='vertical-align:bottom'/> An error occured during the call";
		} else if (res_step.error == null) {
			success = true;
			if (!keep_running)
				step.result_container.innerHTML = " <img src='"+theme.icons_16.ok+"' style='vertical-align:bottom'/>";
		} else {
			success = false;
			step.result_container.innerHTML = " <img src='"+theme.icons_16.error+"' style='vertical-align:bottom'/> "+res_step.error;
		}
		if (!success) {
			scenario.icon.src = theme.icons_16.error;
			scenario.button = document.createElement("IMG");
			scenario.button.className = 'button';
			scenario.button.src = '/static/test/replay.png';
			scenario.button.style.verticalAlign = "bottom";
			scenario.button.component = component;
			scenario.button.scenario = scenario_index;
			scenario.button.onclick = function() {
				scenario.init_step.result_container.innerHTML = "";
				for (var i = 0; i < scenario.steps.length; ++i)
					scenario.steps[i].result_container.innerHTML = "";
				play_service_test(this.component, this.scenario);
			};
			scenario.icon.parentNode.appendChild(scenario.button);
			// TODO details
			scenario.status = -1;
			component.widget.update_status();
			if (ondone) ondone(false);	
			return false;			
		}
		return true;
	};
	
	scenario.init_step.icon = document.createElement("IMG");
	scenario.init_step.icon.src = theme.icons_16.loading;
	scenario.init_step.icon.style.verticalAlign = "bottom";
	scenario.init_step.result_container.appendChild(scenario.init_step.icon);
	// init db
	service.json("test","services_scenario_init_db?testing=true",{component:component.name,scenario:scenario.path},function(res_step){
		if (!update_step_status(scenario, scenario.init_step, res_step)) return;
		var next_step = function(pos,step_data) {
			var step = scenario.steps[pos];
			step.icon = document.createElement("IMG");
			step.icon.src = theme.icons_16.loading;
			step.icon.style.verticalAlign = "bottom";
			step.result_container.appendChild(step.icon);
			service.json("test","services_scenario_step_init?testing=true",{component:component.name,scenario:scenario.path,step:pos,data:step_data},function(res_step) {
				if (!update_step_status(scenario, step, res_step, true)) return;
				var input = res_step.service_input;
				var service_name = res_step.service_name;
				var service_type = res_step.service_type;
				var init_step_data = res_step.data;
				ajax.call(
					"POST",
					"/dynamic/"+component.name+"/service/"+service_name+"?testing=true",
					"text/json;charset=UTF-8",
					service.generateInput(input),
					function(error) {
						update_step_status(scenario, step, {error:error});
					}, function(xhr) {
						var call_check_output;
						var ct = xhr.getResponseHeader("Content-Type");
						if (ct) {
							var i = ct.indexOf(';');
							if (i > 0) ct = ct.substring(0, i);
						}
						if (service_type == "parse_json") {
							if (ct != "text/json") {
								update_step_status(scenario, step, {error:'Output is expected to be JSON, received is: '+ct});
								return;
							}
							if (xhr.responseText.length == 0) {
								update_step_status(scenario, step, {error:'Empty response from the server'});
								return;
							}
							var output;
					        try {
					        	output = eval("("+xhr.responseText+")");
					        } catch (e) {
					        	update_step_status(scenario, step, {error:"Invalid json output:<br/>Error: "+e+"<br/>Output:<br/>"+xhr.responseText});
					        	return;
					        }
			        		call_check_output = [output.errors,typeof output.result == 'undefined' ? null : output.result];
						} else if (service_type == "parse_xml") {
							if (ct != "text/xml") {
								update_step_status(scenario, step, {error:'Output is expected to be XML, received is: '+ct});
								return;
							}
							if (!xhr.responseXML || xhr.responseXML.childNodes.length == 0) {
								update_step_status(scenario, step, {error:'Empty response from the server'});
								return;
							}
				            if (xhr.responseXML.childNodes[0].nodeName == "ok") {
				            	call_check_output = [null, xhr.responseXML.childNodes[0]];
				            } else if (xhr.responseXML.childNodes[0].nodeName == "error") {
				            	call_check_output = [[xhr.responseXML.childNodes[0].getAttribute("message")], null];
					        } else
					        	call_check_output = [[xhr.responseText],null];
						} else {
							call_check_output = xhr.responseText; 
						}
						// call to service is ok
						service.json("test","services_scenario_step_check_output?testing=true",{component:component.name,scenario:scenario.path,step:pos,data:init_step_data},function(res_step_output) {
							var fct = "function(";
							if (service_type == "parse_json" || service_type == "parse_xml")
								fct += "errors,result";
							else
								fct += "raw_output";
							fct += "){"+res_step_output.javascript+"}";
							fct = eval('('+fct+')');
							var error;
							if (service_type == "parse_json" || service_type == "parse_xml")
								error = fct(call_check_output[0], call_check_output[1]);
							else
								error = fct(call_check_output[0]);
							if (!update_step_status(scenario, step, {error:error})) return;
							// finalize
							service.json("test","services_scenario_step_finalize?testing=true",{component:component.name,scenario:scenario.path,step:pos,data:init_step_data},function(res_step) {
								if (!update_step_status(scenario, step, res_step)) return;
								if (pos == scenario.steps.length-1) {
									scenario.icon.src = theme.icons_16.ok;
									scenario.status = 1;
									component.widget.update_status();
									if (ondone) ondone(true);				
								} else {
									next_step(pos+1, res_step.data);
								}
							});
						});
					}, false
				);				
			});
		};
		next_step(0,res_step.data);
	});
}

function play_ui_test(component, scenario_index, ondone) {
	var scenario = component.tests.ui.scenarios[scenario_index]; 
	if (!scenario.button) return;

	scenario.status = -2;
	component.widget.update_status();

	if (!scenario.icon) {
		scenario.icon = document.createElement("IMG");
		scenario.button.parentNode.insertBefore(scenario.icon, scenario.button);
	}
	scenario.button.parentNode.removeChild(scenario.button);
	scenario.button = null;
	scenario.icon.src = theme.icons_16.loading;
	scenario.icon.style.verticalAlign = "bottom";

	scenario.result_container.innerHTML = " <img src='"+theme.icons_16.loading+"' style='vertical-align:bottom'/> Initializing Database";
	// init db
	service.json("test","ui_scenario_init_db",{component:component.name,scenario:scenario.path},function(res_step){
		var success;
		if (!res_step) {
			success = false;
			scenario.result_container.innerHTML = " <img src='"+theme.icons_16.error+"' style='vertical-align:bottom'/> An error occured during the call";
		} else if (res_step.error == null) {
			success = true;
			scenario.result_container.innerHTML = " <img src='"+theme.icons_16.ok+"' style='vertical-align:bottom'/>";
		} else {
			success = false;
			scenario.result_container.innerHTML = " <img src='"+theme.icons_16.error+"' style='vertical-align:bottom'/> "+res_step.error;
		}
		if (!success) {
			scenario.icon.src = theme.icons_16.error;
			scenario.button = document.createElement("IMG");
			scenario.button.className = 'button';
			scenario.button.src = '/static/test/replay.png';
			scenario.button.style.verticalAlign = "bottom";
			scenario.button.component = component;
			scenario.button.scenario = scenario_index;
			scenario.button.onclick = function() {
				scenario.result_container.innerHTML = "";
				play_ui_test(this.component, this.scenario);
			};
			scenario.icon.parentNode.appendChild(scenario.button);
			// TODO details
			scenario.status = -1;
			component.widget.update_status();
			if (ondone) ondone(false);
			return;			
		}
		// execute ui
		remove_javascript("/static/test/ui_script.php?component="+component.name+"&path="+encodeURIComponent(scenario.path));
		add_javascript("/static/test/ui_script.php?component="+component.name+"&path="+encodeURIComponent(scenario.path),function() {
			var actions = window["Test_"+component.name+"_"+scenario.path](res_step.data);
			browser_control.run(actions, function(action){
				scenario.result_container.innerHTML = " <img src='"+theme.icons_16.loading+"' style='vertical-align:bottom'/> "+action;
			}, function(error){
				if (!error)
					scenario.result_container.innerHTML = " <img src='"+theme.icons_16.ok+"' style='vertical-align:bottom'/>";
				else
					scenario.result_container.innerHTML = " <img src='"+theme.icons_16.error+"' style='vertical-align:bottom'/> "+error;
				if (error) {
					scenario.icon.src = theme.icons_16.error;
					scenario.button = document.createElement("IMG");
					scenario.button.className = 'button';
					scenario.button.src = '/static/test/replay.png';
					scenario.button.style.verticalAlign = "bottom";
					scenario.button.component = component;
					scenario.button.scenario = scenario_index;
					scenario.button.onclick = function() {
						scenario.result_container.innerHTML = "";
						play_ui_test(this.component, this.scenario);
					};
					scenario.icon.parentNode.appendChild(scenario.button);
					// TODO details
					scenario.status = -1;
					component.widget.update_status();
					if (ondone) ondone(false);				
				} else {
					scenario.icon.src = theme.icons_16.ok;
					scenario.status = 1;
					component.widget.update_status();
					if (ondone) ondone(true);				
				}
			});
		});
		
	});
}
</script>
<?php
	}
	
}
?>