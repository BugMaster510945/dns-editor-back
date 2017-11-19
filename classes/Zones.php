<?php

/**
 * @SWG\Definition(
 *   definition="zone",
 *   type="object"
 * )
 */

class Zones
{
	static protected $protectedType = array('SOA','DNSKEY','RRSIG','NSEC','NSEC3','NSEC3PARAM','TYPE65534');

	protected $id;

	/**
	 * @SWG\Property(
	 *   type="string",
	 *   readOnly=true,
	 *   description="Zone name"
	 * )
	 */
	protected $name;

	/**
	 * @SWG\Property(
	 *   type="boolean",
	 *   readOnly=true,
	 *   default=false,
	 *   description="Read rights"
	 * )
	 */
	protected $read;

	/**
	 * @SWG\Property(
	 *   type="boolean",
	 *   readOnly=true,
	 *   default=false,
	 *   description="Write rights"
	 * )
	 */
	protected $write;

	public static function getSOAEmail($rname)
	{
		return preg_replace('/\\\./', '.', preg_replace('/(?<!\\\)\./', '@', $rname, 1));
	}

	public static function getSOArname($email)
	{
		return preg_replace('/@/', '.', preg_replace('/\.(.*@.+)/', '\\.\1', $email));
	}

	public static function reduceEntry($entry, $zone)
	{
		return ($entry === $zone)  ? '@' : preg_replace('/\.'.str_replace('.', '\\.', $zone).'$/', '', $entry);
	}

	public static function expandEntry($entry, $zone)
	{
		return ($entry === '@') ? $zone : $entry.'.'.$zone;
	}

	public static function sortEntries($entries)
	{
		function compareDNSName($a, $b)
		{
			$atab = array_reverse( explode( '.', $a) );
			$btab = array_reverse( explode( '.', $b) );

			$longueur = min ( count($atab), count($btab) );
			$index = 0;

			while( ($index < $longueur) && (strcasecmp($atab[$index], $btab[$index])==0) )
				$index++;

			if( ($index < $longueur) )
				return strnatcasecmp($atab[$index], $btab[$index]);
			else
				return (count($atab) - count($btab));
		}

		function compareEntry($a, $b)
		{
			$r = compareDNSName($a['name'], $b['name']);

			if( $r === 0 )
			{
				$r = strnatcasecmp($a['type'], $b['type']);
				if( $r === 0 )
					$r = strnatcasecmp($a['data'], $b['data']);
			}
			return $r;
		}

		usort($entries, 'compareEntry');

		return $entries;
	}

	public static function getListZones($user)
	{
		global $appDb;
		$ret = array();

		if( !$user instanceof Users )
			return $ret;

		foreach( $appDb->zones()->select('name', 'users_zones:rights as rights')->where('users_zones:users_id', $user->getId()) as $z )
		{
			if( !array_key_exists($z['name'], $ret) )
				$ret[$z['name']] = array( 'name' => $z['name'], 'read' => false, 'write' => false );

			$ret[$z['name']][$z['rights']] = true;
		}

		return array_values($ret);
	}

	public static function getZone($name, $user = null, $needWrite = false)
	{
		global $appDb;

		if( !$user instanceof Users )
			return null;

		$rights = $needWrite ? 'write' : 'read';
		$authorized = $appDb->zones()->where('name', $name)->and('users_zones:users_id', $user->getId())->and('users_zones:rights', $rights)->count();

		if( $authorized >= 1 )
			return new Zones($name, $user);

		return null;
	}

	protected function __construct($name, $user=null)
	{
		global $appDb;
		
		$data = $appDb->zones('name', $name);
		if( count($data) == 1 )
			$data = $data->fetch();

		$this->id = +$data['id'];
		$this->name = $data['name'];

		$this->read = null;
		$this->write = null;
		if( $user instanceof Users )
		{
			$this->read = false;
			$this->write = false;
			foreach( $appDb->users_zones()->where('users_id', $user->getId())->where('zones_id', $this->id) as $d )
			{
				if( $d['rights'] == 'read' ) $this->read = true;
				if( $d['rights'] == 'write' ) $this->write = true;
			}
		}
	}

