<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\URL;
use FanCourier;
use Validator;

use App\Offer;
use App\OfferDoc;
use PDF;
use GuzzleHttp\Client as GuzzleClient;

class VoyagerUploadFilesController extends Controller
{

  public function uploadDocuments(Request $request){
    $form_data = $request->only(['files', 'offer_id']);
    $validationRules = [
        'files'    => ['required'],
        'offer_id'    => ['required'],
    ];
    $validationMessages = [
      'files.required'    => "Este necesar cel putin un fisier pentru a fi incarcat!",
      'offer_id.required'    => "Nu ai selectat nicio comanda pentru a incarca fisiere!",
    ];
    $validator = Validator::make($form_data, $validationRules, $validationMessages);
    if ($validator->fails()){
        return ['success' => false, 'msg' => $validator->errors()->all()];  
    }
    $offer_id = $form_data['offer_id'];
    $files = $request->file('files');
    $uploaded_files = [];
    // verific daca am fisiere
    if($files){
      // trec prin toate fisierele si iau datele fiecarui fisier
      foreach($files as $file){
        $uploaded_file = $this->saveFile($file, 'uploaded_documents');
        $createdAt = date("Y-m-d H:i:s");
        // creez un obiect offerDoc pe care-l populez cu date
        $offerDoc = new OfferDoc();
        $offerDoc->uploaded_file = $uploaded_file['file_path'];
        $offerDoc->file_name = $uploaded_file['file_name'];
        $offerDoc->offer_id = $offer_id;
        $offerDoc->created_at = $createdAt;
        $offerDoc->updated_at = $createdAt;
        $offerDoc->save();
        // creez array-ul cu fisierele incarcate pentru a le afisa in frontend
        array_push($uploaded_files, [
          'file_name' => $offerDoc->file_name,
          'doc_id' => $offerDoc->id,
        ]);
      }
      return ['success' => true, 'msg' => 'Fisierele au fost incarcate cu succes!', 'uploaded_files' => $uploaded_files];
    }
    return ['success' => false, 'msg' => 'S-a produs o eroare la incarcarea fisierelor... Reincercati!'];
  }
  
  // sterg un fisier pe baza id-ului fisierului
  public function deleteOfferDoc(Request $request){
    $form_data = $request->only('offer_doc_id');
    $validationRules = [
        'offer_doc_id'    => ['required'],
    ];
    $validationMessages = [
      'offer_doc_id.required'    => "Nu ai selectat niciun fisier pentru a-l incarca!",
    ];
    // fac validarile dupa offer_doc_id
    $validator = Validator::make($form_data, $validationRules, $validationMessages);
    if ($validator->fails()){
        return ['success' => false, 'msg' => $validator->errors()->all()];  
    }
    // iau fisierul din baza de date
    $offerDoc = OfferDoc::find($form_data['offer_doc_id']);
    if($offerDoc){ 
      // il sterg din storage
      unlink(Storage::disk('local')->path($offerDoc->uploaded_file));
      // sterg fisierul din baza de date
      $offerDoc->delete();
      return ['success' => true, 'msg' => 'Fisierul a fost sters cu succes!'];
    }
    return ['success' => false, 'msg' => 'S-a produs o eroare la stergerea fisierului... Reincercati!'];
  }
  // salvez fisierul in discul local in locatia pe care o doresc
  public static function saveFile($file, $location){
    $disk = "local";
    $new_file_name = $file->getClientOriginalName();
    $file_path = $file->storeAs($location.'/'.date("Y-m-d H:i:s"), $new_file_name, $disk);
    return ['file_path' => $file_path, 'file_name' => $new_file_name];
  }
  
  public static function retrieveTempUrl($file_id){
    $offerDocFile = OfferDoc::find($file_id);
    if($offerDocFile == null){
      abort(401, 'Documentul a expirat sau nu exista pe server!');
    }
    return response()->download(Storage::disk('local')->path($offerDocFile->uploaded_file));
  }
  
}
