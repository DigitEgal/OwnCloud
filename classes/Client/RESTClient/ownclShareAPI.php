<?php

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

/**
 * Class ShareAPI
 *
 * @author Theodor Truffer <tt@studer-raimann.ch>
 */
class ownclShareAPI
{

    const URI_SHARE_API = 'ocs/v1.php/apps/files_sharing/api/v1/shares';

    const PARAM_FORMAT_JSON = 'format=json';
    const PARAM_PATH = 'path=';

    const SHARE_TYPE_USER = 0;
    const SHARE_TYPE_GROUP = 1;
    const SHARE_TYPE_PUBLIC_LINK = 3;
    const SHARE_TYPE_FEDERATED_CLOUD_SHARE = 6;
    const PERM_TYPE_READ = 1;
    const PERM_TYPE_UPDATE = 2;
    const PERM_TYPE_CREATE = 4;
    const PERM_TYPE_DELETE = 8;
    const PERM_TYPE_READ_WRITE = 15;
    const PERM_TYPE_SHARE = 16;
    const PERM_TYPE_ALL = 31;

    /**
     * @var Client
     */
    protected $http_client;
    /**
     * @var ownclOAuth2UserToken
     */
    protected $token;


    /**
     * ownclShareAPI constructor.
     *
     * @param Client               $http_client
     * @param ownclOAuth2UserToken $token
     */
    public function __construct(Client $http_client, ownclOAuth2UserToken $token)
    {
        $this->http_client = $http_client;
        $this->token = $token;
    }


    /**
     * @return ownclShare[]
     * @throws GuzzleException
     */
    public function all() : array
    {
        $response = $this->http_client->request('GET', self::URI_SHARE_API . '?' . self::PARAM_FORMAT_JSON, $this->getOptions());
        $decoded = json_decode($response->getBody()->getContents());
        $shares = [];
        if ($decoded->ocs->meta->status === 'ok') {
            $shares = [];
            foreach ($decoded->ocs->data as $std_class) {
                $shares[] = ownclShare::loadFromStdClass($std_class);
            }
        }
        return $shares;
    }

    /**
     * @param string $path
     *
     * @return ownclShare[]
     * @throws GuzzleException
     */
    public function getForPath(string $path) : array
    {
        $response = $this->http_client->request(
            'GET',
            self::URI_SHARE_API . '?' . self::PARAM_FORMAT_JSON . '&' . self::PARAM_PATH . $path,
            $this->getOptions()
        );
        $decoded = json_decode($response->getBody()->getContents());
        $shares = [];
        if ($decoded->ocs->meta->status === 'ok') {
            $shares = [];
            foreach ($decoded->ocs->data as $std_class) {
                $shares[] = ownclShare::loadFromStdClass($std_class);
            }
        }
        return $shares;
    }


    /**
     * @param string $path
     * @param string $user
     *
     * @param int    $permissions
     *
     * @return mixed
     * @throws GuzzleException
     */
    public function create(string $path, string $user, int $permissions)
    {
        $additional_options = [
            'form_params' => [
                'path'        => $path,
                'shareType'   => self::SHARE_TYPE_USER,
                'shareWith'   => $user,
                'permissions' => $permissions
            ]
        ];
        $response = $this->http_client->request('POST', self::URI_SHARE_API . '?' . self::PARAM_FORMAT_JSON, $this->getOptions($additional_options));

        return json_decode($response->getBody()->getContents());
    }

    public function update(int $share_id, int $permissions)
    {
        $additional_options = [
            'form_params' => [
                'permissions' => $permissions
            ]
        ];
        $response = $this->http_client->request(
            'PUT',
            self::URI_SHARE_API . '/' . $share_id . '?' . self::PARAM_FORMAT_JSON,
            $this->getOptions($additional_options)
        );

        return json_decode($response->getBody()->getContents());
    }


    /**
     * @param array $additional_options
     *
     * @return array
     */
    protected function getOptions($additional_options = [])
    {
        return array_merge([
            'headers' => ['Authorization' => 'Bearer ' . $this->token->getAccessToken()]
        ], $additional_options);
    }
}