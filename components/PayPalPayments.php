<?php namespace Creations\PayPal\Components;

use Cms\Classes\ComponentBase;

use Request;
use Mail;
use Session;
use Redirect;
use Config;

use Creations\PayPal\Models\Payment as PayPalPayment;
use Creations\PayPal\Models\Plan  as PayPalPlan;

use PayPal\Rest\ApiContext;
use PayPal\Auth\OAuthTokenCredential;

use PayPal\Api\ChargeModel;
use PayPal\Api\Currency;
use PayPal\Api\MerchantPreferences;
use PayPal\Api\PaymentDefinition;
use PayPal\Api\Plan;
use PayPal\Common\PayPalModel;

use PayPal\Api\Patch;
use PayPal\Api\PatchRequest;

use PayPal\Api\Agreement;
use PayPal\Api\AgreementStateDescriptor;

use PayPal\Api\Payer;
use PayPal\Api\Amount;
use PayPal\Api\Transaction;
use PayPal\Api\RedirectUrls;
use PayPal\Api\Payment;
use PayPal\Api\Item;
use PayPal\Api\PaymentExecution;

use PayPal\Api\ShippingAddress;

use PayPal\Api\Webhook;
use PayPal\Api\WebhookEventType;

use PayPal\Exception\PayPalConnectionException;



class PayPalPayments extends ComponentBase
{

    public $apiContext;
    protected $request, $amount, $plan, $subscription, $error, $interval;

    public function componentDetails()
    {
        return [
            'name'        => 'PayPal Payments Component',
            'description' => 'Pay with PayPal'
        ];
    }

    public function defineProperties()
    {
        return [];
    }

    public function onRun()
    {
        if(isset($_REQUEST['paymentId'])){
            $this->executePayment((object) Request::all());
            return redirect($_SERVER['REDIRECT_URL']);
        }
        elseif(isset($_REQUEST['token']) && !isset($_REQUEST['paymentId'])){
            $this->executeAgreement($_REQUEST['token']);
            return redirect($_SERVER['REDIRECT_URL']);
        }

        $this->addJs('/plugins/creations/paypal/assets/js/paypal_payments.js');

    }

    public function onHandleForm()
    {
      $this->request =(object) Request::all();
      $amt = 0;

      if($this->request->donationAmountOther && is_numeric($this->request->donationAmountOther))
        $amt = $this->request->donationAmountOther;
      elseif(isset($this->request->donationAmountSelected) && is_numeric($this->request->donationAmountSelected))
        $amt = $this->request->donationAmountSelected;
      else{
        Session::flash('error', 'Please select or add donation amount!');
        return back();
      }

      $this->amount = $this->page['amount'] = $amt;
      $this->interval = isset($this->request->donation_frequency)? $this->request->donation_frequency : '';

      Session::put('payment_data', $this->request);
      Session::put('payment_amount', $this->amount);

      if($this->interval){

        $checkPlan = PayPalPlan::where(['amount' => $this->amount, 'currency' => 'USD', 'frequency' => $this->interval])->first();

        if($checkPlan){
            $plan = $this->getPlan($checkPlan->plan_id);
            // $this->activatePlan($plan,'ACTIVE');

            $approveUrl = $this->createAgreement($plan);

            $payUrl =  $approveUrl;
        }
        else{
            $planDetails = (object) [
                                        'title' => 'Donation $'.$this->amount,
                                        'description' => 'Monthly donation plan of '.$this->amount,
                                        'frequency' => $this->interval,
                                        'frequencyInterval' => 1,
                                        'price' => $this->amount,
                                        'price_currency' => 'USD',
                                        'returnUrl' => url($_SERVER['REDIRECT_URL']),
                                        'cancelUrl' => url($_SERVER['REDIRECT_URL']),
                                    ];

            $payUrl =  $this->createPlan($planDetails);
        }
      }
      else
        $payUrl =  $this->singlePayment();


      return redirect($payUrl);
    }

    private function getPlan($id)
    {
      try {
        $plan = Plan::get($id, $this->apiContext());

      } catch (Exception $ex) {
        echo $ex->getMessage(); die;
      }
      return $plan;
    }

    private function apiContext()
    {
        return new ApiContext(
                new OAuthTokenCredential(
                    Config::get('webconfig.PAYPAL_API_CLIENT_ID'),     // ClientID
                    Config::get('webconfig.PAYPAL_API_SECRET')      // ClientSecret
                )
        );

    }

