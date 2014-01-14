<?php 
class PHPDoc {
	
	public static function generate_doc($files) {
		foreach ($files as $file) require_once($file);
		for ($i = 0; $i < count($files); $i++)
			$files[$i] = realpath($files[$i]);
		$existing = get_declared_classes();
		$interfaces = get_declared_interfaces();
		$existing = array_merge($existing, $interfaces);
		$list = array();
		foreach ($existing as $clname) {
			$cl = new ReflectionClass($clname);
			if (in_array($cl->getFileName(), $files))
				array_push($list, $cl);
		}
		$table = new DocTable();
		$row = $table->add_row();
		$row->add_cell("Classes", "codedoc_table_title", 2);
		foreach ($list as $cl) {
			$row = $table->add_row();
			$row->add_cell("<a class='codedoc_class' href='php?file=".urlencode($cl->getFileName())."&class=".$cl->getName()."'>".$cl->getName()."</a>");
			$row->add_cell(PHPDoc::general_comment($cl->getDocComment()));
		}
		$table->generate();
	}
	
	public static function class_doc($classname, $filename) {
		require_once($filename);
		$cl = new ReflectionClass($classname);
		echo "<h1>".$classname."</h1>";
		
		echo "<code class='codedoc_keyword'>";
		foreach (Reflection::getModifierNames($cl->getModifiers()) as $mod)
			echo $mod." ";
		echo "class</code> <span class='codedoc_class'>".$cl->getName()."</span>";
		$list = $cl->getInterfaceNames();
		if (count($list) > 0) {
			echo " <code class='codedoc_keyword'>implements</code>";
			$first = true;
			foreach ($list as $i) {
				if ($first) $first = false; else echo ",";
				echo " ".PHPDoc::type_link($i);
			}
		}
		if ($cl->getParentClass() <> null) {
			echo " <code class='codedoc_keyword'>extends</code> ";
			echo PHPDoc::type_link($cl->getParentClass());
		}
		
		echo "<br/><br/>";
		echo "File: ".$cl->getFileName()."<br/>";
		
		$comment = "";
		$tags = array();
		PHPDoc::parse_comment($cl->getDocComment(), $comment, $tags);
		echo "<div class='codedoc_comment' style='margin:10px;padding:3px'>".$comment."</div>";
		
		echo "<br/>";
	
		$list = array();
		foreach ($cl->getProperties() as $p)
			if ($p->getDeclaringClass() == $cl)
				array_push($list, $p);
		if (count($list) > 0) {
			$table = new DocTable();
			$row = $table->add_row();
			$row->add_cell("Properties", "codedoc_table_title", 4);
			$row = $table->add_row();
			$row->add_cell("Modifiers", "codedoc_table_title2");
			$row->add_cell("Name", "codedoc_table_title2");
			$row->add_cell("Type", "codedoc_table_title2");
			$row->add_cell("Description", "codedoc_table_title2");
			foreach ($list as $p) {
				$row = $table->add_row();
				// modifiers
				$modifiers = "";
				foreach (Reflection::getModifierNames($p->getModifiers()) as $mod)
					$modifiers .= $mod." ";
				$row->add_cell($modifiers, "codedoc_modifiers");
				// name
				$row->add_cell($p->name, "codedoc_property");
				// type
				$comment = "";
				$tags = array();
				PHPDoc::parse_comment($p->getDocComment(), $comment, $tags);
				$type = "";
				if (isset($tags["var"])) {
					$type = PHPDoc::getWord($tags["var"][0]);
					if ($type <> "") {
						$name = PHPDoc::getWord($tags["var"][0]);
						if ($name <> "") {
							if ($name <> "\$".$p->name) $tags["var"][0] = $name." ".$tags["var"][0];
							if ($comment == "") $comment = trim($tags["var"][0]);
						}
						$type = PHPDoc::type_link($type); 
					}
				}
				$row->add_cell($type, "codedoc_class");
				// description
				$row->add_cell($comment, "codedoc_comment");
			}
			$row = $table->add_row();
			$table->generate();
			echo "<br/>";
		}
		$list = array();
		foreach ($cl->getMethods() as $m)
			if ($m->getDeclaringClass() == $cl)
				array_push($list, $m);
		if (count($list) > 0) {
			$table = new DocTable();
			$row = $table->add_row();
			$row->add_cell("Methods", "codedoc_table_title", 4);
			$row = $table->add_row();
			$row->add_cell("Modifiers", "codedoc_table_title2");
			$row->add_cell("Name", "codedoc_table_title2");
			$row->add_cell("Parameters", "codedoc_table_title2");
			$row->add_cell("Description", "codedoc_table_title2");
			foreach ($list as $m) {
				$row = $table->add_row();
				// modifiers
				$modifiers = "";
				foreach (Reflection::getModifierNames($m->getModifiers()) as $mod)
					$modifiers .= $mod." ";
				$cell_modifiers = $row->add_cell($modifiers, "codedoc_modifiers", 1, "vertical-align:top;");
				// name
				$cell_name = $row->add_cell("<a name='method_".$m->name."'>".$m->name."</a>", "codedoc_method", 1, "vertical-align:top;");
				// check if inherited
				$origin = PHPDoc::get_original_method_declaration($m);
				if ($origin <> $m) {
					$row->add_cell("<a href='php?file=".urlencode($origin->getFileName())."&class=".urlencode($origin->getDeclaringClass()->getName())."#method_".$origin->getName()."'><i>Inherits from ".$origin->getDeclaringClass()->getName()."</i></a>", null, 2);
					continue;
				}
				// parameters
				$comment = "";
				$tags = array();
				PHPDoc::parse_comment($m->getDocComment(), $comment, $tags);
				$new_row = false;
				if ($comment <> "") {
					$row->add_cell($comment, "codedoc_comment", 2);
					$new_row = true;
				}
				$parameters = $m->getParameters();
				$rows = $new_row ? 1 : 0;
				$rows += count($parameters);
				if (isset($tags["return"])) $rows++;
				if ($rows == 0) $rows = 1;
				$cell_modifiers->rowspan = $rows;
				$cell_name->rowspan = $rows;
				foreach ($parameters as $p) {
					if ($new_row) $row = $table->add_row();
					else $new_row = true;
					$param_doc = "";
					$param_type = "";
					if (isset($tags["param"])) {
						foreach ($tags["param"] as $t) {
							$name = null;
							$type = null;
							$doc = null;
							$w = PHPDoc::getWord($t);
							if (substr($w,0,1) <> "$") {
								$type = $w;
								if (strlen($t) > 0) {
									$name = substr(PHPDoc::getWord($t),1);
									$doc = $t;
								}
							} else {
								$name = substr($w,1);
								$doc = $t;
							}
							if ($name == $p->getName()) {
								$param_doc = $doc;
								$param_type = $type;
								break;
							}
						}
					}
					$s = $p->getName();
					if ($param_type <> "")
						$s .= ": ".PHPDoc::type_link($param_type);
					$row->add_cell($s, "codedoc_parameter", 1, "vertical-align:top;");
					$row->add_cell($param_doc, "codedoc_comment");
				}
				if (isset($tags["return"])) {
					if ($new_row) $row = $table->add_row();
					else $new_row = true;
					$type = PHPDoc::getWord($tags["return"][0]);
					$row->add_cell("Returns: ".PHPDoc::type_link($type));
					$row->add_cell($tags["return"][0], "codedoc_comment");
				}
			}
			$table->generate();
			echo "<br/>";
		}
	}
	public static function getWord(&$s) {
		$i = strpos($s, " ");
		if ($i === FALSE) {
			$w = $s;
			$s = "";
		} else {
			$w = substr($s, 0, $i);
			$s = trim(substr($s,$i+1));
		}
		return $w;
	}
	
