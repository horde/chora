<?php
/**
 * Chora application API.
 *
 * This file defines Horde's core API interface. Other core Horde libraries
 * can interact with Chora through this API.
 *
 * Copyright 2010-2017 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @package Chora
 */

if (!defined('CHORA_BASE')) {
    define('CHORA_BASE', realpath(__DIR__ . '/..'));
}

if (!defined('HORDE_BASE')) {
    /* If horde does not live directly under the app directory, the HORDE_BASE
     * constant should be defined in config/horde.local.php. */
    if (file_exists(CHORA_BASE . '/config/horde.local.php')) {
        include CHORA_BASE . '/config/horde.local.php';
    } else {
        define('HORDE_BASE', realpath(CHORA_BASE . '/..'));
    }
}

/* Load the Horde Framework core (needed to autoload
 * Horde_Registry_Application::). */
require_once HORDE_BASE . '/lib/core.php';

class Chora_Application extends Horde_Registry_Application
{
    /**
     */
    public $version = 'H6 (3.0.0-git)';

    /**
     * Global variables defined:
     *   $chora_conf
     *   $sourceroots
     */
    protected function _init()
    {
        global $acts, $conf, $defaultActs, $where, $atdir, $fullname, $sourceroot, $page_output;

        // TODO: If chora isn't fully/properly setup, init() will throw fatal
        // errors. Don't want that if this class is being loaded simply to
        // obtain basic chora application information.
        $initial_app = ($GLOBALS['registry']->initialApp == 'chora');

        try {
            $GLOBALS['sourceroots'] = Horde::loadConfiguration('backends.php', 'sourceroots');
        } catch (Horde_Exception $e) {
            $GLOBALS['sourceroots'] = array();
            if (!$initial_app) {
                return;
            }
            $GLOBALS['notification']->push($e);
        }

        $sourceroots = Chora::sourceroots();

        /**
         * Variables we wish to propagate across web pages
         *  ha  = Hide Attic Files
         *  ord = Sort order
         *  sbt = Sort By Type (name, age, author, etc)
         *
         * Obviously, defaults go into $defaultActs :)
         * TODO: defaults of 1 will not get propagated correctly - avsm
         * XXX: Rewrite this propagation code, since it sucks - avsm
         */
        $defaultActs = $acts = array(
            'onb' => 0,
            'ord' => Horde_Vcs::SORT_ASCENDING,
            'rev' => 0,
            'rt'  => null,
            'sa'  => 0,
            'sbt' => constant($conf['options']['defaultsort']),
            'ws'  => 1,
        );

        /* See if any actions have been passed as form variables, and if so,
         * assign them into the acts array. */
        $vars = Horde_Variables::getDefaultVariables();
        foreach (array_keys($acts) as $key) {
            if (isset($vars->$key)) {
                $acts[$key] = $vars->$key;
            }
        }

        /* Use the value of the 'rt' form value for the sourceroot. If not
         * present, use the last sourceroot used as the default value if the
         * user has that preference. Otherwise, use default sourceroot. */
        $last_sourceroot = $GLOBALS['prefs']->getValue('last_sourceroot');
        if (is_null($acts['rt'])) {
            if (!empty($last_sourceroot) &&
                !empty($sourceroots[$last_sourceroot]) &&
                is_array($sourceroots[$last_sourceroot])) {
                $acts['rt'] = $last_sourceroot;
            } else {
                foreach ($sourceroots as $key => $val) {
                    if (!isset($acts['rt']) || isset($val['default'])) {
                        $acts['rt'] = $key;
                        break;
                    }
                }

                if (is_null($acts['rt'])) {
                    if ($initial_app) {
                        Chora::fatal(new Chora_Exception(_("No repositories found.")));
                    }
                    return;
                }
            }
        }

        if (!isset($sourceroots[$acts['rt']])) {
            if ($initial_app) {
                Chora::fatal(new Chora_Exception(sprintf(_("The repository with the slug '%s' was not found"), $acts['rt'])));
            }
            return;
        }

        $sourcerootopts = $sourceroots[$acts['rt']];
        $sourceroot = $acts['rt'];

        /* Store last repository viewed */
        if ($acts['rt'] != $last_sourceroot) {
            $GLOBALS['prefs']->setValue('last_sourceroot', $acts['rt']);
        }

        // Cache.
        $cache = empty($conf['caching'])
            ? null
            : $GLOBALS['injector']->getInstance('Horde_Cache');

        $GLOBALS['chora_conf'] = array(
            'cvsusers' => $sourcerootopts['location'] . '/' . (isset($sourcerootopts['cvsusers']) ? $sourcerootopts['cvsusers'] : ''),
            'introText' => CHORA_BASE . '/config/' . (isset($sourcerootopts['intro']) ? $sourcerootopts['intro'] : ''),
            'introTitle' => (isset($sourcerootopts['title']) ? $sourcerootopts['title'] : ''),
            'sourceRootName' => $sourcerootopts['name']
        );
        $chora_conf = &$GLOBALS['chora_conf'];

        $GLOBALS['VC'] = Horde_Vcs::factory(Horde_String::ucfirst($sourcerootopts['type']), array(
            'cache' => $cache,
            'sourceroot' => $sourcerootopts['location'],
            'paths' => array_merge($conf['paths'], array('temp' => Horde::getTempDir())),
            'username' => isset($sourcerootopts['username']) ? $sourcerootopts['username'] : '',
            'password' => isset($sourcerootopts['password']) ? $sourcerootopts['password'] : ''
        ));

        if (!$initial_app) {
            return;
        }

        $where = Horde_Util::getFormData('f', '/');

        /* Location relative to the sourceroot. */
        $where = preg_replace(array('|^/|', '|\.\.|'), '', $where);

        $fullname = $sourcerootopts['location'] . (substr($sourcerootopts['location'], -1) == '/' ? '' : '/') . $where;

        if ($sourcerootopts['type'] == 'cvs') {
            $fullname = preg_replace('|/$|', '', $fullname);
            $atdir = @is_dir($fullname);
        } else {
            $atdir = !$where || (substr($where, -1) == '/');
        }
        $where = preg_replace('|/$|', '', $where);

        if (($sourcerootopts['type'] == 'cvs') &&
            !@is_dir($sourcerootopts['location'])) {
            Chora::fatal(new Chora_Exception(_("Sourceroot not found. This could be a misconfiguration by the server administrator, or the server could be having temporary problems. Please try again later.")));
        }

        if (Chora::isRestricted($where)) {
            Chora::fatal(new Chora_Exception(sprintf(_("%s: Forbidden by server configuration"), $where)));
        }
    }

