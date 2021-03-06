<?php
/*
    This is the database connection information.
    Please do not commit this to a git repository.
*/
namespace TRIIC;
use Silex\Application;
use Silex\ServiceProviderInterface;
use Silex\Provider\DoctrineServiceProvider;

class DoctrineConnection implements ServiceProviderInterface
{
    public function register(Application $app)
    {
        $dbConnection = array(
                    'driver'   => 'pdo_mysql',
                    'host' => 'localhost',
                    'user' => 'root',
                    'password' => '',
                    'dbname' => 'triic'
                    );

        $app['local'] = false;
        
        $app->register(new \Silex\Provider\DoctrineServiceProvider(), array(
            'db.options' => $dbConnection
        ));
    }
    public function boot(Application $app)
    {
    }
}