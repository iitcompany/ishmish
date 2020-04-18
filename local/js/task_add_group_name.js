$(document).ready(function () {
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

    $('.main-kanban-column-items').find("[data-id]").each(function () {
        let id = $(this).attr('data-id');
        if ($(this).attr('data-type') === 'item') {
            let name = $(this).attr('data-name');

            if (typeof name === 'undefined') {
                send = true;
                task_id[$(this).attr('data-id')] = $(this).attr('data-id');
            } else {
                $(this).find('.tasks-kanban-item-title').empty().text(name);
            }
        }
    });
    if (send === true) {
        console.log(task_id);
        data['action'] = 'get_group';
        data['task_id'] = task_id;

        $.ajax({
            url: '/local/tools/task.php',
            data: data,
            dataType: 'json',
            success: function (response) {
                $.each(response, function (id, val) {
                    $('[data-id="'+id+'"]').each(function () {
                        $(this).attr('data-name', val).find('.tasks-kanban-item-title').empty().text(val);
                    });

                });
            }
        });

    }
}