const HRX_M_ORDER_LIST = {
    confirm_action_no: null, // should have one function at any given time
    confirm_action_yes: null, // should have one function at any given time

    init: function () {
        this.addLogoToOrderList();
        // this.addPrintLabelBtn();
        this.addFilterInput();
    },

    showWorking: function (shouldShow, target) {
        const btnEl = document.querySelector(target);

        btnEl.classList[shouldShow ? 'add' : 'remove']('disabled');
        btnEl.querySelector('.fa').classList[shouldShow ? 'add' : 'remove']('hidden');
        btnEl.querySelector('.bs5-spinner-border').classList[shouldShow ? 'remove' : 'add']('hidden');
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
    },

    addPrintLabelBtn: function () {
        let btnContainer = document.querySelector('#button-invoice').parentNode;
        let printBtn = document.createElement('a');
        printBtn.href = '#';
        printBtn.id = 'hrx_m_print_labels_btn';
        printBtn.dataset.originalTitle = HRX_M_ORDER_LIST_DATA.trans.tooltip_btn_print_register;
        printBtn.dataset.toggle = 'tooltip';
        printBtn.classList.add('btn', 'btn-hrx_m', 'hrx_m-print-label');
        printBtn.innerHTML = `
            <i class="fa fa-print"></i>
            <div class="bs5-spinner-border hidden"></div>
        `;

        $(printBtn).tooltip();

        printBtn.addEventListener('click', function (e) {
            e.preventDefault();
            HRX_M_ORDER_LIST.printLabelsAction();
        });

        btnContainer.append(printBtn);
    },

    addPrintManifestBtn: function () {
        let btnContainer = document.querySelector('#button-invoice').parentNode;
        let manifestBtn = document.createElement('a');
        manifestBtn.href = '#';
        manifestBtn.id = 'hrx_m_print_manifest_btn';
        manifestBtn.dataset.originalTitle = HRX_M_ORDER_LIST_DATA.trans.tooltip_btn_manifest;
        manifestBtn.dataset.toggle = 'tooltip';
        manifestBtn.classList.add('btn', 'btn-hrx_m', 'hrx_m-print-manifest');
        manifestBtn.innerHTML = `
            <i class="fa fa-file-pdf-o"></i>
            <div class="bs5-spinner-border hidden"></div>
        `;

        $(manifestBtn).tooltip();

        manifestBtn.addEventListener('click', function (e) {
            e.preventDefault();
            HRX_M_ORDER_LIST.printManifestAction();
        });

        btnContainer.append(manifestBtn);
    },

    printLabelsAction: function () {
        const checkedOrdersEl = document.querySelectorAll(`input[name^='selected']:checked`);

        let selectedOrders = [];

        checkedOrdersEl.forEach(el => {
            const shippingCodeEl = el.parentNode.querySelector('input[name^="shipping_code"]');
            if (!shippingCodeEl || !shippingCodeEl.value.startsWith('hrx_m.')) {
                return;
            }

            selectedOrders.push(el.value);
        });

        if (selectedOrders.length < 1) {
            alert(HRX_M_ORDER_LIST_DATA.trans.alert_no_orders);
            return;
        }

        HRX_M_ORDER_LIST.showWorking(true, '.btn-hrx_m.hrx_m-print-label');

        HRX_M_ORDER_LIST.confirm_action_yes = function () {
            HRX_M_ORDER_LIST.showWorking(false, '.btn-hrx_m.hrx_m-print-label');
            HRX_M_ORDER_LIST.printLabels(selectedOrders);
        };

        HRX_M_ORDER_LIST.confirm_action_no = function () {
            HRX_M_ORDER_LIST.showWorking(false, '.btn-hrx_m.hrx_m-print-label');
        };
        HRX_M_ORDER_LIST.confirm(HRX_M_ORDER_LIST_DATA.trans.confirm_print_labels);
    },

    printLabels: function (orderIds) {
        data = new FormData();
        orderIds.forEach(id => {
            data.append('order_ids[]', id);
        });

        HRX_M_ORDER_LIST.showWorking(true, '.btn-hrx_m.hrx_m-print-label');
        fetch(HRX_M_ORDER_LIST_DATA.ajax_url + '&action=printLabel', {
            method: 'POST',
            body: data
        })
            .then(res => res.json())
            .then(json => {
                console.log(json);

                if (!json.data && json.data !== false) {
                    return;
                }

                if (typeof json.data.error !== 'undefined') {
                    alert(HRX_M_ORDER_LIST_DATA.trans.alert_response_error + json.data.error);
                    return;
                }

                if (typeof json.data.pdf === 'undefined') {
                    alert(HRX_M_ORDER_LIST_DATA.trans.alert_no_pdf);
                    return;
                }

                HRX_M_ORDER_LIST.downloadPdf(json.data.pdf, 'hrx_labels');
            })
            .catch((error) => {
                console.error(error);
                alert(HRX_M_ORDER_LIST_DATA.trans.alert_response_error);
            })
            .finally(() => {
                HRX_M_ORDER_LIST.showWorking(false, '.btn-hrx_m.hrx_m-print-label');
            });
    },

    callCourier: function () {
        // data = new FormData();

        HRX_M_ORDER_LIST.showWorking(true, '.btn-hrx_m.hrx_m-call-courier');
        fetch(HRX_M_ORDER_LIST_DATA.ajax_url + '&action=callCourier', {
            method: 'GET',
            // body: data
        })
            .then(res => res.json())
            .then(json => {
                console.log(json);

                if (typeof json.data === 'undefined') {
                    alert(HRX_M_ORDER_LIST_DATA.trans.alert_bad_response);
                    return;
                }

                if (typeof json.data.error !== 'undefined') {
                    alert(HRX_M_ORDER_LIST_DATA.trans.alert_response_error + json.data.error);
                    return;
                }

                if (json.data) {
                    alert(HRX_M_ORDER_LIST_DATA.trans.notify_courrier_called);
                    return;
                }

                alert(HRX_M_ORDER_LIST_DATA.trans.notify_courrier_call_failed);
            })
            .catch((error) => {
                console.error(error);
                alert(HRX_M_ORDER_LIST_DATA.trans.alert_response_error);
            })
            .finally(() => {
                HRX_M_ORDER_LIST.showWorking(false, '.btn-hrx_m.hrx_m-call-courier');
            });
    },

    confirm: function (message) {
        const modal = document.querySelector('.hrx_m-modal');
        modal.querySelector('.hrx_m-modal-msg').innerHTML = message;
        $(modal).modal('show');
    },

    downloadPdf: function (data, filename) {
        const pdfContent = `data:application/pdf;base64,${data}`;

        var encodedUri = encodeURI(pdfContent);
        var link = document.createElement("a");
        link.setAttribute("href", encodedUri);
        link.setAttribute("download", filename + ".pdf");
        document.body.appendChild(link); // Required for FF

        link.click(); // This will download the data file

        link.remove();
    }
}

document.addEventListener('DOMContentLoaded', function () {
    HRX_M_ORDER_LIST.init();
});