<?php
$this->add_javascript("/static/widgets/typed_field/typed_field.js");
?>
<table>
<tr>
<td valign=top>
	<img id='picture' style='display:block' src="/dynamic/people/service/picture?people=0&version=0&max_width=150&max_height=200"/>
	<div id='upload_picture' style='width:150px;overflow:hidden'></div>
</td>
<td valign=top>
<table>
<?php
require_once("component/data_model/Model.inc");
$table = DataModel::get()->getTable("People");
$display = $table->getDisplayHandler(null);
$fields = array();
$javascripts = array();
foreach ($display->getDisplayableData() as $data) {
	echo "<tr>";
	echo "<td>".$data->getDisplayName()."</td>";
	$id = $this->generateID();
	echo "<td id='$id'>";
	$fields[$id] = $data;
	$tf = $data->getTypedField(null);
	if (!in_array($tf[0],$javascripts)) array_push($javascripts, $tf[0]);
	echo "</td>";
	echo "</tr>";
}
?>
</table>
</td>
<!-- 
<td valign=top>
	Here are shown the people already existing in the database, and who are similar to the new one.<br/>
	In order to avoid duplicates, if the people you are going to add already exists here, please select it:
	<table style='border:1px solid black;border-collapse:collapse;border-spacing:0'>
		<thead>
			<tr>
				<?php
				foreach ($display->getDisplayableData() as $data) {
					echo "<th style='border:1px solid black'>".$data->getDisplayName()."</th>";
				}
				?>
			</tr>
		</thead>
		<tbody id='similar_people'>
		</tbody>
	</table>
</td>
 -->
</tr>
</table>
<script type='text/javascript'>
function create_people__personal_info() {
	this.people_fields = [];
	this.init_fields = function() {
		this.people_fields = [<?php
		$first = true;
		foreach ($fields as $container_id=>$data) {
			if ($first) $first = false; else echo ",";
			echo "{container:".json_encode($container_id);
			echo ",name:".json_encode($data->getDisplayName());
			$cols = $data->getHandledColumns();
			$col = $table->getColumn($cols[0]);
			echo ",col:".json_encode($col->name);
			$tf = $data->getTypedField(null);
			echo ",field:new ".$tf[0]."(".json_encode($data->getNewData()).",true,".$tf[1].")";
			echo "}";
		}
		?>];

		for (var i = 0; i < this.people_fields.length; ++i) {
			var container = document.getElementById(this.people_fields[i].container);
			container.appendChild(this.people_fields[i].field.getHTMLElement());
			var error = document.createElement("SPAN");
			error.style.visibility = "hidden";
			error.style.position = "absolute";
			error.style.top = "-10000px";
			error.style.paddingLeft = "5px";
			error.style.color = 'red';
			this.people_fields[i].error = error;
			container.appendChild(error);
			if (window.create_people.people && window.create_people.people[this.people_fields[i].col])
				this.people_fields[i].field.setData(window.create_people.people[this.people_fields[i].col]);
		}
	};

	this.validate_and_save = function() {
		window.create_people.people = {};
		var ok = true;
		var errors = "";
		for (var i = 0; i < this.people_fields.length; ++i) {
			if (this.people_fields[i].field.hasError()) {
				errors += this.people_fields[i].name+": "+this.people_fields[i].field.getError()+"<br/>";
				ok = false;
			} 
			window.create_people.people[this.people_fields[i].col] = this.people_fields[i].field.getCurrentData();
		}
		if (!ok)
			error_dialog(errors);
		return ok;
	};
}
var create_people__personal_info_instance = new create_people__personal_info();
window.create_people_sections.push(create_people__personal_info_instance);
require([["typed_field.js",[<?php
$first = true;
foreach ($javascripts as $js) {
	if ($first) $first = false; else echo ",";
	echo json_encode($js.".js");
} 
?>]]],function(){create_people__personal_info_instance.init_fields();});
</script>