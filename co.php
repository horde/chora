<?php
/**
 * Copyright 2000-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author  Anil Madhavapeddy <avsm@horde.org>
 * @package Chora
 */

require_once dirname(__FILE__) . '/lib/base.php';

/* If we know we're at a directory, just go to browse.php. */
if ($atdir) {
    require CHORA_BASE . '/browse.php';
    exit;
}

/* Should we pretty-print this output or not? */
$plain = Util::getFormData('p', 0);

/* Create the VC_File object and populate it. */
$file = $VC->getFileObject($where, array('cache' => $cache));
Chora::checkError($file);

/* Get the revision number. */
$r = Util::getFormData('r');

/* If no revision is specified, default to HEAD.  If a revision is
 * specified, it's safe to cache for a long time. */
if (is_null($r)) {
    $r = $file->queryRevision();
    header('Cache-Control: max-age=60, must-revalidate');
} else {
    header('Cache-Control: max-age=2419200');
}

/* Is this a valid revision being requested? */
if (!$VC->isValidRevision($r)) {
    Chora::fatal(sprintf(_("Revision Not Found: %s is not a valid revision"), $r), '400 Bad Request');
}

/* Retrieve the actual checkout. */
$checkOut = $VC->getCheckout($file, $r);

/* Get the MIME type of the file, or at least our best guess at it. */
$mime_type = Horde_Mime_Magic::filenameToMIME($fullname);
if ($mime_type == 'application/octet-stream') {
    $mime_type = 'text/plain';
}

/* Check error status, and either show error page, or the checkout
 * contents */
Chora::checkError($checkOut);

if (!$plain) {
    /* Pretty-print the checked out copy */
    $pretty = Chora::pretty($mime_type, $checkOut);

    /* Get this revision's attributes in printable form. */
    $log = $file->logs[$r];

    $title = sprintf(_("%s Revision %s (%s ago)"),
                     basename($fullname),
                     $r,
                     Chora::readableTime($log->date, true));
    $extraLink = sprintf('<a href="%s">%s</a> | <a href="%s">%s</a>',
                         Chora::url('annotate', $where, array('rev' => $r)), _("Annotate"),
                         Chora::url('co', $where, array('r' => $r, 'p' => 1)), _("Download"));

    $tags = Chora::getTags($log, $where);
    $branch_info = $log->queryBranch();

    $log_print = Chora::formatLogMessage($log->queryLog());
    $author = Chora::showAuthorName($log->queryAuthor(), true);

    Horde::addScriptFile('prototype.js', 'horde', true);
    Horde::addScriptFile('stripe.js', 'horde', true);
    require CHORA_TEMPLATES . '/common-header.inc';
    require CHORA_TEMPLATES . '/menu.inc';
    require CHORA_TEMPLATES . '/headerbar.inc';
    require CHORA_TEMPLATES . '/checkout/checkout.inc';
    require $registry->get('templates', 'horde') . '/common-footer.inc';
    exit;
}

/* Download the file. */

// Get data.
$content = '';
while ($line = fgets($checkOut)) {
    $content .= $line;
}
fclose($checkOut);

// Get name.
$filename = $file->queryName();
if ($browser->getBrowser() == 'opera') {
    $filename = strtr($filename, ' ', '_');
}

// Send headers.
$browser->downloadHeaders($filename, $mime_type, false, strlen($content));

// Send data.
echo $content;
