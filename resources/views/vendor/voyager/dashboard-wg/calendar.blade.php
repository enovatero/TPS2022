<div class="cl--container">
  <div class="nw__topside">
    <span class="nw__title">Calendar </span>
   
  </div>
  <span class="nw__bar"></span>
  <div id='calendar1'>
  <v-calendar
  is-expanded
  :attributes='attributes'
  @update:from-page="onMonthClick"
  />
    </div>
  
</div>