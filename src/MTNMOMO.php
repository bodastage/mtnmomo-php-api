<?php
namespace Bodastage\MobileMoney;

use Ramsey\Uuid\Uuid;

class MTNMOMO {
	
	const PROD_BASE_URL = 'https://proxy.momoapi.mtn.com';
	
	const SANDBOX_BASE_URL = 'https://sandbox.momodeveloper.mtn.com';
	
	/*
	* MTN API Key
	*/
	private string $api_key;
	
	/*
	* MTN API user
	*/
	private string $api_user;
	
	private string $target_environment;
	
	private string $callback_url;
	
	private string $callback_host;
	/*
	* subscription key
	*
	*/
	private string $subscription_key;
	
	/*
	*
	* @param string $api_user
	* @param string $api_key
	*
	*/
	public function __construct($api_user, $api_key, $target_environment = 'mtnuganda', $callback_url= "", $callback_host = ""){
		$this->api_user = $api_user;
		$this->api_key = $api_key;
		$this->target_environment = $target_environment;
		$this->callback_url = $callback_url;
		$this->callback_host = $callback_host;
	}
	
	public function set_callback_url($callback_url){
		$this->callback_url = $callback_url;
	}
	
	public function get_callback_url(){
		return $this->callback_url;
	}
	
	
	public function set_callback_host($callback_host){
		$this->callback_host = $callback_host;
	}
	
	public function get_callback_host(){
		return $this->callback_host;
	}
	
	/*
	* Set the collection subscription key
	*
	* @param string $subscription_key
	*/
	public function set_subscription_key($subscription_key){
		$this->subscription_key = $subscription_key;
	}
	
	/*
	* Get the subscription key
	*
	* @param string $subscription_key
	*/
	public function get_subscription_key(){
		return $this->subscription_key;
	}
	
	/*
	* Get authorization token
	*
	* @return string
	*/
	public function get_auth_token(){
		return base64_encode("{$this->api_user}:{$this->api_key}");
	}
	
	/*
	* Get access token
	*/
	public function request_access_token(){
		$auth_token = $this->get_auth_token();

		$client = new \GuzzleHttp\Client();
		$response = $client->request('POST', MTNMOMO::PROD_BASE_URL . '/collection/token/', [
			'headers' => [
				'Ocp-Apim-Subscription-Key' => $this->subscription_key,
				'Content-Length' => 0,
				'Content-Type' => 'application/json',
				'Authorization' => 'Basic ' . $auth_token
			]
		]);
		
		$body = json_decode($response->getBody()->getContents(), true);
		
		return $body;
	}
	
	public function get_user_details(){
		$client = new \GuzzleHttp\Client();
		$res = $client->request('GET', MTNMOMO::PROD_BASE_URL . '/v1_0/apiuser/' . $this->api_user, [
			'headers' => [
				'Ocp-Apim-Subscription-Key' => $this->subscription_key
			]
		]);
		
		$body = json_decode($response->getBody()->getContents(), true);
		
		return [
			'status_code' => $response->getStatusCode(),
			'reason_phrase' => $response->getReasonPhrase(),
			'body' => $body
		];
	}
	
	public function get_transaction_status($external_id){
		$client = new \GuzzleHttp\Client();
		$response = $client->request('GET', MTNMOMO::PROD_BASE_URL . '/collection/v1_0/requesttopay/' . $external_id, [
			'headers' => [
				'Ocp-Apim-Subscription-Key' => $this->subscription_key,
				'Authorization' => 'Bearer ' . $this->request_access_token()['access_token'],
				'X-Target-Environment' => $this->target_environment
			]
		]);
		
		$body = json_decode($response->getBody()->getContents(), true);
		
		return [
			'status_code' => $response->getStatusCode(),
			'reason_phrase' => $response->getReasonPhrase(),
			'body' => $body
		];
	}
	
	public static function is_mobile_mtn($mobile){
		$res = preg_match('/256(\d{9})$/', $mobile, $matches);
		
		//no match
		if($res == 0) return false;
		
		$n = $matches[1];
		return preg_match('/^(76|77|78|32|39)\d+/', $n, $matches2);

	}
	
	public function collect($mobile, $amount, $external_id = null, $payer_message = "Payer message", $payee_note = "Payee note"){
		if($external_id == null) $external_id = Uuid::uuid4()->toString();
		
		$client = new \GuzzleHttp\Client();
		$response = $client->request('POST', MTNMOMO::PROD_BASE_URL . '/collection/v1_0/requesttopay', [
			'headers' => [
				'Ocp-Apim-Subscription-Key' => $this->subscription_key,
				'Content-Type' => 'application/json',
				'Authorization' => 'Bearer ' . $this->request_access_token()['access_token'],
				'X-Reference-Id' => $external_id,
				'X-Callback-Url' => $this->callback_url,
				'providerCallbackHost' => $this->callback_host,
				'X-Target-Environment' => $this->target_environment
			],
			'json' => [
				"amount" => $amount,
				"currency" => "UGX",
				"externalId" => $external_id,
				"payer" => [
					"partyIdType" => "MSISDN",
					"partyId" => $mobile
				],
				"payerMessage" => $payer_message,
				"payeeNote" => $payee_note
			]
		]);
		
		$body = json_decode($response->getBody()->getContents(), true);
		
		return [
			'status_code' => $response->getStatusCode(),
			'reason_phrase' => $response->getReasonPhrase(),
			'body' => $body
		];	}
}