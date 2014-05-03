<?php
    function buildBaseString($baseURI, $method, $params) {
        $r = array();
        ksort($params);
        foreach($params as $key=>$value){
            $r[] = "$key=" . rawurlencode($value);
        }
        return $method."&" . rawurlencode($baseURI) . '&' . rawurlencode(implode('&', $r));
    }

    function buildAuthorizationHeader($oauth) {
        $r = 'Authorization: OAuth ';
        $values = array();
        foreach($oauth as $key=>$value)
            $values[] = "$key=\"" . rawurlencode($value) . "\"";
        $r .= implode(', ', $values);
        return $r;
    }

    $url = "https://api.twitter.com/1.1/statuses/user_timeline.json";

    $url = "https://api.twitter.com/1.1/users/show.json";

    $oauth_access_token = "262292287-3taIzndDV4rwcWw0dOiMOZgqOCScyrzTHTD92tuA";
    $oauth_access_token_secret = "O2KGwrvolPS7QZKsQQHFfsdK8UtTu6ALDwNErBiTcz7SS";
    $consumer_key = "yBcFlje5WMUuJ0tIc2sCgc5IA";
    $consumer_secret = "s2SblsvvuA6i2UCCSicR3zbYHnp5Y9ttg7SZmCWAmEIT7j6amp";

    $input_file = fopen("twitter_in.csv","r");
    $output_file = fopen("twitter_out.csv", "w");
    while(!feof($input_file)) {
        $line = fgets($input_file);
        $line = explode(',', $line, 2);
        $real_name = $line[0];
        $screen_name = trim($line[1]);
        //var_dump($line);
        $oauth = array( 
                        'screen_name' => $screen_name,
                        'oauth_consumer_key' => $consumer_key,
                        'oauth_nonce' => time(),
                        'oauth_signature_method' => 'HMAC-SHA1',
                        'oauth_token' => $oauth_access_token,
                        'oauth_timestamp' => time(),
                        'oauth_version' => '1.0');

        $base_info = buildBaseString($url, 'GET', $oauth);
        $composite_key = rawurlencode($consumer_secret) . '&' . rawurlencode($oauth_access_token_secret);
        $oauth_signature = base64_encode(hash_hmac('sha1', $base_info, $composite_key, true));
        $oauth['oauth_signature'] = $oauth_signature;

        // Make requests
        $header = array(buildAuthorizationHeader($oauth), 'Expect:');
        $options = array( CURLOPT_HTTPHEADER => $header,
                          //CURLOPT_POSTFIELDS => $postfields,
                          CURLOPT_HEADER => false,
                          CURLOPT_URL => $url . "?screen_name=" . $screen_name,
                          CURLOPT_RETURNTRANSFER => true,
                          CURLOPT_SSL_VERIFYPEER => false);

        $feed = curl_init();
        curl_setopt_array($feed, $options);
        $json = curl_exec($feed);
        curl_close($feed);

        $twitter_data = json_decode($json, true);
        fwrite($output_file, $real_name . ',' . $twitter_data['followers_count'] . "\n");
    }
    fclose($input_file);
    fclose($output_file);
?>