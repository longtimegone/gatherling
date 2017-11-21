<?php session_start();
require_once 'lib.php';
$stop = 0;
$player = Player::getSessionPlayer();

if (!isset($_GET['event']) || !isset($_GET['action'])) {
  header("Location: player.php");
  exit;
}

$event = new Event($_GET['event']);
$series = new Series($event->series);

$playerIsBanned = $series->isPlayerBanned($player->name);
if($playerIsBanned) {    
  header("Location: bannedplayer.php");
  exit;
}

if ($event->prereg_allowed != 1) {
  header("Location: player.php");
  exit;
}

// check for max registerd players
if ($event->is_full()){
    header("Location: player.php");
    $stop = 1;
}


if ($_GET['action'] == "reg" and $stop != 1) {
    // part of the reg-decklist feature, the header call to deck.php is the switch that turns it on. Not sure if the call is 
    // correct exactly. It works for the super but not non-supers
    $event->addPlayer($player->name);
    header ("Location: deck.php?player={$player->name}&event={$event->name}&mode=register");    
} elseif ($_GET['action'] == "unreg") {
  $event->removeEntry($player->name);
  header("Location: player.php");  
}
    
// comment out below line when you turn on the reg-decklist feature
// header("Location: player.php"); 