    private function singlePayment ()
    {
        $apiContext = $this->apiContext();

        $payer = new Payer();
        $payer->setPaymentMethod("paypal");


        $item1 = new Item();
        $item1->setName('Donation')
            ->setCurrency('USD')
            ->setQuantity(1)
            // ->setSku("123123") // Similar to `item_number` in Classic API
            ->setPrice($this->amount);


        $amount = new Amount();
        $amount->setTotal($this->amount);
        $amount->setCurrency('USD');

        $transaction = new Transaction();
        $transaction->setAmount($amount);

        $redirectUrls = new RedirectUrls();
        $redirectUrls->setReturnUrl(url($_SERVER['REDIRECT_URL']))
                     ->setCancelUrl(url($_SERVER['REDIRECT_URL']));

         $payment = new Payment();
         $payment->setIntent('sale')
                 ->setPayer($payer)
                 ->setTransactions(array($transaction))
                 ->setRedirectUrls($redirectUrls);


         try {
              $payment->create($apiContext);

             return $payment->getApprovalLink();
         }
         catch (\PayPal\Exception\PayPalConnectionException $ex) {
             // This will print the detailed information on the exception.
             //REALLY HELPFUL FOR DEBUGGING
             echo $ex->getData();

             echo $ex->getMessage(); die;
         }


    }

    private function executePayment($request)
    {
        $paymentdata = Session::get('payment_data');
        $amt = Session::get('payment_amount');


        $paymentId = $request->paymentId;
        $payment = Payment::get($paymentId, $this->apiContext());

        $execution = new PaymentExecution();
        $execution->setPayerId($request->PayerID);

        $transaction = new Transaction();
        $amount = new Amount();

        $amount->setCurrency('USD');
        $amount->setTotal($amt);
        // $amount->setDetails('Donation');
        $transaction->setAmount($amount);
        $execution->addTransaction($transaction);


        try {
            $result = $payment->execute($execution, $this->apiContext());
            try {
                $payment = Payment::get($paymentId, $this->apiContext());
            } catch (Exception $ex) {
                echo $ex->getMessage(); die;
            }
        } catch (Exception $ex) {
            echo $ex->getMessage(); die;
        }

        $this->storePaymentDetails($payment);
    }

    private function storePaymentDetails($payment, $type=null)
    {
        $successMess = ' donation of <b>$'.Session::get('payment_amount').'</b>';

        $payment_data = Session::get('payment_data');

        $phone = ($payment_data && $payment_data->phone)?$payment_data->phone:$payment->payer->payer_info->phone;

        $data = [
                  'payment_id' => $payment->id,
                  'first_name' => ($payment_data && $payment_data->first_name)?$payment_data->first_name:$payment->payer->payer_info->first_name,
                  'last_name' => ($payment_data && $payment_data->last_name)?$payment_data->last_name:$payment->payer->payer_info->last_name,
                  'payer_id' => $payment->payer->payer_info->payer_id,
                  'email' => ($payment_data && $payment_data->email)?$payment_data->email:$payment->payer->payer_info->email,
                  'phone' => ($phone)?$phone:'',
                  'country_code' => $payment->payer->payer_info->country_code,
                  'amount' => Session::get('payment_amount'),
                ];

        if($type == 'agmnt'){
          $data['plan'] = Session::get('planId');
          $data['country_code'] = $payment->payer->payer_info->shipping_address->country_code;
          $successMess = ' Monthly donation of <b>$'.Session::get('payment_amount').'</b>';
        }
        else
          $data['plan'] = '-';

        try{
          PayPalPayment::create($data);
        }
        catch(Exception $e){
          echo $e->getMessage(); die;
        }

        Session::forget('payment_amount');
        Session::forget('payment_data');
        Session::forget('planId');

        Session::flash('success-donate', $successMess);

        return;
    }

