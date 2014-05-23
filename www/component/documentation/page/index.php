<?php 
class page_index extends Page {
	
	public function getRequiredRights() { return array(); }
	
	public function execute() {
		echo "<div style='width:100%;height:100%' id='doc_top_container'>";
			echo "<div style='background-color:#C0C0C0' layout='25'>";
			echo "PN Students Database - Technical Documentation";
			echo " <button onclick='location.reload();'><img src='".theme::$icons_16["refresh"]."'/></button>";
			echo "</div>";
			echo "<div layout='fill'>";
				echo "<div style='width:100%;height:100%' id='doc_container'>";
					echo "<iframe src='navigation' style='border:none;width:100%;height:100%' frameBorder=0 name='navigation'></iframe>";
					echo "<iframe src='home' style='border:none;width:100%;height:100%' frameBorder=0 name='documentation'></iframe>";
				echo "</div>";
			echo "</div>";
		echo "</div>";
		$this->requireJavascript("vertical_layout.js");
		$this->onload("new vertical_layout('doc_top_container');");
		$this->requireJavascript("splitter_vertical.js");
		$this->onload("new splitter_vertical('doc_container',0.2);");
		?>
		<script type='text/javascript'>
		var w = window;
		w.jsdoc = null;
		w.jsdoc_handlers = [];
		w.jsdoc_loading = false;
		function init_jsdoc(handler) {
			if (w.jsdoc) { if (handler) handler(); return; }
			if (w.jsdoc_loading) { if (handler) w.jsdoc_handlers.push(handler); return; }
			w.jsdoc_loading = true;
			if (handler) w.jsdoc_handlers.push(handler);
			require("jsdoc.js");
			service.json("documentation","get_js",{},function(res){
				require("jsdoc.js",function() {
					var fct;
					try {
						fct = eval("(function (){"+res.js+";this.jsdoc = jsdoc;})");
						var doc = new fct();
						w.jsdoc = doc.jsdoc;
					} catch (e) {
						w.top.status_manager.add_status(new window.top.StatusMessageError(e,"Invalid output for get_js:"+res.js,10000));
					}
					for (var i = 0; i < w.jsdoc_handlers.length; ++i)
						w.jsdoc_handlers[i]();
				});
			});
		}
		init_jsdoc();
		</script>
		<?php 
	}
	
}
?>