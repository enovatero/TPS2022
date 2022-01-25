<div class="side-menu sidebar-inverse  ">
    
    <nav class="navbar navbar-default" role="navigation">
        
        <div class="side-menu-container">
            <div class="navbar-header">
                <a class="navbar-brand" href="{{ route('voyager.dashboard') }}">
                    <div class="logo-icon-container">
                        <!-- <?php $admin_logo_img = Voyager::setting('admin.icon_image', ''); ?>
                        @if($admin_logo_img == '')
                            <img src="{{ voyager_asset('images/logo-icon-light.png') }}" alt="Logo Icon">
                        @else
                            <img src="{{ Voyager::image($admin_logo_img) }}" alt="Logo Icon">
                        @endif -->
                        <span class="tps__logo">TPS</span>
                    </div>
                    <div class="title">{{Voyager::setting('admin.title', 'VOYAGER')}}</div>
                </a>
            </div>
            <!-- .navbar-header -->
        </div>
        
        <div id="adminmenu" class="admin-left">
            @php
                $user = Auth::user();
                $menuItems = menu('admin', '_json');
                $menuItems = $menuItems->toArray();
                foreach ($menuItems as $index => &$menu) {
                    foreach ($menu['children'] as $subIndex => $subMenu) {
                        $url = ltrim(rtrim($subMenu['url'], '/'), '/');
                        
                        // aici putem sterge linkuri din meniu daca nu avem permisiuni
                        if ($url == 'admin/products-complete' || $url == 'admin/products-incomplete') {
                            if (!$user->can('browse', app(\App\Product::class))) {
                                unset($menu['children'][$subIndex]);
                            }
                        }
                        
                        if ($url == 'admin/lista-oferte' || $url == 'admin/lista-comenzi-tigla' || $url == 'admin/lista-comenzi-sipca') {
                            if (!$user->can('browse', app(\App\Offer::class))) {
                                unset($menu['children'][$subIndex]);
                            }
                        }
                        
                    }
                    // sesetam indexurile/cheile
                    $menu['children'] = array_values($menu['children']);
                    
                    // daca un meniul ramane gol, fara children, il sterg si resetez indexurile
                    if (count($menu['children']) == 0) {
                        if (!$menu['route'] && !$menu['url']) {
                            unset($menuItems[$index]);
                        }
                    }
                }
                $menuItems = array_values($menuItems);
            @endphp
            <admin-menu :items="{{ collect($menuItems) }}"></admin-menu>
        </div>
        
    </nav>
    
    <div class="panel widget center bgimage profile__image--fixed" style="background-color: #292F4C; position: fixed; bottom: 1rem ">
        <div class="panel-content">
            <img src="{{ $user_avatar }}" class="avatar" alt="{{ Auth::user()->name }} avatar">
            <h4>{{ ucwords(Auth::user()->name) }}</h4>
            <p>{{ Auth::user()->email }}</p>
            <a href="{{ route('voyager.profile') }}" class="btn btn-primary">{{ __('voyager::generic.profile') }}</a>
            <div style="clear:both"></div>
        </div>
    </div>
    
</div>