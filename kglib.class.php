<?php
/*
 * Api Version: 0.1
 * Lib Version: 0.2
 * 
 * @
 * 
 */


class kg
{
	private $api_user;
	private $api_key;
	private $api_url;
	
	function __construct($api_user,$api_key)
	{
		$this->api_key=$api_key;
		$this->api_url='http://api.keyground.net/0.3.0/api.php';
	}
	
	function sendRequest($cmd,$params = null)
	{
			
		$post_data = array (
			'api_key'	=> $this->api_key,
			'cmd'		=> $cmd
		);
		
		if(is_array($params)){
			$post_data=array_merge($post_data, $params);
		}
		//var_dump($post_data);
		
		$ch = @curl_init();
		curl_setopt($ch, CURLOPT_URL, $this->api_url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
		$response       = curl_exec($ch);	
		$errno          = curl_errno($ch);
		$error          = curl_error($ch);
		
		if($error!=''){
			echo "*".$response;
			return $error;
		} else {
			return $this->xmlToObject($response);	
		}	
	}
	
	function xmlToObject($xml){
	
		$obj=simplexml_load_string($xml,'SimpleXMLElement', LIBXML_NOCDATA);
		return $obj;
	}
}
