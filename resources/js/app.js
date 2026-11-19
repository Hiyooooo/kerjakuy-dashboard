const holidayImport = document.querySelector('[data-holiday-import]');
const leaveDays = document.querySelector('[data-leave-days]');
const checkoutEnd = document.getElementById('check_out_end');
const checkoutEndDay = document.getElementById('check_out_end_day');

if (leaveDays) {
    const list = leaveDays.querySelector('[data-leave-day-list]');
    const template = leaveDays.querySelector('[data-leave-day-template]');
    const add = leaveDays.querySelector('[data-add-leave-day]');
    let nextIndex = list.children.length;

    const syncButtons = () => {
        add.disabled = list.children.length >= 366;
        list.querySelectorAll('[data-remove-leave-day]').forEach((button) => {
            button.hidden = list.children.length <= 1;
        });
    };

    add.hidden = false;
    syncButtons();
    add.addEventListener('click', () => {
        const fragment = template.content.cloneNode(true);
        fragment.querySelectorAll('[id], [name], [for]').forEach((element) => {
            for (const attribute of ['id', 'name', 'for']) {
                if (element.hasAttribute(attribute)) {
                    element.setAttribute(attribute, element.getAttribute(attribute).replace('__row__', nextIndex));
                }
            }
        });
        const date = fragment.querySelector('input[type="date"]');
        list.append(fragment);
        nextIndex++;
        syncButtons();
        date.focus();
    });
    list.addEventListener('click', (event) => {
        const remove = event.target.closest('[data-remove-leave-day]');
        if (remove && list.children.length > 1) {
            remove.closest('[data-leave-day]').remove();
            syncButtons();
            (add.disabled ? list.lastElementChild.querySelector('input') : add).focus();
        }
    });
}

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
