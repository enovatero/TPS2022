<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use TCG\Voyager\Models\Permission;

class AddOffersPermissions extends Migration
{
    public $permissions_group = 'offers_columns';
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $columnsToEdit = [
            'nr_com',               // Nr Comanda
            'agent',                // Agent
            'tip_comanda',          // Tip Comanda
            'client',               // Client
            'print_awb',            // Print AWB (bifa)
            'ml',                   // Metri liniari
            'accesorii',            // Accesorii
            'livrare',              // Mod Livrare
            'judet',                // Judet
            'data_expediere',       // Data Expediere
            'status',               // Stare (Status)
            'p',                    // P.
            'pjal',                 // P. JAL.
            'pu',                   // P. U.
            'intarziere',           // Intarziere
            'culoare',              // Culoare
            'plata',                // Plata
            'contabilitate',        // Contabilitate
            'comanda_distribuitor', // Comanda Distribuitor
            'fisiere',              // Fisiere
            'print_comanda',        // Print Comanda / Listat (bifa)
            'awb',                  // AWB
            'telefon',              // Telefon
            'sursa',                // Sursa
            'valoare',              // Valoare
        ];
        foreach ($columnsToEdit as $column) {
            Permission::firstOrCreate(['key' => 'column_'.$column, 'table_name' => $this->permissions_group]);
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Permission::removeFrom($this->permissions_group);
    }
}
