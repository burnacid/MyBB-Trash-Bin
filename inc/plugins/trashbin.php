<?php

if (!defined('IN_MYBB'))
    die('This file cannot be accessed directly.');

//HOOKS
if (defined('IN_ADMINCP'))
{
    $plugins->add_hook('admin_tools_menu', create_function('&$args', '$args[] = array(\'id\' => \'trashbin\', \'title\' => \'Trash Bin\', \'link\' => \'index.php?module=tools-trashbin\');'));
    $plugins->add_hook('admin_tools_action_handler', create_function('&$args', '$args[\'trashbin\'] = array(\'active\' => \'trashbin\', \'file\' => \'trashbin.php\');'));
}
else
{
    $plugins->add_hook("class_moderation_delete_thread_start", "trashbin_delete_thread");
    $plugins->add_hook("class_moderation_delete_post_start", "trashbin_delete_post");
}

function trashbin_info()
{
    return array(
        'name' => 'Trash Bin',
        'description' => 'Moves all permanently deleted threads to a trashbin for 60 days',
        'website' => 'http://lenders-it.nl',
        'author' => 'S. Lenders',
        'authorsite' => 'http://lenders-it.nl',
        'version' => '1',
        'compatibility' => '18*',
        'codename' => 'trashbin');
}

function trashbin_install()
{
    global $db, $mybb;


    // Create our table collation
    $collation = $db->build_create_table_collation();

    // Create table if it doesn't exist already
    if (!$db->table_exists('trashbin_posts'))
    {
        $db->write_query("CREATE TABLE IF NOT EXISTS `" . TABLE_PREFIX . "trashbin_posts` (
          `pid` int(10) unsigned NOT NULL,
          `tid` int(10) unsigned NOT NULL DEFAULT '0',
          `replyto` int(10) unsigned NOT NULL DEFAULT '0',
          `fid` smallint(5) unsigned NOT NULL DEFAULT '0',
          `subject` varchar(120) NOT NULL DEFAULT '',
          `icon` smallint(5) unsigned NOT NULL DEFAULT '0',
          `uid` int(10) unsigned NOT NULL DEFAULT '0',
          `username` varchar(80) NOT NULL DEFAULT '',
          `dateline` int(10) unsigned NOT NULL DEFAULT '0',
          `message` text NOT NULL,
          `ipaddress` varbinary(16) NOT NULL DEFAULT '',
          `includesig` tinyint(1) NOT NULL DEFAULT '0',
          `smilieoff` tinyint(1) NOT NULL DEFAULT '0',
          `edituid` int(10) unsigned NOT NULL DEFAULT '0',
          `edittime` int(10) unsigned NOT NULL DEFAULT '0',
          `editreason` varchar(150) NOT NULL DEFAULT '',
          `visible` tinyint(1) NOT NULL DEFAULT '0',
          `tags` text NOT NULL,
          PRIMARY KEY (`pid`),
          KEY `tid` (`tid`,`uid`),
          KEY `uid` (`uid`),
          KEY `visible` (`visible`),
          KEY `dateline` (`dateline`),
          KEY `ipaddress` (`ipaddress`),
          KEY `tiddate` (`tid`,`dateline`),
          FULLTEXT KEY `message` (`message`)
        ) ENGINE=MyISAM DEFAULT CHARSET=utf8;");

    }

    if (!$db->table_exists('trashbin_threads'))
    {
        $db->write_query("CREATE TABLE IF NOT EXISTS `" . TABLE_PREFIX . "trashbin_threads` (
          `tid` int(10) unsigned NOT NULL,
          `fid` smallint(5) unsigned NOT NULL DEFAULT '0',
          `subject` varchar(120) NOT NULL DEFAULT '',
          `prefix` smallint(5) unsigned NOT NULL DEFAULT '0',
          `icon` smallint(5) unsigned NOT NULL DEFAULT '0',
          `poll` int(10) unsigned NOT NULL DEFAULT '0',
          `uid` int(10) unsigned NOT NULL DEFAULT '0',
          `username` varchar(80) NOT NULL DEFAULT '',
          `dateline` int(10) unsigned NOT NULL DEFAULT '0',
          `firstpost` int(10) unsigned NOT NULL DEFAULT '0',
          `lastpost` int(10) unsigned NOT NULL DEFAULT '0',
          `lastposter` varchar(120) NOT NULL DEFAULT '',
          `lastposteruid` int(10) unsigned NOT NULL DEFAULT '0',
          `views` int(100) unsigned NOT NULL DEFAULT '0',
          `replies` int(100) unsigned NOT NULL DEFAULT '0',
          `closed` varchar(30) NOT NULL DEFAULT '',
          `sticky` tinyint(1) NOT NULL DEFAULT '0',
          `numratings` smallint(5) unsigned NOT NULL DEFAULT '0',
          `totalratings` smallint(5) unsigned NOT NULL DEFAULT '0',
          `notes` text NOT NULL,
          `visible` tinyint(1) NOT NULL DEFAULT '0',
          `unapprovedposts` int(10) unsigned NOT NULL DEFAULT '0',
          `deletedposts` int(10) unsigned NOT NULL DEFAULT '0',
          `attachmentcount` int(10) unsigned NOT NULL DEFAULT '0',
          `deletetime` int(10) unsigned NOT NULL DEFAULT '0',
          `deletedby` int(10) unsigned NOT NULL DEFAULT '0',
          PRIMARY KEY (`tid`),
          KEY `fid` (`fid`,`visible`,`sticky`),
          KEY `dateline` (`dateline`),
          KEY `lastpost` (`lastpost`,`fid`),
          KEY `firstpost` (`firstpost`),
          KEY `uid` (`uid`),
          FULLTEXT KEY `subject` (`subject`)
        ) ENGINE=MyISAM DEFAULT CHARSET=utf8;");

    }

    if (!$db->table_exists('trashbin_posts_single'))
    {
        $db->write_query("CREATE TABLE IF NOT EXISTS `" . TABLE_PREFIX . "trashbin_posts_single` (
          `pid` int(10) unsigned NOT NULL,
          `tid` int(10) unsigned NOT NULL DEFAULT '0',
          `replyto` int(10) unsigned NOT NULL DEFAULT '0',
          `fid` smallint(5) unsigned NOT NULL DEFAULT '0',
          `subject` varchar(120) NOT NULL DEFAULT '',
          `icon` smallint(5) unsigned NOT NULL DEFAULT '0',
          `uid` int(10) unsigned NOT NULL DEFAULT '0',
          `username` varchar(80) NOT NULL DEFAULT '',
          `dateline` int(10) unsigned NOT NULL DEFAULT '0',
          `message` text NOT NULL,
          `ipaddress` varbinary(16) NOT NULL DEFAULT '',
          `includesig` tinyint(1) NOT NULL DEFAULT '0',
          `smilieoff` tinyint(1) NOT NULL DEFAULT '0',
          `edituid` int(10) unsigned NOT NULL DEFAULT '0',
          `edittime` int(10) unsigned NOT NULL DEFAULT '0',
          `editreason` varchar(150) NOT NULL DEFAULT '',
          `visible` tinyint(1) NOT NULL DEFAULT '0',
          `tags` text NOT NULL,
          `deletetime` int(10) unsigned NOT NULL DEFAULT '0',
          `deletedby` int(10) unsigned NOT NULL DEFAULT '0',
          PRIMARY KEY (`pid`),
          KEY `tid` (`tid`,`uid`),
          KEY `uid` (`uid`),
          KEY `visible` (`visible`),
          KEY `dateline` (`dateline`),
          KEY `ipaddress` (`ipaddress`),
          KEY `tiddate` (`tid`,`dateline`),
          FULLTEXT KEY `message` (`message`)
        ) ENGINE=MyISAM DEFAULT CHARSET=utf8;
        ");

    }

}
function trashbin_activate()
{
    change_admin_permission('tools', 'trashbin', 1);
}
function trashbin_is_installed()
{
    global $db;

    // If the table exists then it means the plugin is installed because we only drop it on uninstallation
    return $db->table_exists('trashbin_threads') && $db->table_exists('trashbin_posts');
}
function trashbin_deactivate()
{
    change_admin_permission('tools', 'trashbin', -1);
}
function trashbin_uninstall()
{
    global $db, $mybb;

    if ($mybb->request_method != 'post')
    {
        global $page;

        $page->output_confirm_action('index.php?module=config-plugins&action=deactivate&uninstall=1&plugin=trashbin',
            "Are you sure you want to uninstall the Trash bin plugin? This will delete the complete contents of the trashbin.", "Uninstall Trashbin plugin");
    }

    // This is required so it updates the settings.php file as well and not only the database - they must be synchronized!
    rebuild_settings();

    // Drop tables if desired
    if (!isset($mybb->input['no']))
    {
        $db->drop_table('trashbin_threads');
        $db->drop_table('trashbin_posts');
        $db->drop_table('trashbin_posts_single');
    }
}

