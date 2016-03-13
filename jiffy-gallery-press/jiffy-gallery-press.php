<?php
/*
  Plugin Name: Jiffy Gallery Press
  Description: Setup image galleries in a jiffy via shortcodes associated with image slugs, and have the images load in a jiffy by downloading only the minimum size image necessary for the client viewport size.
  Author: Marat Nepomnyashy
  Author URI: http://www.maratbn.com
  License: GPL3
  Version: 0.0.2-development_unreleased
  Text Domain: domain-plugin-JiffyGalleryPress
*/

/*
  Jiffy Gallery Press -- WordPress plugin for setting up image galleries in a
                         jiffy via shortcodes associated with image slugs, and
                         for having the images load in a jiffy by downloading
                         only the minimum size image necessary for the client
                         viewport size.

  Copyright (C) 2016  Marat Nepomnyashy  http://maratbn.com  maratbn@gmail

  Version:        0.0.2-development_unreleased

  Module:         jiffy-gallery-press/jiffy-gallery-press.php

  Description:    Main PHP file for the WordPress plugin Jiffy Gallery Press.

  This file is part of Jiffy Gallery Press.

  Licensed under the GNU General Public License Version 3.

  Jiffy Gallery Press is free software: you can redistribute it and/or modify
  it under the terms of the GNU General Public License as published by
  the Free Software Foundation, either version 3 of the License, or
  (at your option) any later version.

  Jiffy Gallery Press is distributed in the hope that it will be useful,
  but WITHOUT ANY WARRANTY; without even the implied warranty of
  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  GNU General Public License for more details.

  You should have received a copy of the GNU General Public License
  along with Jiffy Gallery Press.  If not, see <http://www.gnu.org/licenses/>.
*/

namespace plugin_JiffyGalleryPress;

const PLUGIN_VERSION = '0.0.2-development_unreleased';


const IS_MODE_RELEASE = false;


const PHP_VERSION_MIN_SUPPORTED = '5.3';

const DOMAIN_PLUGIN_JIFFY_GALLERY_PRESS = 'domain-plugin-JiffyGalleryPress';

const LEN_BUFFER_FILE_READ = 4096;

const SLUG_INFO_SETTINGS = 'plugin_JiffyGalleryPress_admin';

const SHORTCODE__JIFFY_GALLERY_PRESS = 'jiffy-gallery-press';


\register_activation_hook(__FILE__, '\\plugin_JiffyGalleryPress\\plugin_activation_hook');


\add_action('wp_ajax_nopriv_jiffy_gallery_press__get_image',
            '\\plugin_JiffyGalleryPress\\action__wp_ajax_jiffy_gallery_press__get_image');
\add_action('wp_enqueue_scripts',
            '\\plugin_JiffyGalleryPress\\action__wp_enqueue_scripts');
\add_action('wp_print_footer_scripts',
            '\\plugin_JiffyGalleryPress\\action__wp_print_footer_scripts');


\add_shortcode(SHORTCODE__JIFFY_GALLERY_PRESS,
               '\\plugin_JiffyGalleryPress\\shortcode__jiffy_gallery_press');


if (\is_admin()) {
    \add_action('admin_menu',
                '\\plugin_JiffyGalleryPress\\action__admin_menu');
    \add_action('wp_ajax_jiffy_gallery_press__get_image',
                '\\plugin_JiffyGalleryPress\\action__wp_ajax_jiffy_gallery_press__get_image');
}


function _get(&$var, $default = null) {
    return isset($var) ? $var : $default;
}

function _getMatchesInContent($strContent) {
    $arrMatchesShortcode = array();

    \preg_match_all(
        "/[\[]\s*jiffy-gallery-press\s+items\s*=\s*[\'\"]\s*([^\s\'\"]+\s*(?:,?\s*[^\s\'\"]+)*)\s*[\'\"]\s*\]/i",
        $strContent,
        $arrMatchesShortcode,
        \PREG_SET_ORDER);

    $arrMatches = array();

    foreach ($arrMatchesShortcode as $arrMatchShortcode) {
        \array_push($arrMatches, array('shortcode'  => $arrMatchShortcode[0],
                                       'items'      => \preg_split("/\s+|\s*,\s*/",
                                                                   $arrMatchShortcode[1])));
    }

    return $arrMatches;
}

function _getPostForImageByName($strName) {
    if ($strName == null) return null;

    $objQuery = new \WP_Query(array('post_type'  =>'attachment',
                                    'name'       => $strName));
    if (!$objQuery) return null;

    $arrPosts = $objQuery->posts;
    if (!$arrPosts || \count($arrPosts) == 0) return null;

    return $arrPosts[0];
}

/**
 *  Returns unique version args to append to a resource URL to make
 *  that resource be unique in the browser cache.
 */
function _getUVArg() {
    return 'uv=' . PLUGIN_VERSION . (IS_MODE_RELEASE ? "" : ('_' . time() . rand()));
}

