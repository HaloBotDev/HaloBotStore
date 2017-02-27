<?php

/**
 * Define some constants
 */
define('DS', DIRECTORY_SEPARATOR);
define('ROOT', realpath(dirname(__DIR__)) . DS);
define('APPDIR', ROOT . 'app' . DS);
define('ROUTEDIR', APPDIR . 'routes' . DS);
define('VENDORDIR', ROOT . 'vendor' . DS);
define('RESOURCEDIR', ROOT . 'resources' . DS);
define('TEMPLATEDIR', RESOURCEDIR . 'templates' . DS);
define('LANGUAGEDIR', RESOURCEDIR . 'languages' . DS);

/**
 * Include autoload file
 */
if (file_exists(VENDORDIR . 'autoload.php')) {
  require_once VENDORDIR . 'autoload.php';
} else {
  die('<pre>Run `composer.phar install` in root dir</pre>');
}

/**
 * Include bootstrap file
 */
require_once APPDIR . 'bootstrap.php';

/**
 * If user is not logged in, he's redirected to login page
 *
 * @param $app
 * @param $settings
 * @return callable
 */
$authenticate = function($app, $settings) {
  return function() use ($app, $settings) {
    if (!isset($_SESSION['user'])) {
      $app->flash('error', 'Login required');
      $app->redirect($settings->base_url . '/admin/login');
    }
  };
};

/**
 * If user is logged in, he are not able to visit register page, login page and will be
 * redirected to admin home
 *
 * @param $app
 * @param $settings
 * @return callable
 */
$isLogged = function($app, $settings) {
  return function() use ($app, $settings) {
    if (isset($_SESSION['user'])) {
      $app->redirect($settings->base_url . '/admin');
    }
  };
};

/**
 * Add username and settings variable to view
 */
$app->hook('slim.before.dispatch', function() use ($app, $settings) {
  $user = isset($_SESSION['user']) ? $_SESSION['user'] : null;
  $app->view()->setData('user', $user);
  $app->view()->setData('settings', $settings);
  $app->view()->setData('lang', $app->lang);
});

/**
 * Include all files located in routes directory
 */
foreach(glob(ROUTEDIR . '*.php') as $router) {
  require_once $router;
}

/**
 * Run the application
 */
$app->run();
