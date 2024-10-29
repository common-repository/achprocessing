jQuery(function($){
    
    $(document).on('click','.accrdion-header',function(e){
        var self=$(this);
        if((self).hasClass('active')) return false;
       
        self.parent().find('.open').removeClass('open');
        self.parent().find('.active').removeClass('active');
        self.next().addClass('open');
        self.addClass('active');
        $('#ach_processing-payment-type').val(self.data('type'));
    })
    // $(document).on('blur','#ach_processing-account-numer',function(e){
    //     var self=$(this);
    //     if(self.val().trim()!=''){
    //         if (self.val().trim().isValid || (!self.val().trim().isValid && !self.val().trim().isPotentiallyValid)) {
    //             alert('Valid');
    //         }else{
    //             alert('Invalid');
    //         }
    //     }
    // })
})