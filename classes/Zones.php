<?php

/**
 * @SWG\Definition(
 *   definition="zone",
 *   type="object"
 * )
 */

class Zones
{
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

	public function getFilteredEntries($filterExclude=array('SOA','DNSKEY','RRSIG','NSEC','NSEC3','NSEC3PARAM','TYPE65534'))
	{
		$exclude = array();
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

	public function getZoneEntriesObject($filterExclude=array('SOA','DNSKEY','RRSIG','NSEC','NSEC3','NSEC3PARAM','TYPE65534'))
	{
		$exclude = array();
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
				array_map(function ($r) {
					return array(
						'name' => $r->name,
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
		} catch(Net_DNS2_Exception $e) {
		}

		return ($retour instanceof Net_DNS2_RR_SOA) ? $retour : null;
	}

	public function setSOA($soa)
	{
		global $appDb;

		if( !($soa instanceof Net_DNS2_RR_SOA) )
			return array(false, null);

		$retour = true;
		$data = $appDb->signkeys('zones:id', $this->id)->select('host.ip as host', 'signkeys.name as name', 'algorithm.name as algorithm', 'signkeys.secret as secret');
		$data = $data->fetch();

		$updater = new Net_DNS2_Updater($this->name, array('nameservers' => array($data['host'])));
		$retour = $retour && $updater->signTSIG($data['name'], $data['secret'], $data['algorithm']);

		$msg = null;
		try {
			$retour = $retour && $updater->add( $soa );
			$retour = $retour && $updater->update();
		} catch(Net_DNS2_Exception $e) {
			$retour = false;
			$msg = $e->getMessage();
		}

		return array($retour, $msg);
	}
}
