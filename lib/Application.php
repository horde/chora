<?php
/**
 * Chora application API.
 *
 * This file defines Chora's external API interface. Other applications can
 * interact with Chora through this API.
 *
 * @package Chora
 */
class Chora_Application extends Horde_Registry_Application
{
    /**
     * The application's version.
     *
     * @var string
     */
    public $version = 'H4 (3.0-git)';

    /**
     * Generate the menu to use on the prefs page.
     *
     * @return Horde_Menu  A Horde_Menu object.
     */
    public function prefsMenu()
    {
        return Chora::getMenu();
    }

}