<?php
/**
 * Created by PhpStorm.
 * User: lolo
 * Date: 01.10.17
 * Time: 11:40
 */
require_once 'tnef_decoder/constants.php';
require_once 'tnef_decoder/functions.php';
require_once 'tnef_decoder/tnef_mailinfo.php';
require_once 'tnef_decoder/tnef_attachment.php';
require_once 'tnef_decoder/tnef_file_base.php';
require_once 'tnef_decoder/tnef_file.php';
require_once 'tnef_decoder/tnef_date.php';
require_once 'tnef_decoder/tnef_file_rtf.php';

$filename = 'data/winmail.dat';
$handle = fopen($filename, "r");
$contents = fread($handle, filesize($filename));


$attach = new TnefAttachment(true);
$attach->decodeTnef($contents);

$tnef_files = $attach->getFilesNested();

var_dump($tnef_files);