function plugin_activation_hook() {
    if (\version_compare(\strtolower(PHP_VERSION), PHP_VERSION_MIN_SUPPORTED, '<')) {
        \wp_die(
            \sprintf(
                \__('Plugin Jiffy Gallery Press cannot be activated because the currently active PHP version on this server is %s < %s and not supported.  PHP version >= %s is required.',
                    DOMAIN_PLUGIN_JIFFY_GALLERY_PRESS),
                PHP_VERSION,
                PHP_VERSION_MIN_SUPPORTED,
                PHP_VERSION_MIN_SUPPORTED));
    }
}

function action__admin_menu() {
    \add_submenu_page(
        'tools.php',
        \__('Jiffy Gallery Press Info / Settings', DOMAIN_PLUGIN_JIFFY_GALLERY_PRESS),
        \__('Jiffy Gallery Press', DOMAIN_PLUGIN_JIFFY_GALLERY_PRESS),
        'manage_options',
        SLUG_INFO_SETTINGS,
        '\\plugin_JiffyGalleryPress\\renderPageInfoSettings');
}

function action__wp_ajax_jiffy_gallery_press__get_image() {
    $post_id = _get($_GET['id']);
    if ($post_id == null) die;

    if (\get_post_status($post_id) != 'publish') die;

    $post = \get_post($post_id);
    if ($post == null) die;

    $widthClient = _get($_GET['width']);
    $heightClient = _get($_GET['height']);

    $strImageDir = \dirname(\get_attached_file($post_id)) . \DIRECTORY_SEPARATOR;

    $strMinImageFilename = null;
    $strMinMimeType = null;

    $strMaxImageFilename = null;
    $strMaxMimeType = null;

    if ($widthClient > 0 && $heightClient > 0) {
        $objPostMeta = \get_post_meta($post_id);
        $arrAttachmentMeta = $objPostMeta ? _get($objPostMeta['_wp_attachment_metadata']) : null;

        $deltaWidthMin = null;
        $deltaHeightMin = null;

        $areaMax = null;

        foreach ($arrAttachmentMeta as $strAttachmentMeta) {
            $objAttachmentMeta = \unserialize($strAttachmentMeta);
            $objAttachmentSizes = _get($objAttachmentMeta['sizes']);

            foreach ($objAttachmentSizes as $objAttachmentSize) {
                $widthThumb = _get($objAttachmentSize['width']);
                $heightThumb = _get($objAttachmentSize['height']);
                if ($widthThumb == null || $heightThumb == null) continue;

                $strFilenameHere = _get($objAttachmentSize['file']);
                if ($strFilenameHere == null) continue;

                $strMimeTypeHere = _get($objAttachmentSize['mime-type']);
                if ($strMimeTypeHere == null) continue;

                $strFilenamePath = $strImageDir . $strFilenameHere;
                if (!\file_exists($strFilenamePath)) continue;

                $deltaWidthHere = $widthThumb - $widthClient;
                if ($widthThumb >= $widthClient) {
                    if ($deltaWidthMin == null || $deltaWidthHere < $deltaWidthMin) {
                        $deltaWidthMin        = $deltaWidthHere;
                        $strMinImageFilename  = $strFilenamePath;
                        $strMinMimeType       = $strMimeTypeHere;
                    }
                }

                $deltaHeightHere = $heightThumb - $heightClient;;
                if ($heightThumb >= $heightClient) {
                    if ($deltaHeightMin == null || $deltaHeightHere < $deltaHeightMin) {
                        $deltaHeightMin       = $deltaHeightHere;
                        $strMinImageFilename  = $strFilenamePath;
                        $strMinMimeType       = $strMimeTypeHere;
                    }
                }

                $areaHere = $widthThumb * $heightThumb;
                if ($areaMax == null || $areaMax < $areaHere) {
                    $areaMax                  = $areaHere;
                    $strMaxImageFilename      = $strFilenamePath;
                    $strMaxMimeType           = $strMimeTypeHere;
                }
            }
        }
    }

    $strUseImageFilename  = $strMinImageFilename;
    $strUseMimeType       = $strMinMimeType;
    if ($strUseImageFilename == null) {
        $strUseImageFilename  = $strMaxImageFilename;
        $strUseMimeType       = $strMaxMimeType;
    }
    if ($strUseImageFilename == null) die();

    header('Content-type: ' . $strUseMimeType);

    $file_handle = \fopen($strUseImageFilename, 'rb');
    if (!$file_handle) die;

    while ($data = \fread($file_handle, LEN_BUFFER_FILE_READ)) {
        echo $data;
    }

    \fclose($file_handle);

    die();
}

