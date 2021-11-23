<footer class="app-footer">
    <div class="site-footer-right">
        @if (rand(1,100) == 100)
            <i class="voyager-rum-1"></i> {{ __('voyager::theme.footer_copyright2') }}
        @else
            {!! __('voyager::theme.footer_copyright') !!} 
            @if (env('APP_ENV') != 'production')
              <a href="http://thecontrolgroup.com" target="_blank">The Control Group</a>
            @else
              <a href="https://www.touch-media.ro/" target="_blank">TouchMedia</a>
            @endif
        @endif
        @php $version = Voyager::getVersion(); @endphp
        @if (!empty($version))
            - {{ $version }}
        @endif
    </div>
</footer>
