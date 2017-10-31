# PayPal

Create webconfig.php on config folder
============================================	

	
	Add your PayPal details into webconfig.php file
	=========================================================
	<?php

		return [
		    'PAYPAL_API_CLIENT_ID' => '',
		    'PAYPAL_API_SECRET' => ''
		];

	?>




Use PayPal Payment Form
===============================================
	
	Add [PayPalPayments] into view file at the top of page param.

	Example:
	===================
		title = "PayPal Form"
		url = "/paypal"
		layout = "default"

		[PayPalPayments]
		==

	Create donation ammounts array
	========================================================
		<?php
			function onStart()
			{
			  $donationAmount = [
					[
					  'amount' => '50',
					],
					[
					  'amount' => '100',
					],
										
					[
					  'amount' => '500',
					],

				      ];

				    $this['donationAmount'] = $donationAmount;
			}
		?>
		==



	Complete Page Code
	=============================================================
		title = "PayPal Form"
		url = "/paypal"
		layout = "default"

		[PayPalPayments]
		==

		<?php
			function onStart()
			{
			  $donationAmount = [
					[
					  'amount' => '50',
					],
					[
					  'amount' => '100',
					],
										
					[
					  'amount' => '500',
					],

				      ];

				    $this['donationAmount'] = $donationAmount;
			}
		?>
		==


	Use Component
	===================================
	{% component 'PayPalPayments' %}

	Use component into div or section where you want to append PayPal form

