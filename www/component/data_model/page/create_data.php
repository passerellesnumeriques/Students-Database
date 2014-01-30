<?php 
class page_create_data extends Page {
	
	public function get_required_rights() { return array(); }
	
	public function execute() {
		$input = json_decode($_POST["input"], true);
		$root_table = $input["root_table"];
		$sub_model = @$input["sub_model"];
		require_once("component/data_model/Model.inc");
		$root_table = DataModel::get()->getTable($root_table);
		$to_create = array();
		$root_create = $this->create($root_table, $sub_model, $to_create, null, false, null);
		
		$this->add_javascript("/static/widgets/vertical_layout.js");
		$this->onload("new vertical_layout('wizard_page');");
		
		echo "<div id='wizard_page' style='width:100%;height:100%'>";
			echo "<div id='wizard_page_header' layout='fixed' class='wizard_header' style='padding-top:3px'>";
				echo "<img src='".$input["icon"]."' style='vertical-align:bottom;padding-left:5px;padding-right:5px'/>";
				echo "<span id='wizard_page_title'>".htmlentities($input["title"])."</span>";
			echo "</div>";
			echo "<div id='wizard_page_content' layout='fill' style='overflow:auto;padding:5px;'>";
			
		/*
		echo "To create:<ul>";
		foreach ($to_create as $tc) {
			echo "<li>";
			$this->debug($tc);
			echo "</li>";
		}
		echo "</ul>";
		*/

		echo $this->build_page($root_create, $to_create, false, true);
		
		if (count($to_create) > 0) {
			echo "Remaining:<ul style='color:red'>";
				foreach ($to_create as $tc) {
					echo "<li>";
					$this->debug($tc);
					echo "</li>";
				}
			echo "</ul>";
		}
			echo "</div>";
			echo "<div id='wizard_page_footer' layout='fixed' class='wizard_buttons'>";
				echo "<button id='wizard_page_button_previous' disabled='disabled' onclick='wizard_page_previous();'>";
					echo "<img src='".theme::$icons_16["ok"]."' style='vertical-align:bottom'/> Create";
				echo "</button>";
				echo "<button id='wizard_page_button_finish' disabled='disabled' onclick='wizard_page_finish();'>";
					echo "<img src='".theme::$icons_16["cancel"]."' style='vertical-align:bottom'/> Cancel";
				echo "</button>";
			echo "</div>";
		echo "</div>"; 
	}
	
	/** Debug information
	 * @param CreateRow $tc row to debug about
	 */
	private function debug($tc) {
		echo "Create row in table '".$tc->table->getName()."'";
		echo ", sub model '".$tc->sub_model."'";
		echo ", ".($tc->optional ? "optional" : "mandatory");
		if ($tc->for <> null) echo " for ".$tc->for->table->getName();
		echo ":<ul>";
		foreach ($tc->fields as $col_name=>$value) {
			echo "<li>";
			echo "<code>".$col_name."</code> = ";
			if ($value instanceof CreateRow)
				echo "row from ".$value->table->getName();
			else if ($value instanceof datamodel\DataDisplay)
				echo "from data '".$value->handler->category.": ".$value->getDisplayName()."'";
			else
				echo "TODO";
			echo "</li>";
		}
		
		$missing = array();
		foreach ($tc->table->getColumnsFor($tc->sub_model) as $col) {
			if ($col instanceof datamodel\PrimaryKey) continue;
			if (!isset($tc->fields[$col->name])) array_push($missing, $col->name);
		}
		foreach ($missing as $col_name)
			echo "<li style='color:red'><code>$col_name</code> = <code>null</code></li>";
		
		if (count($tc->data) > 0) {
			echo "<li>Data:<ul>";
			foreach ($tc->data as $data) {
				echo "<li>";
				echo "Data '".$data->handler->category.": ".$data->getDisplayName()."': ";
				$first = true;
				foreach ($data->getHandledColumns() as $col_name) {
					if ($first) $first = false; else echo ", ";
					echo "<code>$col_name</code>";
				}
				echo "</li>";
			}
			echo "</ul>";
		}
		echo "</ul>";
	}
	
