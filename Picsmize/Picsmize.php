<?php

namespace Picsmize;
use \Exception;

require __DIR__ . '/Client.php';

/**
* Current API Version
*/

const VERSION = '1.0.0';

/**
* Image Processing With Picsmize
*/

class Picsmize {

	/*
	* Image Compress Level
	*/

	const COMPRESS_LOW = 'low';
	const COMPRESS_MEDIUM = 'medium';
	const COMPRESS_HIGH = 'high';

    /*
    * Image Resize Mode
    */

    const RESIZE_AUTO = 'auto';
    const RESIZE_FIT = 'fit';
    const RESIZE_FILL = 'fill';
    const RESIZE_EXACT = 'exact';

    /*
    * Image Crop Mode
    */

    const CROP_RECT = 'rect';
    const CROP_RATIO = 'ratio';
    const CROP_FACE = 'face';

    /*
    * Image Flip Type
    */

    const FLIP_HORIZONTAL = 'horizontal';
    const FLIP_VERTICAL = 'vertical';

    /*
    * Image Filter Type
    */

    const FILTER_DUOTONE = 'duotone';
    const FILTER_BLUR = 'blur';
    const FILTER_SEPIA = 'sepia';
    const FILTER_PIXELLATE = 'pixellate';

    /*
    * Image Manipulation Options
    */
    
    private $options;

	/**
    * Default constructor
    *
    * @param {String} apiKey
    */

	public function __construct($apiKey = '') {
		
		if ($apiKey == '') {
			$this->error('Requires a valid API key for image processing.');
		}

		$this->options['apiKey'] = $apiKey;
		return $this;
	}

	/**
    * Provides a URL of the image for processing
    *
    * @param {String} url
    */

	public function fetch($url = ''){

		if ($url == '' || !filter_var($url, FILTER_VALIDATE_URL)) {
			$this->error('fetch(string) method requires a valid file URL passed as an argument');
		}

		$this->options['inputFetch'] = true;
		$this->options['img_url'] = $url;

		return $this;
	}

	/**
    * Compress an image
    *
    * @param {Array} data
    */

	public function compress($compressLevel = '') {
		$this->options['process']['compress']['level'] = $compressLevel;
		return $this;
	}

    /**
    * Resizes an image
    *
    * @param {String} Mode Type, {Array} Options
    */

    public function resize($modeType = self::RESIZE_AUTO, $options = array()) {
    	
    	$this->options['process']['resize']['mode'] = $modeType;
    	if(isset($options) && !empty($options))
    		$this->options['process']['resize'] = array_merge($this->options['process']['resize'], $options);
    	return $this;
    }

    /**
    * Scales an image
    *
    * @param {Number} Scale Size
    */

    public function scale($size = null) {
    	$this->options['process']['scale']['size'] = $size;
    	return $this;
    }

    /**
    * Crop an image
    *
    * @param {String} Mode Type, {Array} Options
    */

    public function crop($modeType = '', $options = array()) {
    	$this->options['process']['crop']['mode'] = $modeType;
    	if(isset($options) && !empty($options))
    		$this->options['process']['crop'] = array_merge($this->options['process']['crop'], $options);
    	return $this;
    }

    /**
    * Flip an image
    *
    * @param {String} Type
    */

    public function flip($flipType = '') {
    	$this->options['process']['flip'][$flipType] = true;
    	return $this;
    }

    /**
    * Filter an image
    *
    * @param {String} filter type, {Array} options
    */

    public function filter($type = null, $options = array()) {
    	$this->options['process']['filter'][$type] = $options;
    	return $this;
    }

    /**
    * Watermark an image
    *
    * @param {String} filter type, {Array} options
    */

    public function watermark($options = array()) {
        $this->options['process']['watermark'] = $options;
        return $this;
    }

    /**
    * Get the requested API count
    *
    * @param {Interger} Count
    */

    public function callsMade(){
    	return $this->callLimit() - $this->callLeft();
    }

	/**
    * Get the API request limit
    *
    * @param {Interger} Limit
    */

	public function callLimit(){
		return (int) $this->client->getHeader('x-ratelimit-limit');
	}

	/**
    * Get the remaining API request
    *
    * @param {Interger} Remaning
    */

	public function callLeft(){
		return (int) $this->client->getHeader('x-ratelimit-remaining');
	}

	/**
    * Sends a standard request to the API
    * and returns a JSON response
    *
    * @param {Function} callback
    */

	public function toJSON($callback = array()) {

		if (gettype($callback) != 'object') {
			$this->error('toJSON(fn) method requires a callback function');
		}

		if (!isset($this->options['inputFetch'])) {
			$this->error('No file input has been specified with either fetch(string) method');
		}

		$this->options['toJSON'] = true;
		$this->request($callback);
	}

	/**
    * Sets a timeout for HTTP requests
    *
    * @param {Integer} timeout
    */

	public function setTimeout($timeout = 30) {
		$this->options['timeout'] = $timeout;
		return $this;
	}


    /**
    * Sets a proxy for HTTP requests
    *
    * @param {String} proxy
    */

    public function setProxy($proxy = '') {

    	$parseUrl = parse_url($options['proxy']);
    	if (!$parseUrl || !isset($parseUrl['host']) || !filter_var($proxy, FILTER_VALIDATE_URL)) {
    		$this->error('setProxy() method expects a valid HTTP proxy string as a parameter');
    	}

    	if ($proxy != '') {
    		$this->options['proxy'] = $parseUrl;
    	}

    	return $this;
    }

    /**
    * Sent a request form Picsmize
    *
    * @param {Array} response
    */

    private function request($callback){

    	try{

    		$this->client = new Client($this->options);
    		$this->client->request(function ($response) use ($callback){

    			return $callback(array_merge($response, [
    				'Limit' => $this->callLimit(),
    				'Remaining' => $this->callLeft()
    			]));
    		});
    	}catch(Exception $ex){

    		$exceptionResponse = [
    			'status' => false,
    			'message' => $ex->getMessage()
    		];

    		try{

    			$exceptionResponse = array_merge($exceptionResponse, [
    				'Limit' => $this->callLimit(),
    				'Remaining' => $this->callLeft()
    			]);
    		}catch(Exception $headerEx){};

    		return $callback($exceptionResponse);
    	}
    }

	/**
    * Provides a processing error message
    *
    * @param {String} Message
    */

	private function error($message){

		if(!isset($this->options['errorMessage']))
			$this->options['errorMessage'] = $message;

		return $this;
	}
}

?>