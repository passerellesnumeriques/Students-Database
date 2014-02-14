/**
 * Manage the editable / read only mode of a given page<br/>
 * The data are locked / unlocked and the if depending on the case<br/>
 * Buttons edit / unedit are added or not to the page header
 * The lock parameters can be arrays if several locks are required on the page. All this array must be ordered the same way
 * @param {Boolean} can_edit
 * @param {Boolean} can_add
 * @param {Boolean} can_remove
 * @param {Boolean} global_can_edit
 * @param {Boolean} global_can_remove
 * @param {Boolean} global_can_add
 * @param {String|Array} to_lock can be "cell"|"column"|"row"|"table", 
 * @param {String|Array} table_lock name of the table to lock
 * @param {String|Array} column_lock name of the column to lock. Can be null / array of null
 * @param {String|Array} row_key key of the row to lock. Can be null / array of null
 * @param {String|Array} sub_model
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
	if(typeof to_lock != "object" || to_lock == null)
		t.to_lock = [to_lock];
	else
		t.to_lock = to_lock;
	if(typeof table_lock != "object" || table_lock == null)
		t.table_lock = [table_lock];
	else 
		t.table_lock = table_lock;
	if(typeof column_lock != "object" || column_lock == null)
		t.column_lock = [column_lock];
	else 
		t.column_lock = column_lock;
	if(typeof row_key != "object" || row_key == null)
		t.row_key = [row_key];
	else
		t.row_key = row_key;
	if(typeof sub_model != "object" || sub_model == null)
		t.sub_model = [sub_model];
	else
		t.sub_model = sub_model;
	if(typeof db_lock != "object" || db_lock == null)
		t.db_lock = [db_lock];
	else
		t.db_lock = db_lock;
	t.length = t.to_lock.length;
	
	
	/**
	 * Call the lock data service and update the matching t.db_lock value
	 * @param {String} lock can be "cell"|"column"|"row"|"table" 
	 * @param {String} table
	 * @param {String} sm
	 * @param {String | null} column
	 * @param {String | null} row
	 * @param {Function} ondone called after executing the service
	 */
	t._lockDatabase = function(index, lock, table, sm, column, row, ondone){
		service.json("data_model","lock_"+lock,{table:table, column:column, row_key:row, sub_model:sm},function(res){
			if(res){
				databaselock.addLock(res.lock);
				t.db_lock[index] = res.lock;
			}
			ondone();
		});
	};
	
	/**
	 * Lock all the data to lock after checking we can (calling lock handler)
	 * @param {Function|null} onlock function called ones all the lock are performed
	 * @param {Function|null} onnothing function called if no lock is performed
	 */
	t.lockDatabase = function(onlock, onnothing){
		if (!lock_handler()) {
			if (onnothing) onnothing();
			return;
		}
		var nb_todo = 0;
		for(var i = 0; i < t.length; i++)
			if(t.db_lock[i] == null) nb_todo++;
		if (nb_todo == 0) {
			if (onnothing) onnothing();
			return;
		}
		for(var i = 0; i < t.length; i++) {
			if(t.db_lock[i] != null) continue;
			t._lockDatabase(i, t.to_lock[i], t.table_lock[i], t.sub_model[i], t.column_lock[i], t.row_key[i], function() {
				if (--nb_todo == 0) {
					if (onlock) onlock();
				}
			});
		}
	};
	
	/**
	 * Method that first calls the can_go_uneditable_handler<br/>
	 * If ok, a confirm dialog is poped up
	 */
	t._confirmGoUneditableMode = function(){
		if(can_go_uneditable_handler()){
			new confirm_dialog("Do you really want to go on the \"read only\" mode? <br/><i>Note: all the unsaved data will be lost</i>",function(res){
				if(res)
					t._goUneditableMode();
			});
		}
	};
	
	/**
	 * Method called when the user confirms he wants to go to the uneditable mode<br/>
	 * Lock the screen and calls the unlock method
	 */
	t._goUneditableMode = function(){
		var locker = lock_screen();
		t.setGlobalRights(true);
		var onunlock = function(){
			data_reset_handler();
			reseter();
			unlock_screen(locker);
		};
		t.unlock(onunlock);
	};
	
	/**
	 * Unlock all the data to unlock
	 * @param {Function|Null} onunlock function called once all the unlcok are performed
	 */
	t.unlock = function(onunlock){
		var to_do = 0;
		for(var i = 0; i < t.length; i++)
			if(t.db_lock[i] != null)
				to_do++;
		if(to_do == 0){
			if(onunlock)
				onunlock();
			return;
		}
		for(var i = 0; i < t.length; i++){
			if(t.db_lock[i] == null) continue;
			t._unlock(i,t.db_lock[i],function(){
				if(-- to_do == 0){
					if(onunlock)
						onunlock();
				}
			});
		}
	};
	
	/**
	 * Method that calls the data_model unlock service<br/>
	 * Once the service is performed remove the javascript lock
	 * @param {Number} index the index in the arrays attributes(t.db_lock...)
	 * @param {Number} lock the id of the lock to destroy
	 * @param {Function} ondone called once the service is performed (even if it failed)
	 */
	t._unlock = function(index, lock, ondone){
		service.json("data_model","unlock",{lock:lock},function(res){
			if(res){
				databaselock.removeLock(lock);
				t.db_lock[index] = null;
			}
			ondone();
		});
	};
	
	/**
	 * Lock the screen and calls the lock database method
	 */
	t._goEditableMode = function(){
		var locker = lock_screen(); 
		var onlock = function(){
			t.setGlobalRights(false);
			reseter();
			unlock_screen(locker);
		};
		t.lockDatabase(onlock, onlock);
	};
	
	/**
	 * @param {Boolean} new_read_only_call true if required to switch from editable to readonly mode false if required to switch from read_only to editable mode
	 * Must be called when this object is instantiated<br/>
	 * Set the current displaying mode (editable/non-editable)<br/>
	 * based on the user rights (defined by can_edit, can_add, can_remove parameters)<br/>
	 * and the displaying mode (defined by read_only)<br/>
	 * For instance, a user can have the right can_edit but is using the read_only_mode,<br/>
	 * so he the global_can_edit attribute is false
	 */
	t.setGlobalRights = function(new_read_only_call){
		if(can_edit){
			if(new_read_only_call){
				global_can_edit = false;
			} else
				global_can_edit = true;
		} else global_can_edit = false;
		if(can_add){
			if(new_read_only_call)
				global_can_add = false;
			else
				global_can_add = true;
		} else global_can_add = false;
		if(can_remove){
			if(new_read_only_call)
				global_can_remove = false;
			else
				global_can_remove = true;
		} else global_can_remove = false;
	};
	
	/**
	 * @returns {Boolean} global_can_edit
	 */
	t.getCanEdit = function(){
		return global_can_edit;
	};
	
	/**
	 * @returns {Boolean} global_can_add
	 */
	t.getCanAdd = function(){
		return global_can_add;
	};
	
	/**
	 * @returns {Boolean} global_can_remove
	 */
	t.getCanRemove = function(){
		return global_can_remove;
	};
	
	/**
	 * Get the db_length attribute<br/>
	 * If only one lock is managed by this object, this method would return a number|null<br/>
	 * Else returns an array containing each lock id
	 * @returns {Number|Array}
	 */
	t.getDBLock = function(){
		if(t.length == 1)
			return t.db_lock[0];
		else
			return t.db_lock;
	};
	
	/**
	 * Manage the go_editable / go_read_only buttons<br/>
	 * Buttons are added to the page_header according to the case
	 */
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