	public function getEntries()
	{
		global $appDb;

		$retour = null;

		$data = $appDb->signkeys('zones:id', $this->id)->select('host.ip as host', 'signkeys.name as name', 'algorithm.name as algorithm', 'signkeys.secret as secret');
		$data = $data->fetch();

		$resolv = new Net_DNS2_Resolver(array('nameservers' => array($data['host'])));
		$resolv->signTSIG($data['name'], $data['secret'], $data['algorithm']);

		try {
			$retour = $resolv->query($this->name, 'AXFR');
			$retour = $retour->answer;
		} catch(Net_DNS2_Exception $e) {
		}

		return $retour;
	}

	public function getFilteredEntries($filterExclude=null)
	{
		$exclude = array();
		if( is_null($filterExclude) ) $filterExclude = self::$protectedType;
		foreach($filterExclude as $key)
			$exclude[$key] = true;

		$output = array();
		foreach( $this->getEntries() as $entry )
		{
			if( array_key_exists($entry['type'], $exclude) )
				continue;

			$output[] = $entry;
		}

		return $output;
	}

	/**
	 * @SWG\Definition(
	 *   definition="zoneEntry",
	 *   type="object",
	 *   @SWG\Property(
	 *     property="name",
	 *     description="entry name",
	 *     type="string",
	 *     readOnly=true
	 *   ),
	 *   @SWG\Property(
	 *     property="ttl",
	 *     description="entry ttl",
	 *     type="integer",
	 *     format="int32"
	 *   ),
	 *   @SWG\Property(
	 *     property="type",
	 *     description="entry type",
	 *     type="string"
	 *   ),
	 *   @SWG\Property(
	 *     property="data",
	 *     description="entry data",
	 *     type="string"
	 *   )
	 * )
	 */

	/**
	 * @SWG\Definition(
	 *   definition="zoneEntryAll",
	 *   type="object",
	 *   @SWG\Property(
	 *     property="name",
	 *     description="entry name",
	 *     type="string"
	 *   ),
	 *   @SWG\Property(
	 *     property="ttl",
	 *     description="entry ttl",
	 *     type="integer",
	 *     format="int32"
	 *   ),
	 *   @SWG\Property(
	 *     property="type",
	 *     description="entry type",
	 *     type="string"
	 *   ),
	 *   @SWG\Property(
	 *     property="data",
	 *     description="entry data",
	 *     type="string"
	 *   )
	 * )
	 */

	/**
	 * @SWG\Definition(
	 *   definition="zoneSOA",
	 *   type="object",
	 *   allOf={
	 *     @SWG\Schema(ref="#/definitions/zone"),
	 *     @SWG\Schema(
	 *       @SWG\Property(
	 *         property="master",
	 *         description="Primary Master",
	 *         readOnly=true,
	 *         type="string"
	 *       ),
	 *       @SWG\Property(
	 *         property="responsible",
	 *         description="Maintainer email",
	 *         type="string",
	 *         format="email"
	 *       ),
	 *       @SWG\Property(
	 *         property="serial",
	 *         description="Zone serial number",
	 *         readOnly=true,
	 *         type="integer",
	 *         format="int32"
	 *       ),
	 *       @SWG\Property(
	 *         property="refresh",
	 *         description="Zone refresh",
	 *         type="integer",
	 *         format="int32"
	 *       ),
	 *       @SWG\Property(
	 *         property="retry",
	 *         description="Zone retry",
	 *         type="integer",
	 *         format="int32"
	 *       ),
	 *       @SWG\Property(
	 *         property="expire",
	 *         description="Zone expire",
	 *         type="integer",
	 *         format="int32"
	 *       ),
	 *       @SWG\Property(
	 *         property="minimum",
	 *         description="Zone minimum",
	 *         type="integer",
	 *         format="int32"
	 *       )
	 *     )
	 *   }
	 * )
	 */

