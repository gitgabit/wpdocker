!function(t){"use strict";function n(t){t.find('button[type="submit"]').removeClass("fusion-button-submitting")}t(document.body).on("submit",".awb-stripe-button-form",function(e){var o=t(this);if(e.preventDefault(),o.find(".awb-stripe-button-response").hide(),function(t){t.find('button[type="submit"]').addClass("fusion-button-submitting")}(o),""===o.find('[name="product_name"]').val()||""===o.find('[name="product_price"]').val())return o.find(".awb-stripe-button-response-error .fusion-alert-content").html(fusionStripeButtonVars.productEmptyText).end().find(".awb-stripe-button-response-error").slideDown(300),n(o),!0;t.ajax({type:"POST",url:fusionStripeButtonVars.ajax_url,data:{action:"awb_stripe_button_submit",nonce:window.fusionStripeButtonVars.nonce,data:t(this).serialize()},dataType:"json",beforeSend:function(){o.find('button[type="submit"] .fusion-button-text').text(o.data("button-process-text"))},success:function(t){var e=t.response.code,s=t.body&&JSON.parse(t.body);switch(e){case 200:window.open(s.url,o.data("target"));break;default:o.find(".awb-stripe-button-response-error .fusion-alert-content").html(s.error.message).end().find(".awb-stripe-button-response-error").slideDown(300)}n(o)}})})}(jQuery);