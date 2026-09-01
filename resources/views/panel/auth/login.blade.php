@use(App\Routing\WebRoute)

<div>
    <h1 class="hl-page-title">Log in</h1>

    <form method="POST" action="{{ route(WebRoute::Login) }}" class="mt-6 space-y-4">
        @csrf

        <div>
            <label for="email" class="hl-label">Email</label>
            <input
                id="email"
                name="email"
                type="email"
                value="{{ old('email') }}"
                required
                autofocus
                autocomplete="username"
                class="hl-input"
            >
            @error('email')
                <p class="hl-error">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="password" class="hl-label">Password</label>
            <input
                id="password"
                name="password"
                type="password"
                required
                autocomplete="current-password"
                class="hl-input"
            >
            @error('password')
                <p class="hl-error">{{ $message }}</p>
            @enderror
        </div>

        <button type="submit" class="hl-btn-primary w-full">
            Log in
        </button>
    </form>

    <p class="mt-6 text-center text-sm text-slate-500">
        No account?
        <a href="{{ route(WebRoute::ShowRegister) }}" class="hl-btn-link">Register</a>
    </p>
</div>
