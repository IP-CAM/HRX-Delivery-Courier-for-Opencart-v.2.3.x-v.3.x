var HRX_M_MANIFEST = {
    init: function () {
        MIJORA_COMMON.addGlobalListener('click', '[data-btn-filter]', (e) => {
            e.preventDefault();
            HRX_M_MANIFEST.getManifestPage(1);
        });
        MIJORA_COMMON.addGlobalListener('click', '[data-mijora-paginator] [data-page]', this.handlePaginatorAction);

        MIJORA_COMMON.addGlobalListener('click', '[data-print-label]', this.getLabelAction);
        MIJORA_COMMON.addGlobalListener('click', '[data-print-label-return]', this.getLabelAction);
        MIJORA_COMMON.addGlobalListener('click', '[data-register-order]', this.registerOrderAction);
    },

    getManifestPage: function (page) {
        const data = new FormData();
        data.set('page', page);

        const filters = HRX_M_MANIFEST.collectFilters();
        filters.forEach(filter => {
            data.set(filter.name, filter.value);
        });

        HRX_M_MANIFEST.ajax('getManifestPage', function (json) {
            document.querySelector('#manifest_list').innerHTML = json.data.html;
        }, document.querySelector('#manifest_list'), {
            method: 'POST',
            body: data
        });
    },

    registerOrderAction: function (e) {
        e.preventDefault();

        if (!HRX_M_MANIFEST_DATA.default_warehouse.id) {
            MIJORA_COMMON.alert({ message: HRX_M_MANIFEST_DATA.ts.no_default_warehouse })
            return;
        }

        const orderId = e.target.dataset.ocOrderId;
        const data = new FormData();
        data.set('order_id', orderId);

        HRX_M_MANIFEST.ajax('registerHrxOrder', function (json) {
            console.log(json);
            if (json.data.html) {
                let tempBody = document.createElement('tbody');
                tempBody.innerHTML = json.data.html;

                const localRow = document.querySelector(`[data-row-order-id="${orderId}"]`);
                if (localRow) {
                    localRow.innerHTML = tempBody.firstChild.innerHTML;
                }
                tempBody = null;
                if (json.data.registered) {
                    MIJORA_COMMON.alert({ message: json.data.registered });
                }
            }
        }, document.querySelector('#manifest_list'), {
            method: 'POST',
            body: data
        });
    },

    getLabelAction: function (e) {
        e.preventDefault();

        const data = new FormData();
        data.set('order_id', e.target.dataset.ocOrderId);
        data.set('label_type', e.target.dataset.labelType);

        HRX_M_MANIFEST.ajax('getLabel', function (json) {
            console.log(json);
            if (json.data.label.file_content) {
                MIJORA_COMMON.downloadPdf(json.data.label.file_content, json.data.label.file_name);
            }
        }, document.querySelector('#manifest_list'), {
            method: 'POST',
            body: data
        });
    },

    collectFilters: function () {
        let filters = [];
        document.querySelectorAll('[name^="filter_"]').forEach(item => {
            filters.push({
                name: item.name,
                value: item.value
            });
        });

        return filters;
    },

    ajax: function (action, callback, overlayOn, fetchInit) {
        MIJORA_COMMON.showLoadingOverlay(true, overlayOn);
        /*
        {
            method: 'POST',
            body: data
        }
        */
        fetch(`${HRX_M_MANIFEST_DATA.url_ajax}&action=${action}`, fetchInit)
            .then(res => res.json())
            .then(json => {
                if (!json.data) {
                    MIJORA_COMMON.alert({ message: 'Invalid response from server' });
                    return;
                }

                if (json.error) {
                    MIJORA_COMMON.alert({ message: json.error });
                    return;
                }

                if (typeof callback === 'function') {
                    callback(json);
                    return;
                }
            })
            .catch((reason) => {
                console.log(reason);
                MIJORA_COMMON.alert({ message: reason });
            })
            .finally(() => {
                MIJORA_COMMON.showLoadingOverlay(false, overlayOn);
            });
    },

    handlePaginatorAction: function (e) {
        e.preventDefault();
        const jsFunction = e.target.dataset['jsFunction'] || null;
        const page = e.target.dataset['page'] || null;

        if (!jsFunction || !page) {
            console.log('Paginator missing parameters');
            return;
        }

        if (typeof HRX_M_MANIFEST[jsFunction] === 'function') {
            HRX_M_MANIFEST[jsFunction](page);
        }
    },

    decodeBase64Json: function (string) {
        return JSON.parse(atob(string));
    }
};

document.addEventListener('DOMContentLoaded', function (e) {
    HRX_M_MANIFEST.init();
});