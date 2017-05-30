<?php
/* Copyright (c) 1998-2009 ILIAS open source, Extended GPL, see docs/LICENSE */
require_once(dirname(dirname(__DIR__)) . '/lib/vendor/autoload.php');
require_once 'class.ownclAuth.php';
require_once 'Provider/class.OAuth2Provider.php';
require_once 'Services/Table/interfaces/interface.ilTableFilterItem.php';
require_once 'Services/Form/classes/class.ilSubEnabledFormPropertyGUI.php';
require_once 'Services/Form/classes/class.ilNonEditableValueGUI.php';
/**
 * Class ownclAuthOAuth2
 *
 * @author  Theodor Truffer <tt@studer-raimann.ch>
 */
class ownclAuthOAuth2 implements ownclAuth {
	const CALLBACK_URL = 'owncl_callback_url';
	const AUTH_BEARER = 'owncl_access_token';
	/**
	 * @var ownclApp
	 */
	protected $app;
	/**
	 * @var OAuth2Provider
	 */
	protected $oauth2_provider;
	/**
	 * @var ownclConfig
	 */
	protected $config;


	/**
	 * ownclAuthOAuth2 constructor.
	 *
	 * @param ownclApp $app
	 */
	public function __construct(ownclApp $app) {

		$this->setApp($app);
		$this->config = new ownclConfig();
		$this->oauth2_provider = new OAuth2Provider(array(
			'clientId' => $this->config->getOAuth2ClientID(),
			'clientSecret' => $this->config->getOAuth2ClientSecret(),
			'redirect_uri' => $this->getRedirectUri(),
			'urlAuthorize' => $this->config->getFullOAuth2Path() . '/authorize',
			'urlAccessToken' => $this->config->getFullOAuth2Path() . '/api/v1/token',
			'urlResourceOwnerDetails' => $this->config->getFullOAuth2Path() . '/resource',
		));
	}

	public function getHeaders() {
		return array('Authorization' => 'Bearer ' . $this->app->getIlOwnCloud()->getAccessToken());
	}

	/**
	 * @return array
	 */
	public function getClientSettings() {
		$settings = array(
			'baseUri' => $this->config->getFullWebDAVPath(),
		);

		return $settings;
	}


	public function checkAndRefreshAuthentication() {
		global $ilUser;
		if (!$ilUser->getId() == $this->getApp()->getIlOwnCloud()->getOwnerId()) {
			throw new ilCloudException(ilCloudException::AUTHENTICATION_FAILED, 'Der Ordner kann zur Zeit nur vom Besitzer geöffnet werden.');
		}
		if (!$this->getApp()->getIlOwnCloud()->getAccessToken()) {
			return false;
		}
		if ($this->getApp()->getIlOwnCloud()->isTokenExpired()) {
			try {
				$this->refreshToken();
				return true;
			} catch (Exception $e) {
				return false;
			}
		}
		return true;
	}


	public function refreshToken() {
			$this->getApp()->getIlOwnCloud()->storeToken($this->oauth2_provider->getAccessToken('refresh_token', array(
				'refresh_token' => $this->getApp()->getIlOwnCloud()->getRefreshToken()
			)));
	}

	/**
	 * @param String $callback_url
	 */
	public function authenticate($callback_url) {
		ilSession::set(self::CALLBACK_URL, $this->getApp()->getHttpPath() . $callback_url);
		$this->oauth2_provider->authorize(array('redirect_uri' => $this->getRedirectUri()));
	}

	protected function getRedirectUri() {
		return $this->getApp()->getHttpPath() . 'Customizing/global/plugins/Modules/Cloud/CloudHook/OwnCloud/redirect.php';
	}

	/**
	 *
	 */
	public function redirectToObject() {
		$this->storeTokenToSession($this->oauth2_provider->getAccessToken('authorization_code', array(
			'code' => $_GET['code'],
			'redirect_uri' => $this->getRedirectUri())));

		ilUtil::redirect(ilSession::get(self::CALLBACK_URL));
	}


	/**
	 * @param $access_token League\OAuth2\Client\Token\AccessToken
	 */
	protected function storeTokenToSession($access_token) {
		ilSession::set(self::AUTH_BEARER, serialize($access_token));
	}


	/**
	 * @return League\OAuth2\Client\Token\AccessToken
	 */
	protected function loadTokenFromSession() {
		return unserialize(ilSession::get(self::AUTH_BEARER));
	}



	/**
	 * @param ilOwnCloud $object
	 *
	 * @return bool
	 */
	public function afterAuthentication($object) {
		$token = unserialize(ilSession::get(self::AUTH_BEARER));
		$object->storeToken($token);

		return true;
	}


	public function initPluginSettings(&$form) {
		$n = new ilNonEditableValueGUI(ilOwnCloudPlugin::getInstance()->txt('info_token_expires'));
		$n->setValue(date('d.m.Y - H:i:s', $this->getApp()->getIlOwnCloud()->getValidThrough()));
		$form->addItem($n);
	}


	/**
	 * @return ownclApp
	 */
	public function getApp() {
		return $this->app;
	}


	/**
	 * @param ownclApp $app
	 */
	public function setApp($app) {
		$this->app = $app;
	}
}