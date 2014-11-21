<?php 
require_once("component/selection/page/SelectionPage.inc");
class page_trip_trip extends SelectionPage {

	public function getRequiredRights() { return array("can_access_selection_data"); }

	public function executeSelectionPage() {
		$trip_id = @$input["id"];

		$this->addStylesheet("/static/selection/trip/trip.css");
?>
<table><tr><td id='trip_container'></td></tr></table>
<script type='text/javascript'>
var popup = window.parent.get_popup_window_from_frame(window);

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
		tr.appendChild(td = document.createElement("TD"));
		td.appendChild(this.div_number = document.createElement("DIV"));
		td.rowSpan = 2;
		this.div_number.className = "trip_node_activity_number";
		tr.appendChild(td = document.createElement("TD"));
		td.innerHTML = "What ?";
		td.className = "trip_node_activity_header";
		tr.appendChild(td = document.createElement("TD"));
		this.what = new InputOver(what);
		td.appendChild(this.what.container);

		table.appendChild(tr = document.createElement("TR"));
		tr.appendChild(td = document.createElement("TD"));
		td.innerHTML = "TODO";
		tr.appendChild(td = document.createElement("TD"));
		td.innerHTML = "TODO";
	};
	this.setNumber = function(num) {
		this.div_number.innerHTML = num;
	};
}

function TripNode(title) {
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
	this.title = new InputOver(title);
	this.header.appendChild(this.title.container);
	this.title.input.style.textAlign = "center";
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
	
	this.footer.innerHTML = "TODO";

	this.activities = [];
	
	this.addActivity = function(activity) {
		this.activities.push(activity);
		activity.createContent(this.activities_table.childNodes[0]);
		activity.setNumber(this.activities.length);
	};
	
	this.addConnection = function(connection) {
		td = document.createElement("TD");
		this.tr2.appendChild(td);
		this.node.parentNode.colSpan = this.tr2.childNodes.length;
		td.appendChild(connection.container);
		td.appendChild(connection.destination.container);
		connection.destination.container.style.width = "100%";
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

<?php if ($trip_id == null) { ?>
var departure = new TripNode("Departure");
container.appendChild(departure.container);
var departure_meeting = new Activity("Meeting for Departure");
departure.addActivity(departure_meeting);

var arrival = new TripNode("Arrival");
var conn = new TripConnection(arrival);
departure.addConnection(conn);

var toto = new TripNode("Toto");
var conn2 = new TripConnection(toto);
departure.addConnection(conn2);

popup.addOkCancelButtons(function() {
	// TODO create
});
<?php } else { ?>
popup.addFrameSaveButton(function() {
	// TODO save
});
popup.addCloseButton();
<?php } ?>

</script>
<?php 
	}
}
?>