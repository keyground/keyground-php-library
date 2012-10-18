<?php include 'header.php';?>

<?php
require_once '../sdk/Keyground.php';

$kg = new Keyground();

// Recent Videos
$filterArray = array(
	'q' => $_GET['query'],
	'order_by'	=> 'uploaded_on',
	'desc'	=> 'true',
	'per_page'	=> '9'
);

$videoList = $kg->getVideoList($filterArray);
?>
<body>
	<div class="header_container">
		<div id="portal_name">
			<a href="index.php"> Keyground SDK Demo </a>
		</div>
	</div>
	<div class="main_container">
		<div class="main">
			<h1>Searching: <?=$_GET['query']?></h1>
			<div class="line1"></div>

			<div class="video_list">
			<?foreach($videoList as $video){ ?>
				<div class="video_row">
					<div class="video_thumb_img">
						<a href="video.php?videoId=<?=$video->id?>"> <img width="180" height="100"
							src="<?=$video->getThumb()?>"> </a>
					</div>
					<div class="video_title">
						<a href="video.php?videoId=<?=$video->id?>"><?=$video->title?></a>
					</div>
				</div>
			<? } ?>
			</div>
		</div>
		
		<div class="right">
			<div class="search">
				<form action="search.php">
				<input name="query" border="0"
					style="border: 0px; height: 28px; width: 225px; margin-left: 0px; font-weight: bold; font-size: 13px; color: #CCC;"
					type="text" value="Search"
					onkeydown="if(event.keyCode==13) {window.location('search.php?query='+$('#q').val());}"
					 />
			
					<input type="submit" value="" class="search_button_input"/>
				</form>
			</div>
			<div class="channeltxt">
				<h1>Channels</h1>
			</div>
			<div class="clear"></div>
			<div class="menu">
				<ul>
					<? foreach($kg->channelList as $channel){ ?>
					<li><a href="channel.php?channelId=<?=$channel->id?>"><?=$channel->name?> </a></li>
					<? } ?>
				</ul>
			</div>
		</div>

	</div>
	</div>

	<?php include 'footer.php';?><?php