	/**
	 * @param datamodel\Table $table
	 * @param unknown $sub_model
	 * @param array $to_create
	 */
	private function &create($table, $sub_model, &$to_create, $for, $optional, $from) {
		$create = new CreateRow($table, $sub_model, $for, $optional, $from);
		array_push($to_create, $create);
		// 1- check foreign keys that are mandatory and must be done before
		foreach ($table->getColumnsFor($sub_model) as $col) {
			if (!($col instanceof datamodel\ForeignKey)) continue;
			if ($col->can_be_null) continue;
			if ($col->multiple) continue; // we have to choose an existing one
			// check if we have already one created
			$found = false;
			foreach ($to_create as &$tc) {
				if ($tc->table->getName() == $col->foreign_table) {
					$found = true;
					$create->fields[$col->name] = &$tc;
					break;
				}
				unset($tc);
			}
			if ($found) continue;
			$foreign_table = DataModel::get()->getTable($col->foreign_table);
			$sm = $sub_model <> null && $foreign_table->getModel() instanceof SubDataModel && $foreign_table->getModel->getParentTable() == $table->getModel()->getParentTable() ? $sub_model : null;
			$tc = $this->create($foreign_table, $sm, $to_create, $create, false, null);
			$create->fields[$col->name] = &$tc;
			unset($tc);
		}
		// 2- create data according to the display handler
		$display = $table->getDisplayHandler($from);
		if ($display <> null) {
			foreach ($display->getDisplayableData() as $data) {
				foreach ($data->getHandledColumns() as $col_name)
					$create->fields[$col_name] = $data;
				array_push($create->data, $data);
			}
		}
		// 3- create remaining optional foreign keys
		foreach ($table->getColumnsFor($sub_model) as $col) {
			if (!($col instanceof datamodel\ForeignKey)) continue;
			if (!$col->can_be_null) continue; // already done in the mandatory
			if ($col->multiple) continue; // we have to choose an existing one
			// check if we have already one created
			$found = false;
			foreach ($to_create as &$tc) {
				if ($tc->table->getName() == $col->foreign_table) {
					$found = true;
					$create->fields[$col->name] = &$tc;
					break;
				}
				unset($tc);
			}
			if ($found) continue;
			try { $foreign_table = DataModel::get()->getTable($col->foreign_table); }
			catch (Exception $ex) {
				continue;
			}
			$sm = $sub_model <> null && $foreign_table->getModel() instanceof SubDataModel && $foreign_table->getModel->getParentTable() == $table->getModel()->getParentTable() ? $sub_model : null;
			$this->create($foreign_table, $sm, $to_create, null, true, null);
		}
		
		// 3- check if we have foreign keys in other tables
		foreach (DataModel::get()->getTables() as $t) {
			$sm = null;
			if ($t->getModel() instanceof SubDataModel) {
				if ($sub_model == null) continue; // we are not in a sub model
				if ($t->getModel()->getParentTable() <> $table->getModel()->getParentTable()) continue; // different sub models
				$sm = $sub_model;
			}
			foreach ($t->getColumnsFor($sm) as $col) {
				if (!($col instanceof datamodel\ForeignKey)) continue;
				if ($col->foreign_table <> $table->getName()) continue;
				//if (!$col->multiple) continue; // not related to us
				// check we don't already have one created
				$found = false;
				foreach ($to_create as &$tc)
					if ($tc->table == $t) { $found = true; break; }
				if ($found) continue;
				$this->create($t, $sm, $to_create, $create, true, $col->name);
			}
		}
		
		return $create;
	}
	
