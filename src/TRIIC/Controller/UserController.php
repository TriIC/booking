<?php

namespace TRIIC\Controller;

use Silex\Application;
use Silex\ControllerProviderInterface;

class UserController implements ControllerProviderInterface
{
    public function connect(Application $app)
    {
        // creates a new controller based on the default route
        $controllers = $app['controllers_factory'];



        $controllers->get('/', function (Application $app) {
            if ($app['triic.user']->auth !== true) {
                return $app->redirect($app['url_generator']->generate('user_home').'login');
            }
            return $app['twig']->render('account/list.html', array(
            	'bookings' => $app['db']->fetchAll("SELECT * FROM bookings b INNER JOIN product_mku m ON m.mkuid = b.mkuid INNER JOIN products p ON m.pid = p.pid WHERE username = ?", array($app['triic.user']->username))
        	));
        })->bind('user_home');






        $controllers->get('/login', function (Application $app) {
            if ($app['triic.user']->auth && $app['request']->get('stay') != 'true') {
                return $app->redirect($app['url_generator']->generate('user_home'));
            }
            return $app['twig']->render('account/login.html');
        });
        $controllers->post('/login', function (Application $app) {
            if ($app['triic.user']->auth && $app['request']->get('stay') != 'true') {
                return $app->redirect($app['url_generator']->generate('user_home'));
            }

            $uname = $app['request']->get('uname');
            $passw = $app['request']->get('passw');

            if ($app['triic.user']->login($uname, $passw)) {
                return $app->redirect($app['url_generator']->generate('user_home'));
            } else {
                $error = "Invalid Username/Password";
            }

            return $app['twig']->render('account/login.html', array(
                'error' => $error
            ));
        });




        return $controllers;
    }
}