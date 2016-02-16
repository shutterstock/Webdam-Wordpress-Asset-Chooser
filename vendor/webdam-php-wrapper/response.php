<?php
namespace bbaisley;

class Response {
    public $content_type = null;
    
    public $extract_data = true;
    
    public function __construct($extract_data=true, $content_type='application/json') {
        $this->content_type = $content_type;
        $this->extract_data = $extract_data;
    }
    
    public function process($response) {
		if ( $response->is_success ) {
			if ( strtolower(trim($response->header['Content-Type'])) == $this->content_type || is_null($this->content_type) ) {
				$response->data = json_decode($response->data, true);
                if ($this->extract_data) {
                    $response = $response->data;
                }
			} else {
				trigger_error($response->data, E_USER_ERROR);
			}
		} else {
			trigger_error($response->error, E_USER_ERROR);
		}
		return $response;
	}

}
