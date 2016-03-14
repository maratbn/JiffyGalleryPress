/*
  Jiffy Gallery Press -- WordPress plugin for setting up image galleries in a
                         jiffy via shortcodes associated with image slugs, and
                         for having the images load in a jiffy by downloading
                         only the minimum size image necessary for the client
                         viewport size.

  Copyright (C) 2016  Marat Nepomnyashy  http://maratbn.com  maratbn@gmail

  Version:        0.0.2-development_unreleased

  Module:         jiffy-gallery-press/jiffy-gallery-press.js

  Description:    Main JavaScript file for the WordPress plugin Jiffy Gallery Press.

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

function JiffyGalleryPressLightbox(params) {

    var $         = params.$,
        ajax_url  = params.ajax_url,
        objTitles = params.titles;


    function _getSegment(arrHashSegments, regexSegmentFind) {
        if (!arrHashSegments) return null;

        for (var i = 0; i < arrHashSegments.length; i++) {
            var strSegment = arrHashSegments[i];
            if (!strSegment) continue;

            if (regexSegmentFind.test(strSegment)) return strSegment;
        }

        return null;
    }

    function _getSegmentValue(arrHashSegments, strSegmentFind) {
        var strSegment = _getSegment(arrHashSegments, strSegmentFind);
        if (!strSegment) return null;

        var indexEquals = strSegment.indexOf('=');
        if (indexEquals < 0) return null;

        return strSegment.substr(indexEquals+1);
    }

    function _getBrowseInfo() {
        var strHash = window.location.hash;
        if (!strHash) return null;

        var arrHashSegments = strHash.split('&');

        var strCloseup = _getSegment(arrHashSegments, /^#jgp_closeup$/i);
        if (!strCloseup) return null;

        var strItems = _getSegmentValue(arrHashSegments, /^items=\d+(,\d+)*$/i);
        if (!strItems) return null;

        var arrItems  = strItems.split(','),
            pos       = _getSegmentValue(arrHashSegments, /^pos=\d+$/i) || 0;

        var posPrev = pos - 1;
        if (posPrev < 0) posPrev = arrItems.length - 1;

        var posNext = window.parseInt(pos) + 1;
        if (posNext >= arrItems.length) posNext = 0;

        return {pos:       pos,
                pos_next:  posNext,
                pos_prev:  posPrev,
                items:     arrItems};
    }


    var $body = $('body');

    var $divScreen  = $('<div>').css({'position':          'fixed',
                                      'top':               0,
                                      'left':              0,
                                      'bottom':            0,
                                      'right':             0,
                                      'background-color':  'black',
                                      'opacity':           0.95})
                                .appendTo($body),

        $divPhotoC  = $('<div>').css({'position':          'fixed',
                                      'top':               0,
                                      'left':              0,
                                      'bottom':            0,
                                      'right':             0})
                                .appendTo($body);


    var $divPhoto   = $('<div>').css({'width':             '100%',
                                      'height':            '100%',
                                      'background-position':
                                                           'center center',
                                      'background-repeat': 'no-repeat',
                                      'background-size':   'contain'})
                                .appendTo($divPhotoC),

        $divBrowser = $('<div>').css({'background-color':  'black',
                                      'opacity':           '0.7',
                                      'font-weight':       'bold',
                                      'position':          'absolute',
                                      'left':              0,
                                      'right':             0,
                                      'bottom':            0,
                                      'text-align':        'center'
                                    })
                                .appendTo($divPhotoC);


    var $divTitle   = $('<div>').css({'color':             'white'})
                                .appendTo($divBrowser),

        $aPrev      = $('<a>').css({'margin':              '0 10px',
                                    'color':               'white',
                                    'text-decoration':     'none'})
                              .text("< Prev")
                              .appendTo($divBrowser),

        $aClose     = $('<a>').attr('href', '#jgp_close')
                              .css({'margin':              '0 10px',
                                    'color':               'white',
                                    'text-decoration':     'none'})
                              .text("Close")
                              .appendTo($divBrowser),

        $aNext      = $('<a>').css({'margin':              '0 10px',
                                    'color':               'white',
                                    'text-decoration':     'none'})
                              .text("Next >")
                              .appendTo($divBrowser),

        $window     = $(window),

        $head       = $('head'),

        $metaA      = $('<meta>').attr({'name':            'apple-mobile-web-app-capable',
                                        'content':         'yes'}),

        $metaV      = $('<meta>').attr({'name':            'viewport',
                                        'content':         'width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no'});


    function _processUrlFragment() {
        var objBrowseInfo = _getBrowseInfo();

        var strDisplay = objBrowseInfo ? "" : 'none';

        $divScreen.css('display', strDisplay);
        $divPhotoC.css('display', strDisplay);

        if (!objBrowseInfo) {
            $metaA.remove();
            $metaV.remove();
            return;
        }

        $metaA.appendTo($head);
        $metaV.appendTo($head);

        var idImage = objBrowseInfo.items[objBrowseInfo.pos];

        $divPhoto.css('background-image',
                      'url(' + ajax_url + '?action=jiffy_gallery_press__get_image&id='
                                        + idImage
                                        + '&width=' + $window.width()
                                        + '&height=' + $window.height() + ')');

        $divTitle.text(objTitles[idImage] || "");

        var strItemIDs = objBrowseInfo.items.join(',');

        $aPrev.attr('href', '#jgp_closeup&pos=' + objBrowseInfo.pos_prev + '&items=' + strItemIDs);
        $aNext.attr('href', '#jgp_closeup&pos=' + objBrowseInfo.pos_next + '&items=' + strItemIDs);
    }
    _processUrlFragment();
    $window.on('hashchange', _processUrlFragment);
}
