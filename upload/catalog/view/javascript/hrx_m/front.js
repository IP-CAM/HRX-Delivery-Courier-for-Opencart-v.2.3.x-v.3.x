const HRX_M = {
    tmjs: null,
    leafletJsCdn: {
        src: "https://unpkg.com/leaflet@1.7.1/dist/leaflet.js",
        integrity: 'sha512-XQoYMqMTK8LvdxXYG3nZ448hOEQiglfqkJs1NOQV44cWnUrBc8PkAOcXy20w0vlaXaVUearIOBhiXZ5V3ynxwA==',
        crossOrigin: ''
    },
    leafletCssCdn: {
        src: "https://unpkg.com/leaflet@1.7.1/dist/leaflet.css",
        integrity: 'sha512-xodZBNTC5n17Xt2atTPuE1HxjVMSvLVW9ocqUKLsCC5CXdbqCmblAshOMAS6/keqq/sMZMZ19scR4PsZChSR7A==',
        crossOrigin: ''
    },

    isValidLocationSelection: function() {
        const terminalOptionEl = document.querySelector('input[name="shipping_method"][data-hrx-m-input]:checked');
        if (!terminalOptionEl) {
            return true;
        }

        const code = terminalOptionEl.value.split('.')[1];

        const hasSelection = HRX_M.tmjs.map.getActiveLocation();

        if (!hasSelection) {
            MIJORA_COMMON.alert({
                message: hrx_m_js_translation.no_terminal_selected
            });
        }
console.log('valid selection', hasSelection ? true : false);
        return hasSelection ? true : false;
    },

    commentParser: (location) => {
        return `
            Min (cm): ${location.params.min_length_cm}x${location.params.min_width_cm}x${location.params.min_height_cm}<br>
            Max (cm): ${location.params.max_length_cm}x${location.params.max_width_cm}x${location.params.max_height_cm}<br>
            Min (kg): ${location.params.min_weight_kg === null ? 0 : location.params.min_weight_kg}</br>
            Max (kg): ${location.params.max_weight_kg}
        `;
    },

    nameParser: (location) => {
        let zip = (location['zip'] || '').trim();
        return `${location.address}${zip !== '' ? ', ' + zip : ''}`;
    },

    observe: function () {
        const targetNode = document.body;

        const config = { attributes: false, childList: true, subtree: true };

        const callback = function (mutationsList, observer) {
            if (
                document.querySelector('input[value^="hrx_m.terminal_"]')
                && !document.querySelector('input[value^="hrx_m.terminal_"][data-initialized="hrx_m"]')
            ) {
                console.log('[ HRX_M ] Initializing terminals');
                document.querySelector('input[value^="hrx_m.terminal_"]').dataset.initialized = 'hrx_m';
                HRX_M.init();
            }
        };

        const observer = new MutationObserver(callback);
        observer.observe(targetNode, config);
    },

    init: function () {
        if (typeof hrx_m_status !== 'boolean' || !hrx_m_status) {
            console.log('[ HRX_M ] Disabled');
            return;
        }

        console.log('[ HRX_M ] Starting');
        this.loadLeaflet(this.loadTerminals);
    },

    loadTerminals: function () {
        fetch(hrx_m_ajax_url + '&action=getTerminals&country_code=' + hrx_m_country_code)
            .then(res => res.json())
            .then(json => {
                if (!json.data || !json.data.terminals) {
                    console.warning('[ HRX_M ]: Could not load terminals');
                    return;
                }

                HRX_M.generateJsMap(json.data.terminals);
            })
            .catch(error => {
                console.log('[ HRX_M ] Error loading terminals. ', error);
                HRX_M.generateJsMap([]);
            });
    },

    generateJsMap: function (terminals) {
        // hide generated radio buttons for terminals except first one
        let inputs = document.querySelectorAll('input[value^="hrx_m.terminal_"]');
        if (inputs.length <= 0) {
            return;
        }

        // here we can try to determine the type of checkout used, for now asume basic opencart 3.0 checkout
        let newInput = this.buildForBasicOpencart3_0(inputs);

        this.tmjs = new TerminalMappingHrx();
        this.tmjs.setImagesPath('image/catalog/hrx_m/');
        this.tmjs
            .sub('tmjs-ready', tm => {
                // tm.dom.UI.container.querySelector('.tmjs-selected-terminal').classList.add('hidden');
                // add in custom icon
                HRX_M.tmjs.map.createIcon('hrx_m', 'image/catalog/hrx_m/hrx-icon.svg');
                // need to refresh icons
                HRX_M.tmjs.map.refreshMarkerIcons();

                let selected_terminal = document.querySelector('input[value^="hrx_m.terminal_"]:checked');
                console.log('[ HRX_M ] Selected terminal:', selected_terminal);
                if (selected_terminal) {
                    let terminal_id = selected_terminal.value.replace('hrx_m.terminal_', '');
                    newInput.value = selected_terminal.value;
                    newInput.checked = true;
                    let hrx_location = HRX_M.tmjs.map.getLocationById(terminal_id);
                    HRX_M.tmjs.dom.setActiveTerminal(terminal_id);
                    if (hrx_location) {
                        HRX_M.tmjs.publish('terminal-selected-text', { text: `${hrx_location.address}, ${hrx_location.city}` });
                    }
                }

                tm.sub('terminal-selected', (data) => {
                    newInput.value = `hrx_m.terminal_${data.id}`;
                    newInput.checked = true;

                    tm.publish('close-map-modal');
                    HRX_M.tmjs.publish('terminal-selected-text', { text: `${data.address}, ${data.city}` });
                });
            });

        let closest = newInput.closest('div.radio');
        if (closest) {
            this.tmjs.dom.containerParent = closest;
        }

        this.tmjs.setTranslation(hrx_m_js_translation);

        this.tmjs.init({
            country_code: hrx_m_country_code,
            isModal: true,
            cssThemeRule: 'tmjs-hrx-theme',
            terminalList: terminals,
            parseLocationName: this.nameParser,
            parseLocationComment: this.commentParser,
            customTileServerUrl: 'http://185.140.230.40:8080/tile/{z}/{x}/{y}.png',
            parseMapTooltip: (location, leafletCoords) => {
                return this.commentParser(location);
            }
        });
    },

    buildForBasicOpencart3_0: function (inputs) {
        // hide all options except first
        inputs.forEach((el, index) => {
            if (index === 0) { return; }
            el.closest('.radio').classList.add('hidden');
        });

        let newNode = document.createElement("label");

        newNode.innerHTML = `
            <input type="radio" name="shipping_method" value="" data-hrx-m-input>
            ${hrx_m_js_translation.shipping_method_terminal} - ${hrx_m_terminal_price}
        `;

        let refNode = inputs[0].closest('label');
        let parentEl = refNode.parentNode;

        parentEl.insertBefore(newNode, refNode);

        // hide refNode
        refNode.classList.add('hidden');
        return newNode.querySelector('input');
    },

    // Leaflet loading
    loadLeaflet: function (callback) {
        if (typeof L !== "undefined") {
            console.info('[ HRX_M ] Found Leaflet version:', L.version);
            if (typeof callback === 'function') {
                callback();
            }

            return;
        }

        console.info('[ HRX_M ] Loading Leaflet');
        this.loadScript(this.leafletJsCdn, callback);
        this.loadCSS(this.leafletCssCdn);
    },

    makeIdFromUrl: function (url) {
        return url.split('/').pop().replace(/\./gi, '-').toLowerCase();
    },

    loadScript: function (urlData, callback) {
        let script_id = this.makeIdFromUrl(urlData.src);

        if (document.getElementById(script_id)) {
            setTimeout(() => {
                callback();
            }, 250);
            return;
        }

        let script = document.createElement("script");
        script.type = "text/javascript";
        script.id = script_id;

        if (script.readyState) {  //IE
            script.onreadystatechange = function () {
                if (script.readyState == "loaded" ||
                    script.readyState == "complete") {
                    script.onreadystatechange = null;
                    callback();
                }
            };
        } else {  //Others
            script.onload = function () {
                callback();
            };
        }

        script.src = urlData.src;
        script.integrity = urlData.integrity;
        script.crossOrigin = urlData.crossOrigin;
        document.getElementsByTagName("body")[0].appendChild(script);
    },

    loadCSS: function (urlData) {
        let cssId = this.makeIdFromUrl(urlData.src);
        if (document.getElementById(cssId)) {
            return;
        }
        let head = document.getElementsByTagName('head')[0];
        let link = document.createElement('link');
        link.id = cssId;
        link.rel = 'stylesheet';
        link.type = 'text/css';
        link.href = urlData.src;
        link.integrity = urlData.integrity;
        link.crossOrigin = urlData.crossOrigin;
        link.media = 'all';
        head.appendChild(link);
    }
};

document.addEventListener('DOMContentLoaded', function (e) {
    HRX_M.observe();
});