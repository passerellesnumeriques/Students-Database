/**
 * Get the POST data to prepare the applicant/list page with the matching requirements (filters, clickable, selectable...)
 */
function prepare_applicant_list(){
	var t = this;
	
	/**
	 * Get the object to post to the page
	 * @returns {Object} to be transmitted to the page via POST method
	 */
	t.getDataToPost = function(){
		return {
			filters:t._filters,
			selectable:t._selectable,
			applicants_locked:t._lockedApplicants,
			applicants_preselected:t._preselectedApplicants,
			can_create:t._canCreate,
			can_import:t._canImport,
			clickable:t._clickable
		};
	};
	
	/**
	 * Add a filter on the datalist
	 * @param {String} name the datadisplay name
	 * @param {String | NULL} value if null, the filter will keep the rows where the value is NULL, if "set", the filter will keep the rows where the value is not null, else, will filter according to the given value
	 * @param {Boolean} force true if the filter shall be forced (not removable)
	 */
	t.addFilter = function(name, value, force){
		if(value == null || (typeof value == "string" && value.length == 0))
			value = "NULL";
		if(value == "set")
			value = "NOT_NULL";
		t._filters.push({category:"Selection", name:name, data:{value:value}, force:force});
	};
	
	/**
	 * Make the applicants selectable
	 * By default, the applicants are not selectable
	 */
	t.makeApplicantsSelectable = function(){
		t._selectable = true;
	};
	
	/**
	 * Make the rows not clickable (not a link)
	 * By default, the rows are clickable
	 */
	t.makeRowNotClickable = function(){
		t._clickable = false;
	};
	
	/**
	 * Disable the checkbox for the given applicant
	 * @param {Number} id applicant people ID
	 */
	t.lockSelectionForApplicant = function(id){
		t._lockedApplicants.push(id);
	};
	
	/**
	 * Preselect several applicants
	 * @param {Array} ids array of applicants people IDs
	 */
	t.preselectApplicants = function(ids){
		for(var i = 0; i < ids.length; i++)
			t.preselectApplicant(ids[i]);
	};
	
	/**
	 * Disable the checkbox for the given applicants
	 * @param {Array} ids array of applicants people IDs
	 */
	t.lockSelectionForApplicants = function(ids){
		for(var i = 0; i < ids.length; i++)
			t.lockSelectionForApplicant(ids[i]);
	};
	
	/**
	 * Preselect one applicant
	 * @param {Number} id applicant people ID
	 */
	t.preselectApplicant = function(id){
		t._preselectedApplicants.push(id);
	};
	
	/**
	 * Prevent the user from creating an applicant manually
	 * By default, the user is allowed
	 */
	t.forbidApplicantCreation = function(){
		t._canCreate = false;
	};
	
	/**
	 * Prevent the user from importing any applicant
	 * By default, the user is allowed
	 */
	t.forbidApplicantImport = function(){
		t._canImport = false;
	};
	
	/** Private attributes and methods */
	
	t._filters = [];
	t._selectable = false;
	t._lockedApplicants = [];
	t._preselectedApplicants = [];
	t._canCreate = true;
	t._canImport = true;
	t._clickable = true;

}