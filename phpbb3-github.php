<?php

$forum = 5;
$user_id = 86; // chatbot in our case.

if (!isset($_POST['payload'])) { die('woops'); }

function get_gitio_url($url)
{
    $ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, "http://git.io/");
	curl_setopt($ch, CURLOPT_HEADER, true);
	curl_setopt($ch, CURLOPT_POST, true);
	curl_setopt($ch, CURLOPT_POSTFIELDS, "url={$url}");
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	$exec = curl_exec($ch);
	// NOTE: may need to be mofidifed if the link doesn't include all possible characters to match other regex solutions could also be implemented
	preg_match('/http\:\/\/git\.io\/([a-zA-Z0-9_\-]+)/', $exec, $matches);
	return $matches[0];
}

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

$json_array = json_decode(stripslashes($_POST['payload']), true);
$repository = $json_array['repository']['name'];
foreach ($json_array['commits'] as $commit)
{
	$tiny_url = get_gitio_url(stripslashes($commit['url']));
	$author = $commit['author']['username'];
	$message = $commit['message'];
	$post = "
	[b]Component[/b]: $repository
	[b]Link[/b]: $tiny_url
	[b]Author[/b]: $author
	[b]Commit message[/b]: " . $message;
	
	$time = time();
	$rawsubject = "[{$repository}] - {$author}";
	$my_subject = utf8_normalize_nfc($rawsubject, '', true);
	$my_text = utf8_normalize_nfc($post, '', true);
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
}

?>