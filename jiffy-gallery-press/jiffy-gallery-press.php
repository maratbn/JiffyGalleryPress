<?php
/*
  Plugin Name: Jiffy Gallery Press
  Description: Setup image galleries in a jiffy via shortcodes specifying image slugs, and have the images load in a jiffy by downloading only the minimum size image necessary for the client viewport size.  No locked premium features and tested to work on iOS devices.
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

  Copyright (C) 2016-2017  Marat Nepomnyashy  http://maratbn.com  maratbn@gmail

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

const ADMIN_THUMB_SIZE = 64;


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
    \add_action('admin_enqueue_scripts',
                '\\plugin_JiffyGalleryPress\\action__admin_enqueue_scripts');
    \add_action('admin_menu',
                '\\plugin_JiffyGalleryPress\\action__admin_menu');
    \add_action('admin_print_footer_scripts',
                '\\plugin_JiffyGalleryPress\\action__admin_print_footer_scripts');
    \add_action('wp_ajax_jiffy_gallery_press__get_image',
                '\\plugin_JiffyGalleryPress\\action__wp_ajax_jiffy_gallery_press__get_image');

    \add_filter('plugin_action_links_' . \plugin_basename(__FILE__),
                '\\plugin_JiffyGalleryPress\\filter_plugin_action_links');
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
        $strShortcode = $arrMatchShortcode[0];
        $strItems = $arrMatchShortcode[1];

        \array_push($arrMatches, array('shortcode'    => $strShortcode,
                                       'items'        => \preg_split("/\s+|\s*,\s*/", $strItems),
                                       'indexItems'   => \strpos($strShortcode, $strItems),
                                       'lengthItems'  => \strlen($strItems)));
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

function action__admin_enqueue_scripts() {
    $current_screen = \get_current_screen();
    if ($current_screen->base != 'settings_page_' . SLUG_INFO_SETTINGS) return;

    doEnqueueScripts();
}

function action__admin_menu() {
    \add_options_page(
                    \__('Jiffy Gallery Press Info / Settings', DOMAIN_PLUGIN_JIFFY_GALLERY_PRESS),
                    \__('Jiffy Gallery Press', DOMAIN_PLUGIN_JIFFY_GALLERY_PRESS),
                    'manage_options',
                    SLUG_INFO_SETTINGS,
                    '\\plugin_JiffyGalleryPress\\renderPageInfoSettings');
}

function action__admin_print_footer_scripts() {
    $current_screen = \get_current_screen();
    if ($current_screen->base != 'settings_page_' . SLUG_INFO_SETTINGS) return;
    ?><style>
        div.jiffy_gallery_press__settings h4.jgp_post {
            margin:                     0;
        }

        div.jiffy_gallery_press__settings div.jgp_post {
            display:                    block;
        }

        div.jiffy_gallery_press__settings div.jgp_post,
        div.jiffy_gallery_press__settings div.jgp_post ul.jgp_shortcodes {
            margin-left:                1rem;
        }

        div.jiffy_gallery_press__settings div.jgp_post ul.jgp_shortcodes li ul.jgp_gallery > li {
            margin:                     1rem;
        }

        div.jiffy_gallery_press__settings div.jgp_post ul.jgp_shortcodes li ul.jgp_gallery > li img.jgp_thumbnail {
            vertical-align:             top;
            width:                      <?=ADMIN_THUMB_SIZE?>px;
            height:                     <?=ADMIN_THUMB_SIZE?>px;
        }
    </style><?php
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
    global $post;
    $strContent = $post->post_content;
    if (!\has_shortcode($strContent, SHORTCODE__JIFFY_GALLERY_PRESS)) return;

    doEnqueueScripts();
}

function action__wp_print_footer_scripts() {
    global $post;
    $strContent = $post->post_content;
    if (!\has_shortcode($strContent, SHORTCODE__JIFFY_GALLERY_PRESS)) return;

    $arrItemsMerged = array();
    $arrPostTitles = array();

    $arrMatchesShortcode = _getMatchesInContent($strContent);
    foreach ($arrMatchesShortcode as $arrMatchShortcode) {
        $arrItems = $arrMatchShortcode['items'];

        foreach ($arrItems as $strItem) {
            if (!\array_key_exists($strItem, $arrItemsMerged)) {
                $postItem = _getPostForImageByName($strItem);
                if (!$postItem) continue;

                $strTitleUse = getPostTitle($postItem);

                $arrItemsMerged[$strItem] = array('id'     => $postItem->ID,
                                                  'title'  => $strTitleUse);
                $arrPostTitles[$postItem->ID] = $strTitleUse;
            }
        }
    }

    printSegmentJS($arrPostTitles);
}