	/**
	 * @SWG\Definition(
	 *   definition="zoneEntries",
	 *   type="object",
	 *   allOf={
	 *     @SWG\Schema(ref="#/definitions/zoneSOA"),
	 *     @SWG\Schema(
	 *       @SWG\Property(
	 *         property="secured",
	 *         type="object",
	 *         readOnly=true,
	 *         @SWG\Property(
	 *           property="zsk",
	 *           description="zsk tagid",
	 *           type="array",
	 *           @SWG\Items(
	 *             type="integer",
	 *             format="int32"
	 *           )
	 *         ),
	 *         @SWG\Property(
	 *           property="ksk",
	 *           description="ksk tagid",
	 *           type="array",
	 *           @SWG\Items(
	 *             type="integer",
	 *             format="int32"
	 *           )
	 *         ),
	 *         @SWG\Property(
	 *           property="nsec3param",
	 *           type="object",
	 *           @SWG\Property(
	 *             property="salt",
	 *             description="NSEC3 Salt",
	 *             type="string"
	 *           ),
	 *           @SWG\Property(
	 *             property="iterations",
	 *             description="NSEC3 iterations",
	 *             type="integer",
	 *             format="int32"
	 *           )
	 *         )
	 *       ),
	 *       @SWG\Property(
	 *         property="entries",
	 *         type="array",
	 *         @SWG\Items(ref="#/definitions/zoneEntry")
	 *       )
	 *     )
	 *   }
	 * )
	 */
	public function getSOAObject()
	{
		$soa = null;

		$soa = $this->getSOA();
		if( is_null($soa) )
			return null;

		$retour = array(
			'name' => $this->name,
			'master' => $soa->mname,
			'responsible' => Zones::getSOAEmail( $soa->rname ),
			'serial' => $soa->serial,
			//'timing' => array(
				'refresh' => $soa->refresh,
				'retry' => $soa->retry,
				'expire' => $soa->expire,
				'minimum' => $soa->minimum,
			//),
			'read' => $this->read,
			'write' => $this->write
		);

		return $retour;
	}

	public function getZoneEntriesObject($filterExclude=null)
	{
		$exclude = array();
		if( is_null($filterExclude) ) $filterExclude = self::$protectedType;
		foreach($filterExclude as $key)
			$exclude[$key] = true;

		$soa = null;
		$nsec3param = null;
		$dnskey = array();
		$output = array();

		$entries = $this->getEntries();
		if( is_null($entries) )
			return null;
		foreach( $entries as $entry )
		{
			if( $entry->type == 'SOA' )
				$soa = $entry;
			if( $entry->type == 'NSEC3PARAM' )
				$nsec3param = $entry;
			if( $entry->type == 'DNSKEY' )
				$dnskey[] = $entry;

			if( array_key_exists($entry->type, $exclude) )
				continue;

			$output[] = $entry;
		}
		unset($entries);

		if( is_null($soa) )
			return null;

		$zone_name = $this->name;
		$retour = array(
			'name' => $this->name,
			'master' => $soa->mname,
			'responsible' => Zones::getSOAEmail( $soa->rname ),
			'serial' => $soa->serial,
			//'timing' => array(
				'refresh' => $soa->refresh,
				'retry' => $soa->retry,
				'expire' => $soa->expire,
				'minimum' => $soa->minimum,
			//),
			'secured' => array('zsk' => array(), 'ksk' => array() ),
			'entries' => Zones::sortEntries(
				array_map(function ($r) use ($zone_name) {
					return array(
						'name' => Zones::reduceEntry( $r->name, $zone_name ),
						'ttl' => $r->ttl,
						'type' => $r->type,
						'data' => implode(' ', array_slice(explode(' ', $r), 4))
					);
				}, $output)
			),
			'read' => $this->read,
			'write' => $this->write
		);
		unset($output);

		foreach( $dnskey as $entry )
		{
			if( $entry->flags == 256 )
				$retour['secured']['zsk'][] = $entry->keytag;
			if( $entry->flags == 257 )
				$retour['secured']['ksk'][] = $entry->keytag;
		}

		if( ( count($retour['secured']['zsk']) + count($retour['secured']['ksk']) ) == 0 )
		{
			$retour['secured'] = null;
		}
		elseif( !is_null($nsec3param) )
		{
			$retour['secured']['nsec3param'] = array(
				'salt'       => $nsec3param->salt,
				'iterations' => $nsec3param->iterations
			);
		}

		return $retour;
	}

