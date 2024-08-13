import {getQrcode} from './whatsapp-functions/getQrcode.js';
import {getAllGroups} from './whatsapp-functions/group/getAllGroups.js';
import {setGroupSubject} from './whatsapp-functions/group/setGroupSubject.js';
import {setGroupDescription} from './whatsapp-functions/group/setGroupDescription.js';
import {getGroupInviteLink} from './whatsapp-functions/group/getGroupInviteLink.js';
import {sendText} from './whatsapp-functions/chat/sendText.js';
import {sendFile} from './whatsapp-functions/chat/sendFile.js';
// import {setGroupProperty} 

export default function execFunction(action, sessionId, params = {}) {
    switch (action) {
        case 'getQrcode':
            return getQrcode(sessionId);
        case 'getAllGroups':
            return getAllGroups(sessionId);
        case 'sendText':
            return sendText(sessionId, params.chatId, params.text);
        case 'sendFile':
            return sendFile(sessionId, params.chatId, params.caption, params.filename);
        case 'getGroupInviteLink':
            return getGroupInviteLink(sessionId, params.groupId);
        case 'setGroupProperty':
            return null;
        case 'setGroupSubject':
            return setGroupSubject(sessionId, params.groupId, params.subject);
        case 'setGroupDescription':
            return setGroupDescription(sessionId, params.groupId, params.description);
        default:
            return null;
    }
}
