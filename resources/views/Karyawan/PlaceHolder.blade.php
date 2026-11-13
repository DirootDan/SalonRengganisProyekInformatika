<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $judul }}</title>
    @vite(['resources/css/app.css'])
</head>
<body>
    <h1>{{ $judul }}</h1>
    <p>Halo, {{ $user->nama_staff }} ({{ $user->role_jabatan }})</p>

    <form method="POST" action="{{ route('logout') }}">
        @csrf
        <button type="submit">Logout</button>
    </form>
</body>
</html>