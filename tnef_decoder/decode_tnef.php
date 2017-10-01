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



// set up SquirrelMail environment
//
if (file_exists('../../include/init.php'))
   include_once('../../include/init.php');
else if (file_exists('../../include/validate.php'))
{
   define('SM_PATH', '../../');
   include_once(SM_PATH . 'include/validate.php');
}
else
{
// not compatible with SM version less than 1.4.0
die('Sorry, TNEF Decoder is not compatible with SquirrelMail versions less than 1.4.0');
   chdir('..');
   define('SM_PATH', '../');
   include_once(SM_PATH . 'src/validate.php');
}


// Make sure plugin is activated!
//
global $plugins;
if (!in_array('tnef_decoder', $plugins))
   exit;


// disable browser caching 
//
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: Sat, 1 Jan 2000 00:00:00 GMT');
// FIXME: hmmm, the original author allowed caching... why?
//header("Pragma: ");
//header("Cache-Control: cache");



include_once(SM_PATH . 'functions/imap.php');

// translated strings are in the constants.php file
//
sq_change_text_domain('tnef_decoder');
include_once(SM_PATH . 'plugins/tnef_decoder/constants.php');
sq_change_text_domain('squirrelmail');

include_once(SM_PATH . 'plugins/tnef_decoder/functions.php');
include_once(SM_PATH . 'plugins/tnef_decoder/tnef_attachment.php');
include_once(SM_PATH . 'plugins/tnef_decoder/tnef_mailinfo.php');
include_once(SM_PATH . 'plugins/tnef_decoder/tnef_file_base.php');
include_once(SM_PATH . 'plugins/tnef_decoder/tnef_file_rtf.php');
include_once(SM_PATH . 'plugins/tnef_decoder/tnef_file.php');
include_once(SM_PATH . 'plugins/tnef_decoder/tnef_date.php');
include_once(SM_PATH . 'plugins/tnef_decoder/tnef_vcard.php');



global $tnef_debug, $color, $PHP_SELF;
tnef_decoder_init();



//
// This page request responds with a display of the contents
// of a given TNEF message attachment and provides download
// links for each of those contents
//



sqgetGlobalVar('mailbox', $mailbox, SQ_GET);
sqgetGlobalVar('passed_id', $passed_id, SQ_GET);
sqgetGlobalVar('passed_ent_id', $passed_ent_id, SQ_GET);
sqgetGlobalVar('startMessage', $startMessage, SQ_GET);
sqgetGlobalVar('what', $what, SQ_GET);
sqgetGlobalVar('where', $where, SQ_GET);
if (!sqgetGlobalVar('absolute_dl', $absolute_dl, SQ_GET) || empty($absolute_dl))
   $absolute_dl = 0;



// if a file_id was given, we are downloading the file;
// otherwise, normal decode and display
//
if (!sqgetGlobalVar('file_id', $file_id, SQ_GET) || empty($file_id))
   $tnef_download = FALSE;
else
{
   $tnef_download = TRUE;
   --$file_id;
}



// when downloading, we'll disable debugging
//
$tnef_debug = ((!$tnef_download) && $tnef_debug);
if ($tnef_debug) include_once(SM_PATH . 'plugins/tnef_decoder/debug_functions.php');



// connect to IMAP server and select mailbox
//
global $username, $imapServerAddress, $imapPort;
if (check_sm_version(1, 5, 2))
   $key = FALSE;
else
   sqgetGlobalVar('key', $key, SQ_COOKIE);
$imapConnection = sqimap_login($username, $key, $imapServerAddress, $imapPort, 0);
sqimap_mailbox_select($imapConnection, $mailbox);



// extract the TNEF attachment and decode it
//
$message = sqimap_get_message($imapConnection, $passed_id, $mailbox);
$tnef_entity = getEntity($message, $passed_ent_id);
$tnef = mime_fetch_body($imapConnection, $passed_id, $passed_ent_id); 
$tnef = decodeBody($tnef, $tnef_entity->header->encoding);



// now, dissect the TNEF
//
$attachment = new TnefAttachment($tnef_debug);
$result = $attachment->decodeTnef($tnef);
$tnef_files = $attachment->getFilesNested();



// download the requested file
//
if ($tnef_download)
{

   set_time_limit(0);

   list($type0, $type1) = explode('/', $tnef_files[$file_id]->getType(), 2);

   SendDownloadHeaders($type0, $type1,
                       $tnef_files[$file_id]->getName(),
                       $absolute_dl, $tnef_files[$file_id]->getSize());

   echo($tnef_files[$file_id]->getContent());

}



