<?php

/**
 * DokuWiki Plugin menuext (Action Component)
 *
 * @license GPL 2 http://www.gnu.org/licenses/gpl-2.0.html
 * @author  Andreas Gohr <andi@splitbrain.org>
 */

use dokuwiki\Extension\ActionPlugin;
use dokuwiki\Extension\EventHandler;
use dokuwiki\Extension\Event;
use dokuwiki\plugin\menuext\MenuExtItem;

class action_plugin_menuext extends ActionPlugin
{
    protected $menuconf = [];

    /**
     * action_plugin_menuext constructor.
     */
    public function __construct()
    {
        $cf = DOKU_CONF . 'menuext.json';
        if (file_exists($cf)) {
            $config = @json_decode(file_get_contents($cf), true);
            if (!is_array($config)) {
                msg('Failed to parse config for MenuExt plugin in conf/menuext.json', -1, '', '', MSG_ADMINS_ONLY);
            }
        } else {
            msg('No config for MenuExt plugin found in conf/menuext.json', -1, '', '', MSG_ADMINS_ONLY);
        }
        $this->menuconf = $config;
    }

    /**
     * Registers a callback function for a given event
     *
     * @param EventHandler $controller DokuWiki's event controller object
     *
     * @return void
     */
    public function register(EventHandler $controller)
    {
        $controller->register_hook('MENU_ITEMS_ASSEMBLY', 'AFTER', $this, 'handle_menu_items_assembly', [], 999);
    }

    /**
     * [Custom event handler which performs action]
     *
     * Called for event:
     *
     * @param Event $event event object by reference
     * @param mixed $param [the parameters passed as fifth argument to register_hook() when this
     *                           handler was registered]
     *
     * @return void
     */
    public function handle_menu_items_assembly(Event $event, $param)
    {
        $view = $event->data['view'];
        if (!isset($this->menuconf[$view])) return;

        foreach ($this->menuconf[$view] as $data) {
            $order = isset($data['order']) ? (int) $data['order'] : count($event->data['items']);

            // default action or custom one?
            if (isset($data['action'])) {
                try {
                    $action = ucfirst(strtolower($data['action']));
                    $class = "\\dokuwiki\\Menu\\Item\\$action";
                    if (!class_exists($class)) {
                        msg('No menu item for action ' . hsc($action), -1, '', '', MSG_ADMINS_ONLY);
                        continue;
                    }
                    $item = new $class();
                } catch (\RuntimeException $e) {
                    // just ignore action exceptions, probably caused by insufficient permissions
                    continue;
                }
            } elseif (isset($data['classname'])) {
                $class = $data['classname'];
                if (!class_exists($class)) {
                    msg('Item class ' . hsc($class) . ' does not exist', -1, '', '', MSG_ADMINS_ONLY);
                    continue;
                }
                $item = new $class();
                if (!is_a($item, 'dokuwiki\Menu\Item\AbstractItem')) {
                    msg('Not a menu item: ' . hsc($class), -1, '', '', MSG_ADMINS_ONLY);
                    unset($item);
                    continue;
                }
            } else {
                $item = new MenuExtItem($data);
            }

            array_splice($event->data['items'], $order, 0, [$item]);
        }
    }
}
