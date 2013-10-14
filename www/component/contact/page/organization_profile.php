<?php 
class page_organization_profile extends Page {
	public function get_required_rights() { return array(); }
	public function execute(){
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
		require_once("contact.inc");
		contact($this,"organization","contact","1");
		require_once("address.inc");
		address($this,"organization","address","1");
		require_once("organization_profile.inc");
		organization_profile($this,"1","type","organization_title");
	}
	
}