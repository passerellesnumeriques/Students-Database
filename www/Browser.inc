<?php 
class Browser {
	
	public function __construct($user_agent = null) {
		if ($user_agent == null) $user_agent = $_SERVER["HTTP_USER_AGENT"];
		$this->detect($user_agent);
	}
	
	public function isOldChrome($max_ver) {
		return $this->Chrome > 0 && $this->Chrome < $max_ver;
	}
	public function isOldSafari($max_ver) {
		return $this->SafariBrowser > 0 && $this->SafariBrowser < $max_ver;
	}
	public function isOldFirefox($max_ver) {
		return $this->FireFox > 0 && $this->FireFox < $max_ver;
	}
	public function isOldIE($max_ver) {
		return $this->IE > 0 && $this->IE < $max_ver;
	}
	
	/** information from the UserAgent */
	public  $agent_infos = array();
	/** version of Internet Explorer, or 0 */
	public $IE = 0;
	/** version of Chrome, or 0 */
	public $Chrome = 0;
	/** version of WebKit, or 0 */
	public $WebKit= 0;
	/** version of Safari-compatible, or 0 */
	public $Safari= 0;
	/** version of Safari, or 0 */
	public $SafariBrowser= 0;
	/** version of FireFox, or 0 */
	public $FireFox= 0;
	/** version of Opera-compatible, or 0 */
	public $Opera= 0;
	/** version of Opera, or 0 */
	public $OperaBrowser= 0;
	/** version of Presto, or 0 */
	public $Presto= 0;
	/** version */
	public $Version= 0;
	
	/** Detect the navigator type and version */
	private function detect($s) {
		do {
			$i = strpos($s, "/");
			if ($i === false) break;
			$name = trim(substr($s, 0, $i));
			$s = substr($s, $i+1);
			$i = strpos($s, " ");
			if ($i === false) $i = strlen($s);
			$version = trim(substr($s, 0, $i));
			$s = substr($s, $i+1);
			$infos = array();
			if ($s <> "" && substr($s,0,1) == "(") {
				$i = strpos($s, ")", 1);
				if ($i > 0) {
					$ss = trim(substr($s,1,$i-2));
					$s = substr($s, $i+1);
					while ($ss <> "") {
						$i = strpos($ss, ';');
						if ($i === false) $i = strlen($ss);
						array_push($infos, trim(substr($ss, 0, $i)));
						$ss = trim(substr($ss,$i+1));
					}
				}
			}
			array_push($this->agent_infos, array("name"=>$name, "version"=>$version, "infos"=>$infos));
		} while ($s <> "");
		$this->fill();
	}
	
	/** Fill navigator type and version from agent infos */
	private function fill() {
		$rv = null;
		$hasNav = false;
		for ($i = 0; $i < count($this->agent_infos); ++$i) {
			$a = $this->agent_infos[$i];
			switch (strtolower($a["name"])) {
				case "mozilla":
					for ($j = 0; $j < count($a["infos"]); ++$j) {
						$s = $a["infos"][$j];
						if (substr($s,0,3) == "rv:") {
							$rv = floatval(substr($s,3));
							continue;
						}
						if (strtolower(substr($s,0,5)) <> "msie ") continue;
						$this->IE = floatval(substr($s,5));
						$hasNav = true;
					}
					break;
				case "chrome": $this->Chrome = floatval($a["version"]); $hasNav = true; break;
				case "applewebkit": $this->WebKit = floatval($a["version"]); $hasNav = true; break;
				case "safari": $this->Safari = floatval($a["version"]); $hasNav = true; break;
				case "firefox": $this->FireFox = floatval($a["version"]); $hasNav = true; break;
				case "opera": $this->Opera = floatval($a["version"]); $hasNav = true; break;
				case "presto": $this->Presto = floatval($a["version"]); $hasNav = true; break;
				case "version": $this->Version = floatval($a["version"]); $hasNav = true; break;
			}
		}
		if ($this->Safari > 0 && $this->Version > 0) $this->SafariBrowser = $this->Version;
		if ($this->Opera > 0) { if ($this->Version > 0) $this->OperaBrowser = $this->Version; else $this->OperaBrowser = $this->Opera; }
		if (!$hasNav && $rv <> null) {
			// TODO
		}
	}
	
	public function getName() {
		if ($this->IE > 0) return "Internet Explorer ".$this->IE;
		if ($this->Chrome > 0) return "Google Chrome ".$this->Chrome;
		if ($this->FireFox > 0) return "Mozilla FireFox ".$this->FireFox;
		if ($this->SafariBrowser > 0) return "Apple Safari ".$this->SafariBrowser;
		if ($this->OperaBrowser > 0) return "Opera ".$this->OperaBrowser;
		return "Unknown";
	}
	
}
global $browser;
$browser = new Browser();
?>