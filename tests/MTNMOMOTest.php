<?php
namespace Bodastage\MobileMoney\Tests;

use \Bodastage\MobileMoney\MTNMOMO;
use Ramsey\Uuid\Uuid;

class MTNMOMOTest extends \PHPUnit\Framework\TestCase {
	
	const API_KEY = '--API-KEY--';
	
	const  API_USER = '--API-USER--';
	
	const SUBSCRIPTION_KEY = '--SUBSCRIPTION-KEY--';
	
	const CALLBACK_URL = '--CALLBACK-URL--';
	
	const CALLBACK_HOST = '--CALLBACK-HOST--';
	
	public function test_initiation(){
		list($api_user, $api_key) = [ 'A', 'B'];
		
		$mtnmomo = new MTNMOMO($api_user, $api_key);
		$this->assertInstanceOf('\Bodastage\MobileMoney\MTNMOMO', $mtnmomo);
	}
	
	public function test_get_auth_token(){
		list($api_user, $api_key) = [ 'A', 'B'];
		$mtnmomo = new MTNMOMO($api_user, $api_key);
		
		$base64_userkey = base64_encode("{$api_user}:{$api_key}");
		
		$this->assertEquals($mtnmomo->get_auth_token(), $base64_userkey);
	}
	
	public function test_request_access_token(){
		list($api_user, $api_key) = [ MTNMOMOTest::API_USER, MTNMOMOTest::API_KEY ];
		
		$mtnmomo = new MTNMOMO($api_user, $api_key);
		$mtnmomo->set_subscription_key(MTNMOMOTest::SUBSCRIPTION_KEY);
		
		$res = $mtnmomo->request_access_token();
		
		$this->assertArrayHasKey('access_token', $res);
	}
	
	public function test_collect()
	{
		list($api_user, $api_key) = [ MTNMOMOTest::API_USER, MTNMOMOTest::API_KEY ];
		
		$mtnmomo = new MTNMOMO($api_user, $api_key);
		$mtnmomo->set_subscription_key(MTNMOMOTest::SUBSCRIPTION_KEY);
		$mtnmomo->set_callback_url(MTNMOMOTest::CALLBACK_URL);
		$mtnmomo->set_callback_host(MTNMOMOTest::CALLBACK_HOST);
		
		$mobile = "256779089303";
		$amount = 500;
		$external_id = Uuid::uuid4()->toString();
		$res = $mtnmomo->collect($mobile, $amount, $external_id, $payer_message = "Payer message", $payee_note = "Payee note");
		
		print_r( $res);
		$this->assertEquals($res['status_code'], 202);
		$this->assertEquals($res['reason_phrase'], 'Accepted');
		
		return $external_id;
	}
	
	/**
	* @depends test_collect
	*/
	public function test_get_transaction_status($external_id): void
	{
		list($api_user, $api_key) = [ MTNMOMOTest::API_USER, MTNMOMOTest::API_KEY ];
		
		$mtnmomo = new MTNMOMO($api_user, $api_key);
		$mtnmomo->set_subscription_key(MTNMOMOTest::SUBSCRIPTION_KEY);
		
		$res = $mtnmomo->get_transaction_status($external_id);
		
		print_r($res);
	}
	
	public function test_is_mobile_mtn(){
		$this->assertEquals(MTNMOMO::is_mobile_mtn('256770111222'), true);
		$this->assertEquals(MTNMOMO::is_mobile_mtn('256760111222'), true);
		$this->assertEquals(MTNMOMO::is_mobile_mtn('256780111222'), true);
		$this->assertEquals(MTNMOMO::is_mobile_mtn('256320111222'), true);
		$this->assertEquals(MTNMOMO::is_mobile_mtn('256390111222'), true);
	}
	
}