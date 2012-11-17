document.observe("dom:loaded", function () {
    $$('th input[type="checkbox"]').invoke('observe', 'click', function (event) {
        var column = this.up('tr').select('th').indexOf(this.up('th'));
        var checkboxes = this.up('table').select('tr td:nth-child('+ (column + 1) +') input[type="checkbox"][disabled!="true"]');
        checkboxes.each((function (checkbox) {
            checkbox.checked = this.checked;
        }).bind(this));
    });
});

function clear_messages(container) {
    container = $(container);
    container.select('.alert-success', '.alert-error').invoke('remove');
}

function render_messages(type, container, messages, heading) {
    var element = new Element('div', {
        className: 'alert alert-' + type
    });

    if (typeof heading != 'undefined') {
        heading = (new Element('strong', {
            className: 'alert-heading'
        })).update(heading);
    }

    var close = (new Element('button', {className: 'close pull-right'})).update('&times;');
        close.on('click', function () {
            this.up('.alert').remove();
        });
    element.insert(close);  

    if (typeof heading != 'undefined') {
        element.insert(heading);
    }

    if (!(messages instanceof Array)) {
        messages = [messages];
    }

    var list = new Element('ul', {className: 'unstyled nomargin'});
    for (var i = 0; i < messages.length; i++) {
        var item = new Element('li').update(messages[i]);
        if (i == messages.length - 1) {
            item.addClassName('last');
        }
        
        list.insert(item);
    }

    element.insert(list);

    $(container).down('.log').insert(element);
}

