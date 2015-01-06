<?php

namespace TRIIC\Controller;

use Silex\Application;
use Silex\ControllerProviderInterface;

class BookingController implements ControllerProviderInterface
{
    public function connect(Application $app)
    {
        // creates a new controller based on the default route
        $controllers = $app['controllers_factory'];



        $controllers->get('/', function (Application $app) {
            return $app['twig']->render('booking/product.html', array(
            	'products' => $app['db']->fetchAll("SELECT * FROM products")
        	));
        })->bind('booking_home');


        /* API */

        $controllers->get('/v1/dates/{mkuid}', function (Application $app, $mkuid) {
            return json_encode($app['db']->fetchAll("SELECT `booking_from`, `booking_to` FROM bookings where approved = 1 and mkuid = ?", array($mkuid  )));
        });

        /* PAGES */






        $controllers->get('/p/{pid}', function ($pid, Application $app) {
            return $app['twig']->render('booking/mku.html', array(
            	'mkus' => $app['db']->fetchAll("SELECT * FROM product_mku WHERE pid = ?", array($pid))
        	));
        });


        $controllers->get('/login', function (Application $app) {
            if ($app['triic.user']->auth && $app['request']->get('stay') != 'true') {
                return $app->redirect($app['url_generator']->generate('booking_home').$app['request']->get('mkuid'));
            }
            if (!($details = $app['db']->fetchAssoc("SELECT * FROM product_mku m INNER JOIN products p ON p.pid = m.pid WHERE mkuid = ?", array($app['request']->get('mkuid'))))) {
                $app->abort(404, "Item $mkuid does not exist.");
            }
            return $app['twig']->render('booking/login.html', array('pid' => $details['pid']));
        });
        $controllers->post('/login', function (Application $app) {
            if ($app['triic.user']->auth && $app['request']->get('stay') != 'true') {
                return $app->redirect($app['url_generator']->generate('booking_home').$app['request']->get('mkuid'));
            }

            $uname = $app['request']->get('uname');
            $passw = $app['request']->get('passw');

            if ($app['triic.user']->login($uname, $passw)) {
                return $app->redirect($app['url_generator']->generate('booking_home').$app['request']->get('mkuid'));
            } else {
                $error = "Invalid Username/Password";
            }

            return $app['twig']->render('booking/login.html', array(
                'error' => $error
            ));
        });

        $controllers->get('/thanks', function (Application $app) {
            return $app['twig']->render('booking/thanks.html', array(
            ));
        });





        $controllers->get('/{mkuid}', function ($mkuid, Application $app) {
            if (!($details = $app['db']->fetchAssoc("SELECT * FROM product_mku m INNER JOIN products p ON p.pid = m.pid WHERE mkuid = ?", array($mkuid)))) {
                $app->abort(404, "Item $mkuid does not exist.");
            }
            if ($app['triic.user']->auth !== true) {
                return $app->redirect($app['url_generator']->generate('booking_home').'login?mkuid='.$mkuid);
            }
            return $app['twig']->render('booking/form.html', array(
            	'details' => $details
        	));
        });
        $controllers->post('/{mkuid}', function ($mkuid, Application $app) {
            $details = $app['db']->fetchAssoc("SELECT * FROM product_mku m INNER JOIN products p ON p.pid = m.pid WHERE mkuid = ?", array($mkuid));
            if (!$details) {
                $app->abort(404, "Item $mkuid does not exist.");
            }
            if ($app['triic.user']->auth !== true) {
                return $app->redirect($app['url_generator']->generate('booking_home').'login?mkuid='.$mkuid);
            }

            $details['start_date'] = $app['request']->get('start_date');
            $details['end_date'] = $app['request']->get('end_date');

            if(!checkdate(
                substr($details['start_date'], 5,2),
                substr($details['start_date'], 8,2),
                substr($details['start_date'], 0,4)) 
                || !checkdate(
                substr($details['end_date'], 5,2),
                substr($details['end_date'], 8,2),
                substr($details['end_date'], 0,4))){
                $error = "Invalid/Missing Dates";
            }

            $starttime = strtotime($details['start_date']);
            $endtime = strtotime($details['end_date']);

            if ($starttime > $endtime) {
                $error = "End time must be after Start time!";
            }

            if ($starttime < time()) {
                $error = "Start time cannot be in the past!";
            }


            if ($app['db']->fetchAll("SELECT bid FROM bookings WHERE ((`booking_to` >= ? AND `booking_to` <= ?) OR (`booking_from` >= ? AND `booking_from` <= ?)) AND mkuid = ? AND approved = 1",array($details['start_date'],$details['end_date'],$details['start_date'],$details['end_date'],$mkuid))) {
                $error = "Item is booked for selected time region.";
            }

            

            if (!isset($error)) {
                $details['user'] = $app['triic.user']->username;
                $details['userfname'] = $app['triic.user']->getFullName();
                if ($app['local'] !== true) {
                    $mailer = $app['twig']->render('email/new_booking.html', array('details'=>$details));

                    $headers  = "From: TriIC Admin<triathlon@imperial.ac.uk>\r\n";
                    $headers .= "Content-type: text/html\r\n";
                    mail('dm1911@ic.ac.uk', 'TriIC - New Booking', $mailer, $headers);
                    mail('ruben.tomlin11@ic.ac.uk', 'TriIC - New Booking', $mailer, $headers);
                    mail('triathlon@ic.ac.uk', 'TriIC - New Booking', $mailer, $headers);
                }
                
                $app['db']->insert('bookings', array(
                    'mkuid' => $mkuid,
                    'username' => $app['triic.user']->username,
                    '`booking_from`' => $details['start_date'],
                    '`booking_to`' => $details['end_date'],
                    'approved' => 1
                ));
                return $app->redirect($app['url_generator']->generate('booking_home').'thanks');
            }

            return $app['twig']->render('booking/form.html', array(
                'details' => $details,
                'error' => @$error
            ));
        });











        return $controllers;
    }
}