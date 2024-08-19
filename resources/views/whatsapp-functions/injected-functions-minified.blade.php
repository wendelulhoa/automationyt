window.WAPIWU = {};

// Busca o metadata de um grupo
window.WAPIWU.getGroupMetadata = async (chatId) => {
    return await require("WAWebDBGroupsGroupMetadata").getGroupMetadata(chatId);
};

// Busca os participantes de um grupo
window.WAPIWU.getGroupParticipants = async (chatId) => {
    return await require("WAWebSchemaParticipant")
        .getParticipantTable()
        .get(chatId);
};

// Seta os grupos na variável global
window.WAPIWU.setGroups = async () => {
    window.WAPIWU.groups = [];

    try {
        // Busca as informações de todos os chats
        const chats = await require("WAWebSchemaChat").getChatTable().all();

        // Usa Promise.all para lidar com as funções assíncronas
        await Promise.all(
            chats.map(async (chat) => {
                let groupMetadata = await window.WAPIWU.getGroupMetadata(chat.id);

                // Verifica se é grupo
                if (groupMetadata) {
                    let participants = await window.WAPIWU.getGroupParticipants(chat.id);

                    // Adiciona os participantes no metadata do grupo
                    groupMetadata.participants = participants;

                    // Adiciona o grupo na variável global
                    window.WAPIWU.groups.push(groupMetadata);
                }
            })
        );
    } catch (error) {
        console.error("Erro ao setar os grupos:", error);
    }
};

// Busca todos os grupos
window.WAPIWU.getAllGroups = async () => {
    try {
        await window.WAPIWU.setGroups();

        return { success: true, groups: window.WAPIWU.groups };
    } catch (error) {
        return { success: false, message: "Erro ao buscar os grupos" };
    }
};

// Setar o título de um grupo
window.WAPIWU.setGroupSubject = async (groupId, subject) => {
    try {
        var setJobConfigGroup = require("WAWebGroupModifyInfoJob");
        var WAWebWidFactoryLocal = require("WAWebWidFactory");
        var group = WAWebWidFactoryLocal.createWid(groupId);

        // Seta o título do grupo
        await setJobConfigGroup.setGroupSubject(group, subject);

        // Tentar buscar o metadata no máximo 5 vezes
        let attempts = 0;
        let success = false;
        let metadata;

        while (attempts < 5 && !success) {
            attempts++;
            await window.WAPIWU.sleep(500); // Espera 0.5 segundos

            // Busca as informações do grupo
            metadata = await window.WAPIWU.getGroupMetadata(groupId);
            success = metadata.subject.trim() === subject.trim();
        }

        return { success: success, message: (success ? "Alterado com sucesso" : "Erro ao alterar") };
    } catch (error) {
        return { success: false, message: "Erro ao alterar", error: error };
    }
};

// Seta a descrição de um grupo
window.WAPIWU.setGroupDescription = async (groupId, description) => {
    try {
        // Seta as variáveis
        var setJobConfigGroup = require("WAWebGroupModifyInfoJob");
        var WAWebWidFactoryLocal = require("WAWebWidFactory");
        var group = WAWebWidFactoryLocal.createWid(groupId);

        var c = await require("WAWebDBGroupsGroupMetadata").getGroupMetadata(groupId);

        await setJobConfigGroup.setGroupDescription(
            group,
            description,
            require("WAHex").randomHex(8),
            c.descId
        );

        // Tentar buscar o metadata no máximo 5 vezes
        let attempts = 0;
        let success = false;
        let metadata;

        while (attempts < 5 && !success) {
            attempts++;
            await window.WAPIWU.sleep(500); // Espera 0.5 segundos

            // Busca as informações do grupo
            metadata = await window.WAPIWU.getGroupMetadata(groupId);
            success = metadata.desc.trim() === description.trim();
        }

        return { success: success, message: (success ? "Alterado com sucesso" : "Erro ao alterar") };
    } catch (error) {
        return { success: false, message: "Erro ao alterar" };
    }
};

/**
 * Setar uma propriedade de um grupo
 * 86400/24 h 604800/7
 * @param {*} groupId
 * @param {*} type
 * @param {*} value
 */
