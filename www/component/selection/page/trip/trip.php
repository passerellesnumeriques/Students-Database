<?php 
require_once("component/selection/page/SelectionPage.inc");
class page_trip_trip extends SelectionPage {

	public function getRequiredRights() { return array("can_access_selection_data"); }

	public function executeSelectionPage() {
		$trip_id = @$input["id"];

		$this->addStylesheet("/static/selection/trip/trip.css");
		$this->requireJavascript("mini_popup.js");
		$this->requireJavascript("position.js");
		theme::css($this, "mini_popup.css");
		$this->requireJavascript("geographic_area_selection.js");
		$this->requireJavascript("typed_field.js");
		$this->requireJavascript("field_timestamp.js");
?>
<table style='min-width:100%;'><tr><td id='trip_container'></td></tr></table>
<script type='text/javascript'>
var popup = window.parent.get_popup_window_from_frame(window);
var country_data;
<?php
$d = PNApplication::$instance->getDomainDescriptor();
echo "var pn_area_id = ".$d["geography"]["pn_area_id"].";";
echo "var pn_area_division = ".$d["geography"]["pn_area_division"].";";
?>
var pn_area;

function InputOver(value, onchange) {
	this.container = document.createElement("DIV");
	this.container.style.position = "relative";
	this.container.appendChild(document.createTextNode(value));
	this.container.style.height = "16px";
	this.container.style.paddingLeft = "0px";
	this.container.style.marginRight = "2px";
	this.container.style.paddingTop = "2px";
	this.input = document.createElement("INPUT");
	this.input.style.position = "absolute";
	this.input.style.top = "0px";
	this.input.style.left = "-2px";
	this.input.style.width = "100%";
	this.input.style.padding = "0px";
	this.container.appendChild(this.input);
	this.input.value = value;
	setOpacity(this.input, 0);
	var t=this;
	this.container.onmouseover = function() {
		setOpacity(t.input, 100);
	};
	this.container.onmouseout = function() {
		if (t.input === document.activeElement) return;
		setOpacity(t.input, 0);
	};
	this.input.onblur = function() {
		setOpacity(t.input, 0);
	};
	this.input.onchange = function() {
		t.container.childNodes[0].nodeValue = t.input.value;
		layout.changed(t.container);
	};
}

function Where(container) {
	// TODO
}

function When(container) {
	// TODO
}

function Who(container) {
	// TODO
}

function Activity(what) {
	this.createContent = function(table) {
		var tr, td;
		table.appendChild(tr = document.createElement("TR"));
		tr.className = "trip_node_activity_first_row";
		tr.appendChild(td = document.createElement("TD"));
		td.appendChild(this.div_number = document.createElement("DIV"));
		td.rowSpan = 5;
		this.div_number.className = "trip_node_activity_number";
		tr.appendChild(td = document.createElement("TD"));
		td.innerHTML = "What ?";
		td.className = "trip_node_activity_header";
		tr.appendChild(td = document.createElement("TD"));
		this.what = new InputOver(what);
		td.appendChild(this.what.container);

		table.appendChild(tr = document.createElement("TR"));
		tr.appendChild(td = document.createElement("TD"));
		td.innerHTML = "When ?";
		td.className = "trip_node_activity_header";
		tr.appendChild(td = document.createElement("TD"));
		this.when = new field_timestamp(null,true,{can_be_null:true,data_is_seconds:true,show_time:true});
		td.appendChild(this.when.getHTMLElement());

		table.appendChild(tr = document.createElement("TR"));
		tr.appendChild(td = document.createElement("TD"));
		td.innerHTML = "Where ?";
		td.className = "trip_node_activity_header";
		tr.appendChild(td = document.createElement("TD"));
		td.innerHTML = "TODO";

		table.appendChild(tr = document.createElement("TR"));
		tr.appendChild(td = document.createElement("TD"));
		td.innerHTML = "Who ?";
		td.className = "trip_node_activity_header";
		tr.appendChild(td = document.createElement("TD"));
		td.innerHTML = "TODO";

		table.appendChild(tr = document.createElement("TR"));
		tr.appendChild(td = document.createElement("TD"));
		td.innerHTML = "Cost ?";
		td.className = "trip_node_activity_header";
		tr.appendChild(td = document.createElement("TD"));
		td.innerHTML = "TODO";
	};
	this.setNumber = function(num) {
		this.div_number.innerHTML = num;
	};
}

function TripNode(area) {
	var t=this;

	this.area = area;
	this.onareachange = new Custom_Event();
	
	this.container = document.createElement("TABLE");
	this.container.className = "trip_node_container";
	var tr = document.createElement("TR"); this.container.appendChild(tr);
	var td = document.createElement("TD"); tr.appendChild(td);
	td.style.textAlign = "center";
	this.node = document.createElement("DIV");
	this.node.className = "trip_node";
	td.appendChild(this.node);

	this.header = document.createElement("DIV");
	this.header.className = "trip_node_title";
	this.header.appendChild(document.createTextNode(window.top.geography.getGeographicAreaText(country_data, area).text));
	this.onareachange.add_listener(function(area) { t.header.childNodes[0].nodeValue = window.top.geography.getGeographicAreaText(country_data, area).text; layout.changed(t.header); });
	this.node.appendChild(this.header);
	this.content = document.createElement("DIV");
	this.content.className = "trip_node_content";
	this.node.appendChild(this.content);
	this.footer = document.createElement("DIV");
	this.footer.className = "trip_node_footer";
	this.node.appendChild(this.footer);

	this.tr2 = document.createElement("TR");
	this.container.appendChild(this.tr2);

	this.activities_table = document.createElement("TABLE");
	this.activities_table.className = "trip_node_activities_table";
	this.activities_table.appendChild(document.createElement("TBODY"));
	this.content.appendChild(this.activities_table);

	this.activities = [];

	this.header.onclick = function() {
		var p = new mini_popup("Where ?");
		var sel = new geographic_area_selection(p.content, window.top.default_country_id, t.area.area_id, 'vertical', true, function() {
			p.showBelowElement(t.header);
		});
		sel.onchange = function() {
			if (!sel.selected_area_id) return;
			if (sel.selected_area_id == t.area.area_id) return;
			t.area = window.top.geography.searchArea(country_data, sel.selected_area_id);
			t.onareachange.fire(t.area);
		};
	};

	this._init = function() {
		var tr = document.createElement("TR");
		var td = document.createElement("TD");
		var add_activity = document.createElement("BUTTON");
		td.appendChild(add_activity);
		td.colSpan = 3;
		tr.appendChild(td);
		this.activities_table.appendChild(tr);
		add_activity.innerHTML = "<img src='"+theme.icons_10.add+"' style='vertical-align:middle'/> Add something to do";
		add_activity.onclick = function() {
			var a = new Activity("Enter a description here");
			t.addActivity(a);
		};
		var travel = document.createElement("BUTTON");
		travel.className = "flat";
		travel.innerHTML = "<img src='/static/selection/trip/road.png' style='vertical-align:middle'/> Travel to...";
		travel.onclick = function() {
			var p = new mini_popup("Travel from "+t.header.childNodes[0].nodeValue+" to Where ?");
			new geographic_area_selection(p.content, window.top.default_country_id, null, 'vertical', true, function(sel) {
				var button = document.createElement("BUTTON");
				button.className = "flat";
				button.innerHTML = "<img src='"+theme.icons_10.ok+"' style='vertical-align:middle'/> Ok";
				button.onclick = function() {
					if (!sel.selected_area_id) return;
					var area = window.top.geography.searchArea(country_data, sel.selected_area_id);
					var destination = new TripNode(area);
					var conn = new TripConnection(destination);
					t.addConnection(conn);
					p.close();
				};
				var div = document.createElement("DIV");
				div.style.textAlign = "right";
				div.appendChild(button);
				p.content.appendChild(div);
				p.showBelowElement(travel);
			});
		};
		this.footer.appendChild(travel);
	};
	this._init();
	
	this.addActivity = function(activity) {
		this.activities.push(activity);
		activity.createContent(this.activities_table.childNodes[0]);
		activity.setNumber(this.activities.length);
		layout.changed(this.activities_table);
	};
	
	this.addConnection = function(connection) {
		td = document.createElement("TD");
		td.style.verticalAlign = "top";
		this.tr2.appendChild(td);
		this.node.parentNode.colSpan = this.tr2.childNodes.length;
		td.appendChild(connection.container);
		td.appendChild(connection.destination.container);
		connection.destination.container.style.width = "100%";
		layout.changed(td);
	};
}

function TripConnection(destination) {
	this.container = document.createElement("DIV");
	this.container.className = "trip_connection_container";
	this.node = document.createElement("DIV");
	this.node.className = "trip_connection_node";
	this.node.innerHTML = "TODO";
	this.container.appendChild(this.node);
	this.destination = destination;
}

var container = document.getElementById('trip_container');

window.top.require("geography.js",function() {
	window.top.geography.getCountryData(window.top.default_country_id,function(cd) {
		country_data = cd;
		pn_area = window.top.geography.getAreaFromDivision(country_data, pn_area_id, pn_area_division);
		<?php if ($trip_id == null) { ?>
		var departure = new TripNode(pn_area);
		container.appendChild(departure.container);
		layout.changed(container);
		var departure_meeting = new Activity("Meeting for Departure");
		departure.addActivity(departure_meeting);

		popup.addOkCancelButtons(function() {
			// TODO create
		});
		<?php } else { ?>
		popup.addFrameSaveButton(function() {
			// TODO save
		});
		popup.addCloseButton();
		<?php } ?>
	});
});

</script>
<?php 
	}
}
?>