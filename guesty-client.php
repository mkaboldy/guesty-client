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
     * @param string $method 
     * @param array $postfields 
     * @return array $api_result
     */
    private function request($endpoint, $method='GET', $postfields = []) {

        $ch = curl_init(); 
        curl_setopt($ch, CURLOPT_URL, $this->api_root . $endpoint);    
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
        curl_setopt($ch, CURLOPT_ENCODING, "");
        curl_setopt($ch, CURLOPT_MAXREDIRS, 10);
        curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
        // create request
        if ('POST' == $method) {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");  
            curl_setopt($ch, CURLOPT_POST, true);                                                                   
        }

        // update request
        if ('PUT' == $method) {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");  
            // curl_setopt($ch, CURLOPT_PUT, true);                                                                   
        }

        if (count($postfields) > 0) {
            $data_string = json_encode($postfields);
error_log($data_string);
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
            throw new Exception('HTTP return code ' . $returnCode . ' (' . $this->api_errors[$returnCode] . ') to ' . $this->api_root . $endpoint . ' ' .$result);
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

    /**
     * create a new guest object
     * @param array $guest_data
     * [
     *  'firstName' => $firstName
     *  'lastName' => $lastName
     *  'email' => $email
     * ]
     * @return string $_id the ID of the user created
     */
    public function createguest($guest_data) {
        try {
            $new_guest = $this->request('/guests/','POST');
            $updated_guest = $this->request('/guests/'. $new_guest['_id'],'PUT',$guest_data);
            return $updated_guest;
        } catch (Exception $e) {
            $err = $e->getMessage();
            throw new Exception('Failed to create new guest: ' . $err);
        }
    }
    /**
     * create a new reservation {"listingId": "59b928bb8e6bb31000219e58", "checkInDateLocalized": "2017-09-15", "checkOutDateLocalized": "2017-09-18", "status": "inquiry", "money":{"fareAccommodation": "500", "currency": "USD"}}
     * @param string $listing_id
     * @param string $guest_id
     * @param string $checkin_date
     * @param string $checkout_date
     * @param string $guest_count
     * @param string $total_goods
     * @param string $total_sale
     * @param string $already_paid
     */
    public function createreservation($listing_id, $guest_id, $checkin_date, $checkout_date, $guest_count, $total_goods, $total_sale, $already_paid) {
        $reservation_data = array(
            'listingId' => $listing_id,
            'guestId' => $guest_id,
            'checkInDateLocalized' => $checkin_date,
            'checkOutDateLocalized' => $checkout_date,
            'guestsCount' => $guest_count,
            'status' => 'confirmed',
            'money' => [
                'fareAccommodation'=> $total_goods,
                'guestTotalPrice' => $total_sale,
                'alreadyPaid' => $already_paid,
                'currency'=>'USD',
            ]
        );
        return $this->request('/reservations/','POST',$reservation_data);
    }
}

?>