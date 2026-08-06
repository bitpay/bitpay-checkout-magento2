define(['ko', 'uiComponent', 'jquery'], function (ko, Component, $) {
    'use strict';

    return Component.extend({
        defaults: {
            template: 'Bitpay_BPCheckout/download_support_package',
            url: '',
            success: true,
            message: '',
            visible: false,
            buttonLabel: 'Download',
            loadingLabel: 'Generating...'
        },

        /**
     * Init observable variables
     * @return {Object}
     */
        initObservable: function () {
            this._super().observe(['success', 'message', 'visible', 'buttonLabel']);

            return this;
        },

        /**
     * @override
     */
        initialize: function () {
            this._super();
            this.messageClass = ko.computed(function () {
                return (
                    'message-validation message message-' +
          (this.success() ? 'success' : 'error')
                );
            }, this);
        },

        /**
     * @param {bool} success
     * @param {String} message
     */
        showMessage: function (success, message) {
            this.message(message);
            this.success(success);
            this.visible(true);
        },

        /**
     * Send request to server to download support package
     */
        downloadSupportPackage: function () {
            var origLabel = this.buttonLabel();

            this.visible(false);

            $('[data-ui-id="bitpay-buttons-download-support-package"]').prop(
                'disabled',
                true
            );

            this.buttonLabel(this.loadingLabel);
            $.ajax({
                type: 'POST',
                url: this.url,
                cache: false,
                xhr: function () {
                    var xhr = new XMLHttpRequest();

                    xhr.onreadystatechange = function () {
                        if (xhr.readyState === 2) {
                            if (xhr.status === 200) {
                                xhr.responseType = 'blob';
                            } else {
                                xhr.responseType = 'text';
                            }
                        }
                    };
                    return xhr;
                },
                success: function (response, _textStatus, request) {
                    var extractedFilename = request
                            .getResponseHeader('content-disposition')
                            .split('filename=')[1]
                            .split(';')[0],
                        fileName = extractedFilename,
                        blob,
                        isIE = false,
                        a = document.createElement('a');

                    if (extractedFilename) {
                        // remove trailing quote from filename
                        fileName = fileName.replace(new RegExp('^["]+'), '');
                        fileName = fileName.replace(new RegExp('["]+$'), '');
                    }

                    blob = new File([response], fileName, {
                        type: 'application/octetstream'
                    }),
                    isIE = false || !!document.documentMode;
                    if (isIE) {
                        window.navigator.msSaveBlob(blob, fileName);
                    } else {
                        a = document.createElement('a');

                        a.download = fileName;
                        a.href = (window.URL || window.webkitURL).createObjectURL(blob);
                        document.body.appendChild(a);
                        a.click();
                        document.body.removeChild(a);
                    }
                },
                error: function (e) {
                    this.showMessage(false, e.responseText);
                }.bind(this),
                complete: function () {
                    this.buttonLabel(origLabel);
                    $('[data-ui-id="bitpay-buttons-download-support-package"]').prop(
                        'disabled',
                        false
                    );
                }.bind(this)
            });
        }
    });
});
