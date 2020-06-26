$(document).ready(function() {
    var task_list = $('#task_list');

    // New task
    $('.task_create').on('click', function (e) {
        e.preventDefault();

        let message_block_modal = $('#taskCreateModal .message-block');
        let message_block = $('#content>.message-block');
        message_block_modal.empty();
        message_block.empty();

        let data = new FormData();
        data.append('username', $('#new_username').val());
        data.append('email', $('#new_email').val());
        data.append('text', $('#new_text').val());

        $.ajax({
            type: "POST",
            url: '/api/create-task',
            data: data,
            dataType: 'json',
            contentType: false,
            processData: false,
            success: function(result) {
                $('#taskCreateModal').find('button.close').click();
                task_list.DataTable().order([ 0, "desc" ]);
                task_list.DataTable().ajax.reload();
                message_block.append('<div class="alert alert-success" role="alert">'
                    + 'Новая задача успешно создана!'
                    + '</div>');
            },
            error: function (error) {
                if(error.status == 401)
                    location="/login";
                let message = (error.responseJSON['message'])
                    ? error.responseJSON['message']
                    : error.status +' :'+ error.statusText;
                message_block_modal.append('<div class="alert alert-danger" role="alert">'+ message+ '</div>');
            }
        });
    } );

    // Preview task
    $(document).on('click', '.task_preview', function(e){
        e.preventDefault();

        let task_preview = $('#taskPreviewModal');
        task_preview.find('.username').text($('#new_username').val());
        task_preview.find('.email').text($('#new_email').val());
        task_preview.find('.text').text($('#new_text').val());
    });

    // Edit task
    task_list.on('click', '.task_edit', function(e){
        e.preventDefault();

        let message_block = $('#content>.message-block');
        message_block.empty();

        let task_id = $(this).parent().data('id');
        let task = $(this).parents('tr');
        let button1 = $(this);
        let button2 = button1.next();
        let button3 = button2.next();
        let text_el = task.find('.text');
        let status_el = task.find('.status');

        let old_values = {
            text: text_el.text(),
            status: status_el.find('span').data('value'),
        };

        let checked = old_values['status'] ? 'checked' : '';

        let height = text_el.parent().height();
        let width = text_el.width();
        text_el.hide();
        text_el.after('<textarea class="form-control" style="min-height: '+height+'px; min-width: '+width+'px">'+old_values.text+'</textarea>');
        status_el.find('span').hide();
        status_el.append('<div class="form-check">'+
            '<input class="form-check-input" type="checkbox" value="'+task_id+'" id="status_'+task_id+'" '+checked+'>'+
            '<label class="form-check-label" for="status_'+task_id+'">Выполнено</label>'+
            '</div>');
        button1.hide();
        button2.show();
        button3.show();

    });

    task_list.on('click', '.task_edit_cancel', function(e){
        e.preventDefault();

        let message_block = $('#content>.message-block');
        message_block.empty();

        let task = $(this).parents('tr');
        let button3 = $(this);
        let button2 = button3.prev();
        let button1 = button2.prev();
        let text_el = task.find('.text');
        let status_el = task.find('.status');

        text_el.next().remove();
        text_el.show();
        status_el.find('.form-check').remove();
        status_el.find('span').show();
        button3.hide();
        button2.hide();
        button1.show();

    });

    task_list.on('click', '.task_edit_action', function(e){
        e.preventDefault();

        let message_block = $('#content>.message-block');
        message_block.empty();

        let task_id = $(this).parent().data('id');
        let task = $(this).parents('tr');
        let button2 = $(this);
        let button1 = button2.prev();
        let button3 = button2.next();
        let text_el = task.find('.text');
        let status_el = task.find('.status');

        let new_status = status_el.find('.form-check #status_'+task_id).is(':checked');
        let new_text = text_el.next().val();

        let data = {
            'taskId': task_id,
            'text': new_text,
            'status': new_status
        };

        $.ajax({
            type: "POST",
            url: '/api/edit-task',
            data: data,
            dataType: 'json',
            success: function(result) {
                if(result['id'] == task_id){
                    text_el.text(new_text);
                    if(result['updated_at']){
                        let updated_text = 'отредактировано администратором ('+result['updated_at']+')';
                        let updated_el = text_el.parent().find('.alert');
                        if(updated_el.length){
                            updated_el.text(updated_text);
                        }else{
                            text_el.before('<div class="alert alert-info" role="alert">'+updated_text+'</div>');
                        }
                    }
                    status_el_span = status_el.find('span');
                    status_el_span.data('value', new_status);
                    status_el_span.text(new_status==true ? 'Выполнено' : 'Не выполнено');
                    if(new_status==true){
                        status_el_span.removeClass('badge-warning');
                        status_el_span.addClass('badge-success');
                    }else{
                        status_el_span.removeClass('badge-success');
                        status_el_span.addClass('badge-warning');
                    }
                }
            },
            error: function (error) {
                if(error.status == 401)
                    location="/login";
                let message = (error.responseJSON['message'])
                    ? error.responseJSON['message']
                    : error.status +' :'+ error.statusText;
                message_block.append('<div class="alert alert-danger" role="alert">'+ message+ '</div>');
                $('body,html').animate({
                    scrollTop: 0
                }, 200);
            }
        });

        text_el.next().remove();
        text_el.show();
        status_el.find('.form-check').remove();
        status_el.find('span').show();
        button3.hide();
        button2.hide();
        button1.show();
    });

    // Task list
    let aoColumns = [
        {
            "mData":"id"
        },
        {
            "mData": "username"
        },
        {
            "mData": "email"
        },
        {
            "mData": "text",
            "mRender": function (text, type, row) {
                let updated = row['updated_at'] ? '<div class="alert alert-info" role="alert">отредактировано администратором ('+row['updated_at']+')</div>' : '';
                return  updated + '<div class="text">'+text+'<div/>';
            },
            "orderable": false
        },
        {
            "mData":"status",
            "sClass": "status",
            "mRender": function(status){
                return '<span data-value="'+status+'" class="badge ' + (status==true ? 'badge-success' : 'badge-warning') + '">' + (status==true ? 'Выполнено' : 'Не выполнено') + '</span>';
            }
        }
    ];
    if(is_admin){
        aoColumns.push({
            "mData":"id",
            "mRender": function(id){
                return '<div data-id="'+id+'">'+
                    '<button class="btn btn-primary btn-block task_edit">' + 'Изменить' + '</button>'+
                    '<button class="btn btn-primary btn-block task_edit_action" style="display:none">' + 'Применить' + '</button>'+
                    '<button class="btn btn-secondary btn-block task_edit_cancel" style="display:none">' + 'Отменить' + '</button>'+
                    '<div/>';
            },
            "orderable": false
        });
    }
    task_list.dataTable({
        "iDisplayLength": 3,
        "aLengthMenu": [[3, 5, 10, 20, 50, 100],[3, 5, 10, 20, 50, 100]],
        "order": [[ 0, "desc" ]],
        "deferRender": true,
        "stateSave": true,
        "processing": true,
        "serverSide": true,
        "ajaxSource": "/api/action-task",
        "serverMethod": "post",
        "aoColumns": aoColumns
    });

} );
