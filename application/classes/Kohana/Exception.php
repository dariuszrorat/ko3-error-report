<?php

defined('SYSPATH') OR die('No direct script access.');

class Kohana_Exception extends Kohana_Kohana_Exception
{

    public static function _handler(Exception $e)
    {
        try
        {
            // Log the exception
            Kohana_Exception::log($e);
            // Use only in production environment
            if (Kohana::$environment === Kohana::PRODUCTION)
            {
                // Send warning mail
                Kohana_Exception::send_mail($e);
                // Generate the response
                $response = Kohana_Exception::response($e);
            } else
            {
                $response = Kohana_Kohana_Exception::response($e);
            }

            return $response;
        } catch (Exception $e)
        {
            ob_get_level() AND ob_clean();
            header('Content-Type: text/plain; charset=' . Kohana::$charset, TRUE, 500);
            echo Kohana_Exception::text($e);
            exit(1);
        }
    }

    public static function send_mail($e)
    {
        $class = get_class($e);
        $code = $e->getCode();
        $message = $e->getMessage();
        $file = $e->getFile();
        $line = $e->getLine();
        $trace = $e->getTrace();

        if ($e instanceof HTTP_Exception AND $trace[0]['function'] == 'factory')
        {
            extract(array_shift($trace));
        }


        if ($e instanceof ErrorException)
        {
            if (function_exists('xdebug_get_function_stack') AND $code == E_ERROR)
            {
                $trace = array_slice(array_reverse(xdebug_get_function_stack()), 4);

                foreach ($trace as & $frame)
                {
                    if (!isset($frame['type']))
                    {
                        $frame['type'] = '??';
                    }

                    if ('dynamic' === $frame['type'])
                    {
                        $frame['type'] = '->';
                    } elseif ('static' === $frame['type'])
                    {
                        $frame['type'] = '::';
                    }

                    if (isset($frame['params']) AND ! isset($frame['args']))
                    {
                        $frame['args'] = $frame['params'];
                    }
                }
            }

            if (isset(Kohana_Exception::$php_errors[$code]))
            {
                $code = Kohana_Exception::$php_errors[$code];
            }
        }

        if (
                defined('PHPUnit_MAIN_METHOD')
                OR
                defined('PHPUNIT_COMPOSER_INSTALL')
                OR
                defined('__PHPUNIT_PHAR__')
        )
        {
            $trace = array_slice($trace, 0, 2);
        }

        $error_template = View::factory('template/email/error', get_defined_vars());

        $subject = 'ATTENTION: ERROR on <Your app> occurred.';
        // You must define USE_SWIFTMAILER on index.php
        // define('USE_SWIFTMAILER', True);
        // or false if You use mail() function
        if (defined('USE_SWIFTMAILER') && USE_SWIFTMAILER)
        {
            $email = Email::factory($subject, $error_template, 'text/html')
                    ->to('your_mail')
                    ->from('sender_mail')
                    ->send();
        } else
        {
            $headers = 'MIME-Version: 1.0' . "\r\n";
            $headers .= 'Content-type: text/html; charset=utf-8' . "\r\n";
            $headers .= 'From: sender_mail' . "\r\n";
            mail("your_mail", $subject, $error_template, $headers);
        }
    }

    public static function response(Exception $e)
    {
        $response = Response::factory();
        $response->status(($e instanceof HTTP_Exception) ? $e->getCode() : 500);
        $response->headers('Content-Type', Kohana_Exception::$error_view_content_type . '; charset=' . Kohana::$charset);
        $response->body(View::factory('error/default'));
        return $response;
    }

}
