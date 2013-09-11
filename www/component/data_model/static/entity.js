if (typeof require != 'undefined')
	require("typed_field.js");
function data_entity_edit(icon, table, field, field_classname, field_arguments, key) {
	icon.src = theme.icons_16.loading;
	icon.onclick = null;
	require("typed_field.js",function() { require(field_classname+".js"); });
	service.json("data_model", "lock_cell", {table:table,row_key:key,column:field}, function(result) {
		if (result == null) {
			icon.src = theme.icons_16.edit;
			icon.onclick = function() { data_entity_edit(this, table, field, field_classname, field_arguments, key); };
			return;
		}
		window.database_locks.add_lock(parseInt(result.lock));
		var td = icon.parentNode;
		td = td.previousSibling;
		while (td.nodeName != "TD") td = td.previousSibling;
		td.innerHTML = "";
		require("typed_field.js",function() {
			require(field_classname+".js", function(){
				var f = new window[field_classname](result.value,true,null,null,eval('('+field_arguments+')'));
				td.appendChild(f.getHTMLElement());
				icon.src = theme.icons_16.save;
				icon.onclick = function() { data_entity_save(this, table, field, field_classname, field_arguments, key, result.lock); };
				icon.data = f;
				var img = document.createElement("IMG");
				img.src = theme.icons_16.no_edit;
				img.style.cursor = 'pointer';
				img.onclick = function() { data_entity_cancel_edit(this, table, field, field_classname, field_arguments, key, result.lock); };
				icon.parentNode.appendChild(img);
			});
		});
	});
}
function data_entity_cancel_edit(icon, table, field, field_classname, field_arguments, key, lock_id) {
	var td = icon.parentNode;
	td.removeChild(icon);
	icon = td.childNodes[0];
	icon.src = theme.icons_16.loading;
	icon.onclick = null;
	var f = icon.data;
	var value = f.getOriginalData();
	var td = icon.parentNode.previousSibling;
	while (td.nodeName != "TD") td = td.previousSibling;
	td.innerHTML = value;
	service.json("data_model", "unlock", {lock:lock_id}, function(result) {
		window.database_locks.remove_lock(lock_id);
		icon.src = theme.icons_16.edit;
		icon.onclick = function() { data_entity_edit(this, table, field, field_classname, field_arguments, key); };
	});	
}
function data_entity_save(icon, table, field, field_classname, field_arguments, key, lock_id) {
	var td = icon.parentNode;
	td.removeChild(td.childNodes[td.childNodes.length-1]);
	icon.src = theme.icons_16.loading;
	icon.onclick = null;
	var td = icon.parentNode.previousSibling;
	while (td.nodeName != "TD") td = td.previousSibling;
	var f = icon.data;
	if (!f.hasChanged()) {
		var value = f.getOriginalData();
		td.innerHTML = value;
		service.json("data_model", "unlock", {lock:lock_id}, function(result) {
			window.database_locks.remove_lock(lock_id);
			icon.src = theme.icons_16.edit;
			icon.onclick = function() { data_entity_edit(this, table, field, field_classname, field_arguments, key); };
		});
		return;	
	}
	var value = f.getCurrentData();
	service.call("data_model", "save_cell", {lock:lock_id,table:table,row_key:key,column:field,value:value},function(result) {
		window.database_locks.remove_lock(lock_id);
		icon.src = theme.icons_16.edit;
		icon.onclick = function() { data_entity_edit(this, table, field, field_classname, field_arguments, key); };
		if (!result)
			value = f.getOriginalData();
		td.innerHTML = value;
	});
}