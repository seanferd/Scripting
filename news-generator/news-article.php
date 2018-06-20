<?php
/*
*         news-article.php
*           Sean Ford
*           9/26/2016
*
*  This was written to try and automate most of the news release pressure that
*  we get - they come in as high priority and generally need to be completed
*  within the day (sometimes hours). I started this as a side project
*  to re-orient myself with some of the intricacies of PHP programming.
*
*  This should connect to Gather Content using an API key and user credentials,
*  pull down all the relevant fields and images from the news article. then
*  it should resize the local image to the 3 sizes we need (320x240, 800x600
*  and 1280x1024), as well as keep the orignal. We should also check to see
*  if they are in landscape or portrait and resize accordingly.
*
*  Finally, once all the elements are pulled locally and successfully
*  manipulated, we place all of this into a local html file and push all
*  files to the actual server. This will make the page "live", but it won't be
*  linked to. The index.php and feed-10-v2.php files will likely need
*  to be manually edited (partially as a safeguard, partially as they
*  would be difficult to automatically parse). Then we should be able to proof
*  the generated page and make any changes.
*
*  NOTES:
*  This should return a warning if more than one image is returned (e.g. a
*  slideshow is to be used) - I don't plan on trying to automate this because
*  the decision on how to handle is usually up to the content creator
 */

//set charset to try and remove smart quotes from post body
header('Content-Type: text/html; charset=utf-8');
//set timezone for date function
date_default_timezone_set ( 'America/Chicago' );

//TODO: featured images and image assets may not match!

include( "debug.php/debug.php" );

//This should work to pull a news article via its item ID once it's assigned
//via GC, just pull the ID from the link and input it here. Probably from STDIN

$username = 'user';
$apikey = '';

//if we receieved an item id via command line, set it
//maybe look into parsing an entire url to pull this out
if( isset( $argv[1] ) ) {
  $item_id = $argv[1];
} else {
  $item_id = '3429285';
  //die( 'Missing Gather Content item ID, cannot continue' );
}

$date = date( 'F j, Y' );
$dateForFiles = date( 'Ymd' );

//create empty directory for image storage
if( !is_dir( 'articles/_' . $dateForFiles ) ) {
  mkdir( 'articles/_' . $dateForFiles );
} else {
  echo( 'This directory exists, but images will be moved here anyway' );
}

//get news item
$ch = curl_init();

curl_setopt( $ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
curl_setopt( $ch, CURLOPT_HTTPHEADER, array('Accept: application/vnd.gathercontent.v0.5+json'));
curl_setopt( $ch, CURLOPT_USERPWD, $username . ':' . $apikey);
//Get News Item
curl_setopt( $ch, CURLOPT_URL, 'https://api.gathercontent.com/items/' . $item_id);
curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );

//Get News Item Files
$ch_files = curl_init();

