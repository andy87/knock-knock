<?php /**
 * @name: KnockKnock
 * @author Andrey and_y87 Kidin
 * @description Данные для тестовых запросов
 * @homepage: https://github.com/andy87/KnockKnock
 * @license CC BY-SA 4.0 http://creativecommons.org/licenses/by-sa/4.0/
 * @date 2024-05-23
 * @version 0.99c
 */

declare(strict_types=1);

namespace andy87\knock_knock\tests\core;

use andy87\knock_knock\core\KnockKnock;
use andy87\knock_knock\core\KnockRequest;
use andy87\knock_knock\interfaces\KnockRequestInterface;
use andy87\knock_knock\lib\LibKnockMethod;
use Exception;

/**
 * Class PostmanEcho
 *
 * @package tests\core
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


    /** @var KnockKnock $knockKnock */
    public static KnockKnock $knockKnock;



    /**
     * Возвращает объект `KnockKnock` для работы с запросами к `postman-echo.com`
     *
     * @return KnockKnock
     *
     * @throws Exception
     */
    public static function getKnockKnockInstance(): KnockKnock
    {
        if ( !isset(self::$knockKnock) )
        {
            self::$knockKnock = new KnockKnock( self::HOST, [
                KnockRequestInterface::SETUP_PROTOCOL  => self::PROTOCOL,
                KnockRequestInterface::SETUP_CURL_OPTIONS => [
                    CURLOPT_HEADER => false,
                    CURLOPT_RETURNTRANSFER => true
                ]
            ]);

            self::$knockKnock->disableSSL();
        }

        return self::$knockKnock;
    }


    /**
     * Общий метод который возвращает объект `KnockRequest` для работы с запросами к `postman-echo.com`
     *
     * @param string $method
     * @param string $endpoint
     * @param array $params
     *
     * @return KnockRequest
     *
     * @throws Exception
     */
    public static function constructKnockRequest( string $method, string $endpoint, array $params = [] ): KnockRequest
    {
        return self::getKnockKnockInstance()
            ->constructRequest( $method, $endpoint, $params );
    }

    /**
     * Возвращает объект `KnockRequest` для GET запроса
     *
     * @param array $params
     *
     * @return KnockRequest
     *
     * @throws Exception
     */
    public static function constructKnockRequestMethodGet( array $params = [] ): KnockRequest
    {
        return self::constructKnockRequest( LibKnockMethod::GET, self::ENDPOINT_GET, $params );
    }

    /**
     * Возвращает объект `KnockRequest` для POST запроса
     *
     * @param array $params
     *
     * @return KnockRequest
     *
     * @throws Exception
     */
    public static function constructKnockRequestMethodPost( array $params = [] ): KnockRequest
    {
        return self::constructKnockRequest( LibKnockMethod::POST, self::ENDPOINT_POST, $params );
    }

    /**
     * Возвращает объект `KnockRequest` для PUT запроса
     *
     * @param array $params
     *
     * @return KnockRequest
     *
     * @throws Exception
     */
    public static function constructKnockRequestMethodPut( array $params = [] ): KnockRequest
    {
        return self::constructKnockRequest( LibKnockMethod::PUT, self::ENDPOINT_PUT, $params );
    }

    /**
     * Возвращает объект `KnockRequest` для PATCH запроса
     *
     * @param array $params
     *
     * @return KnockRequest
     *
     * @throws Exception
     */
    public static function constructKnockRequestMethodPatch( array $params = [] ): KnockRequest
    {
        return self::constructKnockRequest( LibKnockMethod::PATCH, self::ENDPOINT_PATCH, $params );
    }

    /**
     * Возвращает объект `KnockRequest` для DELETE запроса
     *
     * @param array $params
     *
     * @return KnockRequest
     *
     * @throws Exception
     */
    public static function constructKnockRequestMethodDelete( array $params = [] ): KnockRequest
    {
        return self::constructKnockRequest( LibKnockMethod::DELETE, self::ENDPOINT_DELETE, $params );
    }


}