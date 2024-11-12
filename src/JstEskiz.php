<?php

namespace Jscorptech;

use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

class JstEskiz
{
    private const GET = 'GET';
    private const POST = 'POST';
    private const PATCH = 'PATCH';
    private const CONTACT = 'contact';

    private mixed $api_url;
    private mixed $email;
    private mixed $password;
    private mixed $callback_url;
    private array $headers;
    private array $methods;

    public function __construct($email, $password, $callback_url = null, $api_url = null,)
    {
        $this->api_url = $api_url ?? "https://notify.eskiz.uz/api/";
        $this->email = $email;
        $this->password = $password;
        $this->callback_url = $callback_url;
        $this->headers = [];

        $this->methods = [
            'auth_user'    => 'auth/user',
            'auth_login'   => 'auth/login',
            'auth_refresh' => 'auth/refresh',
            'send_message' => 'message/sms/send',
        ];
    }

    /**
     * @throws GuzzleException
     */
    public function request($api_path, $data = null, $method, $headers = null)
    {
        $incoming_data = ['status' => 'error'];
        $req_data = [
            'form_params' => $data,
            'headers'     => $headers,
        ];

        try {
            $client = new Client(['base_uri' => $this->api_url]);
            $response = $client->request($method, $api_path, $req_data);

            if ($api_path == $this->methods['auth_refresh']) {
                if ($response->getStatusCode() == 200) {
                    $incoming_data['status'] = 'success';
                }
            } else {
                $incoming_data = json_decode($response->getBody()->getContents(), true);
            }
        } catch (Exception $error) {
            throw new Exception($error->getMessage());
        }

        return $incoming_data;
    }

    /**
     * @throws GuzzleException
     */
    public function auth()
    {
        $data = [
            'email'    => $this->email,
            'password' => $this->password,
        ];

        return $this->request($this->methods['auth_login'], $data, self::POST);
    }

    /**
     * @throws GuzzleException
     */
    public function refreshToken()
    {
        $token = $this->auth()['data']['token'];
        $this->headers['Authorization'] = 'Bearer ' . $token;

        $context = [
            'headers'  => $this->headers,
            'method'   => self::PATCH,
            'api_path' => $this->methods['auth_refresh'],
        ];

        return $this->request($context['api_path'], null, $context['method'], $context['headers']);
    }

    /**
     * @throws GuzzleException
     */
    public function getMyUserInfo()
    {
        $token = $this->auth()['data']['token'];
        $this->headers['Authorization'] = 'Bearer ' . $token;

        $data = [
            'headers'  => $this->headers,
            'method'   => self::GET,
            'api_path' => $this->methods['auth_user'],
        ];

        return $this->request($data['api_path'], null, $data['method'], $data['headers']);
    }

    /**
     * @throws GuzzleException
     */
    public function addSmsContact($first_name, $phone_number, $group)
    {
        $token = $this->auth()['data']['token'];
        $this->headers['Authorization'] = 'Bearer ' . $token;

        $data = [
            'name'         => $first_name,
            'email'        => $this->email,
            'group'        => $group,
            'mobile_phone' => $phone_number,
        ];

        $context = [
            'headers'  => $this->headers,
            'method'   => self::POST,
            'api_path' => self::CONTACT,
            'data'     => $data,
        ];

        return $this->request($context['api_path'], $context['data'], $context['method'], $context['headers']);
    }

    /**
     * @throws GuzzleException
     */
    public function sendSms($phone_number, $message)
    {
        $token = $this->auth()['data']['token'];

        $this->headers['Authorization'] = 'Bearer ' . $token;

        $data = [
            'from'         => 4546,
            'mobile_phone' => $phone_number,
            'callback_url' => $this->callback_url,
            'message'      => $message,
        ];

        $context = [
            'headers'  => $this->headers,
            'method'   => self::POST,
            'api_path' => $this->methods['send_message'],
            'data'     => $data,
        ];

        return $this->request($context['api_path'], $context['data'], $context['method'], $context['headers']);
    }
}
