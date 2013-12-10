<?php 
class page_check_code extends Page {
	
	public function get_required_rights() { return array(); }
	
	public function execute() {
		$this->require_javascript("tree.js");
?>
<div id='page_header'>
	Code Checking
	<div class='button' onclick='location.reload();'><img src='<?php echo theme::$icons_16["refresh"];?>'/></div>
</div>
<div id='tree_container'></div>
<script type='text/javascript'>
var files = <?php $this->build_tree_dir(dirname($_SERVER["SCRIPT_FILENAME"]));?>;
var tr = new tree('tree_container');
function build_tree(parent_item, files, path) {
	for (var i = 0; i < files.length; ++i) {
		if (files[i].type != 'dir') continue;
		var item = new TreeItem("<img src='/static/development/folder.png' style='vertical-align:bottom'/> "+files[i].name, true);
		parent_item.addItem(item);
		build_tree(item, files[i].content, path+files[i].name+"/");
	}
	for (var i = 0; i < files.length; ++i) {
		if (files[i].type != 'file') continue;
		var j = files[i].name.lastIndexOf('.');
		if (j <= 0) continue;
		var ext = files[i].name.substring(j+1).toLowerCase();
		switch (ext) {
		case "php": case "inc":
			build_tree_php(parent_item, path, files[i].name, files[i].sub_type);
			break;
		case "js":
			build_tree_js(parent_item, path, files[i].name);
			break;
		}
	}
}
var todo = [];
var items_to_add = [];
function build_tree_php(parent_item, path, filename, type) {
	var item = new TreeItem("<img src='/static/development/php.gif' style='vertical-align:bottom'/> "+filename, true);
	parent_item.addItem(item);
	todo.push({
		service: "check_php",
		data: {path:path+filename,type:type},
		handler: function(res) {
			for (var i = 0; i < res.length; ++i) {
				var e = new TreeItem("<img src='"+theme.icons_16.error+"' style='vertical-align:bottom'/> "+res[i]);
				items_to_add.push({parent:item,item:e});
			}
		}
	});
}
function build_tree_js(parent_item, path, filename) {
	var item = new TreeItem("<img src='/static/development/javascript.png' style='vertical-align:bottom'/> "+filename, true);
	parent_item.addItem(item);
}
function clean_files(files) {
	for (var i = 0; i < files.length; ++i) {
		if (files[i].type == 'dir') {
			clean_files(files[i].content);
			if (files[i].content.length == 0) {
				files.splice(i,1);
				i--;
			}
		}
	}
}
clean_files(files);
build_tree(tr, files, "");

var in_progress = 0;
var total_todo = 0;
function next_todo() {
	var pc = Math.floor((total_todo-todo.length-in_progress)*100/total_todo);
	set_lock_screen_content(locker, "Checking code... ("+pc+"%)");
	if (todo.length == 0) {
		if (in_progress == 0) {
			unlock_screen(locker);
			for (var i = 0; i < items_to_add.length; ++i)
				items_to_add[i].parent.addItem(items_to_add[i].item);
		}
		return;
	}
	in_progress++;
	var t = todo[0];
	todo.splice(0,1);
	service.json("development",t.service,t.data,function(res) {
		in_progress--;
		next_todo();
		if (!res) return;
		t.handler(res);
	});
}

var locker;
setTimeout(function() {
	total_todo = todo.length;
	locker = lock_screen(null, "Checking code...");
	next_todo();
	next_todo();
	setTimeout(function() { next_todo(); }, 50);
	setTimeout(function() { next_todo(); }, 100);
	setTimeout(function() { next_todo(); }, 200);
	setTimeout(function() { next_todo(); }, 300);
	setTimeout(function() { next_todo(); }, 400);
	setTimeout(function() { next_todo(); }, 500);
	setTimeout(function() { next_todo(); }, 700);
	setTimeout(function() { next_todo(); }, 900);
	setTimeout(function() { next_todo(); }, 1200);
}, 1000);
</script>
<?php 
	}
	
	private function build_tree_dir($path, $type = "") {
		echo "[";
		$dir = opendir($path);
		$dirs = array();
		$files = array();
		$first = true;
		while (($filename = readdir($dir)) !== FALSE) {
			if ($filename == "." || $filename == "..") continue;
			if (is_dir($path."/".$filename)) {
				if ($filename == "test") continue;
				if ($filename == "development") continue;
				if ($filename == "documentation") continue;
				if (substr($filename, 0, 4) == "lib_") continue;
				if ($first) $first = false; else echo ",";
				echo "{type:'dir',name:".json_encode($filename).",content:";
				$this->build_tree_dir($path."/".$filename, $type <> "" ? $type : $filename == "page" ? "page" : $filename == "service" ? "service" : "");
				echo "}";
			} else {
				if ($filename == "datamodel.inc") continue;
				if ($filename == "init_data.inc") continue;
				$i = strrpos($filename, ".");
				if ($i === FALSE) continue;
				$ext = strtolower(substr($filename,$i+1));
				if ($ext == "php") {}
				else if ($ext == "inc") { if ($type <> "") continue; }
				else if ($ext == "js") {}
				else continue;
				if ($first) $first = false; else echo ",";
				echo "{type:'file',sub_type:".json_encode($type).",name:".json_encode($filename)."}";
			}
		}
		closedir($dir);
		echo "]";
	}
	
}
?>