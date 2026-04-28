<?php
/**
 * Theme functions and definitions
 *
 * @package HelloElementorChild
 */

/**
 * Load child theme css and optional scripts
 *
 * @return void
 */
require 'helpers/helpers.php';
function hello_elementor_child_enqueue_scripts() {
	wp_enqueue_style(
		'hello-elementor-child-style',
		get_stylesheet_directory_uri() . '/style.css',
		[
			'hello-elementor-theme-style',
		],
		// '1.0.0'
		time()
	);
    // Enqueue your custom JavaScript file
    wp_enqueue_script(
        'custom-script', // Change this to a unique handle for your script
        get_stylesheet_directory_uri() . '/assets/js/script.js', // Path to your JS file
        array('jquery'), // Array of dependencies (optional, add 'jquery' if needed)
        // '1.0.0', // Version number
        time(),
        true // Load the script in the footer (change to 'false' if you want it in the header)
    );
}
add_action( 'wp_enqueue_scripts', 'hello_elementor_child_enqueue_scripts', 20 );

/* Register Blog Sidebar */
function blog_sidebar_widgets_init() {
    register_sidebar( array(
        'name' => 'Blog Sidebar',
        'id' => 'blog_sidebar',
        'before_widget' => '<aside class="blog-sidebar-widget %2$s">',
        'after_widget' => '</aside>',
        'before_title' => '<h2 class="widget-title">',
        'after_title' => '</h2>',
    ) );
}
add_action( 'widgets_init', 'blog_sidebar_widgets_init' );

/*Custom breadcrumb for post type news */
/**
 * Add custom post type slug to Rank Math Breadcrumbs widget in Elementor.
 */
add_filter( 'rank_math/frontend/breadcrumb/items', function( $crumbs, $class ) {
	$post_type = get_post_type(get_queried_object());

	if (($post_type == 'news') ) { //change 'your_post_type' with the slug of your CPT
		$cpt = ['news', //replace 'CustomPost' with your CPT name
		 site_url().'/'.$post_type.'/' //replace with the URL of the CPT page
		];

	array_splice( $crumbs, 1, 0, array($cpt) );
	} 
	
	/* if (is_singular('post')) {
		$blog_crumb = ['Blog', //replace 'CustomPost' with your CPT name
		 site_url().'/blog/' //replace with the URL of the CPT page
		];
        array_splice($crumbs, 1, 0, array($blog_crumb));
    } */
	
	// customize breadcrumb for custom post type location page 
	if ( is_singular( 'location' ) ) {
        // Replace the URL of the custom post type breadcrumb item
        if ( isset( $crumbs[1] ) && isset( $crumbs[1][1] ) ) {
            $crumbs[1][1] = site_url().'/location/'; // Replace with your custom URL
        }
    }
	// customize breadcrumb for custom post type location category headquarters 
	if ( is_tax( 'type' ) ) {
        $term = get_queried_object();
        
        // Check if it's a specific category
        if ( $term && $term->term_id === 50 ) {            
            $custom_crumb = ['locations', //replace 'CustomPost' with your CPT name
				site_url().'/location/' //replace with the URL of the CPT page
			];
            // Insert the custom breadcrumb item at the desired position
            array_splice( $crumbs, 1, 0, array( $custom_crumb ) );
        }
    }

	return $crumbs;
}, 10, 2);

