<?php

namespace App\Console\Commands;

use App\Http\Controllers\NemoExpressController;
use App\Http\Controllers\VoyagerOfferController;
use App\Status;
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
//            ->whereHas('nemoData', function (Builder $qr) {
//                $qr->where('status', '!=', 'livrat');
//            })
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

                $nemoData = $offer->nemoData;
                if ($nemoData->status == $status_response['data']['status']) {

                    $this->updateOfferStatusAccordingToNemoStatus($nemoData, $offer);

                    continue;
                }

                $nemoData->status = $status_response['data']['status'];
                $nemoData->status_date = $statusDate->format($datetimeFormat);
                $nemoData->status_message = 'AWB-ul a fost ' . $nemoData->status . ' in data de ' . $nemoData->status_date . ', ' . $status_response['message'] . ' - ' . $status_response['status'];
                $nemoData->save();
                //sleep(1);

                $this->updateOfferStatusAccordingToNemoStatus($nemoData, $offer);
            }
        }
        return 0;
    }

    public static function getNewOfferStatus(string $nemoStatus): ?int
    {
        switch ($nemoStatus) {
            case 'in_curs':
                $offerStatus = 8; //expediata
            case 'livrat':
                $offerStatus = 7; // livrata
            default:
                $offerStatus = null;
        }

        return $offerStatus;
    }

    /**
     * @param $nemoData
     * @param $offer
     * @return void
     */
    public function updateOfferStatusAccordingToNemoStatus($nemoData, $offer): void
    {
        $newOfferStatusID = static::getNewOfferStatus($nemoData->status);

        if ($newOfferStatusID) {
            if ($offer->status == $newOfferStatusID) {

                return;
            }

            $oldOfferStatus = Status::find($offer->status);
            $newOfferStatus = Status::find($newOfferStatusID);
            $offer->status = $newOfferStatus->id;
            $offer->save();

            $message = "a schimbat statusul din " . $oldOfferStatus->title . ' in ' . $newOfferStatus->title;
            VoyagerOfferController::createEvent($offer, $message, true);
        }
    }
}
