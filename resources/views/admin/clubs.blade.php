<x-app-layout>
    <x-slot name="header"><h1 class="text-3xl font-black text-[#071a80]">Manage clubs</h1></x-slot>
    <div class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
        @include('admin.partials.nav')
        <form method="POST" action="{{ route('admin.clubs.store') }}" class="mb-6 grid gap-3 rounded-lg bg-white p-5 shadow md:grid-cols-5">@csrf
            <input name="name" placeholder="Club name" class="rounded-md border-gray-300">
            <input name="country" placeholder="Country" class="rounded-md border-gray-300">
            <input name="state" placeholder="State" class="rounded-md border-gray-300">
            <input name="city" placeholder="City" class="rounded-md border-gray-300">
            <button class="rounded-md bg-[#071a80] font-black uppercase text-white">Create</button>
        </form>
        <div class="grid gap-5">
            @foreach ($clubs as $club)
                <section class="rounded-lg bg-white p-5 shadow">
                    <form method="POST" action="{{ route('admin.clubs.update', $club) }}" class="grid gap-3 md:grid-cols-5">@csrf @method('PATCH')
                        <input name="name" value="{{ $club->name }}" class="rounded-md border-gray-300">
                        <input name="country" value="{{ $club->country }}" class="rounded-md border-gray-300">
                        <input name="state" value="{{ $club->state }}" class="rounded-md border-gray-300">
                        <input name="city" value="{{ $club->city }}" class="rounded-md border-gray-300">
                        <button class="rounded-md bg-[#071a80] font-bold text-white">Save</button>
                    </form>
                    <div class="mt-4 flex flex-wrap items-center gap-2">
                        <form method="POST" action="{{ route('admin.clubs.members.store', $club) }}" class="flex gap-2">@csrf
                            <input name="email" placeholder="member@email.com" class="rounded-md border-gray-300">
                            <button class="rounded-md border px-3 font-bold">Add member</button>
                        </form>
                        @foreach ($club->members as $member)
                            <form method="POST" action="{{ route('admin.clubs.members.destroy', [$club, $member]) }}">@csrf @method('DELETE')
                                <button class="rounded-full bg-[#f3f6fb] px-3 py-1 text-xs">{{ $member->email }} x</button>
                            </form>
                        @endforeach
                    </div>
                </section>
            @endforeach
        </div>
        <div class="mt-6">{{ $clubs->links() }}</div>
    </div>
</x-app-layout>
