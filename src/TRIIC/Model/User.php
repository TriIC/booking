<?php

namespace TRIIC\Model;

use Silex\Application;
use Silex\ControllerProviderInterface;
use Silex\ServiceProviderInterface;

class User implements ServiceProviderInterface
{
    public function register(Application $app)
    {
        $app['triic.user'] = $app->share(function ($app) {
            return new TRIICUser($app);
        });
    }

    public function boot(Application $app)
    {
        $app['triic.user']->authenticate($app); //check if the user is authenticated
        $app["twig"]->addGlobal('user', $app['triic.user']);
    }
}




class TRIICUser
{
    public $auth = false;
    public $username;

    function __construct(Application $app)
    {
        $this->app = $app;
    }

    public function login($username, $password) {
        if (!function_exists('\pam_auth')) {
            function pam_auth($u, $p) {
                if (strlen($u) == 6 && strlen($p) > 1) {
                    return true;
                }
                return false;
            }
        }

        if (pam_auth($username, $password)) {
            $this->app['session']->set('username', $username);
            $this->auth = true;
            return true;
        }
        return false;
    }

    public function authenticate() {
        if ($this->app['session']->get('username')) {
            $this->username = $this->app['session']->get('username');
            $this->auth = true;
        }
    }

    public function getFullName() {
        if ($this->auth !== true) {
            return false;
        }
        if (!function_exists('\ldap_get_names')) {
            function ldap_get_names($username) {
                return array("First $username", "Last");
            }
        }
        $names = ldap_get_names($this->username);
        return $names[0] . " " . $names[1];
    }
    
}