	public static function type_link($type) {
		if (is_string($type)) {
			if ($type == "string" || 
				$type == "boolean" || 
				$type == "null"
			)
				return "<span class='codedoc_class'>".$type."</span>";
			return "<span class='codedoc_class'><a href='".PHPDoc::get_link_to_type($type)."'>".$type."</a></span>";
		} else {
			return "<span class='codedoc_class'><a href='php?file=".urlencode($type->getFileName())."&class=".urlencode($type->getName())."'>".$type->getName()."</a></span>";
		}
	}
		
	public static function parse_comment($doc, &$general_comment, &$tags) {
		$i = strpos($doc, "/**");
		if ($i === FALSE) return;
		$doc = substr($doc, $i+2);
		$i = strpos($doc, "*/");
		if ($i !== FALSE) $doc = substr($doc, 0, $i);
		while (strlen($doc) > 0) {
			$i = strpos($doc, "\n");
			if ($i === FALSE) {
				$line = $doc;
				$doc = "";
			} else {
				$line = substr($doc, 0, $i);
				$doc = substr($doc, $i+1);
			}
			$line = trim($line);
			if (strlen($line) == 0) continue;
			if (substr($line, 0, 1) == "*") {
				$line = trim(substr($line, 1));
				if (strlen($line) == 0) continue;
			}
			if (substr($line, 0, 1) == "@") {
				$i = strpos($line, " ");
				if ($i === FALSE) {
					$tag = substr($line,1);
					$comment = "";
				} else {
					$tag = trim(substr($line, 1, $i-1));
					$comment = trim(substr($line, $i+1));
				}
				if (!isset($tags[$tag])) $tags[$tag] = array();
				PHPDoc::_handle_links($comment);
				array_push($tags[$tag], $comment);
			} else {
				$general_comment .= $line." ";
			}
		}
		PHPDoc::_handle_links($general_comment);
	}
	
