<?php

$app->group('/admin', function() use ($app, $settings, $isLogged, $authenticate) {
  $app->get('/login/', $isLogged($app, $settings), function() use ($app) {
    $flash = $app->view()->getData('flash');
    $error = isset($flash['error']) ? $flash['error'] : '';
    $app->render('admin/login.html', array('error' => $error));
  });

  $app->post('/login', function() use ($app, $settings) {
    $email = $app->request->post('email');
    $password = hash('sha512', $app->request->post('password'));
    $user = Users::whereRaw('email = ? AND password = ? AND active = true', array($email, $password))->first();
    if ($user->count() != 0) {
      $_SESSION['user'] = $user;
      $app->redirect($settings->base_url . '/admin');
    } else {
      $app->flash('error', 'login');
      $app->redirect($settings->base_url . '/admin/login');
    }
  });

  $app->get('/logout/', $authenticate($app, $settings), function() use ($app, $settings) {
    unset($_SESSION['user']);
    $app->view()->setData('user', null);
    $app->redirect($settings->base_url);
  });

  $app->get('/', $authenticate($app, $settings), function() use ($app) {
    $app->render('admin/index.html', array('user' => $_SESSION['user']));
  });

  $app->get('/settings/', $authenticate($app, $settings), function() use ($app) {
    $flash = $app->view()->getData('flash');
    $error = isset($flash['error']) ? $flash['error'] : '';
    $paths = glob(TEMPLATEDIR . '*' , GLOB_ONLYDIR);
    $dirs = array();
    foreach ($paths as $path) {
      $a = explode(DS, $path);
      $dirs[] = end($a);
    }
    $l = glob(LANGUAGEDIR . '*.php');
    $langs = array();
    foreach ($l as $lang) {
      $a = explode('.', $lang);
      $b = explode(DS, $a[0]);
      $langs[] = end($b);
    }
    $app->render('admin/settings.html', array('error' => $error, 'dirs' => $dirs, 'langs' => $langs));
  });

  $app->post('/settings/update', function() use ($app, $settings) {
    $base_url = $app->request->post('base_url');
    $template = $app->request->post('template');
    $language = $app->request->post('language');
    if ($base_url == '') {
      $app->flash('error', 'base_url');
      $app->redirect($settings->base_url . '/admin/settings');
    }
    if ($template == '') {
      $app->flash('error', 'template');
      $app->redirect($settings->base_url . '/admin/settings');
    }
    if ($language == '') {
      $app->flash('error', 'language');
      $app->redirect($settings->base_url . '/admin/settings');
    }
    $redirect = $settings->base_url . '/admin/settings';
    Settings::where('id', '=', 1)->update(array('base_url' => $base_url, 'template' => $template, 'language' => $language));
    $app->render('admin/success.html', array('redirect' => $redirect));
  });

  $app->get('/users/', $authenticate($app, $settings), function() use ($app) {
    $users = Users::orderBy('created_at', 'asc')->get();
    $app->render('admin/users.html', array('users' => $users));
  });

  $app->post('/users/new', $authenticate($app, $settings), function() use ($app, $settings) {
    $email = $app->request->post('email');
    $username = $app->request->post('username');
    $password = hash('sha512', $app->request->post('password'));
    $created_at = date('Y-m-d H:i:s');
    if ($email == '' OR !filter_var($email, FILTER_VALIDATE_EMAIL)) {
      $app->flash('error', 'email');
      $app->redirect($settings->base_url . '/admin/users/new');
    }
    if ($username == '') {
      $app->flash('error', 'username');
      $app->redirect($settings->base_url . '/admin/users/new');
    }
    if ($password == '') {
      $app->flash('error', 'password');
      $app->redirect($settings->base_url . '/admin/users/new');
    }
    $redirect = $settings->base_url . '/admin/users';
    Users::insert(array('username' => $username, 'password' => $password, 'email' => $email, 'created_at' => $created_at));
    $app->render('admin/success.html', array('redirect' => $redirect));
  });

  $app->get('/users/edit/:id', $authenticate($app, $settings), function($id) use ($app) {
    $flash = $app->view()->getData('flash');
    $error = isset($flash['error']) ? $flash['error'] : '';
    $u = Users::where('id', '=', $id)->first();
    $app->render('admin/user_edit.html', array('u' => $u, 'error' => $error));
  })->conditions(array('id' => '\d+'));

  $app->post('/users/edit/:id', $authenticate($app, $settings), function($id) use ($app, $settings) {
    $email = $app->request->post('email');
    $username = $app->request->post('username');
    $pass = $app->request->post('password');
    $password = hash('sha512', $pass );
    if ($email == '' OR !filter_var($email, FILTER_VALIDATE_EMAIL)) {
      $app->flash('error', 'email');
      $app->redirect($settings->base_url . '/admin/users/new');
    }
    if ($username == '') {
      $app->flash('error', 'username');
      $app->redirect($settings->base_url . '/admin/users/new');
    }
    $redirect = $settings->base_url . '/admin/users';
    if (!empty($pass)) {
      Users::where('id', '=', $id)->update(array('username' => $username, 'password' => $password, 'email' => $email));
    } else {
      Users::where('id', '=', $id)->update(array('username' => $username, 'email' => $email));
    }
    $app->render('admin/success.html', array('redirect' => $redirect));
  })->conditions(array('id' => '\d+'));

  $app->get('/users/delete/:id', $authenticate($app, $settings), function($id) use ($app) {
    $app->render('admin/user_delete.html', array('user_id' => $id));
  })->conditions(array('id' => '\d+'));

  $app->delete('/users/delete/:id', $authenticate($app, $settings), function($id) use ($app, $settings) {
    Users::destroy($id);
    $redirect = $settings->base_url . '/admin/users';
    $app->render('admin/success.html', array('redirect' => $redirect));
  })->conditions(array('id' => '\d+'));

  $app->get('/users/new/', $authenticate($app, $settings), function() use ($app) {
    $flash = $app->view()->getData('flash');
    $error = isset($flash['error']) ? $flash['error'] : '';
    $app->render('admin/user_new.html', array('error' => $error));
  });

  $app->get('/users/activate/:id', $authenticate($app, $settings), function($id) use ($app, $settings) {
    $user = User::where('id', '=', $id)->first();
    if ($user) {
      $redirect = $settings->base_url . '/admin/users';
      $user->update(array('active' => 'true'));
      $app->render('admin/success.html', array('redirect' => $redirect));
    } else {
      $app->render('admin/404_user.html');
    }
  })->conditions(array('id' => '\d+'));

  $app->get('/users/deactivate/:id', $authenticate($app, $settings), function($id) use ($app, $settings) {
    $user = User::where('id', '=', $id)->first();
    if ($user) {
      $redirect = $settings->base_url . '/admin/users';
      $user->update(array('active' => 'false'));
      $app->render('admin/success.html', array('redirect' => $redirect));
    } else {
      $app->render('admin/404_user.html');
    }
  })->conditions(array('id' => '\d+'));
});
