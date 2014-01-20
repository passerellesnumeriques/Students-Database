<?php 
function capitalizeWordsFirstLetter($s) {
	$last_letter = false;
	for ($i = 0; $i < strlen($s); $i++) {
		$c = substr($s, $i, 1);
		if (ctype_alpha($c)) {
			if (!$last_letter)
				$s = substr($s, 0, $i).strtoupper($c).substr($s, $i+1);
			else
				$s = substr($s, 0, $i).strtolower($c).substr($s, $i+1);
			$last_letter = true;
		} else
			$last_letter = false;
	}
	return $s;
}
?>