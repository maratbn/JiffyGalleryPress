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
        url_icons = params.url_icons,
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

        var strItemIDs = arrItems.join(',');

        return {frag_next:  '#jgp_closeup&pos=' + posNext + '&items=' + strItemIDs,
                frag_prev:  '#jgp_closeup&pos=' + posPrev + '&items=' + strItemIDs,
                pos:        pos,
                pos_next:   posNext,
                pos_prev:   posPrev,
                items:      arrItems};
    }


    var $body = $('body');

    var $divScreen  = $('<div>').css({'position':           'fixed',
                                      'top':                0,
                                      'left':               0,
                                      'bottom':             0,
                                      'right':              0,
                                      'background-color':   'black',
                                      'opacity':            0.95})
                                .appendTo($body),

        $divPhotoC  = $('<div>').css({'position':           'fixed',
                                      'top':                0,
                                      'left':               0,
                                      'bottom':             0,
                                      'right':              0})
                                .appendTo($body);


    var $divPhoto   = $('<div>').css({'width':              '100%',
                                      'height':             '100%',
                                      'background-position':
                                                            'center center',
                                      'background-repeat':  'no-repeat',
                                      'background-size':    'contain'})
                                .appendTo($divPhotoC),

        $divBrowser = $('<div>').css({'background-color':   'black',
                                      'opacity':            '0.7',
                                      'font-weight':        'bold',
                                      'position':           'absolute',
                                      'left':               0,
                                      'right':              0,
                                      'bottom':             0,
                                      'text-align':         'center'
                                    })
                                .appendTo($divPhotoC),

        $divStripLeft = $('<div>').css({'position':         'absolute',
                                        'top':              0,
                                        'left':             0,
                                        'bottom':           0,
                                        'width':            '80px'})
                                  .appendTo($divPhotoC),

        $divStripRight = $('<div>').css({'position':        'absolute',
                                         'top':             0,
                                         'right':           0,
                                         'bottom':          0,
                                         'width':           '80px'})
                                   .appendTo($divPhotoC);


    var $divTitle   = $('<div>').css({'color':              'white'})
                                .appendTo($divBrowser),

        $aIconClose  = $('<a>').attr('href', '#jgp_close')
                               .css({'display':             'block',
                                     'width':               '50px',
                                     'height':              '60px',
                                     'background-image':    'url(' + url_icons + ')',
                                     'background-position': '-157px -195px',
                                     'background-repeat':   'no-repeat',
                                     'position':            'absolute',
                                     'top':                 '10px',
                                     'right':               '10px'})
                               .appendTo($divStripRight),

        $aIconNext  = $('<a>').css({'display':              'block',
                                    'width':                '80px',
                                    'height':               '90px',
                                    'background-image':     'url(' + url_icons + ')',
                                    'background-position':  '5px -95px',
                                    'background-repeat':    'no-repeat',
                                    'position':             'absolute',
                                    'top':                  '50%',
                                    'margin-top':           '-45px'})
                              .appendTo($divStripRight),

        $aIconPrev  = $('<a>').css({'display':              'block',
                                    'width':                '80px',
                                    'height':               '90px',
                                    'background-image':     'url(' + url_icons + ')',
                                    'background-position':  '-7px 0px',
                                    'background-repeat':    'no-repeat',
                                    'position':             'absolute',
                                    'top':                  '50%',
                                    'margin-top':           '-45px'})
                              .appendTo($divStripLeft),

        $window     = $(window),

        $head       = $('head'),

        $metaA      = $('<meta>').attr({'name':             'apple-mobile-web-app-capable',
                                        'content':          'yes'}),

        $metaV      = $('<meta>').attr({'name':             'viewport',
                                        'content':          'width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no'});


    function _updateCloseup(objBrowseInfo) {
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

        $aIconPrev.attr('href', objBrowseInfo.frag_prev);
        $aIconNext.attr('href', objBrowseInfo.frag_next);
    }

    function _processLinkClickNext(event) {
        if (!window.history ||
            !window.history.replaceState) return;

        var objBrowseInfo = _getBrowseInfo();
        if (!objBrowseInfo) return;

        event.preventDefault();

        window.history.replaceState(null, null, objBrowseInfo.frag_next);

        _updateCloseup(_getBrowseInfo());
    }

    function _processLinkClickPrev(event) {
        if (!window.history ||
            !window.history.replaceState) return;

        var objBrowseInfo = _getBrowseInfo();
        if (!objBrowseInfo) return;

        event.preventDefault();

        window.history.replaceState(null, null, objBrowseInfo.frag_prev);

        _updateCloseup(_getBrowseInfo());
    }

    $aIconNext.click(_processLinkClickNext);
    $aIconPrev.click(_processLinkClickPrev);

    function _processUrlFragment() {
        _updateCloseup(_getBrowseInfo());
    }
    _processUrlFragment();
    $window.on('hashchange', _processUrlFragment);
}
