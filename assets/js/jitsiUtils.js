/*
 * Welcome to your app's main JavaScript file!
 *
 */

import $ from 'jquery';

import('bootstrap');
import('popper.js');
global.$ = global.jQuery = $;
import('mdbootstrap');
import stc from 'string-to-color/index';
import {masterNotify} from './lobbyNotification'
import {initCircle} from './initCircle'
import {initWebcam, choosenId} from './cameraUtils'
import * as url from "url";

var api;
var participants;

function initJitsi(options, domain) {
    api = new JitsiMeetExternalAPI(domain, options);
    renewPartList()
    api.addListener('participantJoined', function (id, name) {
        renewPartList()
    });
    api.addListener('readyToClose', function (e) {
        endMeeting();
    })
    api.addListener('readyToClose',function (e) {
        api.dispose();
        window.location.href = '/';
    })
    $('#closeSecure').removeClass('d-none').click(function (e) {
        e.preventDefault();
        endMeeting();
        $.getJSON(($(this).attr('href')));

    })

}

function endMeeting() {
    participants = api.getParticipantsInfo();
    for (var i = 0; i < participants.length; i++) {
        api.executeCommand('kickParticipant', participants[i].participantId);
    }
    return 0;
}

function renewPartList() {
    participants = api.getParticipantsInfo();
}


export {initJitsi}