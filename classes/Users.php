<?php

/**
 * @SWG\Definition(
 *   definition="user",
 *   type="object"
 * )
 */
class Users implements \JsonSerializable
{
	protected $id;

	/**
	 * @SWG\Property(
	 *   type="string",
	 *   readOnly=true,
	 *   description="User login"
	 * )
	 */
	protected $login;

	/**
	 * @SWG\Property(
	 *   type="string",
	 *   description="User password"
	 * )
	 */
	protected $password;

	/**
	 * @SWG\Property(
	 *   type="string",
	 *   description="User name"
	 * )
	 */
	protected $name;

	/**
	 * @SWG\Property(
	 *   type="string",
	 *   description="User email"
	 * )
	 */
	protected $email;

	/**
	 * @SWG\Property(
	 *   type="boolean",
	 *   description="Administaor"
	 * )
	 */
	protected $is_admin;

	private $db = null;

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

	private function loadFromArray($data)
	{
		$this->id = +$data['id'];
		$this->login = $data['login'];
		$this->password = $data['password'];
		$this->name = $data['name'];
		$this->email = $data['email'];
		$this->is_admin = (bool) $data['is_admin'];
	}

	public function load($id=null, $login=null)
	{
		global $appDb;

		if( is_null($id) && is_null($login) )
			return false;

		$this->db = null;
		$data = null;

		if( !is_null($id) )
		{
			$data = $appDb->users[$id];
		}
		else
		{
			if( !is_null($login) )
				$data = $appDb->users('login', $login);
			else
				$data = $appDb->users('api_key', $api_key);

			if( count($data) == 1 )
				$data = $data->fetch();
		}

		if( is_null($data) )
			return false;

		$this->db = $data;
		$this->loadFromArray($data);

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

		$cpt = $saveObject->db->delete();

		return $cpt === 1;
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
		if( password_verify($password, $this->password) )
		{
			if( password_needs_rehash($this->password, PASSWORD_DEFAULT) )
			{
				$this->setPassword($password);
			}
			return true;
		}
		return false;
	}

	public function setBulk($values)
	{
		$ret = $this->db->update($values);
		if( $ret !== false && $ret >= 1)
		{
			global $appDb;

			$this->loadFromArray($appDb->users[$this->id]);
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
			$tmp->db = $user;
			$tmp->loadFromArray($user);

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
		return $this->is_admin;
	}

	public function getEmail()
	{
		return $this->email;
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
			{
				$this->db = $data;
				$this->loadFromArray($data);
			}
			else
				$this->id = null;
		}
	}

	public function isValid()
	{
		return !is_null($this->id);
	}

	public function jsonSerialize()
	{
		return array(
			'login'    => $this->login,
			'name'     => $this->name,
			'email'    => $this->email,
			'is_admin' => $this->is_admin,
		);
	}
}
