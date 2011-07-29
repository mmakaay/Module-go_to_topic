<?php

// Check if we are loaded from the Phorum code.
// Direct access to this file is not allowed.
if (! defined("PHORUM")) return;

// Load the database layer code.
require_once("./mods/go_to_topic/db.php");

// Apply default settings. When changing this code, then remember to update
// the defaults in settings.php as well.
if (!isset($PHORUM['mod_go_to_topic'])) {
    $PHORUM['mod_go_to_topic'] = array( 
        'replace_original_list_urls' => TRUE
    );  
}   

function phorum_mod_go_to_topic_page_list()
{
    global $PHORUM;

    // Catch requests that carry a gototopic argument.
    if (isset($PHORUM['args']['gototopic'])) {
        phorum_mod_go_to_topic_redirect($PHORUM['args']['gototopic']);
    }
}

function phorum_mod_go_to_topic_start_output()
{
    global $PHORUM;

    // Find the forum, thread and message id for the message that we
    // want to jump to on the list page.
    $forum_id   = $PHORUM['forum_id'];
    $thread_id  = NULL;
    $message_id = NULL;

    // On the read page, we can extract the data from the arguments.
    if (phorum_page === 'read')
    {
        // Only do the Go To Topic URL formatting if we have the
        // necessary data available in the URL.
        if (!empty($PHORUM['args'][1]))
        {
            $thread_id  = $PHORUM['args'][1];
            $message_id = empty($PHORUM['args'][2])
                        ? $thread_id : $PHORUM['args'][2];
        }
    }
    // On other pages, we might have the message available in the
    // ref_thread_id and ref_message_id parameters.
    else
    {
        if (!empty($PHORUM['args']['ref_thread_id'])) {
            $thread_id = $PHORUM['args']['ref_thread_id'];
            $message_id = empty($PHORUM['args']['ref_message_id'])
                        ? $thread_id : $PHORUM['args']['ref_message_id'];
        }
    }

    // Thread or message id found? Then generate the Go To Topic URL.
    if ($thread_id !== NULL)
    {
        // Point back to a message id if we are running in threaded list
        // mode. Else point back to the thread id.
        $id = $PHORUM['threaded_list'] ? $message_id : $thread_id;

        // Generate the URL.
        $url = phorum_get_url(PHORUM_LIST_URL, $forum_id, "gototopic=$id");

        // Make the URL available to template authors.
        $PHORUM['DATA']['GO_TO_TOPIC_URL'] = $url;

        // If the module is configured to automatically replace the
        // list URLs in the page, then handle that now.
        if (!empty($PHORUM['mod_go_to_topic']['replace_original_list_urls']))
        {
            // Update the plain list URL.
            $PHORUM['DATA']['URL']['LIST'] = $url;

            // Fix the breadcrumbs.
            foreach ($PHORUM['DATA']['BREADCRUMBS'] as $id => $node) {
               if ($node['TYPE'] == 'forum' && $node['ID'] == $forum_id) {
                   $PHORUM['DATA']['BREADCRUMBS'][$id]['URL'] = $url;
                   break; // there should be only one
               }
            }
        }
    }
}

function phorum_mod_go_to_topic_after_post($msg)
{
    global $PHORUM;

    // If the user is redirected to the read page after posting, then we
    // don't need to change the redirect behavior.
    if ($PHORUM["redirect_after_post"] == "read") {
        return $msg;
    }

    // If a reply to a standard (non-sticky) thread is posted, the we do a
    // redirect to the correct list page for the thread.
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
}
?>
