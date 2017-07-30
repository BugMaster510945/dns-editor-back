<?php

class OTPUsers
{
	public static function getCredentials($user, $url, $method, $count=1, $expire=null)
	{
		global $appDb;

		if( $count <= 0 )
			return array(null, null);

		if( is_null($expire) )
			$expire = time() +  OTP_CREDENTIALS_DURATION;

		if( $expire <= time() )
			return array(null, null);

		$expireObject = new DateTime();
		$expireObject->setTimestamp($expire);

		$login = strtr(base64_encode(openssl_random_pseudo_bytes(30)), '+/', '-_');
		$password = strtr(base64_encode(openssl_random_pseudo_bytes(30)), '+/', '-_');

		$data = $appDb->otpaction->insert( array(
			'login'    => $login,
			'password' => $password,
			'url'      => $url,
			'method'   => $method,
			'count'    => $count,
			'expire'   => $expireObject,
			'users_id' => $user->getId()
		) );

		if( $data !== false )
			return array($login, $password);

		return array(null, null);
	}

	public static function login($login, $password, $url, $method)
	{
		global $appDb;

		$row = $appDb->otpaction
			->where('login',  $login)
			->and('password', $password)
			->and('url',      $url)
			->and('method',   $method);
		$data = $row->fetch();

		if( $data === false )
			return null;

		$expire = new DateTime($data['expire']);
		if( $expire <= new DateTime() )
		{
			$data->delete();
			return null;
		}
		
		$cpt = $data['count'];
		if( $cpt <= 0 )
		{
			$data->delete();
			return null;
		}

		$user = Users::getUser($data['users_id']);

		$cpt = $cpt -1;
		if( $cpt <= 0 )
			$data->delete();
		else
			$data->update(array('count' => $cpt));

		return $user;
	}

	public static function purgeExpiredCredentials()
	{
		global $appDb;

		$row = $appDb->otpaction
			->where('expire <= ?',  new NotORM_Literal("NOW()"))
			->delete();
	}
}

class Users
{
	private $id = null;
	private $data = null;

	public static function login($login, $password)
	{
		$login = preg_replace('/[^0-9a-zA-Z@._-]/', '', $login);

		if( $login == '' )
			return null;

		$user = self::getUserByLogin($login);

		return ( !is_null($user) && $user->checkPassword($password) ) ? $user : null;
	}

	public static function getUser($id)
	{
		$ret = new Users();

		if( $ret->load($id) )
			return $ret;

		return null;
	}

	public static function getUserByLogin($login)
	{
		$ret = new Users();

		if( $ret->load(null, $login) )
			return $ret;

		return null;
	}

	public static function getUserByApiKey($api_key)
	{
		$ret = new Users();

		if( $ret->load(null, null, $api_key) )
			return $ret;

		return null;
	}

	public function load($id=null, $login=null, $api_key=null)
	{
		global $appDb;

		if( is_null($id) && is_null($login) && is_null($api_key) )
			return false;

		$this->id = null;
		$this->data = null;

		if( !is_null($id) )
		{
			$data = $appDb->users[$id];
			$this->data = $data;
		}
		else
		{
			if( !is_null($login) )
				$data = $appDb->users('login', $login);
			else
				$data = $appDb->users('api_key', $api_key);

			if( count($data) == 1 )
				$this->data = $data->fetch();
		}

		if( is_null($this->data) )
			return false;

		$this->id = +$this->data['id'];

		return true;
	}

	public static function newUser($login)
	{
		global $appDb;

		$ret = new Users();

		$login = preg_replace('/[^0-9a-zA-Z@._-]/', '', $login);

		$data = $appDb->users->insert( array('login' => $login) );

		if( $data !== false )
		{
			if( $ret->load(+$data['id']) )
				return $ret;
		}

		return null;
	}

	public static function deleteUser(&$userObject)
	{
		$saveObject = $userObject;
		unset($userObject);

		$data = $saveObject->data->delete();

		return $data === 1;
	}

	public function changeLogin($newLogin)
	{
		$newLogin= preg_replace('/[^0-9a-zA-Z@._-]/', '', $newLogin);

		return $this->setBulk(array('login' => $newLogin));
	}

	public function setPassword($password)
	{
		$pass_hash = password_hash($password, PASSWORD_DEFAULT);
		return $this->setBulk(array('password' => $pass_hash));
	}

	public function checkPassword($password)
	{
		if( is_null($this->data) )
			return false;

		if( password_verify($password, $this->data['password']) )
		{
			if( password_needs_rehash($this->data['password'], PASSWORD_DEFAULT) )
			{
				$this->setPassword($password);
			}
			return true;
		}
		return false;
	}

	public function getData()
	{
		$ret = $this->data->jsonSerialize();
		unset( $ret['password'] );
		$ret['id'] = +$ret['id'];
		$ret['is_admin'] = (bool) $ret['is_admin'];
		return $ret;
	}
	public function getDataWithCounter()
	{
		$ret = $this->data->jsonSerialize();
		unset( $ret['password'] );
		$ret['id'] = +$ret['id'];
		$ret['is_admin'] = (bool) $ret['is_admin'];

		return $ret;
	}

	public function setBulk($values)
	{
		$ret = $this->data->update($values);
		if( $ret !== false && $ret >= 1)
		{
			global $appDb;

			$this->data = $appDb->users[$this->id];
		}
		return $ret !== false;
	}

	public static function loadAllUsers()
	{
		global $appDb;

		$ret = array();

		foreach( $appDb->users->order('login') as $id => $user)
		{
			$tmp = new Users();
			$tmp->data = $user;
			$tmp->id = +$id;

			$ret[] = $tmp;
		}

		return $ret;
	}

	public function getId()
	{
		return $this->id;
	}

	public function isAdmin()
	{
		return (bool) $this->data['is_admin'];
	}

	public function getTheme()
	{
		$ret = $this->data['theme'];
		return getRealTheme($ret);
	}

	public function getEmail()
	{
		return $this->data['email'];
	}

	public function __sleep()
	{
		return array('id');
	}

	public function __wakeup()
	{
		if( !is_null($this->id) )
		{
			global $appDb;

			$data = $appDb->users[$this->id];

			if( !is_null($data) )
				$this->data = $data;
			else
				$this->id = null;
		}
	}

	public function isValid()
	{
		return !is_null($this->id);
	}
}
