import intlTelInput from 'intl-tel-input';
import 'intl-tel-input/dist/css/intlTelInput.css';

const initPhoneInputs = () => {
    document.querySelectorAll('[data-phone-input]').forEach((input) => {
        if (input.dataset.phoneReady === '1') {
            return;
        }

        input.dataset.phoneReady = '1';

        const iti = intlTelInput(input, {
            initialCountry: input.dataset.phoneCountry || 'my',
            nationalMode: false,
            separateDialCode: true,
        });

        const form = input.closest('form');
        const countryInput = form?.querySelector('[data-phone-country-name]');

        const syncPhoneFields = () => {
            const selected = iti.getSelectedCountryData();
            const fullNumber = iti.getNumber();

            if (fullNumber) {
                input.value = fullNumber;
            }

            if (countryInput && selected?.name) {
                countryInput.value = selected.name.replace(/\s*\(.+\)/, '');
            }
        };

        input.addEventListener('countrychange', syncPhoneFields);
        form?.addEventListener('submit', syncPhoneFields);
    });
};

document.addEventListener('DOMContentLoaded', initPhoneInputs);
document.addEventListener('livewire:navigated', initPhoneInputs);
