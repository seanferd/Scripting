<?php

/*    news-scraper.php
//    Sean Ford
//    fords@health.missouri.edu
//    10/21/2016
//
//    This script is supposed to take an archive news story - locally or from
//    the web - then parse out the necessary information and move it back up to
//    a GatherContent template for future use. We try to account for the variances
//    in old articles via fallbacks because many did not follow any standards
//
//    This script is intended to be run locally against a bash for loop as such:
//    for i in archive/*.php;do
//      php news-scraper.php $i;
//    done
//
//    Assumed file structure:
//
//    news-scraper.php (this file)
//      |
//       --> /archive (where php files live)
//       --> /images
//
//
//  Alternatively it works on a singular page as well:
//   php news-scraper.php articles/0206.php
//
//  It can run via browser by providing ?slug=0206.php
//
//  At this time, the GC API does not appear to allow image upload, so we only
//  gather text and links.
*/

//TODO: for image upload, instead of parsing them manually, I'll just upload them
//from their corresponding local directory and attach them all to the GC template.
//I think this makes sense because the links will all come over anyway and
//as long as we keep them relatively linked, they should just work. Also, the
//filename should give away which is the lead and which are just ancillary
//DOES NOT APPEAR TO BE POSSIBLE -- GC api limitation

include( "debug.php/debug.php" );

header( "Content-Type: text/html; charset=utf-8" );

//suppress warnings
error_reporting( 1 );

//variable dump to screen and file
$DEBUG = 0;

/*Variables*/

$arrElements   =  "";
$arrImageInfo  =  array();

//Content tab

$postTitle     =  "";
$excerpt       =  "";
$leadImg       =  "";
$imageAlt      =  "";
$imageSrc      =  "";
$imageCaption  =  "";
$videoSrc      =  "";
$date          =  "";
$body          =  "";

//Meta and SEO tab

$metaDesc      =  "";
$metaAuthor    =  "";
$metaKeywords  =  "";
$ogTitle       =  "";
$ogDescription =  "";
$ogImage       =  "";

//will fill out the published url // originally created the slug field, but that
//is not the correct use of this field in the template
$slug          =  "";

//entire dom body dump (based on contentarea div ID)
$contentArea   =  "";

//fallback variables, assuming lead image isn't found, nor is a video
$fbAlt         =  "";
$fbSrc         =  "";
$fbCaption     =  "";
$fbVideo       =  "";

//will become the entire article broken out by DOM nodes
$dom           =  new DOMDocument();
//the object for XPath queries (able to search for specific nodes)
$xpath         =  "";

//credentials for GatherContent's API
$username      =  "user";
$apikey        =  "";
$gcAccountID   =  "25913";
$gcProjectID   =  "74148";
$gcTemplateID  =  "402912";
$gcParentID    =  "2737814";

