
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<title>Keyground SDK Demo</title>
	
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
</head>
<body>
<?php
require_once 'Keyground.php';

$kg = new Keyground();

/*
foreach($kg->channelList as $channel){
	echo $channel->name."<br>";
}



$filterArray = array(
	'channel_id' => "500414201d41c80bca00001a",
	'order_by'	=> 'uploaded_on',
	'desc'	=> 'true',
	'per_page'	=> '20'
);

$videoList = $kg->getVideoList($filterArray);

//var_dump($videoList);
 
foreach($videoList as $video){
	echo $video->title." | ".$video->uploadedOn."<br>";
}
*/

/*
$filterArray = array(
	'q' => "search query",
);

$videoList = $kg->getVideoList($filterArray);
*/

//var_dump($videoList);

$video = $kg->getVideo("5023d34ac3f4252f2f00029c");

$video->description = "test";
$video->save();

//var_dump($video);

/*
foreach($videoList as $video){
	echo "<br>".$video->title." | ".$video->uploadedOn;
	echo $video->getEmbedCode("640","480");
}
*/


?>
</body>
</html>