var HRX_M_MANIFEST = {
    refreshedOrdersCount: 0,

    init: function () {
        MIJORA_COMMON.addGlobalListener('click', '[data-btn-filter]', (e) => {
            e.preventDefault();
            HRX_M_MANIFEST.getManifestPage(1);
        });

        MIJORA_COMMON.addGlobalListener('change', '[data-check-all]', this.handleCheckAllAction);

        MIJORA_COMMON.addGlobalListener('click', '[data-mijora-paginator] [data-page]', this.handlePaginatorAction);

        MIJORA_COMMON.addGlobalListener('click', '[data-print-label]', this.getLabelAction);

        MIJORA_COMMON.addGlobalListener('click', '[data-mass-action]', (e) => {
            e.preventDefault();

            const functionName = 'massAction' + MIJORA_COMMON.toCamelCase(e.target.dataset.massAction);

            if (typeof HRX_M_MANIFEST[functionName] === 'function') {
                HRX_M_MANIFEST[functionName](e.target.dataset);
            }
        });

        MIJORA_COMMON.addGlobalListener('click', '[data-print-label-return]', this.getLabelAction);

        MIJORA_COMMON.addGlobalListener('click', '[data-register-order]', this.registerOrderAction);

        MIJORA_COMMON.addGlobalListener('click', '[data-refresh-order]', (e) => {
            e.preventDefault();
            HRX_M_MANIFEST.getHrxOrderData(e.target.dataset.ocOrderId);
        });
        MIJORA_COMMON.addGlobalListener('click', '[data-order-state-btn]', (e) => {
            e.preventDefault();
            HRX_M_MANIFEST.changeHrxOrderState(e.target.dataset.ocOrderId, e.target.dataset.changeOrderState);
        });
        MIJORA_COMMON.addGlobalListener('click', '[data-cancel-order]', (e) => {
            e.preventDefault();
            MIJORA_COMMON.confirm({
                message: 'Confirm HRX order cancelation?',
                accept: () => {
                    HRX_M_MANIFEST.cancelHrxOrder(e.target.dataset.ocOrderId);
                }
            });
        });
        MIJORA_COMMON.addGlobalListener('click', '[data-btn-refresh-orders]', (e) => {
            e.preventDefault();
            MIJORA_COMMON.confirm({
                message: 'Confirm HRX order data refresh?',
                accept: () => {
                    HRX_M_MANIFEST.massRefreshDataAction(1);
                }
            });
        });
    },

    handleCheckAllAction: function (event) {
        const target = event.target.dataset.checkAll || null;

        if (!target) {
            return;
        }

        document.querySelectorAll(target).forEach(checkbox => checkbox.checked = event.target.checked);
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
            // console.log(json);
            if (json.data.html) {
                HRX_M_MANIFEST.refreshRowHtml(orderId, json.data.html);

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
            // console.log(json);
            if (json.data.label.file_content) {
                MIJORA_COMMON.downloadPdf(json.data.label.file_content, json.data.label.file_name);
            }
        }, document.querySelector('#manifest_list'), {
            method: 'POST',
            body: data
        });
    },

    massActionMultipleLabels: function (dataset) {
        const selectedIds = HRX_M_MANIFEST.getSelectedOrderIds();

        if (selectedIds.length <= 0) {
            MIJORA_COMMON.alert({ message: 'Nothing selected' });
            return;
        }

        const data = new FormData();
        data.set('label_type', dataset.labelType);

        selectedIds.forEach(id => {
            data.append('order_id[]', id);
        });

        HRX_M_MANIFEST.ajax('getMultipleLabels', function (json) {
            // console.log(json);

            if (json.data.labels) {
                Object.keys(json.data.labels).forEach(key => {
                    MIJORA_COMMON.downloadPdf(json.data.labels[key].file_content, json.data.labels[key].file_name);
                });
            }

            if (json.data.errors) {
                const errorMsgs = Object.keys(json.data.errors).map(key => {
                    return `ID ${key}: ${json.data.errors[key]}`;
                });
                MIJORA_COMMON.alert({ message: `<p>${errorMsgs.join("</p>\n")}</p>` });
            }
        }, document.querySelector('#manifest_list'), {
            method: 'POST',
            body: data
        });
    },

    getSelectedOrderIds: function () {
        return [...document.querySelectorAll('#manifest_list [name="selected[]"]:checked')].map(item => item.value);
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

    getHrxOrderData: function (orderId) {
        const data = new FormData();
        data.set('order_id', orderId);

        HRX_M_MANIFEST.ajax('getHrxOrderData', function (json) {
            // console.log(json);
            if (json.data.html) {
                HRX_M_MANIFEST.refreshRowHtml(orderId, json.data.html);
            }
        }, document.querySelector('#manifest_list'), {
            method: 'POST',
            body: data
        });
    },

    changeHrxOrderState: function (orderId, isReady) {
        const data = new FormData();
        data.set('order_id', orderId);
        data.set('state', isReady);

        HRX_M_MANIFEST.ajax('changeHrxOrderState', function (json) {
            // console.log(json);
            if (json.data.html) {
                HRX_M_MANIFEST.refreshRowHtml(orderId, json.data.html);
            }
        }, document.querySelector('#manifest_list'), {
            method: 'POST',
            body: data
        });
    },

    massActionStateChange: function (dataset, confirmed) {
        if (!confirmed) {
            MIJORA_COMMON.confirm({
                message: 'Confirm order pickup state change?',
                accept: () => {
                    HRX_M_MANIFEST.massActionStateChange(dataset, true);
                }
            });
            return;
        }
        const selectedIds = HRX_M_MANIFEST.getSelectedOrderIds();

        if (selectedIds.length <= 0) {
            MIJORA_COMMON.alert({ message: 'Nothing selected' });
            return;
        }

        const data = new FormData();
        data.set('state', dataset.orderState);

        selectedIds.forEach(id => {
            data.append('order_id[]', id);
        });

        HRX_M_MANIFEST.ajax('massChangeHrxOrderState', function (json) {
            // console.log(json);
            const pageNr = HRX_M_MANIFEST.getCurrentPageNumber();

            if (json.data.errors && Object.keys(json.data.errors).length > 0) {
                const errorMsgs = Object.keys(json.data.errors).map(key => {
                    return `ID ${key}: ${json.data.errors[key]}`;
                });

                MIJORA_COMMON.alert({
                    message: `<p>${errorMsgs.join("</p>\n")}</p>`,
                    onClose: () => {
                        if (json.data.reload_page) {
                            HRX_M_MANIFEST.getManifestPage(pageNr);
                        }
                    }
                });
                return;
            }

            if (json.data.reload_page) {
                HRX_M_MANIFEST.getManifestPage(pageNr);
            }
        }, document.querySelector('#manifest_list'), {
            method: 'POST',
            body: data
        });
    },

    massRefreshDataAction: function (page) {

        if (page === 1) {
            MIJORA_COMMON.showLoadingOverlay(true, document.querySelector('#manifest_list'));
            document.querySelector('[data-orders-refreshed]').textContent = 0;
            document.querySelector('[data-orders-refresh-loader]').classList.remove('hidden');
            HRX_M_MANIFEST.refreshedOrdersCount = 0;
        }

        const data = new FormData();
        data.set('refresh_page', page);

        HRX_M_MANIFEST.ajax('refreshOrdersDataFromApi', function (json) {
            // console.log(json);
            const count = json.data.refreshed_count || 0;

            if (count > 0) {
                HRX_M_MANIFEST.refreshedOrdersCount += count;
                document.querySelector('[data-orders-refreshed]').textContent = HRX_M_MANIFEST.refreshedOrdersCount;
                setTimeout(() => {
                    HRX_M_MANIFEST.massRefreshDataAction(page + 1);
                }, 100);

                return;
            }

            // if count get 0 means finished refreshing
            document.querySelector('[data-orders-refresh-loader]').classList.add('hidden');
            MIJORA_COMMON.showLoadingOverlay(false, document.querySelector('#manifest_list'));
            // reload list
            HRX_M_MANIFEST.getManifestPage(1);
        }, null, {
            method: 'POST',
            body: data
        });
    },

    cancelHrxOrder: function (orderId) {
        const data = new FormData();
        data.set('order_id', orderId);

        HRX_M_MANIFEST.ajax('cancelHrxOrder', function (json) {
            // console.log(json);
            if (json.data.html) {
                HRX_M_MANIFEST.refreshRowHtml(orderId, json.data.html);
            }
        }, document.querySelector('#manifest_list'), {
            method: 'POST',
            body: data
        });
    },

    refreshRowHtml: function (orderId, rowHtml) {
        let tempBody = document.createElement('tbody');
        tempBody.innerHTML = rowHtml;

        const localRow = document.querySelector(`[data-row-order-id="${orderId}"]`);
        if (localRow) {
            localRow.innerHTML = tempBody.firstChild.innerHTML;
        }
        tempBody = null;
    },

    getCurrentPageNumber: function () {
        const pageNumberEl = document.querySelector('[data-mijora-paginator] .current_page');

        if (!pageNumberEl) {
            return 1;
        }

        return parseInt(pageNumberEl.textContent.trim());
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