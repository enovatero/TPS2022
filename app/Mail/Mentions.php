<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

class Mentions extends Mailable
{
    use Queueable, SerializesModels;
  
    public $offer_id = false;
    public $message = false;
    public $agent = false;
    public $offer_serie = false;
    public $user_name = false;
    public $is_admin = false;
    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($offer_id, $offer_serie, $message, $agent, $user_name, $is_admin)
    {
      $this->offer_id = $offer_id;
      $this->message = $message;
      $this->agent = $agent;
      $this->offer_serie = $offer_serie;
      $this->user_name = $user_name;
      $this->is_admin = $is_admin;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $this->withSwiftMessage(function ($message) {
        $message->getHeaders()
                ->addTextHeader('Custom-Header', 'HeaderValue');
        });
        return $this->markdown('emails.mention_email')->subject('Mesaj intern TPS - oferta '.$this->offer_serie);
    }
}