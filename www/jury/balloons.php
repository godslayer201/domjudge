<?php
/**
 * Tool to coordinate the handing out of balloons to teams that solved
 * a problem. Similar to the balloons-daemon, but web-based.
 *
 * $Id$
 *
 * Part of the DOMjudge Programming Contest Jury System and licenced
 * under the GNU GPL. See README and COPYING for details.
 */

require('init.php');
$title = 'Balloon Status';

if ( isset($_POST['done']) ) {
	foreach($_POST['done'] as $done => $dummy) {
		$parts = explode(';', $done);
		$DB->q('UPDATE scoreboard_jury SET balloon=1
			WHERE probid = %s AND teamid = %s AND cid = %i',
			$parts[0], $parts[1], $parts[2]);
	}
}

$refresh = '30;url=' . getBaseURI() . 'jury/balloons.php';
require('../header.php');
require('../forms.php');

echo "<h1>Balloon Status</h1>\n\n";

if ( isset($cdata['lastscoreupdate']) &&
     time() > strtotime($cdata['lastscoreupdate']) ) {
	echo "<h4>Scoreboard is now frozen.</h4>\n\n";
}

// Problem metadata: colours and names.
$probs_data = $DB->q('KEYTABLE SELECT probid AS ARRAYKEY,name,color
		      FROM problem WHERE cid = %i', $cid);

// Get all relevant info from the scoreboard_jury table.
// Order by balloon, so we have the unsent balloons at the top.
// Then by submittime, so the newest will also rank highest.
$res = $DB->q('SELECT s.*,t.login,t.name as teamname,t.room
       FROM scoreboard_jury s
       LEFT JOIN team t ON (t.login = s.teamid)
       WHERE s.cid = %i AND s.is_correct = 1
       ORDER BY s.balloon, s.totaltime DESC',
       $cid);

/* Loop over the result, store the total of balloons for a team
 * (saves a query within the inner loop).
 * We need to store the rows aswell because we can only next()
 * once over the db result.
 */
$BALLOONS = $TOTAL_BALLOONS = array();
while ( $row = $res->next() ) {
	$BALLOONS[] = $row;
	$TOTAL_BALLOONS[$row['login']][] = $row['probid'];
}

if ( !empty($BALLOONS) ) {
	echo addForm('balloons.php');

	echo "<table>\n" .
		"<tr><th colspan=\"2\">Team</th><th>Room</th><th>Solved</th><th>Total</th></tr>\n";

	foreach ( $BALLOONS as $row ) {

		echo '<tr'  . ( $row['balloon'] == 1 ? ' class="disabled"' : '' ) . '>';
		echo '<td class="teamid">' . htmlspecialchars($row['login']) . '</td><td>' .
			htmlspecialchars($row['teamname']) . '</td><td>' .
			htmlspecialchars($row['room']) . '</td><td>' .
			$row['probid'] . ' <span style="color: ' . 
			htmlspecialchars($probs_data[$row['probid']]['color']) .
			'">' . BALLOON_SYM . '</span></td><td>';
		sort($TOTAL_BALLOONS[$row['login']]);
		foreach($TOTAL_BALLOONS[$row['login']] as $prob_solved) {
			echo '<span title="' .
				htmlspecialchars($prob_solved) .
				'" style="color: ' .
				htmlspecialchars($probs_data[$prob_solved]['color']) .
				'">' . BALLOON_SYM . '</span> ';
		}
		echo '</td><td>';
		if ( $row['balloon'] == 0 ) {
			echo '<input type="submit" name="done[' .
				htmlspecialchars($row['probid']) . ';' .
				htmlspecialchars($row['teamid']) . ';' .
				htmlspecialchars($row['cid']) . ']" value="done" />';
		}
		echo "</td></tr>\n";
	}

	echo "</table>\n\n" . addEndForm();
} else {
	echo "<p><em>No correct submissions yet... keep posted!</em></p>\n\n";
}


require('../footer.php');
