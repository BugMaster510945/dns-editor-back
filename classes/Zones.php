<?php

class Zones
{
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

	/**
	 * @SWG\Definition(
	 *   definition="zoneList",
	 *   type="array",
	 *   @SWG\Items(
	 *     type="object",
	 *     @SWG\Property(
	 *       property="name",
	 *       description="Zone name",
	 *       type="string"
	 *     ),
	 *     @SWG\Property(
	 *       property="read",
	 *       type="boolean",
	 *       default=false
	 *     ),
	 *     @SWG\Property(
	 *       property="write",
	 *       type="boolean",
	 *       default=false
	 *     )
	 *   )
	 * )
	 */
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

	protected $id;
	protected $name;
	protected $read;
	protected $write;

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
	 *   definition="zoneEntriesObject",
	 *   type="object",
	 *   @SWG\Property(
	 *     property="name",
	 *     description="Zone name",
	 *     readOnly=true,
	 *     type="string"
	 *   ),
	 *   @SWG\Property(
	 *     property="master",
	 *     description="Primary Master",
	 *     readOnly=true,
	 *     type="string"
	 *   ),
	 *   @SWG\Property(
	 *     property="responsible",
	 *     description="Maintainer email",
	 *     type="string",
	 *     format="email"
	 *   ),
	 *   @SWG\Property(
	 *     property="serial",
	 *     description="Zone serial number",
	 *     readOnly=true,
	 *     type="integer",
	 *     format="int32"
	 *   ),
	 *   @SWG\Property(
	 *     property="refresh",
	 *     description="Zone refresh",
	 *     type="integer",
	 *     format="int32"
	 *   ),
	 *   @SWG\Property(
	 *     property="retry",
	 *     description="Zone retry",
	 *     type="integer",
	 *     format="int32"
	 *   ),
	 *   @SWG\Property(
	 *     property="expire",
	 *     description="Zone expire",
	 *     type="integer",
	 *     format="int32"
	 *   ),
	 *   @SWG\Property(
	 *     property="minimum",
	 *     description="Zone minimum",
	 *     type="integer",
	 *     format="int32"
	 *   ),
	 *   @SWG\Property(
	 *     property="secured",
	 *     type="object",
	 *     readOnly=true,
	 *     @SWG\Property(
	 *       property="zsk",
	 *       description="zsk tagid",
	 *       type="array",
	 *       @SWG\Items(
	 *         type="integer",
	 *         format="int32"
	 *       )
	 *     ),
	 *     @SWG\Property(
	 *       property="ksk",
	 *       description="ksk tagid",
	 *       type="array",
	 *       @SWG\Items(
	 *         type="integer",
	 *         format="int32"
	 *       )
	 *     ),
	 *     @SWG\Property(
	 *       property="nsec3param",
	 *       type="object",
	 *       @SWG\Property(
	 *         property="salt",
	 *         description="NSEC3 Salt",
	 *         type="string"
	 *       ),
	 *       @SWG\Property(
	 *         property="iterations",
	 *         description="NSEC3 iterations",
	 *         type="integer",
	 *         format="int32"
	 *       )
	 *     )
	 *   ),
	 *   @SWG\Property(
	 *     property="entries",
	 *     type="array",
	 *     @SWG\Items(
	 *       type="object",
	 *       @SWG\Property(
	 *         property="name",
	 *         description="entry name",
	 *         type="string"
	 *       ),
	 *       @SWG\Property(
	 *         property="ttl",
	 *         description="entry ttl",
	 *         type="integer",
	 *         format="int32"
	 *       ),
	 *       @SWG\Property(
	 *         property="type",
	 *         description="entry type",
	 *         type="string"
	 *       ),
	 *       @SWG\Property(
	 *         property="data",
	 *         description="entry data",
	 *         type="string"
	 *       )
	 *     )
	 *   ),
	 *   @SWG\Property(
	 *     property="read",
	 *     description="Read rights",
	 *     type="boolean",
	 *     default=false,
	 *     readOnly=true
	 *   ),
	 *   @SWG\Property(
	 *     property="write",
	 *     description="Write rights",
	 *     type="boolean",
	 *     default=false,
	 *     readOnly=true
	 *   )
	 * )
	 */
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
			'responsible' => preg_replace('/\./', '@', $soa->rname, 1),
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

}
