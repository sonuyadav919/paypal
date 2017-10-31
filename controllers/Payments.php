<?php namespace Creations\PayPal\Controllers;

use BackendMenu;
use Backend\Classes\Controller;

use Creations\PayPal\Models\Payment as PaymentModel;
/**
 * Payments Back-end Controller
 */
class Payments extends Controller
{
    public $implement = [
        'Backend.Behaviors.FormController',
        'Backend.Behaviors.ListController'
    ];

    public $formConfig = 'config_form.yaml';
    public $listConfig = 'config_list.yaml';

    public function __construct()
    {
        parent::__construct();

        BackendMenu::setContext('Creations.PayPal', 'paypal', 'payments');
    }

    public function index()
    {
        $this->vars['totalAmount'] = PaymentModel::all()->sum('amount');
        $this->asExtension('ListController')->index();
    }
}
