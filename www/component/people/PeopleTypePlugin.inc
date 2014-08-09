<?php 
interface PeopleTypePlugin {
	
	public function getSecurityPriority();
	
	public function getId();
	public function getName();
	/** Indicates if a people of this type can be created from scratch. For example, a student can, but a contact point cannot because it must be linked to an organization. */
	public function isStandalone();
	
	public function canRead();
	public function canWrite();
	public function canRemove();
	
	public function getTables();
	
	public function getPeopleRowDescription($people);
	
	public function getBuiltinDefaultUserRoles($people);
}
?>