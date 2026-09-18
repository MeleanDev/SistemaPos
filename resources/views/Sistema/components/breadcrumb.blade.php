<div class="page-breadcrumb">
    <div class="page-breadcrumb-executive">
        <div class="row align-items-center g-3">
            <!-- Título, Subtítulo y Migajas de Pan -->
            <div class="col-12 col-md">
                @hasSection('rutas')
                    <nav class="breadcrumb-executive mb-2" aria-label="breadcrumb">
                        <a href="{{ route('dashboard') }}"><i class="fas fa-home me-1"></i>Inicio</a>
                        <span class="breadcrumb-separator"><i class="fas fa-chevron-right"></i></span>
                        @yield('rutas')
                    </nav>
                @endif

                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <h2 class="page-title-executive mb-0">
                        @yield('titulo', 'Panel Principal')
                    </h2>

                    @hasSection('badge')
                        @yield('badge')
                    @endif
                </div>

                @hasSection('subtitulo')
                    <p class="page-subtitle-executive mb-0 text-muted">
                        @yield('subtitulo')
                    </p>
                @endif
            </div>

            <!-- Botones de Acción / Toolbar Superior -->
            @hasSection('acciones')
                <div class="col-12 col-md-auto">
                    <div class="header-action-container d-flex align-items-center gap-2 flex-wrap">
                        @yield('acciones')
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>


