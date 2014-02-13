/**
 * 
 * @param {Boolean} can_edit
 * @param {Boolean} can_add
 * @param {Boolean} can_remove
 * @param {Boolean} global_can_edit
 * @param {Boolean} global_can_remove
 * @param {Boolean} global_can_add
 * @param {String} to_lock can be "cell"|"column"|"row"|"table", 
 * @param {String} table_lock name of the table to lock
 * @param {String | null} column_lock name of the column to lock
 * @param {String | null} row_key key of the row to lock
 * @param {String} sub_model
 * @param {Number} db_lock attribute to update with the value of the database lock
 * @param {Function} lock_handler called before calling the lock service. If true is returned by the handler, the lock is performed
 * @param {Function} reseter of the page content
 * @param {Function} data_reset_handler called before going to uneditable mode<br/>
 * For instance, can be updating the data before reseting into read_only mode
 * @param {Function} can_go_uneditable_handler called before starting the process to go to the read only mode<br/>
 * Must return true if ok
 * @param {Object} page_header the page_header where the buttons edit / unedit should be added
 */
function editable_read_only_manager(can_edit, can_add, can_remove, global_can_edit, global_can_remove, global_can_add, to_lock, table_lock, column_lock, row_key, sub_model, db_lock, lock_handler, reseter, data_reset_handler, can_go_uneditable_handler, page_header){
	var t = this;
	
	/**
	 * Called after creating this object and saving
	 * If the id != -1 and db_lock attribute == null, this method would
	 * call the lock_row service, update the db_lock attribute and add the lock javascript
	 * Else nothing is done
	 * @param {Function} handler called before permorming the lock. Must return true if ok
	 * @param {String} lock can be "cell"|"column"|"row"|"table", 
	 * @param {String} table
	 * @param {String} sm
	 * @param {String | null} column
	 * @param {String | null} row
	 * @param {Function} (optional) onlock
	 * @param {Function} (optional) onnothing
	 */
	t.lockDatabase = function(handler, lock, table, sm, column, row, onlock, onnothing){
		if(t.db_lock == null){
			if(handler()){
				service.json("data_model","lock_"+lock,{table:table, column:column, row_key:row, sub_model:sm},function(res){
					if(res){
						databaselock.addLock(res.lock);
						db_lock = res.lock;
						if(onlock)
							onlock();
					}
				});
			} else {
				if(onnothing)
					onnothing();
			}
		} else {
			if(onnothing)
				onnothing();
		}
	};
	
	
	t._confirmGoUneditableMode = function(){
		if(can_go_uneditable_handler()){
			new confirm_dialog("Do you really want to go on the \"read only\" mode? <br/><i>Note: all the unsaved data will be lost</i>",function(res){
				if(res)
					t._goUneditableMode();
			});
		}
	};
	
	/**
	 * @method _goUneditableMode
	 * Update the global rights attributes and reset the table
	 * Unlock the row in database
	 */
	t._goUneditableMode = function(){
		var locker = lock_screen();
		t.setGlobalRights(true);
		if(t.db_lock != null)
			service.json("data_model","unlock",{lock:t.db_lock},function(res){
				if(res){
					databaselock.removeLock(t.db_lock);
					t.db_lock = null;
				}
			});
		data_reset_handler();
		reseter();
		unlock_screen(locker);
	};
	
	/**
	 * @method _goEditableMode
	 * Update the global rights attributes and reset the table
	 * Lock the row in database
	 */
	t._goEditableMode = function(){
		var onlock = function(){
			t.setGlobalRights(false);
			reseter();
		};
		t.lockDatabase(lock_handler, to_lock, table_lock, sub_model, column_lock, row_key, onlock, onlock);
	};
	
	/**
	 * @method setGlobalRights
	 * @param {boolean} new_read_only
	 * First called when this object is instantiated
	 * Set the current displaying mode (editable/non-editable)
	 * based on the user rights (defined by can_edit, can_add, can_remove parameters)
	 * and the displaying mode (defined by read_only)
	 * For instance, a user can have the right can_edit but is using the read_only_mode,
	 * so he the global_can_edit attribute is false
	 */
	t.setGlobalRights = function(new_read_only){
		if(can_edit){
			if(new_read_only){
				global_can_edit = false;
			} else
				global_can_edit = true;
		} else global_can_edit = false;
		if(can_add){
			if(new_read_only)
				global_can_add = false;
			else
				global_can_add = true;
		} else global_can_add = false;
		if(can_remove){
			if(new_read_only)
				global_can_remove = false;
			else
				global_can_remove = true;
		} else global_can_remove = false;
	};
	
	t.getCanEdit = function(){
		return global_can_edit;
	};
	
	t.getCanAdd = function(){
		return global_can_add;
	};
	
	t.getCanRemove = function(){
		return global_can_remove;
	};
	
	t.manageButtons = function(){
		/**
		 * If the user has not can_edit right no need to add the go_editable button
		 */
		if(can_edit && !global_can_edit){
			var go_editable = document.createElement("div");
			go_editable.className = "button";
			go_editable.innerHTML = "<img src ='"+theme.icons_16.edit+"'/> Edit";
			go_editable.onclick = t._goEditableMode;
			page_header.addMenuItem(go_editable);
		}
		/**
		 * If the user is in an editable mode he can go to read only mode
		 */
		if(global_can_edit){
			var go_read_only = document.createElement("div");
			go_read_only.className = "button";
			go_read_only.innerHTML = "<img src ='"+theme.icons_16.no_edit+"'/> Unedit";
			go_read_only.onclick = t._confirmGoUneditableMode;
			page_header.addMenuItem(go_read_only);
		}
	};
	
	
}