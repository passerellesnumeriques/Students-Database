if (typeof require != 'undefined')
	require("typed_field.js");
function data_entity_edit(ev,icon, field_container, table, field, field_classname, field_arguments, key) {
	stopEventPropagation(ev);
	icon.src = theme.icons_16.loading;
	icon.onclick = null;
	require("typed_field.js",function() { require(field_classname+".js"); });
	service.json("data_model", "lock_cell", {table:table,row_key:key,column:field}, function(result) {
		if (result == null) {
			icon.src = theme.icons_16.edit;
			icon.onclick = function(event) { data_entity_edit(event, this, field_container, table, field, field_classname, field_arguments, key); return false; };
			return;
		}
		window.database_locks.add_lock(parseInt(result.lock));
		field_container.innerHTML = "";
		require("typed_field.js",function() {
			require(field_classname+".js", function(){
				var f = new window[field_classname](result.value,true,null,null,typeof field_arguments == 'string' ? eval('('+field_arguments+')') : field_arguments);
				field_container.appendChild(f.getHTMLElement());
				icon.src = theme.icons_16.save;
				icon.onclick = function(event) { data_entity_save(event, this, field_container, table, field, field_classname, field_arguments, key, result.lock); return false; };
				icon.data = f;
				var img = document.createElement("IMG");
				img.src = theme.icons_16.no_edit;
				img.style.cursor = 'pointer';
				img.style.verticalAlign = 'bottom';
				img.onclick = function(event) { data_entity_cancel_edit(event, this, field_container, table, field, field_classname, field_arguments, key, result.lock); return false; };
				icon.parentNode.appendChild(img);
			});
		});
	});
}
function data_entity_cancel_edit(ev, icon, field_container, table, field, field_classname, field_arguments, key, lock_id) {
	stopEventPropagation(ev);
	var save_icon = icon.previousSibling;
	var td = icon.parentNode;
	td.removeChild(icon);
	icon = save_icon;
	icon.src = theme.icons_16.loading;
	icon.onclick = null;
	var f = icon.data;
	var value = f.getOriginalData();
	field_container.innerHTML = value;
	service.json("data_model", "unlock", {lock:lock_id}, function(result) {
		window.database_locks.remove_lock(lock_id);
		icon.src = theme.icons_16.edit;
		icon.onclick = function(event) { data_entity_edit(event, this, field_container, table, field, field_classname, field_arguments, key); return false; };
	});	
}
function data_entity_save(ev,icon, field_container, table, field, field_classname, field_arguments, key, lock_id) {
	stopEventPropagation(ev);
	var td = icon.parentNode;
	td.removeChild(td.childNodes[td.childNodes.length-1]);
	icon.src = theme.icons_16.loading;
	icon.onclick = null;
	var f = icon.data;
	if (!f.hasChanged()) {
		var value = f.getOriginalData();
		field_container.innerHTML = value;
		service.json("data_model", "unlock", {lock:lock_id}, function(result) {
			window.database_locks.remove_lock(lock_id);
			icon.src = theme.icons_16.edit;
			icon.onclick = function(event) { data_entity_edit(event,this, field_container, table, field, field_classname, field_arguments, key); return false; };
		});
		return;	
	}
	var value = f.getCurrentData();
	service.json("data_model", "save_cell", {lock:lock_id,table:table,row_key:key,column:field,value:value},function(result) {
		window.database_locks.remove_lock(lock_id);
		icon.src = theme.icons_16.edit;
		icon.onclick = function(event) { data_entity_edit(event,this, field_container, table, field, field_classname, field_arguments, key); return false; };
		if (!result)
			value = f.getOriginalData();
		field_container.innerHTML = value;
	});
}