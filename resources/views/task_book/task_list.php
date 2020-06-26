<div class="row">
    <div class="col-md-12 pb-2 pt-2">
        <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#taskCreateModal">Добавить задачу</button>
    </div>
    <div class="col-md-12">

        <table id="task_list" class="table table-striped table-bordered" >
            <thead>
            <tr>
                <th>№</th>
                <th>Имя Пользователя</th>
                <th>E-mail</th>
                <th>Текст задачи</th>
                <th>Статус</th>
                <?php if (auth()): ?>
                    <th>#</th>
                <?php endif; ?>
            </tr>
            </thead>
        </table>

        <div class="modal" id="taskCreateModal"  tabindex="-1" role="dialog">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Новая задача</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <form enctype="multipart/form-data" action="">
                        <div class="modal-body">
                            <div class="message-block"></div>
                            <div class="form-group">
                                <label for="new_username">Имя</label>
                                <input type="text" class="form-control" id="new_username" name="username" placeholder="name" maxlength="40" required>
                            </div>
                            <div class="form-group">
                                <label for="new_email">E-mail</label>
                                <input type="email" class="form-control" id="new_email" name="email" placeholder="name@example.com" maxlength="40" required>
                            </div>
                            <div class="form-group">
                                <label for="new_text">Текст задачи</label>
                                <textarea class="form-control" id="new_text" name="text" rows="5" required></textarea>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-info task_preview"  data-toggle="modal" data-target="#taskPreviewModal">Просмотр</button>
                            <button type="submit" class="btn btn-primary task_create">Сохранить задачу</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="modal" tabindex="-1" role="dialog" aria-hidden="true" id="taskPreviewModal">
            <div class="modal-dialog-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Новая задача</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <table class="table table-striped table-bordered">
                            <thead>
                            <tr>
                                <th>Имя Пользователя</th>
                                <th>E-mail</th>
                                <th>Текст задачи</th>
                            </tr>
                            </thead>
                            <tbody>
                            <tr>
                                <td class="username"></td>
                                <td class="email"></td>
                                <td class="text"></td>
                            </tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Закрыть</button>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

<?php include_js('task_list');
