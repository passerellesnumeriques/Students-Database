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
		$this->requireJavascript("context_menu.js");
?>
<table class='trip_table'><tbody><tr><td id='trip_container'></td></tr></tbody></table>
<script type='text/javascript'>
var popup = window.parent.getPopupFromFrame(window);
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
	departure: null,
	arrival: null,
	_createHow: function() {
		var span = document.createElement("SPAN");
		span.className = "title";
		span.innerHTML = "How ?";
		this.node.appendChild(span);
		// TODO
		this.node.appendChild(document.createElement("BR"));
	},
	_createWhen: function() {
		var t=this;
		var span = document.createElement("SPAN");
		span.className = "title";
		span.innerHTML = "Departure: ";
		this.node.appendChild(span);
		new When(this.node, this.departure, can_edit, function() { return t.getMinimumDepartureDate(true); }, function() { return t.getMaximumDepartureDate(true); }).
			onchange.addListener(function(w) { t.departure = w.when; });
		this.node.appendChild(document.createElement("BR"));
		span = document.createElement("SPAN");
		span.className = "title";
		span.innerHTML = "Arrival: ";
		this.node.appendChild(span);
		new When(this.node, this.arrival, can_edit, function() { return t.getMinimumArrivalDate(true); }, function() { return t.getMaximumArrivalDate(true); }).
			onchange.addListener(function(w) { t.arrival = w.when; });
		this.node.appendChild(document.createElement("BR"));
	},
	_whoPopup: function(button) {
		var p = new mini_popup("Who is travelling ?");
		var selected = arrayCopy(this.peoples);
		var who = new WhoFrom(p.content, this.origin.whoIsThere(), selected);
		var t=this;
		p.addOkButton(function() {
			var added = [];
			for (var i = 0; i < who.selected.length; ++i)
				if (!t.peoples.contains(who.selected[i]))
					added.push(who.selected[i]);
			var removed = [];
			for (var i = 0; i < t.peoples.length; ++i)
				if (!who.selected.contains(t.peoples[i]))
					removed.push(t.peoples[i]);
			for (var i = 0; i < removed.length; ++i)
				t.who.removePeople(removed[i]);
			for (var i = 0; i < added.length; ++i)
				t.who.addPeople(added[i]);
			return true;
		});
		p.showBelowElement(button);
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
		var t=this;
		this.who.onadded.addListener(function(people) {
			t.destination.peopleAdded(people);
		});
		this.who.onremoved.addListener(function(people) {
			t.destination.peopleRemoved(people);
		});
	},
	create: function() {
		this.container = document.createElement("DIV");
		this.container.className = "trip_connection_container";
		this.node = document.createElement("DIV");
		this.node.className = "trip_connection_node";
		this.node.style.marginTop = this.origin ? "30px" : "0px";
		this.node.style.marginBottom = this.origin ? "30px" : "10px";
		this.container.appendChild(this.node);
		if (this.origin) {
			this._createWhen();
			this._createHow();
		}
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
	},
	peopleAdded: function(people) {
	},
	peopleRemoved: function(people) {
		if (!this.peoples.contains(people)) return;
		this.who.removePeople(people);
	},
	getMinimumArrivalDate: function(ignore_arrival) {
		if (this.arrival && !ignore_arrival) return this.arrival;
		if (this.departure) return this.departure;
		if (!this.origin) return null;
		return this.origin.getMinimumEndingDate();
	},
	getMinimumDepartureDate: function(ignore_departure) {
		if (this.departure && !ignore_departure) return this.departure;
		if (!this.origin) return null;
		return this.origin.getMinimumEndingDate();
	},
	getMaximumDepartureDate: function(ignore_departure) {
		if (this.departure && !ignore_departure) return this.departure;
		if (this.arrival) return this.arrival;
		return this.destination.getMaximumStartingDate();
	},
	getMaximumArrivalDate: function(ignore_arrival) {
		if (this.arrival && !ignore_arrival) return this.arrival;
		return this.destination.getMaximumStartingDate();
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
	this.onareachange.addListener(function(area) { t.header.childNodes[0].nodeValue = window.top.geography.getGeographicAreaText(country_data, area).text; layout.changed(t.header); });
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
				var button = this;
				var menu = new context_menu();
				menu.addTitleItem(null, "What will you do in "+t.header.childNodes[0].nodeValue+" ?");
				menu.addIconItem("/static/selection/is/is_16.png", "An Information Session", function() {
					var locker = lock_screen(null, "Loading Information Sessions List...");
					// TODO max date, according to people
					service.json("selection","is/search",{min_date:dateToSQL(t.getMinimumEndingDate()),max_date:null,area_id:t.area.area_id}, function(res) {
						unlock_screen(locker);
						menu = new context_menu();
						menu.addTitleItem("/static/selection/is/is_16.png","Which Information Session are you going to conduct here ?");
						if (res.length == 0)
							menu.addHtmlItem("<i>There are no Information Session avaiable</i>");
						else
							for (var i = 0; i < res.length; ++i) {
								var div = document.createElement("DIV");
								var s = res[i].name;
								if (res[i].start_date) {
									var d = new Date(parseInt(res[i].start_date)*1000);
									s += " on "+getDateString(d)+" at "+getTimeString(d);
								}
								var div2 = document.createElement("DIV");
								div2.appendChild(document.createTextNode(s));
								div.appendChild(div2);
								if (res.geographic_area_name) {
									div2 = document.createElement("DIV");
									div2.style.marginLeft = "10px";
									div2.appendChild(document.createTextNode(" at "+res.geographic_area_name));
									div.appendChild(div2);
								}
								if (res[i].who.length > 0) {
									s = " with ";
									for (var j = 0; j < res[i].who.length; ++j) {
										if (j > 0) s += ", ";
										if (typeof res[i].who[j] == 'string') s += res[i].who[j];
										else s += res[i].who[j].people.first_name+' '+res[i].who[j].people.last_name;
									}
									div2 = document.createElement("DIV");
									div2.style.marginLeft = "10px";
									div2.appendChild(document.createTextNode(s));
									div.appendChild(div2);
								}
								div._is = res[i];
								menu.addHtmlItem(div, function() {
									new InformationSessionActivity(t,this._is);
								});
							}
						menu.showBelowElement(button);
					});
				});
				menu.addIconItem("/static/selection/exam/exam_subject_16.png", "An Exam", function() {
					// TODO
				});
				menu.addIconItem("/static/selection/interview/interview_16.png", "An Interview", function() {
					// TODO
				});
				menu.addIconItem("/static/selection/si/si_16.png", "A Social Investigation", function() {
					// TODO
				});
				menu.addIconItem("/static/selection/trip/eat_16.png", "Eat", function() {
					// TODO
				});
				menu.addIconItem("/static/selection/trip/sleep_16.png", "Sleep", function() {
					// TODO
				});
				menu.addIconItem(null, "Something else", function() {
					new CustomActivity(t,"");
				});
				menu.showBelowElement(this);
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
	this.peopleAdded = function(people) {
		for (var i = 0; i < this.activities.length; ++i)
			this.activities[i].peopleAdded(people);
		for (var i = 0; i < this.connections.length; ++i)
			this.connections[i].peopleAdded(people);
	};
	this.peopleRemoved = function(people) {
		for (var i = 0; i < this.activities.length; ++i)
			this.activities[i].peopleRemoved(people);
		for (var i = 0; i < this.connections.length; ++i)
			this.connections[i].peopleRemoved(people);
	};
	this.getMinimumEndingDate = function() {
		if (this.activities.length > 0) return this.activities[this.activities.length-1].getMinimumEndingDate();
		return from.getMinimumArrivalDate();
	};
	this.getMaximumStartingDate = function() {
		if (this.activities.length > 0) return this.activities[0].getMaximumStartingDate();
		return null; // improve ?
	};
	
	this.addActivity = function(activity) {
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

function Activity(node) {
	this.node = node;
	this.trs = [];
	if (node) node.addActivity(this);
}
Activity.prototype = {
	node: null,
	div_dumber: null,
	trs: null,
	what: null,
	when: null,
	where: null,
	who: null,
	cost: null,
	removable: true,
	_createWhat: function(td) {
		if (can_edit) {
			var w = new InputOver(this.what);
			td.appendChild(w.container);
			var t=this;
			w.onchange.addListener(function(w){t.what = w.input.value;});
		} else
			td.appendChild(document.createTextNode(what));
	},
	_createWhen: function(td) {
		var t=this;
		var w = new When(td, this.when, can_edit, function() {
			return t.getMinimumEndingDate(true);
		},function() {
			return t.getMaximumStartingDate(true);
		});
		w.onchange.addListener(function(w) {t.when = w.getTimestamp();});
	},
	_createWhere: function(td) {
		// TODO
	},
	_createWho: function(td, readonly) {
		if (this.who === null)
			this.who = arrayCopy(this.node.whoIsThere());
		this._who_link = document.createElement(can_edit ? "A" : "SPAN");
		this._who_link.className = "black_link";
		this._who_link.href = "#";
		td.appendChild(this._who_link);
		if (can_edit && !readonly) {
			var t=this;
			this._who_link.onclick = function() {
				var p = new mini_popup("Who is participating ?");
				var who = new WhoFrom(p.content, t.node.whoIsThere(), t.who);
				p.addOkButton(function() {
					t._updateWho();
					return true;
				});
				p.showBelowElement(t._who_link);
				return false;
			};
		}
		this._updateWho();
	},
	_updateWho: function() {
		var s = "";
		if (this.who.length == 0)
			s = "Nobody";
		else
			for (var i = 0; i < this.who.length; ++i) {
				if (i > 0) s+= ", ";
				if (typeof this.who[i] == 'string') s += this.who[i];
				else s += this.who[i].people.first_name+' '+this.who[i].people.last_name;
			}
		this._who_link.removeAllChildren();
		this._who_link.appendChild(document.createTextNode(s));
	},
	_createHowMuch: function(td) {
		// TODO
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
		if (can_edit && this.removable) {
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

		if (this._createWhere) {
			td_number.rowSpan++;
			table.appendChild(tr = document.createElement("TR"));
			this.trs.push(tr);
			tr.appendChild(td = document.createElement("TD"));
			td.innerHTML = "Where ?";
			td.className = "trip_node_activity_header";
			tr.appendChild(td = document.createElement("TD"));
			this._createWhere(td);
		}

		if (this._createWho) {
			td_number.rowSpan++;
			table.appendChild(tr = document.createElement("TR"));
			this.trs.push(tr);
			tr.appendChild(td = document.createElement("TD"));
			td.innerHTML = "Who ?";
			td.className = "trip_node_activity_header";
			tr.appendChild(td = document.createElement("TD"));
			this._createWho(td);
		}
		
		if (this._createHowMuch) {
			td_number.rowSpan++;
			table.appendChild(tr = document.createElement("TR"));
			this.trs.push(tr);
			tr.appendChild(td = document.createElement("TD"));
			td.innerHTML = "Cost";
			td.className = "trip_node_activity_header";
			tr.appendChild(td = document.createElement("TD"));
			this._createHowMuch(td);
		}

		this.trs[0].ondomremoved(function() {
			t.node = null;
			t.div_number = null;
			t.what = null;
			t.when = null;
			t.where = null;
			t.who = null;
			t.cost = null;
			t.trs = null;
			t = null;	
		});
		
	},
	setNumber: function(num) {
		this.div_number.innerHTML = num;
	},
	peopleAdded: function(people) {},
	peopleRemoved: function(people) {
		if (this.who === null) return;
		this.who.removeUnique(people);
		this._updateWho();
	},
	getMinimumEndingDate: function(ignore_when) {
		if (this.when && !ignore_when) return this.when;
		var index = this.node.activities.indexOf(this);
		if (index > 0) return this.node.activities[index-1].getMinimumEndingDate();
		return this.node.from.getMinimumArrivalDate();
	},
	getMaximumStartingDate: function(ignore_when) {
		if (this.when && !ignore_when) return this.when;
		var index = this.node.activities.indexOf(this);
		if (index < this.node.activities.length-1) return this.node.activities[index+1].getMaximumStartingDate();
		return null; // improve ?
	}
	// TODO add a warning if some people are not available (already gone)
};
function CustomActivity(node, what, when) {
	this.what = what;
	this.when = when;
	Activity.call(this, node);
}
CustomActivity.prototype = new Activity;
CustomActivity.prototype.constructor = CustomActivity;

function DepartureMeeting(departure_node) {
	this._createWhat = function(td) {
		td.innerHTML = "Meeting for Departure";
	};
	this._createWho = null;
	this._createHowMuch = null;
	this.who = departure_node.from.peoples;
	this.removable = false;
	Activity.call(this, departure_node);
}
DepartureMeeting.prototype = new Activity;
DepartureMeeting.prototype.constructor = DepartureMeeting;

function InformationSessionActivity(node, is) {
	this._createWhat = function(td) { td.innerHTML = "<img src='/static/selection/is/is_16.png' style='vertical-align:bottom'/> Information Session"; };
	this._createWhen = function(td) {
		if (!is.start_date) {
			td.innerHTML = "<i>unknown</i>";
			return;
		}
		var d = new Date(parseInt(is.start_date)*1000);
		td.innerHTML = getDateString(d)+" at "+getTimeString(d);
	};
	this.who = is.who;
	this._createWho = function(td) {
		Activity.prototype._createWho.call(this, td, true);
		// TODO warning if people missing in the trip
	};
	this._createHowMuch = null;
	Activity.call(this, node);
}
InformationSessionActivity.prototype = new Activity;
InformationSessionActivity.prototype.constructor = InformationSessionActivity;

/*
 * Utilities
 */

function When(container, when, can_edit, minimum_provider, maximum_provider) {
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
	if (can_edit)
		this.link.onclick = function() {
			var p = new mini_popup("When ?");
			var min = minimum_provider ? dateToSQL(minimum_provider()) : null;
			var max = maximum_provider ? dateToSQL(maximum_provider()) : null;
			var f = new field_timestamp(t.when ? t.when.getTime() : null,true,{can_be_null:true,data_is_seconds:false,show_time:true,minimum_date:min,maximum_date:max});
			p.content.appendChild(f.getHTMLElement());
			p.showBelowElement(t.link);
			f.onchange.addListener(function() {
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
			if (this.selected.contains(peoples[i])) cb.checked = 'checked';
		} else {
			cb.checked = 'checked';
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
		window.top.geography.startComputingSearchDictionary(country_data);
		pn_area = window.top.geography.getAreaFromDivision(country_data, pn_area_id, pn_area_division);
		<?php if ($trip_id == null) { ?>
		var departure = new TripNode(pn_area);
		var start = new InitialConnection(departure);
		container.appendChild(start.create());
		container.appendChild(departure.container);
		layout.changed(container);
		new DepartureMeeting(departure);

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