function trashbin_delete_post($pid)
{
    global $db, $mybb;

    $query = $db->simple_select("posts", "*", "pid = " . intval($pid));

    if ($db->num_rows($query) == 1)
    {
        $post = $db->fetch_array($query);
        $post = trashbin_escape_post($post);

        $post['deletetime'] = time();
        $post['deletedby'] = $mybb->user['uid'];

        $db->insert_query("trashbin_posts_single", $post);
    }
}

function trashbin_restore_post($pid)
{
    global $db, $mybb;

    $query = $db->simple_select("trashbin_posts_single", "*", "pid = " . intval($pid));

    if ($db->num_rows($query) == 1)
    {
        $post = $db->fetch_array($query);
        $thread = get_thread($post['tid']);

        if ($thread)
        {
            $post = trashbin_escape_post($post);

            unset($post['deletetime']);
            unset($post['deletedby']);

            $db->insert_query("posts", $post);

            $db->delete_query("trashbin_posts_single", "pid = " . intval($pid));

            return array(true);
        }
        else
        {
            return array(false, "Thread does not longer exist!");
        }

    }
    else
    {
        return array(false, "The post your are trying to restore is not found!");
    }
}

function trashbin_delete_thread($tid)
{
    global $db, $mybb;

    $query = $db->simple_select("threads", "*", "tid = " . intval($tid));

    if ($db->num_rows($query) == 1)
    {
        $thread = $db->fetch_array($query);
        $thread = trashbin_escape_thread($thread);
        $thread['deletetime'] = time();
        $thread['deletedby'] = $mybb->user['uid'];

        $query2 = $db->simple_select("posts", "*", "tid = " . intval($tid));

        while ($post = $db->fetch_array($query2))
        {
            $post = trashbin_escape_post($post);
            $db->insert_query("trashbin_posts", $post);
        }

        $db->insert_query("trashbin_threads", $thread);
    }
}

