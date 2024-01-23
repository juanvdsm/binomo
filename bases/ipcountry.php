<?php
require_once __DIR__.'/geoip2.phar';
use GeoIp2\Database\Reader;

function getip(){
    //Will return Cloudflare's connecting ip only if we are behind the cloud
    if (isset($_SERVER['HTTP_CF_CONNECTING_IP']) &&
        str_contains(strtolower(getisp($_SERVER['REMOTE_ADDR'])),"cloudflare"))
        return $_SERVER['HTTP_CF_CONNECTING_IP'];

    $ipfound = 'Unknown';

    if(isset($_SERVER['REMOTE_ADDR'])){
        $ipfound = $_SERVER['REMOTE_ADDR'];
    }
    else if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        $ipfound = $_SERVER['HTTP_CLIENT_IP'];
    }
    else if(!empty($_SERVER['HTTP_X_FORWARDED_FOR'])){
        $ip=$_SERVER['HTTP_X_FORWARDED_FOR'];
        $ip=explode(", ", $ip);
        if(count($ip)<=1){$ip=explode(",", $ip[0]);}
        if(!empty($ip[0])){
            $ipfound=$ip[0];
        }
    }

	if ($ipfound==='::1'|| !is_public_ip($ipfound))
        return '109.124.224.100'; //for debugging
	return $ipfound;
}

function is_public_ip($ip) : bool
{
    return filter_var(
        $ip,
        FILTER_VALIDATE_IP,
        FILTER_FLAG_IPV4 | FILTER_FLAG_IPV6 | FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
    ) === $ip;
}

function getcountry($ip=null){
	if (is_null($ip)) $ip=getip();
	if ($ip==='Unknown') return 'Unknown';
	$reader = new Reader(__DIR__.'/GeoLite2-Country.mmdb');
	if ($ip==='::1'||$ip==='127.0.0.1') $ip='31.177.76.70'; //for debugging
	try{
        $record = $reader->country($ip);
        return $record->country->isoCode;
    }
	catch (GeoIp2\Exception\AddressNotFoundException $exception)
    {
		return 'Unknown';
    }
}

function getcity($ip,$locale){
	$reader = new Reader(__DIR__.'/GeoLite2-City.mmdb');
	if ($ip==='::1'||$ip==='127.0.0.1') $ip='31.177.76.70'; //for debugging
	try{
        $record = $reader->city($ip);
        if (array_key_exists($locale,$record->city->names))
            return $record->city->names[$locale];
        else
            return $record->city->name;
    }
	catch (GeoIp2\Exception\AddressNotFoundException $exception)
    {
    	return 'Unknown';
    }
}

function getisp($ip){
	$reader = new Reader(__DIR__.'/GeoLite2-ASN.mmdb');
	if ($ip==='::1'||$ip==='127.0.0.1') $ip='31.177.76.70'; //for debugging
	try{
        $record = $reader->asn($ip);
        return $record->autonomousSystemOrganization;
    }
	catch (GeoIp2\Exception\AddressNotFoundException $exception)
    {
    	return 'Unknown';
    }
}
?>
