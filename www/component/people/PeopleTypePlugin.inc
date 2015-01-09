<?php 
interface PeopleTypePlugin {
	
	public function getSecurityPriority();
	
	public function getId();
	public function getName();
	public function getIcon16();
	public function getIcon32();
	/** Indicates if a people of this type can be created from scratch. For example, a student can, but a contact point cannot because it must be linked to an organization. */
	public function isStandalone();
	/** Indicates if the same people can be re-assigned to the same type (ex: applicant, can move from one campaign to another)
	 * @param integer $people_id
	 * @return string|null null if cannot, else a description of what will be done
	 */
	public function canReassignSameType($people_id);
	public function reassign($people_id, $data);
	
	public function canRead();
	public function canWrite();
	public function canRemove();
	
	public function getTables();
	
	public function getPeopleRowDescription($people);
	
	public function getBuiltinDefaultUserRoles($people);
	
	public function remove($people_id);

}
?>