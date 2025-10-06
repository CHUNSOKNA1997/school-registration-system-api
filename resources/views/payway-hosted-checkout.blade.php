<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=0">
    <title>PayWay Checkout</title>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/2.2.4/jquery.min.js"></script>
</head>
<body style="padding: 0; margin: 0; min-height: 100vh;">
    <div id="aba_main_modal" class="aba-modal section">
        <div class="aba-modal-content container" style="margin: 0; padding: 0">
            <form method="POST" action="{{ $checkoutUrl }}" target="aba_webservice" id="aba_merchant_request" style="max-width: 500px; margin: auto; padding: 10px;">
                <input type="hidden" name="hash" value="{{ $formData['hash'] }}">
                <input type="hidden" name="req_time" value="{{ $formData['req_time'] }}">
                <input type="hidden" name="merchant_id" value="{{ $formData['merchant_id'] }}">
                <input type="hidden" name="tran_id" value="{{ $formData['tran_id'] }}">
                <input type="hidden" name="amount" value="{{ $formData['amount'] }}">
                <input type="hidden" name="items" value="{{ $formData['items'] }}">
                <input type="hidden" name="shipping" value="{{ $formData['shipping'] }}">
                <input type="hidden" name="firstname" value="{{ $formData['firstname'] }}">
                <input type="hidden" name="lastname" value="{{ $formData['lastname'] }}">
                <input type="hidden" name="phone" value="{{ $formData['phone'] }}">
                <input type="hidden" name="email" value="{{ $formData['email'] }}">
                <input type="hidden" name="payment_option" value="{{ $formData['payment_option'] }}">
                <input type="hidden" name="type" value="{{ $formData['type'] }}">
                <input type="hidden" name="return_url" value="{{ $formData['return_url'] }}">
                <input type="hidden" name="continue_success_url" value="{{ $formData['continue_success_url'] }}">
                @if(isset($formData['return_deeplink']))
                <input type="hidden" name="return_deeplink" value="{{ $formData['return_deeplink'] }}">
                @endif
                <input type="hidden" name="currency" value="{{ $formData['currency'] }}">
                <input type="hidden" name="custom_fields" value="{{ $formData['custom_fields'] }}">
                <input type="hidden" name="return_params" value="{{ $formData['return_params'] }}">
                <input type="hidden" name="view_type" value="hosted_view">
            </form>
        </div>
    </div>

    <!-- PayWay Official Plugin -->
    <script src="https://checkout-sandbox.payway.com.kh/plugins/checkout2-0.js"></script>

    <script type="text/javascript">
        $(document).ready(function() {
            // Create iframe for PayWay
            var iframe = $('<iframe scrolling="yes" class="aba-iframe" name="aba_webservice" id="aba_webservice" style="width: 100%; min-height: 95vh; margin: 0; padding: 0;" frameBorder="0"></iframe>');
            $('.aba-modal-content').append(iframe);

            // Listen for form submission response
            iframe.on('load', function() {
                try {
                    var iframeDoc = iframe[0].contentDocument || iframe[0].contentWindow.document;
                    var responseText = iframeDoc.body.textContent;

                    if (responseText) {
                        var response = JSON.parse(responseText);

                        // Encode response as base64 and redirect to PayWay checkout
                        var token = btoa(responseText);
                        var checkoutUrl = 'https://checkout-sandbox.payway.com.kh/' + token;

                        // Redirect iframe to checkout page
                        iframe.attr('src', checkoutUrl);
                    }
                } catch(e) {
                    // If not JSON or can't access iframe content, it's already the checkout page
                    console.log('Iframe loaded checkout page');
                }
            });

            // Submit form to iframe
            $('#aba_merchant_request').submit();
        });
    </script>
</body>
</html>
