<html>
<head>
<meta charset="UTF-8">
</head>
<body>

<?php

require_once '../vendor/autoload.php';

use FastFeed\Factory;
use Guzzle\Http\Client;
use FastFeed\FastFeed;
use FastFeed\Parser\RSSParser;

$fastFeed = NULL;
if(true){ // uso un'istanza di monolog propria di fastfeed
	$fastFeed = Factory::create(); // http://fastfeed.github.io/reference/use.html#factory-way
}else{
	$client = new Client();
	$logger = null; // valorizzare con istanza di monolog di slim
	if($logger==null){
		// TODO: raise an exception
	}
	$fastFeed = new FastFeed($client, $logger);
}
$fastFeed->addFeed('default', 'http://www.iubar.it/category/iubar/fatturazione/feed/rss');
$items = $fastFeed->fetch('default');

print_r($items);

$nl = "<br/ >" . PHP_EOL;
foreach ($items as $item) {
	echo '<h2>' . $item->getName() . '</h2>' . PHP_EOL;
	if(true){ // trasforma il testo in link
		echo addLink($item->getIntro(), $item->getSource()) . $nl;
	}else{
		echo $item->getIntro() . $nl;
		echo $item->getSource() . $nl;
	}
	echo $item->getContent() . $nl;
	echo $item->getDate()->format('d-m-Y H:i:s') . $nl;
// 	foreach ($item->getTags() as $tag){
// 		echo $tag . $nl;
// 	}
	
}

function addLink($txt, $url){
	$html = "<a href=\"" . $url . "\">" . $txt ."</a>";
	return $html;
}

?>
</body>
</html>
