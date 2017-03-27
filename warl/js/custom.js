jQuery(document).ready(function($){

    var win_h = $(window).height();
    var header_height = $('.menu').outerHeight() + $('.banner').outerHeight();

    $(window).load(function() {
        $('.holding').height(win_h-3);
        //set width and height for block content
        $('.block-content').height(win_h - header_height);
        $('.slick-slide .img-holder').height(win_h - header_height);

    });

    $(window).resize(function() {
        var win_h = $(window).height();
        $('.holding').height(win_h-3);

        //set width and height for block content
        $('.block-content').height(win_h - header_height);

        $('.carousel .item').height(win_h - header_height);

    });

    $(window).scroll(function () {
        var h = $(window).height() - header_height;
        if ($(this).scrollTop() > h) {
            //$("header").css("opacity",1);
        } else {
            //$("header").css("opacity",0);
        }
    }).resize();

    $('.arrow-down').on('click', function (e) {
        var target = $(this).attr('next');
        if($('#block-content'+target).length){
            $("body,html").stop().animate({
                scrollTop: $('#block-content'+target).offset().top - header_height
            }, 1000);
        }
        return false;
    });

    $('#back_top').click(function(){
        $('body,html').stop().animate({
            scrollTop: 0
        }, 1000);
    });

    //$('.humber').on("click", function(event){
        //var parent_class = $(this).parent().attr('class');
        //$(this).fadeOut('fast', function(){
        //    if(parent_class == 'dropdown'){
        //        $(this).attr('src','img/open-icon.png')
        //    }else{
        //        $(this).attr('src','img/humber-icon.png');
        //    }
        //    $(this).fadeIn(0);
        //});
    //});
});
