<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#fff8f3">
    <title>Masuk | KerjaKuy</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="grid min-h-screen place-items-center bg-paper px-[18px] py-8 font-sans text-ink">
    <main class="w-full max-w-[400px] rounded-[22px] border border-line bg-white p-6 sm:p-8">
        <div class="mb-6 flex items-center gap-3 font-heading text-xl font-extrabold tracking-tight">
            <span aria-hidden="true" class="grid size-9 place-items-center rounded-[12px] rounded-bl-[4px] bg-coral text-lg">K</span>
            KerjaKuy
        </div>
        <h1 class="font-heading text-[32px] leading-tight font-extrabold tracking-tight">Masuk ke admin panel</h1>

        <form action="{{ route('login.store') }}" method="POST" class="mt-6 grid gap-4">
            @csrf

            <div>
                <label for="email" class="block text-sm font-bold">Email</label>
                <input id="email" name="email" type="email" autocomplete="username" value="{{ old('email') }}" required
                    class="mt-1 min-h-12 w-full rounded-[10px] border border-control px-3"
                    @error('email') aria-invalid="true" aria-describedby="email-error" @enderror>
                @error('email')
                    <p id="email-error" class="mt-1 text-sm text-[#b42318]">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="password" class="block text-sm font-bold">Kata sandi</label>
                <input id="password" name="password" type="password" autocomplete="current-password" required
                    class="mt-1 min-h-12 w-full rounded-[10px] border border-control px-3"
                    @error('password') aria-invalid="true" aria-describedby="password-error" @enderror>
                @error('password')
                    <p id="password-error" class="mt-1 text-sm text-[#b42318]">{{ $message }}</p>
                @enderror
            </div>

            <label class="flex min-h-11 w-fit cursor-pointer items-center gap-2 text-sm font-semibold">
                <input name="remember" type="checkbox" value="1" @checked(old('remember')) class="size-4 accent-control">
                Ingat saya
            </label>

            <button type="submit" class="min-h-12 cursor-pointer rounded-[10px] bg-coral px-4 font-bold hover:bg-coral/85 motion-safe:transition-colors">
                Masuk
            </button>
        </form>
    </main>
</body>
</html>
