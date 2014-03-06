<?php

define('TMVC_MYAPPDIR', '/var/www/androidreviews/myapp/');
include_once('myapp/plugins/tools.php');
include_once('myapp/plugins/consumer.php');
include_once('myapp/plugins/AndroidMarket.class.php');
include_once('myapp/plugins/updateTracking.php');
include_once('myapp/configs/config_database.php');
include_once('Mail.php');

function sendMailNewReviews($email, $newReviews) {
  global $config;

  $date = date('l, F d');

  $content = '
<center>
<h1><span style="color:#9acd32">A</span>ndroid <span style="color:#9acd32">R</span>eviews <span style="color:#9acd32">M</span>anager</h1>';

  //  $content .= '<i style="color:#9acd32">The Android Developer\'s best friend</i>';

  $content .= '</center>
<br><br>

<center><h3>Daily Report <small style="color:#9acd32">'.$date.'</small></h3></center>

<br>';

  $total = 0;

  if (empty($newReviews)) {
    $content .= '<p>No new reviews were published on the Google Play Store on the Apps you track.</p><br /><br />';
  } else {

    $content .= '<table>';

    foreach ($newReviews as $app_id => $app) {
      $reviews = $app['reviews'];
      $app = $app['app'];

      $link = '<a href="'.$config['website']['url'].'apps/reviews?id='.$app_id.'" style="color: #9acd32; text-decoration: none;">';
      $content .= '<tr>
<td style="width:30%">'.$link.'<img src="'.$app['icon'].'">&nbsp;&nbsp;
'.$app['title'].'</a></td>
<td><a style="text-decoration: none;" href="https://play.google.com/apps/publish#ReviewsPlace:p='.$app['packageName'].'">'.'<div style="margin: 10px;"><button style="text-align: center; color: #ffffff; font-weight: bold; padding: 5px; background-color: #9acd32; border-radius: 7px; width: 70px;">
Reply</button></div></a></td>
</tr>';

      foreach ($reviews as $review) {

	$total++;

	$content .= '<tr><td>'.viewRatingPics($review['rating']).'</td>';
	$content .= '<td>';
	if (preg_match('/^([^\t]+)\t(.+)$/', $review['text'], $reviewContent)) {
	  $content .= '<p><strong>'.$reviewContent[1].'</strong> '.$reviewContent[2].'</p><br />';
	} else {
	  $content .= '<p>'.$review['text'].'</p>';
	}
	$content .= '</td></tr>';
      }
      $content .= '<tr><td></td><td>';
      $content .= '<small><i>Done processing these new users\' reviews? Then don\'t forget to '.$link.'<strong>mark all of them as read</strong></a>.</i></small></p>';
      $content .= '<br /><br /></td></tr>';
    }
    $content .= '</table>';
  }


  $content .= '<small style="color: #cccccc;">You received this email because you follow at least one App on <a href="'.$config['website']['url'].'">AndroidReviewsManager</a>.
Unfollow all Apps to stop receiving this daily report.</small>
</center>
';

  $subject = 'ARM Daily Report: '.(!$total ? 'no' : $total).' new user reviews on '.$date;
  $headers['From']    = 'notifications@androidreviewsmanager.com';
  $headers['To']      = $email;
  $headers['Subject'] = $subject;
  $headers['Content-Type'] = "text/html; charset=\"UTF-8\"";
  $headers['Content-Transfer-Encoding'] = "8bit";
  
  $content = email_template($content, $subject);
  $content = utf8_encode($content);

  $params['sendmail_path'] = '/usr/lib/sendmail';
  
  $mail_object =& Mail::factory('sendmail', $params);
  $mail_object->send($headers['To'], $headers, $content);

  echo 'Email sent! '.$email."\n";
}


$market = new AndroidMarket();

$db = new PDO('mysql:host='.$config['default']['host'].';dbname='.$config['default']['name'],
              $config['default']['user'], $config['default']['pass']);
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$r = $db->prepare('SELECT * FROM users');
$r->execute();
$users = $r->fetchAll();

foreach ($users as $user) {
  $flag = false;
  $r = $db->prepare('SELECT t.*, a.title AS app_name, a.icon AS app_icon, a.packageName AS app_package FROM apps_tracker AS t JOIN apps AS a WHERE t.app_id=a.id AND t.user=? ORDER BY a.title');
  $r->execute(array($user['email']));
  $apps = $r->fetchAll();

  $newReviews = array();
  foreach ($apps as $app) {
    $flag = true;
    $reviews = updateTracking($db, $market, $app['user'], $app['app_id'], true);
    if (!empty($reviews)) {
      $newReviews[$app['app_id']]['reviews'] = $reviews;
      $newReviews[$app['app_id']]['app'] = array('id' => 'app_id',
						 'title' => $app['app_name'],
						 'packageName' => $app['app_package'],
						 'icon' => $app['app_icon']);
    }
  }
  if ($flag)
    sendMailNewReviews($user['email'], $newReviews);
  else
    echo 'No email for '.$user['email']."\n";
}







