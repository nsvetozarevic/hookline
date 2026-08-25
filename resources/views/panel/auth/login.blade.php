@use(App\Routing\WebRoute)

<div>
    <h1 class="text-xl text-zinc-50">Log in</h1>

    <form method="POST" action="{{ route(WebRoute::Login) }}" class="mt-8 space-y-4">
        @csrf

        <div>
            <label for="email" class="block text-zinc-400">Email</label>
            <input
                id="email"
                name="email"
                type="email"
                value="{{ old('email') }}"
                required
                autofocus
                autocomplete="username"
                class="mt-1 w-full border border-zinc-700 bg-zinc-900 px-3 py-2 text-zinc-50"
            >
            @error('email')
                <p class="mt-1 text-red-400">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="password" class="block text-zinc-400">Password</label>
            <input
                id="password"
                name="password"
                type="password"
                required
                autocomplete="current-password"
                class="mt-1 w-full border border-zinc-700 bg-zinc-900 px-3 py-2 text-zinc-50"
            >
            @error('password')
                <p class="mt-1 text-red-400">{{ $message }}</p>
            @enderror
        </div>

        <button type="submit" class="border border-zinc-500 px-4 py-2 text-zinc-50 hover:bg-zinc-800">
            Log in
        </button>
    </form>

    <p class="mt-6 text-zinc-400">
        <a href="{{ route(WebRoute::ShowRegister) }}" class="text-zinc-50 underline">Register</a>
    </p>
</div>
