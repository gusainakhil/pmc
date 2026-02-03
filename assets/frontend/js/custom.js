(function($) {
    "use strict";

    var $main_window = $(window);

    /*=========================
      preloader js
    ===========================*/
    $main_window.on("load", function() {
        $(".preloader").fadeOut("slow");
    });

    /*===========================
      scroll to top js
    =============================*/
    $(window).on("scroll", function() {
        if ($(this).scrollTop() > 250) {
            $("#toTop").fadeIn(200);
        } else {
            $("#toTop").fadeOut(200);
        }
    });
    $("#toTop").on("click", function() {
        $("html, body").animate({
                scrollTop: 0
            },
            "slow"
        );
        return false;
    });

    /*===========================
      sticky menu js
    =============================*/

    $main_window.on('scroll', function() {
        var scroll = $(window).scrollTop();
        if (scroll >= 200) {
            $(".affix").addClass("sticky-menu");
        } else {
            $(".affix").removeClass("sticky-menu");
        }
    });

    /*=========================
      toggle search
    ===========================*/
    $('a[href="#search"]').on('click', function(event) {
        $('#search').toggleClass('open');
    });
    $('.close').on('click', function(event) {
        $('#search').removeClass('open');

    });

    /*=========================
      Navigation mobile menu
    ==========================*/

    function mainmenu() {
        $('.dropdown-menu a.dropdown-toggle').on('click', function(e) {
            if (!$(this).next().hasClass('show')) {
                $(this).parents('.dropdown-menu').first().find('.show').removeClass("show");
            }
            var $subMenu = $(this).next(".dropdown-menu");
            $subMenu.toggleClass('show');

            return false;
        });
    }
    mainmenu();

    /*============================
      Jarallax
    ============================== */
    if (jQuery().jarallax) {
        $('.jarallax').jarallax({
            speed: 0.2
        });
    }

    /*==========================
      Box mouse-enter hover
    ============================ */
    var BoxHover = function() {
        jQuery('.box-hover').on('mouseenter', function() {
            jQuery(this).closest('.row').find('.box-hover').removeClass('active');
            jQuery(this).addClass('active');
        });
    };
    BoxHover();

    /*===========================
      popular-list-slider
    ============================= */

    $('.popular-list-slider').slick({
        slidesToShow: 3,
        arrows: false,
        dots: true,
        centerPadding: '0',
        centerMode: true,
        autoplay: true,
        autoplaySpeed: 2000,
        responsive: [{
                breakpoint: 1200,
                settings: {
                    slidesToShow: 3
                }
            },
            {
                breakpoint: 992,
                settings: {
                    slidesToShow: 1
                }
            }
        ]
    });

    /*====================================
		partner slider js
	======================================*/

    var partnerslider = $(".partner-slider");
    partnerslider.owlCarousel({
        autoplay: true,
        nav: true,
        autoplayHoverPause: true,
        smartSpeed: 350,
        margin: 30,
        loop: true,
        navText: [
            '<i class="fa fa-angle-left"></i>',
            '<i class="fa fa-angle-right"></i>'
        ],
        responsiveClass: true,
        responsive: {
            0: {
                items: 1,
                nav: false,
                dots: true,
            },
            450: {
                items: 2,
                nav: false,
                dots: true,
            },
            767: {
                items: 3,
                nav: false,
                dots: true,
            },
            991: {
                items: 4,
                nav: false,
                dots: true,
            },
            1199: {
                items: 5,
                dots: false,
            }
        }
    });

    /*====================================
		list category slider
	======================================*/

    var list_slider = $(".list-category-slider");
    list_slider.owlCarousel({
        autoplay: true,
        nav: true,
        autoplayHoverPause: true,
        smartSpeed: 1000,
        dots: false,
        margin: 30,
        loop: true,
        navText: [
            '<i class="fa fa-angle-left"></i>',
            '<i class="fa fa-angle-right"></i>'
        ],
        responsiveClass: true,
        responsive: {
            0: {
                items: 1,
                nav: false,
                dots: true,
            },
            450: {
                items: 2,
                nav: false,
                dots: true,
            },
            767: {
                items: 3,
                nav: false,
                dots: true,
            },
            991: {
                items: 5,
                nav: false,
                dots: true,
            },
            1199: {
                items: 6,
                dots: false,
            }
        }
    });

    /*=======================================
      testimonial slider 1
    ======================================= */
    var testi_slider = $('.testi-slider1');
    testi_slider.owlCarousel({
        autoplay: true,
        items: 2,
        nav: false,
        dots: true,
        margin: 30,
        autoplayHoverPause: true,
        loop: true,
        smartSpeed: 1000,
        responsiveClass: true,
        responsive: {
            0: {
                items: 1,
                nav: false,
                dots: true,
            },
            991: {
                items: 2,
                nav: false,
                dots: true,
            },
        }
    })

    /*====================================
		listing detail slider
	======================================*/

    var listing_detail_slider = $(".listing-detail-slider");
    listing_detail_slider.owlCarousel({
        autoplay: true,
        nav: true,
        dots: false,
        autoplayHoverPause: true,
        margin: 30,
        loop: true,
        navText: [
            '<i class="fa fa-angle-left"></i>',
            '<i class="fa fa-angle-right"></i>'
        ],
        responsiveClass: true,
        responsive: {
            0: {
                items: 1,
            },
            600: {
                items: 2,
            },
            991: {
                items: 3,
            },
            1199: {
                items: 4,
            }
        }
    });

    /*=======================================
      listing sidebar slider
    ======================================= */
    $('.sidebar-listing-slider').owlCarousel({
        autoplay: true,
        items: 1,
        nav: false,
        dots: true,
        autoplayHoverPause: true,
        loop: true,
        smartSpeed: 1000,
    })

    /*=======================================
      counter
    ======================================= */

    if ($('.counters').length > 0) {
        var a = 0;
        $main_window.scroll(function() {
            var oTop = $('.counters').offset().top - window.innerHeight;
            if (a === 0 && $main_window.scrollTop() > oTop) {
                $('.count').each(function() {
                    var $this = $(this),
                        countTo = $this.attr('data-count');
                    $({
                        countNum: $this.text()
                    }).animate({
                        countNum: countTo
                    }, {
                        duration: 3000,
                        easing: 'swing',
                        step: function() {
                            $this.text(Math.floor(this.countNum));
                        },
                        complete: function() {
                            $this.text(this.countNum);
                            //alert('finished');
                        }
                    });
                });
                a = 1;
            }
        });
    }

    /*====================================
      Isotop And Masonry
    ======================================*/
    if ($(".masonary-wrap").length > 0) {
        $main_window.on('load', function() {
            var $grid = $('.masonary-wrap').isotope({
                itemSelector: '.mas-item',
                percentPosition: true,
                masonry: {
                    columnWidth: '.mas-item'
                }
            });
            $('.sorting').on('click', '.filter-btn', function() {
                var filterValue = $(this).attr('data-filter');
                $grid.isotope({
                    filter: filterValue
                });
            });
            $('.sorting li').on('click', function(event) {
                $(".filter-btn").removeClass('active');
                $(this).addClass('active');
                event.preventDefault();
            });
        });
    }

    /*====================================
	  Social sharing
	======================================*/
    if ($(".share").length > 0) {
        $(".share").jsSocials({
            showLabel: false,
            showCount: false,
            shareIn: "blank",
            shares: [{
                    share: "twitter",
                    logo: "fab fa-twitter",
                },
                {
                    share: "facebook",
                    logo: "fab fa-facebook-f"
                },
                {
                    share: "email",
                    logo: "fa fa-envelope"
                },
                {
                    share: "googleplus",
                    logo: "fab fa-google-plus-g"
                }
            ]
        });
    }

    /*====================================
      Add and remove divs
    ======================================*/
    if ($(".clone-wrap").length > 0) {
        $('.clone-wrap').on('click', '.remove-section', function() {
            $('.remove-section').closest('.clone-wrap').find('.clone-section').not(':first').last().remove();
        });
        $('.clone-wrap').on('click', '.add-section', function() {
            $('.add-section').closest('.clone-wrap').find('.clone-section').first().clone().prependTo('.clone-wrap');
        });
    }
    if ($(".clone-wrap2").length > 0) {
        $('.clone-wrap2').on('click', '.remove-section', function() {
            $('.remove-section').closest('.clone-wrap2').find('.clone-section').not(':first').last().remove();
        });
        $('.clone-wrap2').on('click', '.add-section', function() {
            $('.add-section').closest('.clone-wrap2').find('.clone-section').first().clone().prependTo('.clone-wrap2');
        });
    }

    /*====================================
      Range slider
    ======================================*/

    $('#myRange').on('input', function() {
        $('.range-value').text(this.value)
        $('.fill').css('width', this.value + '%');
    });

    /*====================================
     Checkbox toggle
    ======================================*/
    $(document).ready(function() {
        $(".toggle-icon").on('click', function() {
            $(this).toggleClass('open');
            $(".filter-checklist").slideToggle('slow');
            return false;
        });
    });

    /*====================================
     list to grid view
    ======================================*/
    $(document).ready(function() {
        $(".list-view").on('click', function() {
            $(this).addClass('current');
            $(".grid-view").removeClass('current');
            $(".listing-list-view").addClass('show-list');
            $(".list-grid-view").removeClass('show-list');
            return false;
        });
        $(".grid-view").on('click', function() {
            $(this).addClass('current');
            $(".list-view").removeClass('current');
            $(".list-grid-view").addClass('show-list');
            $(".listing-list-view").removeClass('show-list');
            return false;
        });
    });

    /*====================================
     increment and decrement
    ======================================*/
    var buttonPlus = $(".cart-qty-plus");
    var buttonMinus = $(".cart-qty-minus");

    var incrementPlus = buttonPlus.on('click', function() {
        var $n = $(this)
            .parent(".inc-dec-control")
            .parent(".inc-dec-contain")
            .find(".qty");
        $n.val(Number($n.val()) + 1);
    });

    var incrementMinus = buttonMinus.on('click', function() {
        var $n = $(this)
            .parent(".inc-dec-control")
            .parent(".inc-dec-contain")
            .find(".qty");
        var amount = Number($n.val());
        if (amount > 0) {
            $n.val(amount - 1);
        }
    });

})(jQuery);


/*======================================
  Google map
====================================== */
/*************
if (document.getElementById("theme-map")) {
  var myCenter = new google.maps.LatLng(-37.813628, 144.963058);
  function initialize() {
    var mapProp = {
      center: myCenter,
      scrollwheel: false,
      zoom: 13,
      mapTypeId: google.maps.MapTypeId.ROADMAP
    };
    var map = new google.maps.Map(document.getElementById("theme-map"), mapProp);
    var marker = new google.maps.Marker({
      position: myCenter,
      animation: google.maps.Animation.BOUNCE,
      icon: 'assets/img/mapi.png',
      map: map,
    });
    marker.setMap(map);
  }
  google.maps.event.addDomListener(window, 'load', initialize);
}******************/
// map initialization code  ends