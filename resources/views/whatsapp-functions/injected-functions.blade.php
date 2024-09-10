window.WAPIWU = {};

// Guarda os eventos do whatsapp
window.WAPIWU.webhookEvents = {};

// Função para buscar informações de um grupo
window.WAPIWU.findGroupInfo = async (groupId) => {
    try {
        // Busca as informações do grupo
        let groupMetadata = await window.WAPIWU.getGroupMetadata(groupId);

        // Verifica se é grupo
        let participants = await window.WAPIWU.getGroupParticipants(groupId);

        // Adiciona os participantes no metadata do grupo
        groupMetadata.participants = participants;
        groupMetadata.inviteCode = (await window.WAPIWU.getGroupInviteLink(groupId)).link;

        return { success: true, message: 'Grupo encontrado com sucesso.', metadata: groupMetadata };
    } catch (error) {
        return { success: false, message: "Erro ao buscar as informações do grupo" };
    }
};

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

        return { success: true, groups: window.WAPIWU.groups, message: "Grupos encontrados com sucesso" };
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
 * Setar uma propriedade do grupo
 * 
 * @param {*} groupId
 * @param {*} property
 * @param {*} value
 */
window.WAPIWU.setGroupProperty = async (groupId, property, value) => {
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
        if(property == 2) {
            value = value ? 604800 : 0;
        }

        var setJobConfigGroup = require("WAWebGroupModifyInfoJob");

        var WAWebWidFactoryLocal = require("WAWebWidFactory");

        var group = WAWebWidFactoryLocal.createWid(groupId);

        var response = await setJobConfigGroup.setGroupProperty(
            group,
            types[property],
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

// Monta o link preview
window.WAPIWU.getLinkPreview = async (url) => {
    // Pega query
    var query = require("WAWebMexFetchPlaintextLinkPreviewJobQuery.graphql");

    // Pega a url
    var urlQuery = {
        "input": {
            "url": url
        }
    };

    var WAWebMexNativeClient = require('WAWebMexNativeClient');
    var response = await WAWebMexNativeClient.fetchQuery(query, urlQuery);
    var content  = response.xwa2_newsletter_link_preview;

    return {
        "matchedText": url,
        "title": content.title,
        "description": content.description,
        "richPreviewType": 0,
        "doNotPlayInline": true,
        "isLoading": false,
        "thumbnail": content.thumb_data
    };
}

// Monta a lista de mentions
window.WAPIWU.mountAllMention = async (chatId) => {
    // Busca os participantes do grupo
    var participants = await window.WAPIWU.getGroupParticipants(chatId);
    var WAWebWidFactoryLocal = require("WAWebWidFactory");
    
    // Inicializa a lista de mentions
    var mentions = [];

    // Monta a lista a ser mencionada
    participants.participants.forEach((contact) => {
        mentions.push(WAWebWidFactoryLocal.createWid(contact));   
    })

    return mentions;
}

// Enviar uma mensagem para um chat
window.WAPIWU.sendText = async (chatId, text, mention = false) => {
    try {
        var sendMessageText = require("WAWebSendTextMsgChatAction");

        // Busca as informações de todos os grupos
        var webCollection = require("WAWebCollections");

        // Envia o texto
        var response = await sendMessageText.sendTextMsgToChat(
            webCollection.Chat.get(chatId),
            text,
            {
                "linkPreview": null,
                "mentionedJidList": mention ? await window.WAPIWU.mountAllMention(chatId) : [],
                "groupMentions": [],
                "botMsgBodyType": null
            }
        );

        // Verifica se deu sucesso
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
            message: `Erro ao enviar catch: ${error.message}`,
            chatId: chatId,
            text: text,
        };
    }
};

// Enviar um linkpreview para um chat
window.WAPIWU.sendLinkPreview = async (chatId, text, link) => {
    try {
        var sendMessageText = require("WAWebSendTextMsgChatAction");

        // Busca as informações de todos os grupos
        var webCollection = require("WAWebCollections");

        // Response
        var response = await sendMessageText.sendTextMsgToChat(
            webCollection.Chat.get(chatId),
            text,
            {
                "linkPreview": await window.WAPIWU.getLinkPreview(link),
                "mentionedJidList": [],
                "groupMentions": [],
                "botMsgBodyType": null
            }
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
            message: `Erro ao enviar catch: ${error.message}`,
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
        'audio/ogg': 'ogg',
        'image/webp': 'webp'
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
        // Pega o arquivo
        var fileSend = document.querySelector(inputUsed).files[0];

        // Cria o arquivo para envio
        var createFromData = await require("WAWebMediaOpaqueData").createFromData(
            fileSend,
            fileSend.type
        );

        // Prepara o arquivo para envio
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

        // Envia a mensagem
        var addMsg = require("WAWebMsgCollection").MsgCollection.add(configsSend);
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

        return {
            success: success,
            message: success
                ? "Grupo criado com sucesso"
                : "Erro ao criar grupo",
            metadata: {
                id: response._serialized,
            },
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

    // Caso o qrcode tenha vencido faz o reload
    var selectorImg = document.querySelector('canvas');
    var selectorUrl = selectorImg.closest('[data-ref]');
    var buttonReload = selectorUrl.querySelector('button');
    if (buttonReload != null) {
        buttonReload.click();
    }

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

// Função para enviar enquete
window.WAPIWU.sendPoll = async (chatId, title, options, selectableCount = 0) => {
    try {
        // Monta as variaveis
        var webCollection = require('WAWebCollections');
        var poll = {
            name: title,
            options: options,
            selectableOptionsCount: selectableCount
        };
        
        // Envia a enquete
        const response = await require('WAWebPollsSendPollCreationMsgAction').sendPollCreation({ poll: poll, chat: webCollection.Chat.get(chatId), quotedMsg: undefined });

        // Verifica se deu sucesso
        const success = response[1].messageSendResult === "OK";

        return {success: success, message: (success ? "Enquete enviada com sucesso" : "Erro ao enviar enquete")};
    } catch (error) {
        return { success: false, message: 'Erro ao enviar enquete', error: error.message };
    }
};

// Função para enviar enquete
window.WAPIWU.sendAudio = async (chatId, inputUsed) => {
    try {
        // Pega o arquivo
        var fileSend = document.querySelector(inputUsed).files[0];

        // Busca as informações de todos os grupos
        var WAWebCollections = require('WAWebCollections')
        var chat = WAWebCollections.Chat.get(chatId)

        // Monta o MediaCollection
        var WAWebAttachMediaCollection = require('WAWebAttachMediaCollection')
        var WAWebAttachMediaCollection = new WAWebAttachMediaCollection({chatParticipantCount: chat.getParticipantCount()});

        // Prepara o arquivo para envio
        var files = [{
            "file": fileSend,
            "filename": `${window.WAPIWU.generateRandomCode(10)}.ogg`,
            "mimetype": "audio/ogg",
            "type": "audio"
        }]

        // Processa o arquivo
        await WAWebAttachMediaCollection.processAttachments(files, 1, chat)

        // Pega o media
        var media = WAWebAttachMediaCollection.getModelsArray()[0];
        media.mediaPrep._mediaData.type = 'ptt';
        
        // Envia o arquivo
        const response = await media.mediaPrep.sendToChat(chat, {})

        // Verifica se deu sucesso
        const success = response.messageSendResult === "OK";

        return {success: success, message: (success ? "Áudio enviado com sucesso" : "Erro ao enviar áudio")};
    } catch (error) {
        return { success: false, message: 'Erro ao enviar áudio', error: error.message };
    }
};

// Busca o número de telefone
window.WAPIWU.getPhoneNumber = async () => {
    try {
        var phoneNumber = require('WAWebUserPrefsMeUser').getMe().user;

        return { success: true, phoneNumber: phoneNumber, message: 'Número de telefone encontrado' };
    } catch (error) {
        return { success: false, message: 'Erro ao buscar o número de telefone', error: error.message };
    }
};

// Inicia a sessão
window.WAPIWU.startSession = async () => {
    try {
        return { success: true, message: 'Sessão iniciada com sucesso' };
    } catch (error) {
        return { success: false, message: 'Erro ao iniciar a sessão', error: error.message };
    }
};

// Função para promover um participante
window.WAPIWU.promoteParticipants = async (groupId, number) => { 
    try {
        // Seta as variáveis
        var WAWebModifyParticipantsGroupAction = require("WAWebModifyParticipantsGroupAction");
        var groupInfo = require("WAWebGroupMetadataCollection").assertGet(groupId);

        // Pega o contato
        var collections = require('WAWebCollections')
        var contact     = groupInfo.participants.get(number);
        var group       = collections.Chat.get(groupId);

        // Verifica se o participante é admin
        if(contact.__x_isAdmin) {
            return { success: true, message: 'Participante é admin'};      
        }

        // promover o participante
        await WAWebModifyParticipantsGroupAction.promoteParticipants(group, [contact]);

        return { success: true, message: 'Participante promovido com sucesso'};
    } catch (error) {
        return { success: false, message: 'Erro ao promover o participante', error: error.message };
    }
};

// Função para despromover um participante
window.WAPIWU.demoteParticipants = async (groupId, number) => { 
    try {
        // Seta as variáveis
        var WAWebModifyParticipantsGroupAction = require("WAWebModifyParticipantsGroupAction");
        var groupInfo = require("WAWebGroupMetadataCollection").assertGet(groupId);

        // Pega o contato
        var collections = require('WAWebCollections')
        var contact     = groupInfo.participants.get(number);
        var group       = collections.Chat.get(groupId);

        // Verifica se o participante é admin
        if(!contact.__x_isAdmin) {
            return { success: true, message: 'Participante não é admin'};      
        }

        // Despromove o participante
        await WAWebModifyParticipantsGroupAction.demoteParticipants(group, [contact]);

        return { success: true, message: 'Participante despromovido com sucesso'};
    } catch (error) {
        return { success: false, message: 'Erro ao despromover o participante', error: error.message };
    }
};

// Função para promover um participante
window.WAPIWU.addParticipant = async (groupId, number) => { 
    try {
        // Seta as variáveis
        var WAWebModifyParticipantsGroupAction = require("WAWebModifyParticipantsGroupAction");
        var groupInfo = require("WAWebGroupMetadataCollection").assertGet(groupId);

        // Pega o contato
        var collections = require('WAWebCollections')
        var contact     = groupInfo.participants.get(number);
        var group       = collections.Chat.get(groupId);

        // Verifica se o participante é admin
        if(contact) {
            return { success: true, message: 'Participante está adicionado'};      
        }

        // Pega o contato
        contact = collections.Contact.get(number);

        // promover o participante
        await WAWebModifyParticipantsGroupAction.addParticipants(group, [contact]);

        return { success: true, message: 'Participante Adicionado com sucesso'};
    } catch (error) {
        return { success: false, message: 'Erro ao adicionar o participantes', error: error.message, groupId: groupId, number: number };    
    }
};

// Função para promover um participante
window.WAPIWU.removeParticipant = async (groupId, number) => { 
    try {
        // Seta as variáveis
        var WAWebModifyParticipantsGroupAction = require("WAWebModifyParticipantsGroupAction");
        var groupInfo = require("WAWebGroupMetadataCollection").assertGet(groupId);

        // Pega o contato
        var collections = require('WAWebCollections')
        var contact     = groupInfo.participants.get(number);
        var group       = collections.Chat.get(groupId);

        // Verifica se o participante é admin
        if(!contact) {
            return { success: true, message: 'Participante não está adicionado'};      
        }

        // promover o participante
        await WAWebModifyParticipantsGroupAction.removeParticipants(group, [contact]);

        return { success: true, message: 'Participante removido com sucesso'};
    } catch (error) {
        console.log(error);
        return { success: false, message: 'Erro ao remover o participante', error: error };
    }
};

// Seta os eventos para enviar para o webhook
window.WAPIWU.setWebhookEvent = async function (events) {
    events.forEach(event => {
        console.log('event', event);
        switch(event.subtype) {
            case "leave":
            case "invite":
            case "message":
                window.WAPIWU.webhookEvents[event.id.id] = event;
                break;
        }
    });
};

// Gera um código sempre aleatório
window.WAPIWU.generateRandomCode = (length = 22) => {
    const characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'; // Conjunto de caracteres permitidos
    let code = '';
  
    for (let i = 0; i < length; i++) {
      // Seleciona um caractere aleatório do conjunto
      const randomIndex = Math.floor(Math.random() * characters.length);
      code += characters[randomIndex]; // Adiciona o caractere selecionado ao código
    }
  
    return code;
}

// Envia um vcard
window.WAPIWU.sendVcard = async (chatId, title, contact) => {
    try {
        // Busca o chat
        var webCollection = require('WAWebCollections');
        var chat          = webCollection.Chat.get(chatId);

        // Monta as informações de envio
        var dataUtil = require('WAWebMsgDataUtils') 
        var msg = await dataUtil.genOutgoingMsgData(chat, 'text/vcard');

        // Remove código anterior e adiciona o novo
        msg.id._serialized = msg.id._serialized.replace(msg.id.id, ':newcode');
        msg.id.id = window.WAPIWU.generateRandomCode(22);
        msg.id._serialized = msg.id._serialized.replace(':newcode', msg.id.id);

        // Monta o vcard
        const msgData = {
            "type": "vcard",
            "vcardFormattedName": title,
            "body": `BEGIN:VCARD\nVERSION:3.0\nN:;${title};;;\nFN:${title}\nTEL;type=CELL;waid=${contact}:${contact}\nEND:VCARD`,
            "ack": 0,
            "from": msg.from,
            "id": msg.id,
            "local": true,
            "isNewMsg": true,
            "t": msg.t,
            "to": msg.to,
            "ephemeralDuration": 0
        }

        // Instância para o envio
        var WAWebSendMsgChatAction = require('WAWebSendMsgChatAction')
        const response = await WAWebSendMsgChatAction.addAndSendMsgToChat(chat, msgData);
        const result  = await response[1];
        const success = result.messageSendResult === "OK";

        return {success: success, message: (success ? "Vcard enviado com sucesso" : "Erro ao enviar vcard")};
    } catch (error) {
        return { success: false, message: 'Erro ao enviar vcard', error: error.message };
    }
}

// Transforma o arquivo em base64
window.WAPIWU.fileToBase64 = async (file) => {
    return new Promise((resolve, reject) => {
        const reader = new FileReader(); // Cria uma instância do FileReader

        // Define a função de callback para quando a leitura do arquivo estiver concluída
        reader.onload = () => {
            resolve(reader.result); // Retorna a string Base64 com o tipo MIME
        };

        // Define a função de callback para erros
        reader.onerror = (error) => {
            reject(error); // Rejeita a promessa com o erro
        };

        reader.readAsDataURL(file); // Inicia a leitura do arquivo como uma URL de dados (Base64)
    });
};

// Redimensiona a imagem
window.WAPIWU.resizeImage = async (file, sizes) => {
    const reader = new FileReader();
    
    // Ler o arquivo de imagem como Data URL usando Promises
    const dataUrl = await new Promise((resolve) => {
        reader.onload = (event) => resolve(event.target.result);
        reader.readAsDataURL(file);
    });

    const img = new Image();
    img.src = dataUrl;

    await new Promise((resolve) => (img.onload = resolve));

    const resizedFiles = await Promise.all(
        sizes.map((size) => {
            const canvas = document.createElement('canvas');
            const ctx = canvas.getContext('2d');

            // Calcula a nova largura e altura mantendo a proporção
            let width = img.width;
            let height = img.height;

            if (width > height) {
                if (width > size) {
                    height *= size / width;
                    width = size;
                }
            } else {
                if (height > size) {
                    width *= size / height;
                    height = size;
                }
            }

            // Define o tamanho do canvas
            canvas.width = width;
            canvas.height = height;

            // Desenha a imagem redimensionada no canvas
            ctx.drawImage(img, 0, 0, width, height);

            // Determina o novo tipo de arquivo como JPEG
            const outputType = file.type === 'image/png' ? 'image/jpeg' : file.type;

            // Converte o canvas para Blob e cria um arquivo redimensionado
            return new Promise((resolve) =>
                canvas.toBlob(
                    (blob) => {
                        const resizedFile = new File(
                            [blob], 
                            `${size}x${size}_${file.name.replace(/\.[^.]+$/, '.jpg')}`, // Substitui a extensão por .jpg
                            {
                                type: outputType,
                                lastModified: Date.now(),
                            }
                        );
                        resolve(resizedFile);
                    },
                    outputType
                )
            );
        })
    );

    return resizedFiles;
};

// Altera a foto de um grupo
window.WAPIWU.changeGroupPhoto = async (chatId, inputUsed) => {
    try {
        // Pega o arquivo
        var fileSend = document.querySelector(inputUsed).files[0];

        // Redimensiona a imagem
        var filesResized = await window.WAPIWU.resizeImage(fileSend, [96, 640]);

        // Transforma o arquivo em base64
        var file96  = await window.WAPIWU.fileToBase64(filesResized[0]);
        var file640 = await window.WAPIWU.fileToBase64(filesResized[1]);

        // Cria o wid do grupo
        var WAWebWidFactoryLocal = require("WAWebWidFactory");
        var groupWid = WAWebWidFactoryLocal.createWid(chatId);

        // Altera a foto do grupo
        var response = await require("WAWebContactProfilePicThumbBridge").sendSetPicture(groupWid, file96, file640);
        const success = response.status == 200;

        return {
            success: success,
            message: (success ? "Foto alterada com sucesso" : "Erro ao alterar a foto"),
        };
    } catch (error) {
        return {
            success: false,
            message: "Erro ao alterar a foto",
            error: error.message,
        };
    }
};

// Altera a foto de um grupo
window.WAPIWU.removeGroupPhoto = async (chatId) => {
    try {
        // Cria o wid do grupo
        var WAWebWidFactoryLocal = require("WAWebWidFactory");
        var groupWid = WAWebWidFactoryLocal.createWid(chatId);

        // Altera a foto do grupo
        var response = await require("WAWebContactProfilePicThumbBridge").requestDeletePicture(groupWid);
        const success = response.status == 200;

        return {
            success: success,
            message: (success ? "Foto removida com sucesso" : "Erro ao remover a foto"),
        };
    } catch (error) {
        return {
            success: false,
            message: "Erro ao remover a foto",
            error: error.message,
        };
    }
};

// Verifica se o número é válido ou não
window.WAPIWU.checkNumber = async (number) => {
    try {
        // Cria o wid do contato
        var WAWebWidFactoryLocal = require("WAWebWidFactory");
        var contactWid = WAWebWidFactoryLocal.createWid(number);

        // Verifica se o número é válido
        var isValid = await require("WAWebQueryExistsJob").queryWidExists(contactWid);
        var success = isValid == null ? false : true;

        return { success: success, message: (success ? "Número válido" : "Número inválido") };
    } catch (error) {
        return { success: false, message: "Erro ao verificar o número", error: error.message };
    }
};

// Função para fazer logout do whatsapp
window.WAPIWU.disconnect = async () => {
    try {
        // Faz o logout
        await require("WAWebSocketModel").Socket.logout();

        return { success: true, message: "Logout feito com sucesso" };
    } catch (error) {
        return { success: false, message: "Erro ao fazer logout", error: error.message };
    }
};

// Função para apagar uma mensagem
window.WAPIWU.deleteMessage = async function(chatId, msgId) {
   try {
        // Pega a mensagem a ser apagada 
        var WAWebMsgCollection = require("WAWebMsgCollection");
        var msg                = WAWebMsgCollection.MsgCollection.get(msgId)

        // Busca a informação do chat de envio
        var WAWebCollections = require("WAWebCollections");
        var chat = WAWebCollections.Chat.get(chatId);
            
        // Apaga a mensagem
        require('WAWebCmd').Cmd.sendRevokeMsgs(chat, {
                "type": "message",
                "list": [msg]
            }, {"clearMedia": true});
        const success = true;

        // Verifica se a mensagem foi apagada
        return { success: success, message: (success ? 'Mensagem apagada com sucesso' : 'Erro ao apagar a mensagem') };
   } catch (error) {
        return { success: false, message: 'Erro ao apagar a mensagem', error: error.message };
   }
};