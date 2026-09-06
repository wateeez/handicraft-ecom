@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-16">
    <div class="flex justify-center">
        <div class="w-full max-w-md">
            <div class="bg-card shadow-xl rounded-2xl border border-border overflow-hidden">
                <div class="px-8 py-6 bg-paper/60 border-b border-border text-center">
                    <div class="w-10 h-10 rounded-xl bg-primary/10 border border-primary/20 flex items-center justify-center font-serif font-bold text-primary text-xl mx-auto mb-2">
                        {{ strtoupper(substr($siteSettings['site_name'] ?? 'H', 0, 1)) }}
                    </div>
                    <h1 class="text-xl font-serif font-bold text-foreground">{{ __('Artisan Portal Login') }}</h1>
                    <p class="text-xs text-muted-foreground mt-1">Sign in to manage your inventory and orders</p>
                </div>

                <div class="px-8 py-8">
                    <form method="POST" action="{{ route('login') }}">
                        @csrf

                        <div class="mb-5">
                            <label for="email" class="block text-xs font-semibold uppercase tracking-wider text-muted-foreground mb-1.5">{{ __('Email Address') }}</label>
                            <input id="email" type="email" class="w-full px-4 py-3 bg-paper/50 border border-border rounded-xl text-foreground placeholder:text-muted-foreground/60 focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary transition @error('email') border-red-500 @enderror" name="email" value="{{ old('email') }}" required autocomplete="email" autofocus placeholder="admin@example.com">
                            @error('email')
                                <p class="text-red-500 text-xs mt-1.5">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="mb-5">
                            <label for="password" class="block text-xs font-semibold uppercase tracking-wider text-muted-foreground mb-1.5">{{ __('Password') }}</label>
                            <input id="password" type="password" class="w-full px-4 py-3 bg-paper/50 border border-border rounded-xl text-foreground placeholder:text-muted-foreground/60 focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary transition @error('password') border-red-500 @enderror" name="password" required autocomplete="current-password" placeholder="••••••••">
                            @error('password')
                                <p class="text-red-500 text-xs mt-1.5">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="mb-6 flex items-center justify-between">
                            <label class="flex items-center cursor-pointer" for="remember">
                                <input class="h-4 w-4 text-primary focus:ring-primary border-border rounded" type="checkbox" name="remember" id="remember" {{ old('remember') ? 'checked' : '' }}>
                                <span class="ml-2 text-xs font-medium text-muted-foreground">
                                    {{ __('Remember Me') }}
                                </span>
                            </label>
                            @if (Route::has('password.request'))
                                <a class="text-xs font-semibold text-primary hover:underline" href="{{ route('password.request') }}">
                                    {{ __('Forgot password?') }}
                                </a>
                            @endif
                        </div>

                        <div>
                            <button type="submit" class="w-full bg-primary hover:bg-primary/90 text-primary-foreground font-semibold py-3.5 px-4 rounded-xl shadow-md hover:shadow-lg transition-all duration-200">
                                {{ __('Sign In to Admin') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
