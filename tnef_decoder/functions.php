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



/**
  * Optionally Add "view" link for TNEF attachments with wrong MIME type
  *
  * @param array $args Various data about the current page request
  *                    and the target message
  *
  */
function tnef_decoder_link_application_octet(&$args)
{

   // make sure we only run once per attachment
   //
   global $tnef_decoded_attachments, $allow_sloppy_tnef_mime_type;
   if (!empty($tnef_decoded_attachments[$args[7]])) return;

   tnef_decoder_init();

   if ($args[7] == 'winmail.dat' && $allow_sloppy_tnef_mime_type >= 1) 
      tnef_decoder_link($args);

}



/**
  * Optionally Add "view" link for TNEF attachments with wrong MIME type
  *
  * @param array $args Various data about the current page request
  *                    and the target message
  *
  */
function tnef_decoder_link_all_types(&$args)
{

   // make sure we only run once per attachment
   //
   global $tnef_decoded_attachments, $allow_sloppy_tnef_mime_type;
   if (!empty($tnef_decoded_attachments[$args[7]])) return;

   tnef_decoder_init();

   if ($args[7] == 'winmail.dat' && $allow_sloppy_tnef_mime_type >= 2) 
      tnef_decoder_link($args);

}



/**
  * Add "view" link for TNEF attachments
  *
  * @param array $args Various data about the current page request
  *                    and the target message
  *
  */
function tnef_decoder_link(&$args)
{

   // make sure we only run once per attachment
   //
   global $tnef_decoded_attachments;
   $tnef_decoded_attachments[$args[7]] = TRUE;


   // FIXME: in 1.5.2, the message may or may not be available like this and the $args array may not be the same
   global $PHP_SELF, $imapConnection, $tnef_debug, $message,
          $tnef_remove_winmail_download_link, $tnef_maximum_download_links,
          $tnef_show_download_text;


   $old_text_domain = sq_change_text_domain('tnef_decoder');


   include_once(SM_PATH . 'plugins/tnef_decoder/constants.php');
   include_once(SM_PATH . 'plugins/tnef_decoder/tnef_attachment.php');
   include_once(SM_PATH . 'plugins/tnef_decoder/tnef_mailinfo.php');
   include_once(SM_PATH . 'plugins/tnef_decoder/tnef_file_base.php');
   include_once(SM_PATH . 'plugins/tnef_decoder/tnef_file_rtf.php');
   include_once(SM_PATH . 'plugins/tnef_decoder/tnef_file.php');
   include_once(SM_PATH . 'plugins/tnef_decoder/tnef_date.php');
   include_once(SM_PATH . 'plugins/tnef_decoder/tnef_vcard.php');


   tnef_decoder_init();


   // we'll only debug on the dedicated page
   //
   $tnef_debug = 0;


   // extract the TNEF attachment and decode it
   //
   $tnef_entity = getEntity($message, $args[5]);
   $tnef = mime_fetch_body($imapConnection, $args[3], $args[5]);
   $tnef = decodeBody($tnef, $tnef_entity->header->encoding);


   // now, dissect the TNEF
   //
   $attachment = new TnefAttachment($tnef_debug);
   $result = $attachment->decodeTnef($tnef);
   $tnef_files = $attachment->getFilesNested();
   $number_of_attachments = sizeof($tnef_files);



   // compute handler link
   //
   $view_details_link = sqm_baseuri()
                      . 'plugins/tnef_decoder/decode_tnef.php?startMessage='
                      . $args[2] . '&passed_id=' . $args[3]
                      . '&mailbox=' . $args[4]
                      . '&passed_ent_id=' . $args[5];


   // add search parameters if we came from a search
   //
   if (isset($args[8]) && isset($args[9]))
      $view_details_link .= '&where=' . urlencode($args[8])
                          . '&what=' . urlencode($args[9]);


   // show download links for more than one embedded attachment
   //
   if ($number_of_attachments >= 1
    && $number_of_attachments <= $tnef_maximum_download_links)
   {
      foreach ($tnef_files as $index => $file)
      {
         $args[1]['tnef_decoder_' . $index]['text']
            = ($tnef_show_download_text
               ? sprintf(_("Download %s"), $tnef_files[$index]->getName())
               : $tnef_files[$index]->getName());
         $args[1]['tnef_decoder_' . $index]['href']
            = $view_details_link . '&absolute_dl=1&file_id=' . ($index + 1);
      }
   }


   // just show link for viewing all contents
   //
   else
   {
      $args[1]['tnef_decoder']['href'] = $view_details_link;
      $args[1]['tnef_decoder']['text'] = sprintf(_("View Attachments (%d)"), $number_of_attachments);
   }


   $args[6] = $view_details_link;
   if ($tnef_remove_winmail_download_link)
   {
      unset($args[1]['download link']);
      if (!check_sm_version(1, 4, 22) && sizeof($args[1] < 2))
         $args[1]['ignore'] = array();
   }

   sq_change_text_domain($old_text_domain);

}



/**
  * Initialize this plugin (load config values)
  *
  * @return boolean FALSE if no configuration file could be loaded, TRUE otherwise
  *
  */
