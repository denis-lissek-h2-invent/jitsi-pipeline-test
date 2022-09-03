import $ from 'jquery';
import * as mdb from 'mdb-ui-kit'; // lib
global.$ = global.jQuery = $;
import Push from "push.js";
import {initCircle} from './initCircle'
import notificationSound from '../sound/notification.mp3'
import callerSound from '../sound/ringtone.mp3'
import {setSnackbar, deleteToast} from './myToastr';
import {TabUtils} from './tabBroadcast'
import {refreshDashboard} from './refreshDashboard';

import {initDragParticipants} from './lobby_moderator_acceptDragger'
import {close} from './moderatorIframe'
import {initStarSend} from "./endModal";

var callersoundplay = new Audio(callerSound);
callersoundplay.loop = true;

function initNotofication() {
    Push.Permission.request();
}

function masterNotify(data) {

    Push.Permission.request();
    if (data.type === 'notification') {
        notifymoderator(data)
    } else if (data.type === 'cleanNotification') {
        deleteToast(data.messageId);
    } else if (data.type === 'refresh') {
        refresh(data)
    } else if (data.type === 'modal') {
        loadModal(data)
    } else if (data.type === 'redirect') {
        redirect(data);
    } else if (data.type === 'snackbar') {
        setSnackbar(data.message, data.color)
    } else if (data.type === 'newJitsi') {
        //do nothing. Is handeled somewhere localy
    } else if (data.type === 'refreshDashboard') {
        refreshDashboard();
    } else if (data.type === 'endMeeting') {
        endMeeting(data)
    } else if (data.type === 'reload') {
        setTimeout(function () {
            location.reload();

        }, data.timeout)
    } else if (data.type === 'call') {
        callAddhock(data);
    } else {
        alert('Error, Please reload the page')
    }
}


function notifymoderator(data) {
    showPush(data);
    setSnackbar(data.message, data.color, false, data.messageId);
    $('.dragger').addClass('active');

    $('#sliderTop')
        .addClass('notification')
        .css('transform', 'translateY(0px)')
        .mouseover(function (e) {
            $('.dragger').removeClass('active');
            $('#sliderTop')
                .removeClass('notification')
            $('#sliderTop').css('transform', 'translateY(-' + $('#col-waitinglist').outerHeight() + 'px)');
        })
}


function refresh(data) {
    var reloadUrl = data.reloadUrl;

    $('#waitingUserWrapper').load(reloadUrl, function () {
        const exampleEl = document.querySelectorAll('[data-mdb-toggle="popover"]');
        if (exampleEl.length > 0){
            for (var prop in exampleEl){
                const popover = new mdb.Popover(exampleEl[prop])
            }
        }

        if (!$('#sliderTop').hasClass('notification')) {
            $('#sliderTop').css('transform', 'translateY(-' + $('#col-waitinglist').outerHeight() + 'px)');
        }
        initCircle();
        countParts();
        initDragParticipants();
    });
}

/*
wenn ein Meeting komplett durch den Moderator beendet wird:
Sende ein loadModal an alle teilnehmer mit der INformation warum es geshclossenwird
sende ein End-Meeting an alle Teilneher.
 in der Funktion der Teilnehmer wird nochmal ein hangup ausglöst und somit die Konferenz aufgelegt.
*/
function endMeeting(data) {
    initStarSend();
}

function loadModal(data) {

    $('#loadContentModal').html(data.content).modal('show');
}


function redirect(data) {
    setTimeout(function () {
        close();
        window.location.href = data.url;
    }, data.timeout)

}

function countParts() {
    $('#lobbyCounter').text($('.waitingUserCard').length);
}

function showPush(data) {
    setTimeout(function () {
        TabUtils.lockFunction('notification' + data.messageId, function () {
            var audio = new Audio(notificationSound);
            audio.play();
            if (document.visibilityState === 'hidden') {
                Push.create(data.title, {
                    body: data.pushNotification,
                    icon: '/favicon.ico',
                    onClick: function (ele) {
                        window.focus();
                        this.close();
                    }
                });
            }
        }, 2500)
    }, Math.floor(Math.random() * 50) + 50);
}

function callAddhock(data) {
    setTimeout(function () {
        TabUtils.lockFunction('notification' + data.messageId, function () {
            callersoundplay.play()
            setTimeout(function () {
                callersoundplay.pause();
                callersoundplay.currentTime = 0;
            }, data.time)

            Push.create(data.title, {
                body: data.pushMessage,
                icon: '/favicon.ico',
                onClick: function (ele) {
                    window.focus();
                    this.close();
                }
            });

        }, 5000)
    }, Math.floor(Math.random() * 50) + 50);
    setSnackbar(data.message, data.color, true);
}

function stopCallerPlay() {
    callersoundplay.pause();
    callersoundplay.currentTime = 0;
}

export {masterNotify, initNotofication, stopCallerPlay}
