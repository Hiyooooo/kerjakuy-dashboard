const holidayImport = document.querySelector('[data-holiday-import]');

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
