$(function() {
    // Initialize submenu hovers
    $('#main-menu > ul > li').hover(menuHoverIn, menuHoverOut);

    // Attach page editor
    attachEditor();
});

var $editor;

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

function attachEditor()
{
    $editor = $('<div id="_edit" style="">Edit</div>')
        .css({
            position: 'absolute',
            top: 0,
            right: 0,
            fontSize: '7pt',
            padding: '5px 10px',
            zIndex: 999,
            opacity: 0.1,
            cursor: 'pointer'
        })
        .hover(function() {
            $editor
                .css({
                    opacity: 1,
                    backgroundColor: '#cceeff'
                });
            }, function() {
                $editor.css({
                    opacity: 0.1,
                    backgroundColor: 'transparent'
                });
            })
        .on('click', editPage)
        .appendTo('body');

    $('#dialog .btn-primary').on('click', clickOkButton);
}

function editPage()
{
    $.getJSON('', { mode: 'edit' }, handleResult);
}

function clickOkButton()
{
    var data = $('#dialog form').serialize();
    $('#dialog').modal('hide');
    $.post('', data, handleResult);
}

function handleResult(result)
{
    if (typeof result === 'string') {
        result = JSON.parse(result);
    }

    if (!result.success) {
        alert(result.message);
        return;
    }

    if (result.action) {
        switch (result.action)
        {
            case 'reload':
                location.reload(true);
                return;

            case 'repeat':
                $.post('', result.repeat, handleResult);
                return;

            default:
                alert('Unknown result ' + result.action);
                return;
        }
    }

    $('#dialog .modal-title').html(result.title);
    $('#dialog .modal-body').html(result.html);
    $('#dialog .btn-primary').html(result.okButton);
    $('#dialog .btn-secondary').html(result.cancelButton);
    $('#dialog').modal();
}
