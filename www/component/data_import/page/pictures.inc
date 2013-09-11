<?php 
function pictures_import($key, $max_width, $max_height, $min_width, $min_height, $final_callback) {
	$imports = isset($_SESSION["data_import__pictures"]) ? $_SESSION["data_import__pictures"] : null;
	if ($imports == null)
		$imports = array();
	if (isset($_GET["reset"]) && $_GET["reset"] == "1") {
		if (isset($imports[$key]))
			unset($imports[$key]);
		$_SESSION["data_import__pictures"] = $imports;
		return;
	}
	if (isset($imports[$key]))
		$import = &$imports[$key]; 
	else {
		$import = array("ids"=>array(),"names"=>array(),"types"=>array(),"sizes"=>array());
		$imports[$key] = &$import;
	}

	PNApplication::$instance->storage->set_expires($import["ids"], time()+5*60);
	if (!PNApplication::$instance->storage->receive_upload($import["ids"], $import["names"], $import["types"], $import["sizes"], 5*60)) {
		// this is an asynchronous send
		$_SESSION["data_import__pictures"] = $imports;
		return;
	}
	// no more files to receive
	
	// create images
	$images = array();
	for ($i = 0; $i < count($import["ids"]); $i++) {
		$data = PNApplication::$instance->storage->get_data($import["ids"][$i]);
		$img = imagecreatefromstring($data);
		array_push($images, $img);
	}
	
	// execute actions if any
	$actions = false;
	for ($i = 0; $i < count($import["ids"]); $i++) {
		if ($images[$i] === false) continue;
		$changed = false;
		if (isset($_POST["adjust_x_".$import["ids"][$i]])) {
			// adjust size
			$x = intval($_POST["adjust_x_".$import["ids"][$i]]);
			$y = intval($_POST["adjust_y_".$import["ids"][$i]]);
			$w = intval($_POST["adjust_w_".$import["ids"][$i]]);
			$h = intval($_POST["adjust_h_".$import["ids"][$i]]);
			$new_img = imagecreatetruecolor($w, $h);
			imagecopy($new_img, $images[$i], 0, 0, $x, $y, $w, $h);
			imagedestroy($images[$i]);
			$images[$i] = $new_img;
			if ($w < $min_width || $w > $max_width || $h < $min_height || $h > $max_height) {
				// need automatic resize
				if ($w < $min_width) $w = $min_width; else if ($w > $max_width) $w = $max_width;
				if ($h < $min_height) $h = $min_height; else if ($h > $max_height) $h = $max_height;
				$new_img = imagecreatetruecolor($w, $h);
				imagecopyresized($new_img, $images[$i], 0, 0, 0, 0, $w, $h, imagesx($images[$i]), imagesy($images[$i]));
				imagedestroy($images[$i]);
				$images[$i] = $new_img;
			}
			$changed = true;
		}
		if ($changed) {
			$actions = true;
			// save new image
			$path = PNApplication::$instance->storage->get_data_path($import["ids"][$i]);
			imagejpeg($images[$i], $path);
		}
	}
	
	if (!$actions && isset($_POST["picture_edit"])) {
		// here, no actions are asked, and it comes from the form, so this is the final validation
		foreach ($images as $img)
			if ($img <> false) imagedestroy($img);
		$final_callback($import["ids"]);
		return;
	}
		
	// display table of pictures with actions
	echo "<table style='border:1px solid black' rules='all'>";
	echo "<tr><th>File</th><th>Picture</th><th>Information</th></tr>";
	$auto_adjust = array();
	for ($i = 0; $i < count($import["ids"]); $i++) {
		$img = &$images[$i];
		$error = null;
		if ($img === false)
			$error = "Invalid picture";
		else {
			$x = imagesx($img);
			$y = imagesy($img);
			if ($x > $max_width || $y > $max_height)
				$error = "Picture is too large";
		}
		echo "<tr".($error <> null ? " style='background-color:#FFD0D0'" : "").">";
		echo "<td>".$import["names"][$i]."</td>";
		echo "<td>";
		if ($img) {
			echo "<img id='picture_".$import["ids"][$i]."' src='/dynamic/storage/service/get_temp?id=".$import["ids"][$i]."'";
			if ($x > $y) {
				if ($x > 300) echo " width='300px'";
			} else if ($y > 300) echo " height='300px'";
			echo "/>";
		}
		echo "</td>";
		echo "<td>";
		if ($error <> null)
			echo "<img src='".theme::$icons_16["error"]."' style='vertical-align:bottom' onload='this.complete=true;'/> <span style='color:red'>".$error."</span><br/>";
		if ($img) {
			echo "Current dimension: ".$x." x ".$y;
			echo ", minimum: ".$min_width." x ".$min_height;
			echo ", maximum: ".$max_width." x ".$max_height."<br/>";
			echo "<a href='#' id='adjust_".$import["ids"][$i]."' onclick=\"adjust_size(".$import["ids"][$i].",".$x.",".$y.",".$max_width.",".$max_height.");return false\">Adjust size</a>";
			if ($x > $max_width || $y > $max_height)
				array_push($auto_adjust, $import["ids"][$i]);
		}
		echo "</td>";
		echo "</tr>";
	}
	echo "</table>";
	
	// destroy images
	foreach ($images as $img)
		if ($img <> false) imagedestroy($img);
?>
<form method="POST" name="pictures_changes">
<input type='hidden' name='picture_edit' value='1'/>
<button type="submit">Ok</button>
</form>
<script type='text/javascript'>
function adjust_size(id,width,height,crop_width,crop_height) {
	var link = document.getElementById('adjust_'+id);
	var span = document.createElement("SPAN");
	span.innerHTML = "Selected dimension: ";
	var span_dim = document.createElement("SPAN");
	link.parentNode.insertBefore(span, link);
	link.parentNode.insertBefore(span_dim, link);
	link.parentNode.insertBefore(document.createElement("BR"), link);
	span = document.createElement("SPAN");
	span.style.fontStyle = "italic";
	span.innerHTML = "Note: if the new dimension is too large, or too small, the picture will be automatically resized";
	link.parentNode.insertBefore(span, link);
	link.parentNode.removeChild(link);
	var form = document.forms['pictures_changes'];
	var adjust_x = document.createElement("INPUT"); adjust_x.type = "hidden"; adjust_x.name = "adjust_x_"+id; form.appendChild(adjust_x);
	var adjust_y = document.createElement("INPUT"); adjust_y.type = "hidden"; adjust_y.name = "adjust_y_"+id; form.appendChild(adjust_y);
	var adjust_w = document.createElement("INPUT"); adjust_w.type = "hidden"; adjust_w.name = "adjust_w_"+id; form.appendChild(adjust_w);
	var adjust_h = document.createElement("INPUT"); adjust_h.type = "hidden"; adjust_h.name = "adjust_h_"+id; form.appendChild(adjust_h);
	var img = document.getElementById('picture_'+id);
	var div = document.createElement("DIV");
	div.style.border = "2px solid #0000FF";
	div.style.position = "absolute";
	var w = Math.floor(crop_width*img.offsetWidth/width);
	var h = Math.floor(crop_height*img.offsetHeight/height);
	div.style.width = w+"px";
	div.style.height = h+"px";
	div.style.left = Math.floor(absoluteLeft(img)+img.offsetWidth/2-crop_width*img.offsetWidth/width/2)+"px";
	div.style.top = Math.floor(absoluteTop(img)+img.offsetHeight/2-crop_height*img.offsetHeight/height/2)+"px";
	var refresh_dim = function() {
		var real_w = w*width/img.offsetWidth;
		var real_h = h*height/img.offsetHeight;
		span_dim.innerHTML = Math.floor(real_w)+" x "+Math.floor(real_h);
		adjust_w.value = Math.floor(real_w);
		adjust_h.value = Math.floor(real_h);
		adjust_x.value = Math.floor((absoluteLeft(div)-absoluteLeft(img))*width/img.offsetWidth);
		adjust_y.value = Math.floor((absoluteTop(div)-absoluteTop(img))*height/img.offsetHeight);
	};
	refresh_dim();
	var content = document.createElement("DIV");
	content.style.width = "100%";
	content.style.height = "100%";
	content.style.position = "relative";
	div.appendChild(content);
	var tl = document.createElement("DIV");
	tl.style.position = "absolute";
	tl.style.top = "-5px";
	tl.style.left = "-5px";
	tl.style.backgroundColor = "#404000";
	tl.style.width = "5px";
	tl.style.height = "5px";
	content.appendChild(tl);
	var tr = document.createElement("DIV");
	tr.style.position = "absolute";
	tr.style.top = "-5px";
	tr.style.right = "-5px";
	tr.style.backgroundColor = "#404000";
	tr.style.width = "5px";
	tr.style.height = "5px";
	content.appendChild(tr);
	var bl = document.createElement("DIV");
	bl.style.position = "absolute";
	bl.style.bottom = "-5px";
	bl.style.left = "-5px";
	bl.style.backgroundColor = "#404000";
	bl.style.width = "5px";
	bl.style.height = "5px";
	content.appendChild(bl);
	var br = document.createElement("DIV");
	br.style.position = "absolute";
	br.style.bottom = "-5px";
	br.style.right = "-5px";
	br.style.backgroundColor = "#404000";
	br.style.width = "5px";
	br.style.height = "5px";
	content.appendChild(br);
	document.body.appendChild(div);
	div.start_move = function(e) {
		div.style.borderColor = "#8080FF";
		var ev = getCompatibleMouseEvent(e);
		div.lastX = ev.x;
		div.lastY = ev.y;
	};
	div.moving = function(e) {
		var ev = getCompatibleMouseEvent(e);
		var dx = ev.x-div.lastX; 
		var dy = ev.y-div.lastY; 
		div.lastX = ev.x;
		div.lastY = ev.y;
		var x = absoluteLeft(div)+dx;
		var ix = absoluteLeft(img);
		if (x < ix) x = ix; else if (x+div.offsetWidth>ix+img.offsetWidth) x = ix+img.offsetWidth-div.offsetWidth;
		var y = absoluteTop(div)+dy;
		var iy = absoluteTop(img);
		if (y < iy) y = iy; else if (y+div.offsetHeight>iy+img.offsetHeight) y = iy+img.offsetHeight-div.offsetHeight;
		div.style.top = y+"px";
		div.style.left = x+"px";
	};
	div.stop_moving = function(e) {
		div.style.borderColor = "#0000FF";
	};
	tl.start_move = function(e) {
		tl.style.backgroundColor = "#FFFF00";
		var ev = getCompatibleMouseEvent(e);
		tl.lastX = ev.x;
		tl.lastY = ev.y;
	};
	tr.start_move = function(e) {
		tr.style.backgroundColor = "#FFFF00";
		var ev = getCompatibleMouseEvent(e);
		tr.lastX = ev.x;
		tr.lastY = ev.y;
	};
	bl.start_move = function(e) {
		bl.style.backgroundColor = "#FFFF00";
		var ev = getCompatibleMouseEvent(e);
		bl.lastX = ev.x;
		bl.lastY = ev.y;
	};
	br.start_move = function(e) {
		br.style.backgroundColor = "#FFFF00";
		var ev = getCompatibleMouseEvent(e);
		br.lastX = ev.x;
		br.lastY = ev.y;
	};
	tl.moving = function(e) {
		var ev = getCompatibleMouseEvent(e);
		var dx = ev.x-tl.lastX; 
		var dy = ev.y-tl.lastY; 
		tl.lastX = ev.x;
		tl.lastY = ev.y;

		var x = absoluteLeft(div)+dx;
		var ix = absoluteLeft(img);
		if (x < ix) { dx = ix-absoluteLeft(div); x = ix; }
		w -= dx;
		if (w <= 0) { dx -= w; dx--; x = absoluteLeft(div)+dx; w = 1; }

		var y = absoluteTop(div)+dy;
		var iy = absoluteTop(img);
		if (y < iy) { dy = iy-absoluteTop(div); y = iy; }
		h -= dy;
		if (h <= 0) { dy -= h; dy--; y = absoluteTop(div)+dy; h = 1; }
		
		div.style.top = y+"px";
		div.style.left = x+"px";
		div.style.width = w+"px";
		div.style.height = h+"px";
		refresh_dim();
	};
	tr.moving = function(e) {
		var ev = getCompatibleMouseEvent(e);
		var dx = ev.x-tr.lastX; 
		var dy = ev.y-tr.lastY; 
		tr.lastX = ev.x;
		tr.lastY = ev.y;

		w += dx;
		if (w <= 0) w = 1; else if (absoluteLeft(div)+w > absoluteLeft(img)+img.offsetWidth-4) w = absoluteLeft(img)+img.offsetWidth-absoluteLeft(div)-4;  

		var y = absoluteTop(div)+dy;
		var iy = absoluteTop(img);
		if (y < iy) { dy = iy-absoluteTop(div); y = iy; }
		h -= dy;
		if (h <= 0) { dy -= h; dy--; y = absoluteTop(div)+dy; h = 1; }
		
		div.style.top = y+"px";
		div.style.width = w+"px";
		div.style.height = h+"px";
		refresh_dim();
	};
	bl.moving = function(e) {
		var ev = getCompatibleMouseEvent(e);
		var dx = ev.x-bl.lastX; 
		var dy = ev.y-bl.lastY; 
		bl.lastX = ev.x;
		bl.lastY = ev.y;

		var x = absoluteLeft(div)+dx;
		var ix = absoluteLeft(img);
		if (x < ix) { dx = ix-absoluteLeft(div); x = ix; }
		w -= dx;
		if (w <= 0) { dx -= w; dx--; x = absoluteLeft(div)+dx; w = 1; }

		h += dy;
		if (h <= 0) h = 1; else if (absoluteTop(div)+h > absoluteTop(img)+img.offsetHeight-4) h = absoluteTop(img)+img.offsetHeight-absoluteTop(div)-4;  

		div.style.left = x+"px";
		div.style.width = w+"px";
		div.style.height = h+"px";
		refresh_dim();
	};
	br.moving = function(e) {
		var ev = getCompatibleMouseEvent(e);
		var dx = ev.x-br.lastX; 
		var dy = ev.y-br.lastY; 
		br.lastX = ev.x;
		br.lastY = ev.y;

		w += dx;
		if (w <= 0) w = 1; else if (absoluteLeft(div)+w > absoluteLeft(img)+img.offsetWidth-4) w = absoluteLeft(img)+img.offsetWidth-absoluteLeft(div)-4;  
		
		h += dy;
		if (h <= 0) h = 1; else if (absoluteTop(div)+h > absoluteTop(img)+img.offsetHeight-4) h = absoluteTop(img)+img.offsetHeight-absoluteTop(div)-4;  

		div.style.width = w+"px";
		div.style.height = h+"px";
		refresh_dim();
	};
	tl.stop_moving = function(e) {
		tl.style.backgroundColor = "#404000";
	};
	tr.stop_moving = function(e) {
		tr.style.backgroundColor = "#404000";
	};
	bl.stop_moving = function(e) {
		bl.style.backgroundColor = "#404000";
	};
	br.stop_moving = function(e) {
		br.style.backgroundColor = "#404000";
	};
	listenEvent(tl,'mousedown',function(e){
		tl.is_moving = true;
		tl.start_move(e);
		stopEventPropagation(e);
		return false;
	});
	listenEvent(tr,'mousedown',function(e){
		tr.is_moving = true;
		tr.start_move(e);
		stopEventPropagation(e);
		return false;
	});
	listenEvent(bl,'mousedown',function(e){
		bl.is_moving = true;
		bl.start_move(e);
		stopEventPropagation(e);
		return false;
	});
	listenEvent(br,'mousedown',function(e){
		br.is_moving = true;
		br.start_move(e);
		stopEventPropagation(e);
		return false;
	});
	listenEvent(div,'mousedown',function(e){
		div.is_moving = true;
		div.start_move(e);
		stopEventPropagation(e);
		return false;
	});
	listenEvent(window,'mousemove',function(e){
		if (div.is_moving) {
			div.moving(e);
			stopEventPropagation(e);
			return false;
		}
		if (tl.is_moving) {
			tl.moving(e);
			stopEventPropagation(e);
			return false;
		}
		if (tr.is_moving) {
			tr.moving(e);
			stopEventPropagation(e);
			return false;
		}
		if (bl.is_moving) {
			bl.moving(e);
			stopEventPropagation(e);
			return false;
		}
		if (br.is_moving) {
			br.moving(e);
			stopEventPropagation(e);
			return false;
		}
	});
	listenEvent(window,'mouseup',function(e){
		if (div.is_moving) {
			div.is_moving = false;
			div.stop_moving(e);
			stopEventPropagation(e);
			return false;
		}
		if (tl.is_moving) {
			tl.is_moving = false;
			tl.stop_moving(e);
			stopEventPropagation(e);
			return false;
		}
		if (tr.is_moving) {
			tr.is_moving = false;
			tr.stop_moving(e);
			stopEventPropagation(e);
			return false;
		}
		if (bl.is_moving) {
			bl.is_moving = false;
			bl.stop_moving(e);
			stopEventPropagation(e);
			return false;
		}
		if (br.is_moving) {
			br.is_moving = false;
			br.stop_moving(e);
			stopEventPropagation(e);
			return false;
		}
	});
}
function adjust_too_large() {
	var link,img;
<?php 
foreach ($auto_adjust as $id) {
	echo "img=document.getElementById('picture_".$id."');";
	echo "if (img.complete) {link=document.getElementById('adjust_".$id."');link.onclick();}";
	echo "else img.onload=function(){link=document.getElementById('adjust_".$id."');link.onclick();}";
}
?>
}
adjust_too_large();
</script>
<?php
	$_SESSION["data_import__pictures"] = $imports;
}
?>