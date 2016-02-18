<?php
namespace bbaisley;

class Api {
	
	const VERSION = '1.0.0';

	public $rest_client = null;

	public $response = null;
	
	public $base_url = 'https://apiv2.webdamdb.com/';
	
	public $redirect_base = null;
	
	public $state = '';
	
	protected $client_id = null;
	
	protected $client_secret = null;

	protected $client_username = null;

	protected $client_password = null;

	public $access_token_type = null;

	public $access_token = null;

	public $access_token_expires_in = null;

	public $access_expires = 0;
	
	public $refresh_token = null;
	
	public function __construct($client_id, $client_secret, $rest_client, $response) {
		$this->client_id = $client_id;
		$this->client_secret = $client_secret;
		$this->rest_client = $rest_client;
		$this->state = rtrim(base64_encode( mt_rand(1000000000,10000000000) ),'=');
		$this->response = $response;
	}

    public function setRedirectUrl($url) {
        $this->redirect_base = $url;
    }

    public function get($url_path, $params=null) {
		$request_url = $this->base_url.$url_path;
		$response = $this->rest_client->get($request_url, $params);
		$response = $this->response->process($response);
		return $response;
    }

	
	public function authUrl($redirect_uri, $response_type='code') {
    	$params = array(
    	    'response_type' => $response_type,
    	    'client_id' => $this->client_id,
    	    'redirect_uri' => $redirect_uri,
    	    'state' => $this->state
    	    );
        $url = $this->base_url . 'oauth2/authorize?' . http_build_query($params);
        return $url;
	}

	/**
	 * Get and access token using an authorization_code
	 *
	 * Once you've hit the following URL and received your authorization code
	 * you can call this method to receive an access token.
	 *
	 * https://apiv2.webdamdb.com/oauth2/authorize?response_type=code&client_id=CLIENT_ID&redirect_uri=REDIRECT_URI&state=STATE
	 *
	 * @param string $redirect_uri The redirect URI used when obtaining the auth code
	 * @param string $code         The auth_code given by WebDAM
	 *
	 * @return Presto/Response $response The response object
	 */
	public function getAccessTokenUsingAuthCode($redirect_uri, $code) {
    	$url = $this->base_url . 'oauth2/token';
    	$data = array(
    	    'grant_type' => 'authorization_code',
    	    'code' => $code,
    	    'redirect_uri' => $redirect_uri,
    	    'client_id' => $this->client_id,
    	    'client_secret' => $this->client_secret
            );
        $response = $this->rest_client->post($url, $data);

		$response->data = json_decode( $response->data );

		if ( 200 === $response->meta['http_code'] ) {
			$this->access_token = $response->data->access_token;
			$this->access_token_expires_in = $response->data->expires_in;
			$this->access_expires = strtotime( '+' . ( $this->access_token_expires_in - 20 ) . ' seconds' );
			$this->refresh_token = $response->data->refresh_token;
			$this->setAccessHeaders();
		}

        return $response;
	}

	/**
	 * Get an access token using a WebDAM username/password
	 *
	 * There are two methods of authentication:
	 * authorization code
	 * password
	 *
	 * @param string $username Your WebDAM Username
	 * @param string $password Your WebDAM Password
	 *
	 * @return Presto/Response $response The response object
	 */
	public function getAccessTokenUsingPassword( $username, $password ) {

		$url = $this->base_url . 'oauth2/token';

		$this->client_username = $username;
		$this->client_password = $password;

		$data = array(
			'grant_type'    => 'password',
			'client_id'     => $this->client_id,
			'client_secret' => $this->client_secret,
			'username'      => $this->client_username,
			'password'      => $this->client_password
		);

		$response = $this->rest_client->post( $url, $data );

		$response->data = json_decode( $response->data );

		$this->access_token = $response->data->access_token;
		$this->access_token_expires_in = $response->data->expires_in;
		$this->access_expires = strtotime( '+' . ( $this->access_token_expires_in - 20 ) . ' seconds' );
		$this->refresh_token = $response->data->refresh_token;

		if ( 200 === $response->meta['http_code'] ) {
			$this->setAccessHeaders();
		}

		return $response;
	}

