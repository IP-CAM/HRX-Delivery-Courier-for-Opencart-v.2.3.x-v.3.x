var HRX_M = {
    defaultWarehouseEl: null,
    defaultWarehouse: null,
    init: function () {
        const tabContent = document.querySelector('.hrx_m_content .tab-content');
        MIJORA_COMMON.showLoadingOverlay(true, tabContent);

        this.defaultWarehouse = HRX_M_SETTINGS_DATA.defaultWarehouse;
        this.defaultWarehouseEl = document.querySelector('[name="hrx_m_default_warehouse"]:checked');

        MIJORA_COMMON.addGlobalListener('click', '[data-mijora-paginator] [data-page]', this.handlePaginatorAction);

        MIJORA_COMMON.addGlobalListener('click', 'button[data-test-api]', this.testTokenAction);

        MIJORA_COMMON.addGlobalListener('click', 'button[data-refresh-warehouses]', function (e) {
            e.preventDefault();
            HRX_M.syncWarehouseAction(1, HRX_M_SETTINGS_DATA.syncWarehousePerPage);
        });

        MIJORA_COMMON.addGlobalListener('click', 'button[data-refresh-delivery-points]', function (e) {
            e.preventDefault();
            MIJORA_COMMON.showLoadingOverlay(true, document.querySelector('#tab-delivery'));
            document.querySelector('[data-delivery-points-loaded]').textContent = 0;
            document.querySelector('[data-delivery-points-loader]').classList.remove('hidden');
            HRX_M.syncDeliveryPointsAction(1, HRX_M_SETTINGS_DATA.syncDeliveryPointsPerPage);
        });

        MIJORA_COMMON.addGlobalListener('change', '[name="hrx_m_default_warehouse"]', this.defaultWarehouseAction, {}, document.querySelector('#tab-warehouse'));

        MIJORA_COMMON.addGlobalListener('click', '#add-price-btn', this.addPriceAction, {}, document.querySelector('#tab-prices'));
        MIJORA_COMMON.addGlobalListener('click', '[data-price-delete]', this.deletePriceAction, {}, document.querySelector('#tab-prices'));
        MIJORA_COMMON.addGlobalListener('click', '[data-price-edit]', this.editPriceAction, {}, document.querySelector('#tab-prices'));
        MIJORA_COMMON.addGlobalListener('click', '[data-price-edit-save]', this.editPriceSaveAction, {}, document.querySelector('#tab-prices'));
        MIJORA_COMMON.addGlobalListener('click', '[data-price-edit-cancel]', () => {
            HRX_M.closePriceEditModal();
        }, {}, document.querySelector('#tab-prices'));

        MIJORA_COMMON.showLoadingOverlay(false, tabContent);
    },

    isPriceRangeValid: function (string) {
        // checks if string has anything but these chars
        const regex = new RegExp('[^0-9\.\ \;\:]', 'g');
        return !regex.test(string);
    },

    testTokenAction: function (event) {
        event.preventDefault();

        const data = new FormData();
        data.set('hrx_tokent', document.querySelector('#input-api-token').value);
        data.set('hrx_test_mode', document.querySelector('#input-api-test-mode').value);

        HRX_M.ajax('testToken', HRX_M.testTokenHandleAction, document.querySelector('#tab-api'), {
            method: 'POST',
            body: data
        });
    },

    testTokenHandleAction: function (json) {
        if (!json.data.token.valid) {
            MIJORA_COMMON.alert({ message: json.data.token.message });
            return;
        }

        MIJORA_COMMON.alert({ message: 'Token OK' });
    },

    syncWarehouseAction: function (page, per_page) {
        const data = new FormData();
        data.set('page', page);
        data.set('per_page', per_page);

        HRX_M.ajax('syncWarehouse', HRX_M.syncWarehouseHandleAction, document.querySelector('#tab-warehouse'), {
            method: 'POST',
            body: data
        });
    },

    syncWarehouseHandleAction: function (json) {
        console.log('Loaded: ' + json.data.page);
        window.mijora_testas = json.data;
        if (json.data.hasMore) {
            console.log('Loading next page...');
            HRX_M.syncWarehouseAction(json.data.page + 1, HRX_M_SETTINGS_DATA.syncWarehousePerPage);
            return;
        }

        console.log('Warehouse Sync Done');
        HRX_M.getWarehousePage(1);
    },

    getWarehousePage: function (page) {
        const data = new FormData();
        data.set('page', page);

        HRX_M.ajax('getWarehousePage', function (json) {
            document.querySelector('#tab-warehouse').innerHTML = json.data.html;
        }, document.querySelector('#tab-warehouse'), {
            method: 'POST',
            body: data
        });
    },

    getDeliveryPointsPage: function (page) {
        const data = new FormData();
        data.set('page', page);

        HRX_M.ajax('getDeliveryPointsPage', function (json) {
            document.querySelector('#tab-delivery').innerHTML = json.data.html;
        }, document.querySelector('#tab-delivery'), {
            method: 'POST',
            body: data
        });
    },

    syncDeliveryPointsAction: function (page, per_page) {
        const data = new FormData();
        data.set('page', page);
        data.set('per_page', per_page);

        HRX_M.ajax('syncDeliveryPoints', HRX_M.syncDeliveryPointsHandleAction, null, {
            method: 'POST',
            body: data
        });
    },

    syncDeliveryPointsHandleAction: function (json) {
        console.log('Loaded: ' + json.data.page);
        window.mijora_testas = json.data;
        if (json.data.hasMore) {
            console.log('Loading next page...');
            document.querySelector('[data-delivery-points-loaded]').textContent = json.data.page * HRX_M_SETTINGS_DATA.syncDeliveryPointsPerPage;
            HRX_M.syncDeliveryPointsAction(json.data.page + 1, HRX_M_SETTINGS_DATA.syncDeliveryPointsPerPage);
            return;
        }

        console.log('Delivery Points Sync Done');
        HRX_M.getDeliveryPointsPage(1);
        document.querySelector('[data-delivery-points-loader]').classList.add('hidden');
        MIJORA_COMMON.showLoadingOverlay(false, document.querySelector('#tab-delivery'));
    },

    defaultWarehouseAction: function (event) {
        event.preventDefault();
        const checkbox = event.target;
        const warehousePane = document.querySelector('#tab-warehouse');

        if (checkbox.isEqualNode(HRX_M.defaultWarehouseEl)) {
            checkbox.checked = true;
            console.log('Same default');

            return;
        }

        const data = new FormData();
        data.set('warehouse_id', checkbox.dataset.warehouseId);

        HRX_M.ajax('setDefaultWarehouse', (json) => {
            console.log(json);

            warehousePane.querySelectorAll('[name="hrx_m_default_warehouse"]:checked').forEach(item => {
                if (item.dataset.warehouseId === checkbox.dataset.warehouseId) {
                    return;
                }

                item.checked = false;
            });

            if (!json.data.defaultWarehouse || !json.data.defaultWarehouse.id) {
                MIJORA_COMMON.alert({ message: 'Failed to update default warehouse' });
                return;
            }

            const defaulWarehouseAlert = document.querySelector('[data-no-default-warehouse]');
            if (defaulWarehouseAlert) {
                defaulWarehouseAlert.remove();
            }

            checkbox.checked = true;
            HRX_M.defaultWarehouseEl = checkbox;
            HRX_M.defaultWarehouse = json.data.defaultWarehouse;
        }, warehousePane, {
            method: 'POST',
            body: data
        });
    },

    addPriceAction: function (e) {
        e.preventDefault();

        const priceTab = document.querySelector('#tab-prices');
        const countrySelect = priceTab.querySelector('#price-table [name="country"]');

        if (countrySelect.selectedIndex < 0) {
            MIJORA_COMMON.alert({ message: 'No country to add' });
            return;
        }

        let data = {
            country_code: countrySelect.value,
            country_name: countrySelect.selectedOptions[0].textContent,
            price: priceTab.querySelector('#price-table [name="terminal_price"]').value,
            price_range_type: priceTab.querySelector('#price-table [name="terminal_price_range_type"]').value
        };

        console.log(data);
        HRX_M.savePriceAction(data, (response) => {
            if (!response.result) {
                return;
            }

            countrySelect.selectedOptions[0].remove();

            let tempBody = document.createElement('tbody');
            tempBody.innerHTML = response.price_row_html;

            const priceTable = document.querySelector('#tab-prices #created-prices');
            priceTable.append(tempBody.firstChild);
            tempBody = null;

            HRX_M.sortPriceRows();
        });
    },

    sortPriceRows: function () {
        const priceTable = document.querySelector('#tab-prices #created-prices');
        let rows = priceTable.querySelectorAll('[data-price-row]');
        [...rows]
            .sort((a, b) => {
                return a.dataset.priceRow.localeCompare(b.dataset.priceRow);
            })
            .forEach(row => priceTable.append(row));
        
        if (rows.length > 0) {
            document.querySelector('#tab-prices #created-prices #no-price-notification').classList.add('hidden');
        } else {
            document.querySelector('#tab-prices #created-prices #no-price-notification').classList.remove('hidden');
        }
    },

    editPriceAction: function (e) {
        e.preventDefault();

        const countryCode = e.target.dataset.priceEdit;

        if (countryCode === '') {
            MIJORA_COMMON.alert({ message: 'Country code missing!' });
            return;
        }

        const priceRow = document.querySelector(`#tab-prices [data-price-row="${countryCode}"]`);
        const priceData = HRX_M.decodeBase64Json(priceRow.dataset.priceData);
        HRX_M.openPriceEditModal(priceData);
    },

    openPriceEditModal: function (priceData) {
        $('.edit-price-modal [name="country"]').val(priceData.data.country_code);
        $('.edit-price-modal [name="country_name"]').val(priceData.data.country_name);
        $('.edit-price-modal [name="terminal_price"]').val(priceData.data.price);
        $('.edit-price-modal [name="terminal_price_range_type"]').val(priceData.data.price_range_type);
        // $('.edit-price-modal [name="courier_price"]').val(priceData.courier_price);
        // $('.edit-price-modal [name="courier_price_range_type"]').val(priceData.courier_price_range_type);
        document.querySelector('.edit-price-modal').classList.remove('hidden');
    },

    closePriceEditModal: function () {
        $('.edit-price-modal [name="country"]').val('');
        $('.edit-price-modal [name="country_name"]').val('');
        $('.edit-price-modal [name="terminal_price"]').val('');
        $('.edit-price-modal [name="terminal_price_range_type"]').val(0);
        // $('.edit-price-modal [name="courier_price"]').val(priceData.courier_price);
        // $('.edit-price-modal [name="courier_price_range_type"]').val(priceData.courier_price_range_type);
        document.querySelector('.edit-price-modal').classList.add('hidden');
    },

    editPriceSaveAction: function (e) {
        e.preventDefault();

        const priceModal = document.querySelector('#tab-prices .edit-price-modal');

        let data = {
            country_code: priceModal.querySelector('[name="country"]').value,
            country_name: priceModal.querySelector('[name="country_name"]').value,
            price: priceModal.querySelector('[name="terminal_price"]').value,
            price_range_type: priceModal.querySelector('[name="terminal_price_range_type"]').value
        };

        console.log('Editing price', data);
        HRX_M.savePriceAction(data, (response) => {
            if (!response.result) {
                return;
            }

            let tempBody = document.createElement('tbody');
            tempBody.innerHTML = response.price_row_html;

            const priceTable = document.querySelector('#tab-prices #created-prices');
            const currentRow = priceTable.querySelector(`[data-price-row="${data.country_code}"]`);

            if (currentRow) {
                currentRow.remove();
            }

            // insert updated row
            priceTable.append(tempBody.firstChild);
            tempBody = null;

            HRX_M.sortPriceRows();
            HRX_M.closePriceEditModal();
        });
    },

    deletePriceAction: function (e) {
        e.preventDefault();

        const countryCode = e.target.dataset.priceDelete;

        if (countryCode === '') {
            MIJORA_COMMON.alert({ message: 'Country code missing!' });
            return;
        }

        console.log('Delete price for', countryCode);

        const data = new FormData();
        data.set('country_code', countryCode);

        HRX_M.ajax('deletePrice', (json) => {
            console.log('deletePrice', json);

            if (!json.data.result) {
                return;
            }

            const priceRow = document.querySelector(`#tab-prices [data-price-row="${countryCode}"]`);

            if (!priceRow) {
                return;
            }

            const priceData = HRX_M.decodeBase64Json(priceRow.dataset.priceData);
            const countrySelect = document.querySelector('#tab-prices #price-table [name="country"]');

            const optionEl = document.createElement('option');
            optionEl.value = priceData.data.country_code;
            optionEl.textContent = priceData.data.country_name;

            countrySelect.append(optionEl);

            priceRow.remove();
            HRX_M.sortPriceRows();

        }, document.querySelector('#tab-prices'), {
            method: 'POST',
            body: data
        });
    },

    savePriceAction: function (priceData, callback) {
        if (!priceData.price || !HRX_M.isPriceRangeValid(priceData.price)) {
            MIJORA_COMMON.alert({ message: 'Invalid price or price range entered!' });
            return;
        }

        const data = new FormData();

        Object.keys(priceData).forEach(key => {
            data.set(key, priceData[key]);
        });

        HRX_M.ajax('savePrice', (json) => {
            console.log(json);

            if (typeof callback === 'function') {
                callback(json.data);
            }
        }, document.querySelector('#tab-prices'), {
            method: 'POST',
            body: data
        });
    },

    ajax: function (action, callback, overlayOn, fetchInit) {
        MIJORA_COMMON.showLoadingOverlay(true, overlayOn);
        /*
        {
            method: 'POST',
            body: data
        }
        */
        fetch(`${HRX_M_SETTINGS_DATA.url_ajax}&action=${action}`, fetchInit)
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

        if (typeof HRX_M[jsFunction] === 'function') {
            HRX_M[jsFunction](page);
        }
    },

    decodeBase64Json: function (string) {
        return JSON.parse(atob(string));
    }
};

document.addEventListener('DOMContentLoaded', function (e) {
    HRX_M.init();
});