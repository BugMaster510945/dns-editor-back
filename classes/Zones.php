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
			return new Zones($name);

		return null;
	}

	protected $id;
	protected $name;

	protected function __construct($name)
	{
		global $appDb;
		
		$data = $appDb->zones('name', $name);
		if( count($data) == 1 )
			$data = $data->fetch();

		$this->id = +$data['id'];
		$this->name = $data['name'];
	}

	public function getEntries()
	{
		global $appDb;

		$retour = array();

		$data = $appDb->signkeys('zones:id', $this->id)->select('host.ip as host', 'signkeys.name as name', 'algorithm.name as algorithm', 'signkeys.secret as secret');
		$data = $data->fetch();

		$resolv = new Net_DNS2_Resolver(array('nameservers' => array($data['host'])));
		$resolv->signTSIG($data['name'], $data['secret'], $data['algorithm']);

		try {
			$tmp = $resolv->query($this->name, 'AXFR');

			$retour = array_map(function ($r) {
					return array(
							'name' => $r->name,
							'ttl' => $r->ttl,
							'type' => $r->type,
							'data' => implode(' ', array_slice(explode(' ', $r), 4))
							);
					}, $tmp->answer);
			unset($tmp);
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
}
