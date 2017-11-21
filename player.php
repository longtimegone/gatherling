<?php session_start();
require_once 'lib.php';
$player = Player::getSessionPlayer();

print_header("Player Control Panel");
?>
<div class="grid_10 suffix_1 prefix_1">
<div id="gatherling_main" class="box">
<div class="uppertitle"> Player Control Panel </div>
<?php
$result="";
if ($player == NULL) {
  echo "<center> You must <a href=\"login.php\">log in</a> to use your player control panel.</center>\n";
} else {
  // Handle actions
  if (isset($_POST['action'])) {
      // TODO: remove deck ignore functionality
    if ($_POST['action'] == 'setIgnores') {
      setPlayerIgnores();
    } else if ($_POST['action'] == 'changePassword') {
      $success = false;
      if ($_POST['newPassword2'] == $_POST['newPassword']) {
        if (strlen($_POST['newPassword']) >= 6) {
          $authenticated = Player::checkPassword($_POST['username'], $_POST['oldPassword']);
          if ($authenticated) {
            $player = new Player($_POST['username']);
            $player->setPassword($_POST['newPassword']);
            $result = "Password changed.";
            $success = true;
          } else {
            $result = "Password *not* changed, your old password was incorrect!";
          }
        } else {
          $result = "Password *not* changed, your new password needs to be longer!";
        }
      } else {
        $result = "Password *not* changed, your new passwords did not match!";
      }
    } else if ($_POST['action'] == 'editEmail') {
        $success = false;
        if ($_POST['newEmail'] == $_POST['newEmail2']) {
            $player = new Player($_POST['username']);
            $player->emailAddress = ($_POST['newEmail']);
            $player->emailPrivacy = ($_POST['emailstatus']);
            $result = "Email changed.";
            $success = true;
            $player->save();
        } else {
            $result = "Email *NOT* Changed, your new emails did not match!";
        }
    } else if ($_POST['action'] == 'changeTimeZone') {
        $player = new Player($_POST['username']);
        $player->timezone = ($_POST['timezone']);
        $result = "Time Zone Changed.";
        $player->save();
    } else if ($_POST['action'] == 'verifyAccount') {
      $success = false;
      if ($player->checkChallenge($_POST['challenge'])) {
        $player->setVerified(true);
        $result = "Successfully verified your account with MTGO.";
        $success = true;
      } else {
        $result = "Your challenge is wrong.  Get a new one by sending the message 'ua pauperkrew' to infobot on MTGO!";
      }
    }else if ($_POST['action'] == 'finalize_result') {
       // write results to matches table
        if(!isset($_POST['drop'])) {$_POST['drop'] = "N";}
        if($_POST['drop'] == 'Y') {
            $match = new Match($_POST['match_id']);
            $eventname = $match->getEventNamebyMatchid();
            $event = new Event($eventname);
            $event->dropPlayer($player->name);
        }
        if ($_POST['opponent'] != '0'){
            $test = 0;
            $event = new Event($_POST['event']);
            $new_match_id = $event->addPairing($_POST['player'], $_POST['opponent'], $event->current_round, "P");
            Match::saveReport($_POST['report'], $new_match_id, "a");
        }else{
        Match::saveReport($_POST['report'], $_POST['match_id'], $_POST['player']);
        }

    }else if ($_POST['action'] == 'drop') {
      // drop player from event
      $event = new Event($_POST['event']);
      $event->dropPlayer($player->name);
  }
  }
  // Handle modes
  $dispmode = 'playercp';
  if (isset($_GET['mode'])) {
    $dispmode = $_GET['mode'];
  }
  if (isset($_POST['mode'])) {
    $dispmode = $_POST['mode'];
  }
  //* redo this later as switch rather than elseifs, for now just kludge on
  if ($dispmode == 'alldecks') {
    print_allContainer();
  } elseif ($dispmode == 'allratings') {
    if(!isset($_GET['format'])) {$_GET['format'] = "Composite";}
    print_ratingsTable(Player::loginName());
    echo "<br /><br />";
    print_ratingHistoryForm($_GET['format']);
    echo "<br />";
    print_ratingsHistory($_GET['format']);
  } elseif ($dispmode == 'allmatches') {
    print_allMatchForm($player);
    print_matchTable($player);
  } elseif ($dispmode == 'Filter Matches') {
    print_allMatchForm($player);
    print_matchTable($player);
  } elseif ($dispmode == 'changepass') {
    print_changePassForm($player, $result);
  } elseif ($dispmode == 'edit_email') {
    print_editEmailForm($player, $result);
  } elseif ($dispmode == 'change_timezone') {
    print_editTimeZoneForm($player, $result);
  }  elseif ($dispmode == 'submit_result') {
      print_submit_resultForm($_GET['player'], $_GET['match_id']);
  }  elseif ($dispmode == 'submit_league_result') {
      League_print_submit_resultForm($_GET['event'], $_GET['round'], $player, $_GET['subevent']);
      //print_submit_resultForm($_GET['player'], $_GET['match_id']);
  }  elseif ($dispmode == 'verify_result') {
      print_verify_resultForm($_POST['report'], $_POST['match_id'],$_POST['player'],(isset($_POST['drop']))?"Y":"N", 0, 0);
  }  elseif ($dispmode == 'verify_league_result') {
      print_verify_resultForm($_POST['report'], $_POST['match_id'],$_POST['player'], "N", $_POST['opponent'],$_POST['event']);
  }  elseif ($dispmode == 'standings') {
    Standings::printEventStandings($_GET['event'],Player::loginName());
  }  elseif ($dispmode == 'verifymtgo') {
      // print_verifyMtgoForm($player, $result);
      print_manualverifyMtgoForm();
  }  elseif ($dispmode == 'drop_form') {
      print_dropConfirm($_GET['event'], $player);
  } else {
    print_mainPlayerCP( $player);
  }
}
?>
</div> <!-- gatherling_main box -->
</div> <!-- grid 10 suff 1 pre 1 -->

<?php print_footer(); ?>