function register_custom_taxonomy() {
    $labels = array(
        'name'                       => _x( 'Types', 'type' ),
        'singular_name'              => _x( 'Type', 'type' ),
        'search_items'               => __( 'Search Types' ),
        'popular_items'              => __( 'Popular Types' ),
        'all_items'                  => __( 'All Types' ),
        'parent_item'                => null,
        'parent_item_colon'          => null,
        'edit_item'                  => __( 'Edit Type' ),
        'update_item'                => __( 'Update Type' ),
        'add_new_item'               => __( 'Add New Type' ),
        'new_item_name'              => __( 'New Type Name' ),
        'separate_items_with_commas' => __( 'Separate types with commas' ),
        'add_or_remove_items'        => __( 'Add or remove types' ),
        'choose_from_most_used'      => __( 'Choose from the most used types' ),
        'not_found'                  => __( 'No types found.' ),
        'menu_name'                  => __( 'Types' ),
    );

    $args = array(
        'hierarchical'      => true,
        'labels'            => $labels,
        'show_ui'           => true,
        'show_admin_column' => true,
        'query_var'         => true,
        'rewrite'           => array( 'slug' => 'type' ), // Change 'type' to the desired slug for your taxonomy.
    );

    register_taxonomy( 'type', 'location', $args ); // Replace 'your_custom_post_type' with the slug of your custom post type.
}
add_action( 'init', 'register_custom_taxonomy' );
//comment box button text change
function change_comment_button_text( $defaults ) {
    $defaults['label_submit'] = 'SUBMIT'; // Replace 'Your New Button Text' with the desired text for the comment button.
    return $defaults;
}
add_filter( 'comment_form_defaults', 'change_comment_button_text' );

//removing the website field from post comment form
add_filter('comment_form_default_fields', 'unset_url_field');
function unset_url_field($fields){
    if(isset($fields['url']))
       unset($fields['url']);
       return $fields;
}

function remove_hello_elementor_description_meta_tag() {
	remove_action( 'wp_head', 'hello_elementor_add_description_meta_tag' );
}
add_action( 'after_setup_theme', 'remove_hello_elementor_description_meta_tag' );

/* Added by developer 22/08/2023 */
/**
 * Filter to enable/disable SearchAction JSON-LD data
 * Add the sitelinks searchbox schema
 */
add_filter( 'rank_math/json_ld/disable_search', '__return_false' );

// Add/modify VideoObject schema entity
add_filter( 'rank_math/snippet/rich_snippet_videoobject_entity', function( $entity ) {
    // Set the uploadDate using get_the_modified_date in ISO 8601 format
    $entity['uploadDate'] = get_the_modified_date('c');

    // Ensure the format includes time (T separator)
    if ( ! empty( $entity['uploadDate'] ) ) {
        $parts = explode( 'T', $entity['uploadDate'] );
        if ( empty( $parts[1] ) ) {
            $entity['uploadDate'] = wp_date( 'Y-m-d\TH:i:sP', strtotime( $entity['uploadDate'] ) );
        }
    }

    return $entity;
});

// Disable Facebook OG upload date output
add_filter( 'rank_math/opengraph/facebook/ya_ovs_upload_date', '__return_false');

// Front-end: turn junk query-string URLs into a WP 404 (custom 404 template)

