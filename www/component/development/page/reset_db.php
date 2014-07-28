<?php
class page_reset_db extends Page {
	
	public function getRequiredRights() {
		return array();
	}
	
	protected function execute() {
?>
<div id='reset_db'></div>
<script type='text/javascript'>
var todo = [
<?php
if (isset($_GET["domain"]))
	$domains = array($_GET["domain"]);
else {
	$domains = array();
	foreach (PNApplication::$instance->getDomains() as $domain=>$descr)
		array_push($domains, $domain);
}

$first = true;
foreach ($domains as $domain) {
	if ($domain == "Test") continue;
	if ($first) $first = false; else echo ",";
	echo "{service:'create_db',data:{domain:'".$domain."'},message:'Initialize database for domain ".$domain."'}";
	echo ",{service:'reset_storage',data:{domain:'".$domain."'},message:'Removing stored data for domain ".$domain."'}";
	echo ",{service:'init_data',data:{domain:'".$domain."'},message:'Creating initial data for ".$domain."'}";
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
		document.body.appendChild(document.createElement("BR"));
		document.body.appendChild(document.createTextNode("The database has been reset."));
		document.body.appendChild(document.createElement("BR"));
		var a;
		a = document.createElement("A");
		a.href = "#";
		a.onclick = function() {
			location.reload();
			return false;
		};
		a.innerHTML = "Retry to reset Database";
		document.body.appendChild(a);
		document.body.appendChild(document.createElement("BR"));
		document.body.appendChild(document.createElement("BR"));
		document.body.appendChild(document.createTextNode("Would you like to insert some test data ? "));
		a = document.createElement("A");
		a.href = "#";
		a.onclick = function() {
			var link = this;
			var locker = lock_screen(null, "Inserting test data");
			service.json("development","test_data",{domain:<?php echo json_encode($domain);?>,password:""},function(res) {
				link.innerHTML = "Test data inserted";
				link.onclick = function() { return false; };
				unlock_screen(locker);
			});
			return false;
		};
		a.innerHTML = "Yes, please insert test data";
		document.body.appendChild(a);
		document.body.appendChild(document.createElement("BR"));
	}
}
next();
</script>
<?php
	}
	
}
?>