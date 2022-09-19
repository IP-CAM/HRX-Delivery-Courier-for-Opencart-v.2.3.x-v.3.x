const HRX_M_ORDER_LIST = {
    init: function () {
        this.addLogoToOrderList();
        this.addFilterInput();
    },

    addFilterInput: function () {
        const urlParams = new URLSearchParams(location.search);

        let filterOnlyHrxValue = urlParams.get('filter_hrx_m_only');

        if (filterOnlyHrxValue === null) {
            filterOnlyHrxValue = 0;
        }
        filterOnlyHrxValue = parseInt(filterOnlyHrxValue);

        const html = `
            <label class="control-label" for="input-filter_hrx_m_only">${HRX_M_ORDER_LIST_DATA.trans.filter_label_hrx_only}</label>
            <select name="filter_hrx_m_only" id="input-filter_hrx_m_only" class="form-control">
                <option value="0" ${filterOnlyHrxValue === 0 ? 'selected' : ''}>${HRX_M_ORDER_LIST_DATA.trans.filter_option_no}</option>
                <option value="1" ${filterOnlyHrxValue === 1 ? 'selected' : ''}>${HRX_M_ORDER_LIST_DATA.trans.filter_option_yes}</option>
            </select>
        `;

        let inputWrapper = document.createElement('div');
        inputWrapper.classList.add('form-group');
        inputWrapper.innerHTML = html;

        let filtersBlock = document.querySelector('#filter-order');
        if (!filtersBlock) { // oc2 doesnt have filter element with id
            // falback to finding filter input
            filtersBlock = document.querySelector('input[name="filter_order_id"]').closest('.form-group').parentNode;
        }

        // still nothing? giveup
        if (!filtersBlock) {
            console.error('[ HRX_M_ORDER_LIST ] could not insert filter input');
            return;
        }

        const refElement = filtersBlock.querySelector('.form-group');
        refElement.parentNode.insertBefore(inputWrapper, refElement);
    },

    addLogoToOrderList: function () {
        let logoImg = document.createElement('img');
        logoImg.src = 'view/image/hrx_m/logo-hrx.svg';
        logoImg.alt = 'HRX Logo';
        logoImg.classList.add('hrx_m-order-logo');
        document.querySelectorAll(`input[name^='shipping_code'][value^='hrx_m']`)
            .forEach(el => {
                let cols = el.closest('tr').querySelectorAll('td');
                if (cols.length < 2) {
                    return;
                }
                cols[2].append(logoImg.cloneNode());
            })
    }
}

document.addEventListener('DOMContentLoaded', function () {
    HRX_M_ORDER_LIST.init();
});