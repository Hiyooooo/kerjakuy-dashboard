const holidayImport = document.querySelector('[data-holiday-import]');
const leaveDays = document.querySelector('[data-leave-days]');
const checkoutEnd = document.getElementById('check_out_end');
const checkoutEndDay = document.getElementById('check_out_end_day');

if (leaveDays) {
    const list = leaveDays.querySelector('[data-leave-day-list]');
    const template = leaveDays.querySelector('[data-leave-day-template]');
    const add = leaveDays.querySelector('[data-add-leave-day]');
    let nextIndex = Math.max(-1, ...Array.from(list.querySelectorAll('input[type="date"]'), (date) =>
        Number(date.id.slice('date-'.length)))) + 1;

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

const employeeForm = document.querySelector('[data-employee-picker]');

if (employeeForm) {
    const searchPanel = employeeForm.querySelector('[data-employee-search]');
    const query = searchPanel.querySelector('[data-employee-query]');
    const find = searchPanel.querySelector('[data-employee-find]');
    const feedback = searchPanel.querySelector('[data-employee-feedback]');
    const results = searchPanel.querySelector('[data-employee-results]');
    const options = results.querySelector('select');
    const code = employeeForm.elements.employee_code;
    const number = new Intl.NumberFormat('id-ID', { maximumFractionDigits: 2 });
    let found = [];
    let searchVersion = 0;

    const readData = async (endpoint, params) => {
        const url = new URL(endpoint, window.location.href);
        url.search = params;
        const response = await fetch(url, { headers: { Accept: 'application/json' } });
        if (!response.ok) {
            throw new Error('Data tidak tersedia.');
        }
        return (await response.json()).data;
    };

    const findEmployees = (search) => readData(employeeForm.dataset.employeePicker, new URLSearchParams({ search }));
    searchPanel.hidden = false;
    query.addEventListener('input', () => {
        searchVersion++;
        results.hidden = true;
        feedback.textContent = '';
    });
    query.addEventListener('keydown', (event) => {
        if (event.key === 'Enter') {
            event.preventDefault();
            find.click();
        }
    });
    find.addEventListener('click', async () => {
        const search = query.value.trim();
        if (!search) {
            feedback.textContent = 'Masukkan nama atau kode pegawai.';
            query.focus();
            return;
        }
        const version = ++searchVersion;
        results.hidden = true;
        feedback.textContent = 'Mencari pegawai…';
        try {
            const employees = await findEmployees(search);
            if (version !== searchVersion) return;
            found = employees;
            options.replaceChildren(new Option('Pilih dari hasil pencarian', ''));
            found.forEach((employee) => options.add(new Option(
                `${employee.full_name} · ${employee.employee_code}`, employee.employee_code)));
            results.hidden = found.length === 0;
            feedback.textContent = found.length ? `${found.length} pegawai ditemukan.` : 'Tidak ada pegawai yang cocok.';
        } catch {
            if (version === searchVersion) feedback.textContent = 'Pencarian gagal. Coba lagi.';
        }
    });
    options.addEventListener('change', () => {
        const employee = found.find((person) => person.employee_code === options.value);
        if (employee) {
            code.value = employee.employee_code;
            code.dispatchEvent(new Event('input', { bubbles: true }));
            feedback.textContent = `Dipilih: ${employee.full_name} · ${employee.employee_code}.`;
        }
    });
    code.addEventListener('input', () => {
        if (code.value !== options.value) {
            options.value = '';
            feedback.textContent = '';
        }
    });

    const balancePanel = employeeForm.querySelector('[data-balance-panel]');
    if (balancePanel) {
        const result = balancePanel.querySelector('[data-balance-result]');
        const category = employeeForm.elements.leave_category_id;
        const check = balancePanel.querySelector('[data-balance-check]');
        let balanceVersion = 0;
        const invalidateBalance = () => {
            balanceVersion++;
            result.textContent = '';
        };
        balancePanel.hidden = false;
        employeeForm.addEventListener('input', (event) => {
            if (event.target === code || event.target === category || event.target.closest('[data-leave-day]')) {
                invalidateBalance();
            }
        });
        leaveDays.addEventListener('click', (event) => {
            if (event.target.closest('[data-add-leave-day], [data-remove-leave-day]')) invalidateBalance();
        });
        check.addEventListener('click', async () => {
            const employeeCode = code.value.trim();
            const categoryId = category.value;
            const rows = Array.from(leaveDays.querySelectorAll('[data-leave-day]'));
            const totals = new Map();
            const dates = new Map();
            const version = ++balanceVersion;
            if (!employeeCode || !categoryId || rows.some((row) =>
                Array.from(row.querySelectorAll('input')).some((input) => !input.validity.valid))) {
                result.textContent = 'Isi kode pegawai, kategori, tanggal, dan durasi sebelum mengecek saldo.';
                return;
            }
            for (const row of rows) {
                const date = row.querySelector('input[type="date"]').value;
                const year = Number(date.slice(0, 4));
                const duration = row.querySelector('input[type="number"]').valueAsNumber;
                dates.set(year, date);
                totals.set(year, (totals.get(year) ?? 0) + Math.round(duration * 100));
            }
            result.textContent = 'Mengecek saldo…';
            try {
                const employees = await findEmployees(employeeCode);
                if (version !== balanceVersion) return;
                const employee = employees.find((person) => person.employee_code.toLowerCase() === employeeCode.toLowerCase());
                if (!employee) {
                    result.textContent = 'Kode pegawai tidak ditemukan. Pilih pegawai dari hasil pencarian.';
                    return;
                }
                const balances = [];
                const datesByYear = Array.from(dates.values()).sort();
                while (datesByYear.length) {
                    const params = new URLSearchParams({ employee_id: employee.id, leave_category_id: categoryId });
                    datesByYear.splice(0, 50).forEach((date) => params.append('dates[]', date));
                    balances.push(...await readData(employeeForm.dataset.leaveBalance, params));
                    if (version !== balanceVersion) return;
                }
                result.textContent = [
                    employee.full_name,
                    `Total diajukan: ${number.format(Array.from(totals.values()).reduce((sum, value) => sum + value, 0) / 100)} hari`,
                    ...balances.map(({ entitlement_year: year, available_days: available }) => {
                        const requested = totals.get(year);
                        const requestLabel = `${year} · Diajukan ${number.format(requested / 100)} hari`;
                        if (available === null) return `${requestLabel} · Jatah belum tersedia`;
                        const insufficient = requested > Math.round(Number(available) * 100);
                        return `${requestLabel} · Saldo ${number.format(available)} hari${insufficient ? ' · Saldo kurang' : ''}`;
                    }),
                ].join('\n');
            } catch {
                if (version === balanceVersion) result.textContent = 'Saldo gagal dimuat. Coba lagi.';
            }
        });
    }
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
