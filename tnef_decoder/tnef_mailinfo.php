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

class TnefMailinfo
{

   var $subject;
   var $topic;
   var $topic_is_unicode = FALSE;
   var $from;
   var $from_is_unicode = FALSE;
   var $from_name;
   var $from_name_is_unicode = FALSE;
   var $date_sent;
   var $code_page = '';

   function TnefMailinfo()
   {
   }

   function getTopic()
   {
      if ($this->topic_is_unicode)
         return convert_unicode($this->topic);

      //FIXME: should this be using convert_windows_code_page()??
      return $this->topic;
   }

   function getSubject()
   {
      return $this->subject;
   }

   function getFrom()
   {
      if ($this->from_is_unicode)
         return convert_unicode($this->from);

      //FIXME: should this be using convert_windows_code_page()??
      return $this->from;
   }

   function getCodePage()
   {
      return $this->code_page;
   }

   function getFromName()
   {
      if ($this->from_name_is_unicode)
         return convert_unicode($this->from_name);

      //FIXME: should this be using convert_windows_code_page()??
      return $this->from_name;
   }

   function &getDateSent()
   {
      return $this->date_sent;
   }

   function receiveTnefAttribute($attribute, $value, $length)
   {
      switch($attribute)
      {
         case TNEF_AOEMCODEPAGE:
            $this->code_page = tnef_geti16($value);
            break;

         case TNEF_ASUBJECT:
            $this->subject = substr($value, 0, $length - 1);
            break;

         case TNEF_ADATERECEIVED:
            if (!$this->date_sent)
            {
	       $this->date_sent = & new TnefDate;
	       $this->date_sent->setTnefBuffer($value);
            }

         case TNEF_ADATESENT:
            $this->date_sent = & new TnefDate;
            $this->date_sent->setTnefBuffer($value);
      }
   }

   function receiveMapiAttribute($attr_type, $attr_name, $value, $length, $is_unicode=FALSE)
   {
      switch($attr_name)
      {
         case TNEF_MAPI_CONVERSATION_TOPIC:
            $this->topic = $value;
            if ($is_unicode) $this->topic_is_unicode = TRUE;
            break;

         case TNEF_MAPI_SENT_REP_EMAIL_ADDR:
            $this->from = $value;
            if ($is_unicode) $this->from_is_unicode = TRUE;
            break;

         case TNEF_MAPI_SENT_REP_NAME:
            $this->from_name = $value;
            if ($is_unicode) $this->from_name_is_unicode = TRUE;
            break;
      }
   }

}



