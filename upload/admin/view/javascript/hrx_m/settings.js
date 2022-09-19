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

        MIJORA_COMMON.addGlobalListener('click', 'button[data-refresh-delivery-locations]', function (e) {
            e.preventDefault();

            let actionFunctionName = `syncDelivery${e.target.dataset.refreshDeliveryLocations}Action`;

            if (typeof HRX_M[actionFunctionName] !== 'function') {
                return;
            }

            HRX_M[actionFunctionName](1);
        });

        MIJORA_COMMON.addGlobalListener('change', '[name="hrx_m_default_warehouse"]', this.defaultWarehouseAction, {}, document.querySelector('#tab-warehouse'));

        MIJORA_COMMON.addGlobalListener('click', '#add-price-btn', this.addPriceAction, {}, document.querySelector('#tab-prices'));
        MIJORA_COMMON.addGlobalListener('click', '[data-price-delete]', this.deletePriceAction, {}, document.querySelector('#tab-prices'));
        MIJORA_COMMON.addGlobalListener('click', '[data-price-edit]', this.editPriceAction, {}, document.querySelector('#tab-prices'));
        MIJORA_COMMON.addGlobalListener('click', '[data-price-edit-save]', this.editPriceSaveAction, {}, document.querySelector('#tab-prices'));
        MIJORA_COMMON.addGlobalListener('click', '[data-price-edit-cancel]', () => {
            HRX_M.closePriceEditModal();
        }, {}, document.querySelector('#tab-prices'));

        MIJORA_COMMON.addGlobalListener('click', '[data-close-modal]', function (e) {
            const modal = document.querySelector(e.target.dataset.closeModal);
            if (modal) {
                HRX_M.closeParcelDefaultEditModal(modal);
            }
        });

        MIJORA_COMMON.addGlobalListener('click', '[data-save-modal]', function (e) {
            const targetModal = e.target.dataset.saveModal;
            const functionName = MIJORA_COMMON.toCamelCase(targetModal.replace('#', '')) + 'HandleSaveAction';
            const modal = document.querySelector(targetModal);

            if (modal && typeof HRX_M[functionName] === 'function') {
                HRX_M[functionName](modal);
            }

        });

        MIJORA_COMMON.addGlobalListener('click', '[data-parcel-default-edit]', function (e) {
            const modal = document.querySelector(e.target.dataset.modal);
            if (modal) {
                HRX_M.openParcelDefaultEditModal(modal, e.target.dataset.parcelDefaultEdit);
            }
        });

        MIJORA_COMMON.addGlobalListener('click', '[data-parcel-default-reset]', function (e) {
            MIJORA_COMMON.confirm({
                message: 'Confirm removing default dimensions?',
                accept: () => {
                    HRX_M.resetParcelDefaultAction(e.target.dataset.parcelDefaultReset);
                }
            });
        });

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

    getDeliveryLocationsPage: function (page) {
        const data = new FormData();
        data.set('page', page);

        HRX_M.ajax('getDeliveryLocationsPage', function (json) {
            document.querySelector('#tab-delivery-courier').innerHTML = json.data.html;
        }, document.querySelector('#tab-delivery-courier'), {
            method: 'POST',
            body: data
        });
    },

    syncDeliveryTerminalAction: function (page, per_page) {

        if (page === 1) {
            MIJORA_COMMON.showLoadingOverlay(true, document.querySelector('#tab-delivery'));
            document.querySelector('[data-delivery-points-loaded]').textContent = 0;
            document.querySelector('[data-delivery-points-loader]').classList.remove('hidden');
            per_page = HRX_M_SETTINGS_DATA.syncDeliveryPointsPerPage;
        }

        const data = new FormData();
        data.set('page', page);
        data.set('per_page', per_page);

        HRX_M.ajax('syncDeliveryPoints', HRX_M.syncDeliveryTerminalHandleAction, null, {
            method: 'POST',
            body: data
        });
    },

    syncDeliveryTerminalHandleAction: function (json) {
        console.log('Loaded: ' + json.data.page);
        if (json.data.hasMore) {
            console.log('Loading next page...');
            document.querySelector('[data-delivery-points-loaded]').textContent = json.data.page * HRX_M_SETTINGS_DATA.syncDeliveryPointsPerPage;
            HRX_M.syncDeliveryTerminalAction(json.data.page + 1, HRX_M_SETTINGS_DATA.syncDeliveryPointsPerPage);
            return;
        }

        console.log('Delivery Points Sync Done');
        HRX_M.getDeliveryPointsPage(1);
        document.querySelector('[data-delivery-points-loader]').classList.add('hidden');
        MIJORA_COMMON.showLoadingOverlay(false, document.querySelector('#tab-delivery'));
    },

    syncDeliveryCourierAction: function (page, per_page) {
        MIJORA_COMMON.showLoadingOverlay(true, document.querySelector('#tab-delivery-courier'));

        HRX_M.ajax('syncCourierDeliveryLocations', HRX_M.syncDeliveryCourierHandleAction, null, {
            method: 'POST'
        });
    },

    syncDeliveryCourierHandleAction: function (json) {
        console.log('Loaded: ' + json.data.locations_loaded);

        console.log('Delivery Courier Locations Sync Done');
        HRX_M.getDeliveryLocationsPage(1);

        MIJORA_COMMON.showLoadingOverlay(false, document.querySelector('#tab-delivery-courier'));
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
            price_range_type: priceTab.querySelector('#price-table [name="terminal_price_range_type"]').value,
            price_courier: priceTab.querySelector('#price-table [name="courier_price"]').value,
            price_courier_range_type: priceTab.querySelector('#price-table [name="courier_price_range_type"]').value
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
        $('.edit-price-modal [name="courier_price"]').val(priceData.data.price_courier);
        $('.edit-price-modal [name="courier_price_range_type"]').val(priceData.data.price_courier_range_type);
        document.querySelector('.edit-price-modal').classList.remove('hidden');
    },

    closePriceEditModal: function () {
        $('.edit-price-modal [name="country"]').val('');
        $('.edit-price-modal [name="country_name"]').val('');
        $('.edit-price-modal [name="terminal_price"]').val('');
        $('.edit-price-modal [name="terminal_price_range_type"]').val(0);
        $('.edit-price-modal [name="courier_price"]').val('');
        $('.edit-price-modal [name="courier_price_range_type"]').val(0);
        document.querySelector('.edit-price-modal').classList.add('hidden');
    },

    editPriceSaveAction: function (e) {
        e.preventDefault();

        const priceModal = document.querySelector('#tab-prices .edit-price-modal');

        let data = {
            country_code: priceModal.querySelector('[name="country"]').value,
            country_name: priceModal.querySelector('[name="country_name"]').value,
            price: priceModal.querySelector('[name="terminal_price"]').value,
            price_range_type: priceModal.querySelector('[name="terminal_price_range_type"]').value,
            price_courier: priceModal.querySelector('[name="courier_price"]').value,
            price_courier_range_type: priceModal.querySelector('[name="courier_price_range_type"]').value
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
        if (!HRX_M.isPriceRangeValid(priceData.price) || !HRX_M.isPriceRangeValid(priceData.price_courier)) {
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

    getParcelDefaultPage: function (page) {
        const data = new FormData();
        data.set('page', page);

        HRX_M.ajax('getParcelDefaultPage', function (json) {
            document.querySelector('#tab-parcel-default [data-parcel-default-list]').innerHTML = json.data.html;
        }, document.querySelector('#tab-parcel-default [data-parcel-default-list]'), {
            method: 'POST',
            body: data
        });
    },

    openParcelDefaultEditModal: function (modal, targetCategoryId) {
        let defaultValues = HRX_M_PARCEL_DEFAULT_GLOBAL;
        let title = 'Global';

        const row = document.querySelector(`#tab-parcel-default [data-row-category-id="${targetCategoryId}"]`);

        if (row) {
            title = row.querySelector('[data-category-name]').textContent;
        }

        if (row && row.dataset.parcelDefault) {
            defaultValues = HRX_M.decodeBase64Json(row.dataset.parcelDefault);
        }

        modal.querySelector('#parcel_default_modal_title').textContent = title;

        modal.querySelector('[name="hrx_m_pd_category_id"]').value = targetCategoryId;
        modal.querySelector('[name="hrx_m_pd_weight"]').value = defaultValues.weight;
        modal.querySelector('[name="hrx_m_pd_length"]').value = defaultValues.length;
        modal.querySelector('[name="hrx_m_pd_width"]').value = defaultValues.width;
        modal.querySelector('[name="hrx_m_pd_height"]').value = defaultValues.height;

        modal.classList.remove('hidden');
        modal.scrollIntoView({ behavior: "smooth" });
    },

    closeParcelDefaultEditModal: function (modal) {
        modal.querySelector('[name="hrx_m_pd_category_id"]').value = 0;
        modal.querySelector('[name="hrx_m_pd_weight"]').value = '';
        modal.querySelector('[name="hrx_m_pd_length"]').value = '';
        modal.querySelector('[name="hrx_m_pd_width"]').value = '';
        modal.querySelector('[name="hrx_m_pd_height"]').value = '';

        modal.querySelectorAll('.has-error').forEach(el => {
            el.classList.remove('has-error');
        });
        modal.classList.add('hidden');
    },

    parcelDefaultModalHandleSaveAction: function (modal) {
        const inputNamePrefix = 'hrx_m_pd_';
        const modalData = {
            category_id: modal.querySelector(`[name="${inputNamePrefix}category_id"]`).value,
            weight: modal.querySelector(`[name="${inputNamePrefix}weight"]`).value,
            length: modal.querySelector(`[name="${inputNamePrefix}length"]`).value,
            width: modal.querySelector(`[name="${inputNamePrefix}width"]`).value,
            height: modal.querySelector(`[name="${inputNamePrefix}height"]`).value
        };

        const data = new FormData();
        data.set('category_id', modalData.category_id);
        data.set('weight', modalData.weight);
        data.set('length', modalData.length);
        data.set('width', modalData.width);
        data.set('height', modalData.height);

        HRX_M.ajax('saveParcelDefault', function (response) {
            if (response.data.validated) {

                // Check if it was global values update
                if (response.data.save_result && response.data.parcel_default.category_id === 0) {
                    HRX_M_PARCEL_DEFAULT_GLOBAL = response.data.parcel_default;
                    if (response.data.html) {
                        document.querySelector(`#tab-parcel-default [data-parcel-default-global]`).innerHTML = response.data.html;
                    }
                    HRX_M.closeParcelDefaultEditModal(modal);
                    return;
                }

                if (response.data.html) {
                    HRX_M.parcelDefaultUpdateRowHtml(response.data.html, modalData.category_id);
                }

                HRX_M.closeParcelDefaultEditModal(modal);

                document.querySelector(`#tab-parcel-default [data-row-category-id="${modalData.category_id}"]`).scrollIntoView({ behavior: "smooth" });

                return;
            }

            const fields = Object.keys(response.data.validation);
            fields.forEach(field => {
                if (response.data.validation[field]) {
                    return;
                }

                const inputEl = modal.querySelector(`[name="${inputNamePrefix}${field}"]`);
                if (inputEl) {
                    inputEl.closest('.input-group').classList.add('has-error');
                }
            });

        }, modal.querySelector('.panel'), {
            method: 'POST',
            body: data
        });
    },

    resetParcelDefaultAction: function (categoryId) {
        const data = new FormData();
        data.set('category_id', categoryId);

        HRX_M.ajax('resetParcelDefault', function (response) {
            if (response.data.html) {
                HRX_M.parcelDefaultUpdateRowHtml(response.data.html, categoryId);
            }
        }, document.querySelector('#tab-parcel-default [data-parcel-default-list]'), {
            method: 'POST',
            body: data
        });
    },

    parcelDefaultUpdateRowHtml: function (html, categoryId) {
        let tempBody = document.createElement('tbody');
        tempBody.innerHTML = html;

        const tbodyEl = document.querySelector('#tab-parcel-default [data-parcel-default-list] table > tbody');
        const currentRow = tbodyEl.querySelector(`[data-row-category-id="${categoryId}"]`);
        const currentRowSibling = currentRow.nextSibling;

        if (currentRow) {
            currentRow.remove();
        }

        // insert updated row
        tbodyEl.insertBefore(tempBody.firstChild, currentRowSibling);
        tempBody = null;
    },

    ajax: function (action, callback, overlayOn, fetchInit) {
        MIJORA_COMMON.showLoadingOverlay(true, overlayOn);

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