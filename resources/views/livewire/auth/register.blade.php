<?php

use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

new #[Layout('components.layouts.auth')] #[Title('Register')] class extends Component {
    public string $name = '';
    public string $email = '';
    public string $password = '';
    public string $password_confirmation = '';
    public string $role = 'venue';

    public function register(): void
    {
        $this->validate([
            'name' => 'required|min:2',
            'email' => 'required|email|unique:users',
            'password' => 'required|min:8|confirmed',
            'role' => 'required|in:brand,venue,producer',
        ]);

        $user = User::create([
            'name' => $this->name,
            'email' => $this->email,
            'password' => bcrypt($this->password),
            'email_verified_at' => now(),
        ]);

        $user->assignRole($this->role);

        Auth::login($user);
        $this->redirect(route('dashboard'), navigate: true);
    }
}; ?>

<div class="flex flex-col gap-6">
    <div class="text-center">
        <h1 class="text-2xl font-bold text-white">Create account</h1>
        <p class="text-zinc-400 mt-1">Join the Drinks Network</p>
    </div>

    <form wire:submit="register" class="flex flex-col gap-4">
        <flux:input
            wire:model="name"
            label="Name"
            type="text"
            placeholder="Your name"
        />

        <flux:input
            wire:model="email"
            label="Email"
            type="email"
            placeholder="you@example.com"
        />

        <flux:select wire:model="role" label="I am a...">
            <flux:select.option value="venue">Venue (Bar/Restaurant)</flux:select.option>
            <flux:select.option value="brand">Brand Owner</flux:select.option>
            <flux:select.option value="producer">Sales Rep / Producer</flux:select.option>
        </flux:select>

        <flux:input
            wire:model="password"
            label="Password"
            type="password"
            placeholder="Minimum 8 characters"
            viewable
        />

        <flux:input
            wire:model="password_confirmation"
            label="Confirm Password"
            type="password"
            placeholder="Confirm password"
            viewable
        />

        <flux:button type="submit" variant="primary" class="w-full">
            Create account
        </flux:button>
    </form>

    <p class="text-center text-sm text-zinc-400">
        Already have an account?
        <a href="{{ route('login') }}" class="text-emerald-400 hover:underline" wire:navigate>Sign in</a>
    </p>
</div>
