$(document).ready(function(){

	$('input[placeholder], textarea[placeholder]').placeholder();

	$('input, textarea').focus(function(){
		$(this).data('placeholder', $(this).attr('placeholder'))
		$(this).attr('placeholder', '');
	});
	$('input, textarea').blur(function(){
		$(this).attr('placeholder', $(this).data('placeholder'));
	});

	$('input.mask-phone').mask('+7 (999) 999 99 99');

    $('.filter select, .calcul-form__item select').niceSelect();
    
    $('.header').toShowHide({
        button: '.bt-menu',
        button_close: '.bt-menu',
        close_only_button: false,
        box: '.header-user',
        effect: 'fade',
        onShow: function(el){
            el.find('.bt-menu').addClass('active');
        },
        onHide: function(el){
            el.find('.bt-menu').removeClass('active');
        }
    });

    $('.search-task__table-row').toShowHide({
        button: '.task-name',
        button_close: '.task-name',
        close_only_button: true,
        box: '.search-task__table-desc',
        effect: 'slide',
        onShow: function(el){
            el.addClass('active');
        },
        onHide: function(el){
            el.removeClass('active');
        }
    });

    $('.order-table__row').toShowHide({
        button: '.order-name',
        button_close: '.order-name',
        close_only_button: true,
        box: '.order-table__desc',
        effect: 'slide',
        onShow: function(el){
            el.addClass('active');
        },
        onHide: function(el){
            el.removeClass('active');
        }
    });

    $('.job-table__row').toShowHide({
        button: '.job-name',
        button_close: '.job-name',
        close_only_button: true,
        box: '.job-table__desc',
        effect: 'slide',
        onShow: function(el){
            el.addClass('active');
        },
        onHide: function(el){
            el.removeClass('active');
        }
    });


    $('.filter').toShowHide({
        button: '.filter-button',
        button_close: '.filter-button',
        close_only_button: false,
        box: '.filter-content',
        effect: 'slide'
    });

    if($(window).width() > 700) {
        $('.header-user').toShowHide({
            button: '.header-user__bar',
            button_close: '.header-user__bar',
            close_only_button: false,
            box: '.user-nav',
            effect: 'fade'
        });
    };

    $('.services').toShowHide({
        button: '.services-link__all a',
        box: '.services-list',
        effect: 'slide',
        anim_speed: 200,
        delay: 10,
        onShow: function(el){
            el.find('.services-link__all a').addClass('active');
        },
        onHide: function(el){
            el.find('.services-link__all a').removeClass('active');
        }
    });

    $('.slider-promo').slick({
        autoplay: false,
        speed: 300,
        prevArrow: '.slider-promo .arr-l',
        nextArrow: '.slider-promo .arr-r',
        dots: true,
        dotsClass: 'page',
        appendDots: '.product-sale .slider',
        slidesToShow: 5,
        responsive: [
            {
                breakpoint: 750,
                settings: {
                    slidesToShow: 4
                }
            },
            {
                breakpoint: 550,
                settings: {
                    slidesToShow: 3
                }
            }
        ]
    });

$('.edit-task .edit-task__tabs a').click(function(e) {
    e.preventDefault();

    $('.edit-task .edit-task__tabs a').removeClass('active');
    $('.edit-task .edit-task__content').removeClass('active');

    $('.edit-task .edit-task__content' + '.' + $(e.target).attr('class')).addClass('active');

    $(e.target).addClass('active');
});
	
$('.order-slidetoggle').click(function(e) {
        e.preventDefault();
        $(e.target).parent().parent().parent().find('.orders-table').slideToggle();
    });    
});