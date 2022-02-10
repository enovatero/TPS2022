<?php

namespace App\Console\Commands;

use App\Http\Controllers\NemoExpressController;
use Illuminate\Console\Command;
use App\Offer;
use App\NemoOrder;
use Illuminate\Database\Eloquent\Builder;

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
        $offers = Offer::whereNotNull('awb_id')
            ->where('delivery_type', 'nemo')
            ->whereHas('nemoData', function (Builder $qr) {
                $qr->where('status', '!=', 'livrat');
            })
            ->take(100)->get();
        if (count($offers) > 0) {
            foreach ($offers as $offer) {
                $apiKey = $offer->cont_id == 1 ? env('NEMO_API_KEY_IASI') : env('NEMO_API_KEY_BERCENI');
                $status_response = NemoExpressController::getStatus($apiKey, $offer->nemoData->awb);
                $status_response = json_decode($status_response['response'], true);

                $timestamp = $status_response['data']['date'];
                $datetimeFormat = 'Y-m-d H:i:s';
                $statusDate = new \DateTime();
                $statusDate->setTimestamp($timestamp);

                $now = (new \DateTime())->format($datetimeFormat);

                $nemoData = NemoOrder::find($offer->awb_id);
                $nemoData->status = $status_response['data']['status'];
                $nemoData->status_date = $statusDate->format($datetimeFormat);
                $nemoData->updated_at = $now;
                $nemoData->status_message = 'AWB-ul a fost ' . $nemoData->status . ' in data de ' . $nemoData->status_date . ', ' . $status_response['message'] . ' - ' . $status_response['status'];
                $nemoData->save();
                //sleep(1);
            }
        }
        return 0;
    }
}
