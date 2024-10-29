jQuery(function($){
    var valid = require("us-bank-account-validator");
    var validCard = require("card-validator");
    $checkout_form=$( 'form.checkout' );
    $(document).on('click','.accrdion-header',function(e){
        var self=$(this);
        if((self).hasClass('active')) return false;
       
        self.parent().find('.open').removeClass('open');
        self.parent().find('.active').removeClass('active');
        self.next().addClass('open');
        self.addClass('active');
        $('#ach_processing-payment-type').val(self.data('type'));
    });
    $(document).on('blur','#ach_processing-account-number',function(e){
        var self=$(this),
         validationCall=bankValidation('account',self);
        if(!validationCall){
            $('.achp_account_error').remove();
            $('.achp_error_container_bank').append('<p class="achp_error achp_account_error">'+achp_params.account_error_label+'</p>');
        }else{
            $('.achp_account_error').remove();
        }
    });
    $(document).on('blur','#ach_processing-routing-number',function(e){
        var self=$(this),
         validationCall=bankValidation('routing',self);
        if(!validationCall){
            $('.achp_routing_error').remove();
            $('.achp_error_container_bank').append('<p class="achp_error achp_routing_error">'+achp_params.routing_error_label+'</p>');
        }else{
            $('.achp_routing_error').remove();
        }
    });
    $(document).on('blur','.achp_card_element',function(){
        var self=$(this);
        var type=self.data('type');
        var labelText=achp_params[type+'_error_label'];
        var checkValidation=cardValidation(type,self);
        if(!checkValidation){
            $('.achp_'+type+'_error').remove();
            $('.achp_error_container_card').append('<p class="achp_error achp_'+type+'_error">'+labelText+'</p>');
        }else{
            $('.achp_'+type+'_error').remove();
        }
    })
    $checkout_form.on('checkout_place_order', function () {
        if($('#payment_method_ach_processing').prop("checked") == true){
            var validateForm=false;
            var paymentType=$('#ach_processing-payment-type').val();
            if(paymentType=='bank'){
                var accountValidate=bankValidation('account',$('#ach_processing-account-number'));
                var routingValidate=bankValidation('routing',$('#ach_processing-routing-number'));
                if(accountValidate===false || routingValidate===false){
                    $('#ach_processing-account-number').trigger('blur');
                    $('#ach_processing-routing-number').trigger('blur');
                   validateForm=false;
                }else{
                    validateForm=true;
                }
            }
            if(paymentType=='card'){
                $('.achp_card_element').each(function(){
                    var cardValidate=cardValidation($(this).data('type'),$(this));
                    
                    if(cardValidate===false) {
                        validateForm=false;
                        $(this).trigger('blur');
                        return validateForm;
                    }else{
                        validateForm=true;
                    }
                })
            }
            return validateForm;
        }
       
    })
    var bankValidation=function(type,element){
        var self=$(element),
            selfValue=self.val().trim();
            if(type=='account'){
                var accountValidation = valid.accountNumber(selfValue);
                if (accountValidation.isValid) {
                    return true;
                }
            }
            if(type=='routing'){
                var routingValidation = valid.routingNumber(selfValue);
                if (routingValidation.isValid) {
                    return true;
                }
            }
            return false;
    }
    var cardValidation=function(type,element){
        var self=$(element),
            selfValue=self.val().trim();
            if(type=='name'){
                var nameValidation=validCard.cardholderName(selfValue);
                if(nameValidation.isValid) return true;
            }
            if(type=='number'){
                var numberValidation=validCard.number(selfValue);
                if(numberValidation.isValid) return true;
            }
            if(type=='expiry'){
                var expiryValidation=validCard.expirationDate(selfValue);
                if(expiryValidation.isValid) return true;
            }
            if(type=='cvv'){
                var cvcValidation=validCard.cvv(selfValue);
                if(cvcValidation.isValid) return true;
            }
    }
})