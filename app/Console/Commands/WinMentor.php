<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Product;
use App\Unit;

class WinMentor extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'winmentor:fetch';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fetch data from WinMentor and handle it to our database';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
      $insertProducts = [];
      $counterInsert = 0;
      $counterUpdate = 0;
      // url final pentru preluare produse din mentor "pe incredere" fara token sau metoda de autentificare, momentan
      $url = "http://".config('winmentor.host').":".config('winmentor.port')."/datasnap/rest/TServerMethods/%22GetInfoArticole%22/";
      //  Initiate curl
      $ch = curl_init();
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
      curl_setopt($ch, CURLOPT_URL,$url);
      $result=curl_exec($ch);
      curl_close($ch);
      $winMentorFetch = json_decode($result, true);
      // daca am produse, atunci le iau din InfoArticole
      if(count($winMentorFetch) > 0 && $winMentorFetch['result'] == "ok"){
        $winMentorProducts = $winMentorFetch['InfoArticole'];
      }
      $createdAt = date('Y-m-d H:i:s');
      foreach($winMentorProducts as $key => $product){
        $checkProduct = Product::where('mentor_cod_intern', $product['CodIntern'])->first();
        if($checkProduct != null){
          $checkProduct->name = $product['Denumire'];
          $checkProduct->status = $product['Inactiv'] == "Da" ? 0 : 1;
          $checkProduct->mentor_cod_obiect = $product['CodObiect'];
          $checkProduct->mentor_description = null;
          $checkProduct->mentor_cod_intern = $product['CodIntern'];
          $checkProduct->created_at = $createdAt;
          $checkProduct->updated_at = $createdAt;
          $checkProduct->mentor_response = json_encode($product);
          $checkProduct->save();
          $counterUpdate++;
        } else{
          array_push($insertProducts,
          [
            'name' => $product['Denumire'],
            'status' => $product['Inactiv'] == "Da" ? 0 : 1,
            'mentor_cod_obiect' => $product['CodObiect'],
            'mentor_description' => null,
            'mentor_cod_intern' => $product['CodIntern'],
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
            'mentor_response' => json_encode($product),
          ]);
          $counterInsert++;
        }
        $unit = Unit::where('title', $product['UM'])->first();
        if($unit == null){
          $unit = new Unit;
          $unit->title = $product['UM'];
          $checkProduct->created_at = $createdAt;
          $checkProduct->updated_at = $createdAt;
          $unit->save();
        }
      }
      $insertion = null;
      if(count($insertProducts) > 0){
        $insertion = Product::insert($insertProducts);
      }
      return ['success' => true, 'msg' => "Au fost adaugate ".$counterInsert." produse si modificare ". $counterUpdate." produse."];
    }
}
