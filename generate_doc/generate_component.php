<?php 
function get_resources($path, $rel, &$image, &$javascript, &$unknown) {
	$dir = opendir($path);
	while (($filename = readdir($dir)) <> null) {
		if ($filename == "." || $filename == "..") continue;
		if (is_dir($path."/".$filename)) {
			get_resources($path."/".$filename, $rel.$filename."/", $image, $javascript, $unknown);
		} else {
			$i = strrpos($filename, ".");
			if ($i === FALSE)
				array_push($unknown, $rel.$filename);
			else switch (substr($filename, $i+1)) {
				case "js": array_push($javascript, $rel.$filename); break;
				case "png": case "jpg": case "jpeg": case "gif": array_push($image, $rel.$filename); break;
				case "readme": break;
				default: array_push($unknown, $rel.$filename);
			}
		}
	}
	closedir($dir);
}

function generate_component($name, &$nav) {
	echo "   + Component ".$name."\n";
	global $generated_dir,$www_dir;
	mkdir($generated_dir."/component/".$name);
	$path = $www_dir."/component/".$name;
	
	$comp_nav = array();
	
	$title2_counter = 1;
	$html = "<!DOCTYPE html>";
	$html .= "<html>";
	$html .= "<head>";
	$html .= "<link rel='stylesheet' type='text/css' href='../../style.css'/>";
	$html .= "</head>";
	$html .= "<body>";
	
	$html .= "<h1>".$name."</h1>";
	$html .= "<div style='margin-left:10px'>";
	
	if (file_exists($path."/doc/intro.html")) {
		$title = ($title2_counter++)." Introduction";
		$html .= "<a name='intro'><h2>".$title."</h2></a>";
		$html .= "<div style='margin-left:10px'>";
		$html .= file_get_contents($path."/doc/intro.html");
		$html .= "</div>";
		array_push($comp_nav, array("<img src='text.png'/>"."Introduction", "component/".$name."/index.html#intro"));
	}
	if (file_exists($path."/dependencies")) {
		$title = ($title2_counter++)." Dependencies";
		$html .= "<a name='dependencies'><h2>".$title."</h2></a>";
		$html .= "<div style='margin-left:10px'>";
		$uml = "class ".$name."\n";
		$f = fopen($path."/dependencies","r");
		while (($line = fgets($f, 4096)) !== false) {
			$line = trim($line);
			if (strlen($line) == 0) continue;
			$i = strpos($line,":");
			if ($i === FALSE) $i = strlen($line);
			$uml .= substr($line,0,$i)." <-- ".$name;
			$doc = trim(substr($line,$i+1));
			if (strlen($doc) > 0)
				$uml .= " : ".$doc;
			$uml .= "\n";
		}
		fclose($f);
		$uml .= "hide members\n";
		$uml .= "hide circle\n";
		generate_uml($uml, "component/".$name."/dependencies");
		$html .= "<img src='dependencies.png'/>";
		$html .= "</div>";
		array_push($comp_nav, array("<img src='dependencies.png'/>"."Dependencies", "component/".$name."/index.html#dependencies"));
	}
	if (file_exists($path."/datamodel.inc")) {
		$title = ($title2_counter++)." Data Model";
		$html .= "<a name='datamodel'><h2>".$title."</h2></a>";
		$html .= "<div style='margin-left:10px'>";
		require_once("DataModel.inc");
		$model = new DataModel();
		include $path."/datamodel.inc";
		
		$uml = "";
		foreach ($model->tables as $table) {
			$uml .= "class ".$table->name." {\n";
			foreach ($table->columns as $col) {
				if (isset($table->displayable_data[$col->name]))
					$uml .= "+"; // public
				else
					$uml .= "-"; // private
				$uml .= $col->name." : ".$col->get_type()."\n";
			}
			$uml .= "}\n";
			foreach ($table->columns as $col) {
				if ($col instanceof ForeignKey) {
					$uml .= $table->name." --> ".$col->foreign_table."\n";
				}
			}
			foreach ($table->links as $link) {
				$uml .= $table->name." --> ".$link->table."\n";
			}
		}
		$uml .= "hide methods\n";
		
		generate_uml($uml, "component/".$name."/data_model");
		$html .= "<img src='data_model.png'/>";
		$html .= "</div>";
		array_push($comp_nav, array("<img src='datamodel.png'/>"."Data Model", "component/".$name."/index.html#datamodel"));
	}
	// TODO rights
	// functionalities
	mkdir($generated_dir."/component/".$name."/php");
	mkdir_rec($generated_dir."/tmp/component/".$name."/php");
	copy_dir_flat($path, $generated_dir."/tmp/component/".$name."/php");
	exec(getenv("PHP_PATH")."/php.exe -c ".$generated_dir."/php.ini ".dirname(__FILE__)."/tools/apigen/apigen.php --source ".$generated_dir."/tmp/component/".$name."/php"." --destination ".$generated_dir."/component/".$name."/php --extensions inc,php");
	$s = file_get_contents($generated_dir."/component/".$name."/php/class-".$name.".html");
	
	$methods_list = array();
	$i = strpos($s, "<caption>Methods summary</caption>");
	if ($i !== FALSE) {
		$j = strpos($s, "</table>", $i);
		$methods = substr($s, $i, $j-$i);
		
		$i = 0;
		while (($i = strpos($methods, "<tr ", $i)) <> FALSE) {
			$j = strpos($methods, "</tr>", $i);
			$method = substr($methods, $i, $j-$i);
			$i = $j;
			$a = strpos($method, "\"");
			$b = strpos($method, "\"", $a+1);
			$method_name = substr($method, $a+1, $b-$a-1);
			if ($method_name == "init") continue; // skip init
			$a = strpos($method, "<td class=\"attributes\"><code>");
			$b = strpos($method, "</code>", $a);
			$visibility = substr($method, $a, $b-$a);
			if (strpos($visibility, "public") === FALSE) continue; // not public
			$a = strpos($method, "<div class=\"description short\">");
			if ($a === FALSE)
				$descr = "";
			else {
				$b = strpos($method, "</div>", $a);
				$descr = substr($method, $a, $b-$a+5);
			}
			$a = strpos($method, $method_name."</a>(");
			$b = strpos($method, ")", $a);
			$params = substr($method, $a+strlen($method_name)+4, $b-$a-strlen($method_name)-4+1);
			$return = "";
			$a = strpos($method, "<h4>Returns</h4>");
			if ($a !== FALSE) {
				$b = strpos($method, "<div class=\"list\">", $a);
				$c = strpos($method, "</div>", $b);
				$return = substr($method, $b+18, $c-$b-18);
				$return = str_replace("href=\"", "href=\"php/", $return);
			}
			array_push($methods_list, array("name"=>$method_name,"params"=>$params,"return"=>$return,"descr"=>$descr));
		}
	}
		
	$props_list = array();
	$i = strpos($s, "<caption>Properties summary</caption>");
	if ($i !== FALSE) {
		$j = strpos($s, "</table>", $i);
		$props = substr($s, $i, $j-$i);
	
		$i = 0;
		while (($i = strpos($props, "<tr ", $i)) <> FALSE) {
			$j = strpos($props, "</tr>", $i);
			$prop = substr($props, $i, $j-$i);
			$i = $j;
			$a = strpos($prop, "\"");
			$b = strpos($prop, "\"", $a+1);
			$prop_name = substr($prop, $a+1, $b-$a-1);
			$a = strpos($prop, "<td class=\"attributes\"><code>");
			$b = strpos($prop, "</code>", $a);
			$type = substr($prop, $a+23, $b-$a-23+7);
			$a = strpos($prop, "<div class=\"description short\">");
			if ($a === FALSE)
				$descr = "";
			else {
				$b = strpos($prop, "</div>", $a);
				$descr = substr($prop, $a, $b-$a+5);
			}
			array_push($props_list, array("name"=>$prop_name,"type"=>$type,"descr"=>$descr));
		}
	}
	
	$classes = array();
	$dir = opendir($generated_dir."/component/".$name."/php");
	while (($filename = readdir($dir)) <> null) {
		if (is_dir($generated_dir."/component/".$name."/php/".$filename)) continue;
		if (substr($filename,0,6)<>"class-") continue;
		$class_name = substr($filename,6,strlen($filename)-6-5);
		if ($class_name == $name) continue; // class of the component
		array_push($classes, $class_name);
	}
	
	if (count($methods_list) > 0 || count($props_list) > 0 || count($classes) > 0) {
		$title = ($title2_counter++)." Functionalities";
		$html .= "<a name='func'><h2>".$title."</h2></a>";
		$html .= "<div style='margin-left:10px'>";

		$func_nav = array();
		
		$html .= "<a href='php/index.html>Full PHP documentation</a><br/><br/>";
		array_push($func_nav, array("PHP Documentation", "component/".$name."/php/index.html"));
		
		if (count($methods_list) > 0) {
			$html .= "<div class='small_title'><a name='func_functions'>Functions</a></div>";
			array_push($func_nav, array("Functions", "component/".$name."/index.html#func_functions"));
			$html .= "<table style='border:1px solid #CCCCCC' rules='all'>";
			foreach ($methods_list as $method) {
				$html .= "<tr><td>";
				$html .= "<div class='php_method'><a href='php/class-".$name.".html#_".$method["name"]."'>".$method["name"]."</a> ".$method["params"]."</div>";
				if (strlen($method["return"]) > 0)
					$html .= "<table><tr><td valign=top><b>-&gt;</b></td><td class='php_method'>".$method["return"]."</td></tr></table>";
				$html .= $method["descr"];
				$html .= "</td></tr>";
			}
			$html .= "</table>";
			$html .= "<br/>";
		}

		if (count($props_list) > 0) {
			$html .= "<div class='small_title'><a name='func_props'>Properties kept in the session</a></div>";
			array_push($func_nav, array("Properties (session)", "component/".$name."/index.html#func_props"));
			$html .= "<table style='border:1px solid #CCCCCC' rules='all'>";
			foreach ($props_list as $prop) {
				$html .= "<tr><td class='php_method' valign=top>";
				$html .= $prop["type"];
				$html .= "</td><td class='php_method' valign=top>";
				$html .= "<a href='php/class-".$name.".html#\$".$prop["name"]."'>".$prop["name"]."</a>";
				$html .= "</td><td valign=top>";
				$html .= $prop["descr"];
				$html .= "</td></tr>";
			}
			$html .= "</table>";
			$html .= "<br/>";
		}
		
		if (count($classes) > 0) {
			$html .= "<div class='small_title'><a name='func_classes'>Classes</a></div>";
			array_push($func_nav, array("Classes", "component/".$name."/index.html#func_classes"));
			foreach ($classes as $cname) {
				$html .= "<a href='php/class-".$cname.".html'>".$cname."</a><br/>";
			}
			$html .= "<br/>";
		}
		
		$html .= "</div>";
		array_push($comp_nav, array("<img src='php.gif'/>"."Functionalities", "component/".$name."/index.html#func", $func_nav));
	}

	if (file_exists($path."/service")) {
		require_once($www_dir."/component/Service.inc");
		$title = ($title2_counter++)." Services";
		$html .= "<a name='services'><h2>".$title."</h2></a>";
		$service_nav = array();
		$html .= "<div style='margin-left:10px'>";
		$title3_counter = 1;
		$d = opendir($path."/service");
		while (($filename = readdir($d)) <> null) {
			$i = strrpos($filename, '.');
			if ($i !== FALSE && substr($filename,$i+1) == "php") {
				$service_name = substr($filename,0,$i);
				$title = ($title2_counter-1).".".($title3_counter++)." ".$service_name;
				$html .= "<a name='service_".$service_name."'><h3>".$title."</h3></a>";
				array_push($service_nav, array($service_name, "component/".$name."/index.html#service_".$service_name));
				$html .= "<div style='margin-left:10px'>";
				include($path."/service/".$service_name.".php");
				$classname = "service_".$service_name;
				$service = new $classname();
				$html .= "<table class='service_table'>";
				ob_start();$service->documentation();$doc=ob_get_clean();
				$html .= "<tr><td colspan=2>".$doc."</td></tr>";
				ob_start();$service->input_documentation();$doc=ob_get_clean();
				$html .= "<tr><td>Input</td><td>".$doc."</td></tr>";
				ob_start();$service->output_documentation();$doc=ob_get_clean();
				$html .= "<tr><td>Output (".$service->get_output_format().")</td><td>".$doc."</td></tr>";
				$html .= "<tr><td>Rights</td><td>";
				if (count($service->get_required_rights()) == 0)
					$html .= "This service is accessible to everyone.<br/>";
				else {
					$html .= "The service is accessible with the following rights:<ul>";
					foreach ($service->get_required_rights() as $r) {
						$html .= "<li>";
						if (is_array($r)) {
							$first = true;
							foreach ($r as $right) {
								if ($first) $first = false; else $html .= " and ";
								$html .= "<code>".$right."</code>";
							}
						} else
								$html .= "<code>".$r."</code>";
						$html .= "</li>";
					}
				}
				$html .= "</ul>";
				$html .= "</td></tr>";
				$html .= "</table>";
				$html .= "</div>";
			}
		}
		closedir($d);
		$html .= "</div>";
		array_push($comp_nav, array("<img src='service.gif'/>"."Services", "component/".$name."/index.html#services", $service_nav));
	}
	// TODO pages
	if (file_exists($path."/static")) {
		$title = ($title2_counter++)." Static Resources";
		$html .= "<a name='static_resources'><h2>".$title."</h2></a>";
		$static_nav = array();
		$html .= "<div style='margin-left:10px'>";
		
		$image = array();
		$javascript = array();
		$unknown = array();
		
		get_resources($path."/static", "", $image, $javascript, $unknown);

		$title3_counter = 1;
		if (count($javascript) > 0) {
			mkdir($generated_dir."/component/".$name."/javascript");
			$title = ($title2_counter-1).".".($title3_counter++)." Java Scripts";
			$html .= "<a name='javascripts'><h3>".$title."</h3></a>";
			$js_nav = array();
			$html .= "<ul>";
			foreach ($javascript as $filename) {
				copy_static($name, $filename, "component/".$name."/javascript/".$filename);
				mkdir($generated_dir."/component/".$name."/javascript/".$filename."_doc");
				$cmd = dirname(__FILE__)."/tools/jsdoc/jsdoc.cmd --destination ".$generated_dir."/component/".$name."/javascript/".$filename."_doc ".$generated_dir."/component/".$name."/javascript/".$filename;
				if (file_exists($path."/static/".$filename.".readme"))
					$cmd .= " ".$path."/static/".$filename.".readme";
				else
					echo "WARNING: no readme file for JavaScript ".$path."/static/".$filename."\n";
				execute(array($cmd,"del ".$generated_dir."/component/".$name."/javascript/".$filename));
				$html .= "<li><a href='javascript/".$filename."_doc/index.html'>".$filename."</a>";
				if (file_exists($path."/static/".$filename.".readme"))
					$html .= ": ".file_get_contents($path."/static/".$filename.".readme");
				$html .= "</li>";
				array_push($js_nav, array($filename, "component/".$name."/javascript/".$filename."_doc/index.html"));
			}
			$html .= "</ul>";
			array_push($static_nav, array("<img src='javascript.png'/>"."Java Scripts", "component/".$name."/index.html#javascripts", $js_nav));
		}
		if (count($image) > 0) {
			mkdir($generated_dir."/component/".$name."/images");
			$title = ($title2_counter-1).".".($title3_counter++)." Images";
			$html .= "<a name='images'><h3>".$title."</h3></a>";
			array_push($static_nav, array("<img src='image.png'/>"."Images", "component/".$name."/index.html#images"));
			$html .= "<table style='border:1px solid black' rules='all'>";
			foreach ($image as $filename) {
				copy_static($name, $filename, "component/".$name."/images/".$filename);
				$html .= "<tr><td><img src='images/".$filename."'/></td><td>".$filename."</td></tr>";
			}
			$html .= "</table>";
		}
		if (count($unknown) > 0) {
			$title = ($title2_counter-1).".".($title3_counter++)." Unknown resource types";
			$html .= "<a name='unknown_static'><h3>".$title."</h3></a>";
			array_push($static_nav, array("Unknown", "component/".$name."/index.html#unknown_static"));
			$html .= "<ul>";
			foreach ($unknown as $filename) {
				$html .= "<li>".$filename."</li>";
			}
			$html .= "</ul>";
		}
		$html .= "</div>";
		array_push($comp_nav, array("<img src='static.png'/>"."Static resources", "component/".$name."/index.html#static_resources", $static_nav));
	}

	$html .= "</div>";
	
	$html .= "</body>";
	$html .= "</html>";
	write_file("component/".$name."/index.html", $html);
	array_push($nav, array("<img src='component.png'/>".$name, "component/".$name."/index.html", $comp_nav));
}
?>