<?php

$trashbin = new trashbin;

$sub_tabs['trashbin_threads'] = array(
    'title' => 'Thread Trash Bin',
    'link' => 'index.php?module=tools-trashbin',
    'description' => 'View all removed threads.');
$sub_tabs['trashbin_posts'] = array(
    'title' => 'Post Trash Bin',
    'link' => 'index.php?module=tools-trashbin&amp;action=posts',
    'description' => 'View all removed posts.');

switch ($mybb->get_input('action')) {
    case 'threadrestore':
        $sub_tabs['trashbin_threadrestore'] = array(
            'title' => "Restore thread",
            'link' => 'index.php?module=tools-trashbin&amp;action=threadrestore&amp;tid=' . $mybb->input['tid'],
            'description' => "");
        break;
    case 'postrestore':
        $sub_tabs['trashbin_postrestore'] = array(
            'title' => "Restore post",
            'link' => 'index.php?module=tools-trashbin&amp;action=postrestore&amp;pid=' . $mybb->input['pid'],
            'description' => "");
        break;
    case 'viewthread':
        $sub_tabs['trashbin_viewthread'] = array(
            'title' => "View Thread",
            'link' => 'index.php?module=tools-trashbin&amp;action=viewthread&amp;tid=' . $mybb->input['tid'],
            'description' => "View thread");
        break;
    case 'viewpost':
        $sub_tabs['trashbin_viewpost'] = array(
            'title' => "View Post",
            'link' => 'index.php?module=tools-trashbin&amp;action=viewpost&amp;pid=' . $mybb->input['pid'],
            'description' => "View single post");
        break;
}

