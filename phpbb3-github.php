<?php

$forum = 32;
$user_id = 86;
$password = "";

define('IN_PHPBB', true);
$path = $_SERVER['DOCUMENT_ROOT']; 
$phpbb_root_path = (defined('PHPBB_ROOT_PATH')) ? PHPBB_ROOT_PATH : $path.'/forum/'; 
$phpbb_admin_path = (defined('PHPBB_ROOT_PATH')) ? PHPBB_ROOT_PATH : $path.'/forum/';
$phpEx = substr(strrchr(__FILE__, '.'), 1);
include($phpbb_root_path . 'common.' . $phpEx);
include($phpbb_root_path . 'includes/functions_posting.' . $phpEx);
include($phpbb_root_path . 'includes/functions_display.' . $phpEx);
include($phpbb_root_path . 'includes/message_parser.' . $phpEx);

$sql = 'SELECT * FROM ' . USERS_TABLE . ' WHERE user_id = ' . $user_id;
$result = $db->sql_query($sql);
$row = $db->sql_fetchrow($result);
$db->sql_freeresult($result);

// Start session management
$user->session_begin();
$auth->acl($user->data);
$user->setup('common');

/* Now we have a template for the user, insert chatbot details so it's under his account. Hackery! */
$user->data = array_merge($user->data, $row);
$auth->acl($user->data);

$makesure = request_var('auth', '', true); 
if ($makesure != $password) { echo('inc.'); die(); } // Just a bit of protection

$recipient = utf8_normalize_nfc(request_var('', '', true));
 
$message = "
[b][/b]: 
";

$time = time();
$rawsubject = "  ";
$my_subject   = utf8_normalize_nfc($rawsubject, '', true);
$my_text   = utf8_normalize_nfc($message, '', true);
 
$poll = $uid = $bitfield = $options = '';
 
generate_text_for_storage($my_subject, $uid, $bitfield, $options, false, false, false);
generate_text_for_storage($my_text, $uid, $bitfield, $options, true, true, true);
 
$data = array(
       'forum_id'      => $forum,
       'icon_id'      => false,
 
       'enable_bbcode'      => true,
       'enable_smilies'   => true,
       'enable_urls'      => true,
       'enable_sig'      => true,
 
       'message'      => $my_text,
       'message_md5'   => md5($my_text),
               
       'bbcode_bitfield'   => $bitfield,
       'bbcode_uid'      => $uid,
 
       'post_edit_locked'   => 0,
       'topic_title'      => $my_subject,
       'notify_set'      => false,
       'notify'         => true,
       'post_time'       => 0,
       'forum_name'      => '',
       'enable_indexing'   => true,
 
);
 
submit_post('post', $my_subject, $user->data['username'], POST_NORMAL, $poll, $data);

?>