	public function getSOA()
	{
		global $appDb;

		$retour = null;

		$data = $appDb->signkeys('zones:id', $this->id)->select('host.ip as host', 'signkeys.name as name', 'algorithm.name as algorithm', 'signkeys.secret as secret');
		$data = $data->fetch();

		$resolv = new Net_DNS2_Resolver(array('nameservers' => array($data['host'])));
		$resolv->signTSIG($data['name'], $data['secret'], $data['algorithm']);

		try {
			$retour = $resolv->query($this->name, 'SOA');
			$retour = $retour->answer[0];
		}
		catch(Net_DNS2_Exception $e)
		{
			throw new appException(504, $e);
		}

		if( !($retour instanceof Net_DNS2_RR_SOA) )
			throw new appException(500);

		return $retour;
	}

	public function updateSOA($new)
	{
		global $appDb;

		if( !is_array($new) )
			throw new appException(400);

		$soa = $this->getSOA();

		$soa->rname = Zones::getSOAEmail( $soa->rname );

		$tmp = array();
		# Remplit les champs de la soa avec les nouvelles valeurs définies
		foreach(array('rname', 'refresh', 'retry', 'expire', 'minimum') as $key)
		{
			if( array_key_exists($key, $new) )
				$soa->$key = $new[$key];
			$tmp[$key] = $soa->$key;
		}

		// https://github.com/dotse/zonemaster/tree/master/docs/specifications/tests/Zone-TP
		$filter_args = array(
			'rname'       => array('filter' => FILTER_VALIDATE_EMAIL),
			'refresh'     => array('filter' => FILTER_VALIDATE_INT, 'options' => array('min_range' => max(14400, $tmp['retry']+1), 'max_range' => $tmp['expire'] ) ),
			'retry'       => array('filter' => FILTER_VALIDATE_INT, 'options' => array('min_range' => 3600, 'max_range' => $tmp['refresh']-1) ),
			'expire'      => array('filter' => FILTER_VALIDATE_INT, 'options' => array('min_range' => max(604800, $tmp['refresh']), 'max_range' => 2147483647)),
			'minimum'     => array('filter' => FILTER_VALIDATE_INT, 'options' => array('min_range' => 300, 'max_range' => 86400))
		);

		$errors = array();
		filter_var_array_errors($tmp, $filter_args, $errors, false);

		if( count($errors) != 0 )
			throw new appException(400, $errors);
		unset($errors); unset($tmp);

		$soa->serial += 1;
		$soa->rname = Zones::getSOArname($soa->rname);

		$data = $appDb->signkeys('zones:id', $this->id)->select('host.ip as host', 'signkeys.name as name', 'algorithm.name as algorithm', 'signkeys.secret as secret');
		$data = $data->fetch();

		try
		{
			$updater = new Net_DNS2_Updater($this->name, array('nameservers' => array($data['host'])));
			$updater->signTSIG($data['name'], $data['secret'], $data['algorithm']);

			$updater->add( $soa );
			if( !$updater->update() )
				throw new appException(500);
		}
		catch(Net_DNS2_Exception $e)
		{
			throw new appException(500, $e);
		}
	}

	public function getDefaultTTL()
	{
		$soa = $this->getSOA();
		return $soa->minimum;
	}