window.WAPIWU.setGroupProperty = async (groupId, type, value) => {
    try {
        const constantsType = require("WAWebGroupConstants");
        const types = {
            1: constantsType.GROUP_SETTING_TYPE.ANNOUNCEMENT,
            2: constantsType.GROUP_SETTING_TYPE.EPHEMERAL,
            3: constantsType.GROUP_SETTING_TYPE.RESTRICT,
            4: constantsType.GROUP_SETTING_TYPE.ALLOW_NON_ADMIN_SUB_GROUP_CREATION,
            5: constantsType.GROUP_SETTING_TYPE.MEMBERSHIP_APPROVAL_MODE,
            6: constantsType.GROUP_SETTING_TYPE.NO_FREQUENTLY_FORWARDED,
            7: constantsType.GROUP_SETTING_TYPE.REPORT_TO_ADMIN_MODE,
        };

        // Se for ephemeral, multiplica por 86400
        if(type == 2) {
            value = 604800;
        }

        var setJobConfigGroup = require("WAWebGroupModifyInfoJob");

        var WAWebWidFactoryLocal = require("WAWebWidFactory");

        var group = WAWebWidFactoryLocal.createWid(groupId);

        var response = await setJobConfigGroup.setGroupProperty(
            group,
            types[type],
            value
        );

        return {
            success: response == "SetPropertyResponseSuccess",
            message: "Alterado com sucesso",
        };
    } catch (error) {
        return { success: false, message: "Erro ao alterar" };
    }
};

// Enviar uma mensagem para um chat
window.WAPIWU.sendTextMsgToChat = async (chatId, text) => {
    try {
        var sendMessageText = require("WAWebSendTextMsgChatAction");

        // Busca as informações de todos os grupos
        var webCollection = require("WAWebCollections");

        // Response
        var response = await sendMessageText.sendTextMsgToChat(
            webCollection.Chat.get(chatId),
            text,
            {}
        );
        var success = response.messageSendResult == "OK";

        return {
            success: success,
            message: success
                ? "Enviado com sucesso"
                : `Erro ao enviar: ${response}`,
            chatId: chatId,
            text: text,
            response: response,
        };
    } catch (error) {
        return {
            success: false,
            message: `Erro ao enviar catch: ${error}`,
            chatId: chatId,
            text: text,
        };
    }
};

// Cria um link de convite para um grupo
window.WAPIWU.getGroupInviteLink = async (groupId) => {
    try {
        var codeInvite = await require("WAWebMexFetchGroupInviteCodeJob").fetchMexGroupInviteCode(
                groupId
            );

        // Verifica se o código foi gerado
        var success = codeInvite != null;

        return {
            success: success,
            message: (success ? "Link gerado com sucesso" : "Erro ao gerar link"),
            link: `https://chat.whatsapp.com/${codeInvite}`,
        };
    } catch (error) {
        return {
            success: false,
            message: "Erro ao enviar catch",
            error: error,
            groupId: groupId,
        };
    }
};

// Função que retorna as configurações para enviar o arquivo
window.WAPIWU.getConfigsSend = async (chatId, prepRawMedia, caption = "", fileSend) => {
    // Busca as informações de todos os grupos
    var collections = require("WAWebCollections");
    var chat = collections.Chat.get(chatId);
    var dataUtil = require("WAWebMsgDataUtils");
    var msg = await dataUtil.genOutgoingMsgData(chat, fileSend.type);

    var typesFile = {
        "image/jpeg": "image",
        "image/png": "image",
        "image/gif": "gif",
        "video/webm": "video",
        "video/mp4": "video",
    };

    msg.body = prepRawMedia._mediaData.preview;
    msg.filehash = prepRawMedia._mediaData.filehash;
    msg.type = typesFile[fileSend.type];
    msg.agentId = undefined;
    msg.isNewMsg = true;
    msg.local = true;
    msg.ack = 0;
    msg.caption = caption;
    msg.mentionedJidList = [];
    msg.groupMentions = [];
    msg.ephemeralSettingTimestamp = null;
    msg.disappearingModeInitiator = "chat";
    msg.size = fileSend.size;
    msg.isVcardOverMmsDocument = false;
    msg.filename = undefined;
    msg.mimetype = fileSend.type;
    msg.isViewOnce = false;
    msg.ctwaContext = undefined;
    msg.ephemeralDuration = 604800;
    msg.footer = undefined;
    msg.forwardedFromWeb = undefined;
    msg.forwardedNewsletterMessageInfo = undefined;
    msg.forwardingScore = undefined;
    msg.gifAttribution = undefined;
    msg.height = 187;
    msg.width = 198;
    msg.streamingSidecar = undefined;
    msg.isAvatar = undefined;
    msg.isForwarded = undefined;
    msg.isGif = undefined;
    msg.messageSecret = undefined;
    msg.pageCount = undefined;
    msg.quotedMsg = undefined;
    msg.quotedParticipant = undefined;
    msg.quotedRemoteJid = undefined;
    msg.quotedStanzaID = undefined;
    msg.staticUrl = "";
    msg.subtype = undefined;

    return msg;
};

