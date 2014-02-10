<?php

$urls = array(
	      'login' => '/login/',
	      'apps' => '/apps/',
              'search' => '/apps/search',
	      'reviews' => '/apps/reviews?id=',
	      'logout' => '/logout/',
	      'help' => '/help/'
	      );


function getUrl($name) {
  global $urls;
  return $urls[$name];
}

function checkLogin() {
  global $urls;
  if (!isset($_SESSION['email'])) {
    header('location: ' . $urls['login']);
  }
}

function getMarket() {
  return $_SESSION['AndroidMarket'];
}

function redirectsApp($appId) {
  global $urls;
  header('location: ' . $urls['reviews'] . $appId);
}
function redirectsApps() {
  global $urls;
  header('location: ' . $urls['apps']);
}

function protect($string) {
  return (htmlspecialchars(stripslashes($string)));
}

function idToValid($str) {
  return str_replace('.', '_', str_replace(':', '_', $str));
}

//////////////////////////////////////////////////////////////////
// Invites API
//////////////////////////////////////////////////////////////////

include_once(__DIR__.'/consumer.php');

function check_invite($service_name, $invite) {
  return consume('http://invite.paysdu42.fr',
                 'invites', 'string',
                 'GET', $invite,
                 array('service_name' => $service_name));
}

function use_invite($service_name, $invite) {
  return consume('http://invite.paysdu42.fr',
                 'invites', 'bool',
                 'PUT', $invite,
                 array('service_name' => $service_name));
}

function PdoGetMessage($q) {
  $err = $q->errorInfo();
  return $err[2];
}

//////////////////////////////////////////////////////////////////
// Summary
//////////////////////////////////////////////////////////////////

function    summary_($str, $len, $st)
{
  if (strlen($str) < $len)
    return ($str);
  elseif (preg_match("/(.{1,$len})\s./ms", $str, $match))
    {
      if ($st)
	return ($match[1]."...");
      else
	return ($match[1]);
    }
  else
    {
      if ($st)
	return (substr($str, 0, $len)."...");
      else
	return (substr($str, 0, $len));
    }
}

function summary($str, $len)
{
  return (summary_($str, $len, true));
}

//////////////////////////////////////////////////////////////////
// Views
//////////////////////////////////////////////////////////////////

function viewAlert($type, $msg) {
  if ($type == 'warning')
    $icon = 'warning';
  elseif ($type == 'danger')
    $icon = 'exclamation-circle';
  elseif ($type == 'success')
    $icon = 'check-circle';
  else
    $icon = 'info-circle';
  echo '<div class="alert alert-',
    $type,
    '">
  <i class="fa fa-',
    $icon,
    ' fa-lg"></i>
  ',$msg, '
</div>';
}

function viewGrid($per_line, $elements, $displayer, $param, $size = 'md') {
  $row_nb = 0;
  $idx = 0;
?>
  <div class="row row<?= $row_nb ?>">
    <?php $i = 1;
	  foreach ($elements as $e) { ?>
    <div class="col-<?= $size ?>-<?= 12 / $per_line ?>">
	      <?php $displayer($e, $param, $idx) ?>
    </div> <!-- col -->
<?php if ($i == $per_line) { $row_nb++; ?>
  </div> <!-- row -->
  <div class="row row<?= $row_nb ?>">
    <?php $i = 0;
	  }
	  $i++;
	  $idx++;
	  } ?>
  </div> <!-- row -->
<?php
}

function viewSearchApp($app) { ?>
<div class="well">
  <div class="row">
    <div class="col-xs-4">
      <img src="<?= $app['icon'] ?>" alt="<?= $app['title'] ?>">
    </div> <!-- col -->
    <div class="col-xs-8">
      <h5><?= $app['title'] ?></h5>
      <form method="post" class="f_track">
	<input type="hidden" name="f_track_id" value="<?= $app['id'] ?>">
	<button type="submit" class="btn btn-android btn-xs <?= $app['tracked'] ? 'stop' : 'start' ?>" name="f_track_submit">
	  <i class="fa fa-<?= $app['tracked'] ? 'stop' : 'play' ?>"></i>
	  <?= $app['tracked'] ? 'Stop' : 'Start' ?> Tracking
	</button>
	<a href="/apps/reviews?id=<?= $app['id'] ?>" class="btn btn-xs btn-android">
	  Browse reviews</a>
      </form>
    </div> <!-- col -->
  </div> <!-- row -->
</div> <!-- well -->
<?php }

