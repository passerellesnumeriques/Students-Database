<?php 
@unlink($_POST["path"]."/latest/datamodel.json");
if (class_exists("ZipArchive")) {
	$zip = new ZipArchive();
	$zip->open($_POST["path"]."/latest/datamodel.zip");
	$zip->extractTo($_POST["path"]."/latest");
	$zip->close();
} else {
	$output = array();
	$ret = 0;
	exec("/usr/bin/unzip \"".$_POST["path"]."/latest/datamodel.zip"."\" -d \"".$$_POST["path"]."/latest"."\"", $output, $ret);
	if ($ret <> 0)
		throw new Exception("Error unzipping installer (".$ret.")");
}
$datamodel_json = file_get_contents($_POST["path"]."/latest/datamodel.json");
$header = "<script type='text/javascript' src='/static/javascript/utils.js'></script><script type='text/javascript' src='/static/javascript/browser.js'></script><script type='text/javascript' src='/static/javascript/utils_js.js'></script><script type='text/javascript' src='/static/application/service.js'></script>";
?>
<?php include("header.inc");?>
<div style='flex:none;background-color:white;padding:10px'>

<form name='deploy' method="POST" action="generate_datamodel_changes.php">
<input type='hidden' name='version' value='<?php echo $_POST["version"];?>'/>
<input type='hidden' name='path' value='<?php echo $_POST["path"];?>'/>
<input type='hidden' name='latest' value='<?php echo $_POST["latest"];?>'/>
<input type='hidden' name='channel' value='<?php echo $_POST["channel"];?>'/>
<input type='hidden' name='changes' value=''/>
<input type='hidden' name='datamodel_json' value=''/>
<input type='hidden' name='datamodel_sql' value=''/>
</form>

<div style='font-size:14pt;padding-bottom:5px;border-bottom: 1px solid #808080;'>
	Comparison of Data Models
</div>
<div style='margin-top:10px' id='panel'>
</div>

</div>
<script type='text/javascript'>
var old_datamodel = <?php echo $datamodel_json;?>;
var panel = document.getElementById('panel');
panel.innerHTML = "Loading new datamodel...";

var changes = [];

