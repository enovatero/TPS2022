<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Offer;
use App\NemoOrder;

class TrackStatusNemo extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'nemocourier:status';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Get last status of every order on NemoExpressCourier';

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
      $offers = Offer::whereNotNull('awb_id')->where('delivery_type', 'nemo')->get();
      if(count($offers) > 0){
        foreach($offers as $offer){
          $apiKey = $offer->cont_id == 1 ? env('NEMO_API_KEY_IASI') : env('NEMO_API_KEY_BERCENI');
          $status = \App\Http\Controllers\NemoExpressController::getStatus($apiKey, $offer->nemoData->awb);
          $status = json_decode($status['response'], true);
          
          $nemoData = NemoOrder::find($offer->awb_id);
          $nemoData->status = 'AWB-ul a fost '.$status['data']['status'].' in data de '.\Carbon\Carbon::parse()->format('Y-m-d H:i').', '.$status['message'].' - '.$status['status'];
          $nemoData->save();
        }
      }
      return 0;
    }
}
