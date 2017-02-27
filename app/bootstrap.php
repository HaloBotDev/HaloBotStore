<?php

/**
 * Display errors
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);

/**
 * Default timezone
 */
date_default_timezone_set('Asia/Jakarta');

/**
 * Create app
 */
$app = new \Slim\Slim(array(
  'view' => new \Slim\Views\Twig(),
  'debug' => true
));
$app->add(new \Slim\Middleware\SessionCookie(array('secret' => '_ini_rahasia_sekali_')));

// Make a new connection
use Illuminate\Database\Capsule\Manager as Capsule;
if (file_exists(APPDIR . 'config' . DS . 'database.php')) {
  $capsule = new Capsule;
  $capsule->addConnection(include APPDIR . 'config' . DS . 'database.php');
  $capsule->bootEloquent();
  $capsule->setAsGlobal();
  $app->db = $capsule;
} else {
  die('<pre>Rename `app/config/database.php.install` to `app/config/database.php` and configure your connection</pre>');
}

/**
 * Extract settings from db
 */
$settings = Settings::where('id', '=', 1)->first();
$settings->base_url = $app->request->getUrl() . $app->request->getScriptName();

/**
 * Set template directory
 */
$app->config(array(
  'templates.path' => TEMPLATEDIR . $settings->template . DS,
));

/**
 * Add some twig extensions for multilanguage support
 */
$app->view->parserExtensions = array(
  new \Slim\Views\TwigExtension(),
  new Twig_Extension_StringLoader()
);

/**
 * Get language
 */
$app->lang = require_once LANGUAGEDIR . $settings->language . '.lang.php';

/**
 * Load all libs
 */
foreach (glob(APPDIR . 'libraries' . DS . '*.php') as $filename) {
  require_once $filename;
}
