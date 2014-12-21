<?php 
class page_check_code extends Page {
	
	public function getRequiredRights() { return array(); }
	
	public function execute() {
if (!isset($_GET["content"])) {
	theme::css($this, "header_bar.css");
?>
<div style='width:100%;height:100%;display:flex;flex-direction:column'>
<div class='header_bar_style' style='flex:none'>
	Code Checking
	<input id='skip_js' type='checkbox'/>Skip JS
	<input id='skip_php' type='checkbox'/>Skip PHP
	<select id='select_what'>
		<option value='all'>Everything</option>
		<?php 
		foreach (PNApplication::$instance->components as $cname=>$c) {
			if ($cname == "test") continue;
			if ($cname == "development") continue;
			if ($cname == "documentation") continue;
			echo "<option value='$cname'>Only component $cname</option>";
		}
		?>
	</select>
	<button class='action' onclick='launch();'>Check</button>
</div>
<iframe id='check_code_frame' style='flex:1 1 auto;border:0px;margin:0px;padding:0px;'></iframe>
</div>
<script type='text/javascript'>
function launch() {
	var url = "/dynamic/development/page/check_code?content=true";
	if (document.getElementById('skip_js').checked) url += "&skip_js=true";
	if (document.getElementById('skip_php').checked) url += "&skip_php=true";
	var what = document.getElementById('select_what').value;
	if (what != 'all') url += "&component="+what;
	var iframe = document.getElementById('check_code_frame');
	iframe.src = url;
}
</script>
<?php 
	return;
}
		$this->requireJavascript("tree.js");
		$this->addJavascript("/static/documentation/jsdoc.js");
?>
<div id='tree_container'></div>
<script type='text/javascript'>
var files = <?php
$root = dirname($_SERVER["SCRIPT_FILENAME"])."/";
$sub_path = "";
if (isset($_GET["component"])) $sub_path = "component/".$_GET["component"]."/";
$this->build_tree_dir($root.$sub_path);
?>;
var tr = new tree('tree_container');
function build_tree(parent_item, files, path) {
	for (var i = 0; i < files.length; ++i) {
		if (files[i].type != 'dir') continue;
		var item = new TreeItem("<img src='/static/development/folder.png' style='vertical-align:bottom'/> "+files[i].name, false);
		item.file = files[i];
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
			build_tree_php(parent_item, path, files[i]);
			break;
		case "js":
			build_tree_js(parent_item, path, files[i]);
			break;
		}
	}
}
var todo = [];
var problems_counter = 0;
var having_problems = [];
function errorsItemsProvider(item, ondone) {
	item.children = [];
	for (var i = 0; i < item._errors.length; ++i)
		item.addItem(new TreeItem("<img src='"+theme.icons_16.error+"' style='vertical-align:bottom'/> "+item._errors[i]));
	ondone();
}
function add_error(item, msg, location) {
	if (location) msg += " ("+location.file+":"+location.line+")";
	problems_counter++;
	if (item.collapse) {
		if (item._errors) item._errors.push(msg);
		else {
			item._errors = [msg];
			item.collapse();
			item.children = undefined;
			item.children_on_demand = errorsItemsProvider;
			having_problems.push(item);
		}
	} else {
		item.addItem(new TreeItem("<img src='"+theme.icons_16.error+"' style='vertical-align:bottom'/> "+msg));
	}
}

function build_tree_php(parent_item, path, file) {
	var item = new TreeItem("<img src='/static/development/php.gif' style='vertical-align:bottom'/> "+file.name, true);
	item.file = file;
	parent_item.addItem(item);
	todo.push({
		service: "check_php",
		data: {path:path+file.name,type:file.sub_type},
		handler: function(res) {
			for (var i = 0; i < res.length; ++i) add_error(item, res[i]);
		}
	});
	todo.push({
		service: "check_todo",
		data: {path:path+file.name},
		handler: function(res) {
			for (var i = 0; i < res.length; ++i) add_error(item, res[i]);
		}
	});
}
var checking_js = 0;
var js_todo = [];
function build_tree_js(parent_item, path, file) {
	var item = new TreeItem("<img src='/static/development/javascript.png' style='vertical-align:bottom'/> "+file.name, false);
	item.file = file;
	parent_item.addItem(item);
	js_todo.push(function(){
		checking_js++;
		setTimeout(function() {
			check_js_ns("", window.jsdoc, item, file.name, path);
			checking_js--;
			check_end();
		},1);
	});
	todo.push({
		service: "check_todo",
		data: {path:path+file.name},
		handler: function(res) {
			for (var i = 0; i < res.length; ++i) add_error(item, res[i]);
		}
	});
}
function check_js_ns(ns_path, ns, item, filename, path) {
	var location = path+filename;
	var i = location.indexOf("/static/");
	location = location.substring(0,i)+location.substring(i+7);
	var parent_classes = [];
	if (ns instanceof JSDoc_Class && ns.extended) {
		var pc = get_class(window.jsdoc, ns.extended);
		while (pc != null) {
			parent_classes.push(pc);
			if (!pc.extended) break;
			pc = get_class(window.jsdoc, pc.extended);
		}
	}
	for (var name in ns.content) {
		var is_overriden = false;
		for (var i = 0; i < parent_classes.length; ++i) if (parent_classes[i].content[name]) { is_overriden = true; break; }
		if (is_overriden) continue; // skip overriden elements
		var elem = ns.content[name];
		if (elem instanceof JSDoc_Namespace) {
			if (elem.location.file == location) {
				// check name
				//check_name_small_underscore(name, "Namespace "+ns_path+name, item);
				// check doc
				if (elem.doc.length == 0 && name != "window_top") add_error(item, "Namespace "+ns_path+name+": no comment", elem.location);
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
				if (elem.doc.length == 0) add_error(item, "Class "+ns_path+name+": no comment", elem.location);
			}
			// check content
			check_js_ns(ns_path+name+".", elem, item, filename, path);
		} else if (elem instanceof JSDoc_Function) {
			if (elem.location.file != location) continue;
			if (!elem.no_name_check) {
				if (name.charAt(0) == '_')
					check_name_small_then_capital(name.substring(1), "Private Function "+ns_path+name, item);
				else
					check_name_small_then_capital(name, "Public Function "+ns_path+name, item);
			}
			// check doc
			if (elem.doc.length == 0) add_error(item, "Function "+ns_path+name+": no comment", elem.location);
			if (elem.return_type && !elem.return_doc && elem.return_type != "void") add_error(item, "Function "+ns_path+name+": no comment for return value ("+elem.return_type+")", elem.location);
			for (var j = 0; j < elem.parameters.length; ++j) {
				var p = elem.parameters[j];
				if (p.doc.length == 0) add_error(item, "Function "+ns_path+name+": no comment for parameter "+p.name, elem.location);
				if (!p.type) add_error(item, "Function "+ns_path+name+": no type for parameter "+p.name, elem.location);
				else check_js_type(p.type, "Function "+ns_path+name+", Parameter "+p.name, item, elem.location);
				check_name_small_underscore(p.name, "Parameter "+p.name+" in function "+ns_path+name, item);
			}
		} else if (elem instanceof JSDoc_Value) {
			if (elem.location.file != location) continue;
			if (!elem.no_name_check) {
				if (name.charAt(0) == '_')
					check_name_small_underscore(name.substring(1), "Private Variable "+ns_path+name, item);
				else
					check_name_small_underscore(name, "Public Variable "+ns_path+name, item);
			}
			// check doc
			if (elem.doc.length == 0) add_error(item, "Variable "+ns_path+name+": no comment", elem.location);
			if (!elem.type) add_error(item, "Variable "+ns_path+name+": no type", elem.location);
			else check_js_type(elem.type, "Variable "+ns_path+name, item, elem.location);
		}
	}
}
function check_js_type(type, descr, item, location) {
	var i = type.indexOf('|');
	if (i > 0) {
		check_js_type(type.substring(0,i).trim(), descr, item, location);
		check_js_type(type.substring(i+1).trim(), descr, item, location);
		return;
	}
	if (type == "String") return;
	if (type == "Array") return;
	if (type == "Date") return;
	if (type == "Number") return;
	if (type == "Boolean") return;
	if (type == "Window") return;
	if (type == "Document") return;
	if (type == "Location") return;
	if (type == "Element") return;
	if (type == "Function") return;
	if (type == "Object") return;
	if (type == "Event") return;
	if (type == "Exception") return;
	if (type == "null") return;
	var cl = get_class(window.jsdoc, type);
	if (cl == null)
		add_error(item, descr+": unknown type <i>"+type+"</i>", location); 
}
function get_class(ns, name) {
	var i = name.indexOf('.');
	var next_name, after;
	if (i < 0) {
		next_name = name;
		after = null;
	} else {
		next_name = name.substring(0,i);
		after = name.substring(i+1);
	}
	var cl = null;
	for (var n in ns.content) {
		if (n == next_name) {
			cl = ns.content[n];
			break;
		}
	}
	if (cl == null) return null;
	if (after == null) {
		if (cl instanceof JSDoc_Class) return cl;
		return null;
	}
	if (cl instanceof JSDoc_Namespace)
		return get_class(cl, after);
	return null;
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
		if (!is_letter(c) && !is_digit(c) && c != "_") {
			add_error(item, descr+": Must contain only letters, digits or underscore");
			return;
		}
	}
}
function check_name_small_then_capital(name, descr, item) {
	if (!is_small_letter(name.charAt(0)) && !is_digit(name.charAt(0))) {
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
build_tree(tr, files, "<?php echo $sub_path;?>");

function check_end() {
	if (todo.length == 0 && in_progress == 0 && checking_js == 0) {
		var time = new Date().getTime() - window._start_check_code;
		unlock_screen(locker);
		var item = new TreeItem(""+problems_counter+" problem(s) in "+Math.floor(time/1000)+"s.");
		tr.insertItem(item, 0);
		for (var i = 0; i < having_problems.length; ++i) {
			has_error(having_problems[i]);
		}
	}
}
function has_error(p) {
	if (p instanceof tree) return;
	if (p._errors) {
		var span = document.createElement("SPAN");
		span.style.color = "red";
		span.innerHTML = p._errors.length+" problem(s)";
		span.style.marginLeft = "5px";
		p.cells[0].element.appendChild(span);
	} else
		p.expand();
	if (p.parent) has_error(p.parent);
}

var in_progress = 0;
var total_todo = 0;
function next_todo() {
	var pc = Math.floor((total_todo-todo.length-in_progress)*100/total_todo);
	set_lock_screen_content(locker, "Checking code... ("+pc+"%, "+problems_counter+" problem(s) found)");
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

function findItemPath(parent, location) {
	var filename, remaining;
	var i = location.indexOf('/');
	if (i > 0) {
		filename = location.substring(0,i);
		remaining = location.substring(i+1);
	} else {
		filename = location;
		remaining = null;
	}
	var found = null;
	var children = parent == tr ? parent.items : parent.children;
	for (var i = 0; i < children.length; ++i) {
		var item = children[i];
		if (!item.file) continue;
		if (item.file.name.toLowerCase() != filename.toLowerCase()) continue;
		found = item;
		break;
	}
	if (!found) return null;
	if (!remaining) return found;
	return findItemPath(found, remaining);
}

var locker;
setTimeout(function() {
	window._start_check_code = new Date().getTime();
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
			var msg = res.out[i];
			msg = msg.trim();
			if (msg.endsWith(")")) {
				var j = msg.lastIndexOf('(');
				if (j > 0) {
					var location = msg.substring(j+1);
					j = location.indexOf(':');
					if (j > 0) location = location.substring(0,j); else location = location.substring(0,location.length-1);
					// add the static in the location
					if (location.startsWith("component/")) {
						j = location.indexOf('/',10);
						if (j > 0) {
							location = location.substring(0,j)+"/static"+location.substring(j);
							var item = findItemPath(tr, location);
							if (item) {
								add_error(item, "JavaScript parsing: "+msg);
								continue;
							}
						}
					}
				}
			}
			add_error(tr, "JavaScript parsing: "+msg);
		}
		var doc = new fct();
		window.jsdoc = doc.jsdoc;
		var next_js_todos = function(start) {
			var i;
			for (i = start; i < start+50 && i < js_todo.length; ++i)
				js_todo[i]();
			if (i == js_todo.length)
				checking_js--;
			else setTimeout(function() {
				next_js_todos(i);
			}, 50);
		};
		next_js_todos(0);
	});
	checking_js++;
	service.json("development","check_datamodel",{},function(problems) {
		if (problems && problems.length > 0) {
			var datamodel = new TreeItem("Data Model", true);
			tr.insertItem(datamodel, 0);
			for (var i = 0; i < problems.length; ++i)
				add_error(datamodel, problems[i]);
		}
		checking_js--;
		check_end();
	});
	// call services
	next_todo();
	next_todo();
	for (var i = 0; i < 13; ++i)
		setTimeout(function() { next_todo(); }, i*50);
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
				if ($filename == "updates") continue;
				if ($filename == "data") continue;
				if ($filename == "maintenance") continue;
				if ($filename == "conf") continue;
				if (substr($filename, 0, 4) == "lib_") continue;
				if ($first) $first = false; else echo ",";
				echo "{type:'dir',name:".json_encode($filename).",content:";
				$this->build_tree_dir($path."/".$filename, $type <> "" ? $type : ($filename == "page" ? "page" : ($filename == "service" ? "service" : "")));
				echo "}";
			} else {
				if (substr($filename, 0, 9) == "datamodel") continue;
				if ($filename == "init_data.inc") continue;
				if ($filename == "deploy.script.php") continue;
				if ($filename == "cron.php") continue;
				if ($filename == "cron_maintenance.php") continue;
				$i = strrpos($filename, ".");
				if ($i === FALSE) continue;
				$ext = strtolower(substr($filename,$i+1));
				if (isset($_GET["skip_js"]) && $ext == "js") continue;
				if (isset($_GET["skip_php"]) && ($ext == "php" || $ext == "inc")) continue;
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