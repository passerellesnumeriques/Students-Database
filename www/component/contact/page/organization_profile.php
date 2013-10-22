<?php 
class page_organization_profile extends Page {
	public function get_required_rights() { return array(); }
	public function execute(){
		$id = 1;
		?>
		<table>
			<th style = "height:100px">
				<span  id = 'organization_title'></span>
			</th>
			<tr>
				<td style ='vertical-align:top;'>
					<span id='type'></span>
				</td>
				<td style ='vertical-align:top;'>
					<span  id='address'></span>
					<span  id='contact'></span>
				</td>
			</tr>
		</table>
		<?php
		$q = SQLQuery::create()->select("Organization")
				->field("id")
				->where("id = ".$id."");
		$exist = $q->execute();
		if(isset($exist[0]["id"])){
			require_once("contact.inc");
			contact($this,"organization","contact",$id);
			require_once("address.inc");
			address($this,"organization","address",$id);
		}
		require_once("organization_profile.inc");
		organization_profile($this,$id,"type","organization_title");
	}
	
}