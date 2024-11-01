<?php
/*
Plugin Name: Word Search Maker
Version: 1.0
Plugin URI: 
Description: Paste something like the following onto a page or post to display a random word search puzzle (x15 and y25 can be omitted, they set the dimensions): &lt;!--word-search-maker--|x15||y25||red||yellow||blue|--word-search-maker--&gt;
Author: Alan Borgolotto
Author URI: 
License: GPL2
*/

/*Copyright (c) 2011, Alan Borgolotto
All rights reserved.

Redistribution and use in source and binary forms, with or without
modification, are permitted provided that the following conditions are met:
    * Redistributions of source code must retain the above copyright
      notice, this list of conditions and the following disclaimer.
    * Redistributions in binary form must reproduce the above copyright
      notice, this list of conditions and the following disclaimer in the
      documentation and/or other materials provided with the distribution.
    * Neither the name of Scott Swan nor the
      names of its contributors may be used to endorse or promote products
      derived from this software without specific prior written permission.

THIS SOFTWARE IS PROVIDED BY Scott Swan ''AS IS'' AND ANY
EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
DISCLAIMED. IN NO EVENT SHALL Scott Swan BE LIABLE FOR ANY
DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
(INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND
ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
(INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.*/

////////////////////////////////////////////////////////////////////////////////////////////////////
// Filename:      wordsearchmaker.php
// Creation date: 19 May 2011
//
// Usage:
//   This script will create a word search puzzle. 
//
// Version history:
//   1.0 - 19 May 2011: Initial release
//
////////////////////////////////////////////////////////////////////////////////////////////////////

wp_enqueue_style('wordsearchmakerstyle', '/wp-content/plugins/wordsearchmaker/wordsearchmakercss.css');
wp_enqueue_script('wordsearchmakerstyle', '/wp-content/plugins/wordsearchmaker/wordsearchmakerjs.js');

// number of rows and columns in word search puzzle
$MAXROW = 20;
$MAXCOL = 20;
// word search puzzle, holds letters
$wsp = null;

// sort according to string length, longest first
function wsm_my_sort($a, $b){
  if (strlen($a) == strlen($b)) return 0;
  return (strlen($a) > strlen($b)) ? -1 : 1;
}

function caseins_sort($a, $b){
  return strcmp(strtoupper($a),strtoupper($b));
}

$arr = null;

/* Checks if the word $wrd can fit in the word search puzzle, starting from the position $wspi
and $wspj, and going in the direction $it and $jt.  
*/
function traverseWSP($wrd, $wspi, $wspj, $it, $jt) {
	global $wsp, $MAXROW, $MAXCOL;
	$i = $wspi;
	$j = $wspj;
	$retval = 0;
	for ($wrdind = 1; $wrdind < strlen($wrd); $wrdind++){
		// first letter has already been checked
		$i = $i + $it;
		$j = $j + $jt;
		if ($wsp[$i][$j] != null){
			if ($wrd[$wrdind] != $wsp[$i][$j]){
				// position in word is already taken by a different letter so word cannot fit
				return 0;
			}else{
				// returning 2 instead of 1 signifies that word will traverse another existing word
				$retval = 1;
			}
		}
	}
	return 1 + $retval;
}

