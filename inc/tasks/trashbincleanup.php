<?php

function task_trashbincleanup($task)
{
	global $mybb, $db;
    
    $timestamp = strtotime(date("Y-m-d") ." -".intval($mybb->settings['trashbin_retention'])." day");
    
    // Delete threads older then Timestamp
    $query = $db->simple_select("trashbin_threads","tid","deletetime < ".$timestamp);
    while($thread = $db->fetch_array($query)){
        $db->delete_query("trashbin_posts","tid = ".$thread['tid']);
        $db->delete_query("trashbin_threads","tid = ".$thread['tid']);
    }
    
     $db->delete_query("trashbin_posts_single","deletetime < ".$timestamp);       
    
	add_task_log($task, "Trash bin cleaned older then ".date("Y-m-d",$timestamp));
}
