$(function(){
    $('.error-all').on('click','.error-debug-one',function(){
        $(this).closest('.error-debug').toggleClass('error-debug-on');
    });
});