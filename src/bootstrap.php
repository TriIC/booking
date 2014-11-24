<?php
date_default_timezone_set("GMT");
require_once __DIR__.'/../vendor/autoload.php';
use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation;

$app = new Application();

$app->register(new TRIIC\Model\User());

$app->register(new Silex\Provider\UrlGeneratorServiceProvider());
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\NativeSessionStorage;
$storage = new NativeSessionStorage(array(), new NativeSessionStorage());
$session = new Session($storage);
$app->register(new Silex\Provider\SessionServiceProvider());
$app->register(new Silex\Provider\TwigServiceProvider(), array(
    'twig.path' => __DIR__.'/../src/views',
    'twig.options' => array('strict_variables' => false)
));


$app->register(new TRIIC\DoctrineConnection());

return $app;