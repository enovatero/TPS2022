<ol class="dd-list">          
   @foreach ($items as $item)
      <li class="dd-item" data-id="{{ $item->id }}">
          <div class="dd-handle" style="height:inherit">
             <span>{{ $item->{$display_column} }}</span>
          </div>
          @if(!$item->children->isEmpty())
              @include('voyager::offer-types.suborder', ['items' => $item->children])
          @endif
      </li>
    @endforeach
</ol>