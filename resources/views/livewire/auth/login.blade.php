<?php

use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Illuminate\Support\Facades\Auth;

new #[Layout('components.layouts.auth')] #[Title('Login')] class extends Component {
    public string $email = 'admin@repshare.test';
    public string $password = 'password';
    public bool $remember = false;

    public function login(): void
    {
        $this->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if (Auth::attempt(['email' => $this->email, 'password' => $this->password], $this->remember)) {
            session()->regenerate();
            $this->redirect(route('dashboard'), navigate: true);
        } else {
            $this->addError('email', 'Invalid credentials.');
        }
    }
}; ?>

<div class="flex flex-col gap-6">
    <div class="text-center">
        <h1 class="text-2xl font-bold text-white">Welcome back</h1>
        <p class="text-zinc-400 mt-1">Sign in to your RepShare account</p>
    </div>

    <form wire:submit="login" class="flex flex-col gap-4">
        <flux:input
            wire:model="email"
            label="Email"
            type="email"
            placeholder="you@example.com"
        />

        <flux:input
            wire:model="password"
            label="Password"
            type="password"
            placeholder="Password"
            viewable
        />

        <div class="flex items-center justify-between">
            <flux:checkbox wire:model="remember" label="Remember me" />
        </div>

        <flux:button type="submit" variant="primary" class="w-full">
            Sign in
        </flux:button>
    </form>

    <div class="text-center text-sm text-zinc-400">
        <p>Demo accounts (password: <code class="text-emerald-400">password</code>)</p>
        <div class="mt-2 grid grid-cols-2 gap-2 text-xs">
            <div class="bg-zinc-800 rounded p-2">
                <span class="text-emerald-400">Admin:</span><br>
                admin@repshare.test
            </div>
            <div class="bg-zinc-800 rounded p-2">
                <span class="text-emerald-400">Brand:</span><br>
                james@stonefish.com.au
            </div>
            <div class="bg-zinc-800 rounded p-2">
                <span class="text-emerald-400">Venue:</span><br>
                emma@thetavern.com.au
            </div>
            <div class="bg-zinc-800 rounded p-2">
                <span class="text-emerald-400">Producer:</span><br>
                tom@salesrep.com.au
            </div>
        </div>
    </div>
</div>
