<?php /**
 * @name: Handler
 * @author Andrey and_y87 Kidin
 * @description Данные для тестовых запросов
 * @homepage: https://github.com/andy87/Handler
 * @license CC BY-SA 4.0 http://creativecommons.org/licenses/by-sa/4.0/
 * @date 2024-05-27
 * @version 1.0.2
 */

declare(strict_types=1);

namespace andy87\knock_knock\tests\helpers;

use andy87\knock_knock\lib\Method;
use andy87\knock_knock\core\{ Handler, Request };
use andy87\knock_knock\interfaces\RequestInterface;
use andy87\knock_knock\exception\{ handler\InvalidMethodException, request\StatusNotFoundException };
use andy87\knock_knock\exception\{ ParamNotFoundException, ParamUpdateException, InvalidEndpointException, InvalidHostException };

/**
 * Class PostmanEcho
 *
 * @package tests\core
 *
 * Fix not used:
 * - @see PostmanEcho::ENDPOINT_BASIC_AUTH
 * - @see PostmanEcho::constructRequestMethodPut()
 * - @see PostmanEcho::constructRequestMethodPatch()
 * - @see PostmanEcho::constructRequestMethodDelete()
 */
abstract class PostmanEcho
{
    public const PROTOCOL = 'https';

    public const HOST = 'postman-echo.com';


    /**
     * https://www.postman.com/postman/request/631643-078883ea-ac9e-842e-8f41-784b59a33722
     */
    public const ENDPOINT_GET = '/get';

    /**
     * Raw Text https://www.postman.com/postman/request/631643-1eb1cf9d-2be7-4060-f554-73cd13940174
     * Form Data https://www.postman.com/postman/request/631643-083e46e7-53ea-87b1-8104-f8917ce58a17
     */
    public const ENDPOINT_POST = '/post';

    /**
     *  https://www.postman.com/postman/request/631643-12c51acc-50d2-2d9b-10d6-cc80e3a10d70
     */
    public const ENDPOINT_PUT = '/put';

    /**
     *  https://www.postman.com/postman/request/631643-8c53212f-42cd-cb37-6e02-08c47a7c8bb1
     */
    public const ENDPOINT_PATCH = '/patch';

    /**
     * https://www.postman.com/postman/request/631643-1f0fad16-6bff-5130-2056-7f4af6b18912
     */
    public const ENDPOINT_DELETE = '/delete';

    /**
     * Basic Auth https://www.postman.com/postman/request/631643-42c867ca-e72b-3307-169b-26a478b00641
     */
    public const ENDPOINT_BASIC_AUTH = '/basic-auth';

    /** @var array  */
    public const DATA = [
        23 => 'ab',
        'a' => 2,
        'b' => 3,
        'ab' => 23,
    ];

    public const GET_KEY_ARGS = 'args';
    public const GET_KEY_DATA = 'data';
    public const GET_KEY_FILES = 'files';
    public const GET_KEY_FORM = 'form';
    public const GET_KEY_HEADERS = 'headers';
    public const GET_KEY_JSON = 'json';
    public const GET_KEY_URL = 'url';


    /** @var Handler $handler */
    public static Handler $handler;



    /**
     * Возвращает объект `Handler` для работы с запросами к `postman-echo.com`
     *
     * @return Handler
     *
     * @throws InvalidHostException|ParamNotFoundException|StatusNotFoundException|ParamUpdateException
     *
     */
    public static function getHandlerInstance(): Handler
    {
        if ( !isset(self::$handler) )
        {
            self::$handler = new Handler( self::HOST, [
                RequestInterface::SETUP_PROTOCOL  => self::PROTOCOL,
                RequestInterface::SETUP_CURL_OPTIONS => [
                    CURLOPT_HEADER => false,
                    CURLOPT_RETURNTRANSFER => true
                ]
            ]);

            self::$handler->disableSSL();
        }

        return self::$handler;
    }


    /**
     * Общий метод который возвращает объект `Request` для работы с запросами к `postman-echo.com`
     *
     * @param string $method
     * @param string $endpoint
     * @param array $params
     *
     * @return Request
     *
     * @throws InvalidHostException|ParamNotFoundException|StatusNotFoundException|ParamUpdateException|InvalidEndpointException|InvalidMethodException
     */
    public static function constructRequest( string $method, string $endpoint, array $params = [] ): Request
    {
        return self::getHandlerInstance()
            ->constructRequest( $method, $endpoint, $params );
    }

    /**
     * Возвращает объект `Request` для GET запроса
     *
     * @param array $params
     *
     * @return Request
     *
     * @throws InvalidHostException|ParamNotFoundException|StatusNotFoundException|ParamUpdateException|InvalidEndpointException|InvalidMethodException
     */
    public static function constructRequestMethodGet( array $params = [] ): Request
    {
        return self::constructRequest( Method::GET, self::ENDPOINT_GET, $params );
    }

    /**
     * Возвращает объект `Request` для POST запроса
     *
     * @param array $params
     *
     * @return Request
     *
     * @throws InvalidHostException|ParamNotFoundException|StatusNotFoundException|ParamUpdateException|InvalidEndpointException|InvalidMethodException
     */
    public static function constructRequestMethodPost( array $params = [] ): Request
    {
        return self::constructRequest( Method::POST, self::ENDPOINT_POST, $params );
    }

    /**
     * Возвращает объект `Request` для PUT запроса
     *
     * @param array $params
     *
     * @return Request
     *
     * @throws InvalidHostException|ParamNotFoundException|StatusNotFoundException|ParamUpdateException|InvalidEndpointException|InvalidMethodException
     */
    public static function constructRequestMethodPut( array $params = [] ): Request
    {
        return self::constructRequest( Method::PUT, self::ENDPOINT_PUT, $params );
    }

    /**
     * Возвращает объект `Request` для PATCH запроса
     *
     * @param array $params
     *
     * @return Request
     *
     * @throws InvalidHostException|ParamNotFoundException|StatusNotFoundException|ParamUpdateException|InvalidEndpointException|InvalidMethodException
     */
    public static function constructRequestMethodPatch( array $params = [] ): Request
    {
        return self::constructRequest( Method::PATCH, self::ENDPOINT_PATCH, $params );
    }

    /**
     * Возвращает объект `Request` для DELETE запроса
     *
     * @param array $params
     *
     * @return Request
     *
     * @throws InvalidHostException|ParamNotFoundException|StatusNotFoundException|ParamUpdateException|InvalidEndpointException|InvalidMethodException
     */
    public static function constructRequestMethodDelete( array $params = [] ): Request
    {
        return self::constructRequest( Method::DELETE, self::ENDPOINT_DELETE, $params );
    }
}