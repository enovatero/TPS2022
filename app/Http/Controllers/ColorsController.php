<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Validator;
use App\Attribute;
use App\Color;
use App\AttributeColor;
use App\AttributeDimension;
use App\Dimension;
use App\OffertypePreselectedColor;
use App\OfferType;
use App\ProductAttribute;

class ColorsController extends Controller
{
  // am facut asta la cererea lor de a importa dintr-un fisier culorile, pentru a nu fi introduse manual de catre ei. 
  // Ignor asta pentru ca trebuie, la cererea lor, sa schimb toate json-urile in tabele separate, cu legaturi intre ele
//   public function generatePDF(Request $request){
  public function uploadColors(){
    
    $prodattrs = ProductAttribute::get();
    foreach($prodattrs as $prodattr){
      $atColor = null;
      $atDimension = null;
      if($prodattr->getType() == 1){
        $atColor = AttributeColor::where('attribute_id', $prodattr->attribute_id)->inRandomOrder()->first();
      } else{
        $atDimension = AttributeDimension::where('attribute_id', $prodattr->attribute_id)->inRandomOrder()->first();
      }
      $prodattr->color_id = $atColor != null ? $atColor->color_id : null;
      $prodattr->dimension_id = $atDimension != null ? $atDimension->dimension_id : null;
      $prodattr->save();
    }
    dd("Updatate");
    // ultimul upload
    $createdAt = date("Y-m-d H:i:s");
    $colors = 
    [
      [
        'color_code' => '3011 BGM',
        'selected_color_code' => '3009',
        'both' => true,
      ],
      [
        'color_code' => '7024 BGM',
        'selected_color_code' => '7024',
        'both' => true,
      ],
      [
        'color_code' => '8017 BGM',
        'selected_color_code' => '8017',
        'both' => true,
      ],
      [
        'color_code' => '8019 BGM',
        'selected_color_code' => '8019',
        'both' => true,
      ],
      [
        'color_code' => '3005 HIMAT',
        'selected_color_code' => '3005',
        'both' => true,
      ],
      [
        'color_code' => '3009 HIMAT',
        'selected_color_code' => '3009',
        'both' => true,
      ],
      [
        'color_code' => '6005 HIMAT',
        'selected_color_code' => '6020',
        'both' => true,
      ],
      [
        'color_code' => '7024 HIMAT',
        'selected_color_code' => '7024',
        'both' => true,
      ],
      [
        'color_code' => '8017 HIMAT',
        'selected_color_code' => '8017',
        'both' => true,
      ],
      [
        'color_code' => '8019 HIMAT',
        'selected_color_code' => '8019',
        'both' => true,
      ],
      [
        'color_code' => '9005 HIMAT',
        'selected_color_code' => '9005',
        'both' => true,
      ],
      [
        'color_code' => 'ZINCAT',
        'selected_color_code' => 'ZINCAT',
        'both' => false,
      ],
    ];
    dd($colors);
//         $colors = 
//     [
//       [
//         'color_code' => '3011 BGM',
//         'selected_color_code' => '3009',
//         'both' => true,
//       ],
//       [
//         'color_code' => '7024 BGM',
//         'selected_color_code' => '7016',
//         'both' => true,
//       ],
//       [
//         'color_code' => '8017 BGM',
//         'selected_color_code' => '8017',
//         'both' => true,
//       ],
//       [
//         'color_code' => '8019 BGM',
//         'selected_color_code' => '8019',
//         'both' => true,
//       ],
//       [
//         'color_code' => '3005 HIMAT',
//         'selected_color_code' => '3005',
//         'both' => true,
//       ],
//       [
//         'color_code' => '3009 HIMAT',
//         'selected_color_code' => '3009',
//         'both' => true,
//       ],
//       [
//         'color_code' => '6005 HIMAT',
//         'selected_color_code' => '6020',
//         'both' => true,
//       ],
//       [
//         'color_code' => '7024 HIMAT',
//         'selected_color_code' => '7024',
//         'both' => true,
//       ],
//       [
//         'color_code' => '8017 HIMAT',
//         'selected_color_code' => '8017',
//         'both' => true,
//       ],
//       [
//         'color_code' => '8019 HIMAT',
//         'selected_color_code' => '8019',
//         'both' => true,
//       ],
//       [
//         'color_code' => '9005 HIMAT',
//         'selected_color_code' => '7016/8019',
//         'both' => true,
//       ],
//       [
//         'color_code' => '7024 BGM-D',
//         'selected_color_code' => '7016',
//         'both' => true,
//       ],
//       [
//         'color_code' => '8017 BGM-D',
//         'selected_color_code' => '8017',
//         'both' => true,
//       ],
//       [
//         'color_code' => '8019 BGM-D',
//         'selected_color_code' => '8019',
//         'both' => true,
//       ],
//       [
//         'color_code' => 'ALB ALPIN D',
//         'selected_color_code' => '9002',
//         'both' => true,
//       ],
//       [
//         'color_code' => 'CAMUFLAJ',
//         'selected_color_code' => '6020',
//         'both' => true,
//       ],
//       [
//         'color_code' => 'MAHON',
//         'selected_color_code' => '8017',
//         'both' => true,
//       ],
//       [
//         'color_code' => 'MERISOR',
//         'selected_color_code' => '6020',
//         'both' => true,
//       ],
//       [
//         'color_code' => 'NUC',
//         'selected_color_code' => '8017',
//         'both' => true,
//       ],
//       [
//         'color_code' => 'STEJAR',
//         'selected_color_code' => '8004',
//         'both' => true,
//       ],
//       [
//         'color_code' => 'STEJAR ALB',
//         'selected_color_code' => '9006',
//         'both' => true,
//       ],
//       [
//         'color_code' => 'ZID GRANIT',
//         'selected_color_code' => '8017',
//         'both' => true,
//       ],
//       [
//         'color_code' => 'QUARTZ',
//         'selected_color_code' => '9006',
//         'both' => true,
//       ],
//       [
//         'color_code' => 'GRANIT IMPERIAL',
//         'selected_color_code' => '8017',
//         'both' => true,
//       ],
//       [
//         'color_code' => 'MAHON D',
//         'selected_color_code' => '8017',
//         'both' => true,
//       ],
//       [
//         'color_code' => 'NUC D',
//         'selected_color_code' => '8017',
//         'both' => true,
//       ],
//       [
//         'color_code' => 'STEJAR D',
//         'selected_color_code' => '8004',
//         'both' => true,
//       ],
//     ];
    
    $counter = 0;
    $offerTypes = OfferType::whereNotIn('id', [14, 15])->get();
    foreach($offerTypes as $offerType){
      foreach($colors as $cols){
        $colorCode = Color::where('ral', $cols['color_code'])->first();
        if($colorCode == null){
          $colorCode = new Color();
          $colorCode->value = '#000000';
          $colorCode->ral = $cols['color_code'];
          $colorCode->created_at = $createdAt;
          $colorCode->updated_at = $createdAt;
          $colorCode->save();
        }
        $colorSelected = Color::where('ral', $cols['selected_color_code'])->first();
        if($colorSelected == null){
          $colorSelected = new Color();
          $colorSelected->value = '#000000';
          $colorSelected->ral = $cols['selected_color_code'];
          $colorSelected->created_at = $createdAt;
          $colorSelected->updated_at = $createdAt;
          $colorSelected->save();
        }
        $offTpCol = new OffertypePreselectedColor;
        $offTpCol->offer_type_id = $offerType->id;
        $offTpCol->attribute_id = 11;
        $offTpCol->color_id = $colorCode->id;
        $offTpCol->selected_color_id = $colorSelected->id;
        $offTpCol->created_at = $createdAt;
        $offTpCol->updated_at = $createdAt;
        $offTpCol->save();
        $counter++;
        if($cols['both']){
          $offTpCol = new OffertypePreselectedColor;
          $offTpCol->offer_type_id = $offerType->id;
          $offTpCol->attribute_id = 12;
          $offTpCol->color_id = $colorCode->id;
          $offTpCol->selected_color_id = $colorSelected->id;
          $offTpCol->created_at = $createdAt;
          $offTpCol->updated_at = $createdAt;
          $offTpCol->save();
          $counter++;
        }
      }
    }
    dd('Inserted '.$counter.' colors');

  }
  
}