function tnef_decoder_init()
{

   if (!@include_once(SM_PATH . 'config/config_tnef_decoder.php'))
      if (!@include_once(SM_PATH . 'plugins/tnef_decoder/config.php'))
         if (!@include_once(SM_PATH . 'plugins/tnef_decoder/config_default.php'))
            return FALSE;


   return TRUE;

}



/**
  * Debugging output --> to a file (/tmp/squirrelmail_tnef_decoder.log)
  *
  * Note this assumes a world-writable /tmp directory
  *
  * @param string $string The text to be logged
  *
  * @return boolean TRUE on success, FALSE when a problem occurred
  *
  */
function tnef_log($string)
{
   return error_log($string . "\n", 3, '/tmp/squirrelmail_tnef_decoder.log');
}




/**
  * Determines MIME type by file extension
  *
  * @param string $extension The given file extension
  *
  */
function extension_to_mime($extension)
{
   global $file_extension_to_mime_type_map;
   if (empty($file_extension_to_mime_type_map))
   {
      include_once('file_extension_to_mime_type_map.php');
      $file_extension_to_mime_type_map = get_file_extension_to_mime_type_map();
   }

   if ($extension{0} == '.') $extension = substr($extension, 1);

   if (!empty($file_extension_to_mime_type_map[$extension]))
      return $file_extension_to_mime_type_map[$extension];

   //FIXME: return "application/octet-stream"?  return empty string?  what??
   else
      //return 'application/octet-stream';
      return '';
}



/**
  * TNEF decoding helper function
  *
  */
function tnef_getx($size, &$buf)
{
   $value = NULL;
   $len = strlen($buf);
   if ($len >= $size)
   {
      $value = substr($buf, 0, $size);
      $buf = substr_replace($buf, '', 0, $size);
   }
   else
      substr_replace($buf, '', 0, $len);

   return $value;
}



/**
  * TNEF decoding helper function
  *
  */
function tnef_geti8(&$buf)
{
   $value = NULL;
   $len = strlen($buf);
   if ($len >= 1)
   {
      $value = ord($buf{0});
      $buf = substr_replace($buf, '', 0, 1);
   }
   else
      substr_replace($buf, '', 0, $len);

   return $value;
}



/**
  * TNEF decoding helper function
  *
  */
function tnef_geti16(&$buf)
{
   $value = NULL;
   $len = strlen($buf);
   if ($len >= 2)
   {
      $value = ord($buf{0})
             + (ord($buf{1}) << 8);
      $buf = substr_replace($buf, '', 0, 2);
   }
   else
      substr_replace($buf, '', 0, $len);

   return $value;
}



/**
  * TNEF decoding helper function
  *
  */
function tnef_geti32(&$buf)
{
   $value = NULL;
   $len = strlen($buf);
   if ($len >= 4)
   {
      $value = ord($buf{0})
             + (ord($buf{1}) << 8)
             + (ord($buf{2}) << 16)
             + (ord($buf{3}) << 24);
      $buf = substr_replace($buf, '', 0, 4);
   }
   else
      substr_replace($buf, '', 0, $len);

   return $value;
}



/**
  * Convert strings encoded in TNEF Unicode (UTF-16)
  * to the user's current character set (also removes
  * last character (should be null byte))
  *
  * @param string $string The string to be converted
  *
  * @return string The converted string
  *
  */
function convert_unicode($string)
{

   //
   // SquirrelMail internal decoding routines don't support UTF-16, so roll our own
   //
   global $default_charset;
   $current_character_set = 'utf-16';


   // try iconv, which seems to do the best job
   //
   if (function_exists('iconv')
    && ($ret = iconv($current_character_set, $default_charset, $string)) !== FALSE)
      return substr($ret, 0, -1);


   // try recode
   //
   if (function_exists('recode_string')
    && ($ret = recode_string($current_character_set . '..' . $default_charset, $string)) !== FALSE)
      return substr($ret, 0, -1);


   // try mbstring (if it supports the needed character encodings)
   //
   if (function_exists('mb_convert_encoding'))
   {
      $encodings = sq_mb_list_encodings();
      if (in_array(strtolower($current_character_set), $encodings)
       && in_array(strtolower($default_charset), $encodings))
         return substr(mb_convert_encoding($string, $default_charset, $current_character_set), 0, -1);
   }


   // fall back to internal SquirrelMail routines,
   // which should just return the string as is
   //
   return substr(charset_convert($current_character_set, $string, $default_charset, FALSE), 0, -1);

}



/**
  * Convert strings encoded in certain Windows code
  * pages to the user's current character set
  *
  * @param string $string The string to be converted
  * @param string $code_page The name of the code page from which to convert
  *
  * @return string The converted string (or unconverted, if the code page is not supported)
  *
  */
function convert_windows_code_page($string, $code_page)
{

   if (empty($code_page))
      return $string; 


   // do we support the code page?
   //
   if (file_exists(SM_PATH . 'functions/decode/cp' . $code_page . '.php'))
      $code_page = 'cp' . $code_page;


//FIXME: fall back to iso-8859-1 for unknown code pages?  or just bail?
   // fall back to iso-8859-1 if we don't know about this code page
   //
   else
      $code_page = 'iso-8859-1';


   // convert character set
   //
   global $default_charset;
   $string = charset_convert($code_page, $string, $default_charset, FALSE);
   return $string; 

}



