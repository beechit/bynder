
/**
 * Module: BeechIt/Bynder/CompactView
 *
 * Javascript for show the Bynder compact view in an overlay/modal
 */
define(['jquery',
    'nprogress',
    'TYPO3/CMS/Backend/Modal',
    'TYPO3/CMS/Backend/Severity'
], function($, NProgress, Modal, Severity) {
    'use strict';


    /**
     *
     * @param element
     * @constructor
     * @exports BeechIt/Bynder/CompactView
     */
    var CompactViewPlugin = function(element) {
        var me = this;
        me.$btn = $(element);
        me.target = me.$btn.data('target-folder');
        me.irreObjectUid = me.$btn.data('file-irre-object');
        me.allowed = me.$btn.data('online-media-allowed');
        me.$modal = null;

        /**
         *
         * @param {Array} media
         */
        me.addMedia = function (media) {
            if (me.$modal) {
                me.$modal.modal('hide');
            }
            NProgress.start();
            $.post(TYPO3.settings.ajaxUrls['bynder_compact_view_get_files'],
                {
                    files: media,
                    targetFolder: me.target,
                    allowed: me.allowed
                },
                function (data) {
                    if (data.files.length) {
                        inline.importElementMultiple(
                            me.irreObjectUid,
                            'sys_file',
                            data.files,
                            'file'
                        );
                    } else {
                        var $confirm = Modal.confirm(
                            'ERROR',
                            data.error,
                            Severity.error,
                            [{
                                text: TYPO3.lang['button.ok'] || 'OK',
                                btnClass: 'btn-' + Severity.getCssClass(Severity.error),
                                name: 'ok',
                                active: true
                            }]
                        ).on('confirm.button.ok', function () {
                            $confirm.modal('hide');
                        });
                    }
                    NProgress.done();
                }
            );
        };


        /**
         * Open the compact view in a modal
         */
        me.openModal = function() {

            var settings = me.$btn.data();

            me.$modal = Modal.advanced({
                type: 'iframe',
                title: settings.title,
                content: settings.bynderCompactViewUrl,
                severity: Severity.default,
                size: Modal.sizes.full
            });

        };
    };

    $(document).on('click', '.t3js-bynder-compact-view-btn', function(evt) {
        evt.preventDefault();

        var $this = $(this),
            compactViewPlugin = $this.data('compactViewPlugin');
        if (!compactViewPlugin) {
            $this.data('compactViewPlugin', (compactViewPlugin = new CompactViewPlugin(this)));
        }
        compactViewPlugin.openModal();
    });

    document.addEventListener('BynderAddMedia', function (e) {
        console.log('received', e.detail);
        var $element = $(e.detail.element),
            compactViewPlugin = $element.data('compactViewPlugin');
        if (!compactViewPlugin) {
            $element.data('compactViewPlugin', (compactViewPlugin = new CompactViewPlugin($element)));
        }
        compactViewPlugin.addMedia(e.detail.media);
    });
});