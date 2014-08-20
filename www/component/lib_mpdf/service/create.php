<?php 
class service_create extends Service {
	
	public function getRequiredRights() { return array(); }
	
	public function documentation() {}
	public function inputDocumentation() {}
	public function outputDocumentation() {}
	
	public function getOutputFormat($input) {
		return "Content-Type: application/pdf";
	}
	
	public function execute(&$component, $input) {
		$html = $input["html"];
		define('_MPDF_PATH',realpath("component/lib_mpdf/MPDF")."/");
		include(_MPDF_PATH . "mpdf.php");
		$mpdf=new mPDF('');
		//$mpdf->useSubstitutions = true; // optional - just as an example
		//$mpdf->SetHeader($url.'||Page {PAGENO}');  // optional - just as an example
		//$mpdf->CSSselectMedia='mpdf'; // assuming you used this in the document header
		$mpdf->setBasePath("http://".$_SERVER["SERVER_ADDR"].":".$_SERVER["SERVER_PORT"]."/");
		$mpdf->WriteHTML($html);
		$mpdf->Output($input["filename"].".pdf","D");
	}
	
}
?>