	/**
	 * Set the 'Authoriation' header for all future API requests
	 *
	 * @param string $token_type The token type. Currently only 'Bearer' is supported
	 *
	 * @return null
	 */
	public function setAccessHeaders( $token_type = 'Bearer' ) {

		$this->access_token_type = $token_type;

		$this->rest_client->setHeaders(
			array( 'Authorization' => $this->access_token_type . ' ' . $this->access_token )
		);
	}

	/**
	 * Is the current access token still valid?
	 *
	 * @param null
	 *
	 * @return bool True if the token is valid, false if it is not.
	 */
	public function isAccessTokenExpired() {
		if ($this->access_expires < time()) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Refresh access to WebDAM
	 *
	 * Utilize the refresh token to request a new token.
	 * The access token is only good for 1 hour, and needs to be
	 * regenerated when making regular calls to the API.
	 *
	 * This can be called like so:
	 * if ( $api->isAccessTokenExpired() ) {
	 *		$api->refreshAccess();
	 * }
	 *
	 * @param null $refresh_token
	 *
	 * @return mixed
	 */
	public function refreshAccess($refresh_token=null) {
	    static $call_count = 0;
	    if ( $call_count>1 ) {
    	    exit();
	    }
    	$url = $this->base_url . 'oauth2/token';
    	$data = array(
    	    'grant_type'=>'refresh_token',
    	    'refresh_token'=>(is_null($refresh_token) ? $this->refresh_token : $refresh_token),
    	    'client_id'=>$this->client_id,
    	    'client_secret'=>$this->client_secret,
    	    'redirect_uri'=>$this->redirect_base
        );
        $response = $this->rest_client->post($url, $data);

		if ( $response->meta['http_code']==200 ) {
			$response->data = json_decode( $response->data );
			$this->access_token = $response->data->access_token;
			$this->access_token_expires_in = $response->data->expires_in;
			$this->setAccessHeaders();
		}

        $call_count++;
        return $response;
	}
	
	public function folders($path=null) {
    	$url = $this->base_url . 'folders';
    	if ( !is_null($path) ) {
        	$url .= '/'.$path;
    	}
    	$response = $this->rest_client->get($url);
    	return $response;
	}
	
	public function createFolder($name, $eventdate=null, $parent=0, $status='active') {
    	$url = $this->base_url . 'folders';
    	if ( is_null($eventdate) ) {
        	$eventdate = date('Y-m-d');
    	}
    	$data = array(
    	    'parent' => $parent,
    	    'name' => $name,
    	    'status' => $status,
    	    'eventdate' => $eventdate,
            );
        $data = json_encode($data);
    	$response = $this->rest_client->post($url, $data);
    	if ( $response->meta['http_code']==200 ) {
        	return true;
    	} else {
        	return false;
    	}
	}
	
	public function editFolder($id, $data) {
    	$url = $this->base_url . 'folders';
    	$data['id'] = $id;
        $data = json_encode($data);
    	$response = $this->rest_client->put($url, $data);
    	return $response;
	}
	
	public function deleteFolder($id) {
    	$url = $this->base_url . 'folders/'.$id;
     	$response = $this->rest_client->delete($url, null);
    	if ( $response->meta['http_code']==204 ) {
        	return true;
    	} else {
        	return false;
    	}
	}
	
	public function folderAssets($id, $limit=50, $offset=0, $sortby='filename', $sortdir='asc') {
    	$url = $this->base_url . 'folders/'.$id.'/assets';
    	$params = array(
    	    'sortby'=>$sortby,
    	    'sortdir'=>$sortdir,
    	    'limit'=>$limit,
    	    'offset'=>$offset
    	    );
        $response = $this->rest_client->get($url, $params);
        return $response;
	}
	
	public function asset($id) {
    	$url = $this->base_url . 'assets/'.$id;
        $response = $this->rest_client->get($url);
        return $response;
	}
	
	public function uploadAsset($folderid, $file_url) {
    	$url = $this->base_url . 'uploads';
    	
    	$file_name = basename(parse_url($file_url, PHP_URL_PATH));
	    $temp_file = tempnam(sys_get_temp_dir(), 'webdam_');
	    file_put_contents($temp_file, fopen($file_url, 'r'));
	    
    	$response = $this->rest_client->post($url, array('folderid'=>$folderid,'file'=>'@'.$temp_file.';filename='.$file_name), null, array(CURLOPT_TIMEOUT_MS=>120000) );
    	
    	unlink($temp_file);
    	return $response;
	}
	
	public function editAsset($id, $edits) {
    	$url = $this->base_url . 'assets/'.$id;
    	$response = $this->rest_client->put($url, json_encode($edits));
    	return $response;
	}
	
	public function editAssetMeta($id, $meta) {
    	$url = $this->base_url . 'assets/'.$id.'/metadatas/xmp';
    	$response = $this->rest_client->put($url, json_encode($meta));
    	return $response;
	}
	
	public function downloadAsset($id) {
    	$url = $this->base_url . 'assets/';
	}
	
	public function search($query, $limit=50, $offset=0, $sortby='filename', $sortdir='asc') {
    	$url = $this->base_url . 'search';
    	$params = array(
    	    'query'=>$query,
    	    'sortby'=>$sortby,
    	    'sortdir'=>$sortdir,
    	    'limit'=>$limit,
    	    'offset'=>$offset
    	    );
    	$response = $this->rest_client->get($url, $params);
    	return $response;
	}
	
	
	public function lightboxes() {
        $url = $this->base_url . 'lightboxes';
    	$response = $this->rest_client->get($url, $params);
    	return $response;        
	}
	
	public function createLightbox($name) {
        $url = $this->base_url . 'lightboxes';
        $data = array(
            'name'=>$name
            );
    	$response = $this->rest_client->post($url, json_encode($data));
    	return $response;        
	}
	
	public function editLightbox($id, $name) {
        $url = $this->base_url . 'lightboxes';
        $data = array(
            'id'=>$id,
            'name'=>$name
            );
    	$response = $this->rest_client->put($url, json_encode($data));
    	return $response;        
	}
	
	public function lightboxAdd($lid, $aid) {
        $url = $this->base_url . 'lightboxes/'.$lid.'/assets';
        $data = array(
            'id'=>$aid
            );
    	$response = $this->rest_client->post($url, json_encode($data));
    	return $response;        
	}

	/**
	 * GET Image Metadata
	 *
	 * Fetch XMP metadata for a given image ID
	 *
	 * @param int|array $asset_ids The asset ID(s) you're fetching data for
	 * e.g. $asset_ids = 23945510;
	 * $asset_ids = array( 23945510, 23945511, ... );
	 *
	 * @return Presto\Response $response Response object
	 */
	public function getAssetMetadata( $asset_ids = array() ) {

		if ( empty( $asset_ids ) ) {
			return false;
		}

		// Convert non-array asset id to an array so our code below
		// can confidently deal with an array
		$asset_ids = (array) $asset_ids;

		// Ensure we're dealing with integer ID's
		$asset_ids = array_map( 'intval', $asset_ids );

		// Convert our array of ID's into a comma-delimited string
		// this allows us to fetch metadata for up to 50 assets
		$asset_ids = implode( ',', $asset_ids );

		$url = "{$this->base_url}assets/$asset_ids/metadatas/xmp";

		$response = $this->rest_client->get( $url );

		if ( 200 === $response->meta['http_code'] ) {

			// Convert the string response into usable JSON
			$response->data = json_decode( $response->data );

		}

		return $response;
	}
}



