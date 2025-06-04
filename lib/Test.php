<?php

/**
 * This class provides the Chora configuration for the test script.
 *
 * Copyright 2010-2017 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author  Michael Slusarz <slusarz@horde.org>
 * @package Chora
 * @coversNothing
 */
class Chora_Test extends Horde_Test
{
    /**
     * The module list
     *
     * @var array
     */
    protected $_moduleList = [];

    /**
     * PHP settings list.
     *
     * @var array
     */
    protected $_settingsList = [];

    /**
     * PEAR modules list.
     *
     * @var array
     */
    protected $_pearList = [];

    /**
     * Required configuration files.
     *
     * @var array
     */
    protected $_fileList = [
        'config/conf.php' => null,
        'config/backends.php' => null,
    ];

    /**
     * Inter-Horde application dependencies.
     *
     * @var array
     */
    protected $_appList = [];

    /**
     * Any application specific tests that need to be done.
     *
     * @return string  HTML output.
     */
    public function appTests() {}

}
