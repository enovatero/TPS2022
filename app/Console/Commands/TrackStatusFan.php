<?php

namespace App\Console\Commands;

use App\Http\Controllers\VoyagerOfferController;
use App\Status;
use Illuminate\Console\Command;
use FanCourier;
use App\Offer;

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

    public const FAN_STATUS = [
        1 => 'Expeditie in livrare',
        2 => 'Livrat',
        3 => 'Avizat',
        4 => 'Adresa incompleta',
        5 => 'Adresa gresita, destinatar mutat',
        6 => 'Refuz primire',
        7 => 'Refuz plata transport',
        8 => 'Livrare din sediul FAN Courier',
        9 => 'Redirectionat',
        10 => 'Adresa gresita, fara telefon',
        11 => 'Avizat si trimis SMS',
        12 => 'Contactat, livrare ulterioara',
        14 => 'Restrictii acces la adresa',
        15 => 'Refuz predare ramburs',
        16 => 'Retur la termen',
        19 => 'Adresa incompleta - trimis SMS',
        20 => 'Adresa incompleta, fara telefon',
        21 => 'Avizat, lipsa persoana de contact',
        22 => 'Avizat, nu are bani de rbs',
        24 => 'Avizat, nu are imputernicire/CI',
        25 => 'Adresa gresita - trimis SMS',
        27 => 'Adresa gresita, nr telefon gresit',
        28 => 'Adresa incompleta,nr telefon gresit',
        30 => 'Nu raspunde la telefon',
        33 => 'Retur solicitat',
        34 => 'Afisare',
        35 => 'Retrimis in livrare',
        37 => 'Despagubit',
        38 => 'AWB neexpediat',
        42 => 'Adresa gresita',
        43 => 'Retur',
        46 => 'Predat punct Livrare',
        47 => 'Predat partener extern',
        49 => 'Activitate suspendata',
        50 => 'Refuz confirmare',
    ];

    private const DATE_TIME_FORMAT = 'Y-m-d H:i:s';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $offers = Offer::whereNotNull('awb_id')
            ->where('delivery_type', 'fan')
            ->whereNotIn('status', [3, 7])
            ->take(200)
            ->get();

        if (count($offers) > 0) {
            foreach ($offers as $offer) {
                config(['fancourier.client_id' => $offer->fanData->cont_id]);
                app()->bind('fancourier', 'SeniorProgramming\FanCourier\Services\ApiService');

                $fanResponse = FanCourier::trackAwb([
                    'AWB' => $offer->fanData->awb,
                    'display_mode' => 1
                    //1 – last status, 2 – last record from history route, 3 – all history of the expedition
                ]);

                if (empty($fanResponse)) {
                    continue;
                }

                $fanResponseArray = explode(",", $fanResponse);
                if (count($fanResponseArray) != 3) {
                    continue;
                }
                //dd($fanResponseArray);


                $fanStatus = $fanResponseArray[0];
                if (!in_array($fanStatus, array_keys(static::FAN_STATUS))) {
                    continue;
                } else {
                    $fanStatusLabel = static::FAN_STATUS[$fanStatus];
                }

                $fanStatusMessage = $fanResponseArray[1];
                $fanStatusDate = $fanResponseArray[2];
                $statusDate = new \DateTime($fanStatusDate);

                $fanData = $offer->fanData;

                if ($fanData->status == $fanStatusLabel) {

                    $this->updateOfferStatusAccordingToFanStatus($fanStatus, $offer);

                    continue;
                }

                $fanData->status = $fanStatusLabel;
                $fanData->status_date = $statusDate->format(static::DATE_TIME_FORMAT);
                $fanData->status_message = 'Statusul AWB-ului a fost: ' . $fanData->status . ', in data de ' . $fanData->status_date . ', ' . $fanStatusMessage . ' - ' . $fanStatus;
//                dd($fanData);
                $fanData->save();
                //sleep(1);

                $this->updateOfferStatusAccordingToFanStatus($fanStatus, $offer);
            }
        }

        return 0;
    }

    public static function getNewOfferStatus(string $fanStatus): ?int
    {
        switch ($fanStatus) {
            case '1':
                $offerStatus = 8; //expediata
                break;
            case '2':
                $offerStatus = 7; // livrata
                break;
            default:
                $offerStatus = null;
        }

        return $offerStatus;
    }

    public function updateOfferStatusAccordingToFanStatus(int $fanStatus, Offer $offer): void
    {
        $ignoredStatuses = [
            3, //anulata
        ];
        $newOfferStatusID = static::getNewOfferStatus($fanStatus);

        if (!$newOfferStatusID || $offer->status == $newOfferStatusID || in_array($offer->status, $ignoredStatuses)) {
            return;
        }


        $oldOfferStatus = Status::find($offer->status);
        $newOfferStatus = Status::find($newOfferStatusID);
        $offer->status = $newOfferStatus->id;
        $offer->save();

        $message = "a schimbat statusul din " . $oldOfferStatus->title . ' in ' . $newOfferStatus->title;
        VoyagerOfferController::createEvent($offer, $message, false, true);
    }
}
