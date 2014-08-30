<?php 
class documents extends Component {

	public function getPluginImplementations() {
		require_once("DocumentsStorage.inc");
		return array(
			new DocumentsStorage()
		);
	}
	
	public function getAttachedDocumentsPlugin($table, $type) {
		require_once("DocumentsPlugin.inc");
		foreach (PNApplication::$instance->components as $c)
			foreach ($c->getPluginImplementations() as $pi)
				if (($pi instanceof AttachedDocumentsPlugin) && $pi->getTable() == $table && $pi->getType() == $type)
					return $pi;
		return null;
	}
	
	public function insertDivForAttachedDocuments(&$page, $table, $sub_model, $key, $type, $div_size, $div_type) {
		$pi = $this->getAttachedDocumentsPlugin($table, $type);
		if ($pi == null) return;
		if (!$pi->canRead($key, $sub_model)) return;
		$documents = SQLQuery::create()->bypassSecurity()
			->select("AttachedDocument")
			->whereValue("AttachedDocument", "table", $table)
			->whereValue("AttachedDocument", "type", $type)
			->whereValue("AttachedDocument", "key", $key)
			->join("AttachedDocument","Document",array("document"=>"id"))
			->execute();
		$documents_versions = array();
		if (count($documents) > 0) {
			$ids = array();
			foreach ($documents as $doc) array_push($ids, $doc["document"]);
			$q = SQLQuery::create()->bypassSecurity()->select("DocumentVersion")->whereIn("DocumentVersion", "document", $ids);
			$q->orderBy("DocumentVersion","timestamp",false);
			PNApplication::$instance->storage->joinRevision($q, "DocumentVersion", "file", null);
			$versions = $q->execute();
			foreach ($documents as $doc) {
				$documents_versions[$doc["document"]] = array();
				foreach ($versions as $v)
					if ($v["document"] == $doc["document"]) array_push($documents_versions[$doc["document"]], $v);
			}
		}
		switch ($div_size) {
			case "small": $icon = "documents_16.png"; $font_size = "10pt"; break;
			case "medium": $icon = "documents_24.png"; $font_size = "14pt"; break;
		}
		theme::css($page,"documents.css");
		$page->requireJavascript("animation.js");
		$id = $page->generateID();
		echo "<div id='$id' style='font-size:$font_size;border:' class='documents $div_size $div_type'>";
		echo "<img src='/static/documents/$icon'/>";
		if ($div_type == "overview") {
			if (count($documents) == 0)
				echo "No document";
			else
				echo count($documents)." document".(count($documents)>1?"s":"");
		}
		echo "</div>";
		if ($div_type == "overview") {
			$details_id = $page->generateID();
			echo "<div id='$details_id' style='display:none;' class='documents_details'>";
			$table_id = $page->generateID();
			echo "<table class='documents_table'><tbody id='$table_id' elem_id='$id'>";
			echo "</tbody></table>";
			if ($pi->canAddAndRemove($key, $sub_model)) {
				echo "<button class='action green' onclick='window.top.pndocuments.attachFiles(event,\"$table\",".($sub_model <> null ? $sub_model : "null").",".json_encode($key, JSON_HEX_APOS).",\"$type\",document.getElementById(\"$table_id\"));'>Add files...</button>";
			}
			echo "</div>";
			?>
			<script type='text/javascript'>
			<?php
			foreach ($documents as $doc) {
				echo "window.top.pndocuments.addDocumentToTable(document.getElementById('$table_id'),".$doc["document"].",".json_encode($documents_versions[$doc["document"]][0]["mime"]).",".json_encode($doc["name"]).",".json_encode($documents_versions[$doc["document"]]).",".($pi->canWrite($key, $sub_model) ? "true" : "false").",".($pi->canAddAndRemove($key, $sub_model) ? "true" : "false").");\n";
			} 
			?>
			document.getElementById('<?php echo $id;?>').onclick = function() {
				if (this._anim1) animation.stop(this._anim1);
				if (this._anim2) animation.stop(this._anim2);
				this._anim1 = null;
				this._anim2 = null;
				var content = document.getElementById('<?php echo $details_id;?>');
				this._content = content;
				if (!this._opened) {
					content.style.width = "";
					content.style.height = "";
					content.style.position = "absolute";
					content.style.display = "block";
					content.style.overflow = "";
					if (getWidth(content) < getWidth(this)) setWidth(content, getWidth(this));
					var h = content.offsetHeight;
					content.style.height = "0px";
					content.style.overflow = "hidden";
					content.style.top = (absoluteTop(this)+this.offsetHeight-1)+"px";
					content.style.left = (absoluteLeft(this)+this.offsetWidth-getWidth(content))+"px";
					this._anim1 = animation.create(content,0,h,500,function(value,element){
						element.style.height = Math.floor(value)+"px";
					});
					this._anim2 = animation.fadeIn(content,500);
					this._opened = true;
				} else {
					this._anim1 = animation.create(content,getHeight(content),0,500,function(value,element){
						element.style.height = Math.floor(value)+"px";
					});
					this._anim2 = animation.fadeOut(content,500);
					this._opened = false;
				}
			};
			document.getElementById('<?php echo $id;?>').resize = function() {
				var content = this._content;
				content.style.width = "";
				content.style.height = "";
				if (getWidth(content) < getWidth(this)) setWidth(content, getWidth(this));
				content.style.top = (absoluteTop(this)+this.offsetHeight-1)+"px";
				content.style.left = (absoluteLeft(this)+this.offsetWidth-getWidth(content))+"px";
			};
			</script>
			<?php 
		}
	}
	
}
?>