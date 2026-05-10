@props(['action', 'method' => 'DELETE', 'label' => 'Delete', 'message' => 'Are you sure?'])

<form method="POST" action="{{ $action }}" onsubmit="return confirm(@js($message))">
    @csrf
    @method($method)
    <button class="w-full rounded px-3 py-2 text-left text-red-700 hover:bg-red-50">{{ $label }}</button>
</form>