curl_setopt( $ch_files, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
curl_setopt( $ch_files, CURLOPT_HTTPHEADER, array('Accept: application/vnd.gathercontent.v0.5+json'));
curl_setopt( $ch_files, CURLOPT_USERPWD, $username . ':' . $apikey);
curl_setopt( $ch_files, CURLOPT_URL, 'https://api.gathercontent.com/items/' . $item_id . '/files');
curl_setopt( $ch_files, CURLOPT_RETURNTRANSFER, true );

//an array of the fields from the gc post
$response = json_decode( curl_exec( $ch ) );
//an array of files attached to the gc post
$files = json_decode( curl_exec( $ch_files ) );

curl_close( $ch );
curl_close( $ch_files );

//get files !!this might make more sense to move the curl options outside the
//loop, then loop through the curl connection to pull down the images - further
//maybe it would make the most sense as a class
//that way we aren't building and rebuilding the options below multiple times
//the only thing that changes is the URL - and auth for images
//though many of our news articles only have a single image...
foreach( $files->data as $file ) {

  $host = $file->url;
  $filename = $file->filename;
  $link = $host . "/" . $filename;

  //strip spaces and move to all lower case before we create the file
  $filename = str_replace( ' ', '-', strtolower( $filename ) );

  //this uses the apikey and credentials to connect to GC's AWS server(I think)
  //and lets us pull the image(s) that have been defined for us
  //we set the same options as above, but have to tell aws that we have
  //credentials, (BASIC) auth did not work. then we open a file with the
  //filename returned from GC and curl populates it with the actual image
  //from awsah aasdf
  $curl = curl_init();
  curl_setopt( $curl, CURLOPT_URL, $host );
  curl_setopt( $curl, CURLOPT_RETURNTRANSFER, 1 );
  curl_setopt( $curl, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
  curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_ANY);
  curl_setopt( $curl, CURLOPT_HTTPHEADER, array('Accept: application/vnd.gathercontent.v0.5+json'));
  //credentials
  curl_setopt( $curl, CURLOPT_USERPWD, $username . ':' . $apikey);
  //empty file create
  $fp = fopen( "articles/_" . $dateForFiles . "/" . $filename, "w");
  //the file that the transfer should write to (defaults to stdout)
  curl_setopt($curl, CURLOPT_FILE, $fp);

  $file = curl_exec ($curl);
  curl_close ($curl);

  //dump file from aws into empty local file for manipulation
  fwrite( $fp, $file );
  fclose( $fp );

  //attempt to only convert image files (not PDF or videos)
  $exp           = explode( ".", $filename );
  $extension     = end( $exp );
  echo "EXTENSION=" . $extension;
  if( $extension == ".jpg" || $extension == ".jpeg" ) {
    $im = new IMagick( "articles/_" . $dateForFiles . "/" . $filename );

    //strip file extension so it's not repeated later
    $find = array( '/.jpg/', '/.jpeg/' );
    $file = preg_replace( $find, '', $filename );

    //TODO: I *think* these are resizing based on the resize above it INSTEAD
    //of the original, causing some distortion if the order is wrong (320 first).
    //But this way may save some time on the resize if it doesn't have to go through
    //the full size image more than once. Will need to review the output in the
    //future to determine if this should change
    if( $im->getImageWidth() > $im->getImageHeight() ) {
      $im->resizeImage( 1280, 1024, imagick::FILTER_LANCZOS, 0.9 );
      $im->writeImage( "articles/_" . $dateForFiles . "/" . $file . "-1280x1024.jpg" );
      $im->resizeImage( 800, 600, imagick::FILTER_LANCZOS, 0.9 );
      $im->writeImage( "articles/_" . $dateForFiles . "/" . $file . "-800x600.jpg" );
      $im->resizeImage( 320, 240, imagick::FILTER_LANCZOS, 0.9 );
      $im->writeImage( "articles/_" . $dateForFiles . "/" . $file . "-320x240.jpg" );
    } else {
      $im->resizeImage( 1024, 1280, imagick::FILTER_LANCZOS, 0.9 );
      $im->writeImage( "articles/_" . $dateForFiles . "/" . $file . "-1024x1280.jpg" );
      $im->resizeImage( 600, 800, imagick::FILTER_LANCZOS, 0.9 );
      $im->writeImage( "articles/_" . $dateForFiles . "/" . $file . "-600x800.jpg" );
      $im->resizeImage( 240, 320, imagick::FILTER_LANCZOS, 0.9 );
      $im->writeImage( "articles/_" . $dateForFiles . "/" . $file . "-240x320.jpg" );
    }

    $im->destroy();
  }
}

//set news item variables
foreach( $response->data->config[0]->elements as $index ) {

	switch( $index->name ) {

		case "el1423834242747":
			$post_title = strip_tags( $index->value );
			continue;

		case "el1469460517625":
			$image_alt = strip_tags( $index->value );
			break;

    //for some reason the label on GC has a space at the end
		case "el1423834617917":
			$excerpt = strip_tags( $index->value );
			break;

		case "el1423834833347":
			//leave the tags here as GC wraps in <p></p> tags
			//but, remove the "smart" quotes
      $post_body = iconv( 'UTF-8', 'ASCII//TRANSLIT', $index->value );
			break;

		case "el1469023825242":
			$call_to_action = strip_tags( $index->value );
			break;

    default:
      //echo( "Fell through on " . $index->label );
      //echo( $index->value );
      continue;
	}

}

foreach( $response->data->config[1]->elements as $index ) {

	switch( $index->name ) {

		case "el1423834225969":
			$meta_title = strip_tags( $index->value );
			break;

		case "el1423835756898":
			$meta_description = strip_tags( $index->value );
			break;

		case "el1423835797889":
			$keywords = strip_tags( $index->value );
			break;

	}

}

/*echo $post_title . "post title<br />";
echo $image_alt . "image alt<br />";
echo $excerpt . "excerpt <br />";
echo $post_body . "post body<br />";
echo $call_to_action . "call to actions<br />";
echo $meta_title . "meta title<br />";
echo $meta_description . "meta description<br />";
echo $keywords . "keywords<br />";*/

//shorten the post title to create the filename
$tokenizedFilename = explode( ' ', strtolower( $post_title ) );
$tokenizedFilename = array_slice( $tokenizedFilename, 0, 5 );
$tokenizedFilename = implode( '-', $tokenizedFilename );
//remove $ and comma from the filename, may need to add others later
$tokenizedFilename = preg_replace('/[\$,]/', '', $tokenizedFilename );

//Images go here - ensure you get the full path, e.g. _20161003
// pull the lead image and resize it
//might want to check for a slideshow or video and
//alert if found

//TODO: $filename below will be the *last* image pulled above. This may
//be different than the actual featured image that we want to use. Research
//if it distinguishes between Featured Image or "Post Asset". The good news,
//though, is that it does pull down both (all? - at least two) image
//assets, regardless of whether they're Featured or just Post Assets

//TODO: Create local directory for images in the same name format as on the
//server e.g. _20161005 . This will need to be done before we do the body
//dump so we have the actual path name. If we upload them to the server
//before or after this shouldn't matter.

if( !empty( $excerpt ) ) {
  $subhead = '<p id="subtitle">' . $excerpt . '</p>';
} else {
  //omit the enntire subheader paragraph
  $subhead = '';
  //if we don't have an excerpt, set it to meta desc so we have something for
  //the Meta and FB OG fields
  $excerpt = $meta_description;
}

//meta_title isn't required, but post_title is. set it if it's not already,
//so we have something for FB OG
if ( empty( $meta_title ) ) {
  $meta_title = $post_title;
}

if ( empty( $image_alt ) ) {
  echo( "No image alt text!" );
}

//TODO: check how the first FB OG meta field is supposed to be populated
//this may not matter because it's exactly how Adam's code snippet
//currently works, we're just echoing the literal for now and it'll
//be turned into $_SERVER at actual run time

//TODO: $filename below will need to be reflective of the resized version. e.g.
//@320x240 We won't know that size, and subsequently filename, until the image
// manipulation has occurred. The resizer script is going to have to return
// the filename and the orientation

//if the filename is set above, use it in the output file

if( isset( $filename ) ) {
  $metaImage = '<meta property="og:image" content="http://medicine.missouri.edu/news/images/_' . $dateForFiles . '/' . $filename . '" />';
  $leadImage = '<img src="images/_' . $dateForFiles . '/' . $filename . '" alt="' . $image_alt . '" width="100%">';
} else {
  $metaImage = '';
  $leadImage = '';
}

$var_full_page = '<!DOCTYPE html>
<html>
<head>
	<meta charset="UTF-8">
	<title>' . $meta_title . '</title>
	<meta name="description" content="' . $meta_description . '">

	<!-- Facebook og properties -->
	<meta property="og:url" content="<?php echo "http://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]" ?>" />
    <meta property="og:type" content="website" />
    <meta property="og:title" content="' . $post_title . '" />
    <meta property="og:description" content="' . $excerpt . '" />
   	' . $metaImage . '

	<!--Content properties-->
	<meta name="author" content="University of Missouri School of Medicine">
	<meta name="keywords" content="' . $keywords . '">

	<!--Styles-->
	<link href="/css/screen.css" media="screen" rel="stylesheet" type="text/css">
	<link href="css/news.min.css" media="screen" rel="stylesheet" type="text/css">

	<!-- Scripts -->
	<script src="https://ajax.googleapis.com/ajax/libs/jquery/2.1.3/jquery.min.js"></script>
	<script src="/js/external.js" type="text/javascript"></script>
	<script src="js/news.min.js" type="text/javascript"></script>
</head>
<body>
	<div id="fb-root"></div>

	<div id="mainContainer">
		<div id="searchSection">
			<? virtual("/includes/top-menu.php"); ?>
        </div>
		<div id="topContainer">
			<? virtual("/includes/search.php"); ?>
        </div>
		<? virtual("/includes/menu.php"); ?>
		<div id="middleContainer">
			<a href="/news/index.php">
        		<img alt="News" src="/images/sections/news.jpg">
            </a>
            <img alt="Divider" class="divider" src="/images/hr.png">
    		<div class="clear"></div>

			<div id="contentArea">
				<!--START EDITING HERE-->

                <p id="date">' . $date . '</p>

				<div class="lead-image">
            ' . $leadImage . '
				</div>

				<h1 id="headline">' . $post_title . '</h1>

				' . $subhead . '

				<div class="share-button-container">
					<div class="fb-like" data-action="like" data-href="<?php echo "http://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]" ?>" data-layout="standard" data-share="true" data-show-faces="false"></div>
					<a class="twitter-share-button" data-via="mumedicine" href="https://twitter.com/share">Tweet</a>
					<script>
					!function(d,s,id){var js,fjs=d.getElementsByTagName(s)[0],p=/^http:/.test(d.location)?\'http\':\'https\';if(!d.getElementById(id)){js=d.createElement(s);js.id=id;js.src=p+\'://platform.twitter.com/widgets.js\';fjs.parentNode.insertBefore(js,fjs);}}(document, \'script\', \'twitter-wjs\');
					</script>
				</div>

                <div id="body-copy">

                  ' . $post_body . '

                </div>

				<!--END EDITING HERE-->
    			<? virtual("media-contacts.php"); ?>
			</div>
            <? virtual("right-menu-dev.php"); ?>
        </div>
    	<? virtual("/includes/footer3.php"); ?>
	</div>
</body>
</html>
';
file_put_contents( 'articles/' . $dateForFiles . '-' . $tokenizedFilename . '.php', $var_full_page );

echo( "\n\n" );
echo( 'articles/' . $dateForFiles . '-' . $tokenizedFilename . '.php' );

echo( "\n\n" );
echo( 'scp articles/' . $dateForFiles . '-' . $tokenizedFilename . '.php fords@medicine.missouri.edu:/var/www/html/medicine.missouri.edu/news/' );

?>