<?php
//Drop confirmation form
function print_dropConfirm($event_name, $player) {
    echo "<center><h3>Drop Form</h3>";
    echo "<center style=\"color: red; font-weight: bold;\">
    Are you sure you want to drop? This cannot be undone. </center>\n";
    echo "<center bold;\">Please be sure to submit a result for any active matches before you leave.</center>\n";
    echo "<table class=\"form\">";
    echo "<tr><th>";
    echo "<form action=\"player.php\" method=\"post\">\n";
    echo "<input name=\"action\" type=\"hidden\" value=\"drop\" />\n";
    echo "<input name=\"event\" type=\"hidden\" value=\"{$event_name}\" />\n";
    echo "<input name=\"player\" type=\"hidden\" value=\"{$player->name}\" />\n";
    echo "<input name=\"submit\" type=\"submit\" value=\"Drop from Event\" />\n";
    echo "<td> ";
    echo "</form>\n";
    echo "<form action=\"player.php\" method=\"get\">\n";
    echo "<input name=\"submit\" type=\"submit\" value=\"Cancel\" />\n";
    echo "</td> </tr> \n";
    echo "<tr> <td colspan=\"2\" class=\"buttons\">\n";
    echo "</td> </tr> </table> \n";
    echo "</form>\n";
}

function print_submit_resultForm($player, $match_id) {
    echo "<center><h3>Report Game Results</h3>
    Enter results for this match</center>\n";
    $match = new Match($match_id);

    echo "<form action=\"player.php\" method=\"post\">\n";
    echo "<input name=\"mode\" type=\"hidden\" value=\"verify_result\" />\n";
    echo "<input name=\"match_id\" type=\"hidden\" value=\"{$match_id}\" />\n";
    echo "<input name=\"player\" type=\"hidden\" value=\"{$player}\" />\n";
    echo "<table class=\"form\">";
    echo "<tr><td><input type='radio' name='report' value='W20' /> I won the match 2-0</td> </tr>";
    echo "<tr><td><input type='radio' name='report' value='W21' />I won the match 2-1</td> </tr>";
    echo "<tr><td><input type='radio' name='report' value='L20' />I lost the match 0-2 </td> </tr>";
    echo "<tr><td><input type='radio' name='report' value='L21' />I lost the match 1-2</td> </tr>";
    if($match->allowsPlayerReportedDraws() == 1){
        echo "<tr><td><input type='radio' name='report' value='D' />The match was a draw</td> </tr>";
    }
    echo "<tr><td></td></tr>";
    echo "<tr><th><input type='checkbox' name='drop' value='Y' />I want to drop from this event</th> </tr>";
    echo "<tr><td></td></tr>";
    echo "<tr><td class=\"buttons\">";
    echo "<input class=\"inputbutton\" name=\"submit\" type=\"submit\" value=\"Submit Match Report\" />";
    echo "</td></tr></table>";
    echo "</form>";
    echo "<div class=\"clear\"></div>";
}

// *form to report League results
function League_print_submit_resultForm($event, $round, $player, $subevent) {
    echo "<center><h3>Report League Game Results</h3>
    Enter results</center>\n";
    echo "<center style=\" font-weight: bold;\">Opponent</center>\n";
    echo "<form action=\"player.php\" method=\"post\">\n";
    echo "<input name=\"mode\" type=\"hidden\" value=\"verify_league_result\" />\n";
    echo "<input name=\"match_id\" type=\"hidden\" value=\"0\" />\n";
    echo "<input name=\"event\" type=\"hidden\" value=\"{$event}\" />\n";
    echo "<input name=\"player\" type=\"hidden\" value=\"{$player->name}\" />\n";
    echo "<table class=\"form\">";

    echo "<tr><th>";
    leagueOpponentDropMenu($event, $round, $player, $subevent);

    echo "<br /></th>\n";
    echo "<td></td></tr>\n";
    echo "<tr><th><input type='radio' name='report' value='W20' /> I won the match 2-0<br /></th>\n";
    echo "<td></td> </tr> \n";
    echo "<tr><th><input type='radio' name='report' value='W21' />I won the match 2-1</th>\n";
    echo "<td></td> </tr> \n";
    echo "<tr> <td colspan=\"2\" class=\"buttons\">\n";
    echo "<input class=\"inputbutton\" name=\"submit\" type=\"submit\" value=\"Submit Match Report\" />\n";
    echo "</td> </tr> </table> \n";
    echo "</form>\n";
    echo "<div class=\"clear\"> </div>\n";
}

//* form to confirm submission
function print_verify_resultForm($report, $match_id, $player, $drop, $opponent, $event)  {
    echo "<center><h3><br>Confirm Game Results</p></h3></center>\n";
    echo "<center style=\"color: red; font-weight: bold;\">Please confirm your entry.</center></p>\n";
    echo "<center><h4>";
    if ($opponent <> '0'){
        echo "Opponent: ".$opponent."<br />";
    }
    switch ($report){
        case "W20":
            echo "I won the match 2-0";
            break;
        case "W21":
            echo "I won the match 2-1";
            break;
        case "L20":
            echo "I lost the match 0-2";
            break;
        case "L21":
            echo "I lost the match 1-2";
            break;
        case "D":
            echo "The match was a draw";
        break;
    }
    if ($drop == "Y"){
        echo "</p><center style=\"color: red; font-weight: bold;\">I want to drop out of this event.</center>\n";
    }
    echo "</center></h4></p>";

    echo "<table class=\"form\">";
    echo "<tr><th>";
    echo "<form action=\"player.php\" method=\"post\">\n";
    if ($drop == 'Y'){
        echo "<input name=\"drop\" type=\"hidden\" value=\"Y\" />\n";
    }
    echo "<input name=\"action\" type=\"hidden\" value=\"finalize_result\" />\n";
    echo "<input name=\"match_id\" type=\"hidden\" value=\"{$match_id}\" />\n";
    echo "<input name=\"report\" type=\"hidden\" value=\"{$report}\" />\n";
    echo "<input name=\"opponent\" type=\"hidden\" value=\"{$opponent}\" />\n";
    echo "<input name=\"player\" type=\"hidden\" value=\"{$player}\" />\n";
    echo "<input name=\"event\" type=\"hidden\" value=\"{$event}\" />\n";
    echo "<input name=\"submit\" type=\"submit\" value=\"Verify Match Report\" />\n";
    echo "</form>\n";
    echo "</th>\n";
    echo "<td> ";
    echo "<form action=\"player.php\" method=\"get\">\n";
    echo "<input name=\"match_id\" type=\"hidden\" value=\"{$match_id}\" />\n";
    echo "<input name=\"mode\" type=\"hidden\" value=\"submit_result\" />\n";
    echo "<input name=\"submit\" type=\"submit\" value=\"Go Back and Correct\" />\n";
    echo "</form>\n";
    echo "</td> </tr> \n";
    echo "<tr> <td colspan=\"2\" class=\"buttons\">\n";

    echo "</td> </tr> </table> \n";
    echo "</form>\n";
    echo "<div class=\"clear\"> </div>\n";
}

