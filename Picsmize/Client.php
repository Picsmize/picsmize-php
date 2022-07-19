<?php

namespace Picsmize;
use \Exception;

class Client {

	const API_ENDPOINT = "https://api.picsmize.com";


	/**
	* Client constructor
	*
	* @param {Array} options
	*/
	
	public function __construct($options){

	   /**
	   * Initialize a new cURL session
	   */

	   $this->curl = curl_init();
	   $this->options = $options;
	   return $this;
	}

	/**
	* Sends HTTPS request to the Picsmize API
	*
	* @param {Object} callback
	*/

	public function request($callback) {

		/*
		* Client Error Checker
		*/

		$this->errorHandler();

		/**
		* Define empty headers array
		* wich will be injected together with cURL parameters
		*/

		$requestHeaders = array();

		/**
		* Use JSON request for fetch() mode
		*/

		if (isset($this->options['inputFetch'])) {
			
			$data = json_encode([
				'img_url' => $this->options['img_url'],
				'process' => $this->options['process'],
			]);
			array_push($requestHeaders, 'Content-Type: application/json');
		}

		/**
		* Set curl options
		*/

		$this->curlSetopts('POST', '/image/process', isset($data) ? $data : [], $requestHeaders);

		/**
		* Execute cURL request
		*/

		$result = curl_exec($this->curl);
		$errno = curl_errno($this->curl);
		$error = curl_error($this->curl);

		/**
		* Close a cURL session
		*/

		curl_close($this->curl);

		/**
		* Get Response Header
		*/

		list($messageHeaders, $messageBody) = preg_split("/\r\n\r\n|\n\n|\r\r/", $result, 2);
		$this->lastResponseHeaders = $this->curlParseHeaders($messageHeaders);

		/**
		* Parse the response body when dealing with toJSON() requests
		* and return the data to the user
		*/

		if (isset($this->options['toJSON'])) {

			if(!empty($error))
				throw new Exception($error);

			try {
				$response = json_decode($messageBody, true);
			} catch (Exception $e) {
				throw new Exception('Unable to parse JSON response from the Picsmize API');
			}

			if (!isset($response) || empty($response)){
				throw new Exception('Unable to parse JSON response from the Picsmize API');
			}

			if ($response['status'] !== true) {
				throw new Exception($response['message']);
			}

			return $callback($response);
		}
	}

	/**
	* Get Response Header Value
	* 
	* @param {String} Header Name
	*/

	public function getHeader($index) {
		
		if(!isset($this->lastResponseHeaders) || $this->lastResponseHeaders == null)
			throw new Exception('Cannot be called before an API call.');

		if(isset($this->lastResponseHeaders[$index]))
			return $this->lastResponseHeaders[$index];
		return null;
	}

	/**
	* Check Client Side Error
	* 
	* @response {Exception} Error Message
	*/

	private function errorHandler(){

		if(isset($this->options['errorMessage'])){
			throw new Exception($this->options['errorMessage']);
		}

	   	/**
	   	* Check a API Key
	   	*/

	   	if (!isset($this->options['apiKey']) || $this->options['apiKey'] == '') {
	   		throw new Exception('Requires a valid API key for image processing.');
	   	}

	    /**
	    * Check a cURL version
	    */

	    if(!function_exists('curl_version')) {
	    	throw new Exception('cURL is not enabled. Use fallback method.');
	    }

	    /**
	    * Check a cURL secure or not
	    */

	    $this->curlVersion = curl_version();
	    if(!($this->curlVersion['features'] & CURL_VERSION_SSL)) {
	    	throw new Exception('Your curl version does not support secure connections');
	    }

	    /**
	    * Check if a cURL session has been initialized correctly
	    */

	    if($this->curl == false || $this->curl == null) {
	    	throw new Exception('Unable to initialize a new cURL session. Please check if cURL extension is installed correctly.');
	    }
	    return $this;
	}

	/**
	* Set cUrl Options
	*
	* @returns {Object}
	*/

	private function curlSetopts($method, $path, $data = array(), $requestHeaders = array()) {

		if(!isset($this->options['timeout']))
			$this->options['timeout'] = 60;

		curl_setopt($this->curl, CURLOPT_MAXREDIRS, 3);
		curl_setopt($this->curl, CURLOPT_HEADER, true);
		curl_setopt($this->curl, CURLOPT_SSL_VERIFYHOST, 2);
		curl_setopt($this->curl, CURLOPT_FAILONERROR, false);
		curl_setopt($this->curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($this->curl, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($this->curl, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, $method);
		curl_setopt($this->curl, CURLOPT_URL, self::API_ENDPOINT . $path);
		curl_setopt($this->curl, CURLOPT_USERAGENT, $this->getUserAgent());
		curl_setopt($this->curl, CURLOPT_TIMEOUT, $this->options['timeout']);
		curl_setopt($this->curl, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
		curl_setopt($this->curl, CURLOPT_CONNECTTIMEOUT, $this->options['timeout']);

		if ($method == 'POST') {
			
			curl_setopt($this->curl, CURLOPT_POST, true);
			curl_setopt($this->curl, CURLOPT_POSTFIELDS, $data);
		}

		/**
		* Append custom headers
		*/

		array_push($requestHeaders, "apikey: {$this->options['apiKey']}");
		curl_setopt($this->curl, CURLOPT_HTTPHEADER, $requestHeaders);
		
		/**
		* Validate and set HTTP proxy
		*/

		if (isset($options['proxy'])) {

			curl_setopt($this->curl, CURLOPT_PROXY, $this->options['proxy']['host']);
			curl_setopt($this->curl, CURLOPT_PROXYTYPE, CURLPROXY_HTTP);

			if (isset($components['port'])) {
				curl_setopt($this->curl, CURLOPT_PROXYPORT, $this->options['proxy']['port']);
			}

			$proxyAuth = '';

			if (isset($options['proxy']['user'])) {
				$proxyAuth .= $options['proxy']['user'];
			}

			if (isset($options['proxy']['pass'])) {
				$proxyAuth .= ':' . $options['proxy']['pass'];
			}

			if ($proxyAuth != '') {

				curl_setopt($this->curl, CURLOPT_PROXYAUTH, CURLAUTH_ANY);
				curl_setopt($this->curl, CURLOPT_PROXYUSERPWD, $proxyAuth);
			}
		}

		return $this;
	}

	/**
	* Parse Headers form Response
	*/

	private function curlParseHeaders($messageHeaders){

		$headerLines = preg_split("/\r\n|\n|\r/", $messageHeaders);

		$headers = [];
		list(, $headers['http_status_code'], $headers['http_status_message']) = array_pad(explode(' ', trim(array_shift($headerLines)), 3), 3, null);
		foreach($headerLines as $headerLine) {
			list($name, $value) = array_pad(explode(':', $headerLine, 2), 2, null);
			$name = strtolower($name);
			$headers[$name] = trim($value);
		}

		return $headers;
	}

	/**
	* Generates User-Agent string
	*
	* @returns {String}
	*/

	private function getUserAgent() {
		return 'Picsmize/' . VERSION . ' PHP/' . PHP_VERSION . ' CURL/' . $this->curlVersion['version'];
	}
}