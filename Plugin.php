<?php namespace Creations\PayPal;

use Backend;
use System\Classes\PluginBase;

/**
 * PayPal Plugin Information File
 */
class Plugin extends PluginBase
{

    /**
     * Returns information about this plugin.
     *
     * @return array
     */
    public function pluginDetails()
    {
        return [
            'name'        => 'PayPal',
            'description' => 'No description provided yet...',
            'author'      => 'creations',
            'icon'        => 'icon-leaf'
        ];
    }

    /**
     * Registers any front-end components implemented in this plugin.
     *
     * @return array
     */
    public function registerComponents()
    {
        return [
            'Creations\PayPal\Components\PayPalPayments' => 'PayPalPayments',
        ];
    }

    /**
     * Registers any back-end permissions used by this plugin.
     *
     * @return array
     */
    public function registerPermissions()
    {
        return []; // Remove this line to activate

        return [
            'creations.paypal.some_permission' => [
                'tab' => 'PayPal',
                'label' => 'Some permission'
            ],
        ];
    }

    /**
     * Registers back-end navigation items for this plugin.
     *
     * @return array
     */
    public function registerNavigation()
    {
        return [
            'paypal' => [
                'label'       => 'PayPal',
                'url'         => Backend::url('creations/paypal/payments'),
                'icon'        => 'icon-paypal',
                'permissions' => ['creations.paypal.*'],
                'order'       => 500,
                'sideMenu' => [
                    'payments' => [
                        'label'       => 'Payments',
                        'icon'        => 'icon-credit-card',
                        'url'         => Backend::url('creations/paypal/payments'),
                        'permissions' => ['creations.paypal.*'],
                    ],
                    'plans' => [
                        'label'       => 'Plans',
                        'icon'        => 'icon-file-text',
                        'url'         => Backend::url('creations/paypal/plans'),
                        'permissions' => ['creations.paypal.*'],
                    ],
            ],
          ],
        ];
    }

}
