<?php

class Follow {
	
//TODO set up this object and use it instead of associative arrays!
	
}


class FollowDAO {	

	// if followExists
		//updateLastSeen
	// else
		//insert New Follow
	function followExists($user_id, $follower_id) {
		$q = "
			SELECT 
				user_id, follower_id
			FROM 
				follows
			WHERE 
				user_id = ".$user_id." AND follower_id=".$follower_id.";";
		$sql_result = mysql_query($q) or die('Error, selection query failed:' .$q );
		if ( mysql_num_rows($sql_result) > 0 )
			return true;
		else
			return false;		
	}


	function update($user_id, $follower_id) {
		$q = "
			UPDATE 
			 	follows
			SET
				last_seen=NOW()
			WHERE
				user_id = ".$user_id." AND follower_id=".$follower_id.";";
		$sql_result = mysql_query($q) or die('Error, update failed:' .$q );
		if (mysql_affected_rows() > 0)
			return true;
		else
			return false;
	}
	
	function insert($user_id, $follower_id) {
		$q = "
			INSERT INTO
				follows (user_id,follower_id,last_seen)
				VALUES (
					".$user_id.",".$follower_id.",NOW()
				);";
		$foo = mysql_query($q) or die('Error, insert query failed: '. $q );
		if (mysql_affected_rows() > 0)
			return true;
		else
			return false;
	}
	
	function getUnloadedFollowerDetails($user_id) {
		$q = "
			SELECT
				follower_id
			FROM 
				follows f 
			WHERE 
				f.follower_id  NOT IN (SELECT user_id FROM users) 
			 	AND f.user_id=".$user_id."
				AND error is NULL;";
		$sql_result = mysql_query($q)  or die("Error, selection query failed: $sql_query");
		$strays = array();
		while ($row = mysql_fetch_assoc($sql_result)) { $strays[] = $row; }
		mysql_free_result($sql_result);	
		return $strays;
		
	}
	
	function saveError($user_id, $follower_id, $error) {
		$q = "
			UPDATE 
			 	follows
			SET
				error='".$error."'
			WHERE
				user_id = ".$user_id." AND follower_id=".$follower_id.";";
		$sql_result = mysql_query($q) or die('Error, update failed:' .$q );
		if (mysql_affected_rows() > 0)
			return true;
		else
			return false;
	}
	
	function getFollowsWithErrors($user_id) {
		$q = "
			SELECT
				follower_id
			FROM 
				follows f 
			WHERE 
				error IS NOT NULL
			 	AND f.user_id=".$user_id.";";
		$sql_result = mysql_query($q)  or die("Error, selection query failed: $sql_query");
		$ferrors = array();
		while ($row = mysql_fetch_assoc($sql_result)) { $ferrors[] = $row; }
		mysql_free_result($sql_result);	
		return $ferrors;		
		
	}
}

?>