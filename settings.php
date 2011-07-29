<?php

if (!defined('PHORUM_ADMIN')) return;

// save settings
if (count($_POST))
{
    $PHORUM['mod_go_to_topic']['replace_original_list_urls'] =
        isset($_POST['replace_original_list_urls']);

    phorum_db_update_settings(array(
        'mod_go_to_topic' => $PHORUM['mod_go_to_topic']
    ));
    phorum_admin_okmsg('The settings were successfully saved');
}

// Apply default settings. When changing this code, then remember to update
// the defaults in go_to_topic.php as well.
if (!isset($PHORUM['mod_go_to_topic'])) {
    $PHORUM['mod_go_to_topic'] = array(
        'replace_original_list_urls' => TRUE
    );
}

include_once './include/admin/PhorumInputForm.php';
$frm = new PhorumInputForm ('', 'post', 'Save');
$frm->hidden('module', 'modsettings');
$frm->hidden('mod', 'go_to_topic');
$frm->addbreak('Go To Topic module settings');

$row = $frm->addrow(
    'Automatically replace list URLs: ',
    $frm->checkbox(
        "replace_original_list_urls", "1", "Yes",
        $PHORUM["mod_go_to_topic"]["replace_original_list_urls"]
    )
);

$frm->addhelp($row, 'Automatically replace list URLs',
    "This setting controls whether or not the list URLs should automatically
     be updated with the go-to-topic URLs.<br/>
     <br/>
     When disabled, you have full
     control over where to use the go-to-topic URLs. See the module's
     README for information on the required template changes."
);

$frm->show();

?>