	private static function _handle_links(&$comment) {
		$i = 0;
		while (($j = strpos($comment, "{@link", $i)) !== FALSE) {
			$k = strpos($comment, "}", $j);
			if ($k === FALSE) break;
			$link = substr($comment, $j+6, $k-$j-6);
			$link = trim($link);
			$replace = "<a href='".PHPDoc::get_link_to_type($link)."'><code class='codedoc_class'>".$link."</code></a>";
			$comment = substr($comment, 0, $j).$replace.substr($comment, $k+1);
			$i = $j+strlen($replace);
		}
	}
	
	public static function get_link_to_type($type) {
		if (substr($type, strlen($type)-2) == "[]") $type = substr($type,0,strlen($type)-2);
		$all = array_merge(get_declared_classes(), get_declared_interfaces());
		foreach ($all as $cl) {
			if ($cl == $type) {
				$c = new ReflectionClass($cl);
				return "php?file=".urlencode($c->getFileName())."&class=".urlencode($type);
			}
		}
		return "php_search?type=".urlencode($type);
	}
	
	public static function general_comment($doc) {
		$gc = "";
		$tags = array();
		PHPDoc::parse_comment($doc, $gc, $tags);
		return $gc;
	}

	/**
	 * 
	 * @param ReflectionMethod $m
	 */
	public static function get_original_method_declaration($m) {
		$c = $m->getDeclaringClass();
		$pc = $c->getParentClass();
		if ($pc <> null) {
			$pm = $pc->getMethod($m->getName());
			if ($pm <> null) return PHPDoc::get_original_method_declaration($pm);
		}
		foreach ($c->getInterfaces() as $i) {
			$im = $i->getMethod($m->getName());
			if ($im <> null) return $im;
		}
		return $m;
	}
	
}

class DocTable {

	public $rows = array();

	public function &add_row() {
		$row = new DocTableRow();
		array_push($this->rows, $row);
		return $row;
	}

	public function generate() {
		echo "<table class='codedoc_table'>";
		foreach ($this->rows as $row) {
			$row->generate();
		}
		echo "</table>";
	}

}

class DocTableRow {

	public $cells = array();

	public function &add_cell($content, $css_class = null, $colspan = 1, $style = null) {
		$cell = new DocTableCell();
		$cell->content = $content;
		if ($css_class <> null) $cell->css_class = $css_class;
		if ($colspan <> 1) $cell->colspan = $colspan;
		if ($style <> null) $cell->style = $style;
		array_push($this->cells, $cell);
		return $cell;
	}

	public function generate() {
		echo "<tr>";
		foreach ($this->cells as $cell)
			$cell->generate();
		echo "</tr>";
	}

}

class DocTableCell {

	public $css_class = "";
	public $style = "";
	public $content = "";
	public $colspan = 1;
	public $rowspan = 1;

	public function generate() {
		echo "<td";
		if ($this->css_class <> "") echo " class='".$this->css_class."'";
		if ($this->style <> "") echo " style='".$this->style."'";
		if ($this->colspan <> 1) echo " colspan=".$this->colspan;
		if ($this->rowspan <> 1) echo " rowspan=".$this->rowspan;
		echo ">";
		echo $this->content;
		echo "</td>";
	}

}
?>