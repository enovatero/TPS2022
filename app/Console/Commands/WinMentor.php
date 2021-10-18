<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

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
      $winMentorFetch = '{"result":"ok","InfoArticole":[{"Denumire":"TEU SCURGERE","UM":"Buc","PretVanzare":"0","PretCuTVA":"0","PretValuta":"0","Clasa":"","CategoriePretImplicita":"","SimbolClasa":"","Producator":"","IDProducator":"","GestiuneImplicita":"101","CodExtern":"TeuScurgere","CodIntern":"0","ProcentTVA":"19","UMImplicita":"","ParitateUMImplicita":"","Masa":"1","Serviciu":"NU","CodVamal":"","PretMinim":"","DataAdaugarii":"20.08.2019","VizibilComenziOnline":"DA","CodCatalog":"","Promotie":"NU","ZilePlata":"","Inactiv":"NU"},{"Denumire":"TEU SCURGERE 9010 150/100","UM":"Buc","PretVanzare":"0","PretCuTVA":"0","PretValuta":"0","Clasa":"","CategoriePretImplicita":"","SimbolClasa":"","Producator":"","IDProducator":"","GestiuneImplicita":"101","CodExtern":"TeuScurgere9010150/100","CodIntern":"4012","ProcentTVA":"19","UMImplicita":"","ParitateUMImplicita":"","Masa":"1","Serviciu":"NU","CodVamal":"","PretMinim":"","DataAdaugarii":"05.09.2019","VizibilComenziOnline":"DA","CodCatalog":"","Promotie":"NU","ZilePlata":"","Inactiv":"NU"}],"ErrorList":[]}';
      $winMentorFetch = json_decode($winMentorFetch, true);
      if(count($winMentorFetch) > 0 && $winMentorFetch['result'] == "ok"){
        $winMentorProducts = $winMentorFetch['InfoArticole'];
      }
      $createdAt = date('Y-m-d H:i:s');
      foreach($winMentorProducts as $product){
        $insertUpdateProduct = [
          'name' => $product['Denumire'],
          'status' => $product['Inactiv'] == "Da" ? 0 : 1,
          'mentor_id' => $product['CodExtern'],
          'mentor_description' => null,
          'price' => '',
          'created_at' => $createdAt,
          'updated_at' => $createdAt,
          'attributes' => null,
          'mentor_response' => json_encode($product),
        ];
      }
      return $productsRetrieved;
    }
}
