<?php
require_once("PeopleProfilePagePlugin.inc");
class PeopleProfilePage implements PeopleProfilePagePlugin {
	
	public function isValidFor($people_id, $types) { return true; }
	public function getPriority() { return 0; }
	public function getIcon() { return "/static/people/profile_16.png"; }
	public function getName() { return "General Information"; }
	public function getTooltip() { return "Civil information, contacts (emails, phone...), and other general information"; }
	public function getURL($people_id) { return "/dynamic/people/page/profile_people?people=".$people_id; }
	
}

require_once("PeopleProfileGeneralInfoPlugin.inc"); 
class PeopleGeneralInfo implements PeopleProfileGeneralInfoPlugin {
	
	public function getPriority() { return 0; }
	public function getIcon() { return "/static/people/people_info.png"; }
	public function getName() { return "Personal Information"; }
	
	public function isValidFor($people_id, $types) { return true; }
	
	public function prepareRequestForSection(&$q, $people_id) {
		require_once("component/data_model/Model.inc");
		$table = DataModel::get()->internalGetTable("People");
		$table_alias = $q->getTableAlias("People");
		$display = $table->getDisplayHandler(null);
		require_once("component/data_model/DataPath.inc");
		$path = new DataPath_Table($table, null);
		$res = array();
		foreach ($display->getDisplayableData() as $data) {
			$aliases = $data->buildSQL($q, $path, array());
			$res[$data->getDisplayName()] = $aliases["data"];
		}
		$q->field("People", "picture_version", "picture_version");
		$table->prepareSelectToDetermineWriteAccess($q, $table_alias);
		return $res;
	}
	
	public function generateSection(&$page, $people_id, $row, $prepareRequest, $q) {
		require_once("component/data_model/Model.inc");
		$table = DataModel::get()->internalGetTable("People");
		$display = $table->getDisplayHandler(null);
		$rows = $table->filterWriteAccess(array($row));
		$can_edit = count($rows) > 0;
		$page->add_javascript("/static/storage/upload.js");
		$page->add_javascript("/static/storage/picture.js");
?>
<table>
<tr>
<td valign=top>
	<div id='picture'></div>
	<div id='upload_picture' style='width:150px;overflow:hidden'></div>
</td>
<td valign=top>
	<table>
	<?php
	$fields = array();
	foreach ($display->getDisplayableData() as $data) {
		echo "<tr>";
		echo "<td>";
		echo htmlentities($data->getDisplayName(),ENT_QUOTES,"UTF-8");
		echo "</td>";
		$id = $page->generateID();
		echo "<td id='$id'></td>";
		array_push($fields, array($data, $id));
		echo "</td>";
		echo "</tr>";
	}  
	?>
	</table>
</td>
</tr>
</table>
<script type='text/javascript'>
picture('picture',
		'/dynamic/people/service/picture?people=<?php echo $people_id;?>&version=<?php echo $row["picture_version"]?>',
		150,200,
		"<?php echo htmlspecialchars($row[$q->getFieldAlias("People", "first_name")]." ".$row[$q->getFieldAlias("People", "last_name")],ENT_QUOTES,"UTF-8");?>"
);
<?php if ($can_edit) {?>
new upload('upload_picture',false,'/dynamic/people/page/set_picture?people=<?php echo $people_id;?>',
	function(popup){
		popup.setContentFrame('/dynamic/people/page/set_picture?people=<?php echo $people_id;?>');
	},
	true, null, "Change picture"
).addHiddenDropArea('picture');
<?php }?>	

function build_fields() {
	<?php
	require_once("component/data_model/page/utils.inc");
	foreach ($fields as $f) {
		$data = $f[0];
		$id = $f[1]; 
		$value = $row[$prepareRequest[$data->getDisplayName()]];
		datamodel_datadisplay($page, $data, $people_id, $value, null, $id, $can_edit);
	}
	?>
}
build_fields();
</script>
<?php 
	}
}
?>