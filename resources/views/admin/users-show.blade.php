<x-app-layout>
    <x-slot name="header"><h1 class="text-3xl font-black text-brand-blue">{{ $user->name }}</h1></x-slot>
    <div class="mx-auto max-w-5xl px-4 py-10 sm:px-6 lg:px-8">
        @include('admin.partials.nav')
        <div class="grid gap-6 lg:grid-cols-3">
            <section class="rounded-lg bg-white p-6 shadow-lg lg:col-span-2">
                <p class="text-xs font-black uppercase tracking-[.2em] text-brand-green">User detail</p>
                <h2 class="mt-2 text-2xl font-black text-brand-blue">{{ $user->email }}</h2>
                <dl class="mt-5 grid gap-4 sm:grid-cols-2">
                    <div><dt class="text-xs font-black uppercase text-brand-ink/50">Club</dt><dd class="font-bold">{{ $user->clubs->pluck('name')->join(', ') ?: 'Independent' }}</dd></div>
                    <div><dt class="text-xs font-black uppercase text-brand-ink/50">Phone</dt><dd class="font-bold">{{ $user->playerProfile?->phone_number ?: 'Not set' }}</dd></div>
                    <div><dt class="text-xs font-black uppercase text-brand-ink/50">Role</dt><dd class="font-bold">{{ $user->roles->pluck('name')->join(', ') ?: 'Player' }}</dd></div>
                    <div><dt class="text-xs font-black uppercase text-brand-ink/50">Status</dt><dd><x-admin.status-badge :status="$user->suspended_at ? 'suspended' : 'active'" /></dd></div>
                    <div><dt class="text-xs font-black uppercase text-brand-ink/50">Rating</dt><dd class="font-bold">{{ $user->playerProfile?->singles_rating ?? '3.500' }} / {{ $user->playerProfile?->doubles_rating ?? '3.500' }}</dd></div>
                </dl>
                <div class="mt-6 flex flex-wrap gap-3">
                    <a href="{{ route('admin.users.edit', $user) }}" class="rounded-md bg-brand-blue px-4 py-2 text-xs font-black uppercase text-white">Edit</a>
                    <a href="{{ route('admin.users') }}" class="rounded-md border border-brand-ink/10 px-4 py-2 text-xs font-black uppercase text-brand-blue">Back</a>
                </div>
            </section>
            <section id="points" class="rounded-lg bg-white p-6 shadow-lg">
                <p class="text-xs font-black uppercase tracking-[.2em] text-brand-green">SMASHR points</p>
                <p class="mt-2 text-5xl font-black text-brand-blue">{{ $user->playerProfile?->smashr_points ?? 0 }}</p>
                <p class="mt-1 text-xs font-bold uppercase text-brand-ink/50">Before value is the current number above; the saved history records before and after values.</p>
                <form method="POST" action="{{ route('admin.users.points', $user) }}" class="mt-5 grid gap-3">
                    @csrf
                    <select name="mode" class="rounded-md border-brand-ink/10"><option value="set">Set points</option><option value="add">Add points</option><option value="deduct">Deduct points</option></select>
                    <input name="points" type="number" min="0" placeholder="Points" class="rounded-md border-brand-ink/10">
                    <input name="reason" placeholder="Reason" class="rounded-md border-brand-ink/10">
                    <button class="rounded-md bg-brand-blue px-4 py-2 text-xs font-black uppercase text-white">Save adjustment</button>
                </form>
            </section>
        </div>
        <section class="mt-6 rounded-lg bg-white p-6 shadow-lg">
            <h2 class="text-xl font-black text-brand-blue">Point adjustment history</h2>
            <div class="mt-4 grid gap-2">
                @forelse ($user->smashrPointAdjustments as $adjustment)
                    <p class="rounded-md bg-brand-surface p-3 text-sm font-bold">{{ $adjustment->before_points }} to {{ $adjustment->after_points }} ({{ $adjustment->adjustment }}) | {{ $adjustment->reason }}</p>
                @empty
                    <p class="text-sm font-bold text-brand-ink/50">No point adjustments yet.</p>
                @endforelse
            </div>
        </section>
    </div>
</x-app-layout>
