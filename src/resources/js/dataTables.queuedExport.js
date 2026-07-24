(function ($, DataTable) {
    'use strict';

    function emit(dt, name, detail) {
        var table = dt.table().node();

        if (typeof window.CustomEvent === 'function') {
            table.dispatchEvent(new CustomEvent('datatables-export:' + name, {
                bubbles: true,
                detail: detail
            }));
        }
    }

    function callback(config, name, dt, payload) {
        if (typeof config[name] === 'function') {
            config[name](dt, payload, config);
        }
    }

    function buildStartUrl(dt, config) {
        var url = new URL(dt.ajax.url() || window.location.href, window.location.href);
        var tableParams = new URLSearchParams($.param(dt.ajax.params()));

        tableParams.forEach(function (value, key) {
            url.searchParams.append(key, value);
        });

        url.searchParams.set('action', 'queuedExportStart');
        url.searchParams.set('exportType', config.exportType || 'xlsx');
        url.searchParams.set('sheetName', config.sheetName || 'Sheet1');

        if (config.filename) {
            url.searchParams.set('filename', config.filename);
        }

        if (config.emailTo) {
            url.searchParams.set('emailTo', config.emailTo);
        }

        return url.toString();
    }

    function request(url) {
        return $.ajax({
            dataType: 'json',
            headers: {'X-Requested-With': 'XMLHttpRequest'},
            method: 'GET',
            url: url
        });
    }

    DataTable.ext.buttons.queuedExport = {
        autoDownload: true,
        className: 'buttons-queued-export',
        errorText: 'Export failed',
        exportType: 'xlsx',
        pollInterval: 2000,
        processingText: 'Exporting…',
        sheetName: 'Sheet1',

        text: function (dt) {
            return dt.i18n('buttons.queuedExport', 'Export');
        },

        action: function (e, dt, button, config) {
            if (config._exporting) {
                return;
            }

            window.clearTimeout(config._resetTimer);
            config._exporting = true;
            config._originalText = config._originalText || dt.button(button).text();
            dt.button(button).enable(false);
            dt.button(button).text(dt.i18n('buttons.queuedExportProcessing', config.processingText));

            var finish = function () {
                config._exporting = false;
                dt.button(button).enable(true);
                dt.button(button).text(config._originalText);
            };

            var fail = function (error) {
                finish();
                dt.button(button).text(dt.i18n('buttons.queuedExportError', config.errorText));
                config._resetTimer = window.setTimeout(function () {
                    dt.button(button).text(config._originalText);
                }, 3000);
                callback(config, 'onError', dt, error);
                emit(dt, 'error', {error: error, config: config});
            };

            var succeed = function (payload) {
                finish();
                callback(config, 'onSuccess', dt, payload);
                emit(dt, 'success', {export: payload, config: config});

                if (config.autoDownload !== false) {
                    window.location.assign(payload.download_url);
                }
            };

            var observe = function (payload) {
                var processingText = dt.i18n('buttons.queuedExportProcessing', config.processingText);
                dt.button(button).text(processingText + ' ' + payload.progress + '%');
                callback(config, 'onProgress', dt, payload);
                emit(dt, 'progress', {export: payload, config: config});

                if (payload.status === 'finished') {
                    succeed(payload);
                    return;
                }

                if (payload.status === 'failed') {
                    fail(payload);
                    return;
                }

                window.setTimeout(function () {
                    request(payload.status_url).done(observe).fail(fail);
                }, Math.max(250, Number(config.pollInterval) || 2000));
            };

            request(buildStartUrl(dt, config)).done(function (payload) {
                callback(config, 'onStart', dt, payload);
                emit(dt, 'start', {export: payload, config: config});
                observe(payload);
            }).fail(fail);
        }
    };
})(jQuery, jQuery.fn.dataTable);
