<?php

/**
  * SquirrelMail TNEF Decoder Plugin
  *
  * Copyright (c) 2010- Paul Lesniewski <paul@squirrelmail.org>
  * Copyright (c) 2003  Bernd Wiegmann <bernd@wib-software.de>
  * Copyright (c) 2002  Graham Norburys <gnorbury@bondcar.com>
  *
  * Licensed under the GNU GPL. For full terms see the file COPYING.
  *
  * @package plugins
  * @subpackage tnef_decoder
  *
  */



global $tnef_debug, $tnef_minimum_rtf_size_to_decode,
       $tnef_remove_winmail_download_link, $allow_sloppy_tnef_mime_type,
       $tnef_maximum_download_links, $tnef_show_download_text;



// This plugin will provide links for all the files
// included in TNEF attachments, up to the number
// specified here.  When the number of attachments
// is greater than this, only a link to see all files
// is shown
//
$tnef_maximum_download_links = 10;



// This plugin provides download links for the files
// within TNEF attachments, so usually, you don't need
// a download link for the TNEF file (usually "winmail.dat")
// itself.  Disabling this setting (set to 0 (zero))
// will bring back that generic download link.
//
$tnef_remove_winmail_download_link = 1;



// Enabling this setting (set to 1) will show "Download"
// text preceding each of the file names in the TNEF
// attachment list on the message view screen.  With
// this setting disabled (set to 0 (zero)), no
// "Download" text is shown.
//
$tnef_show_download_text = 1;



// Occasionally a TNEF file will be attached with an
// incorrect MIME type.  Should this plugin be more
// aggressive by decoding attachments named "winmail.dat"
// even when the MIME type is wrong?
//
//    0 = Only decode TNEF attachments when the MIME
//        type is correct ("application/ms-tnef")
//    1 = Also decode "winmail.dat" attachments when the
//        MIME type is "application/octet-stream"
//    2 = Decode *ANY* "winmail.dat" attachments,
//        regardless of the MIME type
//
$allow_sloppy_tnef_mime_type = 1;



// Any RTF files smaller than this will NOT be decoded
// and offered to the user.  This helps avoid offering
// small (often blank) RTF files that seem to usually
// be copies of the original email.  This number is in
// bytes.
//
$tnef_minimum_rtf_size_to_decode = 300;



// Enable only when you need to debug the decoding
// decoding of TNEF attachments.  NOTE that this is
// ONLY used when viewing the separate page dedicated
// to a TNEF attachment, and NOT on the message view
// page!
//
// 1 = enable debugging; 0 (zero) = disable debugging
//
$tnef_debug = 0;



