<?php

/*
 * Beta SDK of Keyground v2.
 * 
 * SDK Version: 0.6.2
 * Api Version: 1.0.0
 * 
 */

require_once("KeygroundConfig.php");

class Keyground
{
	public $apiKey;
	
	protected $channelListObject;
	protected $videoList;
	protected $channel;
	protected $video;
	protected $defaultChannel;
	protected $adapter;
	
	public function __construct($apiKey=NULL)
	{
		if($apiKey) $this->apiKey = $apiKey;
		else $this->apiKey = API_KEY;
		
		$this->adapter = new KeygroundAdapter($this->apiKey);
		$this->videoList = new KG_VideoList($this->adapter);
	}
	
	public function __get($name)
	{
		switch ($name){
			case 'channelList':
				if(!is_object($this->channelListObject)) {
					$this->channelListObject = new KG_ChannelList($this->adapter);
				} 
				return $this->channelListObject;
				break;
			case 'defaultChannel':
				if(!is_object($this->channelListObject)) {
					$this->channelListObject = new KG_ChannelList($this->adapter);
				} 
				return $this->getChannel('name','Default');
				
		}
	}
	
	public function getChannel($field,$value)
	{
		$xml = $this->channelList->channels->xpath('//object/'.$field.'[.="'.$value.'"]/parent::*');
		$xml = $xml[0];
		return new KG_Channel($xml,$this->adapter);
	}
	
	public function getVideoList($filterArray)
	{
		$this->videoList->filter($filterArray);
		
		return $this->videoList;
	}
	
	public function getVideo($videoId)
	{
		return new KG_Video($this->adapter,$videoId);
	}
	
	/*
	 * Shortcut function of getVideoList
	 */
	public function search($query)
	{
		
		$filterArray = array(
			'q' => $query
		);

		$this->getVideoList($filterArray);
		
		return $this->videoList;
	}
	
	public function recentVideos()
	{
		$filterArray = array(
			'desc' => 'true'
		);

		$this->getVideoList($filterArray);
		
		return $this->videoList;
	}
}

class KG_ChannelList implements Iterator
{
	private $position;
	private $adapter;
	private $channel;
	public $channels;
	
	public function __construct($adapter)
	{
		$this->position = 0;
		$this->adapter = $adapter;
		$channels = $this->adapter->sendRequest("channels");
		$this->channels = $channels->channels->object;
	}
	
	public function jump($position)
	{
		$this->position = $position;	
	}
	
	public function rewind() {
        $this->position = 0;
    }
    
	public function current() {
		$this->channel = new KG_Channel($this->channels[$this->position],$this->adapter);
        return $this->channel;
    }
    
	public function key() {
        return $this->position;
    }
    
    public function next() {
        ++$this->position;
    }
    
	public function valid() {
        return isset($this->channels[$this->position]);
    }
	
}

class KG_VideoList implements Iterator
{
	private $position = 0;
	private $videos;
	private $video;
	private $adapter;
	public $objectCount;
	
	public function __construct($adapter)
	{
		$this->position = 0;
		$this->adapter = $adapter;
	}
	
	public function filter($filterArray)
	{	
		if(!$filterArray) $filterArray = $this->filterArray;
		if(!array_key_exists('page', $filterArray)) $filterArray['page']=PAGE;
		if(!array_key_exists('per_page', $filterArray)) $filterArray['per_page']=PER_PAGE;
		if(!array_key_exists('order_by', $filterArray)) $filterArray['order_by']=ORDER_BY;
		if(!array_key_exists('desc', $filterArray)) $filterArray['desc']=DESC;
		
		
		if(array_key_exists('q', $filterArray)){
			$xml = $this->adapter->sendRequest("videos/search/".$filterArray['q'],$filterArray);	
		} else {
			$xml = $this->adapter->sendRequest("videos",$filterArray);	
		}
		
		
		$this->objectCount = count($xml->videos->object); 
		$this->videos = $xml->videos->object;
	}
	
	public function jump($position)
	{
		$this->position = $position;	
	}
	
	public function rewind() {
        $this->position = 0;
    }
    
	public function current() {
		$this->video = new KG_Video($this->adapter,'',$xmlObj = $this->videos[$this->position]);
        return $this->video;
    }
    
	public function key() {
        return $this->position;
    }
    
    public function next() {
    	//unset($this->video);
        ++$this->position;
    }
    
	public function valid() {
        return isset($this->videos[$this->position]);
    }
    
    public function __toString()
    {
    	var_dump($this->videos);
    } 
	
}



class KG_Channel
{
	private $adapter;
	
