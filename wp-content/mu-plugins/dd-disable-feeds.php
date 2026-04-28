<?php
/*
Plugin Name: DD Disable Feeds
Description: Disable all RSS/Atom feeds on the front-end.
*/

if (!defined('ABSPATH')) exit;

function dd_disable_feeds() {
    if (is_admin()) return; // keep wp-admin functional
    status_header(410);
    nocache_headers();
    header('Content-Type: text/plain; charset=' . get_option('blog_charset'));
    echo 'Feed disabled.';
    exit;
}
add_action('do_feed', 'dd_disable_feeds', 1);
add_action('do_feed_rdf', 'dd_disable_feeds', 1);
add_action('do_feed_rss', 'dd_disable_feeds', 1);
add_action('do_feed_rss2', 'dd_disable_feeds', 1);
add_action('do_feed_atom', 'dd_disable_feeds', 1);
add_action('do_feed_rss2_comments', 'dd_disable_feeds', 1);
add_action('do_feed_atom_comments', 'dd_disable_feeds', 1);

// Remove <link rel="alternate" ...> feed links from <head>
remove_action('wp_head', 'feed_links', 2);
remove_action('wp_head', 'feed_links_extra', 3);