// Faz o envio do arquivo
window.WAPIWU.sendFile = async (chatId, caption, inputUsed) => {
    try {
        var fileSend = document.querySelector(inputUsed).files[0];

        // Cria o arquivo para envio
        var createFromData =
            await require("WAWebMediaOpaqueData").createFromData(
                fileSend,
                fileSend.type
            );
        var prepRawMedia = await require("WAWebMedia").prepRawMedia(
            createFromData,
            {}
        );
        var objMediaData = prepRawMedia._mediaData;

        // Cria o objeto para envio
        objMediaData.set({ mediaPrep: prepRawMedia });

        // Busca a informação do chat de envio
        var WAWebCollections = require("WAWebCollections");
        var chat = WAWebCollections.Chat.get(chatId);

        // Envia o arquivo
        var configsSend = await window.WAPIWU.getConfigsSend(
            chatId,
            prepRawMedia,
            caption,
            fileSend
        );
        var addMsg =
            require("WAWebMsgCollection").MsgCollection.add(configsSend);
        var response = await objMediaData.mediaPrep.sendToChat(chat, addMsg[0]);
        var success = response.messageSendResult == "OK";

        return {
            success: success,
            message: success
                ? "Arquivo enviado com sucesso"
                : "Erro ao enviar o arquivo",
            response: response,
        };
    } catch (error) {
        return {
            success: false,
            message: "Erro ao enviar o arquivo a",
            error: error.message,
        };
    }
};

// Cria um novo grupo
window.WAPIWU.createGroup = async (name, participants = []) => {
    try {
        // Ação que irá criar o grupo
        var createGroup = require("WAWebCreateGroupAction");

        // Configurações do grupo
        const configs = {
            title: name,
            ephemeralDuration: 0,
            restrict: false,
            announce: false,
            membershipApprovalMode: false,
            memberAddMode: false,
        };

        // Cria o grupo
        const response = await createGroup.createGroup(configs, participants);
        const success = response.server == "g.us";

        // Busca as informações de um grupo
        const metadata = await require("WAWebDBGroupsGroupMetadata").getGroupMetadata(response._serialized);

        return {
            success: success,
            message: success
                ? "Grupo criado com sucesso"
                : "Erro ao criar grupo",
            metadata: metadata,
        };
    } catch (error) {
        return { success: false, message: "Erro ao criar grupo", error: error };
    }
};

// Função de utilidade para pausar a execução
window.WAPIWU.sleep = async (ms) => {
    return new Promise(resolve => setTimeout(resolve, ms));
}

// Pega o qr code
window.WAPIWU.getQrCode = async () => {
    const maxWaitTime = 30000; // 30 segundos
    const interval = 1000; // 1 segundo
    let elapsedTime = 0;

    while (elapsedTime < maxWaitTime) {
        try {
            var path = `//*/div/div[2]/div[3]/div[1]/div/div/div[2]/div`;
            var elQrcode = document.evaluate(path, document, null, XPathResult.FIRST_ORDERED_NODE_TYPE, null).singleNodeValue;
            var qrCode = elQrcode ? elQrcode.getAttribute('data-ref') : null;

            // Retorna o qr code
            if (qrCode !== null) {
                return { success: true, qrCode: qrCode, message: 'QR gerado com sucesso' };
            }

            await window.WAPIWU.sleep(interval);
            elapsedTime += interval;
        } catch (error) {
            return { success: false, error: error.message, qrCode: null};
        }
    }

    return { success: false, error: 'QR code não encontrado em 30 segundos', qrCode: null };
}

// Desativa os autodownloads
window.WAPIWU.disableAutoDownloads = () => {
    var permissionDownload = require("WAWebUserPrefsGeneral");

    // Desativa o download automático
    permissionDownload.setAutoDownloadPhotos(false);
    permissionDownload.setAutoDownloadAudio(false);
    permissionDownload.setAutoDownloadVideos(false);
    permissionDownload.setAutoDownloadDocuments(false);

    return true;
};

// Desativa todos autodownloads
window.WAPIWU.disableAutoDownloads();

// Adiciona um inputfile no body
window.WAPIWU.addInputFile = (nameInput) => {
    var input = document.createElement('input');
    input.type = 'file';
    input.name = `${nameInput}`;
    input.dataset[`${nameInput}`] = `${nameInput}`
    input.dataset[`namedata`] = `${nameInput}`
    input.style.display = 'none'; // Ocultar o input
    document.body.appendChild(input);

    return { success: true, message: 'Input adicionado com sucesso' };
};

// Remove inputfile do body
window.WAPIWU.removeInputFile = (nameInput) => {
    var input = document.querySelector(`input[data-${nameInput}="${nameInput}"]`);
    input.remove();

    return { success: true, message: 'Input removido com sucesso' };
};

// Função para checar a conexão
window.WAPIWU.checkConnection = async () => {
    try {
        var WAWebStreamModel = require('WAWebStreamModel');
        var success = WAWebStreamModel.Stream.__x_displayInfo == 'NORMAL'

        return { success: success, message: success ? 'Conexão OK' : 'Erro na conexão', status: WAWebStreamModel.Stream.__x_displayInfo};
    } catch (error) {
        return { success: false, message: 'Erro na conexão', error: error.message, status: null};
    }
};