function print_changePassForm($player, $result) {
  echo "<center><h3>Changing your password</h3></center>\n";
  echo "<center>New passwords are required to be at least 6 characters long.</center>\n";
  echo "<center style=\"color: red; font-weight: bold;\">{$result}</center>\n";
  echo "<form action=\"player.php\" method=\"post\">\n";
  echo "<input name=\"action\" type=\"hidden\" value=\"changePassword\" />\n";
  echo "<input name=\"mode\" type=\"hidden\" value=\"changepass\" />\n";
  echo "<input name=\"username\" type=\"hidden\" value=\"{$player->name}\" />\n";
  echo "<table class=\"form\">";
  echo "<tr><th>Current Password</th>\n";
  echo "<td><input class=\"inputbox\" name=\"oldPassword\" type=\"password\" /></td></tr>\n";
  echo "<tr><th>New Password</th>\n";
  echo "<td><input class=\"inputbox\" name=\"newPassword\" type=\"password\" /></td></tr>\n";
  echo "<tr><th>Repeat New Password</th>\n";
  echo "<td><input class=\"inputbox\" name=\"newPassword2\" type=\"password\" /></td></tr>\n";
  echo "<tr><td colspan=\"2\" class=\"buttons\">\n";
  echo "<input class=\"inputbutton\" name=\"submit\" type=\"submit\" value=\"Change Password\" />\n";
  echo "</td></tr></table>\n";
  echo "</form>\n";
  echo "<div class=\"clear\"></div>\n";
}

function print_editEmailForm($player, $result) {
  if ($player->emailAddress == "") {
      // add email form
      echo "<center><h3>Add an Email Address to your Account</h3></center>\n";
      echo "<center style=\"color: red; font-weight: bold;\">{$result}</center>\n";
      echo "<form action=\"player.php\" method=\"post\">\n";
      echo "<input name=\"action\" type=\"hidden\" value=\"editEmail\" />\n";
      echo "<input name=\"mode\" type=\"hidden\" value=\"edit_email\" />\n";
      echo "<input name=\"username\" type=\"hidden\" value=\"{$player->name}\" />\n";
      echo "<table class=\"form\">";
      echo "<tr><th>New Email</th>\n";
      echo "<td><input class=\"inputbox\" name=\"newEmail\" type=\"email\" /></td></tr>\n";
      echo "<tr><th>Repeat New Email</th>\n";
      echo "<td><input class=\"inputbox\" name=\"newEmail2\" type=\"email\" /></td></tr>\n";
      echo "<tr><th>Privacy Status</th>\n";
      echo "<td>";
      echo emailStatusDropDown($player->emailPrivacy);
      echo "</td></tr>";
      echo "<tr><td colspan=\"2\" class=\"buttons\">\n";
      echo "<input class=\"inputbutton\" name=\"submit\" type=\"submit\" value=\"Add Email\" />\n";
      echo "</td></tr></table>\n";
      echo "</form>\n";
      echo "<div class=\"clear\"></div>\n";
  } else {
      // edit email form
      echo "<center><h3>Edit Existing Email Address</h3></center>\n";
      echo "<center style=\"color: red; font-weight: bold;\">{$result}</center>\n";
      echo "<form action=\"player.php\" method=\"post\">\n";
      echo "<input name=\"action\" type=\"hidden\" value=\"editEmail\" />\n";
      echo "<input name=\"mode\" type=\"hidden\" value=\"edit_email\" />\n";
      echo "<input name=\"username\" type=\"hidden\" value=\"{$player->name}\" />\n";
      echo "<table class=\"form\">";
      echo "<tr><th>Existing Email: </th>\n";
      echo "<td>{$player->emailAddress}</td></tr>";
      echo "<tr><th>New Email</th>\n";
      echo "<td><input class=\"inputbox\" name=\"newEmail\" type=\"email\" /></td></tr>\n";
      echo "<tr><th>Repeat New Email</th>\n";
      echo "<td><input class=\"inputbox\" name=\"newEmail2\" type=\"email\" /></td></tr>\n";
      echo "<tr><th>Privacy Status</th>\n";
      echo "<td>";
      echo emailStatusDropDown($player->emailPrivacy);
      echo "</td></tr>";
      echo "<tr><td colspan=\"2\" class=\"buttons\">\n";
      echo "<input class=\"inputbutton\" name=\"submit\" type=\"submit\" value=\"Edit Email\" />\n";
      echo "</td></tr></table>\n";
      echo "</form>\n";
      echo "<div class=\"clear\"></div>\n";
  }
}

function print_editTimeZoneForm($player, $result) {
  echo "<center><h3>Changing Your Time Zone</h3></center>\n";
  echo "<center style=\"color: red; font-weight: bold;\">{$result}</center>\n";
  echo "<form action=\"player.php\" method=\"post\">\n";
  echo "<input name=\"action\" type=\"hidden\" value=\"changeTimeZone\" />\n";
  echo "<input name=\"mode\" type=\"hidden\" value=\"change_timezone\" />\n";
  echo "<input name=\"username\" type=\"hidden\" value=\"{$player->name}\" />\n";
  echo "<table class=\"form\">";
  echo "<tr><th>Current Time Zone: </th>\n";
  echo "<td>";
  echo $player->time_zone();
  echo "</td></tr>";
  echo "<tr><th>Desired Time Zone</th>\n";
  echo "<td>";  
  echo timeZoneDropMenu();
  echo "<tr><td colspan=\"2\" class=\"buttons\">\n";
  echo "<input class=\"inputbutton\" name=\"submit\" type=\"submit\" value=\"Change Time Zone\" />\n";
  echo "</td></tr></table>\n";
  echo "</form>\n";
  echo "<div class=\"clear\"></div>\n";
}

function print_manualverifyMtgoForm() {
    echo "<center><h3>Verifying your MTGO account</h3>
        Verify your MTGO account in one simple step:<br />
        1. Leave an offline message for Dabil on MTGO.<br />\n";
}