	public function updateEntry($new, $old = null)
	{
		global $appDb;

		$errors = array();
		if( !is_array($new) )
			throw new appException(400);

		$filter_args = array(
			'name'    => array('filter' => FILTER_VALIDATE_REGEXP, 'options' => array('regexp' => '/^.+/') ),
			'ttl'     => array('filter' => FILTER_VALIDATE_INT, 'options' => array('min_range' => 1, 'max_range' => 2147483647)),
			'type'    => array('filter' => FILTER_VALIDATE_REGEXP, 'options' => array('regexp' => '/^[A-Z]+/') ),
			'data'    => array('filter' => FILTER_VALIDATE_REGEXP, 'options' => array('regexp' => '/^.+/') )
		);
		$new = filter_var_array_errors($new, $filter_args, $errors, true);

		foreach(array('name', 'type', 'data') as $key)
			if( !array_key_exists($key, $new) ||
				is_null($new[$key]) ||
				trim($new[$key]) == ""
			)
				$errors[] = sprintf(_('Field %s: is required'), $key);

		if( count($errors) != 0 )
			throw new appException(400, $errors);

		if( is_null($old) )
			$old = array('name'=>$new['name']);
		if( !is_array($old) )
			throw new appException(400);

		$old = filter_var_array_errors($old, $filter_args, $errors, false);
		if( !array_key_exists('name', $old) )
			$errors[] = sprintf(_('Field %s: is required'), 'name');

		if( count($errors) != 0 )
			throw new appException(400, $errors);

		/*if( array_key_exists('type', $old) &&
		    $old['type'] != $new['type'] )
			throw new appException(400, array( _('DNS Type must match between old and new entry')) );
		*/

		if( in_array(strtoupper($new['type']), self::$protectedType) )
			throw new appException(403, array( sprintf(_('Wrong call to update %s'), strtoupper($new['type']))) );

		if( array_key_exists('type', $old) && in_array(strtoupper($old['type']), self::$protectedType) )
			throw new appException(403, array( sprintf(_('Wrong call to update %s'), strtoupper($old['type']))) );

		if( ($old['name'] == '@') && !array_key_exists('type', $old) )
			throw new appException(400, array( sprintf(_('Type required to update root zone')) ) );

		$newRR = array( Zones::expandEntry($new['name'], $this->name) );
		if( is_null($new['ttl']) )
			$newRR[] = $this->getDefaultTTL();
		else
			$newRR[] = $new['ttl'];
		$newRR[] = $new['type'];
		$newRR[] = $new['data'];

		$data = $appDb->signkeys('zones:id', $this->id)->select('host.ip as host', 'signkeys.name as name', 'algorithm.name as algorithm', 'signkeys.secret as secret');
		$data = $data->fetch();

		try
		{
			$updater = new Net_DNS2_Updater($this->name, array('nameservers' => array($data['host'])));
			$updater->signTSIG($data['name'], $data['secret'], $data['algorithm']);

			$oldRR = array( Zones::expandEntry($old['name'], $this->name), 0 ); // Don't care about ttl when delete
			if( array_key_exists('type', $old) )
			{
				$oldRR[] = $old['type'];
				if( array_key_exists('data', $old) )
				{
					$oldRR[] = $old['data'];

					$oldRR = Net_DNS2_RR::fromString(implode($oldRR, ' '));
					$updater->delete($oldRR);
				}
				else
					$updater->deleteAny($oldRR[0], $oldRR[2]);
			}
			else
				$updater->deleteAll($oldRR[0]);

			$newRR = Net_DNS2_RR::fromString(implode($newRR, ' '));
			$updater->add( $newRR );
			if( !$updater->update() )
				throw new appException(500);
		}
		catch(Net_DNS2_Exception $e)
		{
			throw new appException(400, $e);
		}
	}

	public function updateEntrySimple($entry, $new, $old = null)
	{
		$new['name'] = $entry;
		if( is_array($old) )
			$old['name'] = $entry;

		return $this->updateEntry($new, $old);
	}

