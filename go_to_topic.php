<?php

// Check if we are loaded from the Phorum code.
// Direct access to this file is not allowed.
if (! defined("PHORUM")) return;

// Load the database layer code.
require_once("./mods/go_to_topic/db.php");

function phorum_mod_go_to_topic_common()
{
    global $PHORUM;

    // Catch requests for the list page, which have a gototopic argument set.
    if (phorum_page == "list" && isset($PHORUM["args"]["gototopic"])) {
        phorum_mod_go_to_topic_redirect($PHORUM["args"]["gototopic"]);
    }

    // On the read page, format the gototopic URL.
    elseif (phorum_page == "read" && isset($PHORUM["args"][1]))
    {
        // Point back to a message id if we have one and if
        // we're running in threaded list mode. Else point back
        // to the thread id.
        $id = (isset($PHORUM["args"][2]) && $PHORUM["threaded_list"]) 
            ? $PHORUM["args"][2] : $PHORUM["args"][1];

        $url = phorum_get_url(
            PHORUM_LIST_URL, 
            $PHORUM["forum_id"], 
            "gototopic=$id"
        );
        $PHORUM["DATA"]["GO_TO_TOPIC_URL"] = $url;
    }
}

function phorum_mod_go_to_topic_post_post($msg)
{
    $PHORUM = $GLOBALS["PHORUM"];

    // If a reply to a standard thread is posted, the we do a redirect
    // to the message or thread on the list page.
    if ($msg["parent_id"] != 0 && $msg["sort"] == PHORUM_SORT_DEFAULT)
    {
        // Redirect to the thread or the message? If the forum is in
        // threaded list mode, then we can redirect to the message.
        $id = $PHORUM["threaded_list"]
            ? $msg["message_id"] : $msg["thread"];

        phorum_mod_go_to_topic_redirect($id);
    }

    return $msg;
}

function phorum_mod_go_to_topic_redirect($message_id)
{
    $PHORUM = $GLOBALS["PHORUM"];

    settype($message_id, "int");

    // Determine the number of messages that goes on a list page.
    $list_length = $PHORUM['threaded_list'] 
                 ? $PHORUM['list_length_threaded'] 
                 : $PHORUM['list_length_flat']; 

    // Find the thread offset for the message in the forum.
    $offset = mod_go_to_topic_get_thread_offset($message_id);

    // If the offset is NULL, something went wrong in the database
    // layer. Do not do any redirection in this case.
    if ($offset === NULL) return;

    // Compute the list page on which the message can be found.
    $page = floor($offset / $list_length) + 1;

    // Generate the list page to redirect the user to.
    $url = phorum_get_url(
        PHORUM_LIST_URL,
        $PHORUM["forum_id"],
        "page=$page#msg-$message_id"
    );

    // Redirect the user. We wrap the redirect because of an MSIE bug.
    // See the comments in redirect.php why we need this hack.
    phorum_redirect_by_url(phorum_get_url(
        PHORUM_REDIRECT_URL,
        'phorum_redirect_to=' . urlencode($url)
    ));

    exit();
}
?>
