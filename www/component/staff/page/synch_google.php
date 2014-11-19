<?php 
class page_synch_google extends Page {
	
	public function getRequiredRights() { return array("manage_staff"); }
	
	public function execute() {
		require_once("utilities.inc");
		require_once("component/google/lib_api/PNGoogleDirectory.inc");
		$google = new PNGoogleDirectory();
		$users = $google->getUsersFromOrg("/".PNApplication::$instance->current_domain."/Staff");
		$staff = PNApplication::$instance->people->getPeoplesByType("staff");
		$peoples_ids = array();
		foreach ($staff as $s) array_push($peoples_ids, $s["id"]);
		if (count($peoples_ids) > 0)
			$staff_emails = PNApplication::$instance->contact->getPeoplesContacts($peoples_ids, array("email"));
		else
			$staff_emails = array();
		
		echo "<div style='background-color:white;padding:10px'>";
		// 1 - propose to import PN email which are not yet in our staff contacts
		foreach ($users as $u) {
			$found = null;
			foreach ($staff_emails as $se) if ($se["contact"] == $u->getPrimaryEmail()) { $found = $se; break; }
			if ($found == null) {
				// new email
				$id = $this->generateID();
				echo "<div id='$id'>";
				echo "The EMail <b>".$u->getPrimaryEmail()."</b> is not yet in our staffs' contacts";
				echo "<div style='margin-left:20px'>";
				$matching = array();
				foreach ($staff as $s)
					if (sameString($s["last_name"], $u->getName()->getFamilyName()))
						array_push($matching, $s);
				if (count($matching) == 0)
					echo "But we cannot find any staff with the Last Name <i>".$u->getName()->getFamilyName()."</i>";
				else {
					if (count($matching) > 1) {
						$matching2 = array();
						foreach ($matching as $m) if (sameString($m["first_name"], $u->getName()->getGivenName())) array_push($matching2, $m);
						if (count($matching2) <> 0) $matching = $matching2;
					}
					if (count($matching) == 1) {
						echo "Add this EMail to ";
						echo "<a href='#' onclick=\"addPNEMail('".$u->getPrimaryEmail()."',".$matching[0]["id"].",'$id');return false;\">";
						echo "<i>".$matching[0]["first_name"]." ".$matching[0]["last_name"]."</i>";
						echo "</a>";
					} else {
						echo "Add this Email to<ul>";
						foreach ($matching as $s) {
							echo "<li>";
							echo "<a href='#' onclick=\"addPNEMail('".$u->getPrimaryEmail()."',".$s["id"].",'$id');return false;\">";
							echo $s["first_name"]." ".$s["last_name"];
							echo "</a>";
							echo "</li>";
						}
						echo "</ul>";
					}
				}
				echo "</div>";
				echo "</div>";
			}
		}
		echo "</div>";
?>
<script type='text/javascript'>
function addPNEMail(email,staff_id,div_id) {
	var locker = lock_screen(null,"Adding EMail "+email+"...");
	service.json("contact","add_contact",{owner_type:'people',owner_id:staff_id,contact:{type:'email',sub_type:'PN',contact:email}},function(res) {
		unlock_screen(locker);
		if (res) {
			var div = document.getElementById(div_id);
			div.parentNode.removeChild(div);
		}
	});
}
</script>
<?php 
	}
}
?>