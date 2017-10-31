$('body').on('click', '#paypalpayment', function(){
  $("#paypal-payment-form").validate({
      errorElement: 'p',
      submitHandler: paypalsubmit,
  });
});


function paypalsubmit(form) {
    $('#paypalpayment').attr("disabled", "disabled");
    $('#paypalpayment').attr('value','Processing Payment...');

    var formData = $.base64.encode($("#payment-form").serializeArray());

    var input = $("<input name='paypal' value='paypal' style='display:none;' />");

    form.appendChild(input[0]);
    form.appendChild(paypal[0]);

    if($('input[name="newslatterSubscribe"]').is(':checked')) {
     $('#newslatter-subscription input[name=email]').attr('value',$('#email').val());
     $.post('https://visitor2.constantcontact.com/api/signup',$('#newslatter-subscription').serialize(),function(data){
         form.submit();
     });
    }
    else{
      form.submit();
    }
}
