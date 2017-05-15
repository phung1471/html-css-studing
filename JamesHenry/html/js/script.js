(function($){
$(document).ready(function(){
	$('.sf-menu').superfish({
          speed: 'fast'
    });
	
	$('#tablet-menu').click(function(){
		$('.mobile-navigation').hide();
		$('.tablet-navigation').toggle();
	});
	
	$('#mobile-menu').click(function(){
		$('.tablet-navigation').hide();
		$('.mobile-navigation').toggle();
	});
	
	$('.accordion').on('show hide', function (n) {
		$(n.target).siblings('.accordion-heading').find('.accordion-toggle i').toggleClass('icon-chevron-up icon-chevron-down');
	});
	
	$('#slider').anythingSlider({
		buildArrows         : true,
		buildNavigation     : true,
		buildStartStop      : false,
		expand       		  : true,
		enableArrows        : true,
		enableNavigation    : true,
		hashTags            : false,
		autoPlay            : true,
		autoPlayLocked      : true,
		autoPlayDelayed     : true,
		pauseOnHover        : false,
		delay               : 3000
	});
	
	$('.produc-list li').each(function(index, element) {
        var id = $(this).index();
		if ((id+1) % 4 == 0) {
			$(this).addClass('endrow');	
		}
    });
	
	$('#FlooringServices li').each(function(index, element) {
        var id = $(this).index();
		if ((id+1) % 5 == 0) {
			$(this).addClass('endrow');	
		}
    });
	
	$('#TotalFlooring li').each(function(index, element) {
        var id = $(this).index();
		if ((id+1) % 4 == 0) {
			$(this).addClass('endrow');	
		}
    });
	
	$('#WoodSpecies li').each(function(index, element) {
        var id = $(this).index();
		if ((id+1) % 5 == 0) {
			$(this).addClass('endrow');	
		}
    });
	
	$('#FloorView li').each(function(index, element) {
        var id = $(this).index();
		if ((id+1) % 5 == 0) {
			$(this).addClass('endrow');	
		}
    });
	
	$('#Porfolio li').each(function(index, element) {
        var id = $(this).index();
		if ((id+1) % 4 == 0) {
			$(this).addClass('endrow');	
		}
    });
	
	$(window).resize(function(){
		$('.tablet-navigation').hide();
		$('.mobile-navigation').hide();
	});
	
	if ($('#Porfolio').length > 0) {
		$("#Porfolio .gallery").colorbox({
			rel:'gallery',
			maxWidth:'980px',
			maxHeight:'580px'
		});
		
		$("#Porfolio .small-gallery").colorbox({
			rel:'small-gallery',
			width:'90%'
		});
		
		$(window).resize(function(){
			$.colorbox.close();
		});
	}
	
	if($('#News').length > 0 ){
		var content_height = $('#News .news-category').height();
		$('#News').css('min-height',content_height);
	}
	$(window).resize(function(){
		if($('#News').length > 0 ){
			content_height = $('#News .news-category').height();
			$('#News').css('min-height',content_height);
		}
	});
});
})(jQuery);

/*----------------------------------------------------*/
/* Height of element
------------------------------------------------------*/
equalheight = function(container){

var currentTallest = 0,
     currentRowStart = 0,
     rowDivs = new Array(),
     el,
     topPosition = 0;
 $(container).each(function() {

   el = $(this);
   $(el).height('auto')
   topPostion = el.position().top;

   if (currentRowStart != topPostion) {
     for (currentDiv = 0 ; currentDiv < rowDivs.length ; currentDiv++) {
       rowDivs[currentDiv].height(currentTallest);
     }
     rowDivs.length = 0; // empty the array
     currentRowStart = topPostion;
     currentTallest = el.height();
     rowDivs.push(el);
   } else {
     rowDivs.push(el);
     currentTallest = (currentTallest < el.height()) ? (el.height()) : (currentTallest);
  }
   for (currentDiv = 0 ; currentDiv < rowDivs.length ; currentDiv++) {
     rowDivs[currentDiv].height(currentTallest);
   }
 });
}

$(window).load(function() {
  equalheight('.flooring-list > li');
});


$(window).resize(function(){
  equalheight('.flooring-list > li');
});

(function($) {
    // This is the connector function.
    // It connects one item from the navigation carousel to one item from the
    // stage carousel.
    // The default behaviour is, to connect items with the same index from both
    // carousels. This might _not_ work with circular carousels!
    var connector = function(itemNavigation, carouselStage) {
        return carouselStage.jcarousel('items').eq(itemNavigation.index());
    };

    $(function() {
        // Setup the carousels. Adjust the options for both carousels here.
		var jcarousel = $('.carousel-navigation');
        var carouselStage      = $('.carousel-stage').jcarousel({
														wrap: 'circular'
													})
													.jcarouselAutoscroll({
														interval: 3000,
														target: '+=1',
														autostart: true
													});
        var carouselNavigation = $('.carousel-navigation')
														.on('jcarousel:reload jcarousel:create', function () {
															var width = jcarousel.innerWidth();
											
															if (width >= 600) {
																width = width / 6;
															} else {
																width = width / 3;
															}
											
															jcarousel.jcarousel('items').css('width', width + 'px');
														})
														.jcarousel({
															wrap: 'circular'
														});

        // We loop through the items of the navigation carousel and set it up
        // as a control for an item from the stage carousel.
        carouselNavigation.jcarousel('items').each(function() {
            var item = $(this);

            // This is where we actually connect to items.
            var target = connector(item, carouselStage);

            item
                .on('jcarouselcontrol:active', function() {
                    carouselNavigation.jcarousel('scrollIntoView', this);
                    item.addClass('active');
					$('.s-caption').empty().html(item.find('img').attr('alt'));
                })
                .on('jcarouselcontrol:inactive', function() {
                    item.removeClass('active');
                })
                .jcarouselControl({
                    target: target,
                    carousel: carouselStage
                });
        });

        // Setup controls for the stage carousel
        $('.prev-stage')
            .on('jcarouselcontrol:inactive', function() {
                $(this).addClass('inactive');
            })
            .on('jcarouselcontrol:active', function() {
                $(this).removeClass('inactive');
            })
            .jcarouselControl({
                target: '-=1'
            });

        $('.next-stage')
            .on('jcarouselcontrol:inactive', function() {
                $(this).addClass('inactive');
            })
            .on('jcarouselcontrol:active', function() {
                $(this).removeClass('inactive');
            })
            .jcarouselControl({
                target: '+=1'
            });

        // Setup controls for the navigation carousel
        $('.prev-navigation')
            .on('jcarouselcontrol:inactive', function() {
                $(this).addClass('inactive');
            })
            .on('jcarouselcontrol:active', function() {
                $(this).removeClass('inactive');
            })
            .jcarouselControl({
                target: '-=1'
            });

        $('.next-navigation')
            .on('jcarouselcontrol:inactive', function() {
                $(this).addClass('inactive');
            })
            .on('jcarouselcontrol:active', function() {
                $(this).removeClass('inactive');
            })
            .jcarouselControl({
                target: '+=1'
            });
    });
})(jQuery);