function print_verifyMtgoForm($player, $result) {
  echo "<center><h3>Verifying your MTGO account</h3>
    Verify your MTGO account by following these simple steps:<br />
    1. Chat 'ua pauperkrew' to infobot to get a verification code <br />
    2. Enter the verification code here to be verified <br />
    \n";
  echo "<center style=\"color: red; font-weight: bold;\">{$result}</center>\n";
  if ($player->verified == 1) {
    echo "<center>You are already verified!</center>\n";
    echo "<a href=\"player.php\">Go back to the Player CP</a>\n";
  } else {
    echo "<form action=\"player.php\" method=\"post\">\n";
    echo "<input name=\"action\" type=\"hidden\" value=\"verifyAccount\" />\n";
    echo "<input name=\"mode\" type=\"hidden\" value=\"verifymtgo\" />\n";
    echo "<input name=\"username\" type=\"hidden\" value=\"{$player->name}\" />\n";
    echo "<table class=\"form\">";
    echo "<tr><th>Verification Code</th>\n";
    echo "<td> <input name=\"challenge\" type=\"text\" /></td> </tr> \n";
    echo "<tr> <td colspan=\"2\" class=\"buttons\">\n";
    echo "<input name=\"submit\" type=\"submit\" value=\"Verify Account\" />\n";
    echo "</td> </tr> </table> \n";
    echo "</form>\n";
  }
  echo "<div class=\"clear\"> </div>\n";
}

// TODO: Remove deck ignore functionality
function setPlayerIgnores() {
  global $player;
  $noDeckEntries = $player->getNoDeckEntries();
  foreach ($noDeckEntries as $entry) {
    if (isset($_POST['ignore'][$entry->event->name])) {
      $entry->setIgnored(1);
    } else {
      $entry->setIgnored(0);
    }
  }
}

function print_mainPlayerCP($player) {
  $upper = strtoupper(Player::loginName());
  echo "<div class=\"alpha grid_5\">\n";
  echo "<div id=\"gatherling_lefthalf\">\n";
  print_conditionalAllDecks();
  print_noDeckTable(0);  
  print_recentDeckTable();
  print_preRegistration();
  if ($player->pkmember == 1) {
      print_PKpreRegistration();
  }
  $Leagues = print_ActiveEvents();  //* new
  print_recentMatchTable();
  print_currentMatchTable($Leagues);  //* new
  echo "</div></div>\n";
  echo "<div class=\"omega grid_5\">\n";
  echo "<div id=\"gatherling_righthalf\">\n";
  print_ratingsTableSmall();
  print_statsTable();
  echo "<b>ACTIONS</b><br />\n";
  echo "<ul>\n";
  if ($player->verified == 0) {
      echo "<li><a href=\"player.php?mode=verifymtgo\">Verify your MTGO account</a></li>\n";
  } else {
      echo "<li><span style=\"color: green; font-weight: bold;\">" . image_tag("verified.png") . "Account Verified</span></li>\n";
  }
  echo "<li><a href=\"player.php?mode=changepass\">Change your password</a></li>\n";
  if ($player->emailAddress == "") {
      echo "<li><a href=\"player.php?mode=edit_email\">Add Email Address</a></li>\n";
  } else {
      echo "<li><a href=\"player.php?mode=edit_email\">Change Email Address: {$player->emailAddress}</a></li>\n";      
  }
  echo "<li><a href=\"player.php?mode=change_timezone\">Change Your Time Zone</a></li>\n";
  echo "</ul>\n";
  echo "</div></div>\n";
  echo "<div class=\"clear\"></div>\n";
}

function print_allContainer() {
  $rstar = "<font color=\"#FF0000\">*</font>";
  echo "<p> Decks marked with a $rstar are not legal under current format.<p>\n";
  echo "<div class=\"alpha grid_6\">\n";
  echo "<div id=\"gatherling_lefthalf\">\n";
  print_allDeckTable();
  echo "</div> </div> \n";
  echo "<div class=\"omega grid_4\">\n";
  echo "<div id=\"gatherling_righthalf\">\n";
  print_noDeckTable(1);
  echo "</div> </div> \n";
  echo "<div class=\"clear\"> </div> ";
}

function print_recentDeckTable() {
  global $player;

  echo "<table>\n";
  echo "<tr><td colspan=2><b>RECENT DECKS</td>\n";
  echo "<td colspan=2 align=\"right\">";
  echo "<a href=\"player.php?mode=alldecks\">(see all)</a></td>\n";
  
  $event = $player->getLastEventPlayed();
  if (is_null($event)) {
    echo "<tr><td>No Decks Found!</td>\n";
  } else {
      $entry = new Entry($event->name, $player->name);
      if ($entry->deck) {
          $decks = $player->getRecentDecks(6);
      } else {
          $decks = $player->getRecentDecks(5);
      }
      foreach ($decks as $deck) {
        echo "<tr><td>" . medalImgStr($deck->medal) . "</td>\n";
        echo "<td>" . $deck->linkTo() . "</td>\n";
        echo "<td><a href=\"eventreport.php?event={$deck->eventname}\">{$deck->eventname}</a></a></td>\n";
        echo "<td align=\"right\">" . $deck->recordString() . "</td></tr>\n";
      }
  }
  echo "</table>\n";
}

function print_preRegistration() {
  global $player;
  $events = Event::getNextPreRegister();
  echo "<table><tr><td colspan=\"3\"><b>PREREGISTER FOR EVENTS</b></td></tr>";
  if (count($events) == 0) {
      echo "<tr><td colspan=\"3\"> No Upcoming Events! </td> </tr>";
  }
  foreach ($events as $event) {
      echo "<tr><td><a href=\"{$event->threadurl}\">{$event->name}</a></td>";
      echo "<td>" . distance_of_time_in_words(time(), strtotime($event->start)) . "</td>";
      if ($event->hasRegistrant($player->name)) {
          echo "<td>Registered <a href=\"prereg.php?action=unreg&event={$event->name}\">(Unreg)</a></td>";
      } else {
          if ($event->is_full()){
              echo "<td>This event is currently at capacity.</td>";
          }else {
          echo "<td><a href=\"prereg.php?action=reg&event={$event->name}\">Register</a></td>";
          }
      }
      echo "</tr>";
  }
  echo "</table>";
}

function print_PKpreRegistration() 
{
  global $player;
  $events = Event::getNextPKPreRegister();
  echo "<table><tr><td colspan=\"3\"><b>PREREGISTER FOR PAUPER KREW EVENTS</b></td></tr>";
  if (count($events) == 0) 
      { 
      echo "<tr><td colspan=\"3\"> No Upcoming Pauper Krew Events! </td> </tr>";
      }
  foreach ($events as $event) 
      {
      echo "<tr><td>{$event->name}</td>";
      echo "<td>" . distance_of_time_in_words(time(), strtotime($event->start)) . "</td>";
      if ($event->hasRegistrant($player->name))
          {
          echo "<td>Registered <a href=\"prereg.php?action=unreg&event={$event->name}\">(Unreg)</a></td>";
          } 
      else 
          {
          echo "<td><a href=\"prereg.php?action=reg&event={$event->name}\">Register</a></td>";
          }
      echo "</tr>";
      }
  echo "</table>";
}

