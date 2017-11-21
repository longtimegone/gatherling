<?php
$gatherlingoutofservice = 0;
if ($gatherlingoutofservice != 1)
{
    header('<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">');
    include 'lib.php'; 
    include 'config.php';
    session_start();
    print_header("Home");
    ?>

    <div id="maincolumn" class="grid_10 prefix_1 suffix_1">
        <div id="gatherling_main" class="box">
            <div class="alpha omega uppertitle">Gatherling</div>
            <div class="clear"></div>
            <p>
                Welcome to Gatherling!  With Gatherling you can keep track of your decks in order to see what you played last tournament, last month, or even last year.  You can keep track of all of your decks played here at the Gatherling.com tournaments, and Gatherling will keep a record of how they do.
            </p>
            <div class="alpha grid_5">
            <p><b>Some good starting points:</b></p>
                <ul>
                    <li><a href="eventreport.php"> See a list of recent events</a></li>
                    <li><a href="decksearch.php"> Search for decks with a certain card</a></li>
                </ul>
            <p><b>Gatherling Statistics:</b></p>
                <ul>

                    <li> Current Session Timeout: <?php echo (Event::session_timeout_stat() / 60) ?> minutes.</li>
                    
                    <li> There are <?php echo Deck::uniqueCount() ?> unique decks. </li>
                    <li> We have recorded <?php echo Match::count() ?> matches from <?php echo Event::count() ?> events.</li>
                    <li> There are <?php echo Player::activeCount() ?> active players in gatherling. (<?php echo Player::verifiedCount() ?> verified) </li>
                </ul>
            </div>

            <div class="grid_5 omega">
                <p>
                    <?php $player = Player::getSessionPlayer(); ?>
                    <?php if ($player != NULL): ?>
                    <b> Welcome back <?php echo $player->name ?> </b>
                    <ul>
                        <li> <a href="profile.php">Check out your profile</a> </li>
                        <li> <a href="player.php?mode=alldecks">Enter your own decklists</a> </li>
                        <?php $event = Event::findMostRecentByHost($player->name);
                        if (!is_null($event)) { ?>
                            <li> <a href="event.php?name=<?php echo $event->name ?>">Manage <?php echo $event->name ?></a> </li>
                        <?php } ?>
                        <?php if ($player->isHost()) { ?>
                            <li> <a href="event.php">Host Control Panel</a></li>
                        <?php } ?>
                    </ul>
                    <?php else: ?>
                        <b>Login to Gatherling</b>
                        <form action="login.php" method="post">
                            <table class="form" align="left" style="border-width: 0px" cellpadding="3">
                                <tr>
                                    <th>MTGO Username</th>
                                    <td><input class="inputbox" type="text" name="username" value="" /></td>
                                </tr>
                                <tr>
                                    <th>Gatherling Password</th>
                                    <td><input class="inputbox" type="password" name="password" value="" /></td>
                                </tr>
                                <tr>
                                    <td colspan="2" class="buttons">
                                    <input class="inputbutton" type="submit" name="mode" value="Log In" /> <br />
                                    <a href="register.php">Need to register?</a>
                                    </td>
                                </tr>
                            </table>
                        </form>
                    <?php endif; ?>
            </div> <!-- grid_5 omega (login/links) -->
            <div class="clear"></div>
        </div> <!-- gatherlingmain box -->

        <div class="gatherling_news box">
            <div class=" alpha omega uppertitle"> Gatherling News: Update to Gatherling 4! </div>
            <div class="clear"></div>
                    <?php require_once ("news.php"); ?>
            <div class="clear"></div>
        </div> <!-- box gatherlingnews -->   

        <div class="gatherling_news box">
            <div class=" alpha omega uppertitle"> Bug Report: Know of a bug not listed here? <a href="message.php?mode=Send&type=Bug Report">Message Dabil!</a></div>
            <div class="clear"></div>
                <ul>
                    <?php require_once ("bugs.php"); ?>
                </ul>
            <div class="clear"></div>
        </div> <!-- box gatherlingnews -->   

        <div class="gatherling_news box">
            <div class=" alpha omega uppertitle"> Planned Updates: Got an idea you would like to see added? <a href="message.php?mode=Send&type=Update Request">Message Dabil!</a></div>
            <div class="clear"></div>
                <ul>
                    <?php require_once ("updates.php"); ?>
                </ul>
            <div class="clear"></div>
        </div> <!-- box gatherlingnews -->   
        
    </div>

    <?php print_footer(); ?> 
    
<?php 
}
else
{
    require ("outofservice.php");
}
?>