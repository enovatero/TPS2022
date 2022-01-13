@if (Auth::user()->hasRole('MANAGER') || Auth::user()->hasRole('admin'))
<div class="pf--container">
  <div class="nw__topside">
    <span class="nw__title">Statistici profit lunar </span>
  </div>
  <span class="nw__bar"></span>
   <!-- <div class="pf__about">
      <div class="pf__container">
         <span class="pf__green"></span>
         <span class="pf__title">Profit Lunar </span>
      </div>
      <div class="pf__container">
         <span class="pf__yellow"></span>
         <span class="pf__title">Numar comenzi </span>
      </div>
   </div> -->
   <div  id="chart"></div>
</div>
@endif