//* Modified above function to display active events and a link to current standings
// Undecided about showing all active events, or only those the player is enrolled in.
function print_ActiveEvents(){
    global $player;
    $events = Event::getActiveEvents();
    echo "<table><tr><td colspan=\"3\"><b>ACTIVE EVENTS</b></td></tr>";
    if (count($events) == 0) {
        echo "<tr><td colspan=\"3\"> No events are currently active. </td> </tr>";
    }

    $Leagues = array();
    $numberOfLeagues = 0;
    foreach ($events as $event)
    {
        echo "<tr><td><a href=\"eventreport.php?event={$event->name}\">{$event->name}</a></td>";
        echo "<td><a href=\"player.php?mode=standings&event={$event->name}\">Current Standings</a></td>";
        if (Standings::playerActive($event->name, $player->name) == 1){
            if ($event->current_round > $event->mainrounds) {
                $structure  = $event->finalstruct;
                $subevent_id = $event->finalid;
                $round = "final";
            }else{
                $structure = $event->mainstruct;
                $subevent_id = $event->mainid;
                $round = "main";
            }
            if ($structure == "League"){
                $Leagues[] = "<tr><td>{$event->name} Round: {$event->current_round}</td><td><a href=\"player.php?mode=submit_league_result&event={$event->name}&round={$event->current_round}&subevent={$subevent_id}\">Report League Game</a></td></tr>";
            }
            echo "<td><a href=\"player.php?mode=drop_form&event={$event->name}\">Drop From Event</a></td>";
        }
        echo "</tr>";
    }
    echo "</table>";
    return $Leagues;
}


function print_noDeckTable($allDecks) {
  global $player;
  $entriesNoDecks = $player->getNoDeckEntries();
  
  if (count($entriesNoDecks) OR $allDecks) {
      // don't print the unentered decks part of player cp if player has no unentered decks
      // OR if we are on the $allDecks page. Then always print it. 
      echo "<form action=\"player.php\" method=\"post\">";
      // TODO: Remove deck ignore functionality
      echo "<input type=\"hidden\" name=\"action\" value=\"setIgnores\" />";
      echo "<table style=\"border-width: 0px;\" width=275>\n";
      If (!$allDecks) { 
          // print this on the player cp
          echo "<tr><td colspan=2 style=\"font-size: 14px; color: red;\">";
          echo "<b>UNENTERED DECKS</td>";
          echo "<td colspan=2 align=\"right\">";
          echo "<a href=\"player.php?mode=alldecks\">(see all)</a></td></tr>\n";
      } else {
          // print this on the all decks page
          echo "<tr><td colspan=4 style=\"font-size: 14px; color: red;\">";
          echo "<b>UNENTERED DECKS</td></tr>";
          
      }
      if (count($entriesNoDecks)) {
          foreach ($entriesNoDecks as $entry) {
              echo "<tr><td>" . medalImgStr($entry->medal) . "</td>\n";
              echo "<td align=\"left\">" . $entry->createDeckLink() . "</td>";
              echo "<td align=\"right\"><a href=\"{$entry->event->threadurl}\">{$entry->event->name}</a></td>\n";
              echo "</tr>\n";
          }
      } else {
          echo "<tr><td>No Unentered Decklists Found</td></tr>";
      }
      echo "</table>\n";
      echo "<input type=\"hidden\" name=\"mode\" value=\"alldecks\" />";
      echo "</form>";
  }
}

function print_allDeckTable() {
  global $player;
  $decks = $player->getAllDecks();
  $rstar = "<font color=\"#FF0000\">*</font>";
  $upPlayer = strtoupper($player->name);

  echo "<table style=\"border-width: 0px;\" width=275>\n";
  echo "<tr><td colspan=3><b>$upPlayer'S DECKS</td></tr>\n";
  foreach ($decks as $deck) {
    $imgcell = medalImgStr($deck->medal);
    $recordString = $deck->recordString();
    echo "<td width=20>$imgcell</td>\n";
    echo "<td width=20>$recordString</td>\n";
    echo "<td>" . $deck->linkTo();
    if(!$deck->isValid()) {print $rstar;}
    echo "</td>\n";
    $event = $deck->getEvent();
    echo "<td align=\"right\"><a href=\"eventreport.php?event={$event->name}\">{$event->name}</a></td>\n";
    echo "</td></tr>\n";
  }
  echo "</table>\n";
}

function print_recentMatchTable() {
  global $player;
  $matches = $player->getRecentMatches();

  echo "<table style=\"border-width: 0px\" width=300>\n";
  echo "<tr><td colspan=\"4\"><b>RECENT MATCHES</td><td align=\"right\">\n";
  echo "<a href=\"player.php?mode=allmatches\">(see all)</a></td></tr>\n";
  foreach ($matches as $match) {
    $res = "Draw";
    if ($match->playerWon($player->name)) {
      $res = "Win";
    }
    if ($match->playerLost($player->name)) {
      $res = "Loss";
    }
    if ($match->playera == $match->playerb) {
          $res = "BYE";
    }
    $opp = $match->playera;
    if (strcasecmp($player->name, $opp) == 0) {
      $opp = $match->playerb;
    }
    $event = new Event($match->getEventNamebyMatchid());
    
    echo "<td>".$event->name."</td><td>Round: ".$match->round."</td>";
    echo "<td width=\"4\"><b>$res</b> <b>{$match->getPlayerWins($player->name)}</b><b> - </b><b>{$match->getPlayerLosses($player->name)}</b></td>";
    echo "<td>vs.</td>\n";
    $oppplayer = new Player($opp);
    echo "<td>" . $oppplayer->linkTo() . "</td></tr>\n";
  }
  echo "</table>\n";
}