function viewSearchApps($searchApps, $errorSearch, $currindex = 0) {
  if (isset($searchApps)) {
    if (!empty($errorSearch)) {
      foreach ($errorSearch as $error) {
	viewAlert('danger', $error);
      }
    } elseif (empty($searchApps) || !count($searchApps)) {
      viewAlert('info', 'No result match your search.');
    } else { ?>
    <div class="text-right">  
      <button class="btn btn-default btn-xs untrack-all">Untrack all <span class="total-search"></span> Apps</button>
      <button class="btn btn-default btn-xs track-all">Track all <span class="total-search"></span> Apps</button>
    </div> <!-- text-right -->
    <?php viewGrid(3, $searchApps, viewSearchApp); ?>
	<div class="text-center">
	  <hr class="hover">
	  <a href="#" id="loadmore" class="btn btn-default btn-s">
	    <span class="loadindex hidden"><?= $currindex + 9 ?></span>Load more</a>
	</div>
<?php
    }
  }
}

function viewTrackedApps($tracked) {
?>
  <?php $viewApp = function($app) { ?>
  <?php if (empty($app)) { ?>
  <div class="panel panel-default app">
    <div class="panel-heading">
      <h3>Add a new App to track here</h3>
    </div> <!-- panel-heading -->
    <div class="panel-body">
      <a href="<?= getUrl('search') ?>" class="addapp">+</a>
    </div> <!-- panel-body -->
  </div> <!-- panel -->
  <?php } else { ?>
  <div class="panel panel-android app <?= $app['unread'] ? 'has_unread' : 'no_unread' ?>">
    <div class="panel-heading">
      <h3>
	<img src="<?= $app['icon'] ?>" alt="<?= $app['title'] ?>">
	<?= $app['title'] ?>
      </h3>
    </div> <!-- panel-heading -->
    <div class="panel-body">
      <details>
	<summary><?= summary($app['description'], 100) ?></summary>
	<p>
	  <?= $app['description'] ?>
      </details>
      <!-- <span class="label label-default"><?= $app['creator'] ?></span> -->
      <!-- <span class="label label-warning"><?= $app['rating'] ?></span> -->
      <form method="post" id="f_track" class="buttons">
	<div class="<?= $app['unread'] ? 'unread' : 'read' ?> pull-right">
	  <a href="<?= getUrl('reviews'), $app['id'] ?>">
	    <div class="hexagon">
	      <?= $app['unread'] ?
		  $app['unread']
		  : '<i class="fa fa-envelope-o"></i>'
		  ?>
	    </div>
	  </a>
	  <?php if ($app['unread']) { ?>
	  <span class="fa-stack bubble">
	    <i class="fa fa-comment fa-stack-2x"></i>
	    <i class="fa fa-envelope fa-stack-1x"></i>
	  </span>
	  <?php } ?>
	</div>
	<input type="hidden" name="f_track_id" value="<?= $app['id'] ?>">
	<button type="submit" name="f_track_submit" class="btn btn-android <?= $app['tracked'] ? 'stop' : 'start' ?>">
	  <i class="fa fa-<?= $app['tracked'] ? 'stop' : 'play' ?>"></i>
	  <?= $app['tracked'] ? 'Stop' : 'Start' ?> Tracking
	</button>
	<a href="/apps/reviews?id=<?= $app['id'] ?>" class="btn btn-android">Browse reviews</a>
      </form>
    </div> <!-- panel-body -->
  </div> <!-- panel -->
  <?php } ?>
  <?php } ?>
  <?php
     $per_line = 3;
     $extra = $per_line - (count($tracked) % $per_line);
     for ($i = 0; $i < $extra; $i++) {
		       $tracked[] = array();
		       }
		       ?>
     <div id="apps">
       <?php viewGrid($per_line, $tracked, $viewApp); ?>
     </div>
<?php
}

function viewRatingStars($rating) {
  echo '<div class="text-',
    ($rating <= 2 ? 'danger' : 'android'), '">';
  $full = floor($rating);
  for ($i = 0; $i < $full; $i++)
    echo '<i class="fa fa-2x fa-star"></i>';
  $rest = $rating - $full;
  if ($full == 5);
  elseif ($rest < 0.25)
    echo '<i class="fa fa-2x fa-star-o"></i>';
  elseif ($rest >= 0.25 && $rest <= 0.75)
    echo '<i class="fa fa-2x fa-star-half-o"></i>';
  else
    echo'<i class="fa fa-2x fa-star"></i>';
  $empty = 5 - $full - 1;
  for ($i = 0; $i < $empty; $i++)
    echo '<i class="fa fa-2x fa-star-o"></i>';  
  echo '</div>';
}

