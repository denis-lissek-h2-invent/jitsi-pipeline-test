/*
 * Welcome to your app's main JavaScript file!
 *
 */

import $ from 'jquery';

global.$ = global.jQuery = $;
import ('jquery-confirm');
import {initSearchUser} from './searchUser'
var title = "Bestätigung";
var cancel = "Abbrechen";
var ok = "OK";

function initDirectSend() {
    $(document).on('click', '.directSend', function (e) {
        var $url = $(this).prop('href');
        var $targetUrl = $(this).data('url');
        var target = $(this).data('target');

        e.preventDefault();
        $.get($url, function (data) {
            $(target).closest('div').load($targetUrl + ' ' + target, function () {
                hideTooltip();
                $('[data-mdb-toggle="popover"]').popover({html: true});
                $('[data-mdb-toggle="tooltip"]').tooltip('hide');
                $('.tooltip').remove();
                $('[data-mdb-toggle="tooltip"]').tooltip();
            });
            if (typeof data.snack !== 'undefined') {
                $('#snackbar').text(data.text).addClass('show');
            }

        })
    });
}

function initconfirmHref() {

    $(document).on('click', '.confirmHref', function (e) {
        e.preventDefault();
        var url = $(this).prop('href');
        var text = $(this).data('text');
        if (typeof text === 'undefined') {

            text = 'Wollen Sie die Aktion durchführen?'
        }

        var jc = $.confirm({
            title: title,
            content: text,
            theme: 'material',
            columnClass: 'col-md-8 col-12 col-lg-6',
            buttons: {
                confirm: {
                    text: ok, // text for button
                    btnClass: 'btn-outline-danger btn', // class for the button
                    action: function () {
                        window.location.href = url;
                        jc.showLoading(false);
                        return false;
                    },


                },
                cancel: {
                    text: cancel, // text for button
                    btnClass: 'btn-outline-primary btn', // class for the button
                },
            }
        });
    })
}


function initconfirmLoadOpenPopUp() {

    $(document).on('click', '.confirmloadOpenPopUp', function (e) {

        e.preventDefault();
        var url = $(this).prop('href');
        var text = $(this).data('text');
        if (typeof text === 'undefined') {

            text = 'Wollen Sie die Aktion durchführen?'
        }

        $.confirm({
            title: title,
            content: text,
            theme: 'material',
            columnClass: 'col-md-8 col-12 col-lg-6',
            buttons: {
                confirm: {
                    text: ok, // text for button
                    btnClass: 'btn-outline-danger btn', // class for the button
                    action: function () {
                        const win = window.open('about:blank');
                        $.get(url, function (data) {
                            if(data.popups){
                                data.popups.forEach(function (value,i) {
                                    win.location.href = value;
                                })
                            }
                            window.location.href = data.redirectUrl;
                        })
                    },


                },
                cancel: {
                    text: cancel, // text for button
                    btnClass: 'btn-outline-primary btn', // class for the button
                },
            }
        });
    })
}

function initConfirmDirectSendHref() {
    $(document).on('click', '.directSendWithConfirm', function (e) {
        e.preventDefault();
        var $url = $(this).prop('href');
        var $targetUrl = $(this).data('url');
        var target = $(this).data('target');
        var text = $(this).data('text');
        if (typeof text === 'undefined') {
            text = 'Wollen Sie die Aktion durchführen?'
        }

        $.confirm({
            title: title,
            content: text,
            theme: 'material',
            columnClass: 'col-md-8 col-12 col-lg-6',
            buttons: {
                confirm: {
                    text: ok, // text for button
                    btnClass: 'btn-outline-danger btn', // class for the button
                    action: function () {
                        $.get($url, function (data) {
                            $(target).closest('div').load($targetUrl + ' ' + target, function () {
                                initSearchUser();
                                hideTooltip();
                                $('[data-mdb-toggle="popover"]').popover({html: true});
                                $('[data-mdb-toggle="tooltip"]').tooltip('hide');
                                $('.tooltip').remove();
                                $('[data-mdb-toggle="tooltip"]').tooltip();

                            });
                            if (typeof data.snack !== 'undefined') {
                                $('#snackbar').text(data.snack).addClass('show');
                            }
                            $('[data-mdb-toggle="popover"]').popover({html: true});
                            $('[data-mdb-toggle="tooltip"]').tooltip('hide');
                            $('.tooltip').remove();
                            $('[data-mdb-toggle="tooltip"]').tooltip()
                        })
                    },


                },
                cancel: {
                    text: cancel, // text for button
                    btnClass: 'btn-outline-primary btn', // class for the button
                },
            }
        });

    });
}

function initAjaxSend(titleL, cancelL, okL) {
    title = titleL;
    cancel = cancelL;
    ok = okL;
    initConfirmDirectSendHref();
    initDirectSend();
    initconfirmHref();
    initconfirmLoadOpenPopUp();
}
function hideTooltip() {
    $('.tooltip').remove();
}

export {initAjaxSend, initDirectSend, initConfirmDirectSendHref, initconfirmHref}