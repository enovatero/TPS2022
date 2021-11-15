<div class="wg__dashboard--container">
  <div style="border-left: 5px solid #c64284;" class="wg__products">
    <div class="wg__products--container">
      <img src="/images/shipping.svg" />
      <span class="wg__products--bar"> </span>
      <div class="wg__nr-pr">
        <span class="wg__text">Produse</span>
        <span class="wg__quantity">{{$products}}</span>
      </div>
    </div>
  </div>
  <div style="border-left: 5px solid #38AFAF;" class="wg__products">
    <div class="wg__products--container">
      <img src="/images/profits.svg" />
      <span class="wg__products--bar"> </span>
      <div class="wg__nr-pr">
        <span class="wg__text">Profit azi</span>
        <span class="wg__quantity">{{number_format($todayProfit, 2, '.','')}} lei</span>
      </div>
    </div>
  </div>
  <div style="border-left: 5px solid #C715EB;" class="wg__products">
    <div class="wg__products--container">
      <img src="/images/clients.svg" />
      <span class="wg__products--bar"> </span>
      <div class="wg__nr-pr">
        <span class="wg__text">Clienti</span>
        <span class="wg__quantity">{{$clients}}</span>
      </div>
    </div>
  </div>

  <div style="border-left: 5px solid #EBAC15;" class="wg__products">
    <div class="wg__products--container">
      <img src="/images/users.svg" />
      <span class="wg__products--bar"> </span>
      <div class="wg__nr-pr">
        <span class="wg__text">Utilizatori</span>
        <span class="wg__quantity">{{$users}}</span>
      </div>
    </div>
  </div>

  
</div>
