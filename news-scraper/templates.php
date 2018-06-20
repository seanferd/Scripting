<?php
include( "debug.php/debug.php" );
  $username = 'fords@health.missouri.edu';
  $apikey = '24fd854b-590d-499b-bb49-e2a30171101b';

  $project_id = '74148';
  $template_id = '402912';
  $account_id = '25913';

  $ch = curl_init();

  curl_setopt( $ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
curl_setopt( $ch, CURLOPT_HTTPHEADER, array('Accept: application/vnd.gathercontent.v0.5+json'));
curl_setopt( $ch, CURLOPT_USERPWD, $username . ':' . $apikey);
curl_setopt( $ch, CURLOPT_URL, 'https://api.gathercontent.com/templates?project_id=' . $project_id);
curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );

  $response = json_decode( curl_exec( $ch ) );
  curl_close( $ch );
debug($response);
foreach( $response->data as $template ) {
  //debug($template);
  echo $template->project_id;
  echo $template->name;
}

// $slug = "archive/20161011-mu-awards-500000-in-grants.php";
// $exp = explode( "/", $slug );
// debug($exp);
// $slug = end( $exp );
// echo $slug;


// $config = array(
//                   (object)[
//                     'label' => "Content",
//                     'name' => "tab1423833774834",
//                     'hidden' => false,
//                     'elements' => [
//                         (object) [
//                             'type' => 'text',
//                             'name' => 'el1423834242747',
//                             'label' => 'Post title',
//                             'value' => $headline
//                           ],
//                         (object) [
//                             'type' => 'text',
//                             'name' => 'el1423834242747',
//                             'label' => 'Featured image alt tag (required)',
//                             'value' => $imageAlt
//                           ],
//                         (object) [
//                             'type' => 'text',
//                             'name' => 'el1423834242747',
//                             'label' => 'Featured image URL (optional)',
//                             'value' => $imageSrc
//                           ],
//                         (object) [
//                             'type' => 'text',
//                             'name' => 'el1423834242747',
//                             'label' => 'Post excerpt, tagline or subheader',
//                             'value' => $subtitle
//                           ],
//                         (object) [
//                             'type' => 'text',
//                             'name' => 'el1423834242747',
//                             'label' => 'Post body',
//                             'value' => $body
//                           ],
//                         (object) [
//                           'type' => 'text',
//                           'name' => 'el1423834242747',
//                           'label' => 'Original publish date',
//                           'value' => $date
//                         ]
//                     ]
//                   ]
// );
//
//
// debug($config);

  ?>
