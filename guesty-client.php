<?php 
/**
 * Simple PHP wrapper class to maintan connectivity with guesty API
 */
class Guesty_Client {

    private $api_key, $api_secret;
    private $api_root = 'https://api.guesty.com/api/v2';
    private $api_errors = [
        400 => 'Bad Request -- Your request is invalid.',
        401 => 'Unauthorized -- Your API key is wrong.',
        403 => 'Forbidden -- The kitten requested is hidden for administrators only.',
        404 => 'Not Found -- The specified kitten could not be found.',
        405 => 'Method Not Allowed -- You tried to access a kitten with an invalid method.',
        406 => 'Not Acceptable -- You requested a format that isn\'t json.',
        410 => 'Gone -- The kitten requested has been removed from our servers.',
        418 => 'I\'m a teapot.',
        429 => 'Too Many Requests -- You\'re requesting too many kittens! Slow down!',
        500 => 'Internal Server Error -- We had a problem with our server. Try again later.',
        503 => 'Service Unavailable -- We\'re temporarily offline for maintenance. Please try again later.',
    ];

    public function __construct($api_key, $api_sectret) {
        $this->api_key = $api_key;
        $this->api_secret = $api_sectret;
        if (!$this->api_key) {
            throw new Exception('Guesty API key not provided');
        }
        if (!$this->api_secret) {
            throw new Exception('Guesty API secret not provided');
        }
    }

    /**
     * Raw request, used internally
     * @param string $endpoint 
     * @param array $postfields 
     */
    private function request($endpoint, $postfields = []) {

        $ch = curl_init(); 
        curl_setopt($ch, CURLOPT_URL, $this->api_root . $endpoint);    
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 20);

        if (count($postfields) > 0) {
            $data_string = json_encode($postfields);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");  
            curl_setopt($ch, CURLOPT_POST, true);                                                                   
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);                                                                  
        }            

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);     
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(   
            'Accept: application/json',
            'Content-Type: application/json',
            'Authorization: Basic ' . base64_encode($this->api_key.':'.$this->api_secret)
            )
        );
                
        if( ($result = curl_exec($ch)) === false) {
            $curl_error = curl_error($ch);
            curl_close($ch);
            throw new Exception('Curl error: ' . $curl_error);
        }                                                                                                      
        if ( ($returnCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE )) !== 200) {
            curl_close($ch);
            throw new Exception('HTTP return code ' . $returnCode . ' (' . $this->api_errors[$returnCode] . ') to ' . $this->api_root . $endpoint);
        }
        curl_close($ch);
        return json_decode($result, true);
    }

    /**
     * Get all listings of a single accout
     */

    public function getlistings() {
        return $this->request('/listings');
    }

    /**
     * Get one listing of $id
     * @param string $id listing ID
     */

    public function getlisting($id) {
        if (!$id) {
            throw new Exception('ID must be specified to retrieve a listing');
        }
        return $this->request('/listings/' . $id);
    }

    /**
     * Get the availability calendar of a listing
     * @param string $id Listing ID
     * @param string $from Calendar from, format YYYY-MM-DD
     * @param string $to Calendar to, format YYYY-MM-DD
     */
    public function getcalendar($id, $from, $to) {
        if (!($id && $from && $to)) {
            throw new Exception('ID , from and to must be specified to retrieve a calendar');
        }
        return $this->request('/listings/' . $id .'/calendar?from=' . $from . '&to=' . $to );
    }
}

?>