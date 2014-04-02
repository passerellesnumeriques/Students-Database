<?php 
class page_edit_academic_year extends Page {
	
	public function get_required_rights() { return array("edit_curriculum"); }
	
	public function execute() {
		$id = @$_GET["id"];
		if ($id <> null) {
			$year = SQLQuery::create()->select("AcademicYear")->whereValue("AcademicYear","id",$id)->executeSingleRow();
			$periods = SQLQuery::create()->select("AcademicPeriod")->whereValue("AcademicPeriod","year",$id)->execute();
		} else {
			$year = array(
				"id"=>-1,
				"year"=>$_GET["year"],
				"name"=>$_GET["year"]."-".(intval($_GET["year"])+1)
			);
			$conf = PNApplication::$instance->get_domain_descriptor();
			$conf = $conf["curriculum"];
			$periods = array();
			$last_year = $_GET["year"];
			for ($i = 0; $i < count($conf["default_year_periods"]); $i++) {
				$period = array(
					"id"=>-1,
					"year"=>-1,
					"name"=>$conf["period_name"]." ".($i+1),
					"weeks"=>$conf["period_weeks"]
				);
				$c = $conf["default_year_periods"][$i];
				$start = mktime(0,0,0,$c["month"],1,$last_year);
				$start += ($c["week"]-1)*7*24*60*60;
				$date = getdate($start);
				while ($date["wday"] <> 1) {
					$start += 24*60*60;
					$date = getdate($start);
				}
				$end = $start + ($conf["period_weeks"]+$c["weeks_break"])*7*24*60*60;
				$end -= 24*60*60;
				$period["start"] = date("Y-m-d", $start);
				$period["end"] = date("Y-m-d", $end);
				$period["weeks_break"] = $c["weeks_break"];
				$last_year = getdate($end);
				$last_year = $last_year["year"];
				array_push($periods, $period);
			}
		}
		
		$this->require_javascript("input_utils.js");
		require_once("component/curriculum/CurriculumJSON.inc");
?>
<div style='background-color:white'>
	<div style='text-align:center;font-size:12pt;'>
		Academic Year
		<input type='text' style='font-size:12pt;' id='year_name'/>
	</div>
	<table><tbody id='periods_table'>
	</tbody></table>
</div>
<script type='text/javascript'>
var year_name = document.getElementById('year_name');
year_name.value = <?php echo json_encode($year["name"]);?>;
inputAutoresize(year_name, 10);
inputDefaultText(year_name, "Name");

function getDateStringFromSQL(sql_date) {
	var date = parseSQLDate(sql_date);
	return getDateString(date);
}
function getDateString(date) {
	var s = getDayShortName(date.getDay() == 0 ? 6 : date.getDay()-1);
}

var periods = [];
function addPeriod(period) {
	var input_name = document.createElement("INPUT");
	input_name.type = "text";
	input_name.value = period.name;
	inputAutoresize(input_name, 15);
	inputDefaultText(input_name, "Name");

	var span_start = document.createElement("SPAN");
	span_start.appendChild(document.createTextNode(getDateStringFromSQL(period.start)));
	
	var table = document.getElementById('periods_table');
	var tr, td;
	table.appendChild(tr = document.createElement("TR"));
	tr.appendChild(td = document.createElement("TD"));
	td.appendChild(document.createTextNode("Period "));
	td.appendChild(input_name);
	tr.appendChild(td = document.createElement("TD"));
	td.appendChild(document.createTextNode("Starts on "));
	td.appendChild(span_start);
	
	periods.push(period);
}
<?php 
foreach ($periods as $p) {
	echo "addPeriod(".CurriculumJSON::AcademicPeriodJSONFromDB($p).");";
}
?>
</script>
<?php 
	}
	
}
?>