//receive filenames from command line -- will make bulk selecting them easier
if( isset( $argv[1] ) ) {
  $slug        = $argv[1];
} elseif( isset( $_GET['slug'] ) ) {
  $slug        = '/Users/seanferd/Code/www/sites/news-scraper/archive/' . $_GET['slug'];
} else {
  die( "Missing input file.\n
       Please run:\n
       php news-scraper.php 'filename'\n
       Or in browser\n
       http://news-scraper.sites.dev/news-scraper.php?slug=filename" );
}

$dom->loadHTMLFile( $slug );

//since we're batch running this from command line, the slug was containing
//extraneous directories as well. this breaks it out by /, then we keep only
//the last element in the array (in case we end up multiple direcotries deep)
$exp           = explode( "/", $slug );
$slug          = end( $exp );

//we can't set the xpath until we have the $dom!
$xpath         =  new DOMXPath( $dom );

//by default, the DOM strips the html tags, leaving only the text. we want
//to keep those for body and contentarea - so we find those specific elements,
//then loop through them and pass them to the DOMinnerHTML function, which adds
//them back
$keepTags      = array(  $dom->getElementById( 'body-copy' ),
                         $dom->getElementById( 'contentArea' )
                  );

foreach( $keepTags as $tag ) {
  //error on null object trying to call getAttribute
  if( is_null( $tag ) ) {
    continue;
  }

  if( $tag->getAttribute( "id" )  === "body-copy" ) {
    $body = DOMinnerHTML( $tag );
  } elseif( $tag->getAttribute( "id" ) === "contentArea" ) {
    $contentArea = DOMinnerHTML( $tag );
  }
}


//array of the non-meta elements that we need from the DOM
$arrElements = array(
                      "date",
                      "headline",
                      "subtitle",
                      "lead-image"
                    );

//populate our array with key=>value pairs from the DOM
foreach( $arrElements as $search ) {
  $result = $dom->getElementById( $search );
  $arrElements[$search] = $result->nodeValue;
}

$date         = $arrElements[ "date" ];
$postTitle    = $arrElements[ "headline" ];
$excerpt      = $arrElements[ "subtitle" ];
$leadImg      = $arrElements[ "lead-image" ];

//XPath search queries to find the image div with the lead-image class
//then pull the alt, src and caption
//Added fb (fallback) variables to try and account for older links not using
//lead-image

$arrImageQueries = array(
                        "alt"       =>  "//*[@class='lead-image']/img/@alt",
                        "src"       =>  "//*[@class='lead-image']/img/@src",
                        "caption"   =>  "//*[@class='lead-image']/p",
                        "video"     =>  "//*[@class='lead-image']/iframe/@src",
                        "fbAlt"     =>  "//*/img/@alt",
                        "fbSrc"     =>  "//*/img/@src",
                        "fbCaption" =>  "//*[@class='lead-image']/p",
                        "fbVideo"   =>  "//*[@class='lead-image']/iframe/@src"
                      );

foreach( $arrImageQueries as $key => $query ) {
  $result = $xpath->query( $query );

  foreach( $result as $result ) {
    $arrImageInfo[$key] = $result->nodeValue;
  }
}

$imageAlt     = $arrImageInfo[ "alt" ];
$imageSrc     = $arrImageInfo[ "src" ];
$imageCaption = $arrImageInfo[ "caption" ];
$videoSrc     = $arrImageInfo[ "video" ];
$fbAlt        = $arrImageInfo[ "fbAlt" ];
$fbSrc        = $arrImageInfo[ "fbSrc" ];
$fbCaption    = $arrImageInfo[ "fbCaption" ];
$fbVideo      = $arrImageInfo[ "fbVideo" ];

//gather the meta properties we want to keep
$searchMeta   = $dom->getElementsByTagName( "meta" );

foreach( $searchMeta as $searchMeta ) {

  //search for the top level meta tags
  switch( $searchMeta->getAttribute( "name" ) ) {
    case( "description" ):
      $metaDesc = $searchMeta->getAttribute( "content" );
      //echo $metaDesc;
      break;
    case( "author" ):
      $metaAuthor = $searchMeta->getAttribute( "content" );
      //echo $metaAuthor;
      break;
    case( "keywords" ):
      $metaKeywords = $searchMeta->getAttribute( "content" );
      //echo $metaKeywords;
      break;
  }

  //search for the second level meta property tags
  switch( $searchMeta->getAttribute( "property" ) ) {
    case( "og:title" ):
      $ogTitle = $searchMeta->getAttribute( "content" );
      //echo $ogTitle;
      break;
    case( "og:description" ):
      $ogDescription = $searchMeta->getAttribute( "content" );
      //echo $ogDescription;
      break;
    case( "og:image" ):
      $ogImage = $searchMeta->getAttribute( "content" );
      //echo $ogImage;
      break;
  }

}

/*Set fallback values as GC does not like empty fields*/

//set body to contentArea if body is null or empty string - contentArea is the
//div surrounding the body text and seems to be present on all old articles
//further, this should get the title, subtitle, etc. assuming those variables
//are null or empty as well
$body         = fallthrough_value( $body    ,   $contentArea );
$body         = strip_tags(        $body    ,   '<a><p><h1><h2><h3><img>' );
$imageAlt     = fallthrough_value( $imageAlt,   $fbAlt );
$imageSrc     = fallthrough_value( $imageSrc,   $videoSrc, $fbSrc );
$postTitle    = fallthrough_value( $postTitle,  $slug );
$date         = ( empty( $date         ) ) ? "January 1, 1970" : $date;
$excerpt      = ( empty( $excerpt      ) ) ? "N/A"             : $excerpt;
$ogTitle      = ( empty( $ogTitle      ) ) ? "N/A"             : $ogTitle;
$metaDesc     = ( empty( $metaDesc     ) ) ? "N/A"             : $metaDesc;
$metaKeywords = ( empty( $metaKeywords ) ) ? "N/A"             : $metaKeywords;


/*Functions*/

//compares variables to attempt to keep only one with a value
//otherwise sets to N/A as an alert to Guimel to review (and because GC doesn't
//seem to allow some empty fields)
function fallthrough_value( $top, $secondary, $fallback = "" ) {

  //if top is empty, set it to $secondary, otherwise keep its value, etc.
  //eventually defaulting to N/A if it's still empty at the bottom
  $top = ( empty( $top ) ) ? $secondary : $top;
  $top = ( empty( $top ) ) ? $fallback  : $top;
  $top = ( empty( $top ) ) ? "N/A"      : $top;

  return $top;
}

//this finds the children nodes of the element passed in
//(p, a, img, etc). then makes a new document and rebuilds our element with those
//tags reattached, and finally saves the html formatting and returns the
//tagged output
function DOMinnerHTML( $element ) {
    $innerHTML = "";
    $children = $element->childNodes;
    foreach( $children as $child ) {
        $tmp_dom = new DOMDocument();
        $tmp_dom->appendChild( $tmp_dom->importNode( $child, true ) );
        $innerHTML.=trim( $tmp_dom->saveHTML() );
    }
    return $innerHTML;
}

//this seemed to work, but completely broke the GC template
//$find     = array( "<script>" , "</script>" );
//$replace  = array( "<pre>"    , "</pre>"    );
//$body     = str_replace( $find, $replace, $body );

//variable dump for debug

if( $DEBUG == 1 ) {
  echo $date          . " <strong>date</strong><br />";
  echo $body          . " <strong>body</strong><br />";
  echo $postTitle     . " <strong>post title</strong><br />";
  echo $excerpt       . " <strong>excerpt</strong><br />";
  echo $leadImg       . " <strong>lead image</strong><br />";
  echo $contentArea   . " <strong>contentarea</strong><br />";
  echo $imageAlt      . " <strong>image alt</strong><br />";
  echo $imageSrc      . " <strong>image src</strong><br  />";
  echo $imageCaption  . " <strong>image caption</strong><br />";
  echo $videoSrc      . " <strong>video source</strong><br />";
  echo $fbAlt         . " <strong>fallback image alt</strong><br />";
  echo $fbSrc         . " <strong>fallback image src</strong><br />";
  echo $fbCaption     . " <strong>fallback image caption</strong><br />";
  echo $fbVideo       . " <strong>fallback video source</strong><br />";
  echo $metaDesc      . " <strong>meta name</strong><br />";
  echo $metaAuthor    . " <strong>meta author</strong><br />";
  echo $metaKeywords  . " <strong>keywords</strong><br />";
  echo $ogTitle       . " <strong>og title</strong><br />";
  echo $ogDescription . " <strong>og desc</strong><br />";
  echo $ogImage       . " <strong>og image</strong><br />";

  $var_full_page =   $date . " <strong>date</strong><br />"                   .
            $body          . " <strong>body</strong><br />"                   .
            $postTitle     . " <strong>post title</strong><br />"             .
            $excerpt       . " <strong>excerpt</strong><br />"                .
            $leadImg       . " <strong>lead image</strong><br />"             .
            $contentArea   . " <strong>contentarea</strong><br />"            .
            $imageAlt      . " <strong>image alt</strong><br />"              .
            $imageSrc      . " <strong>image src</strong><br  />"             .
            $imageCaption  . " <strong>image caption</strong><br />"          .
            $videoSrc      . " <strong>video source</strong><br />"           .
            $fbAlt         . " <strong>fallback image alt</strong><br />"     .
            $fbSrc         . " <strong>fallback image src</strong><br />"     .
            $fbCaption     . " <strong>fallback image caption</strong><br />" .
            $fbVideo       . " <strong>fallback video source</strong><br />"  .
            $metaDesc      . " <strong>meta name</strong><br />"              .
            $metaAuthor    . " <strong>meta author</strong><br />"            .
            $metaKeywords  . " <strong>keywords</strong><br />"               .
            $ogTitle       . " <strong>og title</strong><br />"               .
            $ogDescription . " <strong>og desc</strong><br />"                .
            $ogImage       . " <strong>og image</strong><br />";

  file_put_contents( '/Users/seanferd/Downloads/dump.out', $var_full_page );

}

/*Gathercontent specifics*/

$config = array(
    (object)[
      "label"     => "Content",
      "name"      => "tab1423833774834",
      "hidden"    => false,
      "elements"  => [
          (object) [
              "type"        => "text",
              "name"        => "el1423834242747",
              "required"    => false,
              "label"       => "Post title",
              "value"       => "$postTitle",
              "microcopy"   => "",
              "limit_type"  => "chars",
              "limit"       => 100,
              "plain_text"  => false
            ],
          (object) [
              "type"        => "text",
              "name"        => "el1477670488064",
              "required"    => false,
              "label"       => "Publish date (required)",
              "value"       => "$date",
              "microcopy"   => "",
              "limit_type"  => "words",
              "limit"       => 0,
              "plain_text"  => false
            ],
        (object) [
              "type"        => "files",
              "name"        => "el1423834972288",
              "required"    => false,
              "label"       => "Featured image",
              "microcopy"   => ""
            ],
          (object) [
              "type"        => "text",
              "name"        => "el1469460517625",
              "required"    => false,
              "label"       => "Featured image alt tag (required)",
              "value"       => "$imageAlt",
              "microcopy"   => "",
              "limit_type"  => "words",
              "limit"       => 10,
              "plain_text"  => true
            ],
          (object) [
              "type"        => "text",
              "name"        => "el1469023775337",
              "required"    => false,
              "label"       => "Featured image URL (optional)",
              "value"       => "$imageSrc",
              "microcopy"   => "",
              "limit_type"  => "words",
              "limit"       => 0,
              "plain_text"  => true
            ],
          (object) [
              "type"        => "text",
              "name"        => "el1423834617917",
              "required"    => false,
              "label"       => "Post excerpt, tagline or subheader",
              "value"       => "$excerpt",
              "microcopy"   => "",
              "limit_type"  => "words",
              "limit"       => 50,
              "plain_text"  => false
            ],
          (object) [
              "type"        => "text",
              "name"        => "el1423834833347",
              "required"    => false,
              "label"       => "Post body",
              "value"       => "$body",
              "microcopy"   => "",
              "limit_type"  => "words",
              "limit"       => 1000,
              "plain_text"  => false
            ],
          (object) [
                "type"        => "text",
                "name"        => "el1469023825242",
                "required"    => false,
                "label"       => "Call to Actions",
                "value"       => "N/A",
                "microcopy"   => "",
                "limit_type"  => "words",
                "limit"       => 0,
                "plain_text"  => false
              ],
          (object) [
            "type"        => "files",
            "name"        => "el1423834883738",
            "required"    => false,
            "label"       => "Post assets",
            "microcopy"   => ""
          ]
      ]
  ],
  (object)[
    "label"     => "Metadata and SEO",
    "name"      => "tab1423834216486",
    "hidden"    => false,
    "elements"  => [
        (object) [
            "type"        => "text",
            "name"        => "el1467811320445",
            "required"    => false,
            "label"       => "Published URL",
            "value"       => "http://www.medicine.missouri.edu/news/" . $slug,
            "microcopy"   => "",
            "limit_type"  => "words",
            "limit"       => 0,
            "plain_text"  => true
          ],
          (object) [
              "type"        => "text",
              "name"        => "el1467817614520",
              "required"    => false,
              "label"       => "Slug",
              "value"       => "",
              "microcopy"   => "",
              "limit_type"  => "chars",
              "limit"       => 100,
              "plain_text"  => true
            ],
          (object) [
                "type"        => "text",
                "name"        => "el1423835108915",
                "required"    => false,
                "label"       => "Taxonomy / Post Tags",
                "value"       => "N/A",
                "microcopy"   => "",
                "limit_type"  => "words",
                "limit"       => 10,
                "plain_text"  => true
              ],
            (object) [
                "type"        => "text",
                "name"        => "el1423834225969",
                "required"    => false,
                "label"       => "Meta title",
                "value"       => "$ogTitle",
                "microcopy"   => "",
                "limit_type"  => "chars",
                "limit"       => 60,
                "plain_text"  => false
              ],
            (object) [
                  "type"        => "text",
                  "name"        => "el1423835756898",
                  "required"    => false,
                  "label"       => "Meta description",
                  "value"       => "$metaDesc",
                  "microcopy"   => "",
                  "limit_type"  => "chars",
                  "limit"       => 160,
                  "plain_text"  => true
                ],
                (object) [
                    "type"        => "text",
                    "name"        => "el1423835797889",
                    "required"    => false,
                    "label"       => "Keywords",
                    "value"       => "$metaKeywords",
                    "microcopy"   => "",
                    "limit_type"  => "chars",
                    "limit"       => 100,
                    "plain_text"  => false
                  ]
            ]
        ]
);
ob_start();
var_dump($config);
$result = ob_get_clean();

file_put_contents( '/Users/seanferd/Downloads/config.out', $result );
//requirement of GC that the config be an encoded JSON response
$config = base64_encode( json_encode( $config ) );

//project and template info for GC
$data = array(
    "project_id"  => $gcProjectID,
    "name"        => $postTitle,
    "template_id" => $gcTemplateID,
    "parent_id"   => $gcParentID,
    "config"      => $config );


    ob_start();
    var_dump($data);
    $result = ob_get_clean();

    file_put_contents( '/Users/seanferd/Downloads/dump.out', $result );
//attempt to curl this to gc
$c1h = curl_init();

curl_setopt( $ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC );
curl_setopt( $ch, CURLOPT_HTTPHEADER, array( "Accept: application/vnd.gathercontent.v0.5+json" ) );
curl_setopt( $ch, CURLOPT_USERPWD, $username . ":" . $apikey );
curl_setopt( $ch, CURLOPT_URL, "https://api.gathercontent.com/items" );
curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
curl_setopt( $ch, CURLOPT_POST, true );
curl_setopt( $ch, CURLOPT_POSTFIELDS, http_build_query( $data ) );

$response = json_decode( curl_exec( $ch ) );
curl_close( $ch );

if( !is_null( $response ) ) {
  echo( var_dump( $response ) . '\n' );
  echo( $slug );
}

?>
