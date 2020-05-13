<div class="row login-form">
    <div class="col-md-12">
        <?php if (isset($data['message'])): ?>
            <div class="alert alert-danger" role="alert"><?=$data['message']?></div>
        <?php endif; ?>
        <form class="form-horizontal" method="POST">
            <fieldset>
                <legend><h1><small>Вход на сайт</small></h1></legend>
                <div class="form-group">
                    <label class="control-label col-sm-2" for="email">Логин:</label>
                    <div class="col-sm-10">
                        <input type="text" class="form-control" id="login" name="login" placeholder="логин или e-mail" required>
                    </div>
                </div>
                <div class="form-group">
                    <label class="control-label col-sm-2" for="pwd">Пароль:</label>
                    <div class="col-sm-10">
                        <input type="password" class="form-control" id="pwd" name="password" placeholder="пароль" required>
                    </div>
                </div>
                <div class="form-group">
                    <div class="col-sm-offset-2 col-sm-10">
                        <button type="submit" class="btn btn-primary btn-md">Войти</button>
                    </div>
                </div>
            </fieldset>
        </form>
    </div>
</div>