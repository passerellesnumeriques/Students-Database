<?php 
require_once("component/selection/page/SelectionPage.inc");
class page_trip_trip extends SelectionPage {

	public function getRequiredRights() { return array("can_access_selection_data"); }

	public function executeSelectionPage() {
		$trip_id = @$input["id"];
		
		$can_edit = PNApplication::$instance->user_management->has_right("manage_trips");

		$this->addStylesheet("/static/selection/trip/trip.css");
		$this->requireJavascript("mini_popup.js");
		$this->requireJavascript("position.js");
		theme::css($this, "mini_popup.css");
		$this->requireJavascript("geographic_area_selection.js");
		$this->requireJavascript("typed_field.js");
		$this->requireJavascript("field_timestamp.js");
		$this->requireJavascript("who.js");
		$this->requireJavascript("input_utils.js");
?>
<table class='trip_table'><tbody><tr><td id='trip_container'></td></tr></tbody></table>
<script type='text/javascript'>
var popup = window.parent.get_popup_window_from_frame(window);
var can_edit = <?php echo json_encode($can_edit); ?>;
var country_data;
<?php
$d = PNApplication::$instance->getDomainDescriptor();
echo "var pn_area_id = ".$d["geography"]["pn_area_id"].";";
echo "var pn_area_division = ".$d["geography"]["pn_area_division"].";";
?>
var pn_area;

/*
 * Connection 
 */
 
function TripConnection(origin, destination, peoples) {
	this.origin = origin;
	this.destination = destination;
	this.peoples = peoples ? peoples : [];
	if (origin) origin.addConnection(this);
	if (destination) destination.from = this;
}
TripConnection.prototype = {
	origin: null,
	destination: null,
	peoples: null,
	_whoPopup: function() {
		// TODO
	},
	_createWho: function() {
		var title = document.createElement("DIV");
		title.className = "title";
		title.innerHTML = "Who is travelling ?";
		if (can_edit) {
			var add_button = document.createElement("BUTTON");
			add_button.className = "flat icon";
			add_button.style.marginLeft = "5px";
			add_button.innerHTML = "<img src='"+theme.build_icon('/static/people/people_16.png',theme.icons_10.add)+"'/>";
			var t=this;
			add_button.onclick = function() { t._whoPopup(this); };
			add_button.title = "Add someone";
			title.appendChild(add_button);
		}
		this.node.appendChild(title);
		this.who = new who_container(this.node,this.peoples,can_edit);
	},
	create: function() {
		this.container = document.createElement("DIV");
		this.container.className = "trip_connection_container";
		this.node = document.createElement("DIV");
		this.node.className = "trip_connection_node";
		this.node.style.marginTop = this.origin ? "30px" : "0px";
		this.node.style.marginBottom = this.origin ? "30px" : "10px";
		this.container.appendChild(this.node);
		this._createWho();
		return this.container;
	},
	addHorizontalRoad: function(first, last) {
		var div = document.createElement("DIV");
		div.style.backgroundImage = "url(/static/selection/trip/road_horiz.png)";
		div.style.backgroundRepeat = "repeat-x";
		div.style.position = "absolute";
		div.style.top = "0px";
		div.style.height = "12px";
		if (first) {
			div.style.left = "50%";
			div.style.width = "50%";
		} else if (last) {
			div.style.left = "0px";
			div.style.width = "50%";
		} else {
			div.style.left = "0px";
			div.style.width = "100%";
		}
		this.container.appendChild(div);
	}
};
function InitialConnection(departure) {
	TripConnection.call(this,null,departure);
	this._whoPopup = function(button) {
		this.who.addSomeonePopup(button, "Who is going to travel ?");
	};
}
InitialConnection.prototype = new TripConnection;
InitialConnection.prototype.constructor = InitialConnection;

/*
 * Node
 */

function TripNode(area) {
	var t=this;

	this.area = area;
	this.onareachange = new Custom_Event();
	
	this.container = document.createElement("TABLE");
	this.container.className = "trip_node_container";
	this.tbody = document.createElement("TBODY");
	this.container.appendChild(this.tbody);
	var tr = document.createElement("TR"); this.tbody.appendChild(tr);
	var td = document.createElement("TD"); tr.appendChild(td);
	td.style.textAlign = "center";
	this.node = document.createElement("DIV");
	this.node.className = "trip_node";
	td.appendChild(this.node);

	this.header = document.createElement("DIV");
	this.header.className = "trip_node_title"+(can_edit ? " editable" : "");
	this.header.appendChild(document.createTextNode(window.top.geography.getGeographicAreaText(country_data, area).text));
	this.onareachange.add_listener(function(area) { t.header.childNodes[0].nodeValue = window.top.geography.getGeographicAreaText(country_data, area).text; layout.changed(t.header); });
	this.node.appendChild(this.header);
	this.content = document.createElement("DIV");
	this.content.className = "trip_node_content";
	this.node.appendChild(this.content);
	if (can_edit) {
		this.footer = document.createElement("DIV");
		this.footer.className = "trip_node_footer";
		this.node.appendChild(this.footer);
	}

	this.tr2 = document.createElement("TR");
	this.tbody.appendChild(this.tr2);

	this.activities_table = document.createElement("TABLE");
	this.activities_table.className = "trip_node_activities_table";
	this.activities_table.appendChild(document.createElement("TBODY"));
	this.content.appendChild(this.activities_table);

	this.activities = [];
	this.connections = [];
	this.from = null;

	if (can_edit)
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
		if (can_edit) {
			var tr = document.createElement("TR");
			var td = document.createElement("TD");
			td.colSpan = 3;
			var add_activity = document.createElement("BUTTON");
			td.appendChild(add_activity);
			tr.appendChild(td);
			this.activities_table.appendChild(tr);
			add_activity.innerHTML = "<img src='"+theme.icons_10.add+"' style='vertical-align:middle;margin-bottom:3px;'/> Add something to do";
			add_activity.onclick = function() {
				// TODO
				var a = new CustomActivity("");
				t.addActivity(a);
			};
		
			var travel = document.createElement("BUTTON");
			travel.className = "flat";
			travel.innerHTML = "<img src='/static/selection/trip/road.png' style='vertical-align:middle'/> Travel to...";
			travel.onclick = function() {
				var p = new mini_popup("Travel from "+t.header.childNodes[0].nodeValue+" to Where ?");
				new geographic_area_selection(p.content, window.top.default_country_id, null, 'vertical', true, function(sel) {
					var div = document.createElement("DIV");
					div.className = "mini_popup_header mini_popup_title";
					div.innerHTML = "With Who ?";
					div.style.marginTop = "6px";
					p.content.appendChild(div);
					var who = new WhoFrom(p.content, t.whoIsThere());
					p.addOkButton(function() {
						if (!sel.selected_area_id) return false;
						var area = window.top.geography.searchArea(country_data, sel.selected_area_id);
						var destination = new TripNode(area);
						var conn = new TripConnection(t, destination, who.selected);
						return true;
					});
					p.showBelowElement(travel);
				});
			};
			this.footer.appendChild(travel);
		}
	};
	this._init();

	this.whoIsThere = function() {
		return this.from.peoples;
	};
	
	this.addActivity = function(activity) {
		activity.node = this;
		this.activities.push(activity);
		activity.createContent(this.activities_table.childNodes[0]);
		activity.setNumber(this.activities.length);
		layout.changed(this.activities_table);
	};

	this.removeActivity = function(activity) {
		this.activities.removeUnique(activity);
		for (var i = activity.trs.length-1; i >= 0; --i)
			activity.trs[i].parentNode.removeChild(activity.trs[i]);
		for (var i = 0; i < this.activities.length; ++i)
			this.activities[i].setNumber(i+1);
		layout.changed(this.activities_table);
	};
	
	this.addConnection = function(connection) {
		this.connections.push(connection);
		td = document.createElement("TD");
		td.style.verticalAlign = "top";
		this.tr2.appendChild(td);
		this.node.parentNode.colSpan = this.tr2.childNodes.length;
		if (this.connections.length == 1) { // the first one
			this.road_below = document.createElement("DIV");
			this.road_below.style.backgroundImage = "url(/static/selection/trip/road.png)";
			this.road_below.style.backgroundRepeat = "repeat-y";
			this.road_below.style.backgroundPositionX = "center";
			this.road_below.style.height = "10px";
			this.node.parentNode.appendChild(this.road_below);
		}
		td.appendChild(connection.create());
		td.appendChild(connection.destination.container);
		connection.destination.container.style.width = "100%";
		if (this.connections.length > 1)
			for (var i = 0; i < this.connections.length; ++i)
				this.connections[i].addHorizontalRoad(i==0,i==this.connections.length-1);
		layout.changed(td);
	};
}
 
/*
 * Activity
 */

function Activity() {
}
Activity.prototype = {
	node: null,
	div_dumber: null,
	trs: [],
	what: null,
	when: null,
	_createWhat: function(td) {
		if (can_edit) {
			var w = new InputOver(this.what);
			td.appendChild(w.container);
			var t=this;
			w.onchange.add_listener(function(w){t.what = w.input.value;});
		} else
			td.appendChild(document.createTextNode(what));
	},
	_createWhen: function(td) {
		var w = new When(td, this.when, can_edit);
		var t=this;
		w.onchange.add_listener(function(w) {t.when = w.getTimestamp();});
	},
	createContent: function(table) {
		var t=this;
		var tr, td, td2;
		table.appendChild(tr = document.createElement("TR"));
		this.trs.push(tr);
		tr.className = "trip_node_activity_first_row";
		tr.appendChild(td = document.createElement("TD"));
		td.appendChild(this.div_number = document.createElement("DIV"));
		td.rowSpan = 1;
		var td_number = td;
		this.div_number.className = "trip_node_activity_number";
		if (can_edit) {
			var remove_button = document.createElement("BUTTON");
			remove_button.className = "flat icon";
			remove_button.innerHTML = "<img src='"+theme.icons_16.remove+"'/>";
			remove_button.style.marginTop = "5px";
			remove_button.title = "Remove this activity";
			remove_button.onclick = function() {
				t.node.removeActivity(t);
			};
			td.appendChild(remove_button);
		}
		tr.appendChild(td = document.createElement("TD"));
		td.innerHTML = "What ?";
		td.className = "trip_node_activity_header";
		tr.appendChild(td = document.createElement("TD"));
		this._createWhat(td);

		if (this._createWhen) {
			td_number.rowSpan++;
			table.appendChild(tr = document.createElement("TR"));
			this.trs.push(tr);
			tr.appendChild(td = document.createElement("TD"));
			td.innerHTML = "When ?";
			td.className = "trip_node_activity_header";
			tr.appendChild(td = document.createElement("TD"));
			this._createWhen(td);
		}

		/* TODO
		table.appendChild(tr = document.createElement("TR"));
		this.trs.push(tr);
		tr.appendChild(td = document.createElement("TD"));
		td.innerHTML = "Where ?";
		td.className = "trip_node_activity_header";
		tr.appendChild(td = document.createElement("TD"));
		td.innerHTML = "TODO";

		table.appendChild(tr = document.createElement("TR"));
		this.trs.push(tr);
		tr.appendChild(td = document.createElement("TD"));
		td.innerHTML = "Who ?";
		td.className = "trip_node_activity_header";
		tr.appendChild(td2 = document.createElement("TD"));
		this.who = new who_container(td2, [], true);
		td.appendChild(document.createElement("BR"));
		td.appendChild(this.who.createAddButton());

		table.appendChild(tr = document.createElement("TR"));
		this.trs.push(tr);
		tr.appendChild(td = document.createElement("TD"));
		td.innerHTML = "Cost ?";
		td.className = "trip_node_activity_header";
		tr.appendChild(td = document.createElement("TD"));
		td.innerHTML = "TODO";
		*/
		this.trs[0].ondomremoved(function() {
			t.node = null;
			t.div_number = null;
			t.what = null;
			t.when = null;
			t.trs = null;
			t = null;	
		});
		
	},
	setNumber: function(num) {
		this.div_number.innerHTML = num;
	}
};
function CustomActivity(what, when) {
	this.what = what;
	this.when = when;
	Activity.call(this);
}
CustomActivity.prototype = new Activity;
CustomActivity.prototype.constructor = CustomActivity;

function DepartureMeeting() {
	Activity.call(this);
	this._createWhat = function(td) {
		td.innerHTML = "Meeting for Departure";
	};
}
DepartureMeeting.prototype = new Activity;
DepartureMeeting.prototype.constructor = DepartureMeeting;
 
/*
 * Utilities
 */

function When(container, when, can_edit) {
	this.when = when;
	this.link = can_edit ? document.createElement("A") : document.createElement("SPAN");
	this.link.className = "black_link";
	this.link.href = "#";
	this.refresh = function() {
		if (!this.when)
			this.link.innerHTML = "Not set";
		else
			this.link.innerHTML = 
				getDayShortName(this.when.getDay(),true)+
				" "+
				_2digits(this.when.getDate())+
				" "+
				getMonthShortName(this.when.getMonth()+1)+
				" "+
				this.when.getFullYear()+
				" at "+
				getTimeString(this.when);
	};
	this.getTimestamp = function() {
		return this.when;
	};
	this.onchange = new Custom_Event();
	var t=this;
	if (this.can_edit)
		this.link.onclick = function() {
			var p = new mini_popup("When ?");
			var f = new field_timestamp(t.when ? t.when.getTime() : null,true,{can_be_null:true,data_is_seconds:false,show_time:true});
			p.content.appendChild(f.getHTMLElement());
			p.showBelowElement(t.link);
			f.onchange.add_listener(function() {
				var ts = f.getCurrentData();
				t.when = ts ? new Date(ts) : null;
				t.refresh();
				t.onchange.fire(t);
			});
		};
	this.refresh();
	container.appendChild(this.link);
	container.ondomremoved(function() {
		t.link = null;
		t = null;
	});
}

function WhoFrom(container, peoples, selected) {
	this.selected = selected ? selected : [];
	for (var i = 0; i < peoples.length; ++i) {
		var div = document.createElement("DIV");
		var cb = document.createElement("INPUT");
		cb.type = "checkbox";
		cb.style.margin = "0px";
		cb.style.marginRight = "3px";
		cb.style.verticalAlign = "middle";
		cb._index = i;
		cb._t = this;
		if (selected) {
			if (this.selected.contains(peoples[i])) cb.checked = checked;
		} else {
			cb.checked = true;
			this.selected.push(peoples[i]);
		}
		cb.onchange = function() {
			if (this.checked)
				this._t.selected.push(peoples[this._index]);
			else
				this._t.selected.removeUnique(peoples[this._index]);
		};
		cb.ondomremoved(function() { this._t = null; });
		div.appendChild(cb);
		div.appendChild(document.createTextNode(typeof peoples[i] == 'string' ? peoples[i] : peoples[i].people.first_name+" "+peoples[i].people.last_name));
		container.appendChild(div);
	}
}

/*
 * Creation of the Trip Graph
 */

var container = document.getElementById('trip_container');

window.top.require("geography.js",function() {
	window.top.geography.getCountryData(window.top.default_country_id,function(cd) {
		country_data = cd;
		pn_area = window.top.geography.getAreaFromDivision(country_data, pn_area_id, pn_area_division);
		<?php if ($trip_id == null) { ?>
		var departure = new TripNode(pn_area);
		var start = new InitialConnection(departure);
		container.appendChild(start.create());
		container.appendChild(departure.container);
		layout.changed(container);
		departure.addActivity(new DepartureMeeting());

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