add_action('template_redirect', function () {

    // Skip admin/AJAX/REST/cron/CLI early
    if (is_admin() || wp_doing_ajax() || wp_doing_cron() || (defined('REST_REQUEST') && REST_REQUEST) || (defined('WP_CLI') && WP_CLI)) {
        return;
    }

    // Also skip any wp-admin URLs (important for Elementor & editors)
    // Use !== false to handle any path prefix or proxy rewrites
    if (isset($_SERVER['REQUEST_URI']) && strpos($_SERVER['REQUEST_URI'], '/wp-admin/') !== false) {
        return;
    }

    // Skip HEAD and non-GET (Nitro/optimizers and monitors may use HEAD; forms use POST)
    $method = isset($_SERVER['REQUEST_METHOD']) ? strtoupper($_SERVER['REQUEST_METHOD']) : 'GET';
    if ($method !== 'GET') {
        return;
    }
    if (isset($_SERVER['REQUEST_URI']) && strpos($_SERVER['REQUEST_URI'], '/cdn-cgi/') !== false) {
        return;
    }

    // Skip critical public endpoints (robots, sitemaps, feeds)
    $uri = $_SERVER['REQUEST_URI'] ?? '';
    if (preg_match('~^/(robots\.txt|sitemap.*\.xml|feed/?)$~i', $uri)) {
        return;
    }

    // Skip known NitroPack user agents
    $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
    if ($ua && preg_match('~Nitro\-(Optimizer|Warmup|Webhook)\-Agent~i', $ua)) {
        return;
    }

    // Raw query string
    $qs = isset($_SERVER['QUERY_STRING']) ? trim((string) $_SERVER['QUERY_STRING']) : '';
    if ($qs === '') {
        return; // no query string
    }

    // If the query string has NO "=" (e.g. "?vn/2022-10-2965982.html") -> 404
    if (strpos($qs, '=') === false) {
        dd_force_wp_404();
    }

    parse_str($qs, $pairs);
    if (empty($pairs) || !is_array($pairs)) {
        dd_force_wp_404();
    }

    // Exact keys we allow everywhere (frontend)
    $allowed_exact = [
        // Search
        's','search',

        // WP common
        'paged','orderby','order','replytocom','amp','_wpnonce','post','action','p',

        // Analytics / tracking (exact)
        'utm_source','utm_medium','utm_campaign','utm_term','utm_content','utm_id',
        'gclid','fbclid','dclid','msclkid','_ga','_gl',

        // Cache-busting / version
        'ver','nocache',

        // Elementor preview (front-end for editors)
        'elementor-preview','preview','preview_id','preview_nonce','_thumbnail_id','elementor_library','preview-debug', 'page_id', 'p'
    ];

    // Prefix-based allowances (more flexible)
    $allowed_prefixes = [
        'utm_', 'mtm_', 'matomo_', 'pk_', 'piwik_', 'hsa_', 'gad_',
        'nitro', 'np_', 'np-',
        'mc_', 'ttclid', 'fb_', 'li_', 'ig_', '_kx', '_ke',
    ];

    // If this is an editor/admin-like request (safety checks)
    $is_admin_context = false;
    // If REQUEST_URI contained /wp-admin/ we already returned above, but some editors open front-end
    // so detect logged-in editors who can edit posts — allow admin params for them.
    if (is_user_logged_in() && current_user_can('edit_posts')) {
        $is_admin_context = true;
    }

    // Extra admin/editor params to allow but only when in admin-like context
    $admin_params = ['post', 'action', 'post_type', 'post_status', 'meta_box_order', 'meta-box-order-nonce', 'elementor_library'];

    foreach ($pairs as $key => $val) {
        // allow empty values (e.g. ?amp) for allowed exact keys
        if ($val === '' && in_array($key, $allowed_exact, true)) {
            continue;
        }

        if (in_array($key, $allowed_exact, true)) {
            continue;
        }

        // If this key is an admin param and current user can edit posts — allow it
        if ($is_admin_context && in_array($key, $admin_params, true)) {
            continue;
        }

        // check prefixes
        $ok = false;
        foreach ($allowed_prefixes as $p) {
            if (stripos($key, $p) === 0) { $ok = true; break; }
        }

        if (!$ok) {
            // OPTIONAL: log the offending key for debugging (uncomment to enable)
            // error_log("dd_force_wp_404 block: key={$key} uri=" . ($_SERVER['REQUEST_URI'] ?? '') . " ua=" . ($ua ?? ''));
            dd_force_wp_404();
        }
    }
}, 0);


/**
 * Render the theme's 404 template and exit (no server-level 404)
 */
function dd_force_wp_404() {
    status_header(404);
    nocache_headers();
    $template = get_query_template('404');
    if (!$template) { $template = get_404_template(); }
    if ($template && file_exists($template)) { include $template; } else { echo '404 Not Found'; }
    exit;
}



add_filter('gform_akismet_enabled', '__return_true');

add_filter('pmxi_custom_types', function($custom_types){
    $custom_types[] = 'service';
    return $custom_types;
});