//copied above function and altered to show matches in progress
function print_currentMatchTable($Leagues) {
    global $player;
    $matches = $player->getCurrentMatches();

    echo "<table style=\"border-width: 0px\" width=300>\n";
    echo "<tr><td colspan=\"4\"><b>ACTIVE MATCHES</td><td align=\"right\">\n";
    echo "</td></tr>\n";
    foreach ($matches as $match) {
        $event = new Event($match->getEventNamebyMatchid());
        $opp = $match->playera;
        $player_number="b";
        if (strcasecmp($player->name, $opp) == 0) {
            $opp = $match->playerb;
            $player_number="a";
        }

        if ($match->result == "League") {
            leagueResultDropMenu();
            echo "<table><tr><td align=\"left\" colspan=\"2\">";
            leagueOpponentDropMenu($event, $round = 1); 
            echo "</td></tr></table>";
        }

        if ($match->result != "BYE") {
            $oppplayer = new Player($opp);
            echo "<tr><td>";
            echo $event->name." Round: ".$event->current_round." ";
            echo "</td>";
            echo "<td>vs.</td>\n";
            echo "<td>" . $oppplayer->linkTo() ."</td><td>";
            if ($match->verification == "unverified"){
                if ($player_number=="b" AND ($match->playerb_wins + $match->playerb_losses) > 0){
                    echo "(Report Submitted)";
                }else if ($player_number=="a" AND ($match->playera_wins + $match->playera_losses) > 0){
                    echo "(Report Submitted)";
                }else{
                    if ($match->player_reportable_check() == True){
                    echo "<a href=\"player.php?mode=submit_result&match_id=".$match->id."&player=".$player_number ."\">(Report Result)</a>";
                    } else {
                        echo "Please report results in the report channel for this event";
                    }
                }
            } else if ($match->verification == "failed") {
                echo "<font style=\"color: red; font-weight: bold;\">The reported result wasn't consistent with your opponent's, please check with the host  </style><a href=\"player.php?mode=submit_result&match_id=".$match->id."&player=".$player_number ."\">(Correct Result)</a>";
            } else if ($match->result == "BYE") {

            } else {
                echo "(Report Submitted)";
            }
            echo "</td></tr>\n";

        } else {
            if (($match->round == $event->current_round)&& $event->active){
                echo "<tr><td>";
                echo $event->name." Round: ".$event->current_round." ";
                echo "</td>";
                echo "<td>You have a BYE for the current round.</td>\n";
                echo "</tr>\n";
            }
        }
    }
    foreach ($Leagues as $League) {
        echo $League;
    }
    
    echo "</table>\n";
}

function leagueOpponentDropMenu($event, $round, $player, $subevent) {
  $player_standings = new Standings($event, $player->name);
  $playernames = $player_standings->League_getAvailable_Opponents($subevent, $round);
    
  echo "<select class=\"inputbox\" name=\"opponent\"> Opponent";
  
  if (count($playernames)) {
      foreach ($playernames as $playername) {
      echo "<option value=\"{$playername}\">{$playername}</option>";
      }
  } else {
      echo "<option value=\"\">-No Available Opponents-</option>";      
  }
  echo "</select>";
}

function leagueResultDropMenu() {
    echo "<select name=\"result\">";
    echo "<option value=\"\">- Match Result -</option>";
    echo "<option value=\"W20\">I won the match 2-0</option>";
    echo "<option value=\"W21\">I won the match 2-1</option>";
    echo "<option value=\"L20\">I loss the match 2-0</option>";
    echo "<option value=\"L21\">I loss the match 2-1</option>";
    echo "</select>";
}

function print_matchTable($player, $limit=0) {
  if (!isset($_POST['format'])) {
    $_POST['format'] = "%";
  }
  if (!isset($_POST['series'])) {
    $_POST['series'] = "%";
  }
  if (!isset($_POST['season'])) {
    $_POST['season'] = "%";
  }
  if (!isset($_POST['opp'])) {
    $_POST['opp'] = "%";
  }

  $matches = $player->getFilteredMatches($_POST['format'], $_POST['series'], $_POST['season'], $_POST['opp']);

  echo "<table class=\"scoreboard\">";
  echo "<tr class=\"top\"><th>Event</th><th>Round</th><th>Opponent</th><th>Deck</th><th>Rating</th><th>Result</th></tr>";
  $oldname = "";
  $rowcolor = "even";
  $Count = 1;
  foreach ($matches as $match) {
    $rnd = $match->round;
    if($match->timing == 2 && $match->type == "Single Elimination") {
      $rnd = "T" . pow(2, $match->rounds + 1 - $match->round);
    }
    
    if($match->type == "League") {
        $rnd = "L";
    }
    
    $opp = $match->otherPlayer($player->name);
    $res = "D";
    if ($match->playerWon($player->name)) {
      $res = "W";
    }
    if ($match->playerLost($player->name)) {
      $res = "L";
    }
    $opponent = new Player($opp);

    $event = $match->getEvent();
    $oppRating = $opponent->getRating("Composite", $event->start);
    $oppDeck = $opponent->getDeckEvent($event->name);
    $deckStr = "No Deck Found";
    
    if (!is_null($oppDeck)) {
        $deckStr = $oppDeck->linkTo();
    }
        
    if($oldname != $event->name) {
      if ($Count % 2 != 0) {
        $rowcolor = "odd";
        $Count++;
      } else {
        $rowcolor = "even";
        $Count++;
      }
      echo "<tr class=\"{$rowcolor}\"><td>{$event->name}</td>";
    }
    else {echo "<tr class=\"{$rowcolor}\"><td></td>\n";}
    $oldname = $event->name;
    echo "<td>$rnd</td>\n";
    echo "<td>" . $opponent->linkTo() . "</td>\n";
    echo "<td>$deckStr</td>\n";
    echo "<td>$oppRating</td>\n";
    echo "<td>$res {$match->getPlayerWins($player->name)} - {$match->getPlayerLosses($player->name)} </td>";
    echo "</tr>\n";
  }
  echo "</table>";
}

