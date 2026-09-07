/*
 Copyright (c) 2007-2019, CKSource - Frederico Knabben. All rights reserved.
 For licensing, see LICENSE.html or https://ckeditor.com/sales/license/ckfinder
 */

var config = {};
var url = window.location.href;
var url = new URL(url);
var token = url.searchParams.get("token");
var connectorParams = new URLSearchParams();
['token', 'fm_com', 'fm_act', 'fm_type', 'fm_variant', 'fm_has_id'].forEach(function (name) {
    var value = url.searchParams.get(name);
    if (value !== null) connectorParams.set(name, value);
});

// Set your configuration options below.
// Examples:
// config.language = 'pl';
// config.skin = 'jquery-mobile';
config.connectorInfo = connectorParams.toString();
CKFinder.define( config );
