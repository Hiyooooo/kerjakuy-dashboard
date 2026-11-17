const holidayImport = document.querySelector('[data-holiday-import]');
const checkoutEnd = document.getElementById('check_out_end');
const checkoutEndDay = document.getElementById('check_out_end_day');

if (checkoutEnd && checkoutEndDay) {
    const syncCheckoutEndDay = () => {
        checkoutEndDay.disabled = checkoutEnd.value === '';
    };

    syncCheckoutEndDay();
    checkoutEnd.addEventListener('input', syncCheckoutEndDay);
    checkoutEnd.addEventListener('change', syncCheckoutEndDay);
    window.addEventListener('pageshow', syncCheckoutEndDay);
}

if (holidayImport) {
    const button = holidayImport.querySelector('button[type="submit"]');

    holidayImport.addEventListener('submit', () => {
        button.disabled = true;
        button.textContent = 'Mengimpor…';
    });

    window.addEventListener('pageshow', () => {
        button.disabled = false;
        button.textContent = 'Impor libur resmi';
    });
}
