const HRX_M_ORDER = {
    wrapper: null, // wrapper housing order panel, rgistered during panel move

    customData: {},

    editBtn: null,

    init: function () {
        this.moveOrderInformationPanel();
        this.registerGlobalListeners();
    },

    moveOrderInformationPanel: function () {
        const historyPanel = document.querySelector('#history').closest('.panel');

        HRX_M_ORDER.wrapper = document.createElement('div');
        HRX_M_ORDER.wrapper.dataset.hrxOrderPanelWrapper = '';

        historyPanel.parentNode.insertBefore(HRX_M_ORDER.wrapper, historyPanel);
        HRX_M_ORDER.wrapper.append(document.querySelector('[data-hrx-order-panel]'));
    },

    registerGlobalListeners: function () {
        MIJORA_COMMON.addGlobalListener('click', '[data-print-label]', this.getLabelAction, {}, HRX_M_ORDER.wrapper);
        MIJORA_COMMON.addGlobalListener('click', '[data-print-label-return]', this.getLabelAction, {}, HRX_M_ORDER.wrapper);

        MIJORA_COMMON.addGlobalListener('click', '[data-register-order]', (e) => {
            e.preventDefault();
            if (!HRX_M_ORDER.isCustomDataChanged()) {
                HRX_M_ORDER.registerOrderAction();
                return;
            }

            MIJORA_COMMON.confirm({
                message: 'You have unsaved changes. Continue with registration?',
                accept: () => {
                    HRX_M_ORDER.registerOrderAction();
                }
            });
        }, {}, HRX_M_ORDER.wrapper);

        MIJORA_COMMON.addGlobalListener('click', '[data-refresh-order]', (e) => {
            e.preventDefault();
            HRX_M_ORDER.getHrxOrderData();
        }, {}, HRX_M_ORDER.wrapper);

        MIJORA_COMMON.addGlobalListener('click', '[data-edit-order]', (e) => {
            e.preventDefault();
            HRX_M_ORDER.editOrderAction();
        }, {}, HRX_M_ORDER.wrapper);

        MIJORA_COMMON.addGlobalListener('click', '[data-get-tracking-info]', (e) => {
            e.preventDefault();
            HRX_M_ORDER.getHrxTrackingInfo();
        }, {}, HRX_M_ORDER.wrapper);

        MIJORA_COMMON.addGlobalListener('click', '[data-change-order-state]', (e) => {
            e.preventDefault();
            MIJORA_COMMON.confirm({
                message: 'Confirm that parcel is ready for pickup?',
                accept: () => {
                    HRX_M_ORDER.changeHrxOrderState(e.target.dataset.changeOrderState);
                }
            });
        });

        MIJORA_COMMON.addGlobalListener('click', '[data-cancel-order]', (e) => {
            e.preventDefault();
            MIJORA_COMMON.confirm({
                message: 'Confirm HRX order cancelation?',
                accept: () => {
                    HRX_M_ORDER.cancelHrxOrder();
                }
            });
        });
    },

    registerOrderAction: function () {
        const data = new FormData();
        data.set('order_id', HRX_M_ORDER_DATA.orderId);
        data.set('is_order_panel', true);

        HRX_M_ORDER.ajax('registerHrxOrder', function (json) {
            // console.log(json);
            if (json.data.registered) {
                MIJORA_COMMON.alert({ message: json.data.registered });
                HRX_M_ORDER.getHrxOrderData();
            }
        }, HRX_M_ORDER.wrapper, {
            method: 'POST',
            body: data
        });
    },

    cancelHrxOrder: function () {
        const data = new FormData();
        data.set('order_id', HRX_M_ORDER_DATA.orderId);
        data.set('is_order_panel', true);

        HRX_M_ORDER.ajax('cancelHrxOrder', function (json) {
            // console.log(json);
            if (json.data.html) {
                HRX_M_ORDER.wrapper.innerHTML = json.data.html;
            }
        }, HRX_M_ORDER.wrapper, {
            method: 'POST',
            body: data
        });
    },

    getHrxOrderData: function () {
        const data = new FormData();
        data.set('order_id', HRX_M_ORDER_DATA.orderId);
        data.set('is_order_panel', true);

        HRX_M_ORDER.ajax('getHrxOrderData', function (json) {
            if (json.data.html) {
                HRX_M_ORDER.wrapper.innerHTML = json.data.html;
            }
        }, HRX_M_ORDER.wrapper, {
            method: 'POST',
            body: data
        });
    },

    changeHrxOrderState: function (isReady) {
        const data = new FormData();
        data.set('order_id', HRX_M_ORDER_DATA.orderId);
        data.set('state', isReady);
        data.set('is_order_panel', true);

        HRX_M_ORDER.ajax('changeHrxOrderState', function (json) {
            // console.log(json);
            if (json.data.html) {
                HRX_M_ORDER.wrapper.innerHTML = json.data.html;
            }
        }, HRX_M_ORDER.wrapper, {
            method: 'POST',
            body: data
        });
    },

    editOrderAction: function () {
        // console.log(HRX_M_ORDER.customData);

        if (HRX_M_ORDER.isCustomDataChanged() === false) {
            console.log('[ HRX_M ] No changes');
            return;
        }

        const data = new FormData();
        data.set('order_id', HRX_M_ORDER_DATA.orderId);
        // load up dimensions
        HRX_M_ORDER.wrapper.querySelectorAll('.hrx-order-dimensions [name^=hrx_]').forEach(item => {
            data.set(item.name, item.value);
        });

        if (HRX_M_ORDER.customData.hrx_warehouse) {
            data.set('hrx_warehouse', HRX_M_ORDER.customData.hrx_warehouse);
        }

        data.set('hrx_comment', HRX_M_ORDER.wrapper.querySelector('[name="hrx_comment"]').value);

        data.set('is_order_panel', true);

        HRX_M_ORDER.ajax('editOrder', function (json) {
            if (json.data.html) {
                HRX_M_ORDER.wrapper.innerHTML = json.data.html;
                HRX_M_ORDER.customData = {};
            }
        }, HRX_M_ORDER.wrapper, {
            method: 'POST',
            body: data
        });
    },

    checkEditBtnState: function () {
        HRX_M_ORDER.editBtn = HRX_M_ORDER.wrapper.querySelector('[data-edit-order]');

        if (!HRX_M_ORDER.editBtn) {
            return;
        }

        let invalid = false;
        document.querySelectorAll('[data-hrx-order-panel] [name]').forEach(el => {
            if (!el.reportValidity()) {
                invalid = true;
            }
        });

        // disable if invalid fields or no changes to custom data
        HRX_M_ORDER.editBtn.disabled = invalid || !HRX_M_ORDER.isCustomDataChanged();
    },

    markChange: function (el) {
        if (!el.reportValidity()) {
            HRX_M_ORDER.checkEditBtnState();
            return;
        }

        const newValue = el.value;
        const originalValue = el.dataset.value;
        HRX_M_ORDER.customData[el.name] = newValue !== originalValue ? newValue : null;

        HRX_M_ORDER.checkEditBtnState();
    },

    isCustomDataChanged: function () {
        return Object.keys(HRX_M_ORDER.customData).some(key => HRX_M_ORDER.customData[key] !== null);
    },

    getLabelAction: function (e) {
        e.preventDefault();

        const data = new FormData();
        data.set('order_id', HRX_M_ORDER_DATA.orderId);
        data.set('label_type', e.target.dataset.labelType);

        HRX_M_ORDER.ajax('getLabel', function (json) {
            // console.log(json);
            if (json.data.label.file_content) {
                MIJORA_COMMON.downloadPdf(json.data.label.file_content, json.data.label.file_name);
            }
        }, HRX_M_ORDER.wrapper, {
            method: 'POST',
            body: data
        });
    },

    getHrxTrackingInfo: function () {
        const data = new FormData();
        data.set('order_id', HRX_M_ORDER_DATA.orderId);

        HRX_M_ORDER.ajax('getHrxTrackingInfo', function (json) {
            const trackingContainer = HRX_M_ORDER.wrapper.querySelector('[data-hrx-tracking-events]');
            if (!trackingContainer) {
                return;
            }

            if (json.data.html) {
                trackingContainer.innerHTML = json.data.html;
            }
        }, HRX_M_ORDER.wrapper, {
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
        fetch(`${HRX_M_ORDER_DATA.urlAjax}&action=${action}`, fetchInit)
            .then(res => res.json())
            .then(json => {
                // console.log(json);
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
                // console.log(reason);
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

        if (typeof HRX_M_ORDER[jsFunction] === 'function') {
            HRX_M_ORDER[jsFunction](page);
        }
    },

    decodeBase64Json: function (string) {
        return JSON.parse(atob(string));
    }
}

document.addEventListener('DOMContentLoaded', function () {
    HRX_M_ORDER.init();
});