global.frameId = null;
var blockPause = null;
var showBlockPause = false;
var jitsiC = null;

function initModeratorIframe(closeFkt, jitsiController = null) {
    jitsiC = jitsiController;
    window.addEventListener('message', function (e) {

        const decoded = JSON.parse(e.data);
        if (typeof decoded.scope !== 'undefined' && decoded.scope == "jitsi-admin-iframe") {
            window.parent.postMessage(JSON.stringify({type: 'ack', messageId: decoded.messageId}), '*');
            if (decoded.type === 'init') {
                frameId = decoded.frameId;
                if (showBlockPause) {
                    showPlayPause();
                }
            } else if (decoded.type === 'pleaseClose') {
                if (typeof decoded.frameId !== 'undefined') {
                    frameId = decoded.frameId;
                }
                closeFkt();
            }
        }
    });
}

function close(frameIdTmp) {
    if (inIframe()) {
        var id = frameIdTmp ? frameIdTmp : frameId
        if (id) {
            const message = JSON.stringify({
                type: 'closeMe',
                frameId: id
            });
            window.parent.postMessage(message, '*');
        }
    }
}

function showPlayPause() {
    if (inIframe()) {
        var id = frameId
        if (id) {
            const message = JSON.stringify({
                type: 'showPlayPause',
                frameId: id
            });
            window.parent.postMessage(message, '*');
        } else {
            showBlockPause = true;
        }
    }
}

function inIframe() {
    try {
        return window.self !== window.top;
    } catch (e) {
        return true;
    }
}

export {initModeratorIframe, close, showPlayPause, inIframe}