function compare_datamodels(odm,ndm,parent_table,ondone) {
	// remove same tables
	for (var i = 0; i < odm.tables.length; ++i) {
		for (var j = 0; j < ndm.tables.length; ++j) {
			if (objectEquals(odm.tables[i], ndm.tables[j])) {
				ndm.tables.splice(j,1);
				odm.tables.splice(i,1);
				i--;
			}
		}
	}

	if (odm.tables.length > 0 || ndm.tables.length > 0)
		process_differences(odm, ndm, parent_table, function() { compare_submodels(odm,ndm,ondone); });
	else
		compare_submodels(odm,ndm,ondone);
}
function compare_submodels(odm,ndm,ondone) {
	if ((typeof odm.sub_models == 'undefined') || (typeof ndm.sub_models == 'undefined')) { ondone(); return; }
	var todo = [];
	for (var i = 0; i < odm.sub_models.length; ++i) {
		var parent_table = odm.sub_models[i].parent_table;
		for (var j = 0; j < changes.length; ++j) {
			if (changes[j].type == 'rename_table' && changes[j].old_table_name == parent_table) { parent_table = changes[j].new_table_name; break; }
			else if (changes[j].type == 'remove_table' && changes[j].table_name == parent_table) { parent_table = null; break; }
		}
		if (parent_table == null) continue;
		for (var j = 0; j < ndm.sub_models.length; ++j) {
			if (ndm.sub_models[i].parent_table == parent_table) {
				todo.push([odm.sub_models[i],ndm.sub_models[j],parent_table]);
				break;
			}
		}
	}
	var next = function() {
		if (todo.length == 0) { ondone(); return; }
		var t = todo[0];
		todo.splice(0,1);
		compare_datamodels(t[0],t[1],t[2],next);
	};
	next();
}
function isDone(odm,ndm,parent_table,ondone) {
	if (odm.tables.length == 0) {
		// no more table in the old model, remaining ones are new ones
		for (var i = 0; i < ndm.tables.length; ++i)
			changes.push({type:'add_table',table:ndm.tables[i],parent_table:parent_table});
		ondone();
		return true;
	}
	if (ndm.tables.length == 0) {
		// no more table in the new model, remaining ones are removed
		for (var i = 0; i < odm.tables.length; ++i)
			changes.push({type:'remove_table',table_name:odm.tables[i].name,parent_table:parent_table});
		ondone();
		return true;
	}
	return false;
}
function process_differences(odm,ndm,parent_table,ondone) {
	var odm_ = objectCopy(odm,100);
	var ndm_ = objectCopy(ndm,100);
	var end = function() {
		process_keys_and_indexes(odm_,ndm_,parent_table);
		ondone();
	};
	if (isDone(odm,ndm,parent_table,end)) return;
	process_renamed_tables(odm,ndm,parent_table,function() {
		if (isDone(odm,ndm,parent_table,end)) return;
		process_tables_same_name(odm,ndm,parent_table,[],function() {
			if (isDone(odm,ndm,parent_table,end)) return;
			process_remaining(odm,ndm,parent_table,end);
		});
	}); 
}
function process_remaining(odm,ndm,parent_table,ondone) {
	panel.innerHTML = "We have remaining changes in the "+(parent_table ? "sub-model "+parent_table : "model")+", but we don't know what to do with those changes.<br/>";
	panel.appendChild(document.createTextNode("The previous tables were"));
	var ul,ul2;
	var selects = [];
	var tables_changes = [];
	var checkEnd = function() {
		if (ul.childNodes.length == 0 && ul2.childNodes.length == 0) {
			var next_change = function() {
				if (tables_changes.length == 0) { ondone(); return; }
				var c = tables_changes[0];
				tables_changes.splice(0,1);
				process_table_same_name(odm,c.prev_index,ndm,c.new_index,parent_table,next_change);
			};
			next_change();
		}
	};
	ul = document.createElement("UL"); panel.appendChild(ul);
	for (var i = 0; i < odm.tables.length; ++i) {
		var li = document.createElement("LI");
		li.innerHTML = "Table <i>"+odm.tables[i].name+"</i>";
		li.appendChild(document.createElement("BR"));
		var button_remove = document.createElement("BUTTON");
		button_remove.innerHTML = "Remove this one, and lose the data";
		button_remove._table = i;
		button_remove.onclick = function() {
			var index;
			for (index = 0; index < this.parentNode.parentNode.childNodes.length; ++index)
				if (this.parentNode.parentNode.childNodes[index] == this.parentNode) break;
			for (var l = 0; l < selects.length; ++l) selects[l].remove(index);
			changes.push({type:'remove_table',table_name:odm.tables[this._table].name,parent_table:parent_table});
			this.parentNode.parentNode.removeChild(this.parentNode);
			checkEnd();
		};
		li.appendChild(button_remove);
		ul.appendChild(li);
	}
	panel.appendChild(document.createTextNode("The new tables are"));
	ul2 = document.createElement("UL"); panel.appendChild(ul2);
	for (var i = 0; i < ndm.tables.length; ++i) {
		var li = document.createElement("LI");
		li.innerHTML = "Table <i>"+ndm.tables[i].name+"</i>";
		li.appendChild(document.createElement("BR"));
		var button_new = document.createElement("BUTTON");
		button_new.innerHTML = "This is a new one";
		button_new._table = i;
		button_new.onclick = function() {
			changes.push({type:'add_table',table:ndm.tables[this._table],parent_table:parent_table});
			this.parentNode.parentNode.removeChild(this.parentNode);
			checkEnd();
		};
		li.appendChild(button_new);
		li.appendChild(document.createElement("BR"));
		li.appendChild(document.createTextNode("This was table "));
		var select = document.createElement("SELECT");
		selects.push(select);
		for (var l = 0; l < odm.tables.length; ++l) {
			var o = document.createElement("OPTION");
			o.text = odm.tables[l].name;
			o._index = l;
			select.add(o);
		}
		li.appendChild(select);
		li.appendChild(document.createTextNode(" before "));
		var button_change = document.createElement("BUTTON");
		button_change.innerHTML = "Ok";
		button_change._select = select;
		button_change._index = i;
		button_change.onclick = function() {
			var index = this._select.selectedIndex;
			if (index >= 0 && index < this._select.options.length) {
				changes.push({type:'rename_table',old_table_name:odm.tables[this._select.options[index]._index].name,new_table_name:ndm.tables[this._index].name,parent_table:parent_table});
				tables_changes.push({prev_index:this._select.options[index]._index,new_index:this._index});
				for (var l = 0; l < selects.length; ++l) selects[l].remove(index);
				ul.removeChild(ul.childNodes[index]);
				this.parentNode.parentNode.removeChild(this.parentNode);
				checkEnd();
			}
		};
		li.appendChild(button_change);
		ul2.appendChild(li);
	}
}
function process_renamed_tables(odm,ndm,parent_table,ondone) {
	var renamed = {};
	for (var i = 0; i < odm.tables.length; ++i) {
		for (var j = 0; j < ndm.tables.length; ++j) {
			var copy = objectCopy(ndm.tables[j],10);
			copy.name = odm.tables[i].name;
			if (objectEquals(copy, odm.tables[i])) {
				if (typeof renamed[odm.tables[i].name] == 'undefined')
					renamed[odm.tables[i].name] = [];
				renamed[odm.tables[i].name].push(ndm.tables[j].name);
			}
		}
	}
	ask_renamed(odm,ndm,parent_table,renamed,ondone);
}
function ask_renamed(odm,ndm,parent_table,renamed,ondone) {
	for (var old_table in renamed) {
		panel.innerHTML = "The table <i>"+old_table+"</i> seems to be renamed into<br/>";
		var radios = [];
		for (var i = 0; i < renamed[old_table].length; ++i) {
			var radio = document.createElement("INPUT");
			radio.type = 'radio';
			radio.name = 'renamed';
			radio.value = i;
			panel.appendChild(radio);
			panel.appendChild(document.createTextNode(" table "+renamed[old_table][i]));
			panel.appendChild(document.createElement("BR"));
			radios.push(radio);
		}
		var radio = document.createElement("INPUT");
		radio.type = 'radio';
		radio.name = 'renamed';
		radio.value = -1;
		panel.appendChild(radio);
		panel.appendChild(document.createTextNode(" None of them, it is not a rename, it is a new one"));
		panel.appendChild(document.createElement("BR"));
		radios.push(radio);
		var button = document.createElement("BUTTON");
		panel.appendChild(button);
		button.innerHTML = "Confirm";
		button.onclick = function() {
			var new_table = null;
			for (var i = 0; i < radios.length; ++i) if (radios[i].checked) { if (radios[i].value >= 0) new_table = renamed[old_table][radios[i].value]; break; }
			delete renamed[old_table];
			if (new_table != null) {
				changes.push({type:'rename_table',old_table_name:old_table,new_table_name:new_table,parent_table:parent_table});
				for (var i = 0; i < odm.tables.length; ++i)
					if (odm.tables[i].name == old_table) { odm.tables.splice(i,1); break; }
				for (var i = 0; i < ndm.tables.length; ++i)
					if (ndm.tables[i].name == new_table) { ndm.tables.splice(i,1); break; }
			}
			ask_renamed(odm,ndm,parent_table,renamed,ondone);
		};
		return;
	}
	ondone();
}
function process_tables_same_name(odm,ndm,parent_table,done,ondone) {
	for (var i = 0; i < odm.tables.length; ++i) {
		if (done.contains(odm.tables[i].name)) continue;
		for (var j = 0; j < ndm.tables.length; ++j) {
			if (odm.tables[i].name == ndm.tables[j].name) {
				done.push(odm.tables[i].name);
				process_table_same_name(odm,i,ndm,j,parent_table,function() {
					process_tables_same_name(odm,ndm,parent_table,done,ondone);
				});
				return;
			}
		}
	}
	ondone();
}
function process_table_same_name(odm,i,ndm,j,parent_table,ondone) {
	// remove same columns
	for (var ii = 0; ii < odm.tables[i].columns.length; ++ii) {
		for (var jj = 0; jj < ndm.tables[j].columns.length; ++jj) {
			if (objectEquals(odm.tables[i].columns[ii], ndm.tables[j].columns[jj])) {
				ndm.tables[j].columns.splice(jj,1);
				odm.tables[i].columns.splice(ii,1);
				ii--;
			}
		}
	}
	process_columns_differences(odm, i, ndm, j, parent_table, ondone);
}
function process_columns_differences(odm, i, ndm, j, parent_table, ondone) {
	process_renamed_columns(odm, i, ndm, j, parent_table, function() {
		process_columns_spec_change(odm, i, ndm, j, parent_table, [], function() {
			if (odm.tables[i].columns.length == 0) {
				if (ndm.tables[j].columns.length == 0) {
					// everything done
					odm.tables.splice(i,1);
					ndm.tables.splice(j,1);
					ondone();
					return;
				}
				// remaining are new columns
				for (var k = 0; k < ndm.tables[j].columns.length; ++k)
					changes.push({type:'add_column',table:ndm.tables[j].name,column:ndm.tables[j].columns[k],parent_table:parent_table});
				odm.tables.splice(i,1);
				ndm.tables.splice(j,1);
				ondone();
				return;
			} else if (ndm.tables[j].columns.length == 0) {
				// remaining are removed columns
				for (var k = 0; k < odm.tables[i].columns.length; ++k)
					changes.push({type:'remove_column',table:odm.tables[i].name,column:odm.tables[i].columns[k].name,parent_table:parent_table});
				odm.tables.splice(i,1);
				ndm.tables.splice(j,1);
				ondone();
				return;
			}
			ask_remaining_columns(odm,i,ndm,j,parent_table,ondone);
		});
	});
}
function process_renamed_columns(odm, i, ndm, j, parent_table,ondone) {
	var renamed = {};
	for (var ii = 0; ii < odm.tables[i].columns.length; ++ii) {
		for (var jj = 0; jj < ndm.tables[j].columns.length; ++jj) {
			var copy = objectCopy(ndm.tables[j].columns[jj],10);
			copy.name = odm.tables[i].columns[ii].name;
			if (objectEquals(copy, odm.tables[i].columns[ii])) {
				if (typeof renamed[odm.tables[i].columns[ii].name] == 'undefined')
					renamed[odm.tables[i].columns[ii].name] = [];
				renamed[odm.tables[i].columns[ii].name].push(ndm.tables[j].columns[jj].name);
			}
		}
	}
	ask_renamed_columns(odm,i,ndm,j,parent_table,renamed,ondone);
}
function ask_renamed_columns(odm,i,ndm,j,parent_table,renamed,ondone) {
	for (var old_col in renamed) {
		panel.innerHTML = "The column <i>"+old_col+"</i> in table <i>"+odm.tables[i].name+"</i> seems to be renamed into<br/>";
		var radios = [];
		for (var k = 0; k < renamed[old_col].length; ++k) {
			var radio = document.createElement("INPUT");
			radio.type = 'radio';
			radio.name = 'renamed';
			radio.value = k;
			panel.appendChild(radio);
			panel.appendChild(document.createTextNode(" column "+renamed[old_col][k]+" of table "+ndm.tables[j].name));
			panel.appendChild(document.createElement("BR"));
			radios.push(radio);
		}
		var radio = document.createElement("INPUT");
		radio.type = 'radio';
		radio.name = 'renamed';
		radio.value = -1;
		panel.appendChild(radio);
		panel.appendChild(document.createTextNode(" None of them, it is not a rename, it is a new one"));
		panel.appendChild(document.createElement("BR"));
		radios.push(radio);
		var button = document.createElement("BUTTON");
		panel.appendChild(button);
		button.innerHTML = "Confirm";
		button.onclick = function() {
			var new_col = null;
			for (var k = 0; k < radios.length; ++k) if (radios[k].checked) { if (radios[k].value >= 0) new_col = renamed[old_col][radios[k].value]; break; }
			delete renamed[old_col];
			if (new_col != null) {
				changes.push({type:'rename_column',old_table_name:odm.tables[i].name,new_table_name:ndm.tables[j].name,old_column_name:old_col,new_column_name:new_col,parent_table:parent_table});
				for (var k = 0; k < odm.tables[i].columns.length; ++k)
					if (odm.tables[i].columns[k].name == old_col) { odm.tables[i].columns.splice(k,1); break; }
				for (var k = 0; k < ndm.tables[j].columns.length; ++k)
					if (ndm.tables[j].columns[k].name == new_col) { ndm.tables[j].columns.splice(k,1); break; }
			}
			ask_renamed_columns(odm,i,ndm,j,parent_table,renamed,ondone);
		};
		return;
	}
	ondone();
}
function process_columns_spec_change(odm, i, ndm, j, parent_table, done, ondone) {
	for (var ii = 0; ii < odm.tables[i].columns.length; ++ii) {
		if (done.contains(odm.tables[i].columns[ii].name)) continue;
		for (var jj = 0; jj < ndm.tables[j].columns.length; ++jj) {
			if (odm.tables[i].columns[ii].name == ndm.tables[j].columns[jj].name) {
				done.push(odm.tables[i].columns[ii].name);
				process_column_change(odm,i,ii,ndm,j,jj,parent_table,function() {
					process_columns_spec_change(odm,i,ndm,j,parent_table,done,ondone);
				});
				return;
			}
		}
	}
	ondone();
}
function process_column_change(odm,i,ii,ndm,j,jj,parent_table,ondone) {
	panel.innerHTML = "The column <i>"+odm.tables[i].columns[ii].name+"</i> in table <i>"+odm.tables[i].name+"</i>";
	if (odm.tables[i].name != ndm.tables[j].name) panel.innerHTML += " (which became table <i>"+ndm.tables[j].name+"</i>)";
	panel.innerHTML += " seems to have changed<ul><li>from: <code>"+service.generateInput(odm.tables[i].columns[ii])+"</code></li><li>to: <code>"+service.generateInput(ndm.tables[j].columns[jj])+"</code></li></ul>";
	var button_confirm = document.createElement("BUTTON");
	button_confirm.innerHTML = "Yes, and I understand the possible impacts";
	panel.appendChild(button_confirm);
	var button_no = document.createElement("BUTTON");
	button_no.innerHTML = "No, the column is a new one, and we should remove data from previous one";
	panel.appendChild(button_no);
	button_confirm.onclick = function() {
		changes.push({type:'column_spec',old_table_name:odm.tables[i].name,new_table_name:odm.tables[j].name,old_spec:odm.tables[i].columns[ii],new_spec:ndm.tables[j].columns[jj],parent_table:parent_table});
		odm.tables[i].columns.splice(ii,1);
		ndm.tables[j].columns.splice(jj,1);
		ondone();
	};
	button_no.onclick = function() {
		changes.push({type:'remove_column',table:odm.tables[i].name,column:odm.tables[i].columns[ii].name,parent_table:parent_table});
		changes.push({type:'add_column',table:ndm.tables[j].name,column:ndm.tables[j].columns[jj],parent_table:parent_table});
		odm.tables[i].columns.splice(ii,1);
		ndm.tables[j].columns.splice(jj,1);
		ondone();
	};
}
function ask_remaining_columns(odm,i,ndm,j,parent_table,ondone) {
	panel.innerHTML = "We have changes in table ";
	if (odm.tables[i].name != ndm.tables[j].name)
		panel.innerHTML += "previously named "+odm.tables[i].name+" and renamed into "+ndm.tables[j].name;
	else
		panel.innerHTML += odm.tables[i].name;
	panel.innerHTML += ", but we don't know what to do with those changes.<br/>";
	panel.appendChild(document.createTextNode("The previous columns were"));
	var ul,ul2;
	var selects = [];
	var checkEnd = function() {
		if (ul.childNodes.length == 0 && ul2.childNodes.length == 0) {
			odm.tables.splice(i,1);
			ndm.tables.splice(j,1);
			ondone();
		}
	};
	ul = document.createElement("UL"); panel.appendChild(ul);
	for (var k = 0; k < odm.tables[i].columns.length; ++k) {
		var li = document.createElement("LI");
		li.innerHTML = service.generateInput(odm.tables[i].columns[k]);
		li.appendChild(document.createElement("BR"));
		var button_remove = document.createElement("BUTTON");
		button_remove.innerHTML = "Remove this one, and lose the data";
		button_remove._table = odm.tables[i].name;
		button_remove._column = odm.tables[i].columns[k].name;
		button_remove.onclick = function() {
			var index;
			for (index = 0; index < this.parentNode.parentNode.childNodes.length; ++index)
				if (this.parentNode.parentNode.childNodes[index] == this.parentNode) break;
			for (var l = 0; l < selects.length; ++l) selects[l].remove(index);
			changes.push({type:'remove_column',table:this._table,column:this._column,parent_table:parent_table});
			this.parentNode.parentNode.removeChild(this.parentNode);
			checkEnd();
		};
		li.appendChild(button_remove);
		ul.appendChild(li);
	}
	panel.appendChild(document.createTextNode("The new columns are"));
	ul2 = document.createElement("UL"); panel.appendChild(ul2);
	for (var k = 0; k < ndm.tables[j].columns.length; ++k) {
		var li = document.createElement("LI");
		li.innerHTML = service.generateInput(ndm.tables[j].columns[k]);
		li.appendChild(document.createElement("BR"));
		var button_new = document.createElement("BUTTON");
		button_new.innerHTML = "This is a new one";
		button_new._table = ndm.tables[j].name;
		button_new._column = ndm.tables[j].columns[k];
		button_new.onclick = function() {
			changes.push({type:'add_column',table:this._table,column:this._column,parent_table:parent_table});
			this.parentNode.parentNode.removeChild(this.parentNode);
			checkEnd();
		};
		li.appendChild(button_new);
		li.appendChild(document.createElement("BR"));
		li.appendChild(document.createTextNode("This was column "));
		var select = document.createElement("SELECT");
		selects.push(select);
		for (var l = 0; l < odm.tables[i].columns.length; ++l) {
			var o = document.createElement("OPTION");
			o.text = odm.tables[i].columns[l].name;
			o._spec = odm.tables[i].columns[l];
			select.add(o);
		}
		li.appendChild(select);
		li.appendChild(document.createTextNode(" before, and its specification changed "));
		var button_change = document.createElement("BUTTON");
		button_change.innerHTML = "And I understand the possible impacts";
		button_change._select = select;
		button_change._new_spec = ndm.tables[j].columns[k];
		button_change.onclick = function() {
			var index = this._select.selectedIndex;
			if (index >= 0 && index < this._select.options.length) {
				changes.push({type:'column_spec',old_table_name:odm.tables[i].name,new_table_name:ndm.tables[j].name,old_spec:this._select.options[index]._spec,new_spec:this._new_spec,parent_table:parent_table});
				for (var l = 0; l < selects.length; ++l) selects[l].remove(index);
				ul.removeChild(ul.childNodes[index]);
				this.parentNode.parentNode.removeChild(this.parentNode);
				checkEnd();
			}
		};
		li.appendChild(button_change);
		ul2.appendChild(li);
	}
}
function process_keys_and_indexes(odm,ndm,parent_table) {
	for (var i = 0; i < odm.tables.length; ++i) {
		var otable = odm.tables[i];
		var removed = false;
		for (var j = 0; j < changes.length; ++j) if (changes[j].type == "remove_table" && changes[j].table_name == otable.name) { removed = true; break; }
		if (removed) continue;
		var ntable_name = otable.name;
		for (var j = 0; j < changes.length; ++j) if (changes[j].type == "rename_table" && changes[j].old_table_name == otable.name) { ntable_name = changes[j].new_table_name; break; }
		var ntable;
		for (var j = 0; j < ndm.tables.length; ++j) if (ndm.tables[j].name == ntable_name) { ntable = ndm.tables[j]; break; }
		if (objectEquals(otable.key,ntable.key) == false) {
			if (otable.key != null)
				changes.push({type:"index_removed",parent_table:parent_table,table:ntable_name,index_name:(typeof otable.key == 'string' ? "PRIMARY" : "table_key"),key:otable.key});
			if (ntable.key != null)
				changes.push({type:"index_added",parent_table:parent_table,table:ntable_name,index_name:(typeof ntable.key == 'string' ? "PRIMARY" : "table_key"),key:ntable.key});
		}
		for (var j = 0; j < otable.indexes.length; ++j) {
			var found = false;
			for (var k = 0; k < ntable.indexes.length; ++k) {
				if (ntable.indexes[k].length != otable.indexes[j].length) continue;
				var same = true;
				for (var l = 0; l < ntable.indexes[k].length; ++l) if (ntable.indexes[k][l] != otable.indexes[j][l]) { same = false; break; }
				if (!same) continue;
				found = true;
				ntable.indexes.splice(k,1);
				break;
			}
			if (!found) {
				var name = otable.indexes[j][0];
				otable.indexes[j].splice(0,1);
				changes.push({type:"index_removed",parent_table:parent_table,table:ntable_name,index_name:name,columns:otable.indexes[j]});
			}
		}
		for (j = 0; j < ntable.indexes.length; ++j) {
			var name = ntable.indexes[j][0];
			ntable.indexes[j].splice(0,1);
			changes.push({type:"index_added",parent_table:parent_table,table:ntable_name,index_name:name,columns:ntable.indexes[j]});
		}
	}
}