function getPostTitle($post) {
    $strTitleUse = $post->post_excerpt;

    if (\strlen($strTitleUse) == 0) {
        $strTitleUse = $post->post_title;
    }

    return $strTitleUse;
}

function doEnqueueScripts() {
    \wp_enqueue_script('plugin__Jiffy-Gallery-Press__jiffy-gallery-press_js',
                       \plugin_dir_url(__FILE__) . '/jiffy-gallery-press.js',
                       array('jquery'),
                       _getUVArg());

    \wp_enqueue_style('plugin__Jiffy-Gallery-Press__jiffy-gallery-press_css',
                      plugin_dir_url(__FILE__) . '/jiffy-gallery-press.css',
                      null,
                      _getUVArg());
}

function filter_plugin_action_links($arrLinks) {
    \array_push($arrLinks,
                '<a href=\'' . getUrlSettings() . '\'>'
                            . \__('Info / Settings', DOMAIN_PLUGIN_JIFFY_GALLERY_PRESS) . '</a>');
    return $arrLinks;
}

function generateSegmentThumbnail($index, $strIDsThumbnails, $strURL, $strTitle) {
    $strTitleEsc = \esc_attr($strTitle);

    return \implode(array('<a class=\'jiffy_gallery_press__thumbnail\' href=\'#jgp_closeup&pos=',
                                      $index,
                                      '&items=',
                                      $strIDsThumbnails,
                                   '\'>',
                            '<img',
                              ' class=\'jiffy_gallery_press__thumbnail\'',
                              ' src=\'', \esc_url_raw($strURL), '\'',
                              ' alt=\'', $strTitleEsc, '\'',
                              ' data-caption=\'', $strTitleEsc, '\'',
                              ' title=\'', $strTitleEsc, '\'',
                            '>',
                          '</a>'));
}

function getUrlSettings() {
    return \admin_url('options-general.php?page=' . SLUG_INFO_SETTINGS);
}