	private function build_page($create, &$to_create, $optional, $straight) {
		$html = "";
		// remove from to_create
		for ($i = 0; $i < count($to_create); $i++)
			if ($to_create[$i] == $create) { array_splice($to_create, $i, 1); break; }
		
		// take dependencies
		$mandatory = array();
		$optionals = array();
		foreach ($to_create as $tc) {
			if ($tc->for <> $create) continue;
			if ($tc->optional)
				array_push($optionals, $tc);
			else
				array_push($mandatory, $tc);
		}
		
		$content = "";
		// first, all mandatory
		foreach ($mandatory as $tc)
			$content .= $this->build_page($tc, $to_create, false, $straight);
		
		// then, optionals
		foreach ($optionals as $tc)
			$content .= $this->build_page($tc, $to_create, true, false);
		
		
		// the table
		$display = $create->table->getDisplayHandler($create->from);
		if ($display <> null && count($display->getDisplayableData()) == 0) $display = null;
		if ($display <> null || ($optional && $content <> "")) {
			$id = $this->generateID();
			$html .= "<div id='$id' style='border:1px solid #C0C0FF;display:inline-block;margin:5px;vertical-align:top'>";
			$this->onload("setBorderRadius(document.getElementById('$id'),5,5,5,5,5,5,5,5);");
			$id = $this->generateID();
			$html .= "<div style='background-color:#C0C0FF;padding:2px;' id='$id'>";
			$this->onload("setBorderRadius(document.getElementById('$id'),5,5,5,5,0,0,0,0);");
			$hidder_id = $this->generateID();
			$content_id = $this->generateID();
			if ($optional) {
				$html .= "<input type='checkbox' onchange=\"var e = document.getElementById('$hidder_id'); e.style.visibility = this.checked ? 'hidden' : 'visible'; e.style.zIndex = this.checked ? 1 : 3;\"/> ";
			}
			if ($display <> null)
				$html .= htmlentities($display->display_name);
			$html .= "</div>";
			$html .= "<div style='position:relative;z-index:2'>";
			if ($optional) {
				$html .= "<div id='$hidder_id' style='position:absolute;z-index:3;background-color:rgba(0,0,0,0.3);width:100%;height:100%;'></div>";
			}
			$html .= "<div style='padding:5px;' id='$content_id'>";
			if ($display <> null) {
				$html .= "<table style='display:inline-block;'>";
				foreach ($display->getDisplayableData() as $data) {
					$html .= "<tr>";
					$html .= "<td>".htmlentities($data->getDisplayName())."</td>";
					$id = $this->generateID();
					$html .= "<td id='$id'></td>";
					$tf = $data->getTypedField($create->sub_model);
					$this->add_javascript("/static/widgets/typed_field/typed_field.js");
					$this->require_javascript($tf[0].".js");
					$this->onload("document.getElementById('$id').appendChild(new ".$tf[0]."(".json_encode($data->getNewData()).",true,".$tf[1].").getHTMLElement());");
				}
				$html .= "</table>";
			}
		}
		
		if (!$straight)
			$html .= $content;

		if ($display <> null || ($optional && $content <> "")) {
			$html .= "</div>";
			$html .= "</div>";
			$html .= "</div>";
		}
		
		if ($straight)
			$html .= $content;
		
		return $html;
	}
}

class CreateRow {
	
	/** @var datamodel\Table */
	public $table;
	public $sub_model;
	public $fields = array();
	/** @var datamodel\DataDisplay */
	public $data = array();
	/** @var CreateRow */
	public $for;
	/** @var boolean */
	public $optional;
	public $from;
	
	/**
	 * @param datamodel\Table $table
	 * @param unknown $sub_model
	 * @param CreateRow $for
	 * @param boolean $optional
	 */
	public function __construct($table, $sub_model, $for, $optional, $from) {
		$this->table = $table;
		$this->sub_model = $sub_model;
		$this->for = $for;
		$this->optional = $optional;
		$this->from = $from;
	}
	
}

?>