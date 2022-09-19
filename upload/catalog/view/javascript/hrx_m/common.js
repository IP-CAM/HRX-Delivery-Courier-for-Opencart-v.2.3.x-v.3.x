var MIJORA_COMMON = window['MIJORA_COMMON'] || {
    loaderHtml: `<div class="mijora-bs5-spinner-border"></div>`,

    showLoadingOverlay: function (shouldShow, target) {
        if (!(target instanceof Node)) {
            target = document.querySelector(target);
        }

        if (!target) {
            return;
        }

        const hasLoaderElement = target.querySelector('.mijora_loader');

        if (shouldShow && !hasLoaderElement) {
            let loaderEl = document.createElement('div');
            loaderEl.classList.add('mijora_loader');
            loaderEl.innerHTML = MIJORA_COMMON.loaderHtml;

            target.append(loaderEl);
        }

        if (!shouldShow && hasLoaderElement) {
            hasLoaderElement.remove();
        }

        target.classList[shouldShow ? 'add' : 'remove']('loading');
    },

    addGlobalListener: function (type, selector, callback, options, parent = document) {
        parent.addEventListener(type, (e) => {
            if (e.target.matches(selector)) {
                callback(e);
            }
        }, options);
    },

    htmlFromString: function (string) {
        let parser = new DOMParser();
        let doc = parser.parseFromString(string, 'text/html');
        return doc.body;
    },

    downloadPdf: function (data, filename) {
        const pdfContent = `data:application/pdf;base64,${data}`;

        var encodedUri = encodeURI(pdfContent);
        var link = document.createElement("a");
        link.setAttribute("href", encodedUri);
        link.setAttribute("download", filename.replace('.pdf', '') + ".pdf");
        document.body.appendChild(link); // Required for FF

        link.click(); // This will download the data file

        link.remove();
    },

    alert: function ({ message, onClose }) {
        if (window.HTMLDialogElement === undefined) {
            window.alert(message);
            if (typeof onClose === 'function') {
                onClose();
                return;
            }
            return;
        }

        let dialog = document.querySelector('dialog[data-mijora-alert-dialog]');
        if (!dialog) {
            dialog = document.createElement('dialog');
            dialog.dataset.mijoraAlertDialog = true;
            dialog.style.padding = 0;
            let dialogHtml = `
                <form method="dialog" class="panel panel-default" style="margin: 0;">
                    <div class="panel-body" data-alert-message></div>
                    <div class="panel-footer"><button class="btn btn-success">OK</button></div>
                </form>
            `;

            dialog.innerHTML = dialogHtml;
            document.body.append(dialog);
        }

        dialog.querySelector('[data-alert-message]').innerHTML = message;

        dialog.addEventListener('close', (e) => {
            dialog.remove();
            if (typeof onClose === 'function') {
                onClose();
                return;
            }
        }, { once: true });

        dialog.showModal();
    },

    confirm: function ({ message, accept, cancel }) {
        if (window.HTMLDialogElement === undefined) {
            const decision = window.confirm(message);

            if (typeof accept === 'function' && decision) {
                accept();
                return;
            }

            if (typeof cancel === 'function' && !decision) {
                cancel();
                return;
            }

            return;
        }

        let dialog = document.querySelector('dialog[data-mijora-confirm-dialog]');
        if (!dialog) {
            dialog = document.createElement('dialog');
            dialog.dataset.mijoraConfirmDialog = true;
            dialog.style.padding = 0;
            let dialogHtml = `
                <form method="dialog" class="panel panel-default" style="margin: 0;">
                    <div class="panel-body" data-alert-message></div>
                    <div class="panel-footer">
                        <button value="accepted" class="btn btn-success">OK</button>
                        <button value="canceled" class="btn btn-danger">Cancel</button>
                    </div>
                </form>
            `;

            dialog.innerHTML = dialogHtml;
            document.body.append(dialog);
        }

        dialog.querySelector('[data-alert-message]').innerHTML = message;

        dialog.addEventListener('close', (e) => {
            if (typeof accept === 'function' && dialog.returnValue === 'accepted') {
                accept();
            }

            if (typeof cancel === 'function' && dialog.returnValue === 'canceled') {
                cancel();
            }

            dialog.remove();
        }, { once: true });

        dialog.showModal();
    },

    toCamelCase: function (string) {
        return string.toLowerCase().replace(/[^a-zA-Z0-9]+(.)/g, (m, chr) => chr.toUpperCase());
    }
};