    private function createPlan($details)
    {
      $plan = new Plan();
      $plan->setName($details->title)->setDescription($details->description)->setType('fixed');

      $paymentDefinition = new PaymentDefinition();
      $paymentDefinition->setName('Regular Payments')
                        ->setType('REGULAR')
                        ->setFrequency($details->frequency)
                        ->setFrequencyInterval($details->frequencyInterval)
                        ->setCycles('12')
                        ->setAmount(new Currency(array('value' => $details->price, 'currency' => $details->price_currency)));


      $merchantPreferences = new MerchantPreferences();
      $merchantPreferences->setReturnUrl($details->returnUrl)
                          ->setCancelUrl($details->cancelUrl)
                          ->setAutoBillAmount("yes")
                          ->setInitialFailAmountAction("CONTINUE")
                          ->setMaxFailAttempts("0");

      $plan->setPaymentDefinitions(array($paymentDefinition));
      $plan->setMerchantPreferences($merchantPreferences);

      $request = clone $plan;

      try {
        $output = $plan->create($this->apiContext());
        try{
          $this->activatePlan($plan,'ACTIVE');
        }
        catch(Exception $ex){
          echo $ex->getMessage(); die;
        }

        $approveUrl = $this->createAgreement($plan);
        return $approveUrl;

      } catch (Exception $ex) {
         echo $ex->getMessage(); die;
      }
    }

    private function storePlan($plan)
    {
        $data = [
                    'plan_id' => $plan->getId(),
                    'name' => $plan->name,
                    'type' => $plan->payment_definitions[0]->type,
                    'frequency' => $plan->payment_definitions[0]->frequency,
                    'amount' => $plan->payment_definitions[0]->amount->value,
                    'currency' => $plan->payment_definitions[0]->amount->currency,
                    'cycles' => $plan->payment_definitions[0]->cycles,
                    'frequency_interval' => $plan->payment_definitions[0]->frequency_interval,
                ];

        try{
          PayPalPlan::create($data);
        }
        catch(Exception $ex){
          echo $ex->getMessage();
        }

    }

    private function activatePlan($plan,$state)
    {
      try {
          $patch = new Patch();

          $value = new PayPalModel('{
                 "state":"'.$state.'"
               }');

          $patch->setOp('replace')
                ->setPath('/')
                ->setValue($value);

          $patchRequest = new PatchRequest();
          $patchRequest->addPatch($patch);

          $plan->update($patchRequest, $this->apiContext());

          $updatedPlan =  Plan::get($plan->getId(), $this->apiContext());

          $this->storePlan($plan);

      } catch (Exception $ex) {
         echo $ex->getMessage(); die;
      }

    }

    private function createAgreement($plan)
    {
      Session::put('planId', $plan->getId());

      $date = rtrim(date('c', strtotime('+5 minutes', time())),'+00:00').'Z';

      $agreement = new Agreement();
      $agreement->setName($plan->name)
                ->setDescription($plan->description)
                ->setStartDate($date);

      $newPlan = new Plan();
      $newPlan->setId($plan->id);
      $agreement->setPlan($newPlan);

      $payer = new Payer();
      $payer->setPaymentMethod('paypal');
      $agreement->setPayer($payer);

      $request = clone $agreement;

      try {
        $agreement = $agreement->create($this->apiContext());

        return $agreement->getApprovalLink();

      }
      catch (Exception $ex) {
          echo $ex->getMessage(); die;
      }
    }

    private function executeAgreement($token)
    {
        $agreement = new Agreement();
        try {
            $agreement->execute($token, $this->apiContext());

            $this->storePaymentDetails($agreement, 'agmnt');

            return $agreement;
        } catch (Exception $ex) {
            exit(1);
        }
    }

    private function suspendAgreement ($agreement_id)
    {
      $createdAgreement = Agreement::get($agreement_id, $this->apiContext());

      //Create an Agreement State Descriptor, explaining the reason to suspend.
      $agreementStateDescriptor = new AgreementStateDescriptor();
      $agreementStateDescriptor->setNote("Suspending the agreement");

      try {
          $createdAgreement->suspend($agreementStateDescriptor, $this->apiContext());
          $agreement = Agreement::get($createdAgreement->getId(), $this->apiContext());
           \Log::info('#Suspend Agreement - '.$agreement);
          return $agreement;

      }
       catch(PayPalConnectionException $ex){
         exit(1);
       }
       catch (Exception $ex) {
         exit(1);
      }
    }

    private function buyerEmail()
    {
          $intervalTest = '';

          if( $this->interval == 'month')
          $intervalTest = 'Monthly';

          if( $this->interval == '3-month')
          $intervalTest = 'Quarterly';

          if( $this->interval == 'year')
          $intervalTest = 'Annual';

          $data = [
                    'amount'=> $this->amount,
                    'url'=> url(),
                    'date' => date('F d, Y'),
                    'day' => date('jS'),
                    'monthly' => $intervalTest
                  ];


          Mail::send('creations.paypal::mail.buyer_email',$data, function ($message)
          {
           $message->subject('Donation Confirmed');
           $message->to($this->request->email);
          });

    }

}