	public function addEntry($entry, $new)
	{
		global $appDb;

		$errors = array();
		if( !is_array($new) )
			throw new appException(400);

		$filter_args = array(
			'ttl'     => array('filter' => FILTER_VALIDATE_INT, 'options' => array('min_range' => 1, 'max_range' => 2147483647)),
			'type'    => array('filter' => FILTER_VALIDATE_REGEXP, 'options' => array('regexp' => '/^[A-Z]+/') ),
			'data'    => array('filter' => FILTER_VALIDATE_REGEXP, 'options' => array('regexp' => '/^.+/') )
		);
		$new = filter_var_array_errors($new, $filter_args, $errors, true);

		if( count($errors) != 0 )
			throw new appException(400, $errors);

		if( is_null($new['type']) )
			throw new appException(400, array( sprintf(_('Field %s: is required'), 'type')) );
		if( is_null($new['data']) )
			throw new appException(400, array( sprintf(_('Field %s: is required'), 'data')) );

		if( in_array(strtoupper($new['type']), self::$protectedType) )
			throw new appException(403, array( sprintf(_('Wrong call to add %s'), strtoupper($new['type']))) );

		$newRR = array( Zones::expandEntry($entry, $this->name) );
		if( is_null($new['ttl']) )
			$newRR[] = $this->getDefaultTTL();
		else
			$newRR[] = $new['ttl'];
		$newRR[] = $new['type'];
		$newRR[] = $new['data'];

		$data = $appDb->signkeys('zones:id', $this->id)->select('host.ip as host', 'signkeys.name as name', 'algorithm.name as algorithm', 'signkeys.secret as secret');
		$data = $data->fetch();

		try
		{
			$updater = new Net_DNS2_Updater($this->name, array('nameservers' => array($data['host'])));
			$updater->signTSIG($data['name'], $data['secret'], $data['algorithm']);

			$newRR = Net_DNS2_RR::fromString(implode($newRR, ' '));
			$updater->add( $newRR );
			if( !$updater->update() )
				throw new appException(500);
		}
		catch(Net_DNS2_Exception $e)
		{
			throw new appException(400, $e);
		}
	}

	public function deleteEntry($entry, $old)
	{
		global $appDb;

		$errors = array();
		if( !is_array($old) )
			throw new appException(400);

		$filter_args = array(
			'ttl'     => array('filter' => FILTER_VALIDATE_INT, 'options' => array('min_range' => 1, 'max_range' => 2147483647)),
			'type'    => array('filter' => FILTER_VALIDATE_REGEXP, 'options' => array('regexp' => '/^[A-Z]+/') ),
			'data'    => array('filter' => FILTER_VALIDATE_REGEXP, 'options' => array('regexp' => '/^.+/') )
		);
		$old = filter_var_array_errors($old, $filter_args, $errors, true);

		if( count($errors) != 0 )
			throw new appException(400, $errors);

		if( is_null($old['type']) )
			throw new appException(400, array( sprintf(_('Field %s: is required'), 'type')) );
		if( is_null($old['data']) )
			throw new appException(400, array( sprintf(_('Field %s: is required'), 'data')) );

		if( in_array(strtoupper($old['type']), self::$protectedType ) )
			throw new appException(403, array( sprintf(_('Wrong call to delete %s'), strtoupper($old['type']))) );

		$oldRR = array( Zones::expandEntry($entry, $this->name), 0 ); // Don't care about ttl when delete
		$oldRR[] = $old['type'];
		$oldRR[] = $old['data'];

		$data = $appDb->signkeys('zones:id', $this->id)->select('host.ip as host', 'signkeys.name as name', 'algorithm.name as algorithm', 'signkeys.secret as secret');
		$data = $data->fetch();

		try
		{
			$updater = new Net_DNS2_Updater($this->name, array('nameservers' => array($data['host'])));
			$updater->signTSIG($data['name'], $data['secret'], $data['algorithm']);

			$oldRR = Net_DNS2_RR::fromString(implode($oldRR, ' '));
			$updater->delete($oldRR);

			if( !$updater->update() )
				throw new appException(500);
		}
		catch(Net_DNS2_Exception $e)
		{
			throw new appException(400, $e);
		}
	}
}
