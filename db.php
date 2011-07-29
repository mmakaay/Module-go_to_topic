<?php

if(!defined("PHORUM")) return;

function mod_go_to_topic_db_query($sql)
{
    $type = $GLOBALS["PHORUM"]["DBCONFIG"]["type"];

    switch ($type)
    {
      case "mysql":
        $conn = phorum_db_mysql_connect();
        $res = mysql_query($sql, $conn);
        if ($res && mysql_num_rows($res)) {
          $res = mysql_fetch_array($res);
        } else {
          $res = NULL;
        }
        break;

      case "mysqli":
        $conn = phorum_db_mysqli_connect();
        $res = mysqli_query($conn, $sql);
        if ($res && mysqli_num_rows($res)) {
          $res = mysqli_fetch_array($res);
        } else {
          $res = NULL;
        }
        break;

      case postgresql:
        $conn = phorum_db_postgresql_connect();
        $res = pg_query($conn, $sql);
        if ($res && pg_num_rows($res)) {
          $res = pg_fetch_row($res);
        } else {
          $res = NULL;
        }
        break;

      default:
        trigger_error(
            "The Go To Topic module currently contains no database " .
            "implementation for database type \"".htmlspecialchars($type)."\"",
            E_USER_ERROR
        );
    }

    return $res;
}

function mod_go_to_topic_get_thread_offset($message_id)
{
    $PHORUM = $GLOBALS["PHORUM"];

    settype($message_id, "int");

    // Find the thread id for the message_id. We need to look at the
    // threads to find the page on which a message_id can be found.
    $row = mod_go_to_topic_db_query(
        "SELECT thread, modifystamp
         FROM   {$PHORUM['message_table']}
         WHERE  message_id = $message_id"
    );
    if ($row === NULL) return NULL; // Message not found.
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
            $row = mod_go_to_topic_db_query(
                "SELECT modifystamp
                 FROM   {$PHORUM['message_table']}
                 WHERE  message_id = $thread_id"
            );
            $modifystamp = $row === NULL ? 0 : $row[0];
        }

        $condition = "modifystamp > $modifystamp";
    } else {
        $condition = "message_id > $thread_id";
    }

    // Now, find the offset of the message in the forum.
    $row = mod_go_to_topic_db_query(
        "SELECT count(*)
         FROM   {$PHORUM['message_table']}
         WHERE  $condition AND
                forum_id    = {$PHORUM['forum_id']} AND
                status      = ".PHORUM_STATUS_APPROVED." AND
                parent_id   = 0"
    );
    $offset = $row[0];

    return $offset;
}

?>
