<?php
/*
  Plugin Name: Jiffy Gallery Press
  Description: Setup image galleries in a jiffy.
  Author: Marat Nepomnyashy
  Author URI: http://www.maratbn.com
  License: GPL3
  Version: 0.0.1-development_unreleased
  Text Domain: domain-plugin-JiffyGalleryPress
*/

/*
  Jiffy Gallery Press -- WordPress plugin for setting up image galleries in a
                         jiffy.

  Copyright (C) 2016  Marat Nepomnyashy  http://maratbn.com  maratbn@gmail

  Version:        0.0.1-development_unreleased

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

const PLUGIN_VERSION = '0.0.1-development_unreleased';

const IS_MODE_RELEASE = false;


const PHP_VERSION_MIN_SUPPORTED = '5.3';

const DOMAIN_PLUGIN_JIFFY_GALLERY_PRESS = 'domain-plugin-JiffyGalleryPress';


\register_activation_hook(__FILE__, '\\plugin_JiffyGalleryPress\\plugin_activation_hook');


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

?>