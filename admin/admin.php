<?php

namespace Digitpaint\UserDocs\Admin;

use Symfony\Component\Yaml\Yaml;
use Symfony\Component\Yaml\Exception\ParseException;

/**
 * Get the root for all documentation .md files
 *
 * You can modify this path by using the `dp-user-docs/doc-root` filter.
 */
function get_doc_root() {
  return apply_filters("dp-user-docs/doc-root", get_home_path() . '../../doc');
}

/**
 * Get a list of available documentation .md files in doc_root
 * All files starting with an _ will be filtered out of the list.
 *
 * You can modify the list by using the `dp-user-docs/doc-files` filter
 */
function get_doc_files() {
  $root = get_doc_root();

  $paths = glob($root . "/*.md");

  $files = [];

  foreach($paths as $path) {
    $file = basename($path);
    if(strpos($file, "_") !== 0) {
      $files[] = $file;
    }
  }

  return apply_filters("dp-user-docs/doc-files", $files);
}

/**
 * Get the document and parse the front-matter
 *
 * Front-matter will be parsed as YAML data. The folowing
 * keys will be used (all others are ignored):
 *
 * - capability : The capability the user must have to see this page
 * - title : The title of the page (will fall back to filename)
 * - menu_title: The title used in the menu (will fall back to title)
 *
 * You can modify the front-matter with the `dp-user-docs/front-matter` filter.
 */
function get_doc($file) {
  $doc = wp_cache_get($file, 'dp-user-docs/docs');
  if ($doc === false) {
    $doc = get_doc_real($file);
    wp_cache_add($file, $doc, 'dp-user-docs/docs');
  }
  return $doc;
}

function get_doc_real($file) {
  $raw = file_get_contents(get_doc_root() . "/" . $file);
  $front_matter = [];
  if(preg_match('/---\n(.+)\n---\n/smix', $raw, $matches)) {
    $front_matter = parse_front_matter($matches[1]);
  }

  $markdown = preg_replace('/---\n.+\n---\n/smix', '', $raw, 1);

  return [
    "data" => sanitize_front_matter($front_matter, $file),
    "file" => $file,
    "markdown" => $markdown
  ];
}

function parse_front_matter($raw) {
  try {
    return Yaml::parse($raw);
  } catch (ParseException $e) {
    printf("Unable to parse the YAML string: %s", $e->getMessage());
    return [];
  }
}

function sanitize_front_matter($data, $file) {
  if(!key_exists('title', $data)) {
    $data['title'] = basename($file, ".md");
  }

  if(!key_exists('menu_title', $data)) {
    $data['menu_title'] =  $data['title'];
  }

  if(!key_exists('capability', $data)) {
    $data['capability'] = 'edit_posts';
  }

  return apply_filters('dp-user-docs/front-matter', $data);
}

/**
 * Outputs the documentation page
 */
function the_docs_admin_page($file) {
  $doc = get_doc($file);

  $parser = new \cebe\markdown\GithubMarkdown();
  echo '<h1>' . $doc['data']['title'] . '</h1>';
  echo '<article class="markdown-body">' . $parser->parse($doc["markdown"]) . '</article>';
}

function docs_admin_submenu_page_handler() {
  the_docs_admin_page(basename(get_current_screen()->id));
}

function docs_admin_menu_page_handler() {
  $files = get_doc_files();
  the_docs_admin_page($files[0]);
}

function docs_admin_menu() {
  $files = get_doc_files();
  $docs = array_map(function($file){ return get_doc($file); }, $files);

  add_menu_page(
    'Documentation',
    'Documentation',
    $docs[0]['data']['capability'],
    'dp-user-docs/documentation',
    __NAMESPACE__ . '\\docs_admin_menu_page_handler',
    'dashicons-book',
    200
  );

  if(count($docs) > 1) {
    foreach($docs as $doc) {
      add_submenu_page(
        'dp-user-docs/documentation',
        $doc['data']['title'],
        $doc['data']['menu_title'],
        $doc['data']['capability'],
        'dp-user-docs/documentation/' . $doc['file'],
        __NAMESPACE__ . '\\docs_admin_submenu_page_handler'
      );
    }
  }

}

function load_admin_style() {
  wp_enqueue_style( 'admin_css', plugin_dir_url(__FILE__) . '/css/markdown.css', false, '1.0.0' );
}

add_action( 'admin_enqueue_scripts', __NAMESPACE__ . '\\load_admin_style' );

add_action( 'admin_menu',  __NAMESPACE__ . '\\docs_admin_menu' );
