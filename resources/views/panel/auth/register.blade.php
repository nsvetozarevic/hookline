@use(App\Routing\WebRoute)

<div>
    <h1 class="hl-page-title">Register</h1>

    <form method="POST" action="{{ route(WebRoute::Register) }}" class="mt-6 space-y-4">
        @csrf

        <div>
            <label for="name" class="hl-label">Name</label>
            <input
                id="name"
                name="name"
                type="text"
                value="{{ old('name') }}"
                required
                autofocus
                autocomplete="name"
                class="hl-input"
            >
            @error('name')
                <p class="hl-error">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="email" class="hl-label">Email</label>
            <input
                id="email"
                name="email"
                type="email"
                value="{{ old('email') }}"
                required
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
                autocomplete="new-password"
                class="hl-input"
            >
            @error('password')
                <p class="hl-error">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="password_confirmation" class="hl-label">Confirm password</label>
            <input
                id="password_confirmation"
                name="password_confirmation"
                type="password"
                required
                autocomplete="new-password"
                class="hl-input"
            >
        </div>

        <button type="submit" class="hl-btn-primary w-full">
            Register
        </button>
    </form>

    <p class="mt-6 text-center text-sm text-slate-500">
        Already have an account?
        <a href="{{ route(WebRoute::ShowLogin) }}" class="hl-btn-link">Log in</a>
    </p>
</div>
