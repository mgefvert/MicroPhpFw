$(function() {

    // Initialize submenu hovers
    $('#main-menu > ul > li').hover(menuHoverIn, menuHoverOut);

});

function menuHoverIn()
{
    var $submenu = $(this).children('ul:first');
    var parentPos = $(this).position();

    $(this).children('a').addClass('select');
    $submenu.css({
        left: parentPos.left,
        top: parentPos.top + $(this).height() + 2
    }).slideDown('fast');
}

function menuHoverOut()
{
    $(this).children('a').removeClass('select');
    $(this).children('ul:first').hide();
}
