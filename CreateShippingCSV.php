<?php

namespace App\Custom;

use Storage;
use App\Models\Card;

class CreateShippingCSV{
    public static function run(){    
        $cards = Card::all(); 

        //Column headers for the CSV files -- MS Word Mail Merge
        $filetext = "first_name,last_name,address,city,state,zip\n";

        foreach($cards as $c){
            $result = array();

            //strtr() is stripping commas from input, which would mangle the entry in the csv
            $temp = strtr(ucwords($c->first_name), array(',' => ' ')).','.
            strtr(ucwords($c->last_name), array(',' => ' ')).','.
            strtr(ucwords($c->street), array(',' => ' ')).' '.strtr(ucwords($c->apt), array(',' => ' ')).','.
            strtr(ucwords($c->city), array(',' => ' ')).','.
            strtr(ucwords($c->state_abbrv), array(',' => ' ')).','.
            strtr(ucwords($c->zip), array(',' => ' '))."\n";

            $filetext .= $temp;
        }     

        $s3 = Storage::disk('s3');
        $filename = 'address_lists/addresses.csv';
        if ($s3->exists($filename)){
            $s3->delete($filename);
        }
        $s3->put($filename, $filetext);                   
    }
}

