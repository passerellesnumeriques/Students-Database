<?php 
class GoogleAPI {
	
	public static function PlacesTextSearch($query, $country_code, $types = "political") {
		$key = PNApplication::$instance->google->getAPIKey(google::$PLACES_API);
		if ($key == null) {
			PNApplication::error("Google API quota reached, we cannot use the service for now");
			return null;
		}
		set_time_limit(300);
		$url = "https://maps.googleapis.com/maps/api/place/textsearch/json?query=".urlencode($query);
		$url .= "&components=country:".$country_code;
		$url .= "&types=".$types;
		$url .= "&sensor=false";
		$url .= "&key=".$key;
		//echo $url."<br/><br/>\r\n\r\n";
		$c = curl_init($url);
		curl_setopt($c, CURLOPT_RETURNTRANSFER, TRUE);
		curl_setopt($c, CURLOPT_SSL_VERIFYPEER, false);
		$result = curl_exec($c);
		if ($result === false) {
			PNApplication::error("Error connecting to Google: ".curl_error($c));
			curl_close($c);
			return null;
		}
		curl_close($c);
		//echo $result."<br/><br/>\r\n\r\n";
		$result = json_decode($result, true);
		if (isset($result["status"]) && $result["status"] <> "OK" && $result["status"] <> "ZERO_RESULTS") {
			PNApplication::error("Google replied ".$result["status"].(isset($result["error_message"]) ? ": ".$result["error_message"] : ""));
			if ($result["status"] == "OVER_QUERY_LIMIT")
				PNApplication::$instance->google->signalAPIQuotaReached(google::$PLACES_API, $key);
		}
		if (!isset($result["results"])) return array();
		return $result["results"];
		
	}
	
}
?>