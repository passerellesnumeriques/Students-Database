<?php 
class HTTPClient {
	
	private $proxy_conf = "conf/proxy";
	
	public function __construct() {
	}
	
	public function setProxyConfLocation($path) {
		$this->proxy_conf = $path;
	}
	
	/**
	 * @param HTTPRequest $request
	 */
	public function send($request, $max_redirect = 20) {
		$c = curl_init($request->getURL());
		if (file_exists($this->proxy_conf)) include($this->proxy_conf);
		curl_setopt($c, CURLOPT_RETURNTRANSFER, TRUE);
		curl_setopt($c, CURLOPT_HEADER, true);
		curl_setopt($c, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($c, CURLOPT_FOLLOWLOCATION, FALSE);
		curl_setopt($c, CURLOPT_CONNECTTIMEOUT, 20);
		curl_setopt($c, CURLOPT_TIMEOUT, 300);
		if ($request->getPostedForm() <> null)
			curl_setopt($c, CURLOPT_POSTFIELDS, $request->getPostedForm());
		$headers = array();
		foreach ($request->getHeaders() as $name=>$value)
			array_push($headers, $name.": ".$value);
		curl_setopt($c, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($c, CURLOPT_CUSTOMREQUEST, $request->getMethod());
		set_time_limit(350);
		$result = curl_exec($c);
		if ($result === false || $result == "") {
			$error = curl_error($c)." (#".curl_errno($c).")";
			curl_close($c);
			throw new Exception($error);
		}
		curl_close($c);
		$response = new HTTPResponse($result);
		if ($response->isRedirected() && $max_redirect > 0) {
			$request->followRedirection($response->getRedirectionLocation());
			$responses = $this->send($request, $max_redirect-1);
			array_splice($responses, 0, 0, array($response));
			return $responses;
		}
		if ($request->isRangeRequested() && $response->getStatus() == 200)
			throw new Exception("Invalid response from server ".$request->getHostName().": it does not support progressive download.");
		return array($response);
	}
	
}

class HTTPRequest {
	private $method = "GET";
	private $secure = false;
	private $hostname = null;
	private $port = 80;
	private $path = "/";
	
	private $headers = array();
	
	private $posted_form = null;
	
	public function getMethod() {
		return $this->method;
	}
	
	public function setMethod($method) {
		$this->method = $method;
	}
	
	public function getHostName() {
		return $this->hostname;
	}

	public function setURL($url) {
		$i = strpos($url, "://");
		if ($i === false) {
			$this->secure = false;
		} else {
			$protocol = strtolower(substr($url, 0, $i));
			if ($protocol == "https")
				$this->secure = true;
			else 
				$this->secure = false;
			$url = substr($url, $i+3);
		}
		$i = strpos($url, "/");
		$j = strpos($url, ":");
		if ($j > 0 && ($j < $i || $i === false)) {
			$this->hostname = substr($url, 0, $j);
			if ($i > 0) $this->port = intval(substr($url,$j+1, $i-$j-1));
			else $this->port = intval(substr($url, $j+1)); 
		} else {
			$this->hostname = $i > 0 ? substr($url, 0, $i) : $url;
			$this->port = $this->secure ? 443 : 80;
		}
		if ($i === false) $this->path = "/";
		else $this->path = substr($url, $i);
	}
	
	public function followRedirection($location) {
		$i = strpos($location, "://");
		if ($i > 0) $this->setURL($location);
		else $this->path = $location;
	}
	
	public function setHTTPAddress($hostname, $path = "/", $port = 80) {
		$this->hostname = $hostname;
		$this->$path = $path;
		$this->port = $port;
		$this->secure = false;
	}
	public function setHTTPSAddress($hostname, $path = "/", $port = 443) {
		$this->hostname = $hostname;
		$this->$path = $path;
		$this->port = $port;
		$this->secure = true;
	}
	
	public function postForm($fields) {
		$this->method = "POST";
		$this->posted_form = $fields;
	}
	public function getPostedForm() {
		return $this->posted_form;
	}
	
	public function setHeader($name, $value) {
		$this->headers[$name] = $value;
	}
	
	public function addHeaders($headers) {
		foreach ($headers as $name=>$value)
			$this->setHeader($name, $value);
	}
	
	public function removeHeader($name) {
		unset($this->headers[$name]);
	}
	
	public function getHeaders() {
		return $this->headers;
	}
	
	public function downloadRange($from, $to) {
		$this->headers["Range"] = "bytes=".$from."-".$to;
	}
	
	public function isRangeRequested() {
		return array_key_exists("Range", $this->headers);
	}
	
	public function getURL() {
		$url = $this->secure ? "https" : "http";
		$url .= "://";
		$url .= $this->hostname;
		$url .= ":".$this->port;
		$url .= $this->path;
		return $url;
	}
	
	public function getAsString() {
		$s = $this->method." ".$this->path."\n";
		foreach ($this->headers as $name=>$value) $s .= $name.": ".$value."\n";
		$s .= "\n";
		return $s;
	}
}

class HTTPResponse {
	private $protocol = null;
	private $status = 0;
	private $message = "";
	private $headers = array();
	private $body = null;
	
	public function __construct($data) {
		do {
			$continue = false;
			$i = strpos($data, "\r\n\r\n");
			if ($i === false) $this->parseHeaders($data);
			else {
				$this->parseHeaders(substr($data, 0, $i+2));
				if ($this->status == 100) {
					$data = substr($data, $i+4);
					$continue = true;
					continue;
				}
				$this->setBody(substr($data, $i+4));
			}
		} while ($continue);
	}
	
	public function getStatus() { return $this->status; }
	public function getStatusMessage() { return $this->message; }
	public function getBody() { return $this->body; }
	public function getHeader($name) {
		return @$this->headers[strtolower($name)];
	}
	
	public function parseHeaders($data) {
		$i = strpos($data, "\n");
		if ($i === false) return;
		$line = trim(substr($data, 0, $i));
		$data = substr($data, $i+1);
		$i = strpos($line, " ");
		if ($i > 0) {
			$this->protocol = substr($line, 0, $i);
			$line = substr($line, $i+1);
			$i = strpos($line, " ");
			if ($i === false) $this->status = intval(trim($line));
			else {
				$this->status = intval(substr($line, 0, $i));
				$this->message = substr($line, $i+1);
			}
		}
		$lines = explode("\n", $data);
		foreach ($lines as $line) {
			$line = trim($line);
			$i = strpos($line, ":");
			if ($i === false) continue;
			$name = trim(substr($line,0,$i));
			$value = trim(substr($line,$i+1));
			if ($name == "") continue;
			$this->headers[strtolower($name)] = $value;
		}
	}
	
	public function setBody($data) {
		$this->body = $data;
	}
	
	public function isRedirected() {
		if ($this->status >= 300 && $this->status < 400 && isset($this->headers["location"])) return true;
	}
	
	public function getRedirectionLocation() {
		return $this->headers["location"];
	}
	
	public function getAsString() {
		$s = $this->protocol." ".$this->status." ".$this->message."\n";
		foreach ($this->headers as $name=>$value) $s .= $name.": ".$value."\n";
		$s .= "\n";
		$s .= $this->body;
		return $s;
	}
}
?>