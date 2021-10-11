<?php
namespace App\Http\Controllers\Admin;
use Illuminate\Routing\Controller as BaseController;

class CursBNR extends BaseController
{
    public static function getExchangeRate($currencyVal)
    {
      try{
        $xmlDocument = file_get_contents("http://www.bnro.ro/nbrfxrates.xml");
        $currency = (new self())->parseXMLDocument($xmlDocument);
        foreach($currency as $line)
        {
            if($line["name"] == $currencyVal)
            {
                return $line["value"];
            }
        }
        return 0;
      } catch(\Exception $e){ return 4.9;}
    }
    public static function parseXMLDocument($xmlDocument)
    {
       $currency = [];
       $xml = new \SimpleXMLElement($xmlDocument);
       foreach($xml->Body->Cube->Rate as $line)    
       {                      
           $currency[] = [
             "name" => $line["currency"], 
             "value" => $line, 
             "multiplier" => $line["multiplier"]
           ];
       }
      return $currency;
    }
}