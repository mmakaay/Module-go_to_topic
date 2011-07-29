<?php

if(!defined("PHORUM")) return;

function mod_go_to_topic_get_thread_offset($message_id)
{
    $PHORUM = $GLOBALS["PHORUM"];

    settype($message_id, "int");

    // Find the thread id for the message_id. We need to look at the
    // threads to find the page on which a message_id can be found.
    $row = phorum_db_interact(
        DB_RETURN_ROW,
        "SELECT thread, modifystamp, sort
         FROM   {$PHORUM['message_table']}
         WHERE  message_id = $message_id"
    );
    if ($row === NULL) return NULL; // Message not found.

    // When this is a sticky message, then it will always be shown
    // on page 1.
    if ($row[2] == PHORUM_SORT_STICKY) {
      return 0;
    }

    $thread_id   = $row[0];
    $modifystamp = $row[1];

    // Create the SQL condition for selecting threads that come
    // before the given $thread_id.
    if ($PHORUM["float_to_top"])
    {
        // For float_to_top, we have to compare against the modifystamp.
        // So find the modifystamp for the thread.
        // Skip the database lookup if we already found the correct
        // modifystamp above.
        if (!$modifystamp || $message_id != $thread_id)
        {
            $row = phorum_db_interact(
                DB_RETURN_ROW,
                "SELECT modifystamp
                 FROM   {$PHORUM['message_table']}
                 WHERE  message_id = $thread_id"
            );
            $modifystamp = $row === NULL ? 0 : $row[0];
        }

        $condition =
            "(" .
            "modifystamp > $modifystamp OR " .
            "(modifystamp = $modifystamp AND message_id > $thread_id)" .
            ")";
    } else {
        $condition = "message_id > $thread_id";
    }

    // Now, find the offset of the message in the forum.
    $offset = phorum_db_interact(
        DB_RETURN_VALUE,
        "SELECT count(*)
         FROM   {$PHORUM['message_table']}
         WHERE  $condition AND
                forum_id    = {$PHORUM['forum_id']} AND
                status      = ".PHORUM_STATUS_APPROVED." AND
                parent_id   = 0"
    );

    // Compensate for the sticky threads.
    // This is done in a separate query, so the counting can make use of
    // an index. When combined with the query from above, the performance
    // was lacking.
    $skip_special_threads = phorum_db_interact(
        DB_RETURN_VALUE,
        "SELECT count(*)
         FROM   {$PHORUM['message_table']}
         WHERE  forum_id    = {$PHORUM['forum_id']} AND
                status      = ".PHORUM_STATUS_APPROVED." AND
                sort        = ".PHORUM_SORT_STICKY." AND
                parent_id   = 0"
    );

    return ($offset - $skip_special_threads);
}

?>
