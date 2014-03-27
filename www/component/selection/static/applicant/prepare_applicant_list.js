function prepare_applicant_list(){
	var t = this;
	
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
	
	t.addFilter = function(name, value, force){
		if(value == null || (typeof value == "string" && value.length == 0))
			value = "NULL";
		if(value == "set")
			value = "NOT_NULL";
		t._filters.push({category:"Selection", name:name, data:{value:value}, force:force});
	};
	
	t.makeApplicantsSelectable = function(){
		t._selectable = true;
	};
	
	t.makeRowNotClickable = function(){
		t._clickable = false;
	};
	
	t.lockSelectionForApplicant = function(id){
		t._lockedApplicants.push(id);
	};
	
	t.preselectApplicants = function(ids){
		for(var i = 0; i < ids.length; i++)
			t.preselectApplicant(ids[i]);
	};
	
	t.lockSelectionForApplicants = function(ids){
		for(var i = 0; i < ids.length; i++)
			t.lockSelectionForApplicant(ids[i]);
	};
	
	t.preselectApplicant = function(id){
		t._preselectedApplicants.push(id);
	};
	
	t.forbidApplicantCreation = function(){
		t._canCreate = false;
	};
	
	t.forbidApplicantImport = function(){
		t._canImport = false;
	};
	
	t._filters = [];
	t._selectable = false;
	t._lockedApplicants = [];
	t._preselectedApplicants = [];
	t._canCreate = true;
	t._canImport = true;
	t._clickable = true;

}