<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>LIMS · {{ config('lims.plant.code', 'ГМЗ-5') }}</title>

        {{-- Брендинг завода — доступен во фронтенде сразу (в т.ч. на странице входа) --}}
        <script>window.__LIMS__ = @json(config('lims.plant'));</script>

        <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700&display=swap" rel="stylesheet">

      
                <link
        href="https://fonts.googleapis.com/css2?family=Source+Sans+Pro:ital,wght@0,400;1,700&display=swap"
        rel="stylesheet"
        />
        <link rel="stylesheet" href="{{ asset('awesome/css/all.min.css') }}">
        @vite(['resources/css/app.css','resources/js/app.js'])

    </head>
    <body id="app" class="h-screen">
    </body>
</html>
