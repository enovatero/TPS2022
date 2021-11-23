<?php

namespace App;

use Illuminate\Database\Eloquent\Model;


class RulesPrice extends Model
{
    public static function getFormulaByCategory($categoryId = 5){
      $rulePrices = RulesPrice::get();
      foreach($rulePrices as $key1 => &$item){
        $ruleItem = json_decode($item->formulas, true);
        foreach($ruleItem as $key => $form){
          if($form['categorie'] != $categoryId){
            unset($ruleItem[$key]);
          }
        }
        if($ruleItem == null || count($ruleItem) == 0){
          $ruleItem[0] = [
            "tip_obiect"     => "category",
            "categorie"      => "5",
            "categorie_name" => "Default",
            "variabila"      => "PI",
            "operator"       => "*",
            "formula"        => null,
            "full_formula"   => "PI"
          ];
        }
        $ruleItem = array_values($ruleItem);
        $item->formulas = $ruleItem[0];
      }
//       $rulePrices = (new self())->getFormulasWithPricesByProduct($rulePrices, 21, 4.9345);
      return $rulePrices;
    }
  
    public static function getFormulasWithPricesByProduct($rulePricesFilteredByCategory, $productPrice = null, $currency = null){
      $tva = floatVal(setting('admin.tva_products'))/100;
      foreach($rulePricesFilteredByCategory as &$item){
        $formula = str_replace("PI", $productPrice, $item['formulas']['full_formula']);
        $price = eval('return '.$formula.';');
        $formatedPriceFormula = floatVal(number_format($price ,2,'.', ''));
        $itemFormulas = $item['formulas'];
        $itemFormulas['price'] = number_format($formatedPriceFormula, 2, '.', '');
        if($currency == null){
          $currency = 1;
        }
        $priceWithCurrency = $productPrice*$currency;
        
        $priceWithTva = $priceWithCurrency+($priceWithCurrency*$tva);
        $itemFormulas['currency_price'] = number_format(floatVal($formatedPriceFormula)*floatVal($currency), 2, '.', '');
        $itemFormulas['eur_prod_price'] = number_format($productPrice, 2, '.', '');
        $itemFormulas['ron_cu_tva'] = number_format($priceWithTva, 2, '.', '');
        $itemFormulas['ron_fara_tva'] = number_format($productPrice*$currency, 2, '.', '');
        
        $itemFormulas['product_price'] = number_format(floatVal($productPrice)*floatVal($currency), 2, '.', '');
        $itemFormulas['product_price_tva'] = number_format($productPrice+$productPrice*$tva, 2, '.', '');
        $item['formulas'] = $itemFormulas;
      }
      return $rulePricesFilteredByCategory;
    }
  
}
