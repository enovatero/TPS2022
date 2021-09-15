<?php

namespace App\Admin\FormFields;

use TCG\Voyager\FormFields\AbstractHandler;

class AddressFormField extends AbstractHandler
{
    protected $codename = 'address';

    public function createContent($row, $dataType, $dataTypeContent, $options)
    {
        return view('vendor.voyager.formfields.address', [
            'row' => $row,
            'options' => $options,
            'dataType' => $dataType,
            'dataTypeContent' => $dataTypeContent
        ]);
    }
}