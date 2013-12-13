<?php
class page_reset_db extends Page {
	
	public function get_required_rights() {
		return array();
	}
	
	protected function execute() {
?>
<div id='reset_db'></div>
<script type='text/javascript'>
var todo = [
<?php
if (isset($_GET["dev"]))
	$domains = array("Dev");
else {
	$domains = array();
	foreach (PNApplication::$instance->get_domains() as $domain=>$descr)
		array_push($domains, $domain);
}

$first = true;
foreach ($domains as $domain) {
	if ($domain == "Test") continue;
	if ($first) $first = false; else echo ",";
	echo "{service:'create_db',data:{domain:'".$domain."'},message:'Initialize database for domain ".$domain."'}";
	echo ",{service:'test_data',data:{domain:'".$domain."'},message:'Insert test data in domain ".$domain."'}";
}?>
];
function next() {
	var container = document.getElementById('reset_db'); 
	if (todo.length > 0) {
		var t = todo[0];
		todo.splice(0,1);
		var div = document.createElement("DIV");
		div.innerHTML = t.message;
		var img = document.createElement("IMG");
		img.src = theme.icons_16.loading;
		div.appendChild(img);
		container.appendChild(div);
		service.json('development',t.service,t.data,function(result){
			div.removeChild(img);
			div.appendChild(document.createTextNode(' DONE.'));
			next();
		});
	} else {
		var a = document.createElement("A");
		a.href = "#";
		a.onclick = function() {
			location.href = "/dynamic/application/page/enter";
		};
		a.innerHTML = "Back to login page";
		document.body.appendChild(a);
	}
}
next();
</script>
<?php
	}
	
}
?>