function email_template($content, $subject = '') {
return '
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd"> 
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
	<title>'.$subject.'</title>
	<style type="text/css">
		/* Based on The MailChimp Reset INLINE: Yes. */  
		/* Client-specific Styles */
		#outlook a {padding:0;} /* Force Outlook to provide a "view in browser" menu link. */
		body{font-family:sans-serif;width:100% !important; -webkit-text-size-adjust:100%; -ms-text-size-adjust:100%; margin:0; padding:0;} 
		/* Prevent Webkit and Windows Mobile platforms from changing default font sizes.*/ 
		.ExternalClass {width:100%;} /* Force Hotmail to display emails at full width */  
		.ExternalClass, .ExternalClass p, .ExternalClass span, .ExternalClass font, .ExternalClass td, .ExternalClass div {line-height: 100%;}
		/* Forces Hotmail to display normal line spacing.  More on that: http://www.emailonacid.com/forum/viewthread/43/ */ 
		#backgroundTable {margin:0; padding:0; width:100% !important; line-height: 100% !important;}
		/* End reset */

		/* Some sensible defaults for images
		Bring inline: Yes. */
		img {outline:none; text-decoration:none; -ms-interpolation-mode: bicubic;} 
		a img {border:none;} 
		.image_fix {display:block;}

		/* Yahoo paragraph fix
		Bring inline: Yes. */
		p {margin: 1em 0;}

		/* Hotmail header color reset
		Bring inline: Yes. */
		h1, h2, h3, h4, h5, h6 {color: black !important;}

		h1 a, h2 a, h3 a, h4 a, h5 a, h6 a {color: blue !important;}

		h1 a:active, h2 a:active,  h3 a:active, h4 a:active, h5 a:active, h6 a:active {
		color: red !important; /* Preferably not the same color as the normal header link color.  There is limited support for psuedo classes in email clients, this was added just for good measure. */
		}

		h1 a:visited, h2 a:visited,  h3 a:visited, h4 a:visited, h5 a:visited, h6 a:visited {
		color: purple !important; /* Preferably not the same color as the normal header link color. There is limited support for psuedo classes in email clients, this was added just for good measure. */
		}

		/* Outlook 07, 10 Padding issue fix
		Bring inline: No.*/
		table td {border-collapse: collapse;}

		/* Remove spacing around Outlook 07, 10 tables
		Bring inline: Yes */
		table { border-collapse:collapse; mso-table-lspace:0pt; mso-table-rspace:0pt; }

		/* Styling your links has become much simpler with the new Yahoo.  In fact, it falls in line with the main credo of styling in email and make sure to bring your styles inline.  Your link colors will be uniform across clients when brought inline.
		Bring inline: Yes. */
		a {color: orange;}


		/***************************************************
		****************************************************
		MOBILE TARGETING
		****************************************************
		***************************************************/
		@media only screen and (max-device-width: 480px) {
			/* Part one of controlling phone number linking for mobile. */
			a[href^="tel"], a[href^="sms"] {
						text-decoration: none;
						color: blue; /* or whatever your want */
						pointer-events: none;
						cursor: default;
					}

			.mobile_link a[href^="tel"], .mobile_link a[href^="sms"] {
						text-decoration: default;
						color: orange !important;
						pointer-events: auto;
						cursor: default;
					}

		}

		/* More Specific Targeting */

		@media only screen and (min-device-width: 768px) and (max-device-width: 1024px) {
		/* You guessed it, ipad (tablets, smaller screens, etc) */
			/* repeating for the ipad */
			a[href^="tel"], a[href^="sms"] {
						text-decoration: none;
						color: blue; /* or whatever your want */
						pointer-events: none;
						cursor: default;
					}

			.mobile_link a[href^="tel"], .mobile_link a[href^="sms"] {
						text-decoration: default;
						color: orange !important;
						pointer-events: auto;
						cursor: default;
					}
		}

		@media only screen and (-webkit-min-device-pixel-ratio: 2) {
		/* Put your iPhone 4g styles in here */ 
		}

		/* Android targeting */
		@media only screen and (-webkit-device-pixel-ratio:.75){
		/* Put CSS for low density (ldpi) Android layouts in here */
		}
		@media only screen and (-webkit-device-pixel-ratio:1){
		/* Put CSS for medium density (mdpi) Android layouts in here */
		}
		@media only screen and (-webkit-device-pixel-ratio:1.5){
		/* Put CSS for high density (hdpi) Android layouts in here */
		}
		/* end Android targeting */

          .notification_left {
            background:#ffffff;
            height:54px;
            width:54px;
            padding:5px;
            margin:5px;
            margin-right:0;
            border-radius:3px;
            border-top-right-radius:0;
            border-bottom-right-radius:0;
         }
         .notification_left_img_avatar {
            border-radius:50%;
            border:2px solid #26b671;
            width:42px;
            height:42px;
            margin:5px;
         }
         .notification_left_img_achievement {
           border-radius: 3px;
            width: 34px;
            height:34px;
            padding:5px;
            margin:5px;
         }
         .notification_right {
            background:#ffffff;
            height:24px;
            color:#14623d;
            padding:20px;
            border-radius:3px;
            margin:5px;
            margin-left:0;
            border-top-left-radius:0;
            border-bottom-left-radius:0;
         }


	</style>

	<!-- Targeting Windows Mobile -->
	<!--[if IEMobile 7]>
	<style type="text/css">
	
	</style>
	<![endif]-->   

	<!-- ***********************************************
	****************************************************
	END MOBILE TARGETING
	****************************************************
	************************************************ -->

	<!--[if gte mso 9]>
		<style>
		/* Target Outlook 2007 and 2010 */
		</style>
	<![endif]-->
</head>
<body>
<!-- Wrapper/Container Table: Use a wrapper table to control the width and the background color consistently of your email. Use this approach instead of setting attributes on the body tag. -->
<table cellpadding="0" cellspacing="0" border="0" id="backgroundTable">
	<tr>
		<td valign="top"> 
'.$content.'
		</td>
	</tr>
</table>  
<!-- End of wrapper table -->
</body>
</html>';
}