if ($mybb->get_input('action') == 'posts') {
    $page->add_breadcrumb_item("Trash Bin", "");
    $page->output_header("Trash Bin");
    $page->output_nav_tabs($sub_tabs, 'trashbin_posts');

    $table = new Table;
    $table->construct_header("Thread subject", array());
    $table->construct_header("Post subject", array());
    $table->construct_header("Poster", array());
    $table->construct_header("Deleted by", array());
    $table->construct_header("Deleted on", array());
    $table->construct_header("", array());

    $numquery = $db->simple_select('trashbin_posts_single', '*', '');
    $total = $db->num_rows($numquery);

    if ($mybb->input['page']) {
        $pagenr = intval($mybb->input['page']);
        $pagestart = (($pagenr - 1) * 30);

        if ((($pagenr - 1) * 30) > $total) {
            $pagenr = 1;
            $pagestart = 0;
        }
    } else {
        $pagenr = 1;
        $pagestart = 0;
    }

    $query = $db->simple_select('trashbin_posts_single', '*', '', array(
        "order_by" => "deletetime",
        "order_dir" => "DESC",
        "limit_start" => $pagestart,
        "limit" => 30));

    if (!$db->num_rows($query)) {
        $table->construct_cell('<div align="center">The trash bin is empty</div>', array('colspan' => 6));
        $table->construct_row();
        $table->output("Post Trash Bin");
    } else {
        while ($post = $db->fetch_array($query)) {
            $restore_link = "index.php?module=tools-trashbin&amp;action=postrestore&amp;pid={$post['pid']}";
            $view_link = "index.php?module=tools-trashbin&amp;action=viewpost&amp;pid={$post['pid']}";

            $thread = get_thread($post['tid']);
            if ($thread) {
                $table->construct_cell("<a href='../showthread.php?tid=" . $thread['tid'] . "'>" . $thread['subject'] . "</a>");
            } else {
                $table->construct_cell("- REMOVED THREAD -");
            }

            $table->construct_cell($post['subject']);

            //poster
            $poster = get_user($post['uid']);
            $table->construct_cell("<a href='../member.php?uid=" . $poster['uid'] . "'>" . $poster['username'] . "</a>");

            //deleter
            $deletedby = get_user($post['deletedby']);
            $table->construct_cell("<a href='../member.php?uid=" . $deletedby['uid'] . "'>" . $deletedby['username'] . "</a>");

            $table->construct_cell(date("d-m-Y H:i", $post['deletetime']));

            $popup = new PopupMenu("post_{$post['pid']}", $lang->options);
            $popup->add_item("View", $view_link);

            if ($thread) {
                $popup->add_item("Restore", $restore_link);
            }

            $table->construct_cell($popup->fetch(), array('class' => 'align_center'));

            $table->construct_row();
        }
        $table->output("Post Trash Bin");

        echo draw_admin_pagination($pagenr, 30, $total, $trashbin->build_url(array("action" => "posts")));
    }

    $page->output_footer();
} elseif ($mybb->get_input('action') == 'viewthread') {

    require_once MYBB_ROOT . "inc/class_parser.php";

    $page->add_breadcrumb_item("Trash Bin", "index.php?module=tools-trashbin");
    $page->add_breadcrumb_item("Thread Post", "");
    $page->output_header("Trash Bin");
    $page->output_nav_tabs($sub_tabs, 'trashbin_viewthread');

    if ($mybb->input['tid']) {
        $query = $db->simple_select("trashbin_threads", "*", "tid = " . intval($mybb->input['tid']), array("order_by" => "dateline", "order_dir" => "asc"));

        if ($db->num_rows($query) == 1) {
            $thread = $db->fetch_array($query);
            $query2 = $db->simple_select("trashbin_posts", "*", "tid = " . $thread['tid']);

            if ($total = $db->num_rows($query2)) {
                if ($total <= 10) {
                    $num = 1;
                    while ($post = $db->fetch_array($query2)) {
                        $parsed_post = trashbin_parse_post($post, $num);

                        $table = new Table;
                        $table->construct_cell($parsed_post['head']);
                        $table->construct_row();
                        $table->construct_cell($parsed_post['middle'] . $parsed_post['content']);
                        $table->construct_row();

                        $table->output("");
                        $num++;
                    }
                } else {
                    if ($mybb->input['page']) {
                        $pagenr = intval($mybb->input['page']);
                        $pagestart = (($pagenr - 1) * 10);

                        if ((($pagenr - 1) * 10) > $total) {
                            $pagenr = 1;
                            $pagestart = 0;
                        }
                    } else {
                        $pagenr = 1;
                        $pagestart = 0;
                    }

                    $num = $pagestart + 1;

                    $query2 = $db->simple_select("trashbin_posts", "*", "tid = " . $thread['tid'], array(
                        "order_by" => "dateline",
                        "order_dir" => "asc",
                        "limit_start" => $pagestart,
                        "limit" => 10));

                    while ($post = $db->fetch_array($query2)) {
                        $parsed_post = trashbin_parse_post($post, $num);

                        $table = new Table;
                        $table->construct_cell($parsed_post['head']);
                        $table->construct_row();
                        $table->construct_cell($parsed_post['middle'] . $parsed_post['content']);
                        $table->construct_row();

                        $table->output("");
                        $num++;
                    }
                    
                    echo draw_admin_pagination($pagenr, 10, $total, $trashbin->build_url(array("action"=>"viewthread","tid"=>$thread['tid'])));
                }
            } else {
                $trashbin->admin_redirect("This thread does not contain any posts!", true, "posts");
            }
        } else {
            $trashbin->admin_redirect("The thread you tried to view doesn't exist!", true, "posts");
        }

    } else {
        $trashbin->admin_redirect("The thread you tried to view doesn't exist!", true, "posts");
    }


    $page->output_footer();
} elseif ($mybb->get_input('action') == 'viewpost') {

    require_once MYBB_ROOT . "inc/class_parser.php";

    $page->add_breadcrumb_item("Trash Bin", "index.php?module=tools-trashbin&action=posts");
    $page->add_breadcrumb_item("View Post", "");
    $page->output_header("Trash Bin");
    $page->output_nav_tabs($sub_tabs, 'trashbin_viewpost');

    if ($mybb->input['pid']) {
        $query = $db->simple_select("trashbin_posts_single", "*", "pid = " . intval($mybb->input['pid']));

        if ($db->num_rows($query) == 1) {
            $post = $db->fetch_array($query);

            $parsed_post = trashbin_parse_post($post, 1);

            $table = new Table;
            $table->construct_cell($parsed_post['head']);
            $table->construct_row();
            $table->construct_cell($parsed_post['middle'] . $parsed_post['content']);
            $table->construct_row();

            $table->output("");
        } else {
            $trashbin->admin_redirect("The post you tried to view doesn't exist!", true, "posts");
        }

    } else {
        $trashbin->admin_redirect("The post you tried to view doesn't exist!", true, "posts");
    }


    $page->output_footer();
} elseif ($mybb->get_input('action') == 'threadrestore') {
    if ($mybb->input['tid']) {
        $result = trashbin_restore_thread($mybb->input['tid']);

        if ($result[0]) {
            $trashbin->admin_redirect("The selected thread has been restored.", false);
        } else {
            $trashbin->admin_redirect($result[1], true);
        }
    } else {
        $trashbin->admin_redirect();
    }
} elseif ($mybb->get_input('action') == 'postrestore') {
    if ($mybb->input['pid']) {
        $result = trashbin_restore_post($mybb->input['pid']);

        if ($result[0]) {
            $trashbin->admin_redirect("The selected post has been restored.", false, "posts");
        } else {
            $trashbin->admin_redirect($result[1], true, "posts");
        }
    } else {
        $trashbin->admin_redirect("Oops something went wrong", true, "posts");
    }
} else {

    $page->add_breadcrumb_item("Trash Bin", "");
    $page->output_header("Trash Bin");
    $page->output_nav_tabs($sub_tabs, 'trashbin_threads');

    $table = new Table;
    $table->construct_header("Thread subject", array());
    $table->construct_header("Poster", array());
    $table->construct_header("Posts", array());
    $table->construct_header("Deleted by", array());
    $table->construct_header("Deleted on", array());
    $table->construct_header("", array());

    $numquery = $db->simple_select('trashbin_threads', '*', '');
    $total = $db->num_rows($numquery);

    if ($mybb->input['page']) {
        $pagenr = intval($mybb->input['page']);
        $pagestart = (($pagenr - 1) * 30);

        if ((($pagenr - 1) * 30) > $total) {
            $pagenr = 1;
            $pagestart = 0;
        }
    } else {
        $pagenr = 1;
        $pagestart = 0;
    }

    $query = $db->simple_select('trashbin_threads', '*', '', array(
        "order_by" => "deletetime",
        "order_dir" => "DESC",
        "limit_start" => $pagestart,
        "limit" => 30));

    if (!$db->num_rows($query)) {
        $table->construct_cell('<div align="center">The trash bin is empty</div>', array('colspan' => 6));
        $table->construct_row();
        $table->output("Threads Trash Bin");
    } else {
        while ($thread = $db->fetch_array($query)) {
            $restore_link = "index.php?module=tools-trashbin&amp;action=threadrestore&amp;tid={$thread['tid']}";
            $view_link = "index.php?module=tools-trashbin&amp;action=viewthread&amp;tid={$thread['tid']}";

            $table->construct_cell($thread['subject']);

            //poster
            $poster = get_user($thread['uid']);
            $table->construct_cell("<a href='../member.php?uid=" . $poster['uid'] . "'>" . $poster['username'] . "</a>");

            //num posts
            $query2 = $db->simple_select("trashbin_posts", "pid", "tid = " . $thread['tid']);
            $table->construct_cell($db->num_rows($query2));

            //deleter
            $deletedby = get_user($thread['deletedby']);
            $table->construct_cell("<a href='../member.php?uid=" . $deletedby['uid'] . "'>" . $deletedby['username'] . "</a>");

            $table->construct_cell(date("d-m-Y H:i", $thread['deletetime']));

            $popup = new PopupMenu("thread_{$thread['tid']}", $lang->options);
            $popup->add_item("View", $view_link);
            $popup->add_item("Restore", $restore_link);

            $table->construct_cell($popup->fetch(), array('class' => 'align_center'));

            $table->construct_row();
        }
        $table->output("Threads Trash Bin");

        echo draw_admin_pagination($pagenr, 30, $total, $trashbin->build_url());
    }

    $page->output_footer();
}

?>