	public $id;
	public $name;
	public $description;
	public $isOnline;
	public $videoCount;
	
	
	public function __construct($xml,$adapter)
	{		
		$this->adapter = $adapter;
		$this->id = (string)$xml->id;
		$this->name = (string)$xml->name;
		$this->description = (string)$xml->description;		
	}	
}


class KG_Video
{
	private $adapter;
	
	public $id;
	public $title;
	public $channelId;
	public $description;
	public $uploadedOn;
	public $lastModified;
	public $tags;
	public $duration;
	public $embedCode;
	public $directLink;
	public $xml;
	protected $channel;
	
	public function __construct($adapter,$videoId=null,$xmlObj=null)
	{
		$this->adapter = $adapter;
		
		if(isset($xmlObj)) {
			$this->xml = $xmlObj;
		} else { 
			if(is_null($videoId)) {
				throw new KeygroundExeption('videoId or xmlObj must be provided for getting video object');
			} else { 
				$xml = $this->adapter->sendRequest("video/".$videoId.'/');
				$this->xml = $xml->video;
			}
		}

		$this->id = (string)$this->xml->id;
		$this->title = (string)$this->xml->title;
		$this->channelId = (string)$this->xml->channel_id;
		$this->description = (string)$this->xml->description;
		$this->uploadedOn = date('Y-m-d H:i:s',strtotime((string)$this->xml->uploaded_on));
		$this->image = (string)$this->xml->image;
		$this->lastModified = date('Y-m-d H:i:s',strtotime((string)$this->xml->last_modified));
		$this->tags = $this->tagList($this->xml->tags);
		$this->duration = (string)$this->xml->duration;
		$this->embedCode = (string)$this->xml->embed_code;
		$this->directLink = (string)$this->xml->direct_link;
	}
	
	
	public function tagList($tagsXML)
	{
		$list =array();
		$tagCount = count($tagsXML->tag); 
		
		$i=0;
		foreach ($tagsXML->tag as $tag){
			$list += $tag;
			if($i!=$tagCount) $list+=",";
			$i++;
		}
		return $list;
	}
	
	public function getEmbedCode($width,$height,$autoStart=false)
	{
		$params = array (
			'videoId' => $this->id,
			'width'	=> $width,
			'height' => $height,
			'auto_start' => $autoStart,
		);
		$xml = $this->adapter->sendRequest("video/embed_code",$params);
		$this->embedCode = (string)$xml->embed_code;
		return $this->embedCode;
	}
	
	public function getThumb($thumbString=null)
	{
		if($thumbString)
			$thumb = $this->xml->thumbs->$thumbString;
		else 
			$thumb = (string)$this->xml->thumbs->i200x115;
		
		return $thumb; 
	}
	
	public function save()
	{
		$params['video_id'] = $this->id;
		$postParams = array(
			'title' => $this->title,
			'description' => $this->description,
			'tags'		=> $this->tags,
			'direct_link' => $this->directLink
		);
		$xml = $this->adapter->sendRequest("video/".$this->id.'/',$params,$postParams);
	}
	
}


class KeygroundAdapter
{
	public $apiKey; 
	
	public function __construct($apiKey=NULL)
	{
		if($apiKey) $this->apiKey = $apiKey;
		else $this->apiKey = API_KEY;
	}
	
	function sendRequest($cmd,$params = null,$postParams=null)
	{
		$url=API_URL.$cmd."?api_key=".$this->apiKey;

		if($params){
			foreach($params as $key => $param){
				$url=$url."&".$key.'='.$param;
			}
		}

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		
		//var_dump($postParams);
		if(is_array($postParams)){
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $postParams);
		}
		
		$response       = curl_exec($ch);	
		$errno          = curl_errno($ch);
		$error          = curl_error($ch);
		
		
		echo $url;
		echo $response;
		
		if($error){
			throw new KeygroundException('Keyground API CURL Connection Error. '.$error.' '.$errno);
		} else {
			$resObj = $this->xmlToObject($response);
			
			if($resObj->errors){
				//var_dump($resObj->errors);		
				
				$message='';
				foreach ($resObj->errors->children() as $errorLine){
					$message.=$errorLine->getName().': '.$errorLine->value.'<br/>';
				}
				
				throw new KeygroundException($message);
			}

			return $this->xmlToObject($response);	
		}
	}
	
	private function xmlToObject($xml)
	{
		try {
			$obj=simplexml_load_string($xml,'SimpleXMLElement', LIBXML_NOCDATA);	
		} catch (Exception $e) {
			throw new KeygroundException('Keyground API Failed. '.$xml);
		}
		
		return $obj;
	}

}

class KeygroundException extends Exception{}
?>