// Replace plugin's secret code with a word search puzzle if code is found
function wordsearchmaker($text){

	global $wsp, $MAXROW, $MAXCOL;
	$arr1 = null;
	// Example string to put in a wordpress post or page
	// <!--word-search-maker--|x25||y20||cleaner||jewelry box||broach||promise ring||engagement ring||wedding band||anklet||pendant||bangle||charm bracelet||locket||earrings||navel pins||chain||gemstones||gold||silver||copper||platinum||medical alert IDs||engraved||religious||birthstone||antique||trading up||clip on earrings||toe ring|--word-search-maker-->
	if (preg_match('/<!--word-search-maker--(\|[^\|]+\|)+--word-search-maker-->/',$text,$rexmatch) == 1 or
			preg_match('/&lt;!--word-search-maker--(\|[^\|]+\|)+--word-search-maker--&gt;/',$text,$rexmatch) == 1){
		// Full string found by regular expression
		$wsm_string = $rexmatch[0];
		preg_match_all('/\|([^\|]+)\|/',$wsm_string,$arr1,PREG_PATTERN_ORDER);
		// Array of strings found by regular expression goes in $arr1[0], $arr1[1] holds the strings
		// contained in the () of the regular expression.
		$arr = $arr1[1];
		// first entry holds number of columns if it matches x followed by a number
		if (preg_match('/^x([[:digit:]]+)$/',$arr[0],$rexmatch) == 1){
			$MAXCOL = $rexmatch[1];
			array_shift($arr);
		}
		// second entry (or maybe first) holds number of rows if it matches y followed by a number
		if (preg_match('/^y([[:digit:]]+)$/',$arr[0],$rexmatch) == 1){
			$MAXROW = $rexmatch[1];
			array_shift($arr);
		}
	}else{
		return $text;
	}
	$arrlen = count($arr);

	// user didnt type any words for word search puzzle
	if ($arrlen == 0){return str_replace($wsm_string,'',$text);}

	usort($arr,"caseins_sort");
	$valperrow = ceil($arrlen / 3);
	// div is used to denote area that gets printed. I need to display array in 3 columns,
	// in alphabetical order going down (not going across, thus the 2 for loops and fancy increment).
	$retval = "<div id='wsm_html'><table class='wsm_words'>";
	for ($i = 0; $i < $valperrow; $i++){
		$retval .= "<tr class='wsm_words'>";
		for ($j = $i; $j < $arrlen; $j += $valperrow){
			$retval .= "<td class='wsm_words'>" . $arr[$j] . "</td>";
		}
		$retval .= "</tr>";
	}	
	$retval .= "</table>";

	// capitalize everything in array
	foreach (array_keys($arr) as $arrkey){
		$arr[$arrkey] = strtoupper($arr[$arrkey]);
	}
	// want to fit longest words in puzzle before shorter ones
	usort($arr, "wsm_my_sort");

	$wsp = null;
	for ($i = 1; $i <= $MAXROW; $i++){
		for ($j = 1; $j <= $MAXCOL; $j++){
			$wsp[$i][$j] = null;
		}
	}

	$matches = null;
	$wrdind = 0;
	$wrdpos = null;

	foreach ($arr as $wrd){
		$wrd = trim($wrd);
		$wrd = str_replace(" ","",$wrd);
		$wrdlen = strlen($wrd);
		// matches holds the number of times, for a certain direction, that a word fits in the puzzle.
		// When the direction for a word is randomly chosen, a match is randomly selected. (the match is
		// randomly selected by iterating through the loops again until I hit the random number)
		// Second array of matches is used only for matches where the word crosses another existing word in the puzzle.
		for ($i = 0; $i <= 1; $i++){
			for ($j = 0; $j <= 8; $j++){
				$matches[$i][$j] = 0;
			}
		}
		// iterate through every point in word search puzzle, attempt every direction, and increment
		// matches if word fits using a certain starting point and direction.
		for ($i = 1; $i <= $MAXROW; $i++){
			for ($j = 1; $j <= $MAXCOL; $j++){
				if ($wsp[$i][$j] == null or $wrd[0] == $wsp[$i][$j]){
					$wrdind = 0;
					// $it and $jt hold the direction the word will go, of course when they're both zero
					// we skip to next direction.
					for ($it = -1; $it < 2; $it++) {
						for ($jt = -1; $jt < 2; $jt++) {
							if ($it == 0 and $jt == 0){
								$tmp = 0;
							}else{	
								// check if word falls out of bounds
								$lasti = $i + $it * ($wrdlen - 1);
								if ($lasti < 1 or $lasti > $MAXROW){
									$tmp = 0;
								}else{
									$lastj = $j + $jt * ($wrdlen - 1);
									if ($lastj < 1 or $lastj > $MAXCOL){
										$tmp = 0;
									}else{
										// word doesnt fall out of bounds so check that there are no existing
										// letters in puzzle that conflict with the word..
										$tmp = traverseWSP($wrd,$i,$j,$it,$jt);
									}
								}
							}
							if ($tmp > 0) {$matches[0][$wrdind]++;}
							if ($tmp == 2) {$matches[1][$wrdind]++;}
							$wrdpos[$i][$j][$wrdind] = $tmp;
							$wrdind++;
						}
					}
				}else{
					// first letter doesn't match existing letter in the puzzle
					for ($wrdind = 0; $wrdind < 9; $wrdind++){
						$wrdpos[$i][$j][$wrdind] = 0;
					}
				}
			}
		}

		// Choose direction randomly, from list of available directions. I want there to be a 75%
		// chance that the word will cross an existing word. $xwrd will be 1 if the word is going
		// to cross an existing word.
		$xwrd = rand(0,3);
		if ($xwrd > 0) { $xwrd = 1;}
		// holds number of possible directions for word, regardless of starting point
		$summatches = 0;
		for ($tmp = 0; $tmp < 9; $tmp++){
			if ($matches[$xwrd][$tmp] > 0){ $summatches++; }
		}
		// Use the available directions from the array that doesn't require that the word crosses another existing word.
		if ($xwrd == 1 and $summatches == 0) {
			$xwrd = 0;
			for ($tmp = 0; $tmp < 9; $tmp++){
				if ($matches[$xwrd][$tmp] > 0){ $summatches++; }
			}
		}
		// word doesn't fit in puzzle in any way
		if ($summatches == 0) {return str_replace($wsm_string,'',$text);}
		$dir = rand(1,$summatches);

		$i = 0;
		// holds the direction of the word from the possible 8 directions
		$direction = 0;
		for ($tmp = 0; $tmp < 9; $tmp++){
			if ($matches[$xwrd][$tmp] > 0) {
				$i++;
				if ($i == $dir) {
					$direction = $tmp;
					break;
				}
			}
		}
		
		// choose a starting point randomly from the list of starting points that are possible with
		// the chosen direction.
		$match = rand(1,$matches[$xwrd][$direction]);

		// iterate through every point in the puzzle and every direction until we reach the randomly
		// selected number ($match). At that point we have the randomly chosen starting point and
		// direction for the word.
		$tmp = 0;
		for ($i = 1; $i <= $MAXROW; $i++){
			for ($j = 1; $j <= $MAXCOL; $j++){
				$wrdind = 0;
				for ($it = -1; $it < 2; $it++) {
					for ($jt = -1; $jt < 2; $jt++) {
						// $wrdpos[$i][$j][$wrdind] is 2 if the word, at the current starting point and direction, will cross another existing word.
						if ($wrdpos[$i][$j][$wrdind] > $xwrd){
							if ($wrdind == $direction){
								$tmp++;
								if ($match == $tmp){
									// copy word into puzzle
									for ($ii = $i, $jj = $j, $wrdind2 = 0; $wrdind2 < strlen($wrd); $ii += $it, $jj += $jt, $wrdind2++){
										$wsp[$ii][$jj] = $wrd[$wrdind2];
									}
									break 4;
								}
							}
						}	
						$wrdind++;
					}
				}		
			}
		}	
	}

	// holds the solution to the puzzle which user can show/hide with a button
	$wsm_hidden = "<table class='wsm_solution' id='wsm_solution_id'>";
	$retval .= "<table class='wsm_puzzle'>";
	for ($i = 1; $i <= $MAXROW; $i++){
		$retval .= "<tr class='wsm_puzzle'>";
		$wsm_hidden .= "<tr class='wsm_solution'>";
		for ($j = 1; $j <= $MAXCOL; $j++){
			if ($wsp[$i][$j] == null){
				// select a random letter
				$retval .= "<td class='wsm_puzzle'>" . chr(rand(65,90)) . "</td>";
				$wsm_hidden .= "<td class='wsm_solution'> </td>";
			}else{
				$retval .= "<td class='wsm_puzzle'>" . $wsp[$i][$j] . "</td>";
				$wsm_hidden .= "<td class='wsm_solution'>" . $wsp[$i][$j] . "</td>";
			}
		}
		$retval .= "</tr>";
		$wsm_hidden .= "</tr>";
	}
	$wsm_hidden .= "</table>";
	
	$retval .= "</table></div>";
	$retval .= "<input type=\"button\" value=\"Print\" onclick=\"printwordsearchpuzzle('wsm_html');\"></input>";
	$retval .= "<input type=\"button\" value=\"Show/Hide Solution\" onclick=\"showhidewordsearchsolution();\"></input>";
	$retval .= $wsm_hidden;
	return str_replace($wsm_string,$retval,$text);
}

add_filter('the_content', 'wordsearchmaker',2);
//add_filter('the_posts', 'wordsearchmaker_conditionally_add_scripts_and_styles'); // the_posts gets triggered before wp_head
function wordsearchmaker_conditionally_add_scripts_and_styles($posts){
	if (empty($posts)) return $posts;
 
	$shortcode_found = false; // use this flag to see if styles and scripts need to be enqueued
	foreach ($posts as $post) {
		if (strpos($post->post_content, '<!--word-search-maker--') or strpos($post->post_content, '&lt;!--word-search-maker--')) {
			$shortcode_found = true; // bingo!
			break;
		}
	}
 
	if ($shortcode_found) {
		// enqueue here
		wp_enqueue_style('wordsearchmakerstyle', '/wp-content/plugins/wordsearchmaker/wordsearchmakercss.css');
		wp_enqueue_script('wordsearchmakerstyle', '/wp-content/plugins/wordsearchmaker/wordsearchmakerjs.js');
	}
 
	return $posts;
}
?>