function trashbin_restore_thread($tid)
{
    global $db, $mybb;

    $query = $db->simple_select("trashbin_threads", "*", "tid = " . intval($tid));

    if ($db->num_rows($query) == 1)
    {
        $thread = $db->fetch_array($query);
        $forum = get_forum($thread['fid']);

        if ($forum)
        {
            $thread = trashbin_escape_thread($thread);
            $thread['deletetime'] = 0;
            unset($thread['deletedby']);

            $query2 = $db->simple_select("trashbin_posts", "*", "tid = " . intval($tid));

            while ($post = $db->fetch_array($query2))
            {
                $post = trashbin_escape_post($post);
                $db->insert_query("posts", $post);
            }

            $db->insert_query("threads", $thread);

            $db->delete_query("trashbin_posts", "tid = " . intval($tid));
            $db->delete_query("trashbin_threads", "tid = " . intval($tid));

            return array(true);
        }
        else
        {
            return array(false, "Forum does not longer exist!");
        }
    }
    else
    {
        return array(false, "The thread your are trying to restore is not found!");
    }
}

function trashbin_escape_post($post)
{
    global $db;

    $post['subject'] = $db->escape_string($post['subject']);
    $post['username'] = $db->escape_string($post['username']);
    $post['message'] = $db->escape_string($post['message']);
    $post['editreason'] = $db->escape_string($post['editreason']);
    $post['tags'] = $db->escape_string($post['tags']);

    return $post;
}

function trashbin_escape_thread($thread)
{
    global $db;

    $thread['subject'] = $db->escape_string($thread['subject']);
    $thread['username'] = $db->escape_string($thread['username']);
    $thread['lastposter'] = $db->escape_string($thread['lastposter']);
    $thread['closed'] = $db->escape_string($thread['closed']);

    return $thread;
}

$plugins->add_hook('admin_tools_permissions', 'trashbin_admin_tools_permissions');
function trashbin_admin_tools_permissions(&$admin_permissions)
{
    $admin_permissions['trashbin'] = "Can manage trash bin?";
}

$plugins->add_hook('admin_config_action_handler', 'trashbin_admin_config_action_handler');
function trashbin_admin_config_action_handler(&$actions)
{
    $actions['trashbin'] = array(
        'active' => 'trashbin',
        'file' => 'trashbin.php',
        );
}

class trashbin
{
    public function build_url($params = array())
    {
        global $PL;
        $PL or require_once PLUGINLIBRARY;

        if (defined('IN_ADMINCP'))
        {
            $url = 'index.php?module=tools-trashbin';
        }
        else
        {
            $url = 'modcp.php?action=trashbin';
        }

        if (!is_array($params))
        {
            $params = explode('=', $params);
            if (isset($params[0]) && isset($params[1]))
            {
                $params = array($params[0] => $params[1]);
            }
            else
            {
                $params = array();
            }
        }

        return $PL->url_append($url, $params);
    }

    function admin_redirect($message = '', $error = false, $action = "")
    {
        if ($message)
        {
            flash_message($message, ($error ? 'error' : 'success'));
        }

        if ($action != "")
        {
            $parm = array("action" => $action);
        }
        else
        {
            $parm = array();
        }

        admin_redirect($this->build_url($parm));
        exit;
    }

}

?>