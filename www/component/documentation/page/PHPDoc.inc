<?php 
class PHPDoc {
	
	public static function generate_doc($files) {
		foreach ($files as $file) require_once($file);
		for ($i = 0; $i < count($files); $i++)
			$files[$i] = realpath($files[$i]);
		$existing = get_declared_classes();
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
	
		if (count($cl->getProperties()) > 0) {
			$table = new DocTable();
			$row = $table->add_row();
			$row->add_cell("Properties", "codedoc_table_title", 4);
			$row = $table->add_row();
			$row->add_cell("Modifiers", "codedoc_table_title2");
			$row->add_cell("Name", "codedoc_table_title2");
			$row->add_cell("Type", "codedoc_table_title2");
			$row->add_cell("Description", "codedoc_table_title2");
			foreach ($cl->getProperties() as $p) {
				if (!$p->isPublic()) continue;
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
					$type = $tags["var"][0];
					// TODO link?
				}
				$row->add_cell($type, "codedoc_class");
				// description
				$row->add_cell($comment);
			}
			$row = $table->add_row();
			$table->generate();
		}
		if (count($cl->getMethods()) > 0) {
			$table = new DocTable();
			$row = $table->add_row();
			$row->add_cell("Methods", "codedoc_table_title", 4);
			$row = $table->add_row();
			$row->add_cell("Modifiers", "codedoc_table_title2");
			$row->add_cell("Name", "codedoc_table_title2");
			$row->add_cell("Parameters", "codedoc_table_title2");
			$row->add_cell("Description", "codedoc_table_title2");
			foreach ($cl->getMethods() as $m) {
				if (!$m->isPublic()) continue;
				$row = $table->add_row();
				// modifiers
				$modifiers = "";
				foreach (Reflection::getModifierNames($m->getModifiers()) as $mod)
					$modifiers .= $mod." ";
				$row->add_cell($modifiers, "codedoc_modifiers");
				// name
				$row->add_cell($m->name, "codedoc_method");
				// parameters
				$comment = "";
				$tags = array();
				PHPDoc::parse_comment($m->getDocComment(), $comment, $tags);
				$params = "";
				foreach ($m->getParameters() as $p) {
					$params .= "<span class='codedoc_parameter'>".$p->getName()."</span>";
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
					if ($param_type <> "")
						$params .= ": <span class='codedoc_class'>".$param_type."</span>";
					if ($param_doc <> null)
						$params .= "<div style='margin-left:15px;background-color:#FFFFD0'>".$param_doc."</div>";
					else
						$params .= "<br/>";
				}
				if (isset($tags["return"])) {
					$params .= "=&gt; Returns ";
					$type = PHPDoc::getWord($tags["return"][0]);
					$params .= "<span class='codedoc_class'>".$type."</span>";
					if (strlen($tags["return"][0]) > 0)
						$params .= "<div style='margin-left:15px;background-color:#FFFFD0'>".$param_doc."</div>";
				}
				$row->add_cell($params);
				// description
				$row->add_cell($comment);
			}
			$table->generate();
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
				array_push($tags[$tag], $comment);
			} else {
				$general_comment .= $line." ";
			}
		}
	}
	
	public static function general_comment($doc) {
		$gc = "";
		$tags = array();
		PHPDoc::parse_comment($doc, $gc, $tags);
		return $gc;
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

	public function generate() {
		echo "<td";
		if ($this->css_class <> "") echo " class='".$this->css_class."'";
		if ($this->style <> "") echo " style='".$this->style."'";
		if ($this->colspan <> 1) echo " colspan=".$this->colspan;
		echo ">";
		echo $this->content;
		echo "</td>";
	}

}
?>