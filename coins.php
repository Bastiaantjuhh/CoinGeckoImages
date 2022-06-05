<?php

class CoinGeckoImages {

    public function execute($testRun = false) {

        echo $this->consoleHeader("CoinGeckoImages");

        // Check if the directory exists. If not create one
        if (!file_exists("./images-coins")) {
            mkdir("./images-coins", 0777);
        }

        // NOTE: Nothing special, used for testing only
        // The testfile contains a couple coins for testing.
        if($testRun == true) {
            $json = json_decode(file_get_contents("./test/list-coins.json"), true);

        } else {
            $json = json_decode($this->httpRequest("https://api.coingecko.com/api/v3/coins/list"), true);
        }

        $totalCount = count($json) - 1;
                    
        for ($i=0; $i <= $totalCount; $i++) { 

            if($this->fileCheck($json[$i]["id"]) == false) {
    
                echo $this->console($json[$i]["id"]);

                $jsonTarget = json_decode($this->httpRequest("https://api.coingecko.com/api/v3/coins/" . $json[$i]["id"]), false);
                $imageUrl = $jsonTarget->image->large;

                if($imageUrl !== null) {

                    // Need to take the filetype to extension conversion
                    $extension = pathinfo(parse_url($imageUrl, PHP_URL_PATH), PATHINFO_EXTENSION);

                    // Making all lowerCase because otherwise you can have .PNG instead of .png
                    // CoinGecko has saved an published some images with capital extensions
                    $filename = strtolower("./images-coins/" . $jsonTarget->id . "." . $extension);
                    
                    // Simple one liner
                    // Using PHP-GD extension to make a PNG file from the input. 
                    file_put_contents($filename, $this->httpRequest($imageUrl));
                }
                
                // Sleeping is needed because of the CoinGecko API ratelimit.
                usleep(750000);
            }
        }

        // Displays in hours because of lots of crypto takes a lot of time.
        echo $this->consoleHeader("FINISHED");

    }

    private function httpRequest($endpoint) {

        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => $endpoint,

            /*
            BUG: SSL operation failed with code 1... & Failed to enable crypto in...
                 Solution disable SSL verification. Good solution? Nope. Might fix later
            */

            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_HEADER => false,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_2_0,
            CURLOPT_CUSTOMREQUEST => "GET",
        ));
    
        $response = curl_exec($curl);
        curl_close($curl);

        return $response;

    }

    private function console($input) {

        // Safe input
        if($input == null) {
            $input = "n/a";
        }

        // Date time
        // EXAMPLE: [23:57:04] ethereum
        $dt = '[' . date("H:i:s") . '] ';

        return  $dt . $input . PHP_EOL;
    }

    private function consoleHeader($input) {  
        $line = "----------------------------------------------------------------------------" . PHP_EOL;

        $return = PHP_EOL . $line;
        $return .= $input . PHP_EOL;
        $return .= $line . PHP_EOL;

        return $return;
    }

    private function fileCheck($id) {

        // The empty-extension (last in Array) is needed because two files use no extensions and just a dot
        foreach([".png", ".jpg", ".webp", ".jpeg", ".ico", ".svg", ".gif", "."] as $extension) {
            if(file_exists("./images-coins/" . $id . $extension)) {
                return true;
            }
        }

        return false;
    }
}

$cgi = new CoinGeckoImages();
$cgi->execute();
