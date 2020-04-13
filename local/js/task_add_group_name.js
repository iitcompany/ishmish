$(window).load(function () {

    getGroup();
});

BX.addCustomEvent('onScriptsLoaded', function () {
    BX.addCustomEvent('onAjaxSuccess', function (responce, data) {
        getGroup();
    });
});

function getGroup() {
    let data = {},
        task_id = {},
        send = false;


    $('.main-kanban-item').each(function () {
        if ($(this).hasClass('add-group-name') === false && $(this).attr('data-type') === 'item') {
            send = true;
            task_id[$(this).attr('data-id')] = $(this).attr('data-id');
        }
    });
    if (send === true) {
        data['action'] = 'get_group';
        data['task_id'] = task_id;

        $.ajax({
            url: '/local/tools/task.php',
            data: data,
            dataType: 'json',
            success: function (response) {
                $.each(response, function (i, val) {
                    $('[data-type="item"]').each(function () {
                        if ($(this).attr('data-id') === i) {
                            if ($(this).hasClass('add-group-name') === false) {
                                $(this).addClass('add-group-name');
                                let name = $(this).find('.tasks-kanban-item-title'),
                                    text = name.text();

                                name.empty().text(val + ': ' + text);
                            }

                        }
                    });

                });
                console.log(response);
            }
        });

    }
}