function printSegmentJS($arrPostTitles) {
?>
<script type='text/javascript'>
    jQuery(document).ready(function($) {
            new JiffyGalleryPressLightbox({
                        ajax_url:  <?=\json_encode(
                                       \wp_make_link_relative(
                                        \admin_url('admin-ajax.php')))?>, $: $,
                        titles:    <?=\json_encode($arrPostTitles)?>,

                        url_icons: <?=\json_encode(
                                       \wp_make_link_relative(
                                        \plugin_dir_url(__FILE__) . 'icons.svg?' . _getUVArg()))?>
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

        \array_push(
            $arrOutputThumbnails,
            generateSegmentThumbnail($i,
                                     $strIDsThumbnails,
                                     $objThumbnail['url'],
                                     $objThumbnail['title']));
    }

    return \implode(array(
                '<div class=\'jiffy_gallery_press__container\'>',
                  \implode($arrOutputThumbnails),
                '</div>'));
}

function renderPageInfoSettings() {
?><div class='wrap jiffy_gallery_press__settings'><?php
  ?><h1><?=\__('Jiffy Gallery Press Info / Settings',
               DOMAIN_PLUGIN_JIFFY_GALLERY_PRESS)?></h1><?php

  ?><h2><?=\__('Instructions:',
               DOMAIN_PLUGIN_JIFFY_GALLERY_PRESS)?></h2><?
  ?><p><?php
    ?><?=\sprintf(
            \__('Insert the shortcode %s into any page / post at the position at which you want to display a gallery.',
                DOMAIN_PLUGIN_JIFFY_GALLERY_PRESS),
            '<strong>' .
              '[jiffy-gallery-press items=\'image_slug_1 image_slug_2 image_slug_3\']' .
            '</strong>')?><?php
  ?></p><?php
  ?><p><?php
    ?><?=\__('Click on a post URL to view that post (with the gallery), or on the post slug to edit.')?><?php
    ?>  <?php
    ?><?=\__('Click on an image slug to go to its \'Edit Media\' page.')?><?php
  ?></p><?php

  ?><h2><?=\__('The following posts are using the Jiffy Gallery Press shortcode:',
               DOMAIN_PLUGIN_JIFFY_GALLERY_PRESS)?></h2><?php

    $totalPostsUsingShortcode = 0;

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

        $totalPostsUsingShortcode++;
        $strPermalink = \get_permalink($post->ID);

    ?><li><?php
      ?><h4 class='jgp_post'><?=\get_the_title($post->ID)?></h4><?php
      ?><div class='jgp_post'><?php
        ?><a href='<?=\esc_url_raw($strPermalink)?>' target='_blank'><?=$strPermalink?></a><?php
        ?><br><?php
        ?><a href='<?=\esc_url_raw(\get_edit_post_link($post->ID))?>' target='_blank'><?php
          ?><?=$post->post_name?><?php
        ?></a><?php
        ?><ul class='jgp_shortcodes'><?php
          $arrMatchesShortcode = _getMatchesInContent($strContent);

          foreach ($arrMatchesShortcode as $arrMatchShortcode) {
              $arrIDsThumbnails  = array();
              $arrListItems      = $arrMatchShortcode['items'];
              $indexItems        = $arrMatchShortcode['indexItems'];
              $strShortcode      = $arrMatchShortcode['shortcode'];

              foreach ($arrListItems as $strListItem) {
                  $indexItem = \strpos($strShortcode, $strListItem, $indexItems);

                  $postListItem = _getPostForImageByName($strListItem);

                  \array_push($arrIDsThumbnails, $postListItem->ID);

                  $strListItemReplace = $postListItem
                                      ? '<a href=\'' . \esc_url_raw(
                                                        \get_edit_post_link($postListItem->ID))
                                                     . '\' target=\'_blank\'>' .
                                          $strListItem .
                                        '</a>'
                                      : '<font color=\'red\'>' . $strListItem . '</font>';

                  $strShortcode = \substr_replace($strShortcode,
                                                  $strListItemReplace,
                                                  $indexItem,
                                                  \strlen($strListItem));
              }
          ?><li><?php
            ?><strong><?=$strShortcode?></strong><?php
            ?><ul class='jgp_gallery jiffy_gallery_press__container'><?php
                $strIDsThumbnails = \implode($arrIDsThumbnails, ',');
                $totalListItems = \count($arrListItems);
                for ($i = 0; $i < $totalListItems; $i++) {
                    $strListItem = $arrListItems[$i];
                    $postListItem = _getPostForImageByName($strListItem);
                    if (!$postListItem) continue;

                ?><li><?php

                      $objImage = \wp_get_attachment_image_src($postListItem->ID,
                                                               array(ADMIN_THUMB_SIZE,
                                                                     ADMIN_THUMB_SIZE),
                                                               true);
                      $urlImage = $objImage ? $objImage[0] : null;
                      if ($urlImage) {
                          $width   = $objImage[1];
                          $height  = $objImage[2];
                          $scale   = $width > $height ? ADMIN_THUMB_SIZE / $width
                                                      : ADMIN_THUMB_SIZE / $height;
                      ?><a class='jiffy_gallery_press__thumbnail'<?php
                        ?> href='#jgp_closeup&pos=<?=$i?>&items=<?=$strIDsThumbnails?>'><?php
                        ?><img class='jgp_thumbnail jiffy_gallery_press__thumbnail' src='<?=$urlImage?>'></a><?php
                      }

                  ?><div><?php
                    ?><a href='<?=\esc_url_raw(
                                   \get_edit_post_link($postListItem->ID))?>' target='_blank'><?php
                    ?><?=$strListItem?><?php
                  ?></a></div></li><?php
                }
            ?></ul><?php
          ?></li><?php
          }
        ?><ul><?php
      ?></div><?php
    ?></li><?php
    }
  ?><ul><?php

  if ($totalPostsUsingShortcode == 0) {
    ?><?=\__('Currently no posts use the Jiffy Gallery Press shortcode.',
             DOMAIN_PLUGIN_JIFFY_GALLERY_PRESS)?><?php
  }
?></div><?php
}
?>