// display TNEF contents
//
else
{

   $mailinfo = $attachment->getMailinfo();
   $subject = $mailinfo->getSubject();
   $topic = $mailinfo->getTopic();
   $from_mail = $mailinfo->getFrom();
   $from_name = $mailinfo->getFromName();
   $date = $mailinfo->getDateSent();


   displayPageHeader($color, '');
   sq_change_text_domain('tnef_decoder');


   // from a search?
   //
   if (isset($where) && isset($what))
      $message_link = sqm_baseuri() . 'src/read_body.php?mailbox='
                    . urlencode($mailbox) . "&passed_id=$passed_id&startMessage=$startMessage&where="
                    . urlencode($where) . '&what=' . urlencode($what);
   else
      $message_link = sqm_baseuri() . 'src/read_body.php?mailbox='
                    . urlencode($mailbox)
                    . "&passed_id=$passed_id&startMessage=$startMessage";


//FIXME: move HTML into a template file - first step in making this 1.5.2-compatible
?>
<br>
<table width="100%" border="0" cellspacing="0" cellpadding="2" align="center">
  <tr>
    <td bgcolor="<?php echo $color[0]; ?>">
      <strong>
        <center>
          <?php
             echo _("Viewing Attachment Contents - ")
                . '<a href="' . $message_link . '">' . _("View Message") . '</a>';
           ?>
        </center>
      </strong>
    </td>
  </tr>
</table>
<?php

   if (!empty($subject) || !empty($topic) || !empty($from_mail)
    || !empty($from_name) || !empty($date))
   {

      echo '<table width="100%" cellspacing="0" cellpadding="2" border="0" bgcolor="'
         . $color[0] . '"><tr><th align="left" bgcolor="'
         . $color[9] . '"><strong>'
         . _("Attachment Details:")
         . '</strong></th></tr><tr><td>'
         . '<table cellspacing="0" cellpadding="1" border="0">';


      if ($subject)
         echo '<tr><td>&nbsp;&nbsp;</td><td><strong>' . _("Subject:") . '</strong></td><td>' . $subject . '</td></tr>';

      if (($topic) && ($topic != $subject))
         echo '<tr><td>&nbsp;&nbsp;</td><td><strong>' . _("Topic:") . '</strong></td><td>' . $topic . '</td></tr>';

      if ($from_name)
         echo '<tr><td>&nbsp;&nbsp;</td><td><strong>' . _("Name:") . '</strong></td><td>' . $from_name . '</td></tr>';

      if ($from_mail)
         echo '<tr><td>&nbsp;&nbsp;</td><td><strong>' . _("Name:") . '</strong></td><td>' . $from_mail . '</td></tr>';

      if ($date)
         echo '<tr><td>&nbsp;&nbsp;</td><td><strong>' . _("Date:") . '</strong></td><td>' . $date->getString() . '</td></tr>';

      echo "</table></td></tr></table>";

   }


   // show anything but Vcards...
   //
   $output = '<table width="100%" cellspacing="0" cellpadding="2" border="0" bgcolor="'
           . $color[0] . '"><tr><th align="left" bgcolor="'
           . $color[9] . '"><strong>'
           .  _("Attachments:")
           . '</strong></th></tr><tr><td><table cellspacing="0" cellpadding="1" border="0">';

   $show_it = 0;
   $id = 1;
   foreach ($tnef_files as $file)
   {

      if (get_class($file) != "tnefvcard")
      {
         $dl_href = $PHP_SELF . "&file_id=$id";

         $output .= '<tr><td>&nbsp;&nbsp;</td><td><a href="'
                  . $dl_href . '">' . $file->getName()
                  . '</a>&nbsp;</td><td><small><strong>'
                  . show_readable_size($file->getSize())
                  . '</strong>&nbsp;&nbsp;</small></td><td><small>[ '
                  . $file->getType()
                  . ' ]&nbsp;</small></td><td></td><td><small>&nbsp;<a href="'
                  . $dl_href . '&absolute_dl=1">'
                  . _("Download") . '</a></small></td></tr>';

         $show_it = 1;
      }

      $id++;

   }

   $output .= "</table></td></tr></table>";

   if ($show_it > 0)
      echo $output;


   // show Vcards only
   //
   foreach ($tnef_files as $file)
   {

      if (get_class($file) == "tnefvcard")
      {

         $output = '<table width="100%" cellspacing="0" cellpadding="2" border="0" bgcolor="'
                 . $color[0] . '"><tr><th align="left" bgcolor="'
                 . $color[9] . '"><strong>'
                 . sprintf(_("Contact Information For: %s"), $file->getName())
                 . '</strong></th></tr><tr><td><table cellspacing="0" cellpadding="1" border="0">';


         $name = '';

         $value = $file->getGivenName();
         if ($value)
            $name .= $value;

         $value = $file->getMiddleName();
         if ($value)
            $name .= ' ' . $value;

         $value = $file->getSurname();
         if ($value)
            $name .= ' ' . $value;

         if ($name != '')
            $output .= '<tr><td>&nbsp;&nbsp;</td><td><strong>'
                     . _("Name:")
                     . '</strong></td><td>'
                     . $name . '</td></tr>';


         $company = $file->getCompany();
         if ($company)
            $output .= '<tr><td>&nbsp;&nbsp;</td><td><strong>'
                     . _("Company:")
                     . '</strong></td><td>'
                     . $company . '</td></tr>';


         $telefones = $file->getTelefones();
         ksort($telefones);
         foreach ($telefones as $telkey => $telvalue)
            $output .= '<tr><td>&nbsp;&nbsp;</td><td><strong>'
                     . sprintf(_("%s:"), $telkey)
                     . '</strong></td><td>' . $telvalue . '</td></tr>';
         

         $emails = $file->getEmails();
         ksort($emails);
         foreach ($emails as $emailkey => $emailvalue)
         {
            $disp = $emailvalue[EMAIL_DISPLAY];
            if (!$disp)
               $disp = $emailkey;

            $output .= '<tr><td>&nbsp;&nbsp;</td><td><strong>'
                     . sprintf(_("%s:"), $disp)
                     . '</strong></td><td>'
                     . $emailvalue[EMAIL_EMAIL] . '</td></tr>';
            unset($disp);
         }


         $homepages = $file->getHomepages();
         foreach ($homepages as $hpkey => $hpvalue)
            $output .= '<tr><td>&nbsp;&nbsp;</td><td><strong>'
                     . sprintf(_("%s:"), $hpkey)
                     . '</strong></td><td>' . $hpvalue . '</td></tr>';


         $output .= "</table></td></tr></table>";
         echo $output;

      }

   }

?>
<table border="0" cellspacing="0" cellpadding="2" align="center">
  <tr><td bgcolor="<?php echo $color[4] ?>"></td></tr>
</table>
</body></html>

<?php

}



