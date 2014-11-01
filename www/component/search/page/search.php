<?php 
class page_search extends Page {
	
	public function getRequiredRights() { return array(); }
	
	public function execute() {
		require_once("component/search/SearchPlugin.inc");
		if (isset($_POST["input"]))
			$_POST = json_decode($_POST["input"], true);
?>
<style type='text/css'>
.search_type {
	margin: 2px 5px 5px 5px;
	background-color: white;
	border: 1px solid #808080;
	border-radius: 3px;
	box-shadow: 2px 2px 2px 0px #808080;
}
.search_type .search_result_title {
	font-size: 11pt;
	color: #000080;
	font-weight: bold;
	padding: 1px 3px;
}
.search_type .search_result_category {
	margin-left: 5px;
}
.search_type .search_result_category .search_result_category_title {
	font-size: 10pt;
	color: #000080;
	font-weight: bold;
	padding: 1px 3px;
}
.search_type .search_results {
	margin-left: 10px;
}
.search_type .search_results>table {
	border-collapse: collapse;
	border-spacing: 0px;
}
.search_type .search_results tr.search_result_row {
}
.search_type .search_results tr.search_result_row>td {
	padding: 1px 2px;
}
.search_type .search_results tr.search_result_row:hover {
	background: linear-gradient(to bottom, #FFF0D0 0%, #F0D080 100%);
	cursor: pointer;
}
</style>
<div style='width:100%;height:100%;display:flex;flex-direction:column'>
	<div class='page_title' style='flex:none;display:flex;flex-direction:row;'>
		<div style='flex:none;'>Search</div>
		<div style='flex:1 0 auto;margin-left:20px;display:flex;flex-direction:column;align-self:center'>
			<input type='text' id='generic_search' value="<?php if (isset($_POST["q"])) echo toHTML($_POST["q"]);?>" onchange="postFrame('/dynamic/search/page/search',{q:this.value},'application_frame');" style="border-radius: 7px;background-color: white;color: black;border: 1px solid #40A0FF;background: #ffffff url('/static/application/menu/search.png') no-repeat 3px 1px;padding-left: 20px;font-size: 11pt;"/>
		</div>
	</div>
	<div style='flex:none;background-color:white;margin-bottom:3px;' class='light_shadow'>
		TODO: advanced search
	</div>
	<div id='results' style='flex:1 1 auto;overflow:auto'>
	</div>
</div>
<script type='text/javascript'>
function SearchTypeControl(div) {
	// TODO
}

function searchPlugin(data, name) {
	var results = document.getElementById('results');
	var div = document.createElement("DIV");
	div.className = "search_type";
	div.innerHTML = "<img src='"+theme.icons_16.loading+"' style='vertical-align:bottom'/> Searching "+name+"...";
	results.appendChild(div);
	service.customOutput("search","search",data,function(res) {
		if (res.length == 0) {
			results.removeChild(div);
			return;
		}
		div.innerHTML = res;
		new SearchTypeControl(div);
	});
}

<?php 
if (isset($_POST["q"])) {
	// generic search
	$plugins = array();
	foreach (PNApplication::$instance->components as $c)
		foreach ($c->getPluginImplementations() as $pi)
			if ($pi instanceof SearchPlugin)
				array_push($plugins, $pi);
	usort($plugins, function($p1,$p2){return $p1->getPriority()-$p2->getPriority();});
	foreach ($plugins as $pi) {
		echo "searchPlugin({plugin:".json_encode($pi->getId()).",generic:".json_encode($_POST["q"])."},".json_encode($pi->getName()).");\n";
	}
}
?>
</script>
<?php 
	}
	
}
?>