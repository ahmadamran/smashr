<form {{ $attributes->merge(['class' => 'mb-6 flex flex-col gap-3 rounded-lg bg-white p-4 shadow sm:flex-row sm:items-center']) }}>
    {{ $slot }}
    <button class="rounded-md bg-[#071a80] px-4 py-2 text-xs font-black uppercase text-white">Apply</button>
    <a href="{{ url()->current() }}" class="rounded-md border border-blue-950/10 px-4 py-2 text-center text-xs font-black uppercase text-[#071a80]">Reset</a>
</form>
