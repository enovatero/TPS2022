<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use FanCourier;
use App\Offer;
use App\FanOrder;

class TrackStatusFan extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fancourier:status';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Get last status of every order on FanCourier';

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
      $offers = Offer::whereNotNull('awb_id')->get();
      if(count($offers) > 0){
        foreach($offers as $offer){
          config(['fancourier.client_id' => $offer->fanData->cont_id]);
          app()->bind('fancourier','SeniorProgramming\FanCourier\Services\ApiService');
          
          $status = FanCourier::trackAwb([
              'AWB' => $offer->fanData->awb, 
              'display_mode' => 1 //1 – last status, 2 – last record from history route, 3 – all history of the expedition
          ]);
          $fanData = FanOrder::find($offer->awb_id);
          $fanData->status = $status;
          $fanData->save();
        }
      }
      return 0;
    }
}