function viewModals() {
?>
<div class="modal fade" id="modalAbout" tabindex="-1" role="dialog">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
        <h4 class="modal-title">About</h4>
      </div>
      <div class="modal-body">
	<div class="row about">
	  <div class="col-sm-4">
	    <i class="fa fa-th"></i>
	    <p>Search and track your favorite Apps</p>
	  </div> <!-- col -->
	  <div class="col-sm-4">
	    <i class="fa fa-exclamation-circle"></i>
	    <p>Get notified when new reviews are posted</p>
	  </div> <!-- col -->
	  <div class="col-sm-4">
	    <i class="fa fa-smile-o"></i>
	    <p>Read, reply, improve your ratings!</p>
	  </div> <!-- col -->
	</div> <!-- row -->
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-android" data-dismiss="modal">Got it!</button>
      </div>
    </div><!-- /.modal-content -->
  </div><!-- /.modal-dialog -->
</div><!-- /.modal -->

<div class="modal fade" id="modalTerms" tabindex="-1" role="dialog">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
        <h4 class="modal-title">Terms Of Service</h4>
      </div>
      <div class="modal-body">
	to be written
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-android" data-dismiss="modal">Got it!</button>
      </div>
    </div><!-- /.modal-content -->
  </div><!-- /.modal-dialog -->
</div><!-- /.modal -->

<div class="modal fade" id="modalPrivacy" tabindex="-1" role="dialog">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
        <h4 class="modal-title">Privacy Policy</h4>
      </div>
      <div class="modal-body">
	to be written
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-android" data-dismiss="modal">Got it!</button>
      </div>
    </div><!-- /.modal-content -->
  </div><!-- /.modal-dialog -->
</div><!-- /.modal -->
<?php
}

function viewReviews($reviews, $isTracked, $errorsReviews, $packageName, $viewStyle = null) {
   if (!$viewStyle || ($viewStyle != 'one_view' && $viewStyle != 'list_view'))
     //$viewStyle = $isTracked ? 'one_view' : 'list_view';
     $viewStyle = 'list_view';
?>
    <div class="reviews <?= $viewStyle ?>">
      <?php
    if (!empty($errorsReviews)) {
      foreach ($errorsReviews as $error) {
	viewAlert('danger', $error);
      }
    }
   if (empty($reviews) || !count($reviews)) {
      viewAlert('android', 'No reviews found');
    } else { ?>

      <?php $viewReview = function ($review, $array, $idx) {
      $isTracked = $array[0];
      $packageName = $array[1];
      $reviews = $array[2];
       ?>
      <div class="review panel panel-default" id="<?= idToValid($review['id']) ?>">
	<div class="panel-body">
	  <div>
	    <div class="pull-right date">
	      <?= date('d M Y H:i', $review['creationTime'] / 1000) ?>
	    </div> <!-- pull-right -->
	    <p class="text-android"><?= $review['author'] ?></p>
	  </div>
	  <?php if (preg_match('/^([^\t]+)\t(.+)$/', $review['text'], $reviewContent)) { ?>
	  <p><strong><?= $reviewContent[1] ?></strong></p>
	  <p><?= $reviewContent[2] ?></p>
	  <? } else { ?>
	  <p><?= $review['text'] ?></p>
	  <? } ?>
	  <div class="pull-right rating">
	    <?php viewRatingStars($review['rating']); ?>
	  </div>
	</div> <!-- panel-body -->
	<div class="panel-footer">
	  <?php if ($isTracked && isset($review['read'])) { ?>
	  <form method="post" class="f_read">
	    <input type="hidden" name="f_read_id" value="<?= $review['id'] ?>">
	    <div class="pull-right">
	      <button type="submit" name="f_read_<?= $review['read'] ? 'unread' : 'read' ?>"
		      class="btn btn-default <?= $review['read'] ? 'read' : 'unread' ?>">
		<i class="fa fa-<?= $review['read'] ? 'envelope' : 'envelope-o' ?>"></i>
		<span>Mark as <?= $review['read'] ? 'unread' : 'read' ?></span>
	      </button>
	      <a href="https://play.google.com/apps/publish#ReviewsPlace:p=<?= $packageName ?>"
		 target="_blank" class="btn btn-default">
		<i class="fa fa-reply"></i>
		Reply
	      </a>
	      <button class="btn btn-android next">
		<span class="hidden id_next"><?= idToValid($reviews[$idx + 1]['id']) ?></span>
		<i class="fa fa-chevron-right"></i>
	      </button>
	    </div> <!-- text-right -->
	    <button class="btn btn-default<?= $idx ? '' : ' invisible' ?> prev">
	      <span class="hidden id_prev"><?= idToValid($reviews[$idx - 1]['id']) ?></span>
	      <i class="fa fa-chevron-left"></i>
	    </button>
	  </form>
	  <?php } ?>
	</div> <!-- panel-footer -->
      </div> <!-- panel -->
      <?php } ?>
      <?php if ($isTracked) { ?>
      <div class="start_reading panel panel-android">
	<div class="panel-body">
	  <div class="play js_start">
	    <i class="fa fa-youtube-play"></i>
	  </div>
	</div> <!-- panel-body -->
	<div class="panel-footer">
	  <div class="text-right">
	    <button class="btn btn-android js_start">
	      <i class="fa fa-play-circle"></i>
	      Start Reading Reviews
	    </button>
	  </div> <!-- text-right -->
	</div> <!-- panel-footer -->
      </div> <!-- panel -->
      <?php } ?>
      <?php viewGrid(2, $reviews, $viewReview, array($isTracked, $packageName, $reviews)); ?>
      <?php } ?>
    </div> <!-- reviews -->
<?php
}
