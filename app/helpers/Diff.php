<?php

/**
 * Copyright 2000-2017 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author  Chuck Hagenbuch <chuck@horde.org>
 * @package Chora
 */
class Chora_Diff_Helper extends Horde_View_Helper_Base
{
    /**
     * @var array
     */
    protected $_context = [];

    /**
     * @var array
     */
    protected $_leftLines = [];
    protected $_rightLines = [];
    protected $_leftNumbers = [];
    protected $_rightNumbers = [];

    /**
     * @var integer
     */
    protected $_leftLine = 0;
    protected $_rightLine = 0;

    public function diff(Horde_Vcs_File $file, $r1, $r2, $id = null)
    {
        try {
            $diff = $GLOBALS['VC']->diff($file, $r1, $r2, ['human' => true]);
        } catch (Horde_Vcs_Exception $e) {
            return '<div class="diff"><p>' . sprintf(_("There was an error generating the diff: %s"), $e->getMessage()) . '</p></div>';
        }

        $this->_leftLines = [];
        $this->_rightLines = [];
        $this->_leftNumbers = [];
        $this->_rightNumbers = [];

        $firstSection = true;
        foreach ($diff as $section) {
            if (!$firstSection) {
                $this->_leftLines[] = ['type' => 'separator', 'lines' => ['']];
                $this->_rightLines[] = ['type' => 'separator', 'lines' => ['']];
                $this->_leftNumbers[] = '…';
                $this->_rightNumbers[] = '…';
            }
            $firstSection = false;

            $this->_leftLine = (int) $section['oldline'];
            $this->_rightLine = (int) $section['newline'];

            foreach ($section['contents'] as $change) {
                if ($this->hasContext() && $change['type'] != 'empty') {
                    $this->diffContext();
                }

                $method = 'diff' . ucfirst($change['type']);
                $this->$method($change);
            }

            if ($this->hasContext()) {
                $this->diffContext();
            }
        }

        return $this->render('app/views/diff/diff.html.php', [
            'leftLines' => $this->_leftLines,
            'rightLines' => $this->_rightLines,
            'leftNumbers' => $this->_leftNumbers,
            'rightNumbers' => $this->_rightNumbers,
            'file' => $file,
            'r1' => $r1,
            'r2' => $r2,
            'id' => $id,
        ]);
    }

    public function diffAdd($change)
    {
        $leftSection = [];
        $rightSection = [];
        foreach ($change['lines'] as $addedLine) {
            $leftSection[] = '';
            $rightSection[] = $addedLine;
            $this->_leftNumbers[] = '';
            $this->_rightNumbers[] = $this->_rightLine++;
        }
        $this->_leftLines[] = ['type' => 'added-empty', 'lines' => $leftSection];
        $this->_rightLines[] = ['type' => 'added', 'lines' => $rightSection];
        /*
        return $this->render('app/views/diff/added.html.php', array(
            'lines' => $change['lines'],
        ));
        */
    }

    public function diffRemove($change)
    {
        $leftSection = [];
        $rightSection = [];
        foreach ($change['lines'] as $removedLine) {
            $leftSection[] = $removedLine;
            $rightSection[] = '';
            $this->_leftNumbers[] = $this->_leftLine++;
            $this->_rightNumbers[] = '';
        }
        $this->_leftLines[] = ['type' => 'removed', 'lines' => $leftSection];
        $this->_rightLines[] = ['type' => 'removed-empty', 'lines' => $rightSection];
        /*
        return $this->render('app/views/diff/removed.html.php', array(
            'lines' => $change['lines'],
        ));
        */
    }

    public function diffEmpty($change)
    {
        $this->_context[] = ['left' => $this->_leftLine++, 'right' => $this->_rightLine++, 'text' => $change['line']];
        return '';
    }

    public function diffChange($change)
    {
        $leftSection = [];
        $rightSection = [];

        // Pop the old/new stacks one by one, until both are empty.
        $oldsize = count($change['old']);
        $newsize = count($change['new']);
        for ($row = 0, $rowMax = max($oldsize, $newsize); $row < $rowMax; ++$row) {
            if (isset($change['old'][$row])) {
                $leftSection[] = $change['old'][$row];
                $this->_leftNumbers[] = $this->_leftLine++;
            } else {
                $leftSection[] = '';
                $this->_leftNumbers[] = '';
            }

            if (isset($change['new'][$row])) {
                $rightSection[] = $change['new'][$row];
                $this->_rightNumbers[] = $this->_rightLine++;
            } else {
                $rightSection[] = '';
                $this->_rightNumbers[] = '';
            }
        }
        $this->_leftLines[] = ['type' => 'modified', 'lines' => $leftSection];
        $this->_rightLines[] = ['type' => 'modified', 'lines' => $rightSection];
        /*
        return $this->render('app/views/diff/change.html.php', array(
            'left' => $left,
            'right' => $right,
            'oldsize' => $oldsize,
            'newsize' => $newsize,
            'row' => $row,
        ));
        */
    }

    public function hasContext()
    {
        return !empty($this->_context);
    }

    public function diffContext()
    {
        $context = $this->_context;
        $this->_context = [];

        $leftSection = [];
        $rightSection = [];
        foreach ($context as $contextLine) {
            $leftSection[] = $contextLine['text'];
            $rightSection[] = $contextLine['text'];
            $this->_leftNumbers[] = $contextLine['left'];
            $this->_rightNumbers[] = $contextLine['right'];
        }
        $this->_leftLines[] = ['type' => 'unmodified', 'lines' => $leftSection];
        $this->_rightLines[] = ['type' => 'unmodified', 'lines' => $rightSection];
        /*
        return $this->render('app/views/diff/context.html.php', array(
            'context' => $context,
        ));
        */
    }

    public function diffCaption()
    {
        return $this->render('app/views/diff/caption.html.php');
    }
}
