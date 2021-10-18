<?php

namespace App;

use Illuminate\Database\Eloquent\Model;


class RulesPrice extends Model
{
    public static function getFormulaByCategory($categoryId = 5){
      $rulePrices = RulesPrice::get();
      foreach($rulePrices as &$item){
        $ruleItem = json_decode($item->formulas, true);
        foreach($ruleItem as $key => $form){
          if($form['categorie'] != $categoryId){
            unset($ruleItem[$key]);
          }
        }
        if(count($ruleItem) <= 0){
          $ruleItem = [
            "tip_obiect"     => "category",
            "categorie"      => "5",
            "categorie_name" => "Default",
            "variabila"      => "PI",
            "operator"       => "*",
            "formula"        => null,
            "full_formula"   => "PI"
          ];
        }
        $item->formulas = $ruleItem[0];
      }
      $rulePrices = (new self())->getFormulasWithPricesByProduct($rulePrices, 21, 4.9345);
      return $rulePrices;
    }
  
    public static function getFormulasWithPricesByProduct($rulePricesFilteredByCategory, $productPrice, $currency = null){
      foreach($rulePricesFilteredByCategory as &$item){
        $formula = str_replace("PI", $productPrice, $item->formulas['full_formula']);
        $price = eval('return '.$formula.';');
        $formatedPrice = floatVal(number_format($price ,2,',', '.'));
        $itemFormulas = $item->formulas;
        $itemFormulas['price'] = $formatedPrice;
        if($currency != null){
          $itemFormulas['currency_price'] = floatVal($formatedPrice)*floatVal($currency);
        }
        $item->formulas = $itemFormulas;
      }
      return $rulePricesFilteredByCategory;
    }
}
