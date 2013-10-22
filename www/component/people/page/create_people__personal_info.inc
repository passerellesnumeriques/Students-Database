<?php
$this->add_javascript("/static/widgets/typed_field/typed_field.js");
?>
<table>
<tr>
<td>
	<img id='picture' style='display:block' src="/dynamic/people/service/picture?people=0&version=0&max_width=150&max_height=200"/>
	<div id='upload_picture' style='width:150px;overflow:hidden'></div>
</td>
<td valign=top>
<table>
<?php
require_once("component/data_model/Model.inc");
$table = DataModel::get()->getTable("People");
$fields = array();
foreach ($table->getDisplayableData() as $col_name=>$disp) {
	echo "<tr>";
	echo "<td>".$disp[1]."</td>";
	$id = $this->generate_id();
	echo "<td id='$id'>";
	$col = $table->getColumn($col_name);
	$tf = PNApplication::$instance->widgets->get_typed_field($col);
	$this->add_javascript("/static/widgets/typed_field/".$tf[0].".js");
	$fields[$col_name] = $id;
	echo "</td>";
	echo "</tr>";
}
?>
</table>
</td>
<td valign=top>
	Here are shown the people already existing in the database, and who are similar to the new one.<br/>
	In order to avoid duplicates, if the people you are going to add already exists here, please select it:
	<table style='border:1px solid black;border-collapse:collapse;border-spacing:0'>
		<thead>
			<tr>
				<?php
				foreach ($table->getDisplayableData() as $col_name=>$disp) {
					echo "<th style='border:1px solid black'>".$disp[1]."</th>";
				}
				?>
			</tr>
		</thead>
		<tbody id='similar_people'>
		</tbody>
	</table>
</td>
</tr>
</table>
<script type='text/javascript'>
function search_similar() {
	var table = document.getElementById('similar_people');
	while (table.childNodes.length > 0) table.removeChild(table.childNodes[0]);
	save_create_people_info(false);
	window.parent.create_people.people.people_id = null;
	service.json("people","search_people",window.parent.create_people.people,function(res){
		if (!res) return;
		for (var i = 0; i < res.length; ++i) {
			var cells = [];
			for (var j = 0; j < fields.length; ++j) cells.push(null);
			for (var name in res[i]) {
				for (var j = 0; j < fields.length; ++j)
					if (fields[j].col == name) { cells[j] = res[i][name]; break; }
			}
			var tr = document.createElement("TR");
			tr.style.cursor = 'pointer';
			tr.data = res[i];
			tr.onclick = function() {
				// update fields
				for (var name in this.data)
					for (var j = 0; j < fields.length; ++j)
						if (fields[j].col == name) { fields[j].setData(this.data[name]); break; }
				// put the id in the structure
				window.parent.create_people.people.people_id = this.data["id"];
			};
			for (var j = 0; j < cells.length; ++j) {
				var td = document.createElement("TD");
				td.innerHTML = cells[j];
				td.style.border = "1px solid black";
				tr.appendChild(td);
			}
			table.appendChild(tr);
		}
	});
}

var fields = [
<?php 
$first = true;
foreach ($fields as $col_name=>$container_id) {
	$col = $table->getColumn($col_name);
	$tf = PNApplication::$instance->widgets->get_typed_field($col);
	if ($first) $first = false; else echo ",";
	echo "{container:".json_encode($container_id);
	echo ",col:".json_encode($col_name);
	echo ",field:new ".$tf[0]."(null,true,search_similar,search_similar,".$tf[1].")";
	echo ",validate:function(){";
		echo "var err = null;";
		echo "var value = this.field.getCurrentData();";
		echo $col->validate_javascript("value","err");
		echo "this.field.signal_error(err!=null);";
		echo "if (err==null){this.error.style.visibility='hidden';this.error.style.position='absolute';}";
		echo "else{this.error.style.visibility='visible';this.error.style.position='static';this.error.innerHTML=\"<img src='\"+theme.icons_16.error+\"' style='vertical-align:bottom;padding-right:3px;'/>\"+err;}";
		echo "return err == null;";
	echo "}";
	echo "}";
}
?>
];
for (var i = 0; i < fields.length; ++i) {
	var container = document.getElementById(fields[i].container);
	container.appendChild(fields[i].field.getHTMLElement());
	var error = document.createElement("SPAN");
	error.style.visibility = "hidden";
	error.style.position = "absolute";
	error.style.top = "-10000px";
	error.style.paddingLeft = "5px";
	error.style.color = 'red';
	fields[i].error = error;
	container.appendChild(error);
	if (window.parent.create_people.people && window.parent.create_people.people[fields[i].col])
		fields[i].field.setData(window.parent.create_people.people[fields[i].col]);
}

function save_create_people_info(validate) {
	window.parent.create_people.people = {};
	var ok = true;
	for (var i = 0; i < fields.length; ++i) {
		ok &= fields[i].validate();
		window.parent.create_people.people[fields[i].col] = fields[i].field.getCurrentData();
	}
	return ok;
}
</script>