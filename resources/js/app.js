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

const initBulkSelectors = () => {
    document.querySelectorAll('[data-select-all]').forEach((checkbox) => {
        if (checkbox.dataset.selectReady === '1') {
            return;
        }

        checkbox.dataset.selectReady = '1';
        const target = checkbox.dataset.selectAll;

        checkbox.addEventListener('change', () => {
            document.querySelectorAll(target).forEach((input) => {
                input.checked = checkbox.checked;
            });
        });
    });
};

const initSmashr = () => {
    initPhoneInputs();
    initBulkSelectors();
};

document.addEventListener('DOMContentLoaded', initSmashr);
document.addEventListener('livewire:navigated', initSmashr);
