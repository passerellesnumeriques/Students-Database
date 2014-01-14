<?php 
class page_check_code extends Page {
	
	public function get_required_rights() { return array(); }
	
	public function execute() {
		$this->require_javascript("tree.js");
		$this->add_javascript("/static/documentation/jsdoc.js");
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
var checking_js = 0;
var js_todo = [];
function build_tree_js(parent_item, path, filename) {
	var item = new TreeItem("<img src='/static/development/javascript.png' style='vertical-align:bottom'/> "+filename, true);
	parent_item.addItem(item);
	js_todo.push(function(){
		checking_js++;
		setTimeout(function() {
			check_js_ns("", window.jsdoc, item, filename, path);
			checking_js--;
			check_end();
		},1);
	});
}
function check_js_ns(ns_path, ns, item, filename, path) {
	var location = path+filename;
	var i = location.indexOf("/static/");
	location = location.substring(0,i)+location.substring(i+7);
	for (var name in ns.content) {
		var elem = ns.content[name];
		if (elem instanceof JSDoc_Namespace) {
			if (elem.location.file == location) {
				// check name
				check_name_small_underscore(name, "Namespace "+ns_path+name, item);
				// check doc
				if (elem.doc.length == 0 && name != "window_top") add_error(item, "Namespace "+ns_path+name+": no comment");
			}
			// check content
			check_js_ns(ns_path+name+".", elem, item, filename, path);
		} else if (elem instanceof JSDoc_Class) {
			if (elem.location.file == location) {
				var i = filename.indexOf(".js");
				var fname = filename.substring(0,i);
				if (name != fname && !name.startsWith(fname)) {
					// not a class corresponding to the filename: must comply
					check_name_class(name, "Class "+ns_path+name, item);
				}
				// check doc
				if (elem.doc.length == 0) add_error(item, "Class "+ns_path+name+": no comment");
			}
			// check content
			check_js_ns(ns_path+name+".", elem, item, filename, path);
		} else if (elem instanceof JSDoc_Function) {
			if (elem.location.file != location) continue;
			if (name.charAt(0) == '_')
				check_name_small_then_capital(name.substring(1), "Private Function "+ns_path+name, item);
			else
				check_name_small_then_capital(name, "Public Function "+ns_path+name, item);
			// check doc
			if (elem.doc.length == 0) add_error(item, "Function "+ns_path+name+": no comment");
			if (elem.return_type && !elem.return_doc && elem.return_type != "void") add_error(item, "Function "+ns_path+name+": no comment for return value ("+elem.return_type+")");
			for (var j = 0; j < elem.parameters.length; ++j) {
				var p = elem.parameters[j];
				if (p.doc.length == 0) add_error(item, "Function "+ns_path+name+": no comment for parameter "+p.name);
				if (!p.type) add_error(item, "Function "+ns_path+name+": no type for parameter "+p.name);
				check_name_small_then_capital(p.name, "Parameter "+p.name+" in function "+ns_path+name, item);
			}
		} else if (elem instanceof JSDoc_Value) {
			if (elem.location.file != location) continue;
			if (name.charAt(0) == '_')
				check_name_small_underscore(name.substring(1), "Private Variable "+ns_path+name, item);
			else
				check_name_small_underscore(name, "Public Variable "+ns_path+name, item);
			// check doc
			if (elem.doc.length == 0) add_error(item, "Variable "+ns_path+name+": no comment");
		}
	}
}
function add_error(item, msg) {
	var e = new TreeItem("<img src='"+theme.icons_16.error+"' style='vertical-align:bottom'/> "+msg);
	items_to_add.push({parent:item,item:e});
}
function is_small_letter(letter) {
	if (letter.toUpperCase() != letter && letter.toLowerCase() == letter)
		return true;
	return false;
}
function is_capital_letter(letter) {
	if (letter.toLowerCase() != letter && letter.toUpperCase() == letter)
		return true;
	return false;
}
function is_letter(letter) {
	return is_small_letter(letter) || is_capital_letter(letter);
}
function is_digit(c) {
	var code = c.charCodeAt(0);
	if (code >= "0".charCodeAt(0) && code <= "9".charCodeAt(0))
		return true;
	return false;
}
function check_name_small_underscore(name, descr, item) {
	if (!is_small_letter(name.charAt(0))) {
		add_error(item, descr+": Must start with a small letter");
		return;
	}
	for (var i = 1; i < name.length; ++i) {
		var c = name.charAt(i);
		if (c != '_' && !is_small_letter(c) && !is_digit(c)) {
			add_error(item, descr+": Must contain only small letters, digits, and underscore between words");
			return;
		}
	}
}
function check_name_class(name, descr, item) {
	if (!is_capital_letter(name.charAt(0))) {
		add_error(item, descr+": Must start with a capital letter");
		return;
	}
	for (var i = 1; i < name.length; ++i) {
		var c = name.charAt(i);
		if (!is_letter(c) && !is_digit(c)) {
			add_error(item, descr+": Must contain only letters or digits");
			return;
		}
	}
}
function check_name_small_then_capital(name, descr, item) {
	if (!is_small_letter(name.charAt(0))) {
		add_error(item, descr+": Must start with a small letter");
		return;
	}
	for (var i = 1; i < name.length; ++i) {
		var c = name.charAt(i);
		if (!is_letter(c) && !is_digit(c)) {
			add_error(item, descr+": Must contain only letters or digits");
			return;
		}
	}
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

function check_end() {
	if (todo.length == 0 && in_progress == 0 && checking_js == 0) {
		unlock_screen(locker);
		var item = new TreeItem(""+items_to_add.length+" problem(s)");
		tr.insertItem(item, 0);
		for (var i = 0; i < items_to_add.length; ++i)
			items_to_add[i].parent.addItem(items_to_add[i].item);
	}
}

var in_progress = 0;
var total_todo = 0;
function next_todo() {
	var pc = Math.floor((total_todo-todo.length-in_progress)*100/total_todo);
	set_lock_screen_content(locker, "Checking code... ("+pc+"%, "+items_to_add.length+" problem(s) found)");
	if (todo.length == 0) {
		check_end();
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
	// load javascript
	checking_js++;
	service.json("documentation","get_js",{},function(res){
		if (res == null) { checking_js--; return; }
		var fct;
		try {
			fct = eval("(function (){"+res.js+";this.jsdoc = jsdoc;})");
		} catch (e) {
			window.top.status_manager.add_status(new window.top.StatusMessageError(e,"Invalid output for get_js: "+res.js,10000));
			checking_js--;
			return;
		}
		for (var i = 0; i < res.out.length; ++i) {
			add_error(tr, "JavaScript parsing: "+res.out[i]);
		}
		var doc = new fct();
		window.jsdoc = doc.jsdoc;
		for (var i = 0; i < js_todo.length; ++i)
			js_todo[i]();
		checking_js--;
	});
	// call services
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
				$this->build_tree_dir($path."/".$filename, $type <> "" ? $type : ($filename == "page" ? "page" : ($filename == "service" ? "service" : "")));
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