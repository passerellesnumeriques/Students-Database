<?php 
class page_print extends Page {
	
	public function getRequiredRights() { return array(); }
	
	public function execute() {
		theme::css($this, "header_bar.css");
?>
<div style='width:100%;height:100%;display:flex;flex-direction:column;'>
	<div id='header' class='header_bar_toolbar_style' style='padding:2px 5px 2px 5px;height:25px;flex:none;'>
	<!-- Paper Size: 
	<select id='paper_size' onchange='refreshSize();'>
		<option value='1' selected='selected'>A4 (210 x 297 mm)</option>
		<option value='2'>Letter (8.5 x 11 in)</option>
		<option value='3'>Folio (8.5 x 13 in)</option>
		<option value='4'>Legal (8.5 x 14 in)</option>
	</select> -->
	Orientation: 
	<select id='orientation' onchange='refreshSize();'>
		<option value='1' selected='selected'>Portrait</option>
		<option value='2'>Landscape</option>
	</select>
	<button class='action' onclick="getIFrameWindow(document.getElementById('print_content')).print();"><img src='<?php echo theme::$icons_16["print"];?>'/> Print</button>
	</div>
	<div id='content' style='flex:1 1 auto;overflow:hidden;text-align:center;display:flex;flex-direction:row;justify-content:center;'>
		<iframe name='print_content' id='print_content' style='border:none;flex:0 1 auto' src='/dynamic/application/page/blank'></iframe>
	</div>
</div>
<script type='text/javascript'>
window.printing_ready = false;

function refreshSize() {
	//var paper = document.getElementById('paper_size').value;
	paper = 1;
	var ori = document.getElementById('orientation').value;
	var size;
	switch (parseInt(paper)) {
	case 1: size = [595,841]; break;
	case 2: size = [612,792]; break;
	case 3: size = [612,936]; break;
	case 4: size = [612,1008]; break;
	default: alert('Unknown paper size '+paper);
	}
	// apply margins
	size[0] -= 55;
	size[1] -= 55;
	if (parseInt(ori) == 2) {
		var tmp = size[0];
		size[0] = size[1];
		size[1] = tmp;
	}
	var frame = document.getElementById('print_content');
	frame.style.width = size[0]+"pt";
	//frame.style.height = size[1]+"pt";
	//frame.style.height = "100%";
}

waitFrameContentReady(document.getElementById('print_content'), function(win) { return win._page_ready; }, function(win) {
	win.document.body.style.backgroundColor = 'white';
	refreshSize();
	window.printing_ready = true;
});

window.setPrintContent = function(container) {
	var win = getIFrameWindow(document.getElementById('print_content'));
	win.document.body.innerHTML = container.innerHTML;
	win.document.body.style.backgroundColor = "white";
	var container_win = getWindowFromElement(container);
	var container_head = container_win.document.getElementsByTagName("HEAD")[0];
	var head = win.document.getElementsByTagName("HEAD")[0];
	for (var i = 0; i < container_head.childNodes.length; ++i) {
		if (container_head.childNodes[i].nodeName == "LINK") {
			var cl = container_head.childNodes[i];
			var link = document.createElement("LINK");
			link.rel = cl.rel;
			link.href = cl.href;
			link.type = cl.type;
			head.appendChild(link);
		}
	}
	var nodes = win.document.getElementsByTagName("STYLE");
	for (var i = 0; i < nodes.length; ++i) {
		var node = nodes.item(i);
		if (node.parentNode == head) continue;
		node.parentNode.removeChild(node);
		var n = document.createElement("STYLE");
		n.type = "text/css";
		n.appendChild(document.createTextNode(node.textContent));
		head.appendChild(n);
	}
	nodes = win.document.getElementsByTagName("SCRIPT");
	for (var i = 0; i < nodes.length; ++i) {
		var node = nodes.item(i);
		if (node.parentNode == head) continue;
		node.parentNode.removeChild(node);
		var n = document.createElement("SCRIPT");
		n.type = "text/javascript";
		n.textContent = node.textContent;
		head.appendChild(n);
	}
};
</script>
<?php 
	}
	
}
?>