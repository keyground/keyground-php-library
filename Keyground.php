<?php
/*
 * Api Version: 0.3.3
 * SDK Version: 0.4.1
 * 
 */

require_once("KeygroundConfig.php");

class Keyground
{
	private $channelList;
	private $videoList;
	private $channel;
	private $video;
	private $defaultChannel;
	
	private $apiKey;
	private $adapter;
	
	
	public function __construct($apiKey=NULL)
	{
		if($apiKey) $this->apiKey = $apiKey;
		else $this->apiKey = API_KEY;
		
		$this->adapter = new KeygroundAdapter($this->apiKey);
		$this->channelList = new KG_ChannelList($this->adapter);
	}
	
	public function __get($name)
	{
		switch ($name){
			case 'channelList':
				if(!is_object($this->channelList)) {
					$this->channelList = new KG_ChannelList($this->adapter);
				} 
				return $this->channelList;
			
				break;	
			case 'videoList':
				if(!is_object($this->videoList)) {
					$this->videoList = new KG_VideoList($this->adapter);
					$this->videoList->find();
				}
				return $this->videoList;
				
				break;
			default:
				break;
		}
	}
	
	public function getVideo($videoId)
	{
		return new KG_Video($this->adapter,$videoId);
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
		$channels = $this->adapter->sendRequest("getChannels");
		$this->channels = $channels->channels->channel;
	}
	
	public function get($field,$value)
	{
		$xml = $this->channels->xpath('//channel/'.$field.'[.="'.$value.'"]/parent::*');
		$xml = $xml[0];
		return new KG_Channel($xml,$this->adapter);
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
    	//unset($this->video);
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

	//private $params;
	
	
	public $params;
	
	public $page;
	public $per_page;
	public $order;
	public $desc;
	public $lastModified;
	public $objectCount;
	
	public function __construct($adapter)
	{
		$this->position = 0;
		$this->adapter = $adapter;
	}
	
	public function find($queryType=NULL,$params=NULL)
	{
		
		if(is_array($params)) $this->params = $params;
		if(!is_null($queryType)) $this->params['queryType'] = $queryType;
		$this->getVideos();
	}
	
	public function getVideos()
	{
		
		switch($this->params['queryType']){
			case 'by_channel_id':
				$xml = $this->adapter->sendRequest("getVideos",$this->params);
				break;
			case 'popular':
				$xml = $this->adapter->sendRequest("getMostPopularVideos",$this->params);
				break;
			case 'by_tags':
				$xml = $this->adapter->sendRequest("getVideos",$this->params);
				break;	
			case 'all':
				$this->params['all'] = 'true';
				$xml = $this->adapter->sendRequest("getVideos",$this->params);
				break;
			default:
				$xml = $this->adapter->sendRequest("getVideos");		
		}
		
	
		$this->objectCount = count($xml->videos->video); 
		$this->videos = $xml->videos->video;
		
	}
	
	public function __get($name)
	{
		switch ($name){
			case 'video':
				if(is_object($this->video))
					return $this->video;
				else 
					return $this->current();
				break;
			default:
				break;
		}
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
	public $s_description;
	public $description;
	public $isOnline;
	public $videoCount;
	
	
	/*
	 * @todo
	 * $channel->rssLink;
	 */
	private $rssLink;
	
	
	public function __construct($xml,$adapter)
	{
		$this->adapter = $adapter;
		$this->id = (string)$xml->id;
		$this->name = (string)$xml->name;
		$this->s_description = (string)$xml->s_description;
		$this->description = (string)$xml->s_description;
		$this->isOnline = (string)$xml->online;
		$this->videoCount = (string)$xml->video_count;
	}
	
	public function videoList($filter)
	{
		$filter['channelId'] = $this->id;
		$videoList = new KG_VideoList();
		$videoList->find('by_channel_id',$filter);
		
		return $videoList;
	}
	
}


class KG_Video
{
	private $adapter;
	
	public $id;
	public $title;
	public $channelId;
	public $s_description;
	public $description;
	public $upload_time;
	public $thumb;
	public $thumb_s;
	public $thumb_m;
	public $thumb_l;
	public $lastModified;
	public $tags;
	public $duration;
	public $embedCode;
	public $directLink;
	
	/*
	 * @todo: channel Object
	 */
	private $channel;
	
	public function __construct($adapter,$videoId=null,$xmlObj=null)
	{
		
		
		if(isset($xmlObj))
			$videoId = (string)$xmlObj->id;
		else if(is_null($videoId))
			throw new KeygroundExeption('videoId or xmlObj must be provided for getting video object');
		
		$this->adapter = $adapter;
		$xml_data = $this->adapter->sendRequest("getVideoDetails",array('videoId'=>$videoId));
		$xml = $xml_data->video;
		
		
		$this->id = (string)$xml->id;
		$this->title = (string)$xml->title;
		$this->channelId = (string)$xml->channelId;
		$this->s_description = (string)$xml->s_description;
		$this->description = (string)$xml->description;
		$this->uploadTime = (string)$xml->upload_time;
		$this->thumb = (string)$xml->thumb;
		$this->thumb_s = (string)$xml->thumb_s;;
		$this->thumb_m = (string)$xml->thumb_m;
		$this->thumb_l = (string)$xml->thumb_l;
		$this->lastModified = (string)$xml->lastModified;
		$this->tags = (string)$xml->tags;
		$this->duration = (string)$xml->duration;
		$this->embedCode =(string)$xml->embed_code;
		$this->directLink = (string)$xml->embed_code;
	}
	
	public function __get($name)
	{
		switch ($name){
			case 'comments':
				$comments = new KG_CommentList($this->id);
				return $comments;
				break;
			default:
				break;
		}
	}
	
	public function getEmbedCode($width,$height,$autoStart)
	{
		$params = array (
			'videoId' => $this->id,
			'width'	=> $width,
			'height' => $height,
			'auto_start' => $autoStart,
		);
		$xml = $this->adapter->sendRequest("getEmbedCode",$params);
		$this->embedCode = (string)$xml->embed_code;
		return $this->embedCode;
	}
	
	/*
	 * @todo: improve this method
	 */
	public function update($params)
	{
		$params['videoId'] = $this->id;
		$xml = $this->adapter->sendRequest("update",$params);
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
	
	function sendRequest($cmd,$params = null)
	{
			
		$post_data = array (
			'api_key'	=> $this->apiKey,
			'cmd'		=> $cmd
		);
		
		if(is_array($params)){
			$post_data=array_merge($post_data, $params);
		}

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, API_URL);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
		$response       = curl_exec($ch);	
		$errno          = curl_errno($ch);
		$error          = curl_error($ch);
		
		if($error){
			throw new KeygroundException('Keyground API Connection Error. '.$error);
		} else {
			$resObj = $this->xmlToObject($response);
			if($resObj->error){
				throw new KeygroundException($resObj->error.'<br/>');
			}
			return $this->xmlToObject($response);	
		}	
	}
	
	private function xmlToObject($xml){
		try {
			$obj=simplexml_load_string($xml,'SimpleXMLElement', LIBXML_NOCDATA);	
		} catch (Exception $e) {
			throw new KeygroundException('Keyground API Failed. '.$xml);
		}
		
		return $obj;
	}
}

class KeygroundException extends Exception{}