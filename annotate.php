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

/* Spawn the file object. */
try {
    $fl = $VC->getFileObject($where);
} catch (Horde_Vcs_Exception $e) {
    Chora::fatal($e);
}

/* Retrieve the desired revision from the GET variable. */
$rev = Horde_Util::getFormData('rev');
if (!$rev || !$VC->isValidRevision($rev)) {
    Chora::fatal(sprintf(_("Revision %s not found"), $rev ? $rev : _("NONE")), '404 Not Found');
}

switch (Horde_Util::getFormData('actionID')) {
case 'log':
    $log = $fl->queryLogs($rev);
    if (!is_null($log)) {
        echo '<em>' . _("Author") . ':</em> ' . Chora::showAuthorName($log->queryAuthor(), true) . '<br />' .
            '<em>' . _("Date") . ':</em> ' . Chora::formatDate($log->queryDate()) . '<br /><br />' .
            Chora::formatLogMessage($log->queryLog());
    }
    exit;
}

try {
    $lines = $VC->annotate($fl, $rev);
} catch (Horde_Vcs_Exception $e) {
    Chora::fatal($e);
}

$title = sprintf(_("Source Annotation of %s (revision %s)"), Horde_Text_Filter::filter($where, 'space2html', array('charset' => Horde_Nls::getCharset(), 'encode' => true, 'encode_all' => true)), $rev);
$extraLink = sprintf('<a href="%s">%s</a> | <a href="%s">%s</a>',
                     Chora::url('co', $where, array('r' => $rev)), _("View"),
                     Chora::url('co', $where, array('r' => $rev, 'p' => 1)), _("Download"));

Horde::addScriptFile('prototype.js', 'chora', true);
Horde::addScriptFile('annotate.js', 'chora', true);

$js_vars = array(
    'ANNOTATE_URL' => Horde_Util::addParameter(Horde::applicationUrl('annotate.php'), array('actionID' => 'log', 'f' => $where, 'rev' => ''), null, false),
    'loading_text' => _("Loading...")
);

require CHORA_TEMPLATES . '/common-header.inc';
require CHORA_TEMPLATES . '/menu.inc';
require CHORA_TEMPLATES . '/headerbar.inc';
require CHORA_TEMPLATES . '/annotate/header.inc';

$author = '';
$style = 0;

while (list(,$line) = each($lines)) {
    $lineno = $line['lineno'];
    $author = Chora::showAuthorName($line['author']);
    $prevRev = $rev;
    $rev = $line['rev'];
    if ($prevRev != $rev) {
        $style = (++$style % 2);
    }
    $prev = $fl->queryPreviousRevision($rev);

    $line = Horde_Text_Filter::filter($line['line'], 'space2html', array('charset' => Horde_Nls::getCharset(), 'encode' => true, 'encode_all' => true));
    include CHORA_TEMPLATES . '/annotate/line.inc';
}

require $registry->get('templates', 'horde') . '/common-footer.inc';