    /**
     */
    public function perms()
    {
        $perms = array(
            'sourceroots' => array(
                'title' => _("Repositories")
            )
        );

        // Run through every source repository
        require __DIR__ . '/../config/backends.php';
        foreach ($sourceroots as $sourceroot => $srconfig) {
            $perms['sourceroots:' . $sourceroot] = array(
                'title' => $srconfig['name']
            );
        }

        return $perms;
    }

    /**
     * Add additional items to the sidebar.
     *
     * @param Horde_View_Sidebar $sidebar  The sidebar object.
     */
    public function sidebar($sidebar)
    {
        foreach (Chora::sourceroots() as $key => $val) {
            $row = array(
                'selected' => $GLOBALS['sourceroot'] == $key,
                'url' => Chora::url('browsedir', '', array('rt' => $key)),
                'label' => $val['name'],
                'type' => 'radiobox',
            );
            $sidebar->addRow($row, 'backends');
        }
    }

    /* Topbar method. */

    /**
     */
    public function topbarCreate(Horde_Tree_Renderer_Base $tree, $parent = null,
                                 array $params = array())
    {
        $sourceroots = Chora::sourceroots();
        asort($sourceroots);

        foreach ($sourceroots as $key => $val) {
            $tree->addNode(array(
                'id' => $parent . $key,
                'parent' => $parent,
                'label' => $val['name'],
                'expanded' => false,
                'params' => array(
                    'icon' => Horde_Themes::img('tree/folder.png'),
                    'url' => Chora::url('browsedir', '', array('rt' => $key))
                )
            ));
        }
    }

}