function action__wp_enqueue_scripts() {
    \wp_enqueue_script('plugin__Jiffy-Gallery-Press__jiffy-gallery-press_js',
                       \plugin_dir_url(__FILE__) . '/jiffy-gallery-press.js',
                       array('jquery'),
                       _getUVArg());

    \wp_enqueue_style('plugin__Jiffy-Gallery-Press__jiffy-gallery-press_css',
                      plugin_dir_url(__FILE__) . '/jiffy-gallery-press.css',
                      null,
                      _getUVArg());
}

function action__wp_print_footer_scripts() {
    global $post;
    $strContent = $post->post_content;
    if (!\has_shortcode($strContent, SHORTCODE__JIFFY_GALLERY_PRESS)) return;
?>
<script type='text/javascript'>
    jQuery(document).ready(function($) {
            new JiffyGalleryPressLightbox({
                        ajax_url:  <?=\json_encode(
                                       \wp_make_link_relative(
                                        \admin_url('admin-ajax.php')))?>, $: $
                    });
        });
</script>
<?php
}

function shortcode__jiffy_gallery_press($arrAttrs) {
    $strItems = _get($arrAttrs['items']);
    $arrItems = \preg_split('/\s+/', $strItems);

    $arrOutputThumbnails = array();

    $totalItems = \count($arrItems);

    $arrDataThumbnails = array();
    $arrIDsThumbnails = array();

    for ($i = 0; $i < $totalItems; $i++) {
        $strItem = $arrItems[$i];
        $postItem = _getPostForImageByName($strItem);
        if (!$postItem) continue;

        $objImage = \wp_get_attachment_image_src($postItem->ID, array(150, 150));
        if (!$objImage) continue;

        $urlImage = $objImage[0];
        if ($urlImage == null) continue;

        \array_push($arrDataThumbnails, array('url'    => $urlImage,
                                              'title'  => $postItem->post_title));
        \array_push($arrIDsThumbnails, $postItem->ID);
    }

    $totalThumbnails = \count($arrDataThumbnails);
    $strIDsThumbnails = \implode($arrIDsThumbnails, ',');

    for ($i = 0; $i < $totalThumbnails; $i++) {
        $objThumbnail = $arrDataThumbnails[$i];

        $strTitleEsc = \esc_attr($objThumbnail['title']);

        \array_push(
            $arrOutputThumbnails,
            \implode(array(
                '<a class=\'jiffy-gallery-press--a\' href=\'#jgp_closeup&pos=',
                            $i,
                            '&items=',
                            $strIDsThumbnails,
                         '\'>',
                  '<img',
                    ' class=\'jiffy-gallery-press--thumbnail\'',
                    ' src=\'', \esc_url_raw($objThumbnail['url']), '\'',
                    ' alt=\'', $strTitleEsc, '\'',
                    ' data-caption=\'', $strTitleEsc, '\'',
                    ' title=\'', $strTitleEsc, '\'',
                  '>',
                '</a>')));
    }

    return \implode(array(
                '<div class=\'jiffy-gallery-press--container\'>',
                  \implode($arrOutputThumbnails),
                '</div>'));
}

function renderPageInfoSettings() {
?><div class='wrap'><?php
  ?><h1><?=\__('Jiffy Gallery Press Info / Settings',
               DOMAIN_PLUGIN_JIFFY_GALLERY_PRESS)?></h1><?php

    $w_p_query = new \WP_Query(array('order'           => 'ASC',
                                     'orderby'         => 'name',
                                     'post_status'     => 'any',
                                     'post_type'       => \get_post_types(array('public' => true)),
                                     'posts_per_page'  => -1));

    global $post;

  ?><ul><?php
    while ($w_p_query->have_posts()) {
        $w_p_query->the_post();

        $strContent = $post->post_content;

        if (!\has_shortcode($strContent, SHORTCODE__JIFFY_GALLERY_PRESS)) continue;

    ?><li><a href='<?=\esc_url_raw(\get_edit_post_link($post->ID))?>' target='_blank'><?php
        ?><?=$post->post_name?><?php
      ?></a><?php
      ?><ul style='margin-left:1rem;'><?php
        $arrMatchesShortcode = _getMatchesInContent($strContent);

        foreach ($arrMatchesShortcode as $arrMatchShortcode) {
        ?><li><?=$arrMatchShortcode['shortcode']?><?php
          ?><ul style='margin-left:1rem;'><?php
              $arrListItems = $arrMatchShortcode['items'];
              foreach ($arrListItems as $strListItem) {
                  $postListItem = _getPostForImageByName($strListItem);
                  if (!$postListItem) continue;

              ?><li><a href='<?=\esc_url_raw(
                                 \get_edit_post_link($postListItem->ID))?>' target='_blank'><?php
                  ?><?=$strListItem?><?php
                ?></a></li><?php
              }
          ?></ul><?php
        ?></li><?php
        }
      ?><ul><?php
    ?></li><?php
    }
  ?><ul><?php
?></div><?php
}
?>