function print_ratingsTableSmall() {
  // TODO: Re-write this to only show ratings for formats the player has played in
  // TODO: Write this to look at each format from the formats table, instead of being hard coded
  global $player;
  $composite = $player->getRating("Composite");
  $standard = $player->getRating("Standard");
  $extended = $player->getRating("Extended");
  $modern = $player->getRating("Modern");
  $classic = $player->getRating("Classic");
  $legacy = $player->getRating("Legacy");
  $pauper = $player->getRating("Pauper");
  $silverblack = $player->getRating("SilverBlack");
  $heirloom =  $player->getRating("Heirloom");
  $commander = $player->getRating("Commander");
  $tribalWars = $player->getRating("Tribal Wars");
  $other = $player->getRating("Other Formats");

  echo "<table style=\"border-width: 0px;\" width=300>";
  echo "<tr><td colspan=1><b>MY RATINGS</td>\n";
  echo "<td colspan=1 align=\"right\">";
  echo "<a href=\"player.php?mode=allratings\">(see all)</a></td></tr>\n";
  
  if ($composite > 0) {
      echo "<tr><td>Composite</td><td align=\"right\">$composite</td></tr>\n";
  } else {
      echo "<tr><td>Composite</td><td align=\"right\">1600</td></tr>\n";
  }
  
  if ($standard > 0) {echo "<tr><td>Standard</td><td align=\"right\">$standard</td></tr>\n";}
  if ($extended > 0) {echo "<tr><td>Extended</td><td align=\"right\">$extended</td></tr>\n";}
  if ($modern > 0) {echo "<tr><td>Modern</td><td align=\"right\">$modern</td></tr>\n";}
  if ($classic > 0) {echo "<tr><td>Classic</td><td align=\"right\">$classic</td></tr>\n";}
  if ($legacy > 0) {echo "<tr><td>Legacy</td><td align=\"right\">$legacy</td></tr>\n";}
  if ($pauper > 0) {echo "<tr><td>Pauper</td><td align=\"right\">$pauper</td></tr>\n";}
  if ($silverblack > 0) {echo "<tr><td>SilverBlack</td><td align=\"right\">$silverblack</td></tr>\n";}
  if ($heirloom > 0) {echo "<tr><td>Heirloom</td><td align=\"right\">$heirloom</td></tr>\n";}
  if ($commander > 0) {echo "<tr><td>Commander</td><td align=\"right\">$commander</td></tr>\n";}
  if ($tribalWars > 0) {echo "<tr><td>Tribal Wars</td><td align=\"right\">$tribalWars</td></tr>\n";}
  if ($other > 0) {echo "<tr><td>Other Formats</td><td align=\"right\">$other</td></tr>\n";}
  echo "</table>";
}

function print_ratingsTable() {
  echo "<table style=\"border-width: 0px;\" width=400 align=\"center\">\n";
  echo "<tr><td><b>Format</td>\n";
  echo "<td align=\"center\"><b>Rating</td>\n";
  echo "<td align=\"center\"><b>Record</td>\n";
  echo "<td align=\"center\"><b>Low</td>\n";
  echo "<td align=\"center\"><b>High</td></tr>\n";
  print_ratingLine("Composite");
  print_ratingLine("Standard");
  print_ratingLine("Extended");
  print_ratingLine("Modern");
  print_ratingLine("Classic");
  print_ratingLine("Pauper");
  print_ratingLine("SilverBlack");
  print_ratingLine("Heirloom");
  print_ratingLine("Commander");
  print_ratingLine("Tribal Wars");
  print_ratingLine("Other Formats");
  echo "</table>\n";
}

function print_ratingLine($format) {
  global $player;
  $rating = $player->getRating($format);
  $record = $player->getRatingRecord($format);
  $max = $player->getMaxRating($format);
  $min = $player->getMinRating($format);

  echo "<tr><td>$format</td>\n";
  echo "<td align=\"center\">$rating</td>\n";
  echo "<td align=\"center\">$record</td>\n";
  if(isset($min)) {
    echo "<td align=\"center\">$min</td>\n";
    echo "<td align=\"center\">$max</td>\n";
  }
  else {
    echo "<td colspan=2 align=\"center\">";
    echo "<i>Less than 20 matches played</td>\n";
  }
  echo "</tr>\n";
}

