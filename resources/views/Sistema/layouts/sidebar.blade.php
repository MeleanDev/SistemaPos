<aside class="left-sidebar" data-sidebarbg="skin6">
    <div class="scroll-sidebar" data-sidebarbg="skin6">
        <nav class="sidebar-nav">
            <ul id="sidebarnav">

                <!-- PANEL PRINCIPAL -->
                <li class="sidebar-item @if (request()->routeIs('dashboard')) selected @endif">
                    <a class="sidebar-link" href="{{ route('dashboard') }}" aria-expanded="false">
                        <i class="fas fa-chart-pie"></i>
                        <span class="hide-menu">Panel Principal</span>
                    </a>
                </li>

                <!-- VENTAS Y CAJA -->
                <li class="list-divider"></li>
                <li class="nav-small-cap"><span class="hide-menu">Ventas & Facturación</span></li>

                <li class="sidebar-item @if (request()->routeIs('pos*')) selected @endif">
                    <a class="sidebar-link" href="{{ route('pos') }}" aria-expanded="false">
                        <i class="fas fa-cash-register"></i>
                        <span class="hide-menu">Punto de Venta (POS)</span>
                    </a>
                </li>

                <li class="sidebar-item">
                    <a class="sidebar-link" href="javascript:void(0)" aria-expanded="false">
                        <i class="fas fa-history"></i>
                        <span class="hide-menu">Historial Facturas</span>
                    </a>
                </li>

                <li class="sidebar-item">
                    <a class="sidebar-link" href="javascript:void(0)" aria-expanded="false">
                        <i class="fas fa-wallet"></i>
                        <span class="hide-menu">Caja y Arqueos</span>
                    </a>
                </li>

                <li class="sidebar-item @if (request()->routeIs('metodo_pago*')) selected @endif">
                    <a class="sidebar-link" href="{{ route('metodo_pago') }}" aria-expanded="false">
                        <i class="fas fa-credit-card"></i>
                        <span class="hide-menu">Métodos de Pago</span>
                    </a>
                </li>

                <!-- CRÉDITOS Y FINANZAS -->
                <li class="list-divider"></li>
                <li class="nav-small-cap"><span class="hide-menu">Créditos & Finanzas</span></li>

                <li class="sidebar-item @if (request()->routeIs('cxc*')) selected @endif">
                    <a class="sidebar-link" href="{{ route('cxc') }}" aria-expanded="false">
                        <i class="fas fa-hand-holding-usd"></i>
                        <span class="hide-menu">Cuentas por Cobrar (CXC)</span>
                    </a>
                </li>

                <li class="sidebar-item @if (request()->routeIs('cxp*')) selected @endif">
                    <a class="sidebar-link" href="{{ route('cxp') }}" aria-expanded="false">
                        <i class="fas fa-file-invoice-dollar"></i>
                        <span class="hide-menu">Cuentas por Pagar (CXP)</span>
                    </a>
                </li>


                <!-- COMPRAS Y RECEPCIÓN -->
                <li class="list-divider"></li>
                <li class="nav-small-cap"><span class="hide-menu">Compras & Recepción</span></li>

                <li class="sidebar-item @if (request()->routeIs('recepcion.*') || request()->routeIs('recepcion')) selected @endif">
                    <a class="sidebar-link" href="{{ route('recepcion') }}" aria-expanded="false">
                        <i class="fas fa-truck-loading"></i>
                        <span class="hide-menu">Recepción Mercancía</span>
                    </a>
                </li>

                <li class="sidebar-item @if (request()->routeIs('recepcion_moto*')) selected @endif">
                    <a class="sidebar-link" href="{{ route('recepcion_moto') }}" aria-expanded="false">
                        <i class="fas fa-truck-ramp-box"></i>
                        <span class="hide-menu">Recepción de Motos</span>
                    </a>
                </li>

                <li class="sidebar-item @if (request()->routeIs('proveedor*')) selected @endif">
                    <a class="sidebar-link" href="{{ route('proveedor') }}" aria-expanded="false">
                        <i class="fas fa-truck-moving"></i>
                        <span class="hide-menu">Proveedores</span>
                    </a>
                </li>

                <!-- INVENTARIO Y CATÁLOGO -->
                <li class="list-divider"></li>
                <li class="nav-small-cap"><span class="hide-menu">Inventario & Catálogo</span></li>

                <li class="sidebar-item @if (request()->routeIs('categoria*') || request()->routeIs('producto*') || request()->routeIs('servicio*') || request()->routeIs('almacen*') || request()->routeIs('moto*')) selected @endif">
                    <a class="sidebar-link has-arrow @if (request()->routeIs('categoria*') || request()->routeIs('producto*') || request()->routeIs('servicio*') || request()->routeIs('almacen*') || request()->routeIs('moto*')) active @endif" href="javascript:void(0)" aria-expanded="@if (request()->routeIs('categoria*') || request()->routeIs('producto*') || request()->routeIs('servicio*') || request()->routeIs('almacen*') || request()->routeIs('moto*')) true @else false @endif">
                        <i class="fas fa-boxes-stacked"></i>
                        <span class="hide-menu">Inventario & Catálogo</span>
                    </a>
                    <ul aria-expanded="@if (request()->routeIs('categoria*') || request()->routeIs('producto*') || request()->routeIs('servicio*') || request()->routeIs('almacen*') || request()->routeIs('moto*')) true @else false @endif" class="collapse first-level base-level-line @if (request()->routeIs('categoria*') || request()->routeIs('producto*') || request()->routeIs('servicio*') || request()->routeIs('almacen*') || request()->routeIs('moto*')) in @endif">
                        <li class="sidebar-item @if (request()->routeIs('categoria*')) active @endif">
                            <a href="{{ route('categoria') }}" class="sidebar-link @if (request()->routeIs('categoria*')) active @endif">
                                <i class="fas fa-tags me-2"></i>
                                <span class="hide-menu">Categorías</span>
                            </a>
                        </li>
                        <li class="sidebar-item @if (request()->routeIs('producto*')) active @endif">
                            <a href="{{ route('producto') }}" class="sidebar-link @if (request()->routeIs('producto*')) active @endif">
                                <i class="fas fa-boxes-stacked me-2"></i>
                                <span class="hide-menu">Productos</span>
                            </a>
                        </li>
                        <li class="sidebar-item @if (request()->routeIs('moto*')) active @endif">
                            <a href="{{ route('moto') }}" class="sidebar-link @if (request()->routeIs('moto*')) active @endif">
                                <i class="fas fa-motorcycle me-2"></i>
                                <span class="hide-menu">Motos & Seriales</span>
                            </a>
                        </li>
                        <li class="sidebar-item @if (request()->routeIs('servicio*')) active @endif">
                            <a href="{{ route('servicio') }}" class="sidebar-link @if (request()->routeIs('servicio*')) active @endif">
                                <i class="fas fa-wrench me-2"></i>
                                <span class="hide-menu">Servicios</span>
                            </a>
                        </li>
                        <li class="sidebar-item @if (request()->routeIs('almacen*')) active @endif">
                            <a href="{{ route('almacen') }}" class="sidebar-link @if (request()->routeIs('almacen*')) active @endif">
                                <i class="fas fa-warehouse me-2"></i>
                                <span class="hide-menu">Almacenes</span>
                            </a>
                        </li>
                        <li class="sidebar-item">
                            <a href="javascript:void(0)" class="sidebar-link">
                                <i class="fas fa-dolly me-2"></i>
                                <span class="hide-menu">Movimiento Kardex</span>
                            </a>
                        </li>
                    </ul>
                </li>

                <!-- CLIENTES -->
                <li class="list-divider"></li>
                <li class="nav-small-cap"><span class="hide-menu">Clientes</span></li>

                <li class="sidebar-item @if (request()->routeIs('cliente*')) selected @endif">
                    <a class="sidebar-link" href="{{ route('cliente') }}" aria-expanded="false">
                        <i class="fas fa-users"></i>
                        <span class="hide-menu">Clientes</span>
                    </a>
                </li>

                <!-- REPORTES -->
                <li class="list-divider"></li>
                <li class="nav-small-cap"><span class="hide-menu">Informes</span></li>

                <li class="sidebar-item">
                    <a class="sidebar-link" href="javascript:void(0)" aria-expanded="false">
                        <i class="fas fa-chart-line"></i>
                        <span class="hide-menu">Reportes</span>
                    </a>
                </li>

                <!-- ADMINISTRACIÓN Y CONFIGURACIÓN -->
                <li class="list-divider"></li>
                <li class="nav-small-cap"><span class="hide-menu">Sistema & Accesos</span></li>

                <li class="sidebar-item @if (request()->routeIs('empresa*')) selected @endif">
                    <a class="sidebar-link" href="{{ route('empresa') }}" aria-expanded="false">
                        <i class="fas fa-building"></i>
                        <span class="hide-menu">Empresas / Sedes</span>
                    </a>
                </li>

                <li class="sidebar-item @if (request()->routeIs('usuario*')) selected @endif">
                    <a class="sidebar-link" href="{{ route('usuario') }}" aria-expanded="false">
                        <i class="fas fa-users-cog"></i>
                        <span class="hide-menu">Usuarios & Roles</span>
                    </a>
                </li>

                <li class="sidebar-item @if (request()->routeIs('configuracion*')) selected @endif">
                    <a class="sidebar-link" href="{{ route('configuracion') }}" aria-expanded="false">
                        <i class="fas fa-sliders-h"></i>
                        <span class="hide-menu">Configuración</span>
                    </a>
                </li>

            </ul>
        </nav>
    </div>
</aside>
