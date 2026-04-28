<?php
/**
 * Plugin Name: Quarantine – block malicious external JS
 * Description: Dequeues/deregisters known-bad scripts, strips hard-coded tags from HTML (after all optimizers), and logs attempts.
 * Version: 1.2
 * Author: Site Security
 */

/**
 * 1) Block enqueued scripts by handle or URL (late so it beats late enqueues).
 */
add_action('wp_enqueue_scripts', function () {
	if ( ! isset( wp_scripts()->queue ) ) return;

	// Hosts you explicitly allow for JS/CSS
	$allow_hosts = [
		'cdn.jsdelivr.net',
		'www.googletagmanager.com',
		'www.google.com',
		'www.gstatic.com',
		'fonts.googleapis.com',
		// NitroPack CDNs
		'*.nitrocdn.com',
		'*.nitropack.io',
	];

	// simple host matcher supporting leading wildcard
	$host_allowed = function($host) use ($allow_hosts) {
		foreach ($allow_hosts as $pat) {
			if ($pat[0] === '*' && substr($pat, 0, 2) === '*.') {
				if (substr($host, -strlen(substr($pat, 1))) === substr($pat, 1)) return true;
			} elseif (strcasecmp($host, $pat) === 0) {
				return true;
			}
		}
		return false;
	};

	foreach ((array) wp_scripts()->queue as $handle) {
		$reg = wp_scripts()->registered[$handle] ?? null;
		$src = $reg ? $reg->src : '';
		if (!$src) continue;

		$host = parse_url($src, PHP_URL_HOST) ?: '';
		$is_bad_domain =
			stripos($src, 'quanthic.cloud') !== false ||
			stripos($src, 'emily-grayson.online') !== false;

		// Block known-bad handles and any bootstrap.bundle.min.js from non-allowlisted hosts
		$is_bad_handle = in_array($handle, ['astra-auth-lib','asahi-jquery-min-bundle-js'], true);
		$is_suspicious_bootstrap = stripos($src, 'bootstrap.bundle.min.js') !== false && !$host_allowed($host);

		if ($is_bad_domain || $is_bad_handle || $is_suspicious_bootstrap) {
			@file_put_contents(WP_CONTENT_DIR.'/quanthic.log', "[ENQUEUE BLOCKED] {$handle} -> {$src}\n", FILE_APPEND);
			wp_dequeue_script($handle);
			wp_deregister_script($handle);
		}
	}
}, 999);

/**
 * 2) Strip hard-coded tags from the final HTML (run VERY late so it executes after NitroPack/other buffers).
 */
add_action('template_redirect', function () {
	if (is_admin()) return;
	ob_start(function ($html) {
		if (stripos($html, '<script') !== false || stripos($html, 'dns-prefetch') !== false) {
			// Remove known bad domains
			$html = preg_replace('#<script[^>]+src=["\'][^"\']*(quanthic\.cloud|emily-grayson\.online)[^>]*></script>#i', '', $html);
			// Remove bootstrap.bundle.min.js from non-allowed hosts (keep jsdelivr etc.)
			$html = preg_replace('#<script[^>]+src=["\']https?://(?!([^"/]+\.)?(nitrocdn\.com|nitropack\.io|cdn\.jsdelivr\.net))[^"\']*bootstrap\.bundle\.min\.js[^>]*></script>#i', '', $html);
			// Remove prefetch hints to bad hosts
			$html = preg_replace('#<link[^>]+rel=["\']dns-prefetch["\'][^>]+href=["\'][^"\']*(quanthic\.cloud|emily-grayson\.online)[^>]*>#i', '', $html);
		}
		return $html;
	});
}, 9999);

/**
 * 3) Log who tries to load it (short trace).
 */
add_filter('script_loader_src', function ($src, $handle) {
	if (
		stripos($src, 'quanthic.cloud') !== false ||
		stripos($src, 'emily-grayson.online') !== false ||
		in_array($handle, ['astra-auth-lib','asahi-jquery-min-bundle-js'], true)
	) {
		$trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
		$lines = ["[TRACE] {$handle} -> {$src}"];
		for ($i = 0; $i < min(10, count($trace)); $i++) {
			if (!empty($trace[$i]['file'])) {
				$lines[] = $trace[$i]['file'].':'.($trace[$i]['line'] ?? '?');
			}
		}
		@file_put_contents(WP_CONTENT_DIR.'/quanthic.log', implode("\n", $lines)."\n---\n", FILE_APPEND);
	}
	return $src;
}, 11, 2);

/**
 * 4) Remove prefetch hints for bad hosts.
 */
add_filter('wp_resource_hints', function ($urls, $rel) {
	if ($rel === 'dns-prefetch' && is_array($urls)) {
		$urls = array_values(array_filter($urls, function ($u) {
			return !preg_match('#(quanthic\.cloud|emily-grayson\.online)#i', (string)$u);
		}));
	}
	return $urls;
}, 999, 2);