function finish(odm,ndm) {
	if (changes.length == 0)
		panel.innerHTML = "No change in the data model<br/>";
	else {
		panel.innerHTML = "The changes made to the model are the following:";
		var ul = document.createElement("UL");
		for (var i = 0; i < changes.length; ++i)
			ul.appendChild(createChangeDescription(changes[i],odm,ndm));
		panel.appendChild(ul);
	}
	var button = document.createElement("BUTTON");
	button.innerHTML = "I confirm all those changes, I made necessary migration scripts if any has functional implications";
	panel.appendChild(button);
	button.onclick = function() {
		panel.innerHTML = "Generating data model migration script...";
		var form = document.forms['deploy'];
		form.elements['changes'].value = service.generateInput(changes);
		var xhr = new XMLHttpRequest();
		xhr.open("GET","/dynamic/development/service/get_datamodel?output=json", true);
		xhr.onreadystatechange = function() {
		    if (this.readyState != 4) return;
			form.elements['datamodel_json'].value = xhr.responseText;
			xhr = new XMLHttpRequest();
			xhr.open("GET","/dynamic/development/service/get_datamodel?output=sql", true);
			xhr.onreadystatechange = function() {
			    if (this.readyState != 4) return;
				form.elements['datamodel_sql'].value = xhr.responseText;
				form.submit();
			};
			xhr.send();
		};
		xhr.send();
	};
}
function createChangeDescription(change,odm,ndm) {
	var li = document.createElement("LI");
	switch (change.type) {
	case "add_table": li.innerHTML = "Table <i>"+change.table.name+"</i> has been added"; break;
	case "remove_table": 
		li.innerHTML = "Table <i>"+change.table_name+"</i> has been removed, <img src='/static/theme/default/icons_16/warning.png'/> all data in it will be lost";
		var found = false;
		for (var i = 0; i < odm.sub_models.length; ++i) if (odm.sub_models[i].parent_table == change.table_name) { found = true; break; }
		if (found) li.innerHTML += "<br/><img src='/static/theme/default/icons_16/warning.png'/> This table was associated with a sub-model, all tables from this sub-model will be removed!";
		break;
	case "rename_table": li.innerHTML = "Table <i>"+change.old_table_name+"</i> has been renamed into <i>"+change.new_table_name+"</i>"; break; 
	case "add_column": li.innerHTML = "Column <i>"+change.column.name+"</i> has been added in table <i>"+change.table+"</i>"; break;
	case "remove_column": li.innerHTML = "Column <i>"+change.column+"</i> has been removed from table <i>"+change.table+"</i>, <img src='/static/theme/default/icons_16/warning.png'/> all data in it will be lost"; break;
	case "rename_column": li.innerHTML = "Column <i>"+change.old_column_name+"</i> of table <i>"+change.old_table_name+"</i> became column <i>"+change.new_column_name+"</i> of table <i>"+change.new_table_name+"</i>"; break;
	case "column_spec":
		li.innerHTML = "Column <i>"+change.old_spec.name+"</i> of table <i>"+change.old_table_name+"</i> changed its specification";
		if(change.new_spec.name != change.old_spec.name)
			li.innerHTML += " and will be column <i>"+change.new_spec.name+"</i> of table <i>"+change.new_table_name+"</i>";
		else if (change.new_table_name != change.old_table_name)
			li.innerHTML += " and will be in table <i>"+change.new_table_name+"</i>";
		break;
	case "index_removed":
		if (change.index_name == "PRIMARY")
			li.innerHTML = "Primary key <i>"+change.key+"</i> removed from table <i>"+change.table+"</i>";
		else if (change.index_name == "table_key") {
			var s = "";
			for (var i = 0; i < change.key.length; ++i) {
				if (i > 0) s += ", ";
				s += change.key[i];
			} 
			li.innerHTML = "Key index on columns <i>"+s+"</i> removed from table <i>"+change.table+"</i>";
		} else {
			var s = "";
			for (var i = 0; i < change.columns.length; ++i) {
				if (i > 0) s += ", ";
				s += change.columns[i];
			} 
			li.innerHTML = "Index on columns <i>"+s+"</i> removed from table <i>"+change.table+"</i>";
		}
		break;
	case "index_added":
		if (change.index_name == "PRIMARY")
			li.innerHTML = "Primary key <i>"+change.key+"</i> added to table <i>"+change.table+"</i>";
		else if (change.index_name == "table_key") {
			var s = "";
			for (var i = 0; i < change.key.length; ++i) {
				if (i > 0) s += ", ";
				s += change.key[i];
			} 
			li.innerHTML = "Key index on columns <i>"+s+"</i> added to table <i>"+change.table+"</i>";
		} else {
			var s = "";
			for (var i = 0; i < change.columns.length; ++i) {
				if (i > 0) s += ", ";
				s += change.columns[i];
			} 
			li.innerHTML = "Index on columns <i>"+s+"</i> added to table <i>"+change.table+"</i>";
		}
		break;
	default: li.innerHTML = "<img src='/static/theme/default/icons_16/error.png'/> Unknown change: "+change.type; break;
	}
	return li;
}

var xhr = new XMLHttpRequest();
xhr.open("GET","/dynamic/development/service/get_datamodel?output=json", true);
xhr.onreadystatechange = function() {
    if (this.readyState != 4) return;
    var new_datamodel = eval('('+xhr.responseText+')');
	panel.innerHTML = "Comparing models...";
    setTimeout(function() {
    	compare_datamodels(old_datamodel.result.model, new_datamodel.result.model,null,function(){
        	finish(old_datamodel.result.model, new_datamodel.result.model);
    	});
    },1);
};
xhr.send();
</script>
<?php include("footer.inc");?>