@props([
    'name' => 'phone_number',
    'countryName' => 'country',
    'value' => '',
    'country' => 'Malaysia',
])

@php
    $countryIso = strtolower($country) === 'malaysia' || strtolower($country) === 'my' ? 'my' : '';
@endphp

<div class="font-bold text-brand-blue">
    <label for="{{ $name }}">Phone number</label>
    <input
        id="{{ $name }}"
        name="{{ $name }}"
        type="tel"
        value="{{ old($name, $value) }}"
        data-phone-input
        data-phone-country="{{ $countryIso ?: 'my' }}"
        class="mt-1 w-full rounded-md border-brand-ink/10"
        autocomplete="tel"
        placeholder="+60 12-345 6789"
    >
    <input type="hidden" name="{{ $countryName }}" value="{{ old($countryName, $country ?: 'Malaysia') }}" data-phone-country-name>
</div>
