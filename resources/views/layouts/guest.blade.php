<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'DataVault System') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

        <!-- Font Awesome 6 Icons -->
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

        <!-- Bootstrap 5 CSS -->
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

        <!-- Favicon -->
        <link rel="icon" type="image/jpeg" href="{{ asset('estilos/imgPropio/datavault-logo.jpg') }}">

        <style>
            * {
                font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, sans-serif;
            }
            body {
                background-color: #0b1120;
                background-image: 
                    radial-gradient(at 15% 15%, rgba(14, 165, 233, 0.18) 0px, transparent 50%),
                    radial-gradient(at 85% 85%, rgba(79, 70, 229, 0.18) 0px, transparent 50%),
                    radial-gradient(at 50% 50%, rgba(15, 23, 42, 0.8) 0px, transparent 100%);
                background-attachment: fixed;
                min-height: 100vh;
            }
            .dv-guest-card {
                background: #ffffff;
                border-radius: 24px;
                box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
                border: 1px solid rgba(255, 255, 255, 0.1);
            }
        </style>
    </head>
    <body class="text-gray-900 antialiased d-flex flex-column justify-content-center align-items-center py-5 px-3">
        <div class="text-center mb-4">
            <a href="/" class="d-inline-flex flex-column align-items-center text-decoration-none">
                <img src="{{ asset('estilos/imgPropio/datavault-logo.jpg') }}" alt="DataVault System" class="rounded-circle shadow-lg mb-2" style="width: 90px; height: 90px; object-fit: cover; border: 3px solid rgba(14, 165, 233, 0.5);">
                <h3 class="fw-bold text-white mb-0" style="letter-spacing: -0.02em;">DATAVAULT <span style="color: #38bdf8;">SYSTEM</span></h3>
            </a>
        </div>

        <div class="w-100 dv-guest-card p-4 p-md-5" style="max-width: 480px;">
            {{ $slot }}
        </div>
        
        <div class="text-center mt-4 text-white-50 small">
            &copy; {{ date('Y') }} DataVault System • Todos los derechos reservados.
        </div>
    </body>
</html>
