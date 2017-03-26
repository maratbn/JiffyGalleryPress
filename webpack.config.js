/*
  Jiffy Gallery Press -- WordPress plugin for setting up image galleries in a
                         jiffy via shortcodes associated with image slugs, and
                         for having the images load in a jiffy by downloading
                         only the minimum size image necessary for the client
                         viewport size.

  Copyright (C) 2016-2017  Marat Nepomnyashy  http://maratbn.com  maratbn@gmail

  Version:        1.2.0-development_unreleased

  Module:         webpack.config.js

  Description:    Webpack configuration file for minimizing plugin JavaScript.

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

const path = require('path'),
      webpack = require('webpack');

module.exports = {
    entry: path.join(__dirname, 'jiffy-gallery-press', 'jiffy-gallery-press.js'),
    output: {
        path:      path.join(__dirname, 'jiffy-gallery-press'),
        filename:  'jiffy-gallery-press.min.js'
      },
    plugins: [
        new webpack.optimize.UglifyJsPlugin,
        new webpack.optimize.DedupePlugin,
        new webpack.DefinePlugin({'process.env': {
                                      'NODE_ENV': JSON.stringify('production')
                                    }
                                  })
      ]
  };