function print_ratingsHistory($format) {
  global $player;
  $db = Database::getConnection();
  $stmt = $db->prepare("SELECT e.name, r.rating, n.medal, n.deck AS id
    FROM events e, entries n, ratings r
    WHERE r.format= ? AND r.player = ?
    AND e.start=r.updated AND n.player=r.player AND n.event=e.name
    ORDER BY e.start DESC");
  $stmt->bind_param("ss", $format, $player->name);
  $stmt->execute();
  $stmt->bind_result($eventname, $rating, $medal, $deckid);

  $stmt->store_result();

  echo "<table style=\"border-width: 0px;\" align=\"center\">\n";
  echo "<tr><td align=\"center\"><b>Pre-Event</td>\n";
  echo "<td><b>Event</td>\n";
  echo "<td><b>Deck</td>\n";
  echo "<td align=\"center\"><b>Record</td>\n";
  echo "<td align=\"center\"><b>Medal</td>\n";
  echo "<td align=\"center\"><b>Post-Event</td></tr>\n";

  if($stmt->num_rows > 0) {
    $stmt->fetch();
    $preveventname = $eventname;
    $prevrating = $rating;
    while($stmt->fetch()) {
      $entry = new Entry($preveventname, $player->name);
      $wl = $entry->recordString();
      $img = medalImgStr($entry->medal);

      echo "<tr><td align=\"center\">{$rating}</td>\n";
      echo "<td>{$preveventname}</td>\n";
      echo "<td>" . $entry->deck->linkTo() . "</td>\n";
      echo "<td align=\"center\">$wl</td>\n";
      echo "<td align=\"center\">$img</td>";
      echo "<td align=\"center\">{$prevrating}</td></tr>";
      $prevrating = $rating;
      $preveventname = $eventname;
    }

    $entry = new Entry($preveventname, $player->name);
    $wl = $entry->recordString();
    $img = medalImgStr($entry->medal);
    echo "<tr><td align=\"center\">1600</td>\n";
    echo "<td>{$preveventname}</td>\n";
    echo "<td>" . $entry->deck->linkTo() . "</td>\n";
    echo "<td align=\"center\">$wl</td>\n";
    echo "<td align=\"center\">$img</td>";
    echo "<td align=\"center\">{$prevrating}</td></tr>";
  } else {
    echo "<tr><td colspan=6 align=\"center\"><i>";
    echo "You have not played any $format events.</td></tr>\n";
  }
  echo "</table>\n";
}

function print_ratingHistoryForm($format) {
  $formats = array("Composite", "Standard", "Extended", "Modern", "Classic",
    "Pauper", "SilverBlack", "Heirloom", "Commander", "Tribal Wars", "Other Formats");
  echo "<center>\n";
  echo "<form action=\"player.php\" method=\"get\">\n";
  echo "Show history for&nbsp;";
  echo "<select class=\"inputbox\" name=\"format\">\n";
  for($i = 0; $i < sizeof($formats); $i++) {
    $sel = ($formats[$i] == $format) ? "selected" : "";
    echo "<option value=\"{$formats[$i]}\" $sel>{$formats[$i]}</option>\n";
  }
  echo "</select><br /><br />\n";
  echo "<input class=\"inputbutton\" type=\"submit\" name=\"button\" value=\"Show History\">\n";
  echo "<input type=\"hidden\" name=\"mode\" value=\"allratings\">\n";
  echo "</form></center>\n";
}

function print_allMatchForm($player) {
  if (!isset($_POST['format'])) {
    $_POST['format'] = "%";
  }
  if (!isset($_POST['series'])) {
    $_POST['series'] = "%";
  }
  if (!isset($_POST['season'])) {
    $_POST['season'] = "%";
  }
  if (!isset($_POST['opp'])) {
    $_POST['opp'] = "%";
  }
  echo "<form action=\"player.php\" method=\"post\">\n";
  echo "<table style=\"border-width: 0px;\" align=\"center\">\n";
  echo "<tr><td align=\"center\" colspan=2><b>Filters</td></tr>\n";
  echo "<tr><td>&nbsp;</td>\n";
  echo "<tr><td>Format&nbsp</td><td>";
  formatDropMenuP($player, $_POST['format']);
  echo "</td></tr>\n";
  echo "<tr><td>Series&nbsp;</td><td>";
  seriesDropMenuP($player, $_POST['series']);
  echo "</td></tr>\n";
  echo "<tr><td>Season&nbsp;</td><td>";
  seasonDropMenuP($player, $_POST['season']);
  echo "</td></tr>\n";
  echo "<tr><td>Opponent&nbsp;</td><td>";
  oppDropMenu($player, $_POST['opp']);
  echo "</td></tr><tr><td>&nbsp;</td></tr>\n";
  echo "<tr><td colspan=2 align=\"center\">";
  echo "<input type=\"submit\" name=\"mode\" value=\"Filter Matches\">";
  echo "</td></tr><tr><td>&nbsp;</td></tr></table></form>\n";
}

function formatDropMenuP($player, $def) {
  $formats = $player->getFormatsPlayed();

  echo "<select class=\"inputbox\" name=\"format\">\n";
  echo "<option value=\"%\">- Format -</option>\n";
  foreach ($formats as $thisformat) {
    $sel = ($thisformat == $def) ? "selected" : "";
    echo "<option value=\"$thisformat\" $sel>$thisformat</option>\n";
  }
  echo "</select>\n";
}

function seriesDropMenuP($player, $def) {
  $series = $player->getSeriesPlayed();

  echo "<select name=\"series\">\n";
  echo "<option value=\"%\">- Series -</option>\n";
  foreach ($series as $thisseries) {
    $sel = ($thisseries == $def) ? "selected" : "";
    echo "<option value=\"$thisseries\" $sel>$thisseries</option>\n";
  }
  echo "</select>\n";
}

function seasonDropMenuP($player, $def) {
  $seasons = $player->getSeasonsPlayed();

  echo "<select name=\"season\">\n";
  echo "<option value=\"%\">- Season -</option>\n";
  foreach ($seasons as $thisseason) {
    $sel = (($thisseason == $def) && ($def != "%")) ? "selected" : "";
    echo "<option value=\"$thisseason\" $sel>$thisseason</option>\n";
  }
  echo "</select>\n";
}

function oppDropMenu($player, $def) {
  $opponents = $player->getOpponents();

  echo "<select name=\"opp\">\n";
  echo "<option value=\"%\">- Opponent -</option>\n";
  foreach ($opponents as $row) {
    $thisopp = $row['opp'];
    $cnt = $row['cnt'];
    $sel = ($thisopp == $def) ? "selected" : "";
    echo "<option value=\"$thisopp\" $sel>$thisopp [$cnt]</option>\n";
  }
  echo "</select>";
}

function print_statsTable() {
  global $player;
  echo "<table style=\"border-width: 0px\">";
  echo "<tr><td colspan=2><b>STATISTICS</td></tr>\n";
  echo "<tr><td>Record</td><td align=\"right\"> {$player->getRecord()}";
  echo "</td></tr>\n";
  echo "<tr><td>Longest Winning Streak</td><td align=\"right\"> {$player->getStreak("W")}";
  echo "</td></tr>\n";
  echo "<tr><td>Longest Losing Streak</td><td align=\"right\"> {$player->getStreak("L")}";
  echo "</td></tr>\n";
  echo "<tr><td>Biggest Rival</td><td align=\"right\"> ";
  $rival = $player->getRival();
  if ($rival != null) {
    $rivalrec = $player->getRecordVs($rival->name);
    echo $rival->linkTo();
    echo " ({$rivalrec})";
  } else {
    echo "none";
  }
  echo "</td></tr>";
  echo "<tr><td>Favorite Card</td><td align=\"right\"> {$player->getFavoriteNonLand()}";
  echo "</td></tr>\n";
  echo "<tr><td>Favorite Land</td><td align=\"right\"> {$player->getFavoriteLand()}";
  echo "</td></tr>\n";
  echo "<tr><td>Medals Won</td><td align=\"right\"> {$player->getMedalCount()}";
  echo "</td></tr>\n";
  echo "<tr><td>Events Won</td><td align=\"right\"> {$player->getMedalCount("1st")}";
  echo "</td></tr>\n";
  echo "<tr><td>&nbsp;</td></tr>\n";
  echo "<tr><td colspan=2 align=\"center\"><b>Most Recent Trophy</td></tr>\n";
  echo "<tr><td colspan=2 align=\"center\">"; statTrophy();
  echo "</td></tr>\n";
  echo "</table>\n";
#Fave Series
#Fave Format
#highrating
#lowrating
#bestdeck
#creativity
}

function statTrophy() {
  global $player;
  $trophyevent = $player->getLastEventWithTrophy();
  if ($trophyevent != NULL) {
    $event = new Event($trophyevent);
    echo $event->getTrophyImageLink();
  } else {
    echo "<i>No trophies earned</i>\n";
  }
}

function print_conditionalAllDecks() {
  global $player;
  $noentrycount = $player->getUnenteredCount();
  if ($noentrycount > 0) {
    echo "<br /><a href=\"player.php?mode=alldecks\" style=\"color: red;\">";
    echo "You have $noentrycount unreported decks<br />";
    echo "Click here to enter them.</a>";
  }
}

?>
