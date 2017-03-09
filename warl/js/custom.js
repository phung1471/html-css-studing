jQuery(document).ready(function($){

    //block content slider
    //$('.slick-slider').slick({
    //    slidesToShow: 1,
    //    slidesToScroll:1,
    //    autoplay: true,
    //    autoplaySpeed: 1500,
    //    arrows: false,
    //    centerMode: true,
    //    lazyLoad: 'progressive',
    //    infinite: true,
    //    dots: true,
    //    pauseOnDotsHover: true,
    //    variableWidth: true,
    //    responsive: [
    //        {
    //            breakpoint: 767,
    //            settings: {
    //                slidesToShow: 1,
    //                slidesToScroll: 1,
    //                infinite: true,
    //                variableWidth: true
    //            }
    //        },
    //        {
    //            breakpoint: 360,
    //            settings: {
    //                slidesToShow: 1,
    //                slidesToScroll: 1,
    //                infinite: true,
    //                variableWidth: true
    //            }
    //        }
    //        // You can unslick at a given breakpoint now by adding:
    //        // settings: "unslick"
    //        // instead of a settings object
    //    ]
    //});

    var el, set, timeRemain, sliderContinue;


    // App
    var Application = {

        settings: {
            sliderAutoplaySpeed: 7000,
            sliderSpeed: 1200
        },

        elements: {
            slider: $('.slick-slider')
            //slickAllThumbs: $('.slick-dots button'),
            //slickActiveThumb: $('.slick-dots .slick-active button')

        },

        init: function() {
            set = this.settings;
            el = this.elements;


            this.slider();

        },

        /**
         * Slider
         */
        slider: function() {

            el.slider.on('init', function() {
                $(this).find('.slick-dots button').text('');
                //Application.dotsAnimation();

            });

            el.slider.slick({
                arrows: false,
                slidesToShow: 1,
                dots: true,
                autoplay: true,
                autoplaySpeed: set.sliderAutoplaySpeed,
                fade: false,
                speed: set.sliderSpeed,
                pauseOnHover: false,
                pauseOnDotsHover: true
            });

            //$('.slick-dots').hover(
            //    function() {
            //        var trackWidth = $('.slick-dots .slick-active button').width();
            //        $('.slick-dots .slick-active button').stop().width(trackWidth);
            //        el.slider.slick('slickPause');
            //        clearTimeout(sliderContinue);
            //    },
            //    function() {
            //        Application.dotsAnimation(timeRemain);
            //        var trackWidth = $('.slick-dots .slick-active button').width();
            //
            //
            //        sliderContinue = setTimeout(function() {
            //            el.slider.slick('slickNext');
            //            el.slider.slick('slickPlay');
            //        }, timeRemain);
            //    }
            //);

            //el.slider.on('beforeChange', function() {
            //    $('.slick-dots button').stop().width(0);
            //});
            //
            //el.slider.on('afterChange', function() {
            //    $('.slick-dots button').width(0);
            //    Application.dotsAnimation();
            //});

        },

        /**
         *
         * @param remain {number}
         */

        //dotsAnimation: function(remain) {
        //
        //    if (remain) {
        //        var newDuration = remain;
        //    } else {
        //        var newDuration = set.sliderAutoplaySpeed;
        //    }
        //
        //    $('.slick-dots .slick-active button').animate({ width: '100%' },
        //        {
        //            duration: newDuration,
        //            easing: 'linear',
        //            step: function(now, fx) {
        //                var timeCurrent = Math.round((now*set.sliderAutoplaySpeed)/100);
        //                timeRemain = set.sliderAutoplaySpeed - timeCurrent;
        //            }
        //        }
        //    );
        //}

    };



    //Init
    Application.init();


    $(window).load(function() {
        var win_h = $(window).height();
        $('.holding').height(win_h-3);

        //set width and height for block content
        var height = $('.menu').outerHeight(true) + $('.banner').outerHeight(true);
        $('.block-content').height(win_h - height);
        $('.slick-slide .img-holder').height(win_h - height);
    });

    $(window).resize(function() {
        var win_h = $(window).height();
        $('.holding').height(win_h-3);

        //set width and height for block content
        var height = $('.menu').Height() + $('.banner').Height();
        $('.block-content').height(win_h - height);

        $('.slick-slide .img-holder').height(win_h - height);

    });

});
