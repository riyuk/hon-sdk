<?php
// CARE
// USE THIS AT YOUR OWN RISK!
// * DONT USE MANY FUNCTION CALLS AT ONCE
// * YOU SHOULD CACHE THE RESPONSE
// * YOUR IP COULD BE TEMP.-BANNED IF YOU CALL MORE THEN 5 FUNCTIONS IN A SECOND
exit;

require_once( 'lib/hon.php' );

// If you set Debug to true, you see the output directly (no need to do var_dump)
hon::get()->auth( '<nickname>', '<password>' )->debug( true );

$myLastMatches = hon::get()->grab_last_matches( array( 'account_id' => 'self' ) );

$myMatesLastMatches = hon::get()->debug(true)->grab_last_matches( array( 'account_id' => 355466 ) );

$myMatesId = hon::get()->nick2id( array( 'nickname[0]' => 'riyuk' ) );

// needs to be at least 3 chars
$mySearchRequest = hon::get()->autocompleteNicks( array( 'nickname' => 'riyuk' ) );

// Since you're authed you can do "self" for your Account-Id
$allMyStats = hon::get()->get_all_stats( array( 'account_id[0]' => 'self' ) );

$allMyBuddysAccountStats = hon::get()->get_all_stats( array( 'account_id[0]' => 355466 ) );

$matchStatistics = hon::get()->get_match_stats( array( 'match_id[0]' => 97506502 ) );

// not sure if they still work - but add new Buddy:
hon::get()->new_buddy( array( 'buddy_id' => 355466 ) );
hon::get()->remove_buddy( array( 'buddy_id' => 355466 ) );